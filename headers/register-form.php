<?php
session_start();
require_once __DIR__ . '/../config.php';
$hostRegistration = $accountRole === 'host';
$next = booking_return(input($_POST, 'next') ?: input($_GET, 'next'));
$error = '';
$name = $email = $phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = input($_POST, 'name');
    $email = input($_POST, 'email');
    $phone = input($_POST, 'phone');
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $confirm = is_string($_POST['confirm'] ?? null) ? $_POST['confirm'] : '';
    if ($name === '' || strlen($name) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255
        || !preg_match('/^[+\d(). -]{6,20}$/D', $phone)) {
        $error = 'Vérifiez votre nom, votre e-mail et votre numéro de téléphone.';
    } elseif (strlen($password) < 8 || strlen($password) > 72 || str_contains($password, "\0") || $password !== $confirm) {
        $error = 'Les mots de passe doivent être identiques et contenir entre 8 et 72 octets.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, telephone, password, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        try {
            $stmt->execute([$name, $email, $phone, password_hash($password, PASSWORD_DEFAULT), $accountRole]);
            if (!isset($_SESSION['user'])) {
                session_regenerate_id(true);
                $_SESSION['user'] = ['id' => (int) $pdo->lastInsertId(), 'role' => $accountRole, 'name' => $name, 'email' => $email];
                unset($_SESSION['csrf_token']);
                redirect($hostRegistration ? 'host-dashboard.php' : ($next ?: 'index.php'));
            }
            redirect('profile.php');
        } catch (PDOException $exception) {
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }
            $error = 'Un compte existe déjà avec cette adresse e-mail.';
        }
    }
}
$pageTitle = $hostRegistration ? 'Devenir hôte' : 'Créer un compte voyageur';
require __DIR__ . '/head.php';
?>
<main class="site-wrap page-stack">
  <div class="auth-card">
    <p class="eyebrow"><?= $hostRegistration ? 'CÔTÉ HÔTE' : 'VOTRE ESPACE' ?></p><h1><?= e($pageTitle) ?></h1>
    <?php if ($error): ?><p class="notice error" role="alert"><?= e($error) ?></p><?php endif; ?>
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="next" value="<?= e($next) ?>">
      <label>Nom complet<input name="name" maxlength="255" value="<?= e($name) ?>" autocomplete="name" required></label>
      <label>Adresse e-mail<input name="email" type="email" value="<?= e($email) ?>" autocomplete="email" required></label>
      <label>Téléphone<input name="phone" type="tel" value="<?= e($phone) ?>" maxlength="20" autocomplete="tel" required></label>
      <label>Mot de passe<input name="password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required></label>
      <label>Confirmer le mot de passe<input name="confirm" type="password" minlength="8" maxlength="72" autocomplete="new-password" required></label>
      <button class="button primary" type="submit">Créer mon compte</button>
    </form>
    <p>Déjà inscrit ? <a href="login.php<?= $next ? '?' . e(http_build_query(['next' => $next])) : '' ?>">Se connecter</a></p>
  </div>
</main>
<?php require __DIR__ . '/footer.php'; ?>
</body>
</html>
