<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/app.php';
admin_required();
$adminActive = 'reviews';
$adminTitle = 'Avis clients';
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT) ?: 0;
$editing = null;
if ($editId) {
    $stmt = db()->prepare('SELECT * FROM reviews WHERE id = ?');
    $stmt->execute([$editId]);
    $editing = $stmt->fetch() ?: null;
}
$reviews = db()->query('SELECT * FROM reviews ORDER BY created_at DESC')->fetchAll();
require __DIR__ . '/header.php';
?>
<div class="admin-page-title"><div><h1>Avis clients</h1><p>Modérez les avis avant leur affichage sur la page d’accueil.</p></div></div>
<section class="admin-grid two-col">
    <article class="admin-panel">
        <h2><?= $editing ? 'Modifier un avis' : 'Ajouter un avis' ?></h2>
        <form action="/admin/avis-action.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
            <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
            <div class="admin-form-grid">
                <div class="form-group"><label for="customer_name">Nom affiché</label><input class="form-control" id="customer_name" name="customer_name" required maxlength="100" value="<?= h($editing['customer_name'] ?? '') ?>"></div>
                <div class="form-group"><label for="rating">Note</label><select class="form-select" id="rating" name="rating"><?php for ($rating = 5; $rating >= 1; $rating--): ?><option value="<?= $rating ?>" <?= (int) ($editing['rating'] ?? 5) === $rating ? 'selected' : '' ?>><?= $rating ?> / 5</option><?php endfor; ?></select></div>
                <div class="form-group"><label for="message">Commentaire</label><textarea class="form-control" id="message" name="message" required maxlength="600" rows="4"><?= h($editing['message'] ?? '') ?></textarea></div>
                <label class="d-flex align-items-center gap-2"><input type="checkbox" name="is_visible" value="1" <?= (int) ($editing['is_visible'] ?? 0) ? 'checked' : '' ?>> Publier immédiatement</label>
            </div>
            <div class="admin-actions mt-3"><button class="admin-btn primary" type="submit"><?= $editing ? 'Enregistrer' : 'Ajouter' ?></button><?php if ($editing): ?><a class="admin-btn" href="/admin/avis.php">Annuler</a><?php endif; ?></div>
        </form>
    </article>
    <article class="admin-panel">
        <h2>Avis enregistrés</h2>
        <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Auteur et avis</th><th>État</th><th>Actions</th></tr></thead><tbody>
        <?php foreach ($reviews as $review): ?>
            <tr><td class="break"><strong><?= h($review['customer_name']) ?> · <?= (int) $review['rating'] ?>/5</strong><br><?= h($review['message']) ?></td><td><?= (int) $review['is_visible'] ? 'Publié' : 'Masqué' ?></td><td><div class="admin-actions">
                <a class="admin-btn" href="/admin/avis.php?edit=<?= (int) $review['id'] ?>">Modifier</a>
                <form action="/admin/avis-action.php" method="post"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $review['id'] ?>"><input type="hidden" name="action" value="toggle"><button class="admin-btn" type="submit"><?= (int) $review['is_visible'] ? 'Masquer' : 'Publier' ?></button></form>
                <form action="/admin/avis-action.php" method="post" data-confirm="Supprimer définitivement cet avis ?"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $review['id'] ?>"><input type="hidden" name="action" value="delete"><button class="admin-btn danger" type="submit">Supprimer</button></form>
            </div></td></tr>
        <?php endforeach; ?>
        <?php if (!$reviews): ?><tr><td colspan="3" class="note-muted text-center py-4">Aucun avis à modérer.</td></tr><?php endif; ?>
        </tbody></table></div>
    </article>
</section>
<?php require __DIR__ . '/footer.php'; ?>
