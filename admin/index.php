<?php



declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/includes/admin-auth.php';
declare(strict_types=1);

require_once __DIR__ . '/includes/admin-auth.php';

/*
|--------------------------------------------------------------------------
| Dashboard Statistics
|--------------------------------------------------------------------------
*/

/* Total users */
$stmt = $pdo->query(
    'SELECT COUNT(*)
     FROM users'
);

$totalUsers = (int) $stmt->fetchColumn();


/* Active users */
$stmt = $pdo->query(
    'SELECT COUNT(*)
     FROM users
     WHERE status = "ACTIVE"'
);

$activeUsers = (int) $stmt->fetchColumn();


/* Pending withdrawals */
$stmt = $pdo->query(
    'SELECT COUNT(*)
     FROM withdrawals
     WHERE status = "PENDING"'
);

$pendingWithdrawals = (int) $stmt->fetchColumn();


/* Pending withdrawal amount */
$stmt = $pdo->query(
    'SELECT COALESCE(SUM(amount), 0)
     FROM withdrawals
     WHERE status IN ("PENDING", "PROCESSING")'
);

$pendingWithdrawalAmount = (float) $stmt->fetchColumn();


/* Total conversions */
$stmt = $pdo->query(
    'SELECT COUNT(*)
     FROM conversions'
);

$totalConversions = (int) $stmt->fetchColumn();


/* Pending conversions */
$stmt = $pdo->query(
    'SELECT COUNT(*)
     FROM conversions
     WHERE status = "PENDING"'
);

$pendingConversions = (int) $stmt->fetchColumn();


/* Total worker rewards */
$stmt = $pdo->query(
    'SELECT COALESCE(SUM(worker_reward), 0)
     FROM conversions
     WHERE status = "APPROVED"'
);

$totalWorkerRewards = (float) $stmt->fetchColumn();


/* Platform revenue */
$stmt = $pdo->query(
    'SELECT COALESCE(SUM(platform_margin), 0)
     FROM conversions
     WHERE status = "APPROVED"'
);

$platformRevenue = (float) $stmt->fetchColumn();


/* Active campaigns */
$stmt = $pdo->query(
    'SELECT COUNT(*)
     FROM campaigns
     WHERE status = "ACTIVE"
       AND approval_status = "APPROVED"'
);

$activeCampaigns = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| Recent Conversions
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    'SELECT
        c.id,
        c.worker_reward,
        c.network_payout,
        c.platform_margin,
        c.status,
        c.converted_at,
        u.name AS user_name,
        u.email,
        cp.title AS offer_title

     FROM conversions c

     INNER JOIN users u
        ON u.id = c.worker_id

     INNER JOIN campaigns cp
        ON cp.id = c.campaign_id

     ORDER BY c.created_at DESC
     LIMIT 8'
);

$recentConversions = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Recent Withdrawals
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    'SELECT
        w.id,
        w.amount,
        w.method,
        w.method_network,
        w.status,
        w.created_at,
        u.name AS user_name,
        u.email

     FROM withdrawals w

     INNER JOIN users u
        ON u.id = w.user_id

     ORDER BY w.created_at DESC
     LIMIT 8'
);

$recentWithdrawals = $stmt->fetchAll();

?>
<!doctype html>
<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Admin Dashboard — PoketFlow</title>

    <link
        rel="stylesheet"
        href="assets/admin.css"
    >

</head>

<body class="admin-page">

<header class="admin-header">

    <a href="index.php" class="admin-brand">
        <span class="brand-mark">P</span>
        <span>Poket<span>Flow</span></span>
    </a>

    <div class="admin-header-right">

        <span class="admin-user">
            <?= e($adminUser['name']) ?>
        </span>

        <a href="../dashboard.php">
            View Site
        </a>

        <a href="../logout.php">
            Logout
        </a>

    </div>

</header>


<div class="admin-layout">

    <aside class="admin-sidebar">

        <nav>

            <a class="active" href="index.php">
                📊
                <span>Dashboard</span>
            </a>

            <a href="users.php">
                👥
                <span>Users</span>
            </a>

            <a href="withdrawals.php">
                💳
                <span>Withdrawals</span>

                <?php if ($pendingWithdrawals > 0): ?>
                    <b><?= $pendingWithdrawals ?></b>
                <?php endif; ?>

            </a>

            <a href="conversions.php">
                ✓
                <span>Conversions</span>
            </a>

            <a href="campaigns.php">
                🎯
                <span>Campaigns</span>
            </a>

            <a href="wallet.php">
                💰
                <span>Wallet</span>
            </a>

            <a href="settings.php">
                ⚙
                <span>Settings</span>
            </a>

        </nav>

    </aside>


    <main class="admin-content">

        <div class="page-heading">

            <div>

                <span class="kicker">
                    CONTROL CENTER
                </span>

                <h1>
                    Admin Dashboard
                </h1>

                <p>
                    Monitor users, offers, conversions, earnings and withdrawals.
                </p>

            </div>

        </div>


        <!-- STAT CARDS -->

        <section class="stats-grid">

            <div class="stat-card">

                <span class="stat-label">
                    Total Users
                </span>

                <strong>
                    <?= number_format($totalUsers) ?>
                </strong>

                <small>
                    <?= number_format($activeUsers) ?> active
                </small>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Total Conversions
                </span>

                <strong>
                    <?= number_format($totalConversions) ?>
                </strong>

                <small>
                    <?= number_format($pendingConversions) ?> pending
                </small>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Worker Rewards
                </span>

                <strong>
                    $<?= number_format($totalWorkerRewards, 2) ?>
                </strong>

                <small>
                    Approved rewards
                </small>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Platform Revenue
                </span>

                <strong>
                    $<?= number_format($platformRevenue, 2) ?>
                </strong>

                <small>
                    From approved conversions
                </small>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Pending Withdrawals
                </span>

                <strong>
                    <?= number_format($pendingWithdrawals) ?>
                </strong>

                <small>
                    $<?= number_format($pendingWithdrawalAmount, 2) ?> reserved
                </small>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Active Campaigns
                </span>

                <strong>
                    <?= number_format($activeCampaigns) ?>
                </strong>

                <small>
                    Approved & active
                </small>

            </div>

        </section>


        <!-- RECENT CONVERSIONS -->

        <section class="admin-panel">

            <div class="panel-heading">

                <div>
                    <h2>Recent Conversions</h2>
                    <p>Latest offer completions on PoketFlow.</p>
                </div>

                <a href="conversions.php">
                    View all →
                </a>

            </div>


            <?php if (empty($recentConversions)): ?>

                <div class="admin-empty">
                    No conversions yet.
                </div>

            <?php else: ?>

                <div class="table-wrap">

                    <table>

                        <thead>

                            <tr>
                                <th>Offer</th>
                                <th>User</th>
                                <th>Payout</th>
                                <th>Reward</th>
                                <th>Margin</th>
                                <th>Status</th>
                            </tr>

                        </thead>

                        <tbody>

                        <?php foreach ($recentConversions as $conversion): ?>

                            <tr>

                                <td>
                                    <?= e($conversion['offer_title']) ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= e($conversion['user_name']) ?>
                                    </strong>

                                    <small>
                                        <?= e($conversion['email']) ?>
                                    </small>
                                </td>

                                <td>
                                    $<?= number_format(
                                        (float) $conversion['network_payout'],
                                        2
                                    ) ?>
                                </td>

                                <td>
                                    +$<?= number_format(
                                        (float) $conversion['worker_reward'],
                                        2
                                    ) ?>
                                </td>

                                <td>
                                    $<?= number_format(
                                        (float) $conversion['platform_margin'],
                                        2
                                    ) ?>
                                </td>

                                <td>
                                    <span class="status-badge">
                                        <?= e($conversion['status']) ?>
                                    </span>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </section>


        <!-- RECENT WITHDRAWALS -->

        <section class="admin-panel">

            <div class="panel-heading">

                <div>
                    <h2>Recent Withdrawals</h2>
                    <p>Monitor worker withdrawal requests.</p>
                </div>

                <a href="withdrawals.php">
                    Manage withdrawals →
                </a>

            </div>


            <?php if (empty($recentWithdrawals)): ?>

                <div class="admin-empty">
                    No withdrawals yet.
                </div>

            <?php else: ?>

                <div class="table-wrap">

                    <table>

                        <thead>

                            <tr>
                                <th>User</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>

                        </thead>

                        <tbody>

                        <?php foreach ($recentWithdrawals as $withdrawal): ?>

                            <tr>

                                <td>
                                    <strong>
                                        <?= e($withdrawal['user_name']) ?>
                                    </strong>

                                    <small>
                                        <?= e($withdrawal['email']) ?>
                                    </small>
                                </td>

                                <td>
                                    $<?= number_format(
                                        (float) $withdrawal['amount'],
                                        2
                                    ) ?>
                                </td>

                                <td>

                                    <?= e($withdrawal['method']) ?>

                                    <?php if (!empty($withdrawal['method_network'])): ?>

                                        <small>
                                            <?= e($withdrawal['method_network']) ?>
                                        </small>

                                    <?php endif; ?>

                                </td>

                                <td>
                                    <span class="status-badge">
                                        <?= e($withdrawal['status']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= e(
                                        date(
                                            'M j, Y',
                                            strtotime(
                                                (string) $withdrawal['created_at']
                                            )
                                        )
                                    ) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </section>

    </main>

</div>

</body>
</html>
