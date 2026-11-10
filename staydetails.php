<?php
session_start();
require_once __DIR__ . '/config.php';

$stayId = (int) input($_GET, 'id');
$userId = (int) ($_SESSION['user']['id'] ?? 0);
$role = $_SESSION['user']['role'] ?? null;
$stmt = $pdo->prepare("SELECT s.*, u.name AS host_name FROM stays s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
$stmt->execute([$stayId]);
$stay = $stmt->fetch();
$ownsStay = $stay && (int) $stay['user_id'] === $userId;
if (!$stay || (!$stay['available'] && !$ownsStay && $role !== 'admin')) {
    http_response_code(404);
    $pageTitle = 'Logement introuvable';
    require __DIR__ . '/headers/head.php';
    echo '<main class="site-wrap page-stack"><div class="empty-state"><h1>Logement introuvable</h1><a class="button outline" href="search.php">Voir les logements</a></div></main>';
    require __DIR__ . '/headers/footer.php';
    echo '</body></html>';
    exit;
}

$arrival = input($_GET, 'check_in');
$departure = input($_GET, 'check_out');
$guestsValue = input($_GET, 'guests') ?: '1';
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if ($role !== 'guest') {
        http_response_code(403);
        exit('Un compte voyageur est nécessaire pour réserver.');
    }
    $arrival = input($_POST, 'checkIn');
    $departure = input($_POST, 'checkOut');
    $guestsValue = input($_POST, 'guests');
    $nights = booking_nights($arrival, $departure);
    $guests = guest_count($guestsValue);

    if ($nights === null) {
        $error = 'Vérifiez les dates de votre séjour. La durée maximale est de 90 nuits.';
    } elseif (!$guests || $guests > (int) $stay['max_guests']) {
        $error = 'Le nombre de voyageurs dépasse la capacité du logement ou est invalide.';
    } else {
        $pdo->beginTransaction();
        try {
            $lockGuest = $pdo->prepare("SELECT id FROM users WHERE id = ? FOR UPDATE");
            $lockGuest->execute([$userId]);
            $activeGuest = $lockGuest->fetchColumn();
            $lockStay = $pdo->prepare("SELECT available, price_per_night, max_guests FROM stays WHERE id = ? FOR UPDATE");
            $lockStay->execute([$stayId]);
            $current = $lockStay->fetch();
            if (!$activeGuest || !$current || !$current['available'] || $guests > (int) $current['max_guests']
                || has_confirmed_overlap($pdo, $stayId, $arrival, $departure)) {
                $error = 'Le logement n’est plus disponible pour cette demande. Essayez d’autres dates.';
            } else {
                $overlap = $pdo->prepare("SELECT 1 FROM reservations WHERE user_id = ? AND status IN ('confirmed','pending')
                    AND check_in < ? AND check_out > ? LIMIT 1");
                $overlap->execute([$userId, $departure, $arrival]);
                if ($overlap->fetchColumn()) {
                    $error = 'Vous avez déjà une demande ou une réservation qui chevauche ces dates.';
                } else {
                    $insert = $pdo->prepare("INSERT INTO reservations
                        (user_id, stay_id, check_in, check_out, guests, total_price, status, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())");
                    $insert->execute([$userId, $stayId, $arrival, $departure, $guests, $nights * (float) $current['price_per_night']]);
                }
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
        if (!$error) {
            $_SESSION['reservation_notice'] = 'Votre demande a été envoyée à l’hôte.';
            redirect('my-reservations.php');
        }
    }
}

$stmt = $pdo->prepare("SELECT url FROM images WHERE stay_id = ? ORDER BY is_thumbnail DESC, id");
$stmt->execute([$stayId]);
$images = array_values(array_unique(array_map('image_path', $stmt->fetchAll(PDO::FETCH_COLUMN))));
$images = $images ?: [image_path(null)];
$stmt = $pdo->prepare("SELECT a.name, NULL AS quantity FROM stay_amenity sa JOIN amenities a ON a.id = sa.amenity_id WHERE sa.stay_id = ?
    UNION ALL SELECT f.name, sf.count FROM stay_facility sf JOIN facilities f ON f.id = sf.facility_id WHERE sf.stay_id = ? ORDER BY name");
$stmt->execute([$stayId, $stayId]);
$features = $stmt->fetchAll();
$favorite = false;
if ($role === 'guest') {
    $stmt = $pdo->prepare("SELECT 1 FROM wishlists w JOIN wishlist_stay ws ON w.id = ws.wishlist_id
        WHERE w.user_id = ? AND ws.stay_id = ? LIMIT 1");
    $stmt->execute([$userId, $stayId]);
    $favorite = (bool) $stmt->fetchColumn();
}
$filters = array_filter(['check_in' => $arrival, 'check_out' => $departure, 'guests' => $guestsValue]);
$back = 'search.php' . ($filters ? '?' . http_build_query($filters) : '');
$detailUrl = 'staydetails.php?' . http_build_query(['id' => $stayId] + $filters);
$map = https_url($stay['map_url']);
$pageTitle = $stay['title'];
require __DIR__ . '/headers/head.php';
?>
<main class="site-wrap page-stack">
  <a class="back-link" href="<?= e($back) ?>"><?= icon('back') ?> Explorer les logements</a>
  <div class="detail-title">
    <div><p class="eyebrow">VOTRE PROCHAIN SÉJOUR</p><h1><?= e($stay['title']) ?></h1><p class="card-location"><?= icon('pin') ?><?= e($stay['location']) ?></p></div>
    <?php if ($role === 'guest'): ?>
      <form method="post" action="wishlist-action.php">
        <?= csrf_field() ?><input type="hidden" name="stay_id" value="<?= $stayId ?>"><input type="hidden" name="return" value="<?= e($detailUrl) ?>">
        <input type="hidden" name="save" value="<?= $favorite ? 0 : 1 ?>">
        <button class="button outline <?= $favorite ? 'saved' : '' ?>" type="submit" aria-pressed="<?= $favorite ? 'true' : 'false' ?>"><?= icon('heart') ?> <?= $favorite ? 'Retirer des favoris' : 'Enregistrer' ?></button>
      </form>
    <?php elseif ($ownsStay): ?><a class="button outline" href="edit-stay.php?id=<?= $stayId ?>">Modifier mon logement</a><?php endif; ?>
  </div>
  <div class="detail-gallery <?= count($images) === 1 ? 'single' : (count($images) === 2 ? 'pair' : '') ?>" aria-label="Photos du logement">
    <?php foreach (array_slice($images, 0, 5) as $index => $image): ?>
      <a href="<?= e($image) ?>" target="_blank" rel="noopener"><img src="<?= e($image) ?>" alt="<?= e($stay['title']) ?>, photo <?= $index + 1 ?>"></a>
    <?php endforeach; ?>
  </div>
  <div class="detail-body">
    <div>
      <section class="detail-section"><h2>Hébergé par <?= e($stay['host_name']) ?></h2><p>Jusqu’à <?= (int) $stay['max_guests'] ?> voyageurs</p></section>
      <section class="detail-section"><h2>À propos du logement</h2><p><?= nl2br(e($stay['description'])) ?></p></section>
      <?php if ($features): ?><section class="detail-section"><h2>Ce que propose le logement</h2><ul class="feature-list">
        <?php foreach ($features as $feature): ?><li><?= icon('check') ?> <?= $feature['quantity'] ? (int) $feature['quantity'] . ' × ' : '' ?><?= e($feature['name']) ?></li><?php endforeach; ?>
      </ul></section><?php endif; ?>
      <section class="detail-section"><h2>Où vous serez</h2><p><?= e($stay['location']) ?></p><?php if ($map): ?><p><a class="back-link" href="<?= e($map) ?>" target="_blank" rel="noopener">Voir sur la carte <?= icon('arrow') ?></a></p><?php endif; ?></section>
    </div>
    <aside class="booking-card" aria-label="Réserver ce logement">
      <h2><?= e(money($stay['price_per_night'])) ?> <small>/ nuit</small></h2>
      <?php if ($error): ?><p class="notice error" role="alert"><?= e($error) ?></p><?php endif; ?>
      <?php if (!$stay['available']): ?><p>Cette annonce est actuellement masquée.</p>
      <?php elseif ($role === 'guest'): ?>
        <form method="post" action="staydetails.php?id=<?= $stayId ?>" class="booking-form" data-dates>
          <?= csrf_field() ?>
          <div class="date-grid">
            <label>Arrivée<input type="date" name="checkIn" value="<?= e($arrival) ?>" min="<?= date('Y-m-d') ?>" required data-arrival></label>
            <label>Départ<input type="date" name="checkOut" value="<?= e($departure) ?>" min="<?= date('Y-m-d') ?>" required data-departure></label>
          </div>
          <label>Voyageurs<input type="number" name="guests" value="<?= e($guestsValue) ?>" min="1" max="<?= (int) $stay['max_guests'] ?>" required></label>
          <output class="booking-total" aria-live="polite" data-total="<?= e($stay['price_per_night']) ?>" data-currency="<?= e(STAY_CURRENCY) ?>">Choisissez vos dates pour voir le total.</output>
          <button class="button primary" type="submit">Envoyer une demande</button>
          <p class="booking-note">L’hôte confirme votre demande. Aucun paiement en ligne.</p>
        </form>
      <?php elseif (!$role): ?>
        <p class="booking-note">Connectez-vous avec un compte voyageur pour demander ce séjour.</p><a class="button primary" href="login.php?<?= e(http_build_query(['next' => $detailUrl])) ?>">Se connecter</a>
      <?php else: ?><p class="booking-note">La réservation est accessible depuis un compte voyageur.</p><?php endif; ?>
    </aside>
  </div>
</main>
<?php require __DIR__ . '/headers/footer.php'; ?>
</body>
</html>
