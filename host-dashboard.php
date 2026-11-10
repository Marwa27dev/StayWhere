<?php
session_start();
require_once __DIR__ . '/config.php';
if (($_SESSION['user']['role'] ?? null) !== 'host') {
    redirect('login.php');
}
$stmt = $pdo->prepare("SELECT (SELECT COUNT(*) FROM stays WHERE user_id = ?) AS listings,
    (SELECT COUNT(*) FROM reservations r JOIN stays s ON s.id = r.stay_id WHERE s.user_id = ? AND r.status = 'pending') AS pending");
$stmt->execute([$_SESSION['user']['id'], $_SESSION['user']['id']]);
$counts = $stmt->fetch();
$pageTitle = 'Espace hôte';
require __DIR__ . '/headers/head.php';
?>
<main class="site-wrap page-stack">
  <div class="section-heading"><div><p class="eyebrow">ESPACE HÔTE</p><h1>Bonjour, <?= e($_SESSION['user']['name']) ?></h1><p>Gérez vos logements et les demandes de vos voyageurs.</p></div><a class="button primary" href="add-stay.php">Publier un logement <?= icon('arrow') ?></a></div>
  <div class="dashboard-grid">
    <a class="dashboard-card" href="my-stays.php"><?= icon('arch') ?><strong><?= (int) $counts['listings'] ?></strong><span>Mes logements</span></a>
    <a class="dashboard-card" href="requests.php"><?= icon('calendar') ?><strong><?= (int) $counts['pending'] ?></strong><span>Demandes en attente</span></a>
  </div>
</main>
<?php require __DIR__ . '/headers/footer.php'; ?>
</body>
</html>
