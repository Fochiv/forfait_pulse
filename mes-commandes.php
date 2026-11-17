<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/app.php';
$activePage = 'orders';
$pageTitle = 'Mes commandes';
$orders = [];
$tracked = $_SESSION['customer_orders'] ?? [];
if (is_array($tracked) && $tracked) {
    $ids = array_keys($tracked);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("SELECT * FROM orders WHERE id IN ({$placeholders}) ORDER BY created_at DESC");
    $stmt->execute($ids);
    $orders = $stmt->fetchAll();
}
$error = take_flash();
require __DIR__ . '/includes/header.php';
?>
<section class="container">
    <div class="page-heading"><h1 data-i18n="nav_orders">Mes commandes</h1><p data-i18n="orders_subtitle">Suivez le paiement et la livraison de vos forfaits.</p></div>
    <?php if ($error): ?><div class="notice notice-<?= h($error['type']) ?>"><?= h($error['message']) ?></div><?php endif; ?>
    <?php if (!$orders): ?>
        <div class="empty-state">
            <p data-i18n="no_orders">Aucune commande pour le moment.</p>
            <p data-i18n="no_orders_help">Choisissez un forfait pour commencer.</p>
            <a class="btn-primary" href="/forfaits.php">Voir les forfaits</a>
        </div>
    <?php else: ?>
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
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
