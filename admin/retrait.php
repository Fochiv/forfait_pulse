<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bumxpress.php';
admin_required();
$adminActive = 'withdrawal';
$adminTitle = 'Retrait';
$withdrawals = db()->query('SELECT * FROM withdrawals ORDER BY created_at DESC LIMIT 100')->fetchAll();
$configured = bumxpress_is_configured();
require __DIR__ . '/header.php';
?>
<div class="admin-page-title">
    <div><h1>Faire un retrait</h1><p>Envoyez un paiement de votre portefeuille marchand Bumxpress vers Mobile Money.</p></div>
</div>
<div class="notice notice-warning">Un retrait en mode production envoie réellement de l’argent et peut être irréversible. Vérifiez le montant, le numéro et le bénéficiaire avant de confirmer.</div>
<?php if (!$configured): ?><div class="notice notice-error">Bumxpress n’est pas configuré. Ajoutez la clé API, le secret API et le secret webhook avant d’activer les retraits.</div><?php endif; ?>
<section class="admin-grid two-col">
    <article class="admin-panel">
        <h2>Informations du bénéficiaire</h2>
        <form action="/admin/retrait-creer.php" method="post" data-payout-form data-payout-fee-rate="<?= h((string) PAYOUT_FEE_RATE) ?>">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <div class="admin-form-grid two-col">
                <div class="form-group"><label for="first_name">Prénom</label><input class="form-control" id="first_name" name="first_name" required maxlength="80"></div>
                <div class="form-group"><label for="last_name">Nom</label><input class="form-control" id="last_name" name="last_name" required maxlength="80"></div>
                <div class="form-group"><label for="email">E-mail</label><input class="form-control" id="email" name="email" type="email" required maxlength="190"></div>
                <div class="form-group"><label for="phone">Numéro Mobile Money</label><input class="form-control" id="phone" name="phone" inputmode="tel" placeholder="6XX XXX XXX" required maxlength="18"></div>
                <div class="form-group"><label for="operator">Opérateur destinataire</label><select class="form-select" id="operator" name="operator" required><option value="mtn">MTN Mobile Money</option><option value="orange">Orange Money</option></select></div>
                <div class="form-group"><label for="amount">Budget total du retrait (FCFA)</label><input class="form-control" id="amount" name="amount" type="number" min="203" max="400000" step="1" required data-payout-budget><span class="form-help">Le montant inclut les frais estimés du retrait.</span></div>
                <div class="form-group"><label for="estimated_net">Montant net estimé reçu (FCFA)</label><input class="form-control" id="estimated_net" type="text" value="0 FCFA" readonly aria-live="polite" data-payout-net><span class="form-help" data-payout-fee>Les frais estimés à 1,5 % s’afficheront ici.</span></div>
            </div>
            <div class="callout mt-3"><?= icon('shield', 18) ?><p>L’estimation est recalculée sans recharger la page. Le serveur vérifie le budget et crée le devis avant l’envoi réel.</p></div>
            <button class="admin-btn primary mt-3" type="submit" <?= !$configured ? 'disabled' : '' ?>><?= icon('wallet', 16) ?> Confirmer le retrait</button>
        </form>
    </article>
    <article class="admin-panel">
        <h2>Historique des retraits</h2>
        <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Référence</th><th>Bénéficiaire</th><th>Budget / reçu</th><th>Opérateur</th><th>État</th></tr></thead><tbody>
        <?php foreach ($withdrawals as $row): ?>
            <tr><td><?= h($row['public_ref']) ?><br><span class="note-muted"><?= h($row['created_at']) ?></span></td><td><?= h($row['recipient_name']) ?><br><span class="note-muted"><?= h($row['recipient_phone']) ?></span></td><td><?php if ($row['budget_amount'] !== null): ?><?= money($row['budget_amount']) ?><br><span class="note-muted">Net reçu : <?= money($row['amount']) ?></span><?php else: ?><?= money($row['amount']) ?><br><span class="note-muted">Montant envoyé</span><?php endif; ?></td><td><?= h($row['operator_code'] === 'CM_MTN_MOMO' ? 'MTN' : 'Orange') ?></td><td><span class="status-pill <?= $row['status'] === 'completed' ? 'status-delivered' : ($row['status'] === 'failed' ? 'status-failed' : 'status-pending') ?>"><?= h(match ($row['status']) {'completed' => 'Terminé', 'failed' => 'Échoué', default => 'En attente'}) ?></span><?php if ($row['failure_reason']): ?><div class="note-muted"><?= h($row['failure_reason']) ?></div><?php endif; ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$withdrawals): ?><tr><td colspan="5" class="note-muted text-center py-4">Aucun retrait enregistré.</td></tr><?php endif; ?>
        </tbody></table></div>
    </article>
</section>
<?php require __DIR__ . '/footer.php'; ?>
