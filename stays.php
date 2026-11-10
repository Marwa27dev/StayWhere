<?php
session_start();
require_once __DIR__ . '/config.php';
if (($_SESSION['user']['role'] ?? null) !== 'admin') {
    redirect('login.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $_SESSION['listing_notice'] = delete_listing($pdo, (int) input($_POST, 'delete'))
        ? 'Le logement a été supprimé.' : 'Ce logement ne peut pas être supprimé.';
    redirect('stays.php');
}
$stays = $pdo->query("SELECT s.id, s.title, s.location, s.available, s.price_per_night, u.name AS host_name
    FROM stays s JOIN users u ON u.id = s.user_id ORDER BY s.id DESC")->fetchAll();
$notice = $_SESSION['listing_notice'] ?? '';
unset($_SESSION['listing_notice']);
$pageTitle = 'Gestion des logements';
require __DIR__ . '/headers/head.php';
?>
<main class="site-wrap page-stack">
  <a class="back-link" href="admin-dashboard.php"><?= icon('back') ?> Administration</a>
  <div class="section-heading"><div><p class="eyebrow">CATALOGUE</p><h1>Logements</h1></div></div>
  <?php if ($notice): ?><p class="notice" role="status"><?= e($notice) ?></p><?php endif; ?>
  <?php if (!$stays): ?><div class="empty-state"><?= icon('arch') ?><h2>Aucun logement</h2></div><?php endif; ?>
  <div class="reservation-list"><?php foreach ($stays as $stay): ?>
    <article class="reservation-row">
      <div><p class="eyebrow"><?= $stay['available'] ? 'PUBLIÉ' : 'MASQUÉ' ?></p><h2><?= e($stay['title']) ?></h2><p><?= e($stay['location']) ?> · <?= e($stay['host_name']) ?> · <?= e(money($stay['price_per_night'])) ?> / nuit</p></div>
      <div class="reservation-actions">
        <a class="button outline" href="staydetails.php?id=<?= (int) $stay['id'] ?>">Voir</a>
        <form method="post" onsubmit="return confirm('Supprimer ce logement ?')"><?= csrf_field() ?><button class="button outline" name="delete" value="<?= (int) $stay['id'] ?>">Supprimer</button></form>
      </div>
    </article>
  <?php endforeach; ?></div>
</main>
<?php require __DIR__ . '/headers/footer.php'; ?>
</body>
</html>
