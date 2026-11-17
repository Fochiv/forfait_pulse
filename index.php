<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/app.php';
$activePage = 'home';
$pageTitle = 'Forfaits internet au Cameroun';
$bundles = db()->query('SELECT * FROM bundles WHERE is_active = 1 ORDER BY sort_order LIMIT 3')->fetchAll();
$reviews = db()->query('SELECT customer_name, message, rating FROM reviews WHERE is_visible = 1 ORDER BY created_at DESC LIMIT 3')->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div class="container hero-grid">
        <div>
            <p class="eyebrow" data-i18n="mobile_internet">Internet mobile, simplement</p>
            <h1><span data-i18n="hero_choose">Choisissez votre</span> <span data-i18n="operator_word">opérateur</span></h1>
            <p class="hero-copy" data-i18n="intro_copy">Des forfaits Orange, MTN et Camtel au Cameroun, au prix affiché.</p>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-heading">
            <div>
                <h2 data-i18n="choose_operator">Choisissez votre opérateur</h2>
                <p data-i18n="subtitle">Trouvez le forfait internet qui vous convient.</p>
            </div>
        </div>
        <div class="operator-list" data-operator-picker>
            <?php foreach (['orange' => 'Orange', 'mtn' => 'MTN', 'camtel' => 'Camtel'] as $code => $name): ?>
                <button class="operator-card <?= h(operator_class($code)) ?>" type="button" data-operator="<?= h($code) ?>">
                    <span class="operator-logo"><img src="/<?= h(operator_logo($code)) ?>" alt="Logo <?= h($name) ?>"></span>
                    <span>
                        <span class="operator-name"><?= h($name) ?></span>
                        <span class="operator-caption" data-i18n="see_plans">Voir les forfaits</span>
                    </span>
                    <span class="operator-arrow"><?= icon('arrow', 24) ?></span>
                </button>
            <?php endforeach; ?>
        </div>
        <div class="trust-row">
            <div class="trust-item"><?= icon('shield', 20) ?><span data-i18n="secure_payment">Paiement sécurisé</span></div>
            <div class="trust-item"><?= icon('truck', 20) ?><span data-i18n="fast_delivery">Livraison rapide</span></div>
            <div class="trust-item"><?= icon('check', 20) ?><span data-i18n="no_account">Sans compte client</span></div>
        </div>
    </div>
</section>

<?php if ($bundles): ?>
<section class="section">
    <div class="container">
        <div class="section-heading">
                <div><h2 data-i18n="popular_offers">Forfaits populaires</h2><p data-i18n="clear_prices">Choisissez un forfait et payez avec Mobile Money.</p></div>
            <a class="btn-outline" href="/forfaits.php"><span data-i18n="see_everything">Tout voir</span> <?= icon('arrow', 16) ?></a>
        </div>
        <div class="offer-grid">
            <?php foreach ($bundles as $bundle): ?>
                <article class="bundle-card <?= h(operator_class($bundle['operator_code'])) ?>">
                    <div class="bundle-top">
                        <div>
                            <h3 class="bundle-name"><?= h($bundle['name']) ?></h3>
                            <p class="bundle-volume"><?= h($bundle['volume_label']) ?></p>
                        </div>
                        <span class="operator-logo" style="width:46px;height:46px;flex-basis:46px;border-radius:13px">
                            <img src="/<?= h(operator_logo($bundle['operator_code'])) ?>" alt="<?= h(operator_name($bundle['operator_code'])) ?>">
                        </span>
                    </div>
                    <p class="bundle-validity"><?= icon('clock', 18) ?> Valable <?= (int) $bundle['validity_days'] ?> jours</p>
                    <div class="bundle-bottom">
                        <span class="bundle-price"><?= money($bundle['price']) ?></span>
                        <a class="btn-buy" href="/checkout.php?id=<?= (int) $bundle['id'] ?>" data-i18n="buy">Acheter</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section" id="comment-ca-marche">
    <div class="container">
        <div class="section-heading"><div><h2 data-i18n="how_title">Comment ça marche</h2><p data-i18n="process_subtitle">Votre forfait, en trois étapes faciles.</p></div></div>
        <div class="steps-grid">
            <div class="step-card"><span class="step-number">1</span><p data-i18n="step_one">Choisissez votre forfait</p></div>
            <div class="step-card"><span class="step-number">2</span><p data-i18n="step_two">Payez avec Mobile Money</p></div>
            <div class="step-card" data-admin-secret-step tabindex="0" role="button" aria-label="Étape 3 : recevez votre forfait">
                <span class="step-number">3</span><p data-i18n="step_three">Recevez votre forfait</p>
            </div>
        </div>
    </div>
</section>

<?php if ($reviews): ?>
<section class="section">
    <div class="container">
        <div class="section-heading"><div><h2 data-i18n="reviews_title">Ils nous font confiance</h2><p data-i18n="reviews_subtitle">Les avis de nos clients.</p></div></div>
        <div class="offer-grid">
            <?php foreach ($reviews as $review): ?>
                <article class="bundle-card">
                    <div class="bundle-volume" aria-label="<?= (int) $review['rating'] ?> étoiles"><?php for ($i = 0; $i < max(1, min(5, (int) $review['rating'])); $i++): ?><?= icon('star', 15) ?><?php endfor; ?></div>
                    <p><?= h($review['message']) ?></p><strong><?= h($review['customer_name']) ?></strong>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<div class="footer"><div class="container">Forfait Pulse · Orange · MTN · Camtel</div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
