<?php
session_start();
require_once __DIR__ . '/config.php';
if (($_SESSION['user']['role'] ?? null) !== 'admin') {
    redirect('login.php');
}
$stmt = $pdo->prepare('SELECT name, email, telephone, about_me, role FROM users WHERE id = ?');
$stmt->execute([(int) input($_GET, 'id')]);
$user = $stmt->fetch();
if (!$user) {
    http_response_code(404);
    exit('Profil introuvable.');
}
$pageTitle = $user['name'];
require __DIR__ . '/headers/head.php';
?>
<main class="site-wrap page-stack">
  <a class="back-link" href="<?= $user['role'] === 'host' ? 'hosts.php' : 'guests.php' ?>"><?= icon('back') ?> Retour aux comptes</a>
  <div class="section-heading"><div><p class="eyebrow">PROFIL</p><h1><?= e($user['name']) ?></h1><p><?= e($user['email']) ?> · <?= e($user['telephone']) ?></p></div></div>
  <?php if ($user['about_me']): ?><section class="detail-section"><h2>À propos</h2><p><?= nl2br(e($user['about_me'])) ?></p></section><?php endif; ?>
</main>
<?php require __DIR__ . '/headers/footer.php'; ?>
</body>
</html>
