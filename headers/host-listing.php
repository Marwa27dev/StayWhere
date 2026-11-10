<?php
session_start();
require_once __DIR__ . '/../config.php';
if (($_SESSION['user']['role'] ?? null) !== 'host') {
    redirect('login.php');
}

$hostId = (int) $_SESSION['user']['id'];
$id = $edit ? (int) input($_GET, 'id') : 0;
$stay = ['title' => '', 'location' => '', 'description' => '', 'price_per_night' => '', 'max_guests' => 2, 'map_url' => '', 'available' => 1];
$amenities = $pdo->query('SELECT id, name FROM amenities ORDER BY name')->fetchAll();
$facilities = $pdo->query('SELECT id, name FROM facilities ORDER BY name')->fetchAll();
$selected = $counts = $images = [];
if ($edit) {
    $stmt = $pdo->prepare('SELECT * FROM stays WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $hostId]);
    $stay = $stmt->fetch();
    if (!$stay) {
        http_response_code(404);
        exit('Logement introuvable.');
    }
    $stmt = $pdo->prepare('SELECT amenity_id FROM stay_amenity WHERE stay_id = ?');
    $stmt->execute([$id]);
    $selected = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $stmt = $pdo->prepare('SELECT facility_id, count FROM stay_facility WHERE stay_id = ?');
    $stmt->execute([$id]);
    $counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $stmt = $pdo->prepare('SELECT id, url FROM images WHERE stay_id = ? ORDER BY id');
    $stmt->execute([$id]);
    $images = $stmt->fetchAll();
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach (['title', 'location', 'description', 'price_per_night', 'max_guests', 'map_url'] as $field) {
        $stay[$field] = input($_POST, $field);
    }
    $stay['available'] = isset($_POST['available']) ? 1 : 0;
    $selected = array_intersect(array_column($amenities, 'id'), (array) ($_POST['amenities'] ?? []));
    $counts = [];
    $submittedCounts = (array) ($_POST['facility_count'] ?? []);
    foreach ($facilities as $facility) {
        $count = guest_count(input($submittedCounts, (string) $facility['id']));
        if ($count && $count <= 100) {
            $counts[$facility['id']] = $count;
        }
    }

    $maxGuests = guest_count((string) $stay['max_guests']);
    if ($stay['title'] === '' || strlen($stay['title']) > 255 || $stay['location'] === '' || strlen($stay['location']) > 255
        || $stay['description'] === '' || !$maxGuests || $maxGuests > 1000
        || !preg_match('/^\d{1,6}(?:\.\d{1,2})?$/D', $stay['price_per_night']) || (float) $stay['price_per_night'] < 1
        || ($stay['map_url'] !== '' && (!https_url($stay['map_url']) || strlen($stay['map_url']) > 255))) {
        $error = 'Vérifiez le titre, la destination, la description, le tarif, la capacité et le lien HTTPS de la carte.';
    }
    $newPhotos = [];
    $files = $_FILES['images'] ?? [];
    $toRemove = (array) ($_POST['remove_images'] ?? []);
    $removed = array_filter($images, static fn (array $image): bool => in_array((string) $image['id'], $toRemove, true));
    $incoming = count(array_filter($files['error'] ?? [], static fn (int $code): bool => $code !== UPLOAD_ERR_NO_FILE));
    if (!$error && $incoming && count($images) - count($removed) + $incoming > 10) {
        $error = 'Un logement peut contenir jusqu’à 10 photos.';
    }
    if (!$error) {
        foreach ($files['tmp_name'] ?? [] as $key => $temp) {
            if ($files['error'][$key] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $photo = secure_image_upload(['tmp_name' => $temp, 'error' => $files['error'][$key], 'size' => $files['size'][$key]], 'uploads/stay_images');
            if (!$photo) {
                $error = 'Utilisez des images JPEG, PNG ou WebP de 8 Mo maximum.';
                break;
            }
            $newPhotos[] = $photo;
        }
    }
    if (!$error) {
        $pdo->beginTransaction();
        try {
            $values = [$stay['title'], $stay['location'], $stay['description'], $stay['price_per_night'], $maxGuests, $stay['map_url'], $stay['available']];
            if ($edit) {
                $stmt = $pdo->prepare('UPDATE stays SET title=?, location=?, description=?, price_per_night=?, max_guests=?, map_url=?, available=?, updated_at=NOW() WHERE id=? AND user_id=?');
                $stmt->execute([...$values, $id, $hostId]);
                $pdo->prepare('DELETE FROM stay_amenity WHERE stay_id=?')->execute([$id]);
                $pdo->prepare('DELETE FROM stay_facility WHERE stay_id=?')->execute([$id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO stays (title,location,description,price_per_night,max_guests,map_url,available,user_id,created_at) VALUES (?,?,?,?,?,?,?,?,NOW())');
                $stmt->execute([...$values, $hostId]);
                $id = (int) $pdo->lastInsertId();
            }
            $stmt = $pdo->prepare('INSERT INTO stay_amenity (stay_id, amenity_id) VALUES (?,?)');
            foreach ($selected as $amenityId) {
                $stmt->execute([$id, $amenityId]);
            }
            $stmt = $pdo->prepare('INSERT INTO stay_facility (stay_id, facility_id, count) VALUES (?,?,?)');
            foreach ($counts as $facilityId => $quantity) {
                $stmt->execute([$id, $facilityId, $quantity]);
            }
            $stmt = $pdo->prepare('DELETE FROM images WHERE id = ? AND stay_id = ?');
            foreach ($removed as $image) {
                $stmt->execute([$image['id'], $id]);
            }
            $stmt = $pdo->prepare('INSERT INTO images (stay_id, url) VALUES (?,?)');
            foreach ($newPhotos as $photo) {
                $stmt->execute([$id, $photo]);
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            foreach ($newPhotos as $photo) {
                unlink(__DIR__ . '/../' . $photo);
            }
            throw $exception;
        }
        foreach ($removed as $image) {
            delete_stay_photo($image['url']);
        }
        $_SESSION['listing_notice'] = $edit ? 'Le logement a été modifié.' : 'Le logement a été ajouté.';
        redirect('my-stays.php');
    }
    foreach ($newPhotos as $photo) {
        unlink(__DIR__ . '/../' . $photo);
    }
}

$pageTitle = $edit ? 'Modifier le logement' : 'Publier un logement';
require __DIR__ . '/head.php';
?>
<main class="site-wrap page-stack">
  <a class="back-link" href="my-stays.php"><?= icon('back') ?> Mes logements</a>
  <div class="section-heading"><div><p class="eyebrow">ESPACE HÔTE</p><h1><?= e($pageTitle) ?></h1></div></div>
  <?php if ($error): ?><p class="notice error" role="alert"><?= e($error) ?></p><?php endif; ?>
  <form method="post" enctype="multipart/form-data" class="listing-form">
    <?= csrf_field() ?>
    <div class="listing-grid">
      <label>Nom du logement<input name="title" value="<?= e($stay['title']) ?>" maxlength="255" required></label>
      <label>Ville et pays<input name="location" value="<?= e($stay['location']) ?>" maxlength="255" required></label>
      <label>Prix par nuit (<?= e(STAY_CURRENCY) ?>)<input type="number" name="price_per_night" min="1" max="999999.99" step="0.01" value="<?= e($stay['price_per_night']) ?>" required></label>
      <label>Nombre maximal de voyageurs<input type="number" name="max_guests" min="1" max="1000" value="<?= e($stay['max_guests']) ?>" required></label>
    </div>
    <label>Description<textarea name="description" rows="5" required><?= e($stay['description']) ?></textarea></label>
    <label>Lien vers la carte (facultatif)<input type="url" name="map_url" value="<?= e($stay['map_url']) ?>" maxlength="255" placeholder="https://…"></label>
    <fieldset><legend>Équipements</legend><div class="listing-grid">
      <?php foreach ($amenities as $amenity): ?><label class="check-label"><input type="checkbox" name="amenities[]" value="<?= (int) $amenity['id'] ?>" <?= in_array($amenity['id'], $selected) ? 'checked' : '' ?>><?= e($amenity['name']) ?></label><?php endforeach; ?>
    </div></fieldset>
    <fieldset><legend>Pièces et couchages</legend><div class="listing-grid">
      <?php foreach ($facilities as $facility): ?><label><?= e($facility['name']) ?><input type="number" min="0" max="100" name="facility_count[<?= (int) $facility['id'] ?>]" value="<?= (int) ($counts[$facility['id']] ?? 0) ?>"></label><?php endforeach; ?>
    </div></fieldset>
    <fieldset><legend>Photos</legend>
      <?php if ($images): ?><div class="photo-list">
        <?php foreach ($images as $image): ?><label><img src="<?= e(image_path($image['url'])) ?>" alt="Photo du logement"><span class="check-label"><input type="checkbox" name="remove_images[]" value="<?= (int) $image['id'] ?>">Retirer</span></label><?php endforeach; ?>
      </div><?php endif; ?>
      <label>Ajouter des photos JPEG, PNG ou WebP<input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple></label>
    </fieldset>
    <label class="check-label"><input type="checkbox" name="available" value="1" <?= $stay['available'] ? 'checked' : '' ?>> Afficher le logement dans le catalogue</label>
    <div><button class="button primary" type="submit"><?= $edit ? 'Enregistrer' : 'Ajouter le logement' ?></button></div>
  </form>
</main>
<?php require __DIR__ . '/footer.php'; ?>
</body>
</html>
