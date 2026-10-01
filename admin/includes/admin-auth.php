<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$userId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare(
    'SELECT
        id,
        name,
        email,
        role,
        status
     FROM users
     WHERE id = ?
     LIMIT 1'
);

$stmt->execute([$userId]);

$adminUser = $stmt->fetch();

if (!$adminUser) {
    $_SESSION = [];
    session_destroy();

    redirect('../login.php');
}

/*
|--------------------------------------------------------------------------
| Admin Role Check
|--------------------------------------------------------------------------
*/

$role = strtoupper(trim((string) ($adminUser['role'] ?? '')));

if ($role !== 'ADMIN') {

    http_response_code(403);

    exit('Access denied.');
}

/*
|--------------------------------------------------------------------------
| Admin Status Check
|--------------------------------------------------------------------------
*/

$status = strtoupper(trim((string) ($adminUser['status'] ?? '')));

if ($status !== 'ACTIVE') {

    $_SESSION = [];
    session_destroy();

    redirect('../login.php');
}
