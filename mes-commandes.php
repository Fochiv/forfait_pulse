<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/app.php';
$activePage = 'orders';
$pageTitle = 'Mes commandes';
$orders = [];
$lookupError = '';
$searchPerformed = false;
$submittedPhone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $searchPerformed = true;
    $submittedPhone = normalize_cameroon_phone((string) ($_POST['phone'] ?? ''));
    if (!preg_match('/^\+237[2368]\d{7,8}$/', $submittedPhone)) {
        $lookupError = 'Saisissez un numéro de téléphone camerounais valide.';
    } else {
        $since = date('Y-m-d H:i:s', time() - 900);
        $ipHash = hash_hmac('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), env_value('SESSION_SECRET', __FILE__));
        $phoneHash = hash_hmac('sha256', $submittedPhone, env_value('SESSION_SECRET', __FILE__));
        $attempts = db()->prepare('SELECT COUNT(*) FROM order_lookup_attempts WHERE ip_hash = ? AND attempted_at >= ?');
        $attempts->execute([$ipHash, $since]);
        if ((int) $attempts->fetchColumn() >= 20) {
            $lookupError = 'Trop de recherches. Réessayez dans 15 minutes.';
        } else {
            db()->prepare('INSERT INTO order_lookup_attempts (ip_hash, phone_hash, attempted_at) VALUES (?, ?, ?)')
                ->execute([$ipHash, $phoneHash, date('Y-m-d H:i:s')]);
            $stmt = db()->prepare('SELECT * FROM orders WHERE customer_phone = ? OR payment_phone = ? ORDER BY created_at DESC LIMIT 10');
            $stmt->execute([$submittedPhone, $submittedPhone]);
            $orders = $stmt->fetchAll();
            if ($orders) {
                $_SESSION['order_lookup_phone'] = $submittedPhone;
                $_SESSION['order_lookup_at'] = time();
            } else {
                unset($_SESSION['order_lookup_phone'], $_SESSION['order_lookup_at']);
            }
        }
    }
} else {
    $sessionPhone = current_order_lookup_phone();
    if ($sessionPhone !== null) {
        $submittedPhone = $sessionPhone;
        $stmt = db()->prepare('SELECT * FROM orders WHERE customer_phone = ? OR payment_phone = ? ORDER BY created_at DESC LIMIT 10');
        $stmt->execute([$sessionPhone, $sessionPhone]);
        $orders = $stmt->fetchAll();
    }
}

$error = take_flash();
require __DIR__ . '/includes/header.php';
?>
<section class="container">
    <div class="page-heading"><h1 data-i18n="nav_orders">Mes commandes</h1><p data-i18n="orders_subtitle">Retrouvez vos commandes avec votre numéro de téléphone.</p></div>
    <?php if ($error): ?><div class="notice notice-<?= h($error['type']) ?>"><?= h($error['message']) ?></div><?php endif; ?>
    <form class="form-card order-lookup-form mb-3" method="post" action="/mes-commandes.php">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <div class="form-group">
            <label for="order_phone" data-i18n="order_lookup_number">Numéro de téléphone</label>
            <input class="form-control" id="order_phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" required maxlength="22" value="<?= h($submittedPhone) ?>" placeholder="6XX XXX XXX ou +237 6XX XXX XXX">
        </div>
        <button class="btn-primary mt-3" type="submit"><?= icon('search', 18) ?> <span data-i18n="order_lookup_button">Rechercher mes commandes</span></button>
    </form>
    <?php if ($lookupError): ?><div class="notice notice-error"><?= h($lookupError) ?></div><?php endif; ?>
    <?php if ($orders): ?>
        <div class="order-list">
            <?php foreach ($orders as $order): [$label, $statusClass] = order_status_label($order); ?>
                <a class="order-card" href="/commande.php?id=<?= (int) $order['id'] ?>">
                    <div class="order-head">
                        <div><span class="order-ref">Commande <?= h($order['public_ref']) ?></span><p class="order-title"><?= h(operator_name($order['operator_code'])) ?> · <?= h($order['bundle_name']) ?></p></div>
                        <span class="status-pill <?= h($statusClass) ?>"><?= h($label) ?></span>
                    </div>
                    <div class="summary-row"><span><?= h($order['volume_label']) ?> · <?= (int) $order['validity_days'] ?> jours</span><strong><?= money($order['amount']) ?></strong></div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php elseif ($searchPerformed && !$lookupError): ?>
        <div class="empty-state"><p data-i18n="order_lookup_empty">Aucune commande trouvée pour ce numéro.</p></div>
    <?php elseif (!$lookupError): ?>
        <div class="empty-state"><p>Saisissez le numéro utilisé pour recevoir ou payer le forfait afin de retrouver vos commandes.</p></div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
