<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bumxpress.php';
admin_required();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/admin/retrait.php');
require_csrf();

$first = trim((string) ($_POST['first_name'] ?? ''));
$last = trim((string) ($_POST['last_name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$phoneInput = trim((string) ($_POST['phone'] ?? ''));
$operator = (string) ($_POST['operator'] ?? '');
$budgetAmount = filter_var($_POST['amount'] ?? null, FILTER_VALIDATE_INT);
$digits = preg_replace('/\D+/', '', $phoneInput) ?? '';
$phone = str_starts_with($phoneInput, '+') ? '+' . $digits : '+237' . ltrim($digits, '0');
if ($first === '' || $last === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || !preg_match('/^\+237[2368]\d{7,8}$/', $phone) || !in_array($operator, ['orange', 'mtn'], true)
    || !$budgetAmount || $budgetAmount < 203 || $budgetAmount > 400000) {
    flash('Vérifiez le bénéficiaire, son numéro, l’opérateur et un budget compris entre 203 et 400 000 FCFA.', 'error');
    redirect('/admin/retrait.php');
}
$sendAmount = payout_net_amount((int) $budgetAmount);
if ($sendAmount < 200) {
    flash('Le montant net après frais doit être d’au moins 200 FCFA.', 'error');
    redirect('/admin/retrait.php');
}
if (!bumxpress_is_configured()) {
    flash('Les identifiants Bumxpress ou le secret webhook manquent.', 'error');
    redirect('/admin/retrait.php');
}

$publicRef = 'P-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
$name = trim($first . ' ' . $last);
$pdo = db();
$pdo->prepare("INSERT INTO withdrawals (public_ref, recipient_name, recipient_phone, recipient_email, operator_code, amount, budget_amount, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'initiating', ?)")
    ->execute([$publicRef, $name, $phone, $email, bumxpress_operator($operator), $sendAmount, $budgetAmount, date('Y-m-d H:i:s')]);
$withdrawalId = (int) $pdo->lastInsertId();
try {
    $customer = create_bumxpress_customer($publicRef, $name, $email, $phone, 'payout_customer_' . $publicRef);
    $customerId = (string) ($customer['id'] ?? '');
    if ($customerId === '') throw new RuntimeException('Bumxpress n’a pas retourné l’identifiant du bénéficiaire.');
    $pdo->prepare('UPDATE withdrawals SET customer_id = ? WHERE id = ?')->execute([$customerId, $withdrawalId]);

    $quote = bumxpress_request('/quotes/payout', [
        'customer_id' => $customerId,
        'country_code' => 'CM',
        'operator_code' => bumxpress_operator($operator),
        'rail_type' => 'mobile_money',
        'send_amount' => (string) $sendAmount,
        'send_currency' => 'XAF',
    ], 'payout_quote_' . $publicRef);
    $quoteId = (string) ($quote['id'] ?? '');
    if ($quoteId === '') throw new RuntimeException('Bumxpress n’a pas retourné l’identifiant du devis.');
    $pdo->prepare('UPDATE withdrawals SET quote_id = ? WHERE id = ?')->execute([$quoteId, $withdrawalId]);

    $payout = bumxpress_request('/payouts', [
        'quote_id' => $quoteId,
        'merchant_reference' => $publicRef,
        'payment_reason_code' => 'OTHER',
        'payment_reason_text' => 'Retrait Forfait Pulse',
        'recipient' => [
            'recipient_type' => 'individual',
            'first_name' => $first,
            'last_name' => $last,
            'phone' => $phone,
            'email' => $email,
            'recipient_nationality_code' => 'CM',
        ],
    ], 'payout_' . $publicRef);
    $providerId = (string) ($payout['id'] ?? '');
    if ($providerId === '') throw new RuntimeException('Bumxpress n’a pas retourné l’identifiant du retrait.');
    $status = (string) ($payout['status'] ?? 'pending');
    $finalStatus = $status === 'completed' ? 'completed' : ($status === 'failed' ? 'failed' : 'pending');
    $pdo->prepare('UPDATE withdrawals SET provider_payout_id = ?, status = ?, completed_at = ? WHERE id = ?')
        ->execute([$providerId, $finalStatus, $finalStatus === 'completed' ? date('Y-m-d H:i:s') : null, $withdrawalId]);
    flash('Retrait envoyé à Bumxpress. Son état final sera confirmé par webhook.', 'success');
} catch (Throwable $exception) {
    error_log('Bumxpress payout creation failed for ' . $publicRef . ': ' . $exception->getMessage());
    $pdo->prepare("UPDATE withdrawals SET status = 'failed', failure_reason = ? WHERE id = ?")
        ->execute([substr($exception->getMessage(), 0, 255), $withdrawalId]);
    flash('Le retrait n’a pas pu être créé. Aucun succès ne sera affiché sans confirmation Bumxpress.', 'error');
}
redirect('/admin/retrait.php');
