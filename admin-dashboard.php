<?php
session_start();
require_once __DIR__ . '/config.php';
if (($_SESSION['user']['role'] ?? null) !== 'admin') {
    redirect('login.php');
}
$counts = $pdo->query("SELECT (SELECT COUNT(*) FROM users WHERE role = 'host') AS hosts,
    (SELECT COUNT(*) FROM users WHERE role = 'guest') AS guests, (SELECT COUNT(*) FROM stays) AS stays")->fetch();
$pageTitle = 'Administration';
require __DIR__ . '/headers/head.php';
?>
<main class="site-wrap page-stack">
  <div class="section-heading"><div><p class="eyebrow">STAYWHERE</p><h1>Administration</h1><p>Gérez les comptes et les annonces de la plateforme.</p></div></div>
  <div class="dashboard-grid">
    <a class="dashboard-card" href="hosts.php"><?= icon('user') ?><strong><?= (int) $counts['hosts'] ?></strong><span>Hôtes</span></a>
    <a class="dashboard-card" href="guests.php"><?= icon('users') ?><strong><?= (int) $counts['guests'] ?></strong><span>Voyageurs</span></a>
    <a class="dashboard-card" href="stays.php"><?= icon('arch') ?><strong><?= (int) $counts['stays'] ?></strong><span>Logements</span></a>
  </div>
</main>
<?php require __DIR__ . '/headers/footer.php'; ?>
</body>
</html>
