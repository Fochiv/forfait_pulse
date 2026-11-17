<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/app.php';
admin_required();
$adminActive = 'settings';
$adminTitle = 'Paramètres';
$driver = env_value('DB_DRIVER', 'sqlite');
$apiKeyReady = env_value('BUMXPRESS_API_KEY') !== '';
$apiSecretReady = env_value('BUMXPRESS_API_SECRET') !== '';
$webhookReady = env_value('BUMXPRESS_WEBHOOK_SECRET') !== '';
$environment = env_value('BUMXPRESS_ENVIRONMENT', 'sandbox');
require __DIR__ . '/header.php';
?>
<div class="admin-page-title"><div><h1>Paramètres</h1><p>Vérifiez la configuration et les informations de sécurité du site.</p></div></div>
<section class="admin-grid two-col">
    <article class="admin-panel">
        <h2>Base de données</h2>
        <div class="config-row"><span class="config-label">Moteur actif</span><strong><?= h(strtoupper($driver)) ?></strong></div>
        <?php if ($driver === 'sqlite'): ?><div class="notice notice-warning mt-3">SQLite est réservé à l’aperçu. Pour Hostinger, configurez MySQL dans l’environnement d’hébergement.</div><?php else: ?><div class="notice notice-success mt-3">La configuration de base de données MySQL est active.</div><?php endif; ?>
        <h2 class="mt-4">Mode Bumxpress</h2>
        <div class="config-row"><span class="config-label">Environnement</span><strong><?= h($environment === 'production' ? 'Production · argent réel' : 'Sandbox · simulation') ?></strong></div>
        <p class="form-help mt-2">Ce réglage dépend de BUMXPRESS_ENVIRONMENT. Les clés de production et de sandbox ne sont pas interchangeables.</p>
        <?php if ($environment === 'production'): ?><div class="notice notice-error">Le mode production peut débiter de vrais portefeuilles et envoyer de vrais retraits.</div><?php endif; ?>
    </article>
    <article class="admin-panel">
        <h2>Identifiants API</h2>
        <div class="config-row"><span class="config-label">Clé API Bumxpress</span><strong><?= $apiKeyReady ? 'Configurée' : 'Manquante' ?></strong></div>
        <div class="config-row"><span class="config-label">Secret API Bumxpress</span><strong><?= $apiSecretReady ? 'Configuré' : 'Manquant' ?></strong></div>
        <div class="config-row"><span class="config-label">Secret de signature webhook</span><strong><?= $webhookReady ? 'Configuré' : 'Manquant' ?></strong></div>
        <div class="config-row"><span class="config-label">URL webhook à enregistrer</span><code>https://forfait-pulse-cm.top/webhook.php</code></div>
        <p class="form-help mt-2">Les valeurs ne sont jamais affichées dans cette page. Configurez-les comme secrets sur Replit, puis de nouveau dans Hostinger lors de la mise en ligne.</p>
        <a class="admin-btn mt-2" href="https://portal.bumxpress.com" target="_blank" rel="noopener">Ouvrir le portail Bumxpress <?= icon('arrow', 15) ?></a>
    </article>
</section>
<section class="admin-panel mt-3">
    <h2>Informations importantes</h2>
    <ul class="mb-0">
        <li>La documentation Bumxpress ne fournit pas d’endpoint de lecture du solde ; vérifiez-le dans le portail marchand.</li>
        <li>Une vente est confirmée uniquement par un webhook signé (ou une vérification serveur de l’état payin).</li>
        <li>La livraison du forfait est manuelle : la commande ne devient verte qu’après paiement confirmé et action « Livrer ».</li>
        <li>Le mot de passe admin est vérifié avec <code>password_verify</code> à partir d’un hash stocké côté serveur.</li>
    </ul>
</section>
<?php require __DIR__ . '/footer.php'; ?>
