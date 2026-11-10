<?php
session_start();
require_once __DIR__ . '/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}
verify_csrf();
session_unset();
session_destroy();
redirect('index.php');
