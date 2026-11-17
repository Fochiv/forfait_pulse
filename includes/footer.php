</main>
<a class="floating-support" href="<?= h(whatsapp_url()) ?>" target="_blank" rel="noopener" aria-label="Contacter l’assistance sur WhatsApp">
    <?= icon('headset', 25) ?>
</a>
<nav class="mobile-bottom-nav" aria-label="Navigation mobile">
    <a class="<?= $activePage === 'home' ? 'active' : '' ?>" href="/"><?= icon('home', 20) ?><span data-i18n="nav_home">Accueil</span></a>
    <a class="<?= $activePage === 'plans' ? 'active' : '' ?>" href="/forfaits.php"><?= icon('grid', 20) ?><span data-i18n="nav_plans">Forfaits</span></a>
    <a class="<?= $activePage === 'orders' ? 'active' : '' ?>" href="/mes-commandes.php"><?= icon('receipt', 20) ?><span data-i18n="nav_orders">Mes commandes</span></a>
</nav>
<script src="/assets/app.js" defer></script>
</body>
</html>
