<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/app.php';
$activePage = 'plans';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$stmt = db()->prepare('SELECT * FROM bundles WHERE id = ? AND is_active = 1');
$stmt->execute([$id]);
$bundle = $stmt->fetch();
if (!$bundle) {
    http_response_code(404);
}
$pageTitle = $bundle ? 'Acheter ' . $bundle['name'] : 'Forfait introuvable';
$error = take_flash();
require __DIR__ . '/includes/header.php';
?>
<section class="container">
    <div class="page-heading">
        <a class="back-link" href="/forfaits.php<?= $bundle ? '?operateur=' . h($bundle['operator_code']) : '' ?>"><?= icon('arrow', 17) ?> <span data-i18n="back_plans">Retour aux forfaits</span></a>
        <h1 data-i18n="checkout_title">Finaliser votre achat</h1><p data-i18n="checkout_description">Vos informations servent à créer le paiement et à retrouver votre commande.</p>
    </div>
    <?php if (!$bundle): ?>
        <div class="notice notice-error">Ce forfait n’est plus disponible.</div>
    <?php else: ?>
        <?php if ($error): ?><div class="notice notice-<?= h($error['type']) ?>"><?= h($error['message']) ?></div><?php endif; ?>
        <?php if (!bumxpress_is_configured()): ?>
            <div class="notice notice-warning">Le paiement Bumxpress n’est pas encore configuré. Les commandes ne seront pas acceptées avant l’ajout des identifiants API et du secret webhook.</div>
        <?php endif; ?>
        <div class="row g-3 align-items-start">
            <div class="col-lg-7">
                <form class="form-card" action="/create-order.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="bundle_id" value="<?= (int) $bundle['id'] ?>">
                    <div class="form-grid two-col">
                        <div class="form-group"><label for="customer_name" data-i18n="full_name">Nom complet</label><input class="form-control" id="customer_name" name="customer_name" autocomplete="name" required minlength="2" maxlength="160" placeholder="Ex. Awa Ngu"></div>
                        <div class="form-group"><label for="customer_phone" data-i18n="delivery_number">Numéro qui va recevoir le forfait</label><input class="form-control" id="customer_phone" name="customer_phone" type="tel" inputmode="tel" autocomplete="tel" required maxlength="22" placeholder="6XX XXX XXX ou +237 6XX XXX XXX"></div>
                        <div class="form-group"><label for="payment_phone" data-i18n="payment_phone">Numéro qui va effectuer le paiement</label><input class="form-control" id="payment_phone" name="payment_phone" type="tel" inputmode="tel" autocomplete="tel" required maxlength="22" placeholder="6XX XXX XXX ou +237 6XX XXX XXX"><p class="form-help" data-i18n="payment_phone_help">Numéro Orange Money ou MTN Mobile Money qui sera débité.</p></div>
                        <div class="form-group" style="grid-column:1/-1"><label for="customer_email" data-i18n="email_address">Adresse e-mail</label><input class="form-control" id="customer_email" name="customer_email" type="email" autocomplete="email" required maxlength="190" placeholder="vous@exemple.com"></div>
                        <div class="form-group" style="grid-column:1/-1">
                            <label data-i18n="payment_method">Moyen de paiement</label>
                            <div class="payment-choice">
                                <label><input type="radio" name="payment_operator" value="orange" required><span>Orange Money</span></label>
                                <label><input type="radio" name="payment_operator" value="mtn" required><span>MTN Mobile Money</span></label>
                            </div>
                        </div>
                        <div style="grid-column:1/-1">
                            <button class="btn-primary w-100" type="submit" <?= !bumxpress_is_configured() ? 'disabled' : '' ?>><span data-i18n="continue_payment">Continuer vers le paiement</span> <?= icon('arrow', 18) ?></button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-lg-5">
                <aside class="form-card">
                    <p class="eyebrow" data-i18n="your_bundle">Votre forfait</p>
                    <div class="summary-row"><span data-i18n="operator_label">Opérateur</span><strong><?= h(operator_name($bundle['operator_code'])) ?></strong></div>
                    <div class="summary-row"><span data-i18n="bundle_label">Forfait</span><strong><?= h($bundle['name']) ?></strong></div>
                    <div class="summary-row"><span data-i18n="internet_label">Internet</span><strong><?= h($bundle['volume_label']) ?></strong></div>
                    <div class="summary-row"><span data-i18n="validity_label">Validité</span><strong><?= (int) $bundle['validity_days'] ?> jours</strong></div>
                    <div class="summary-row summary-total"><span data-i18n="total_label">Total</span><strong><?= money($bundle['price']) ?></strong></div>
                    <div class="callout mt-3"><?= icon('shield', 19) ?><p data-i18n="secure_payment_note">Vos données de paiement restent chez Bumxpress. Forfait Pulse ne demande jamais votre code PIN Mobile Money.</p></div>
                </aside>
            </div>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
