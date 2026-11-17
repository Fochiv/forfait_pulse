<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/app.php';
$activePage = 'plans';
$operator = (string) ($_GET['operateur'] ?? '');
$allowed = ['orange', 'mtn', 'camtel'];
$selectedOperator = in_array($operator, $allowed, true) ? $operator : '';
$pageTitle = $selectedOperator ? 'Forfaits ' . operator_name($selectedOperator) : 'Tous les forfaits';
$bundles = $selectedOperator
    ? db()->prepare('SELECT * FROM bundles WHERE is_active = 1 AND operator_code = ? ORDER BY sort_order')
    : db()->prepare('SELECT * FROM bundles WHERE is_active = 1 ORDER BY operator_code, sort_order');
if ($selectedOperator) {
    $bundles->execute([$selectedOperator]);
} else {
    $bundles->execute();
}
$bundles = $bundles->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<section class="container">
    <div class="page-heading">
            <a class="back-link" href="/"><?= icon('arrow', 17) ?> <span data-i18n="how_to_return">Retour à l’accueil</span></a>
        <?php if ($selectedOperator): ?>
            <div class="operator-title">
                <span class="operator-logo"><img src="/<?= h(operator_logo($selectedOperator)) ?>" alt="<?= h(operator_name($selectedOperator)) ?>"></span>
                <div><h1><span data-i18n="<?= h('plans_' . $selectedOperator) ?>">Forfaits <?= h(operator_name($selectedOperator)) ?></span></h1><p data-i18n="plans_description">Choisissez le forfait qui vous convient.</p></div>
            </div>
        <?php else: ?>
            <h1 data-i18n="plans_heading">Tous les forfaits</h1><p data-i18n="plans_subtitle">Choisissez votre opérateur pour voir ses offres.</p>
        <?php endif; ?>
    </div>
    <?php if (!$selectedOperator): ?>
        <div class="operator-list">
            <?php foreach ($allowed as $code): ?>
                <a class="operator-card <?= h(operator_class($code)) ?>" href="/forfaits.php?operateur=<?= h($code) ?>">
                    <span class="operator-logo"><img src="/<?= h(operator_logo($code)) ?>" alt="<?= h(operator_name($code)) ?>"></span>
                    <span><span class="operator-name"><?= h(operator_name($code)) ?></span><span class="operator-caption">Voir les forfaits</span></span>
                    <span class="operator-arrow"><?= icon('arrow', 24) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="filter-row" data-category-filters>
            <button class="filter-chip selected" type="button" data-filter="all" data-i18n="filter_all">Tous</button>
            <button class="filter-chip" type="button" data-filter="daily" data-i18n="filter_daily">Journaliers</button>
            <button class="filter-chip" type="button" data-filter="weekly" data-i18n="filter_weekly">Hebdomadaires</button>
            <button class="filter-chip" type="button" data-filter="monthly" data-i18n="filter_monthly">Mensuels</button>
        </div>
        <?php if ($bundles): ?>
            <div class="offer-grid">
                <?php foreach ($bundles as $bundle): ?>
                    <article class="bundle-card <?= h(operator_class($bundle['operator_code'])) ?>" data-days="<?= (int) $bundle['validity_days'] ?>">
                        <div class="bundle-top">
                            <div><h2 class="bundle-name"><?= h($bundle['name']) ?></h2><p class="bundle-volume"><?= h($bundle['volume_label']) ?></p></div>
                            <span class="operator-logo" style="width:46px;height:46px;flex-basis:46px;border-radius:13px">
                                <img src="/<?= h(operator_logo($bundle['operator_code'])) ?>" alt="<?= h(operator_name($bundle['operator_code'])) ?>">
                            </span>
                        </div>
                        <p class="bundle-validity"><?= icon('clock', 18) ?> Valable <?= (int) $bundle['validity_days'] ?> jours</p>
                        <p class="bundle-description"><?= h($bundle['description']) ?></p>
                        <div class="bundle-bottom">
                            <span class="bundle-price"><?= money($bundle['price']) ?></span>
                            <a class="btn-buy" href="/checkout.php?id=<?= (int) $bundle['id'] ?>" data-i18n="buy">Acheter</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">Aucun forfait actif pour cet opérateur.</div>
        <?php endif; ?>
    <?php endif; ?>
</section>
<div class="footer"><div class="container">Forfait Pulse · Forfaits simples et prix clairs.</div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
