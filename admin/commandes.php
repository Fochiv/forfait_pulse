<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/app.php';
admin_required();
$adminActive = 'orders';
$adminTitle = 'Commandes';
$filter = (string) ($_GET['statut'] ?? 'paid');
$allowedFilters = ['paid', 'pending', 'failed', 'all'];
if (!in_array($filter, $allowedFilters, true)) $filter = 'paid';
$where = match ($filter) {
    'pending' => "payment_status IN ('pending','initiating','awaiting_otp')",
    'failed' => "payment_status = 'failed'",
    'all' => '1=1',
    default => "payment_status = 'paid'",
};
$stmt = db()->prepare("SELECT * FROM orders WHERE {$where} ORDER BY created_at DESC LIMIT 250");
$stmt->execute();
$orders = $stmt->fetchAll();
require __DIR__ . '/header.php';
?>
<div class="admin-page-title"><div><h1>Commandes</h1><p>La liste principale n’affiche que les paiements confirmés.</p></div></div>
<nav class="admin-tabs" aria-label="Filtrer les commandes">
    <a class="admin-tab <?= $filter === 'paid' ? 'active' : '' ?>" href="/admin/commandes.php?statut=paid">Payées</a>
    <a class="admin-tab <?= $filter === 'pending' ? 'active' : '' ?>" href="/admin/commandes.php?statut=pending">En attente</a>
    <a class="admin-tab <?= $filter === 'failed' ? 'active' : '' ?>" href="/admin/commandes.php?statut=failed">Échouées</a>
    <a class="admin-tab <?= $filter === 'all' ? 'active' : '' ?>" href="/admin/commandes.php?statut=all">Toutes</a>
</nav>
<section class="admin-panel">
    <div class="admin-table-wrap"><table class="admin-table">
        <thead><tr><th>Commande</th><th>Client</th><th>Forfait</th><th>Paiement</th><th>Livraison</th><th>Date</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($orders as $order): [$label, $class] = order_status_label($order); ?>
            <tr>
                <td><strong><?= h($order['public_ref']) ?></strong><br><span class="note-muted"><?= money($order['amount']) ?></span></td>
                <td class="break"><strong><?= h($order['customer_name']) ?></strong><br><?= h($order['customer_phone']) ?><br><span class="note-muted"><?= h($order['customer_email']) ?></span></td>
                <td class="break"><?= h(operator_name($order['operator_code'])) ?> · <?= h($order['bundle_name']) ?><br><span class="note-muted"><?= h($order['volume_label']) ?> · <?= (int) $order['validity_days'] ?> jours</span></td>
                <td><?= h($order['payment_operator'] === 'orange' ? 'Orange Money' : 'MTN Mobile Money') ?><br><span class="status-pill <?= h($class) ?>"><?= h($label) ?></span></td>
                <td><?= $order['delivery_status'] === 'delivered' ? '<span class="status-pill status-delivered">Livrée</span>' : 'À livrer' ?></td>
                <td><?= h($order['created_at']) ?></td>
                <td>
                    <?php if ($order['payment_status'] === 'paid' && $order['delivery_status'] !== 'delivered'): ?>
                        <form action="/admin/livrer.php" method="post" data-confirm="Confirmez-vous que le forfait a été envoyé au numéro indiqué ?">
                            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
                            <button class="admin-btn success" type="submit">Marquer « Livrer »</button>
                        </form>
                    <?php elseif ($order['payment_status'] === 'paid'): ?><span class="note-muted">Déjà livrée</span>
                    <?php else: ?><span class="note-muted">Aucune livraison possible</span><?php endif; ?>
                    <?php if ($order['failure_reason']): ?><div class="note-muted mt-1"><?= h($order['failure_reason']) ?></div><?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$orders): ?><tr><td colspan="7" class="text-center note-muted py-4">Aucune commande dans cette catégorie.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</section>
<div class="callout mt-3"><?= icon('truck', 18) ?><p>La livraison est manuelle. Vérifiez d’abord le numéro, envoyez le forfait avec l’outil opérateur, puis cliquez sur « Livrer ». Le client verra le statut vert dans ses commandes.</p></div>
<?php require __DIR__ . '/footer.php'; ?>
