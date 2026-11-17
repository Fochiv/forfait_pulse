<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bumxpress.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/mes-commandes.php');
}
require_csrf();

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$otp = trim((string) ($_POST['otp_code'] ?? ''));
if (!$id || !preg_match('/^[A-Za-z0-9]{3,12}$/', $otp)) {
    flash('Le code saisi est invalide.', 'error');
    redirect('/mes-commandes.php');
}
$attemptKey = (string) $id;
$_SESSION['otp_attempts'] ??= [];
$attemptData = $_SESSION['otp_attempts'][$attemptKey] ?? ['count' => 0, 'since' => time()];
if (time() - (int) ($attemptData['since'] ?? 0) > 900) {
    $attemptData = ['count' => 0, 'since' => time()];
}
if ((int) ($attemptData['count'] ?? 0) >= 5) {
    flash('Trop de tentatives de code. Réessayez plus tard ou contactez l’assistance.', 'error');
    redirect('/commande.php?id=' . $id);
}
$stmt = db()->prepare("SELECT * FROM orders WHERE id = ? AND payment_status IN ('awaiting_otp','pending')");
$stmt->execute([$id]);
$order = $stmt->fetch();
if (!$order || !order_matches_phone_lookup($order)) {
    flash('Cette commande ne peut plus être autorisée.', 'error');
    redirect('/mes-commandes.php');
}
$_SESSION['order_lookup_at'] = time();
try {
    if (!empty($order['provider_payin_id']) && $order['payment_status'] === 'pending') {
        $current = bumxpress_request('/payins/' . rawurlencode((string) $order['provider_payin_id']));
        if (empty($current['provider_action']) || ($current['provider_action']['type'] ?? '') !== 'submit_otp') {
            flash('Le code a déjà été accepté ou le paiement a changé d’état. Actualisez le suivi.', 'info');
            redirect('/commande.php?id=' . $id);
        }
        $payin = bumxpress_request('/payins/' . rawurlencode((string) $order['provider_payin_id']) . '/authorize', ['otp_code' => $otp]);
        $payinId = (string) $order['provider_payin_id'];
    } else {
        $payin = create_bumxpress_payin($order, $otp);
        $payinId = (string) ($payin['id'] ?? '');
        if ($payinId === '') {
            throw new RuntimeException('Bumxpress n’a pas retourné l’identifiant du paiement.');
        }
    }
    $action = $payin['provider_action'] ?? null;
    db()->prepare("UPDATE orders SET provider_payin_id = ?, provider_action_json = ?, payment_status = 'pending' WHERE id = ?")
        ->execute([$payinId, $action ? json_encode($action, JSON_UNESCAPED_UNICODE) : null, $id]);
    unset($_SESSION['otp_attempts'][$attemptKey]);
    flash('Code envoyé. Suivez les instructions affichées et validez la demande sur votre téléphone.', 'success');
} catch (Throwable $exception) {
    $_SESSION['otp_attempts'][$attemptKey] = [
        'count' => (int) ($attemptData['count'] ?? 0) + 1,
        'since' => (int) ($attemptData['since'] ?? time()),
    ];
    error_log('Bumxpress OTP authorization failed for ' . $order['public_ref'] . ': ' . $exception->getMessage());
    flash('Le code n’a pas pu être traité. Vérifiez-le puis réessayez.', 'error');
}
redirect('/commande.php?id=' . $id);
