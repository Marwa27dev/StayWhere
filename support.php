<?php
session_start();
require_once __DIR__ . '/config.php';
if (($_SESSION['user']['role'] ?? null) !== 'admin') {
    redirect('index.php');
}
redirect('admin-dashboard.php');
