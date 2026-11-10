<?php
session_start();
require_once __DIR__ . '/config.php';
if (($_SESSION['user']['role'] ?? null) !== 'guest') {
    redirect('login.php');
}
$userId = (int) $_SESSION['user']['id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int) input($_POST, 'reservation_id');
    if (input($_POST, 'action') === 'cancel') {
        $stmt = $pdo->prepare("UPDATE reservations SET status = 'cancelled', updated_at = NOW()
            WHERE id = ? AND user_id = ? AND status = 'pending'");
        $stmt->execute([$id, $userId]);
        $_SESSION['reservation_notice'] = $stmt->rowCount() ? 'Votre demande a été annulée.' : 'Cette demande n’est plus en attente.';
    } elseif (input($_POST, 'action') === 'delete') {
        $pdo->prepare("DELETE FROM reservations WHERE id = ? AND user_id = ? AND status IN ('cancelled','declined')")
            ->execute([$id, $userId]);
    }
    redirect('my-reservations.php');
}
$stmt = $pdo->prepare("SELECT r.*, s.title, s.location, s.available FROM reservations r
    JOIN stays s ON r.stay_id = s.id WHERE r.user_id = ? ORDER BY r.created_at DESC, r.id DESC");
$stmt->execute([$userId]);
$reservations = $stmt->fetchAll();
$notice = $_SESSION['reservation_notice'] ?? '';
unset($_SESSION['reservation_notice']);
$pageTitle = 'Mes réservations';
require __DIR__ . '/headers/head.php';
?>
<main class="site-wrap page-stack">
  <div class="section-heading"><div><p class="eyebrow">VOS SÉJOURS</p><h1>Mes réservations</h1></div></div>
  <?php if ($notice): ?><p class="notice" role="status"><?= e($notice) ?></p><?php endif; ?>
  <?php if (!$reservations): ?><div class="empty-state"><?= icon('calendar') ?><h2>Votre prochain séjour vous attend</h2><a class="button outline" href="search.php">Explorer les logements</a></div><?php endif; ?>
  <div class="reservation-list">
    <?php foreach ($reservations as $reservation): ?>
      <article class="reservation-row">
        <div><p class="eyebrow"><?= e(reservation_status($reservation['status'])) ?></p><h2><?= e($reservation['title']) ?></h2>
          <p><?= e($reservation['location']) ?> · <?= (int) $reservation['guests'] ?> voyageur(s)</p>
          <p><?= e($reservation['check_in']) ?> → <?= e($reservation['check_out']) ?> · <?= e(money($reservation['total_price'])) ?></p>
        </div>
        <div class="reservation-actions">
          <?php if ($reservation['available']): ?><a class="button outline" href="staydetails.php?id=<?= (int) $reservation['stay_id'] ?>">Voir le logement</a><?php endif; ?>
          <?php if ($reservation['status'] !== 'confirmed'): ?><form method="post">
            <?= csrf_field() ?><input type="hidden" name="reservation_id" value="<?= (int) $reservation['id'] ?>">
            <?php if ($reservation['status'] === 'pending'): ?><button name="action" value="cancel" class="button outline">Annuler la demande</button>
            <?php else: ?><button name="action" value="delete" class="button outline">Retirer de la liste</button><?php endif; ?>
          </form><?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</main>
<?php require __DIR__ . '/headers/footer.php'; ?>
</body>
</html>
