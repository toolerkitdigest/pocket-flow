<?php

declare(strict_types=1);

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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Create Account - PoketFlow</title>

    <link
        rel="stylesheet"
        href="assets/register.css"
    >

</head>

<body>

<header class="site-header">

    <!-- Brand -->

    <a href="index.html" class="brand">

        <span class="brand-mark">P</span>

        <span>
            Poket<span>Flow</span>
        </span>

    </a>


    <!-- Desktop Navigation -->

    <nav class="desktop-nav">

        <a href="index.html">
            Home
        </a>

        <a href="offers.html">
            Earn
        </a>

    </nav>


    <!-- Desktop Actions -->

    <div class="header-actions">

        <a
            href="login.php"
            class="btn btn-ghost"
        >
            Log In
        </a>

    </div>


    <!-- Mobile Hamburger -->

    <button
        type="button"
        class="mobile-menu-toggle"
        id="mobileMenuToggle"
        aria-label="Open navigation menu"
        aria-expanded="false"
    >

        <span></span>
        <span></span>
        <span></span>

    </button>


    <!-- Mobile Navigation -->

    <nav
        class="mobile-nav"
        id="mobileNav"
        aria-hidden="true"
    >

        <a href="index.html">
            Home
        </a>

        <a href="offers.html">
            Earn
        </a>

        <a
            href="login.php"
            class="mobile-nav-login"
        >
            Log In
        </a>

    </nav>

</header>


<main class="auth-page">

    <div class="auth-container">

        <div class="auth-card">

            <div class="auth-heading">

                <h1>
                    Create Your Account
                </h1>

                <p>
                    Join PoketFlow and start discovering available rewards.
                </p>

            </div>


            <?php if ($errors): ?>

                <div class="auth-error">

                    <?php foreach ($errors as $error): ?>

                        <p><?= e($error) ?></p>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>


            <form
                method="POST"
                action=""
                class="auth-form"
            >

                <div class="auth-field">

                    <label for="name">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        placeholder="Enter your full name"
                        value="<?= e($_POST['name'] ?? '') ?>"
                        autocomplete="name"
                        required
                    >

                </div>


                <div class="auth-field">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="you@example.com"
                        value="<?= e($_POST['email'] ?? '') ?>"
                        autocomplete="email"
                        required
                    >

                </div>


                <div class="auth-field">

                    <label for="country">
                        Country
                    </label>

                    <input
                        type="text"
                        id="country"
                        name="country"
                        placeholder="Enter your country"
                        value="<?= e($_POST['country'] ?? '') ?>"
                        autocomplete="country-name"
                    >

                </div>


                <div class="auth-field">

                    <label for="password">
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Minimum 8 characters"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >

                </div>


                <button
                    type="submit"
                    class="btn btn-primary btn-large auth-submit"
                >
                    Create Account
                    <span>→</span>
                </button>

            </form>


            <div class="auth-footer">

                Already have an account?

                <a href="login.php">
                    Log in
                </a>

            </div>


            <div class="auth-note">

                By creating an account, you agree to use PoketFlow
                responsibly and follow our platform rules.

            </div>

        </div>

    </div>

</main>


<script>

    const mobileMenuToggle =
        document.getElementById('mobileMenuToggle');

    const mobileNav =
        document.getElementById('mobileNav');


    mobileMenuToggle.addEventListener('click', function () {

        const isOpen =
            mobileNav.classList.toggle('open');

        mobileMenuToggle.classList.toggle(
            'active',
            isOpen
        );

        mobileMenuToggle.setAttribute(
            'aria-expanded',
            isOpen ? 'true' : 'false'
        );

        mobileMenuToggle.setAttribute(
            'aria-label',
            isOpen
                ? 'Close navigation menu'
                : 'Open navigation menu'
        );

        mobileNav.setAttribute(
            'aria-hidden',
            isOpen ? 'false' : 'true'
        );

    });


    mobileNav.querySelectorAll('a').forEach(function (link) {

        link.addEventListener('click', function () {

            mobileNav.classList.remove('open');

            mobileMenuToggle.classList.remove('active');

            mobileMenuToggle.setAttribute(
                'aria-expanded',
                'false'
            );

            mobileMenuToggle.setAttribute(
                'aria-label',
                'Open navigation menu'
            );

            mobileNav.setAttribute(
                'aria-hidden',
                'true'
            );

        });

    });

</script>

</body>
</html>
