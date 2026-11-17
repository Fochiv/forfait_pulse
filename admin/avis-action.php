<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/app.php';
admin_required();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/admin/avis.php');
require_csrf();
$action = (string) ($_POST['action'] ?? '');
$id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
$pdo = db();
if ($action === 'delete' && $id) {
    $pdo->prepare('DELETE FROM reviews WHERE id = ?')->execute([$id]);
    flash('Avis supprimé.', 'success');
} elseif ($action === 'toggle' && $id) {
    $pdo->prepare('UPDATE reviews SET is_visible = CASE WHEN is_visible = 1 THEN 0 ELSE 1 END WHERE id = ?')->execute([$id]);
    flash('Visibilité de l’avis mise à jour.', 'success');
} elseif (in_array($action, ['create', 'update'], true)) {
    $name = trim((string) ($_POST['customer_name'] ?? ''));
    $message = trim((string) ($_POST['message'] ?? ''));
    $rating = filter_var($_POST['rating'] ?? 5, FILTER_VALIDATE_INT);
    $visible = isset($_POST['is_visible']) ? 1 : 0;
    if ($name === '' || $message === '' || !$rating || $rating < 1 || $rating > 5) {
        flash('Vérifiez le nom, le commentaire et la note.', 'error');
    } elseif ($action === 'create') {
        $pdo->prepare('INSERT INTO reviews (customer_name, message, rating, is_visible, created_at) VALUES (?, ?, ?, ?, ?)')
            ->execute([$name, $message, $rating, $visible, date('Y-m-d H:i:s')]);
        flash('Avis ajouté.', 'success');
    } else {
        $pdo->prepare('UPDATE reviews SET customer_name = ?, message = ?, rating = ?, is_visible = ? WHERE id = ?')
            ->execute([$name, $message, $rating, $visible, $id]);
        flash('Avis modifié.', 'success');
    }
}
redirect('/admin/avis.php');
