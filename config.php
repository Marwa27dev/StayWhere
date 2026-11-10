<?php
declare(strict_types=1);

require_once __DIR__ . '/app.php';

date_default_timezone_set('Africa/Casablanca');

$local = is_file(__DIR__ . '/config.local.php') ? require __DIR__ . '/config.local.php' : [];
$host = getenv('DB_HOST') ?: ($local['host'] ?? '127.0.0.1');
$port = getenv('DB_PORT') ?: ($local['port'] ?? 3306);
$db = getenv('DB_NAME') ?: ($local['database'] ?? 'staywhere');
$user = getenv('DB_USER') ?: ($local['user'] ?? 'root');
$password = getenv('DB_PASSWORD');
if ($password === false) {
    $password = $local['password'] ?? '';
}
define('STAY_CURRENCY', getenv('STAY_CURRENCY') ?: ($local['currency'] ?? 'MAD'));

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $error) {
    http_response_code(503);
    exit('Base de données indisponible. Vérifiez la configuration et suivez le guide du README.');
}

if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user']['id'])) {
    $stmt = $pdo->prepare('SELECT id, name, email, role FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user']['id']]);
    $currentUser = $stmt->fetch();
    if ($currentUser) {
        $_SESSION['user'] = $currentUser;
    } else {
        unset($_SESSION['user'], $_SESSION['csrf_token']);
    }
}
