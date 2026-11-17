<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bumxpress.php';
$activePage = 'orders';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$token = $_SESSION['customer_orders'][(string) $id] ?? '';
$stmt = db()->prepare('SELECT * FROM orders WHERE id = ? AND tracking_token_hash = ?');
$stmt->execute([$id, is_string($token) ? hash('sha256', $token) : '']);
$order = $stmt->fetch();
if (!$order) {
    http_response_code(404);
    $order = null;
} else {
    $order = sync_order_from_provider($order);
    $fresh = db()->prepare('SELECT * FROM orders WHERE id = ?');
    $fresh->execute([$id]);
    $order = $fresh->fetch() ?: $order;
}
$pageTitle = $order ? 'Commande ' . $order['public_ref'] : 'Commande introuvable';
$error = take_flash();
$action = $order && !empty($order['provider_action_json']) ? json_decode($order['provider_action_json'], true) : null;
$customerAction = $order && !empty($order['customer_action_json']) ? json_decode($order['customer_action_json'], true) : null;
[$statusLabel, $statusClass] = $order ? order_status_label($order) : ['', ''];
require __DIR__ . '/includes/header.php';
?>
<section class="container">
    <div class="page-heading">
        <a class="back-link" href="/mes-commandes.php"><?= icon('arrow', 17) ?> Mes commandes</a>
        <h1><?= $order ? 'Suivi de votre commande' : 'Commande introuvable' ?></h1>
    </div>
    <?php if ($error): ?><div class="notice notice-<?= h($error['type']) ?>"><?= h($error['message']) ?></div><?php endif; ?>
    <?php if (!$order): ?>
        <div class="empty-state">Cette commande n’est pas accessible depuis ce navigateur.</div>
    <?php else: ?>
        <article class="form-card">
            <div class="order-head">
                <div><span class="order-ref">Référence <?= h($order['public_ref']) ?></span><h2 class="order-title"><?= h(operator_name($order['operator_code'])) ?> · <?= h($order['bundle_name']) ?></h2></div>
                <span class="status-pill <?= h($statusClass) ?>"><?= h($statusLabel) ?></span>
            </div>
            <div class="summary-row"><span>Forfait</span><strong><?= h($order['volume_label']) ?> · <?= (int) $order['validity_days'] ?> jours</strong></div>
            <div class="summary-row"><span>Montant</span><strong><?= money($order['amount']) ?></strong></div>
            <div class="summary-row"><span>Paiement</span><strong><?= $order['payment_operator'] === 'orange' ? 'Orange Money' : 'MTN Mobile Money' ?></strong></div>
            <?php if ($order['payment_status'] === 'failed'): ?>
                <div class="notice notice-error mt-3">Le paiement n’a pas été confirmé. <?= h($order['failure_reason'] ?? '') ?></div>
            <?php elseif ($order['payment_status'] === 'awaiting_otp' && ($customerAction['type'] ?? '') === 'otp_before'): ?>
                <div class="notice notice-warning mt-3">
                    <strong>Une action est nécessaire pour continuer le paiement.</strong>
                    <p class="mb-0"><?= h($customerAction['instructions']['fr'] ?? 'Suivez les instructions de votre opérateur puis saisissez le code reçu.') ?></p>
                    <?php if (!empty($customerAction['ussd_code'])): ?><p class="mb-0">Code USSD : <strong><?= h($customerAction['ussd_code']) ?></strong></p><?php endif; ?>
                </div>
                <form class="form-card mt-3" action="/authorize-payin.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
                    <div class="form-group"><label for="otp_code">Code de paiement</label><input class="form-control" id="otp_code" name="otp_code" required maxlength="12" autocomplete="one-time-code"></div>
                    <button class="btn-primary mt-3" type="submit">Envoyer le code</button>
                </form>
            <?php elseif ($order['payment_status'] === 'pending' && ($action['type'] ?? '') === 'submit_otp'): ?>
                <div class="notice notice-warning mt-3"><strong>Code de confirmation requis.</strong><p class="mb-0"><?= h($action['message'] ?? 'Saisissez le code envoyé par votre opérateur.') ?></p></div>
                <form class="form-card mt-3" action="/authorize-payin.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
                    <div class="form-group"><label for="otp_code">Code reçu</label><input class="form-control" id="otp_code" name="otp_code" required maxlength="12" autocomplete="one-time-code"></div>
                    <button class="btn-primary mt-3" type="submit">Valider le code</button>
                </form>
            <?php elseif ($order['payment_status'] === 'paid' && $order['delivery_status'] === 'delivered'): ?>
                <div class="notice notice-success mt-3"><?= icon('check', 18) ?> Votre commande est livrée. Vous pouvez maintenant utiliser votre forfait.</div>
            <?php elseif ($order['payment_status'] === 'paid'): ?>
                <div class="notice notice-success mt-3">Paiement confirmé. L’admin prépare votre forfait. Le statut passera au vert dès qu’il sera livré.</div>
            <?php else: ?>
                <div class="notice notice-warning mt-3">
                    Le paiement est en attente. Confirmez la demande sur votre téléphone si elle s’affiche.
                    <?php if (is_array($action) && ($action['type'] ?? '') === 'redirect' && !empty($action['redirect_url']) && str_starts_with($action['redirect_url'], 'https://')): ?>
                        <p class="mb-0 mt-2"><a class="btn-primary" href="<?= h($action['redirect_url']) ?>" target="_blank" rel="noopener">Ouvrir le paiement</a></p>
                    <?php elseif (is_array($action) && !empty($action['ussd_code'])): ?>
                        <p class="mb-0 mt-2">Code à composer : <strong><?= h($action['ussd_code']) ?></strong></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="callout mt-3"><?= icon('clock', 19) ?><p>Le forfait est livré manuellement après la confirmation du paiement. Cette page se met à jour lorsque le statut change.</p></div>
            <a class="btn-outline mt-3" href="/mes-commandes.php">Retour à mes commandes</a>
        </article>
    <?php endif; ?>
</section>
<?php if ($order && in_array($order['payment_status'], ['pending', 'awaiting_otp'], true)): ?>
<script>setTimeout(() => location.reload(), 25000);</script>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
