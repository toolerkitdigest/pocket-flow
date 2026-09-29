<?php

declare(strict_types=1);

$configFile = '/home/u541027683/private/poketflowcpa-database.php';

if (!file_exists($configFile)) {
    die('Database configuration file not found.');
}

$config = require $configFile;

try {
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $config['host'],
        $config['dbname'],
        $config['charset']
    );

    $pdo = new PDO(
        $dsn,
        $config['username'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

} catch (PDOException $e) {
    die('Database connection failed.');
}
