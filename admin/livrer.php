<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/app.php';
admin_required();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/admin/commandes.php');
require_csrf();
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    flash('Commande introuvable.', 'error');
    redirect('/admin/commandes.php');
}
$stmt = db()->prepare("UPDATE orders SET delivery_status = 'delivered', delivered_at = ? WHERE id = ? AND payment_status = 'paid' AND delivery_status = 'waiting'");
$stmt->execute([date('Y-m-d H:i:s'), $id]);
flash($stmt->rowCount() ? 'Commande marquée comme livrée. Le client verra le statut vert.' : 'Cette commande n’est pas payée ou a déjà été livrée.', $stmt->rowCount() ? 'success' : 'warning');
redirect('/admin/commandes.php?statut=paid');
