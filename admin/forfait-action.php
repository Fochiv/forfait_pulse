<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/app.php';
admin_required();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/forfaits.php');
}
require_csrf();
$action = (string) ($_POST['action'] ?? '');
$id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
$pdo = db();

if ($action === 'toggle' && $id) {
    $pdo->prepare('UPDATE bundles SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END WHERE id = ?')->execute([$id]);
    flash('Le statut du forfait a été mis à jour.', 'success');
} elseif ($action === 'delete' && $id) {
    $pdo->prepare('DELETE FROM bundles WHERE id = ?')->execute([$id]);
    flash('Le forfait a été supprimé du catalogue.', 'success');
} elseif (in_array($action, ['create', 'update'], true)) {
    $operator = (string) ($_POST['operator_code'] ?? '');
    $name = trim((string) ($_POST['name'] ?? ''));
    $volume = trim((string) ($_POST['volume_label'] ?? ''));
    $days = filter_var($_POST['validity_days'] ?? null, FILTER_VALIDATE_INT);
    $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_INT);
    $sort = filter_var($_POST['sort_order'] ?? 0, FILTER_VALIDATE_INT);
    $description = trim((string) ($_POST['description'] ?? ''));
    if (!in_array($operator, ['orange', 'mtn', 'camtel'], true) || $name === '' || $volume === ''
        || !$days || $days > 365 || !$price || $price < 200 || $price > 400000 || $sort === false) {
        flash('Vérifiez les champs du forfait. Le prix doit être compris entre 200 et 400 000 FCFA.', 'error');
    } elseif ($action === 'create') {
        $pdo->prepare('INSERT INTO bundles (operator_code, name, volume_label, validity_days, price, description, is_active, sort_order, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?)')
            ->execute([$operator, $name, $volume, $days, $price, $description, $sort, date('Y-m-d H:i:s')]);
        flash('Le forfait a été ajouté.', 'success');
    } else {
        $pdo->prepare('UPDATE bundles SET operator_code = ?, name = ?, volume_label = ?, validity_days = ?, price = ?, description = ?, sort_order = ? WHERE id = ?')
            ->execute([$operator, $name, $volume, $days, $price, $description, $sort, $id]);
        flash('Le forfait a été modifié.', 'success');
    }
}
redirect('/admin/forfaits.php');
