<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function generateReferralCode(PDO $pdo, string $name): string
{
    $base = strtoupper(
        preg_replace('/[^A-Za-z0-9]/', '', $name)
    );

    $base = substr($base ?: 'USER', 0, 8);

    do {
        $code = $base . strtoupper(
            substr(bin2hex(random_bytes(4)), 0, 6)
        );

        $stmt = $pdo->prepare(
            'SELECT id FROM users WHERE referral_code = ? LIMIT 1'
        );

        $stmt->execute([$code]);

    } while ($stmt->fetch());

    return $code;
}
