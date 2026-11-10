<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/config.php';

$email = $argv[1] ?? '';
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
    fwrite(STDERR, "Usage : php create-admin.php adresse@example.com\n");
    exit(1);
}
$password = bin2hex(random_bytes(12));
$stmt = $pdo->prepare("INSERT INTO users (name, email, telephone, password, role, created_at) VALUES ('Administration', ?, '0000000000', ?, 'admin', NOW())");
try {
    $stmt->execute([$email, password_hash($password, PASSWORD_DEFAULT)]);
} catch (PDOException $exception) {
    if ($exception->getCode() !== '23000') {
        throw $exception;
    }
    fwrite(STDERR, "Un compte existe déjà avec cette adresse.\n");
    exit(1);
}
echo "Compte créé. Mot de passe généré : $password\n";
