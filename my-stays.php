<?php
session_start();
require_once __DIR__ . '/config.php';
if (($_SESSION['user']['role'] ?? null) !== 'host') {
    redirect('login.php');
}
$hostId = (int) $_SESSION['user']['id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $_SESSION['listing_notice'] = delete_listing($pdo, (int) input($_POST, 'delete'), $hostId)
        ? 'Le logement a été supprimé.' : 'Ce logement ne peut pas être supprimé.';
    redirect('my-stays.php');
}
$stmt = $pdo->prepare("SELECT s.id, s.title, s.location, s.price_per_night, s.available,
    (SELECT url FROM images WHERE stay_id = s.id ORDER BY is_thumbnail DESC, id LIMIT 1) AS image_url
    FROM stays s WHERE s.user_id = ? ORDER BY s.id DESC");
$stmt->execute([$hostId]);
$stays = $stmt->fetchAll();
$notice = $_SESSION['listing_notice'] ?? '';
unset($_SESSION['listing_notice']);
$pageTitle = 'Mes logements';
require __DIR__ . '/headers/head.php';
?>
<main class="site-wrap page-stack">
  <a class="back-link" href="host-dashboard.php"><?= icon('back') ?> Espace hôte</a>
  <div class="section-heading"><div><p class="eyebrow">ESPACE HÔTE</p><h1>Mes logements</h1></div><a href="add-stay.php" class="button primary">Publier un logement</a></div>
  <?php if ($notice): ?><p class="notice" role="status"><?= e($notice) ?></p><?php endif; ?>
  <?php if (!$stays): ?><div class="empty-state"><?= icon('arch') ?><h2>Vous n’avez pas encore publié de logement</h2></div><?php endif; ?>
  <div class="reservation-list">
    <?php foreach ($stays as $stay): ?><article class="reservation-row">
      <img class="host-thumb" src="<?= e(image_path($stay['image_url'])) ?>" alt="">
      <div class="reservation-info"><p class="eyebrow"><?= $stay['available'] ? 'PUBLIÉ' : 'MASQUÉ' ?></p><h2><?= e($stay['title']) ?></h2><p><?= e($stay['location']) ?> · <?= e(money($stay['price_per_night'])) ?> / nuit</p></div>
      <div class="reservation-actions">
        <a class="button outline" href="staydetails.php?id=<?= (int) $stay['id'] ?>">Voir</a><a class="button outline" href="edit-stay.php?id=<?= (int) $stay['id'] ?>">Modifier</a>
        <form method="post" onsubmit="return confirm('Supprimer ce logement ?')"><?= csrf_field() ?><button class="button outline" name="delete" value="<?= (int) $stay['id'] ?>">Supprimer</button></form>
      </div>
    </article><?php endforeach; ?>
  </div>
</main>
<?php require __DIR__ . '/headers/footer.php'; ?>
</body>
</html>
