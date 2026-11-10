<?php
require_once __DIR__ . '/../app.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$navRole = $_SESSION['user']['role'] ?? null;
?>
<header class="site-header">
  <nav class="site-wrap nav-row" aria-label="Navigation principale">
    <a href="index.php" class="brand"><span class="brand-icon"><?= icon('logo') ?></span>Stay<span>Where</span></a>
    <div class="nav-main">
      <a href="index.php" class="<?= basename($_SERVER['SCRIPT_NAME']) === 'index.php' ? 'current' : '' ?>">Découvrir</a>
      <a href="search.php" class="<?= basename($_SERVER['SCRIPT_NAME']) === 'search.php' ? 'current' : '' ?>">Les logements</a>
    </div>
    <div class="nav-actions">
      <?php if ($navRole === 'host'): ?>
        <a class="nav-host" href="host-dashboard.php">Espace hôte</a>
      <?php elseif ($navRole === 'admin'): ?>
        <a class="nav-host" href="admin-dashboard.php">Administration</a>
      <?php else: ?>
        <a class="nav-host" href="become-host.php">Devenir hôte</a>
      <?php endif; ?>
      <?php if (!$navRole): ?>
        <a href="login.php" class="nav-account"><?= icon('user') ?><span>Connexion</span></a>
      <?php else: ?>
        <details class="nav-menu">
          <summary class="nav-account"><?= icon('user') ?><span>Mon compte</span></summary>
          <div class="nav-menu-items">
            <?php if ($navRole === 'guest'): ?>
              <a href="my-reservations.php">Mes réservations</a><a href="wishlist.php">Mes favoris</a>
            <?php elseif ($navRole === 'host'): ?>
              <a href="my-stays.php">Mes logements</a><a href="add-stay.php">Publier un logement</a><a href="requests.php">Demandes reçues</a>
            <?php endif; ?>
            <a href="profile.php">Mon profil</a>
            <form method="post" action="logout.php"><?= csrf_field() ?><button type="submit">Déconnexion</button></form>
          </div>
        </details>
      <?php endif; ?>
    </div>
  </nav>
</header>
