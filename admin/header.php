<?php
$adminTitle = $adminTitle ?? 'Administration';
$adminActive = $adminActive ?? '';
$currentLang = language();
?>
<!doctype html>
<html lang="<?= h($currentLang) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#f76a0b">
    <title><?= h($adminTitle) ?> · Forfait Pulse</title>
    <link rel="icon" type="image/png" href="/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="/assets/style.css">
    <link rel="stylesheet" href="/admin/admin.css">
</head>
<body>
<header class="site-header">
    <div class="admin-shell admin-topbar">
        <a class="admin-brand" href="/admin/"><img src="/logo.png" alt=""><strong>Administration</strong></a>
        <nav class="admin-nav" aria-label="Navigation administration">
            <a class="<?= $adminActive === 'dashboard' ? 'active' : '' ?>" href="/admin/">Vue d’ensemble</a>
            <a class="<?= $adminActive === 'bundles' ? 'active' : '' ?>" href="/admin/forfaits.php">Forfaits</a>
            <a class="<?= $adminActive === 'orders' ? 'active' : '' ?>" href="/admin/commandes.php">Commandes</a>
            <a class="<?= $adminActive === 'reviews' ? 'active' : '' ?>" href="/admin/avis.php">Avis</a>
            <a class="<?= $adminActive === 'withdrawal' ? 'active' : '' ?>" href="/admin/retrait.php">Retrait</a>
            <a class="<?= $adminActive === 'settings' ? 'active' : '' ?>" href="/admin/parametres.php">Paramètres</a>
        </nav>
        <div class="language-switch" role="group" aria-label="Langue">
            <a class="<?= $currentLang === 'fr' ? 'selected' : '' ?>" href="<?= h(lang_url('fr')) ?>">FR</a>
            <a class="<?= $currentLang === 'en' ? 'selected' : '' ?>" href="<?= h(lang_url('en')) ?>">EN</a>
        </div>
        <form action="/admin/logout.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <button class="admin-logout" type="submit"><?= icon('logout', 16) ?><span>Déconnexion</span></button>
        </form>
    </div>
</header>
<main class="admin-shell admin-content">
<?php if ($adminFlash = take_flash()): ?>
    <div class="notice notice-<?= h($adminFlash['type']) ?>"><?= h($adminFlash['message']) ?></div>
<?php endif; ?>
