<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/app.php';
if (!empty($_SESSION['admin_authenticated'])) {
    redirect('/admin/');
}
$error = '';
$locked = false;
$ipHash = hash_hmac('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), env_value('SESSION_SECRET', __FILE__));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $since = date('Y-m-d H:i:s', time() - 900);
    $attempts = db()->prepare('SELECT COUNT(*) FROM admin_attempts WHERE ip_hash = ? AND succeeded = 0 AND attempted_at >= ?');
    $attempts->execute([$ipHash, $since]);
    $locked = (int) $attempts->fetchColumn() >= 5;
    $password = (string) ($_POST['password'] ?? '');
    $hash = defined('FORFAIT_PULSE_ADMIN_PASSWORD_HASH')
        ? FORFAIT_PULSE_ADMIN_PASSWORD_HASH
        : env_value('ADMIN_PASSWORD_HASH');
    $secretPassword = env_value('FORFAIT_PULSE_ADMIN_PASSWORD');
    $valid = !$locked && ($secretPassword !== ''
        ? hash_equals($secretPassword, $password)
        : ($hash !== '' && password_verify($password, $hash)));
    db()->prepare('INSERT INTO admin_attempts (ip_hash, attempted_at, succeeded) VALUES (?, ?, ?)')
        ->execute([$ipHash, date('Y-m-d H:i:s'), $valid ? 1 : 0]);
    if ($valid) {
        db()->prepare('DELETE FROM admin_attempts WHERE ip_hash = ? AND succeeded = 0')->execute([$ipHash]);
        session_regenerate_id(true);
        $_SESSION['admin_authenticated'] = true;
        $_SESSION['admin_since'] = time();
        $_SESSION['last_admin_activity'] = time();
        redirect('/admin/');
    }
    $error = $locked
        ? 'Trop de tentatives. Réessayez dans 15 minutes.'
        : ($hash === '' && $secretPassword === '' ? 'Le mot de passe admin n’est pas configuré.' : 'Mot de passe incorrect.');
}
$pageTitle = 'Connexion admin';
$currentLang = language();
?>
<!doctype html>
<html lang="<?= h($currentLang) ?>">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f76a0b"><title>Connexion admin · Forfait Pulse</title>
    <link rel="icon" type="image/png" href="/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="/assets/style.css"><link rel="stylesheet" href="/admin/admin.css">
</head>
<body>
<main class="admin-login-wrap">
    <div class="admin-login-logo"><a href="/" aria-label="Retour au site"><img src="/logo.png" alt="Forfait Pulse"></a></div>
    <div class="d-flex justify-content-center mb-3">
        <div class="language-switch" role="group" aria-label="Langue">
            <a class="<?= $currentLang === 'fr' ? 'selected' : '' ?>" href="<?= h(lang_url('fr')) ?>">FR</a>
            <a class="<?= $currentLang === 'en' ? 'selected' : '' ?>" href="<?= h(lang_url('en')) ?>">EN</a>
        </div>
    </div>
    <form class="form-card" method="post" action="/admin/login.php">
        <h1 class="h3 fw-bold mb-2">Connexion administrateur</h1>
        <p class="note-muted mb-4">Accès réservé à l’administration de Forfait Pulse.</p>
        <?php if ($error): ?><div class="notice notice-error"><?= h($error) ?></div><?php endif; ?>
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input class="visually-hidden" type="text" name="username" autocomplete="username" tabindex="-1" value="admin" aria-hidden="true" readonly>
        <div class="form-group"><label for="password">Mot de passe</label><input class="form-control" id="password" name="password" type="password" required autocomplete="current-password"></div>
        <button class="btn-primary w-100 mt-3" type="submit"><?= icon('lock', 17) ?> Se connecter</button>
        <p class="note-muted mt-3 mb-0">Les tentatives répétées sont temporairement bloquées.</p>
    </form>
    <p class="text-center mt-3"><a class="back-link" href="/">Retour au site</a></p>
</main>
</body>
</html>
