<?php
session_start();
require_once __DIR__ . '/../config.php';
if (($_SESSION['user']['role'] ?? null) !== 'admin') {
    redirect('login.php');
}
$isHostPage = $managedRole === 'host';
$return = $isHostPage ? 'hosts.php' : 'guests.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int) input($_POST, 'delete');
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = ?");
    $stmt->execute([$id, $managedRole]);
    redirect($return);
}
$stmt = $pdo->prepare('SELECT id, name, email, telephone FROM users WHERE role = ? ORDER BY name, id');
$stmt->execute([$managedRole]);
$users = $stmt->fetchAll();
$pageTitle = $isHostPage ? 'Hôtes' : 'Voyageurs';
require __DIR__ . '/head.php';
?>
<main class="site-wrap page-stack">
  <a class="back-link" href="admin-dashboard.php"><?= icon('back') ?> Administration</a>
  <div class="section-heading"><div><p class="eyebrow">COMPTES</p><h1><?= e($pageTitle) ?></h1></div></div>
  <?php if (!$users): ?><div class="empty-state"><?= icon('users') ?><h2>Aucun compte</h2></div><?php endif; ?>
  <div class="reservation-list"><?php foreach ($users as $user): ?>
    <article class="reservation-row">
      <div><h2><?= e($user['name']) ?></h2><p><?= e($user['email']) ?> · <?= e($user['telephone']) ?></p></div>
      <div class="reservation-actions">
        <a class="button outline" href="about-user.php?id=<?= (int) $user['id'] ?>">Voir le profil</a>
        <form method="post" onsubmit="return confirm('Supprimer ce compte et ses données associées ?')"><?= csrf_field() ?><button class="button outline" name="delete" value="<?= (int) $user['id'] ?>">Supprimer</button></form>
      </div>
    </article>
  <?php endforeach; ?></div>
</main>
<?php require __DIR__ . '/footer.php'; ?>
</body>
</html>
