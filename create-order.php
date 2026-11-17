<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bumxpress.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/forfaits.php');
}
require_csrf();

$bundleId = filter_var($_POST['bundle_id'] ?? null, FILTER_VALIDATE_INT);
$name = trim((string) ($_POST['customer_name'] ?? ''));
$email = trim((string) ($_POST['customer_email'] ?? ''));
$deliveryPhoneInput = trim((string) ($_POST['customer_phone'] ?? ''));
$paymentPhoneInput = trim((string) ($_POST['payment_phone'] ?? ''));
$paymentOperator = (string) ($_POST['payment_operator'] ?? '');
$deliveryPhone = normalize_cameroon_phone($deliveryPhoneInput);
$paymentPhone = normalize_cameroon_phone($paymentPhoneInput);

if (!$bundleId) {
    flash('Ce forfait est introuvable. Choisissez à nouveau un forfait.', 'error');
    redirect('/forfaits.php');
}
if (mb_strlen($name) < 2) {
    flash('Saisissez votre nom.', 'error');
    redirect('/checkout.php?id=' . (int) $bundleId);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash('Vérifiez votre adresse e-mail.', 'error');
    redirect('/checkout.php?id=' . (int) $bundleId);
}
if (!preg_match('/^\+237[2368]\d{7,8}$/', $deliveryPhone)) {
    flash('Vérifiez le numéro qui va recevoir le forfait.', 'error');
    redirect('/checkout.php?id=' . (int) $bundleId);
}
if (!preg_match('/^\+237[2368]\d{7,8}$/', $paymentPhone)) {
    flash('Vérifiez le numéro qui va effectuer le paiement.', 'error');
    redirect('/checkout.php?id=' . (int) $bundleId);
}
if (!in_array($paymentOperator, ['orange', 'mtn'], true)) {
    flash('Choisissez Orange Money ou MTN Mobile Money.', 'error');
    redirect('/checkout.php?id=' . (int) ($bundleId ?: 0));
}
if (!bumxpress_is_configured()) {
    flash('Le paiement en ligne est momentanément indisponible. Réessayez plus tard.', 'warning');
    redirect('/checkout.php?id=' . (int) $bundleId);
}

$bundleQuery = db()->prepare('SELECT * FROM bundles WHERE id = ? AND is_active = 1');
$bundleQuery->execute([$bundleId]);
$bundle = $bundleQuery->fetch();
if (!$bundle) {
    flash('Ce forfait n’est plus disponible.', 'error');
    redirect('/forfaits.php');
}

$publicRef = 'FP-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
$trackingToken = bin2hex(random_bytes(32));
$now = date('Y-m-d H:i:s');
$insert = db()->prepare("INSERT INTO orders (
    public_ref, tracking_token_hash, customer_name, customer_phone, payment_phone, customer_email, operator_code,
    bundle_id, bundle_name, volume_label, validity_days, amount, payment_operator, payment_status,
    delivery_status, created_at
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'initiating', 'waiting', ?)");
$insert->execute([
    $publicRef, hash('sha256', $trackingToken), $name, $deliveryPhone, $paymentPhone, $email, $bundle['operator_code'],
    $bundle['id'], $bundle['name'], $bundle['volume_label'], $bundle['validity_days'],
    $bundle['price'], $paymentOperator, $now,
]);
$orderId = (int) db()->lastInsertId();

try {
    $customer = create_bumxpress_customer($publicRef, $name, $email, $paymentPhone, 'customer_' . $publicRef);
    $customerId = (string) ($customer['id'] ?? '');
    if ($customerId === '') {
        throw new RuntimeException('Bumxpress n’a pas retourné l’identifiant du client.');
    }
    db()->prepare('UPDATE orders SET provider_customer_id = ? WHERE id = ?')->execute([$customerId, $orderId]);

    $quote = bumxpress_request('/quotes/payin', [
        'customer_id' => $customerId,
        'country_code' => 'CM',
        'operator_code' => bumxpress_operator($paymentOperator),
        'rail_type' => 'mobile_money',
        'send_amount' => (string) $bundle['price'],
        'send_currency' => 'XAF',
    ], 'quote_' . $publicRef);
    $quoteId = (string) ($quote['id'] ?? '');
    if ($quoteId === '') {
        throw new RuntimeException('Bumxpress n’a pas retourné l’identifiant du devis.');
    }
    $customerAction = $quote['customer_action'] ?? null;
    db()->prepare('UPDATE orders SET provider_quote_id = ?, customer_action_json = ? WHERE id = ?')
        ->execute([$quoteId, $customerAction ? json_encode($customerAction, JSON_UNESCAPED_UNICODE) : null, $orderId]);

    if (($customerAction['type'] ?? '') === 'otp_before') {
        db()->prepare("UPDATE orders SET payment_status = 'awaiting_otp' WHERE id = ?")->execute([$orderId]);
    } else {
        $order = array_merge($bundle, [
            'public_ref' => $publicRef,
            'provider_quote_id' => $quoteId,
            'payment_operator' => $paymentOperator,
            'customer_phone' => $deliveryPhone,
            'payment_phone' => $paymentPhone,
        ]);
        $payin = create_bumxpress_payin($order);
        $payinId = (string) ($payin['id'] ?? '');
        if ($payinId === '') {
            throw new RuntimeException('Bumxpress n’a pas retourné l’identifiant du paiement.');
        }
        $action = $payin['provider_action'] ?? null;
        db()->prepare("UPDATE orders SET provider_payin_id = ?, provider_action_json = ?, payment_status = 'pending' WHERE id = ?")
            ->execute([$payinId, $action ? json_encode($action, JSON_UNESCAPED_UNICODE) : null, $orderId]);
    }
} catch (Throwable $exception) {
    error_log('Bumxpress order creation failed for ' . $publicRef . ': ' . $exception->getMessage());
    db()->prepare("UPDATE orders SET payment_status = 'failed', failure_reason = ? WHERE id = ?")
        ->execute([substr($exception->getMessage(), 0, 255), $orderId]);
}

flash('Votre commande a été enregistrée. Saisissez le numéro utilisé pour recevoir ou payer le forfait afin de la retrouver.', 'success');
redirect('/mes-commandes.php');
