<?php

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $country = trim($_POST['country'] ?? '');

    if ($name === '') {
        $errors[] = 'Please enter your name.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if (!$errors) {

        $stmt = $pdo->prepare(
            'SELECT id FROM users WHERE email = ? LIMIT 1'
        );

        $stmt->execute([$email]);

        if ($stmt->fetch()) {

            $errors[] = 'An account with this email already exists.';

        } else {

            $referralCode = generateReferralCode($pdo, $name);

            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare(
                'INSERT INTO users
                (role, name, email, password_hash, country, status, referral_code)
                VALUES
                (?, ?, ?, ?, ?, ?, ?)'
            );

            $stmt->execute([
                'WORKER',
                $name,
                $email,
                $passwordHash,
                $country ?: null,
                'ACTIVE',
                $referralCode
            ]);

            $userId = (int) $pdo->lastInsertId();

            session_regenerate_id(true);

            $_SESSION['user_id'] = $userId;

            redirect('dashboard.php');
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Account - PoketFlow</title>

    <link rel="stylesheet" href="assets/poketflow.css">
</head>

<body>

    <main style="max-width:500px;margin:80px auto;padding:20px;">

        <div class="pf-card">

            <h1>Create your PoketFlow account</h1>

            <p>
                Join PoketFlow and start discovering available opportunities.
            </p>

            <?php if ($errors): ?>

                <div>
                    <?php foreach ($errors as $error): ?>

                        <p><?= e($error) ?></p>

                    <?php endforeach; ?>
                </div>

            <?php endif; ?>

            <form method="POST" action="">

                <div>
                    <label for="name">Full Name</label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        required
                        value="<?= e($_POST['name'] ?? '') ?>"
                    >
                </div>

                <div>
                    <label for="email">Email Address</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        required
                        value="<?= e($_POST['email'] ?? '') ?>"
                    >
                </div>

                <div>
                    <label for="country">Country</label>

                    <input
                        type="text"
                        id="country"
                        name="country"
                        value="<?= e($_POST['country'] ?? '') ?>"
                    >
                </div>

                <div>
                    <label for="password">Password</label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        minlength="8"
                        required
                    >
                </div>

                <button type="submit">
                    Create Account
                </button>

            </form>

            <p>
                Already have an account?
                <a href="login.php">Log in</a>
            </p>

        </div>

    </main>

</body>
</html>
