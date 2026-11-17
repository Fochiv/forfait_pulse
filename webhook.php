<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/app.php';

function webhook_header(string $wanted): string
{
    foreach (function_exists('getallheaders') ? getallheaders() : [] as $name => $value) {
        if (strcasecmp((string) $name, $wanted) === 0) {
            return trim((string) $value);
        }
    }
    $serverName = 'HTTP_' . strtoupper(str_replace('-', '_', $wanted));
    return trim((string) ($_SERVER[$serverName] ?? ''));
}

function valid_bumxpress_signature(string $secret, string $timestamp, string $rawBody, string $signature): bool
{
    if ($secret === '' || !preg_match('/^sha256=[a-f0-9]{64}$/i', $signature)) {
        return false;
    }
    $expected = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $rawBody, $secret);
    return hash_equals($expected, $signature);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed');
}

$rawBody = file_get_contents('php://input');
$timestamp = webhook_header('X-Bumxpress-Timestamp');
$signature = webhook_header('X-Bumxpress-Signature');
$previousSignature = webhook_header('X-Bumxpress-Signature-Previous');
$secret = env_value('BUMXPRESS_WEBHOOK_SECRET');
$previousSecret = env_value('BUMXPRESS_WEBHOOK_SECRET_PREVIOUS');

if ($rawBody === false || $timestamp === '' || !valid_bumxpress_signature($secret, $timestamp, $rawBody, $signature)
    && !valid_bumxpress_signature($previousSecret, $timestamp, $rawBody, $signature)
    && !valid_bumxpress_signature($secret, $timestamp, $rawBody, $previousSignature)
    && !valid_bumxpress_signature($previousSecret, $timestamp, $rawBody, $previousSignature)) {
    http_response_code($secret === '' ? 503 : 401);
    exit('Invalid webhook signature');
}

$event = json_decode($rawBody, true);
if (!is_array($event)) {
    http_response_code(400);
    exit('Invalid JSON');
}
$eventId = webhook_header('X-Bumxpress-Webhook-Id');
if ($eventId === '') {
    $eventId = (string) ($event['id'] ?? '');
}
if ($eventId === '') {
    http_response_code(400);
    exit('Missing event id');
}
$eventType = (string) ($event['event'] ?? webhook_header('X-Bumxpress-Event'));
$pdo = db();
$exists = $pdo->prepare('SELECT id FROM webhook_events WHERE event_id = ?');
$exists->execute([$eventId]);
if ($exists->fetchColumn()) {
    http_response_code(200);
    exit('Already processed');
}

$pdo->beginTransaction();
try {
    $pdo->prepare('INSERT INTO webhook_events (event_id, event_type, received_at, payload_hash) VALUES (?, ?, ?, ?)')
        ->execute([$eventId, $eventType, date('Y-m-d H:i:s'), hash('sha256', $rawBody)]);

    $object = $event['data']['object'] ?? [];
    $resourceId = (string) ($event['data']['id'] ?? $object['id'] ?? '');
    if (str_starts_with($eventType, 'payin.') && $resourceId !== '') {
        $find = $pdo->prepare('SELECT * FROM orders WHERE provider_payin_id = ?');
        $find->execute([$resourceId]);
        $order = $find->fetch();
        if ($order && in_array($eventType, ['payin.completed', 'payin.failed', 'payin.action_required'], true)) {
            if ($eventType === 'payin.completed') {
                $amountMatches = isset($object['send_amount'])
                    && (float) $object['send_amount'] === (float) $order['amount']
                    && ($object['send_currency'] ?? '') === 'XAF'
                    && ($object['operator_code'] ?? '') === bumxpress_operator($order['payment_operator']);
                if ($amountMatches) {
                    $pdo->prepare("UPDATE orders SET payment_status = 'paid', paid_at = ?, failure_reason = NULL WHERE id = ? AND payment_status IN ('pending','initiating')")
                        ->execute([date('Y-m-d H:i:s'), $order['id']]);
                } else {
                    error_log('Bumxpress payin amount/operator mismatch for order ' . $order['public_ref']);
                }
            } elseif ($eventType === 'payin.failed') {
                $reason = (string) ($object['failure_reason'] ?? $object['reason'] ?? 'Paiement refusé par le prestataire.');
                $pdo->prepare("UPDATE orders SET payment_status = 'failed', failure_reason = ? WHERE id = ? AND payment_status <> 'paid'")
                    ->execute([substr($reason, 0, 255), $order['id']]);
            } else {
                $action = $object['provider_action'] ?? null;
                $pdo->prepare('UPDATE orders SET provider_action_json = ? WHERE id = ? AND payment_status <> ?')
                    ->execute([$action ? json_encode($action, JSON_UNESCAPED_UNICODE) : null, $order['id'], 'paid']);
            }
        }
    } elseif (str_starts_with($eventType, 'payout.') && $resourceId !== '') {
        $find = $pdo->prepare('SELECT * FROM withdrawals WHERE provider_payout_id = ? OR public_ref = ? LIMIT 1');
        $merchantRef = (string) ($object['merchant_reference'] ?? '');
        $find->execute([$resourceId, $merchantRef]);
        $withdrawal = $find->fetch();
        if ($withdrawal && in_array($eventType, ['payout.completed', 'payout.failed'], true)) {
            $amountMatches = isset($object['send_amount'])
                && (float) $object['send_amount'] === (float) $withdrawal['amount']
                && ($object['send_currency'] ?? '') === 'XAF'
                && ($object['operator_code'] ?? '') === $withdrawal['operator_code'];
            if (!$amountMatches) {
                error_log('Bumxpress payout amount/operator mismatch for ' . $withdrawal['public_ref']);
            } elseif ($eventType === 'payout.completed') {
                $pdo->prepare("UPDATE withdrawals SET status = 'completed', completed_at = ? WHERE id = ? AND status IN ('pending','initiating')")
                    ->execute([date('Y-m-d H:i:s'), $withdrawal['id']]);
            } else {
                $reason = (string) ($object['failure_reason'] ?? $object['reason'] ?? 'Retrait refusé par le prestataire.');
                $pdo->prepare("UPDATE withdrawals SET status = 'failed', failure_reason = ? WHERE id = ? AND status <> 'completed'")
                    ->execute([substr($reason, 0, 255), $withdrawal['id']]);
            }
        }
    }
    $pdo->commit();
} catch (PDOException $exception) {
    $pdo->rollBack();
    if (str_contains(strtolower($exception->getMessage()), 'unique') || str_contains(strtolower($exception->getMessage()), 'duplicate')) {
        http_response_code(200);
        exit('Already processed');
    }
    error_log('Bumxpress webhook database failure: ' . $exception->getMessage());
    http_response_code(500);
    exit('Webhook processing failed');
} catch (Throwable $exception) {
    $pdo->rollBack();
    error_log('Bumxpress webhook processing failure: ' . $exception->getMessage());
    http_response_code(500);
    exit('Webhook processing failed');
}

http_response_code(200);
header('Content-Type: text/plain; charset=utf-8');
echo 'OK';
