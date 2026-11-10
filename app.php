<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function input(array $source, string $key): string
{
    return is_string($source[$key] ?? null) ? trim($source[$key]) : '';
}

function money(mixed $amount): string
{
    $value = (float) $amount;
    return number_format($value, floor($value) === $value ? 0 : 2, ',', ' ') . ' ' . STAY_CURRENCY;
}

function redirect(string $path): never
{
    header('Location: ' . $path, true, 303);
    exit;
}

function csrf_token(): string
{
    return $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf(): void
{
    if (!hash_equals(csrf_token(), input($_POST, 'csrf_token'))) {
        http_response_code(403);
        exit('La session du formulaire a expiré. Rechargez la page et réessayez.');
    }
}

function date_value(string $value): ?DateTimeImmutable
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)) {
        return null;
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $date : null;
}

function booking_nights(string $arrival, string $departure): ?int
{
    $start = date_value($arrival);
    $end = date_value($departure);
    if (!$start || !$end || $start < new DateTimeImmutable('today') || $end <= $start) {
        return null;
    }
    $nights = (int) $start->diff($end)->days;
    return $nights <= 90 ? $nights : null;
}

function guest_count(string $value): ?int
{
    $guests = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $guests === false ? null : $guests;
}

function has_confirmed_overlap(PDO $pdo, int $stay, string $arrival, string $departure): bool
{
    $stmt = $pdo->prepare("SELECT 1 FROM reservations
        WHERE stay_id = ? AND status = 'confirmed' AND check_in < ? AND check_out > ? LIMIT 1 FOR UPDATE");
    $stmt->execute([$stay, $departure, $arrival]);
    return (bool) $stmt->fetchColumn();
}

function image_path(?string $path): string
{
    if ($path && preg_match('~^(?:uploads/(?:stay_images|profile_pics)|assets)/[\w .-]+\.(?:jpe?g|png|webp|gif)$~iD', $path)
        && is_file(__DIR__ . '/' . $path)) {
        return $path;
    }
    return 'assets/hero.webp';
}

function secure_image_upload(array $upload, string $directory): ?string
{
    $temp = $upload['tmp_name'] ?? '';
    if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($temp)
        || ($upload['size'] ?? 0) > 8 * 1024 * 1024) {
        return null;
    }
    $image = @getimagesize($temp);
    $extension = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'][$image[2] ?? 0] ?? null;
    if (!$extension) {
        return null;
    }
    $relative = $directory . '/' . bin2hex(random_bytes(16)) . '.' . $extension;
    return move_uploaded_file($temp, __DIR__ . '/' . $relative) ? $relative : null;
}

function https_url(?string $value): ?string
{
    return $value && filter_var($value, FILTER_VALIDATE_URL) && parse_url($value, PHP_URL_SCHEME) === 'https'
        ? $value : null;
}

function icon(string $name): string
{
    $paths = [
        'logo' => '<path d="M4 19V10l8-7 8 7v9a2 2 0 0 1-2 2h-3v-5a3 3 0 0 0-6 0v5H6a2 2 0 0 1-2-2Z"/>',
        'user' => '<circle cx="12" cy="8" r="3.3"/><path d="M5 20v-2a5 5 0 0 1 5-5h4a5 5 0 0 1 5 5v2"/>',
        'users' => '<circle cx="9" cy="8" r="3"/><path d="M3 20v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2M17 5a3 3 0 0 1 0 6m4 9v-2a4 4 0 0 0-3-4"/>',
        'pin' => '<path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4m10-4v4M3 10h18"/>',
        'search' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4 4"/>',
        'arrow' => '<path d="M4 12h15m-6-6 6 6-6 6"/>',
        'back' => '<path d="M20 12H5m6-6-6 6 6 6"/>',
        'compass' => '<circle cx="12" cy="12" r="9"/><path d="m15.5 8.5-2 5-5 2 2-5 5-2Z"/>',
        'arch' => '<path d="M4 21h16V10c0-4-5-6-8-8-3 2-8 4-8 8v11Zm5 0v-8a3 3 0 0 1 6 0v8"/>',
        'waves' => '<path d="M3 7c3 0 3-2 6-2s3 2 6 2 3-2 6-2M3 13c3 0 3-2 6-2s3 2 6 2 3-2 6-2M3 19c3 0 3-2 6-2s3 2 6 2 3-2 6-2"/>',
        'mountain' => '<path d="m2 20 7-13 3 5 3-8 7 16H2Zm11-10 2 2 2-2"/>',
        'heart' => '<path d="M20 5a5 5 0 0 0-7 0l-1 1-1-1a5 5 0 0 0-7 7l8 8 8-8a5 5 0 0 0 0-7Z"/>',
        'photo' => '<rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m4 18 5-5 3 3 4-5 5 6"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
    ];
    return '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">' . ($paths[$name] ?? '') . '</svg>';
}

function catalog(PDO $pdo, int $userId, string $location = '', string $arrival = '', string $departure = '',
    ?int $guests = null, ?int $budget = null, string $sort = '', ?int $limit = null): array
{
    $sql = "SELECT s.id, s.title, s.location, s.max_guests, s.price_per_night,
        (SELECT url FROM images WHERE stay_id = s.id ORDER BY is_thumbnail DESC, id LIMIT 1) AS image_url,
        EXISTS(SELECT 1 FROM wishlists w JOIN wishlist_stay ws ON ws.wishlist_id = w.id
               WHERE w.user_id = ? AND ws.stay_id = s.id) AS saved
        FROM stays s WHERE s.available = 1";
    $params = [$userId];
    if ($location !== '') {
        $sql .= " AND (s.location LIKE ? ESCAPE '!' OR s.title LIKE ? ESCAPE '!')";
        $pattern = '%' . strtr($location, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
        array_push($params, $pattern, $pattern);
    }
    if ($guests !== null) {
        $sql .= ' AND s.max_guests >= ?';
        $params[] = $guests;
    }
    if ($budget !== null) {
        $sql .= ' AND s.price_per_night <= ?';
        $params[] = $budget;
    }
    if ($arrival !== '' && $departure !== '') {
        $sql .= " AND NOT EXISTS (SELECT 1 FROM reservations r WHERE r.stay_id = s.id
                  AND r.status = 'confirmed' AND r.check_in < ? AND r.check_out > ?)";
        array_push($params, $departure, $arrival);
    }
    $sql .= match ($sort) {
        'asc' => ' ORDER BY s.price_per_night ASC, s.id DESC',
        'desc' => ' ORDER BY s.price_per_night DESC, s.id DESC',
        default => ' ORDER BY s.id DESC',
    };
    if ($limit !== null) {
        $sql .= ' LIMIT ' . $limit;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function booking_return(string $path): ?string
{
    return preg_match('~^staydetails\.php\?id=[1-9]\d*(?:&(?:check_in|check_out|guests)=[\d-]+)*$~D', $path) ? $path : null;
}

function reservation_status(string $status): string
{
    return ['pending' => 'En attente', 'confirmed' => 'Confirmée', 'declined' => 'Refusée', 'cancelled' => 'Annulée'][$status] ?? $status;
}

function delete_stay_photo(?string $path): void
{
    if ($path && str_starts_with($path, 'uploads/stay_images/') && image_path($path) === $path) {
        unlink(__DIR__ . '/' . $path);
    }
}

function delete_listing(PDO $pdo, int $id, ?int $owner = null): bool
{
    $pdo->beginTransaction();
    try {
        $query = 'SELECT id FROM stays WHERE id = ?';
        $params = [$id];
        if ($owner !== null) {
            $query .= ' AND user_id = ?';
            $params[] = $owner;
        }
        $stmt = $pdo->prepare($query . ' FOR UPDATE');
        $stmt->execute($params);
        $found = $stmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT 1 FROM reservations WHERE stay_id = ? AND status = 'confirmed' LIMIT 1 FOR UPDATE");
        $stmt->execute([$id]);
        if (!$found || $stmt->fetchColumn()) {
            $pdo->rollBack();
            return false;
        }
        $stmt = $pdo->prepare('SELECT url FROM images WHERE stay_id = ?');
        $stmt->execute([$id]);
        $photos = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $pdo->prepare('DELETE FROM stays WHERE id = ?')->execute([$id]);
        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
    foreach ($photos as $photo) {
        delete_stay_photo($photo);
    }
    return true;
}
