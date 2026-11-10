<?php
session_start();
require_once __DIR__ . '/config.php';
if (!isset($_SESSION['user'])) {
    redirect('login.php');
}
$id = (int) $_SESSION['user']['id'];
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch();
$error = '';
$notice = $_SESSION['profile_notice'] ?? '';
unset($_SESSION['profile_notice']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = input($_POST, 'action');
    if ($action === 'details') {
        $name = input($_POST, 'name');
        $email = input($_POST, 'email');
        $phone = input($_POST, 'telephone');
        $bio = input($_POST, 'about_me');
        if ($name === '' || strlen($name) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255
            || !preg_match('/^[+\d(). -]{6,20}$/D', $phone)) {
            $error = 'Vérifiez vos coordonnées.';
        } else {
            try {
                $pdo->prepare('UPDATE users SET name = ?, email = ?, telephone = ?, about_me = ?, updated_at = NOW() WHERE id = ?')
                    ->execute([$name, $email, $phone, $bio, $id]);
            } catch (PDOException $exception) {
                if ($exception->getCode() !== '23000') {
                    throw $exception;
                }
                $error = 'Cette adresse e-mail est déjà utilisée.';
            }
        }
    } elseif ($action === 'password' || $action === 'delete') {
        $current = is_string($_POST['current'] ?? null) ? $_POST['current'] : '';
        $new = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        if (!password_verify($current, $user['password'])) {
            $error = 'Le mot de passe actuel est incorrect.';
        } elseif ($action === 'delete') {
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
            session_unset();
            session_destroy();
            redirect('index.php');
        } elseif (strlen($new) < 8 || strlen($new) > 72 || str_contains($new, "\0") || $new !== ($_POST['confirm'] ?? null)) {
            $error = 'Saisissez deux mots de passe identiques de 8 à 72 octets.';
        } else {
            $pdo->prepare('UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $id]);
            session_regenerate_id(true);
            unset($_SESSION['csrf_token']);
        }
    } elseif ($action === 'photo') {
        $photo = secure_image_upload($_FILES['profile_picture'] ?? [], 'uploads/profile_pics');
        if (!$photo) {
            $error = 'Choisissez une image JPEG, PNG ou WebP de 8 Mo maximum.';
        } else {
            $pdo->prepare('UPDATE users SET profile_picture = ? WHERE id = ?')->execute([$photo, $id]);
            $previous = $user['profile_picture'];
            if ($previous && $previous !== 'uploads/profile_pics/no_pfp.jpg' && str_starts_with($previous, 'uploads/profile_pics/') && image_path($previous) === $previous) {
                unlink(__DIR__ . '/' . $previous);
            }
        }
    } else {
        $error = 'Action inconnue.';
    }
    if (!$error) {
        $_SESSION['profile_notice'] = 'Votre profil a été mis à jour.';
        redirect('profile.php');
    }
}
$pageTitle = 'Mon profil';
require __DIR__ . '/headers/head.php';
?>
<main class="site-wrap page-stack">
  <div class="section-heading"><div><p class="eyebrow">VOTRE ESPACE</p><h1>Mon profil</h1></div></div>
  <?php if ($notice): ?><p class="notice" role="status"><?= e($notice) ?></p><?php endif; ?>
  <?php if ($error): ?><p class="notice error" role="alert"><?= e($error) ?></p><?php endif; ?>
  <div class="profile-layout">
    <form method="post" class="listing-form">
      <?= csrf_field() ?><input type="hidden" name="action" value="details">
      <h2>Mes coordonnées</h2>
      <label>Nom complet<input name="name" value="<?= e($user['name']) ?>" maxlength="255" required></label>
      <label>Adresse e-mail<input type="email" name="email" value="<?= e($user['email']) ?>" required></label>
      <label>Téléphone<input type="tel" name="telephone" value="<?= e($user['telephone']) ?>" maxlength="20" required></label>
      <label>À propos de moi<textarea name="about_me" rows="3"><?= e($user['about_me']) ?></textarea></label>
      <div><button class="button primary">Enregistrer</button></div>
    </form>
    <form method="post" enctype="multipart/form-data" class="listing-form">
      <?= csrf_field() ?><input type="hidden" name="action" value="photo">
      <h2>Photo de profil</h2>
      <?php if ($user['profile_picture']): ?><img class="profile-avatar" src="<?= e(image_path($user['profile_picture'])) ?>" alt="Photo de profil"><?php endif; ?>
      <label>Choisir une image<input type="file" name="profile_picture" accept="image/jpeg,image/png,image/webp" required></label>
      <div><button class="button outline">Mettre à jour la photo</button></div>
    </form>
    <form method="post" class="listing-form">
      <?= csrf_field() ?><input type="hidden" name="action" value="password">
      <h2>Modifier mon mot de passe</h2>
      <label>Mot de passe actuel<input type="password" name="current" autocomplete="current-password" required></label>
      <label>Nouveau mot de passe<input type="password" name="password" autocomplete="new-password" minlength="8" required></label>
      <label>Confirmer le nouveau mot de passe<input type="password" name="confirm" autocomplete="new-password" minlength="8" required></label>
      <div><button class="button outline">Modifier</button></div>
    </form>
    <form method="post" class="listing-form" onsubmit="return confirm('Supprimer votre compte et ses données associées ?')">
      <?= csrf_field() ?><input type="hidden" name="action" value="delete">
      <h2>Supprimer mon compte</h2>
      <label>Mot de passe actuel<input type="password" name="current" autocomplete="current-password" required></label>
      <div><button class="button outline">Supprimer mon compte</button></div>
    </form>
  </div>
</main>
<?php require __DIR__ . '/headers/footer.php'; ?>
</body>
</html>
