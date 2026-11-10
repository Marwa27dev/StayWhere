<?php
session_start();
require_once __DIR__ . '/config.php';
$next = booking_return(input($_POST, 'next') ?: input($_GET, 'next'));
$destination = ['admin' => 'admin-dashboard.php', 'host' => 'host-dashboard.php'];
if (isset($_SESSION['user'])) {
    redirect($destination[$_SESSION['user']['role']] ?? ($next ?: 'index.php'));
}
$error = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = input($_POST, 'email');
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        unset($user['password'], $_SESSION['csrf_token']);
        $_SESSION['user'] = $user;
        redirect($destination[$user['role']] ?? ($next ?: 'index.php'));
    }
    $error = 'Adresse e-mail ou mot de passe incorrect.';
}
$pageTitle = 'Connexion';
require __DIR__ . '/headers/head.php';
?>
<main class="site-wrap page-stack">
  <div class="auth-card">
    <p class="eyebrow">VOTRE ESPACE</p><h1>Se connecter</h1>
    <?php if ($error): ?><p class="notice error" role="alert"><?= e($error) ?></p><?php endif; ?>
    <form method="post" action="login.php">
      <?= csrf_field() ?><input type="hidden" name="next" value="<?= e($next) ?>">
      <label>Adresse e-mail<input type="email" name="email" value="<?= e($email) ?>" autocomplete="username" required></label>
      <label>Mot de passe<input type="password" name="password" autocomplete="current-password" required></label>
      <button class="button primary" type="submit">Se connecter</button>
    </form>
    <p>Première visite ? <a href="register.php<?= $next ? '?' . e(http_build_query(['next' => $next])) : '' ?>">Créer un compte voyageur</a></p>
    <p>Vous proposez un logement ? <a href="become-host.php">Créer un compte hôte</a></p>
  </div>
</main>
<?php require __DIR__ . '/headers/footer.php'; ?>
</body>
</html>
