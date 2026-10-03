<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$userId = (int) ($_SESSION['user_id'] ?? 0);

$user = getUser($pdo, $userId);

if (!$user) {
    redirect('logout.php');
}

/*
|--------------------------------------------------------------------------
| Wallet Summary
|--------------------------------------------------------------------------
*/

$availableBalance = getUserBalance($pdo, $userId);
$pendingBalance   = getUserPendingBalance($pdo, $userId);
$totalEarned      = getUserTotalEarned($pdo, $userId);

$currency = getSetting($pdo, 'currency', 'USD') ?? 'USD';

/*
|--------------------------------------------------------------------------
| Transaction History
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT
        id,
        type,
        amount,
        currency,
        balance_before,
        balance_after,
        status,
        description,
        created_at
     FROM wallet_transactions
     WHERE user_id = :user_id
     ORDER BY created_at DESC, id DESC
     LIMIT 100'
);

$stmt->execute([
    'user_id' => $userId,
]);

$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function walletTransactionLabel(string $type): string
{
    return match ($type) {
        'OFFER_REWARD'    => 'Offer Reward',
        'REFERRAL_REWARD' => 'Referral Reward',
        'WITHDRAWAL'      => 'Withdrawal',
        'ADJUSTMENT'      => 'Adjustment',
        'REVERSAL'        => 'Reversal',
        'BONUS'           => 'Bonus',
        default            => ucwords(strtolower(str_replace('_', ' ', $type))),
    };
}

function walletStatusClass(string $status): string
{
    return match ($status) {
        'COMPLETED' => 'is-completed',
        'PENDING'   => 'is-pending',
        'REVERSED'  => 'is-reversed',
        'FAILED'    => 'is-failed',
        default     => 'is-default',
    };
}

function walletTypeClass(string $type): string
{
    return match ($type) {
        'OFFER_REWARD',
        'REFERRAL_REWARD',
        'BONUS'      => 'is-positive',

        'WITHDRAWAL',
        'REVERSAL'   => 'is-negative',

        'ADJUSTMENT' => 'is-adjustment',

        default      => 'is-default',
    };
}

function formatWalletAmount(float $amount, string $currency): string
{
    $prefix = $currency === 'USD' ? '$' : $currency . ' ';

    return ($amount >= 0 ? '+' : '-') .
        $prefix .
        number_format(abs($amount), 2);
}

function formatWalletDate(string $date): string
{
    $timestamp = strtotime($date);

    if (!$timestamp) {
        return $date;
    }

    return date('M j, Y · g:i A', $timestamp);
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

    <title>Wallet — PoketFlow</title>

    <link
        rel="stylesheet"
        href="assets/poketflow.css"
    >

    <link
        rel="stylesheet"
        href="assets/wallet.css"
    >

</head>

<body>

<div class="app-shell">

    <!-- =========================================
         SIDEBAR
    ========================================== -->

    <aside class="sidebar">

        <div class="sidebar-brand">
            <a href="dashboard.php" class="brand">
                <span class="brand-mark">P</span>

                <span>
                    Poket<span>Flow</span>
                </span>
            </a>
        </div>

        <nav class="sidebar-nav">

            <a href="dashboard.php">
                <span class="sidebar-nav-icon">⌂</span>
                <span>Home</span>
            </a>

            <a href="offers.php">
                <span class="sidebar-nav-icon">▦</span>
                <span>Earn Rewards</span>
            </a>

            <a href="history.php">
                <span class="sidebar-nav-icon">◷</span>
                <span>History</span>
            </a>

            <a href="referrals.php">
                <span class="sidebar-nav-icon">♧</span>
                <span>Refer & Earn</span>
            </a>

            <a href="withdraw.php">
                <span class="sidebar-nav-icon">▣</span>
                <span>Withdraw</span>
            </a>

            <a href="wallet.php" class="active">
                <span class="sidebar-nav-icon">◉</span>
                <span>Wallet</span>
            </a>

            <a href="logout.php">
                <span class="sidebar-nav-icon">↪</span>
                <span>Log Out</span>
            </a>

        </nav>

        <div class="side-balance">

            <span class="side-balance-label">
                Available Balance
            </span>

            <strong>
                <?= e($currency) ?>
                <?= number_format($availableBalance, 2) ?>
            </strong>

        </div>

    </aside>


    <!-- =========================================
         MAIN AREA
    ========================================== -->

    <main class="main-content">

        <!-- HEADER -->

        <header class="app-header">

            <div class="header-left">

                <a href="dashboard.php" class="brand">

                    <span class="brand-mark">P</span>

                    <span>
                        Poket<span>Flow</span>
                    </span>

                </a>

            </div>

            <nav>

                <a href="dashboard.php">Home</a>

                <a href="offers.php">Earn Rewards</a>

                <a href="history.php">History</a>

                <a href="referrals.php">Refer & Earn</a>

                <a href="withdraw.php">Withdraw</a>

            </nav>

            <div class="header-actions">

                <a
                    href="wallet.php"
                    class="header-balance"
                >
                    <?= e($currency) ?>
                    <?= number_format($availableBalance, 2) ?>
                </a>

                <button
                    type="button"
                    class="mobile-menu-button"
                    id="mobileMenuButton"
                    aria-label="Open navigation menu"
                    aria-expanded="false"
                    aria-controls="mobileNavigation"
                >
                    <span></span>
                    <span></span>
                    <span></span>
                </button>

            </div>

        </header>


        <!-- MOBILE NAVIGATION -->

        <div
            class="mobile-navigation"
            id="mobileNavigation"
            aria-hidden="true"
        >

            <nav>

                <a href="dashboard.php">
                    <span class="mobile-nav-icon">⌂</span>
                    <span>Home</span>
                </a>

                <a href="offers.php">
                    <span class="mobile-nav-icon">▦</span>
                    <span>Earn Rewards</span>
                </a>

                <a href="history.php">
                    <span class="mobile-nav-icon">◷</span>
                    <span>History</span>
                </a>

                <a href="referrals.php">
                    <span class="mobile-nav-icon">♧</span>
                    <span>Refer & Earn</span>
                </a>

                <a href="withdraw.php">
                    <span class="mobile-nav-icon">▣</span>
                    <span>Withdraw</span>
                </a>

                <a href="wallet.php" class="active">
                    <span class="mobile-nav-icon">◉</span>
                    <span>Wallet</span>
                </a>

                <a href="logout.php">
                    <span class="mobile-nav-icon">↪</span>
                    <span>Log Out</span>
                </a>

            </nav>

        </div>


        <!-- =========================================
             WALLET CONTENT
        ========================================== -->

        <section class="wallet-page">

            <div class="wallet-page-header">

                <div>

                    <span class="wallet-eyebrow">
                        YOUR WALLET
                    </span>

                    <h1>Wallet</h1>

                    <p>
                        Track your earnings, rewards and wallet activity.
                    </p>

                </div>

                <div class="wallet-header-action">

                    <a
                        href="withdraw.php"
                        class="wallet-withdraw-button"
                    >
                        Withdraw Funds
                    </a>

                </div>

            </div>


            <!-- =====================================
                 SUMMARY CARDS
            ====================================== -->

            <div class="wallet-summary-grid">

                <div class="wallet-summary-card wallet-balance-card">

                    <div class="wallet-card-top">

                        <span class="wallet-card-label">
                            Available Balance
                        </span>

                        <span class="wallet-card-icon">
                            ◉
                        </span>

                    </div>

                    <div class="wallet-card-value">
                        <?= e($currency) ?>
                        <?= number_format($availableBalance, 2) ?>
                    </div>

                    <div class="wallet-card-description">
                        Available for withdrawal
                    </div>

                </div>


                <div class="wallet-summary-card">

                    <div class="wallet-card-top">

                        <span class="wallet-card-label">
                            Pending Balance
                        </span>

                        <span class="wallet-card-icon">
                            ◷
                        </span>

                    </div>

                    <div class="wallet-card-value">
                        <?= e($currency) ?>
                        <?= number_format($pendingBalance, 2) ?>
                    </div>

                    <div class="wallet-card-description">
                        Rewards still being processed
                    </div>

                </div>


                <div class="wallet-summary-card">

                    <div class="wallet-card-top">

                        <span class="wallet-card-label">
                            Total Earned
                        </span>

                        <span class="wallet-card-icon">
                            ↗
                        </span>

                    </div>

                    <div class="wallet-card-value">
                        <?= e($currency) ?>
                        <?= number_format($totalEarned, 2) ?>
                    </div>

                    <div class="wallet-card-description">
                        Your lifetime completed earnings
                    </div>

                </div>

            </div>


            <!-- =====================================
                 TRANSACTIONS
            ====================================== -->

            <section class="wallet-transactions-section">

                <div class="wallet-section-header">

                    <div>

                        <h2>Wallet Transactions</h2>

                        <p>
                            Your latest wallet activity.
                        </p>

                    </div>

                    <span class="wallet-transaction-count">
                        <?= count($transactions) ?> transactions
                    </span>

                </div>


                <?php if (empty($transactions)): ?>

                    <div class="wallet-empty-state">

                        <div class="wallet-empty-icon">
                            ◉
                        </div>

                        <h3>No wallet transactions yet</h3>

                        <p>
                            Your rewards, withdrawals and other wallet
                            activity will appear here.
                        </p>

                        <a href="offers.php">
                            Start Earning
                        </a>

                    </div>

                <?php else: ?>

                    <div class="wallet-table-wrapper">

                        <table class="wallet-table">

                            <thead>

                                <tr>

                                    <th>Description</th>

                                    <th>Type</th>

                                    <th>Amount</th>

                                    <th>Status</th>

                                    <th>Date</th>

                                </tr>

                            </thead>

                            <tbody>

                            <?php foreach ($transactions as $transaction): ?>

                                <?php
                                $amount = (float) $transaction['amount'];
                                $type = (string) $transaction['type'];
                                $status = (string) $transaction['status'];
                                ?>

                                <tr>

                                    <td>

                                        <div class="wallet-transaction-description">

                                            <span
                                                class="wallet-transaction-icon <?= e(walletTypeClass($type)) ?>"
                                            >
                                                <?= $amount >= 0 ? '↗' : '↘' ?>
                                            </span>

                                            <div>

                                                <strong>
                                                    <?= e(
                                                        $transaction['description']
                                                        ?: walletTransactionLabel($type)
                                                    ) ?>
                                                </strong>

                                                <small>
                                                    Transaction #<?= (int) $transaction['id'] ?>
                                                </small>

                                            </div>

                                        </div>

                                    </td>

                                    <td>

                                        <span class="wallet-type">
                                            <?= e(walletTransactionLabel($type)) ?>
                                        </span>

                                    </td>

                                    <td>

                                        <span
                                            class="wallet-amount <?= $amount >= 0 ? 'positive' : 'negative' ?>"
                                        >
                                            <?= e(
                                                formatWalletAmount(
                                                    $amount,
                                                    (string) ($transaction['currency'] ?: $currency)
                                                )
                                            ) ?>
                                        </span>

                                    </td>

                                    <td>

                                        <span
                                            class="wallet-status <?= e(walletStatusClass($status)) ?>"
                                        >
                                            <?= e($status) ?>
                                        </span>

                                    </td>

                                    <td>

                                        <time>
                                            <?= e(
                                                formatWalletDate(
                                                    (string) $transaction['created_at']
                                                )
                                            ) ?>
                                        </time>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

            </section>

        </section>

    </main>

</div>


<script>

document.addEventListener('DOMContentLoaded', function () {

    const button = document.getElementById('mobileMenuButton');
    const navigation = document.getElementById('mobileNavigation');

    if (!button || !navigation) {
        return;
    }

    button.addEventListener('click', function () {

        const isOpen = navigation.classList.toggle('open');

        button.classList.toggle('open', isOpen);

        button.setAttribute(
            'aria-expanded',
            isOpen ? 'true' : 'false'
        );

        navigation.setAttribute(
            'aria-hidden',
            isOpen ? 'false' : 'true'
        );

    });


    navigation.querySelectorAll('a').forEach(function (link) {

        link.addEventListener('click', function () {

            navigation.classList.remove('open');
            button.classList.remove('open');

            button.setAttribute(
                'aria-expanded',
                'false'
            );

            navigation.setAttribute(
                'aria-hidden',
                'true'
            );

        });

    });


    document.addEventListener('click', function (event) {

        if (
            !navigation.contains(event.target) &&
            !button.contains(event.target)
        ) {

            navigation.classList.remove('open');
            button.classList.remove('open');

            button.setAttribute(
                'aria-expanded',
                'false'
            );

            navigation.setAttribute(
                'aria-hidden',
                'true'
            );

        }

    });

});

</script>

</body>
</html>
