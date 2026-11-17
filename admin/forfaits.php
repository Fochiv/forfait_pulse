<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/app.php';
admin_required();
$adminActive = 'bundles';
$adminTitle = 'Gestion des forfaits';
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT) ?: 0;
$editBundle = null;
if ($editId) {
    $edit = db()->prepare('SELECT * FROM bundles WHERE id = ?');
    $edit->execute([$editId]);
    $editBundle = $edit->fetch() ?: null;
}
$bundles = db()->query('SELECT * FROM bundles ORDER BY operator_code, sort_order, id')->fetchAll();
require __DIR__ . '/header.php';
?>
<div class="admin-page-title"><div><h1>Gestion des forfaits</h1><p>Créez, modifiez ou désactivez les offres proposées aux clients.</p></div></div>
<section class="admin-grid two-col">
    <article class="admin-panel">
        <h2><?= $editBundle ? 'Modifier le forfait' : 'Ajouter un forfait' ?></h2>
        <form action="/admin/forfait-action.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="action" value="<?= $editBundle ? 'update' : 'create' ?>">
            <input type="hidden" name="id" value="<?= (int) ($editBundle['id'] ?? 0) ?>">
            <div class="admin-form-grid two-col">
                <div class="form-group"><label for="operator_code">Opérateur</label><select class="form-select" id="operator_code" name="operator_code" required>
                    <?php foreach (['orange','mtn','camtel'] as $code): ?><option value="<?= h($code) ?>" <?= ($editBundle['operator_code'] ?? '') === $code ? 'selected' : '' ?>><?= h(operator_name($code)) ?></option><?php endforeach; ?>
                </select></div>
                <div class="form-group"><label for="name">Nom du forfait</label><input class="form-control" id="name" name="name" required maxlength="120" value="<?= h($editBundle['name'] ?? '') ?>"></div>
                <div class="form-group"><label for="volume_label">Volume / débit</label><input class="form-control" id="volume_label" name="volume_label" required maxlength="80" placeholder="5 Go ou Illimité · 2 Mb/s" value="<?= h($editBundle['volume_label'] ?? '') ?>"></div>
                <div class="form-group"><label for="validity_days">Validité (jours)</label><input class="form-control" id="validity_days" name="validity_days" type="number" min="1" max="365" required value="<?= h((string) ($editBundle['validity_days'] ?? '30')) ?>"></div>
                <div class="form-group"><label for="price">Prix (FCFA)</label><input class="form-control" id="price" name="price" type="number" min="200" max="400000" required value="<?= h((string) ($editBundle['price'] ?? '')) ?>"></div>
                <div class="form-group"><label for="sort_order">Ordre d’affichage</label><input class="form-control" id="sort_order" name="sort_order" type="number" min="0" max="999" value="<?= h((string) ($editBundle['sort_order'] ?? '0')) ?>"></div>
                <div class="form-group" style="grid-column:1/-1"><label for="description">Description courte</label><input class="form-control" id="description" name="description" maxlength="255" value="<?= h($editBundle['description'] ?? '') ?>"></div>
            </div>
            <div class="admin-actions mt-3"><button class="admin-btn primary" type="submit"><?= $editBundle ? 'Enregistrer les changements' : 'Créer le forfait' ?></button><?php if ($editBundle): ?><a class="admin-btn" href="/admin/forfaits.php">Annuler</a><?php endif; ?></div>
        </form>
    </article>
    <article class="admin-panel">
        <h2>Offres disponibles</h2>
        <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Forfait</th><th>Prix</th><th>État</th><th>Actions</th></tr></thead><tbody>
            <?php foreach ($bundles as $bundle): ?>
                <tr>
                    <td class="break"><strong><?= h(operator_name($bundle['operator_code'])) ?> · <?= h($bundle['name']) ?></strong><br><span class="note-muted"><?= h($bundle['volume_label']) ?> · <?= (int) $bundle['validity_days'] ?> jours</span></td>
                    <td><?= money($bundle['price']) ?></td><td><?= (int) $bundle['is_active'] ? 'Actif' : 'Inactif' ?></td>
                    <td><div class="admin-actions">
                        <a class="admin-btn" href="/admin/forfaits.php?edit=<?= (int) $bundle['id'] ?>">Modifier</a>
                        <form action="/admin/forfait-action.php" method="post"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $bundle['id'] ?>"><input type="hidden" name="action" value="toggle"><button class="admin-btn" type="submit"><?= (int) $bundle['is_active'] ? 'Désactiver' : 'Activer' ?></button></form>
                        <form action="/admin/forfait-action.php" method="post" data-confirm="Supprimer ce forfait du catalogue ? Les commandes déjà enregistrées resteront dans l’historique."><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $bundle['id'] ?>"><input type="hidden" name="action" value="delete"><button class="admin-btn danger" type="submit">Supprimer</button></form>
                    </div></td>
                </tr>
            <?php endforeach; ?>
        </tbody></table></div>
    </article>
</section>
<?php require __DIR__ . '/footer.php'; ?>
