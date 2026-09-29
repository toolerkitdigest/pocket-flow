<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$userId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare(
    'SELECT
        id,
        name,
        email,
        country,
        status
     FROM users
     WHERE id = ?
     LIMIT 1'
);

$stmt->execute([$userId]);

$user = $stmt->fetch();

if (!$user) {
    $_SESSION = [];
    session_destroy();

    redirect('login.php');
}

$availableBalance = 0.00;

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <title>History — PoketFlow</title>

    <link rel="stylesheet" href="assets/poketflow.css">
</head>

<body class="app-page">

<header class="app-header">

    <a class="brand" href="index.html">
        <span class="brand-mark">P</span>
        <span>Poket<span>Flow</span></span>
    </a>

    <nav>
        <a href="dashboard.php">Home</a>
        <a href="offers.php">Earn</a>
        <a class="active" href="history.php">History</a>
        <a href="referrals.html">Refer & Earn</a>
        <a href="withdraw.html">Withdraw</a>
    </nav>

</header>

<main class="app-shell">

    <aside class="sidebar">

        <div class="sidebar-nav">

            <a href="dashboard.php">
                ⌂ <span>Home</span>
            </a>

            <a href="offers.php">
                ▦ <span>Offers</span>
            </a>

            <a class="active" href="history.php">
                ◷ <span>History</span>
            </a>

            <a href="referrals.html">
                ♧ <span>Refer & Earn</span>
            </a>

            <a href="withdraw.html">
                ▣ <span>Withdraw</span>
            </a>

        </div>

    </aside>

    <section class="app-content">

        <span class="kicker">ACCOUNT ACTIVITY</span>

        <h1>Offer History</h1>

        <p class="lead">
            Track your recent offer activity and completion status.
        </p>

        <div class="empty-state">

            <div>◷</div>

            <h2>No activity yet</h2>

            <p>
                Your completed offers will appear here.
            </p>

            <a class="btn btn-primary" href="offers.php">
                Browse Offers →
            </a>

        </div>

    </section>

</main>

</body>
</html>
