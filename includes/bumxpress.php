<?php
declare(strict_types=1);

require_once __DIR__ . '/app.php';

function bumxpress_request(string $path, ?array $payload = null, ?string $idempotencyKey = null): array
{
    $key = env_value('BUMXPRESS_API_KEY');
    $secret = env_value('BUMXPRESS_API_SECRET');
    if ($key === '' || $secret === '') {
        throw new RuntimeException('L’intégration Bumxpress n’est pas configurée. Ajoutez la clé API et le secret dans l’environnement.');
    }
    if (!function_exists('curl_init')) {
        throw new RuntimeException('L’extension PHP cURL est requise pour contacter Bumxpress.');
    }

    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'X-Api-Key: ' . $key,
        'X-Api-Secret: ' . $secret,
    ];
    if ($idempotencyKey !== null) {
        $headers[] = 'Idempotency-Key: ' . $idempotencyKey;
    }

    $curl = curl_init(bumxpress_base_url() . $path);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    if ($payload !== null) {
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload, JSON_THROW_ON_ERROR));
    }
    $body = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($body === false) {
        throw new RuntimeException('Bumxpress ne répond pas : ' . ($curlError ?: 'erreur réseau'));
    }
    $decoded = json_decode($body, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Réponse Bumxpress illisible (HTTP ' . $status . ').');
    }
    if ($status < 200 || $status >= 300) {
        $message = $decoded['error']['message'] ?? $decoded['message'] ?? ('HTTP ' . $status);
        throw new RuntimeException('Bumxpress : ' . substr((string) $message, 0, 220));
    }
    return $decoded;
}

function bumxpress_operator(string $operator): string
{
    return match ($operator) {
        'orange' => 'CM_ORANGE_MONEY',
        'mtn' => 'CM_MTN_MOMO',
        default => throw new InvalidArgumentException('Choisissez Orange Money ou MTN Mobile Money.'),
    };
}

function create_bumxpress_customer(
    string $externalRef,
    string $name,
    string $email,
    string $phone,
    string $idempotencyKey
): array {
    $parts = preg_split('/\s+/', trim($name), 2);
    $firstName = $parts[0] ?? '';
    $lastName = $parts[1] ?? $firstName;
    return bumxpress_request('/customers', [
        'external_ref' => $externalRef,
        'customer_type' => 'individual',
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'phone' => $phone,
        'country_code' => 'CM',
    ], $idempotencyKey);
}

function create_bumxpress_payin(array $order, string $otpCode = ''): array
{
    $quote = (string) ($order['provider_quote_id'] ?? '');
    if ($quote === '') {
        throw new RuntimeException('Le devis Bumxpress de cette commande est introuvable.');
    }
    $payload = [
        'quote_id' => $quote,
        'merchant_reference' => $order['public_ref'],
        'merchant_metadata' => ['order_ref' => $order['public_ref']],
        'source' => ['phone' => $order['payment_phone'] ?? $order['customer_phone']],
    ];
    if ($otpCode !== '') {
        $payload['otp_code'] = $otpCode;
    }
    return bumxpress_request('/payins', $payload, 'payin_' . $order['public_ref']);
}

function sync_order_from_provider(array $order): array
{
    if (empty($order['provider_payin_id']) || !in_array($order['payment_status'], ['pending', 'initiating'], true)) {
        return $order;
    }
    try {
        $provider = bumxpress_request('/payins/' . rawurlencode((string) $order['provider_payin_id']));
        $status = $provider['status'] ?? '';
        $amountMatches = isset($provider['send_amount'])
            && (float) $provider['send_amount'] === (float) $order['amount']
            && ($provider['send_currency'] ?? '') === 'XAF'
            && ($provider['operator_code'] ?? '') === bumxpress_operator((string) $order['payment_operator']);
        if ($status === 'completed' && $amountMatches) {
            db()->prepare("UPDATE orders SET payment_status = 'paid', paid_at = ? WHERE id = ?")
                ->execute([date('Y-m-d H:i:s'), $order['id']]);
            $order['payment_status'] = 'paid';
            $order['paid_at'] = date('Y-m-d H:i:s');
        } elseif ($status === 'failed') {
            db()->prepare("UPDATE orders SET payment_status = 'failed', failure_reason = ? WHERE id = ?")
                ->execute([substr((string) ($provider['failure_reason'] ?? 'Paiement refusé par le prestataire.'), 0, 255), $order['id']]);
            $order['payment_status'] = 'failed';
        }
        if (!empty($provider['provider_action'])) {
            $action = json_encode($provider['provider_action'], JSON_UNESCAPED_UNICODE);
            db()->prepare('UPDATE orders SET provider_action_json = ? WHERE id = ?')->execute([$action, $order['id']]);
            $order['provider_action_json'] = $action;
        }
    } catch (Throwable $exception) {
        error_log('Bumxpress payin status check failed for ' . ($order['public_ref'] ?? 'unknown') . ': ' . $exception->getMessage());
    }
    return $order;
}
