<?php
session_start();
require_once __DIR__ . '/config.php';
if (($_SESSION['user']['role'] ?? null) !== 'guest') {
    redirect('login.php');
}
$stmt = $pdo->prepare("SELECT s.id, s.title, s.location, s.price_per_night, s.max_guests, 1 AS saved,
    (SELECT url FROM images WHERE stay_id = s.id ORDER BY is_thumbnail DESC, id LIMIT 1) AS image_url
    FROM stays s WHERE s.available = 1 AND EXISTS
    (SELECT 1 FROM wishlist_stay ws JOIN wishlists w ON w.id = ws.wishlist_id WHERE ws.stay_id = s.id AND w.user_id = ?)
    ORDER BY s.id DESC");
$stmt->execute([$_SESSION['user']['id']]);
$stays = $stmt->fetchAll();
$pageTitle = 'Mes favoris';
require __DIR__ . '/headers/head.php';
?>
<main class="site-wrap page-stack">
  <div class="section-heading"><div><p class="eyebrow">VOTRE CARNET</p><h1>Mes favoris</h1></div></div>
  <?php if ($stays): ?><div class="stay-grid"><?php foreach ($stays as $cardIndex => $stay) require __DIR__ . '/headers/card.php'; ?></div>
  <?php else: ?><div class="empty-state"><?= icon('heart') ?><h2>Retrouvez ici les logements enregistrés</h2><a class="button outline" href="search.php">Explorer les logements</a></div><?php endif; ?>
</main>
<?php require __DIR__ . '/headers/footer.php'; ?>
</body>
</html>
