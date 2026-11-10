<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../config.php';

if ((int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) {
    fwrite(STDERR, "Utilisez une base neuve pour charger la démo.\n");
    exit(1);
}

$properties = [
    ['Dar Safran', 'Marrakech, Maroc', 'Un riad à deux pas de la médina. Prenez le petit-déjeuner dans la cour, puis retrouvez le calme du bassin en zellige après une journée dans les souks.', 980, 4, 'riad.webp', 'Marrakech', [1, 2, 3, 5]],
    ['La Terrasse des Alizés', 'Essaouira, Maroc', 'Un appartement lumineux au-dessus des remparts. La terrasse abritée donne sur l’Atlantique, à quelques minutes à pied du port et de la médina.', 740, 3, 'ocean.webp', 'Essaouira', [1, 3, 4]],
    ['Maison des Noyers', 'Imlil, Haut Atlas, Maroc', 'Une maison en terre aux portes des sentiers de l’Atlas. La terrasse donne sur la vallée, avec de la place pour prendre le thé au retour de randonnée.', 650, 5, 'atlas.webp', 'Imlil', [3, 4, 6]],
    ['Dar Zellige', 'Fès, Maroc', 'Un patio bleu et blanc, des boiseries sculptées et un citronnier au cœur de la maison. Un point de départ paisible pour découvrir les ruelles de Fès.', 890, 4, 'fes.webp', 'Fès', [1, 3, 5]],
];

$password = bin2hex(random_bytes(8));
$pdo->beginTransaction();
try {
    $user = $pdo->prepare("INSERT INTO users (name, email, telephone, password, role, created_at) VALUES (?, ?, '0000000000', ?, ?, NOW())");
    $user->execute(['Équipe StayWhere', 'hote@example.test', password_hash($password, PASSWORD_DEFAULT), 'host']);
    $host = $pdo->lastInsertId();
    $user->execute(['Voyageur Démo', 'voyageur@example.test', password_hash($password, PASSWORD_DEFAULT), 'guest']);
    $property = $pdo->prepare("INSERT INTO stays (user_id, title, location, description, price_per_night, max_guests, map_url, available, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())");
    $image = $pdo->prepare("INSERT INTO images (stay_id, url, is_thumbnail) VALUES (?, ?, 1)");
    $amenity = $pdo->prepare("INSERT INTO stay_amenity (stay_id, amenity_id) VALUES (?, ?)");
    $facility = $pdo->prepare("INSERT INTO stay_facility (stay_id, facility_id, count) VALUES (?, ?, ?)");
    foreach (array_reverse($properties) as [$title, $location, $description, $price, $maxGuests, $photo, $city, $amenities]) {
        $property->execute([$host, $title, $location, $description, $price, $maxGuests, 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($city . ', Maroc')]);
        $stay = $pdo->lastInsertId();
        $image->execute([$stay, 'assets/' . $photo]);
        $facility->execute([$stay, 1, $maxGuests >= 4 ? 2 : 1]);
        $facility->execute([$stay, 2, 1]);
        foreach ($amenities as $id) {
            $amenity->execute([$stay, $id]);
        }
    }
    $pdo->commit();
} catch (Throwable $error) {
    $pdo->rollBack();
    throw $error;
}

echo "Démo chargée. Comptes : hote@example.test et voyageur@example.test\n";
echo "Mot de passe généré pour cette installation : $password\n";
