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
        referral_code,
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

$referralCode = trim($user['referral_code'] ?? '');

$referralLink = '';

if ($referralCode !== '') {
    $referralLink = 'https://poketflow.com/r/' . $referralCode;
}

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <title>Refer & Earn — PoketFlow</title>

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
        <a href="history.php">History</a>
        <a class="active" href="referrals.php">Refer & Earn</a>
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

            <a href="history.php">
                ◷ <span>History</span>
            </a>

            <a class="active" href="referrals.php">
                ♧ <span>Refer & Earn</span>
            </a>

            <a href="withdraw.html">
                ▣ <span>Withdraw</span>
            </a>

        </div>

    </aside>

    <section class="app-content">

        <span class="kicker">GROW WITH POKETFLOW</span>

        <h1>Refer & Earn</h1>

        <p class="lead">
            Invite friends using your referral link when the program is enabled for your account.
        </p>

        <div class="referral-box">

            <h2>Your referral link</h2>

            <div class="copy-row">

                <input
                    type="text"
                    value="<?= e($referralLink) ?>"
                    readonly
                >

                <button
                    type="button"
                    class="btn btn-primary"
                    onclick="copyReferralLink()"
                >
                    Copy
                </button>

            </div>

            <small>
                Share your referral link with friends when the referral program is enabled.
            </small>

        </div>

    </section>

</main>

<script>
function copyReferralLink() {
    const input = document.querySelector('.copy-row input');

    if (!input || !input.value) {
        return;
    }

    navigator.clipboard.writeText(input.value).then(function () {
        const button = document.querySelector('.copy-row button');

        if (button) {
            const originalText = button.textContent;

            button.textContent = 'Copied!';

            setTimeout(function () {
                button.textContent = originalText;
            }, 1500);
        }
    });
}
</script>

</body>
</html>
