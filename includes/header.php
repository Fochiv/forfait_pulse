<?php
$pageTitle = $pageTitle ?? 'Forfait Pulse';
$currentLang = language();
$activePage = $activePage ?? '';
?>
<!doctype html>
<html lang="<?= h($currentLang) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#f76a0b">
    <title><?= h($pageTitle) ?> · Forfait Pulse</title>
    <link rel="icon" type="image/png" href="/logo.png">
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a href="/" class="brand-mark" aria-label="Forfait Pulse, accueil"><img src="/logo.png" alt="Forfait Pulse"></a>
        <nav class="desktop-nav" aria-label="Navigation principale">
            <a class="<?= $activePage === 'home' ? 'active' : '' ?>" href="/" data-i18n="nav_home">Accueil</a>
            <a class="<?= $activePage === 'plans' ? 'active' : '' ?>" href="/forfaits.php" data-i18n="nav_plans">Forfaits</a>
            <a class="<?= $activePage === 'orders' ? 'active' : '' ?>" href="/mes-commandes.php" data-i18n="nav_orders">Mes commandes</a>
        </nav>
        <div class="header-actions">
            <div class="language-switch" role="group" aria-label="Langue">
                <a class="<?= $currentLang === 'fr' ? 'selected' : '' ?>" href="<?= h(lang_url('fr')) ?>">FR</a>
                <a class="<?= $currentLang === 'en' ? 'selected' : '' ?>" href="<?= h(lang_url('en')) ?>">EN</a>
            </div>
            <a class="support-small" href="<?= h(whatsapp_url()) ?>" target="_blank" rel="noopener" aria-label="Assistance WhatsApp"><?= icon('headset', 20) ?></a>
        </div>
    </div>
</header>
<main class="page-main">
