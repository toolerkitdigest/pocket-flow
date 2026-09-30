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

/*
|--------------------------------------------------------------------------
| Wallet
|--------------------------------------------------------------------------
*/

$availableBalance = getUserBalance(
    $pdo,
    $userId
);

$minimumWithdrawal = (float) getSetting(
    $pdo,
    'minimum_withdrawal',
    '5.00'
);

$canWithdraw = $availableBalance >= $minimumWithdrawal;
?>
<!doctype html>
<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width,initial-scale=1"
    >

    <title>Withdraw — PoketFlow</title>

    <link
        rel="stylesheet"
        href="assets/poketflow.css"
    >

</head>

<body class="app-page">

<header class="app-header">

    <a class="brand" href="index.html">

        <span class="brand-mark">P</span>

        <span>
            Poket<span>Flow</span>
        </span>

    </a>

    <nav>

        <a href="dashboard.php">
            Home
        </a>

        <a href="offers.php">
            Earn
        </a>

        <a href="history.php">
            History
        </a>

        <a href="referrals.php">
            Refer & Earn
        </a>

        <a class="active" href="withdraw.php">
            Withdraw
        </a>

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

            <a href="history.php">
                ◷ <span>History</span>
            </a>

            <a href="referrals.php">
                ♧ <span>Refer & Earn</span>
            </a>

            <a class="active" href="withdraw.php">
                ▣ <span>Withdraw</span>
            </a>

        </div>

    </aside>

    <section class="app-content">

        <span class="kicker">
            REWARDS
        </span>

        <h1>
            Withdraw
        </h1>

        <p class="lead">
            Request your available rewards when you meet PocketFlow's cash-out requirements.
        </p>

        <div class="withdraw-card">

            <div>

                <span>
                    Available balance
                </span>

                <strong>
                    $<?= number_format($availableBalance, 2) ?>
                </strong>

            </div>

            <button
    class="btn btn-primary"
    type="button"
    <?= $canWithdraw ? '' : 'disabled' ?>
>
    Withdraw Funds
</button>

            <small>
    Minimum withdrawal:
    $<?= number_format($minimumWithdrawal, 2) ?>.
    <?php if ($canWithdraw): ?>
        You are eligible to request a withdrawal.
    <?php else: ?>
        You need
        $<?= number_format(
            max(0, $minimumWithdrawal - $availableBalance),
            2
        ) ?>
        more to reach the minimum withdrawal amount.
    <?php endif; ?>
</small>

        </div>

    </section>

</main>

</body>

</html>
