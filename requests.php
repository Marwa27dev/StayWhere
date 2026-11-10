<?php
session_start();
require_once __DIR__ . '/config.php';

if (($_SESSION['user']['role'] ?? null) !== 'host') {
    redirect('login.php');
}
$hostId = (int) $_SESSION['user']['id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int) input($_POST, 'reservation_id');
    $action = input($_POST, 'action');
    if (!in_array($action, ['approve', 'decline'], true)) {
        http_response_code(400);
        exit;
    }

    $pdo->beginTransaction();
    try {
        $find = $pdo->prepare("SELECT r.stay_id FROM reservations r JOIN stays s ON r.stay_id = s.id WHERE r.id = ? AND s.user_id = ?");
        $find->execute([$id, $hostId]);
        $stayId = (int) $find->fetchColumn();
        $notice = 'Cette demande n’est plus en attente.';
        if ($stayId) {
            $lock = $pdo->prepare("SELECT id FROM stays WHERE id = ? AND user_id = ? FOR UPDATE");
            $lock->execute([$stayId, $hostId]);
            $owned = $lock->fetchColumn();
            $stmt = $pdo->prepare("SELECT check_in, check_out FROM reservations WHERE id = ? AND status = 'pending' FOR UPDATE");
            $stmt->execute([$id]);
            $reservation = $stmt->fetch();
            if ($owned && $reservation) {
                $arrival = $reservation['check_in'];
                $departure = $reservation['check_out'];
                if ($action === 'approve' && $arrival < date('Y-m-d')) {
                    $notice = 'La date d’arrivée est déjà passée.';
                } elseif ($action === 'approve' && has_confirmed_overlap($pdo, $stayId, $arrival, $departure)) {
                    $notice = 'Ces dates chevauchent une réservation confirmée.';
                } else {
                    $status = $action === 'approve' ? 'confirmed' : 'declined';
                    $pdo->prepare("UPDATE reservations SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$status, $id]);
                    if ($action === 'approve') {
                        $pdo->prepare("UPDATE reservations SET status = 'declined', updated_at = NOW()
                            WHERE stay_id = ? AND status = 'pending' AND check_in < ? AND check_out > ?")
                            ->execute([$stayId, $departure, $arrival]);
                    }
                    $notice = $action === 'approve' ? 'Réservation confirmée.' : 'Demande refusée.';
                }
            }
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
    $_SESSION['host_notice'] = $notice;
    redirect('requests.php');
}

$stmt = $pdo->prepare("SELECT r.*, u.name AS guest_name, s.title AS stay_title
    FROM reservations r JOIN stays s ON r.stay_id = s.id JOIN users u ON r.user_id = u.id
    WHERE s.user_id = ? ORDER BY r.created_at DESC, r.id DESC");
$stmt->execute([$hostId]);
$reservations = $stmt->fetchAll();
$notice = $_SESSION['host_notice'] ?? '';
unset($_SESSION['host_notice']);
$pageTitle = 'Demandes reçues';
require __DIR__ . '/headers/head.php';
?>
<main class="site-wrap page-stack">
  <a class="back-link" href="host-dashboard.php"><?= icon('back') ?> Espace hôte</a>
  <div class="section-heading"><div><p class="eyebrow">ESPACE HÔTE</p><h1>Demandes reçues</h1></div></div>
  <?php if ($notice): ?><p class="notice" role="status"><?= e($notice) ?></p><?php endif; ?>
  <?php if (!$reservations): ?><div class="empty-state"><?= icon('calendar') ?><h2>Aucune demande pour l’instant</h2></div><?php endif; ?>
  <div class="reservation-list">
    <?php foreach ($reservations as $reservation): ?>
      <article class="reservation-row">
        <div><p class="eyebrow"><?= e(reservation_status($reservation['status'])) ?></p><h2><?= e($reservation['stay_title']) ?></h2>
          <p><?= e($reservation['guest_name']) ?> · <?= (int) $reservation['guests'] ?> voyageur(s)</p>
          <p><?= e($reservation['check_in']) ?> → <?= e($reservation['check_out']) ?> · <?= e(money($reservation['total_price'])) ?></p>
        </div>
        <?php if ($reservation['status'] === 'pending'): ?><form method="post" class="reservation-actions">
          <?= csrf_field() ?><input type="hidden" name="reservation_id" value="<?= (int) $reservation['id'] ?>">
          <button name="action" value="decline" class="button outline">Refuser</button>
          <button name="action" value="approve" class="button primary">Confirmer</button>
        </form><?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div>
</main>
<?php require __DIR__ . '/headers/footer.php'; ?>
</body>
</html>
