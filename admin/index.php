<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/app.php';
admin_required();
$pdo = db();
$adminActive = 'dashboard';
$adminTitle = 'Vue d’ensemble';
$revenue = (int) $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM orders WHERE payment_status = 'paid'")->fetchColumn();
$payinFeesStmt = $pdo->prepare("SELECT COALESCE(SUM(ROUND(amount * ?)), 0) FROM orders WHERE payment_status = 'paid'");
$payinFeesStmt->execute([PAYIN_FEE_RATE]);
$payinFees = (int) $payinFeesStmt->fetchColumn();
$netRevenue = max(0, $revenue - $payinFees);
$periodRevenue = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM orders WHERE payment_status = 'paid' AND paid_at >= ?");
$periodRevenue->execute([date('Y-m-d 00:00:00')]);
$today = (int) $periodRevenue->fetchColumn();
$periodRevenue->execute([date('Y-m-d 00:00:00', strtotime('-6 days'))]);
$week = (int) $periodRevenue->fetchColumn();
$periodRevenue->execute([date('Y-m-01 00:00:00')]);
$month = (int) $periodRevenue->fetchColumn();
$ordersTotal = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$pending = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status IN ('pending','initiating','awaiting_otp')")->fetchColumn();
$failed = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'failed'")->fetchColumn();
$delivered = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'paid' AND delivery_status = 'delivered'")->fetchColumn();
$waitingDelivery = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'paid' AND delivery_status <> 'delivered'")->fetchColumn();
$operatorStmt = $pdo->prepare("SELECT operator_code, SUM(amount) AS revenue, COUNT(*) AS total FROM orders WHERE payment_status = 'paid' GROUP BY operator_code ORDER BY revenue DESC");
$operatorStmt->execute();
$operatorRevenue = $operatorStmt->fetchAll();
$maxRevenue = max(1, ...array_map(static fn($row) => (int) $row['revenue'], $operatorRevenue ?: [['revenue' => 0]]));
$popular = $pdo->query("SELECT bundle_name, operator_code, COUNT(*) AS total FROM orders WHERE payment_status = 'paid' GROUP BY bundle_name, operator_code ORDER BY total DESC LIMIT 5")->fetchAll();
$recent = $pdo->query("SELECT * FROM orders WHERE payment_status = 'paid' ORDER BY created_at DESC LIMIT 8")->fetchAll();
require __DIR__ . '/header.php';
?>
<div class="admin-page-title"><div><h1>Vue d’ensemble</h1><p>Suivez les ventes et les commandes en un coup d’œil.</p></div><a class="admin-btn primary" href="/admin/retrait.php"><?= icon('wallet', 17) ?> Faire un retrait</a></div>
<section class="metric-grid">
    <article class="metric-card accent"><span class="metric-label">Revenus bruts</span><strong class="metric-value"><?= money($revenue) ?></strong></article>
    <article class="metric-card net"><span class="metric-label">Revenus après frais Payin (2 %)</span><strong class="metric-value"><?= money($netRevenue) ?></strong><span class="metric-note">Frais estimés : <?= money($payinFees) ?> · paiements confirmés uniquement</span></article>
    <article class="metric-card"><span class="metric-label">Ventes brutes du jour</span><strong class="metric-value"><?= money($today) ?></strong></article>
    <article class="metric-card"><span class="metric-label">Ventes brutes sur 7 jours</span><strong class="metric-value"><?= money($week) ?></strong></article>
    <article class="metric-card"><span class="metric-label">Ventes brutes du mois</span><strong class="metric-value"><?= money($month) ?></strong></article>
    <article class="metric-card"><span class="metric-label">Commandes totales</span><strong class="metric-value"><?= $ordersTotal ?></strong></article>
    <article class="metric-card"><span class="metric-label">Paiements en attente</span><strong class="metric-value"><?= $pending ?></strong></article>
    <article class="metric-card"><span class="metric-label">Commandes échouées</span><strong class="metric-value"><?= $failed ?></strong></article>
    <article class="metric-card"><span class="metric-label">À livrer</span><strong class="metric-value"><?= $waitingDelivery ?></strong></article>
</section>
<section class="admin-grid two-col">
    <article class="admin-panel">
        <h2>Revenu par opérateur</h2>
        <?php if (!$operatorRevenue): ?><p class="note-muted">Aucun paiement confirmé pour le moment.</p><?php endif; ?>
        <?php foreach ($operatorRevenue as $row): ?>
            <div class="bar-row"><strong><?= h(operator_name($row['operator_code'])) ?></strong><div class="bar-track"><div class="bar-fill" style="width:<?= (int) round(((int) $row['revenue'] / $maxRevenue) * 100) ?>%"></div></div><span><?= money($row['revenue']) ?></span></div>
        <?php endforeach; ?>
    </article>
    <article class="admin-panel">
        <h2>Statut des commandes</h2>
        <div class="config-row"><span class="config-label">En attente de paiement</span><strong><?= $pending ?></strong></div>
        <div class="config-row"><span class="config-label">Payées · à livrer</span><strong><?= $waitingDelivery ?></strong></div>
        <div class="config-row"><span class="config-label">Livrées</span><strong><?= $delivered ?></strong></div>
        <div class="config-row"><span class="config-label">Échouées</span><strong><?= $failed ?></strong></div>
        <div class="config-row"><span class="config-label">Solde disponible</span><a href="https://portal.bumxpress.com" target="_blank" rel="noopener">Consulter Bumxpress <?= icon('arrow', 15) ?></a></div>
        <p class="form-help mt-2">La documentation Bumxpress ne publie pas de lecture de solde via l’API. Aucun montant n’est simulé ici.</p>
    </article>
</section>
<section class="admin-grid two-col mt-3">
    <article class="admin-panel">
        <h2>Forfaits les plus vendus</h2>
        <?php if (!$popular): ?><p class="note-muted">Les forfaits les plus achetés apparaîtront après les premières ventes.</p><?php endif; ?>
        <?php foreach ($popular as $row): ?><div class="config-row"><span><?= h(operator_name($row['operator_code'])) ?> · <?= h($row['bundle_name']) ?></span><strong><?= (int) $row['total'] ?></strong></div><?php endforeach; ?>
    </article>
    <article class="admin-panel">
        <h2>Historique des commandes</h2>
        <?php if (!$recent): ?><p class="note-muted">Aucune commande enregistrée.</p><?php else: ?>
            <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Réf.</th><th>Client</th><th>Forfait</th><th>État</th></tr></thead><tbody>
            <?php foreach ($recent as $order): [$label, $class] = order_status_label($order); ?>
                <tr><td><?= h($order['public_ref']) ?></td><td><?= h($order['customer_name']) ?><br><span class="note-muted"><?= h($order['customer_phone']) ?></span></td><td><?= h($order['bundle_name']) ?></td><td><span class="status-pill <?= h($class) ?>"><?= h($label) ?></span></td></tr>
            <?php endforeach; ?>
            </tbody></table></div>
            <a class="admin-btn mt-2" href="/admin/commandes.php">Gérer les commandes</a>
        <?php endif; ?>
    </article>
</section>
<?php require __DIR__ . '/footer.php'; ?>
