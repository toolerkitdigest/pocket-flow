<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/admin-auth.php';

$userId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$userId || $userId <= 0) {
    redirect('users.php');
}

/*
|--------------------------------------------------------------------------
| Get User
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT
        id,
        name,
        email,
        role,
        country,
        status,
        referral_code,
        referred_by,
        created_at,
        updated_at
     FROM users
     WHERE id = ?
     LIMIT 1'
);

$stmt->execute([$userId]);

$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    exit('User not found.');
}

/*
|--------------------------------------------------------------------------
| User Statistics
|--------------------------------------------------------------------------
*/

$balance = getUserBalance($pdo, $userId);

$totalEarned = getUserTotalEarned(
    $pdo,
    $userId
);

$pendingBalance = getUserPendingBalance(
    $pdo,
    $userId
);

/*
|--------------------------------------------------------------------------
| Conversion Statistics
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT COUNT(*)
     FROM conversions
     WHERE worker_id = ?'
);

$stmt->execute([$userId]);

$totalConversions = (int) $stmt->fetchColumn();


$stmt = $pdo->prepare(
    'SELECT COUNT(*)
     FROM conversions
     WHERE worker_id = ?
     AND status = ?'
);

$stmt->execute([
    $userId,
    'APPROVED'
]);

$approvedConversions = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| Withdrawal Statistics
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT COUNT(*)
     FROM withdrawals
     WHERE user_id = ?'
);

$stmt->execute([$userId]);

$totalWithdrawals = (int) $stmt->fetchColumn();


$stmt = $pdo->prepare(
    'SELECT COUNT(*)
     FROM withdrawals
     WHERE user_id = ?
     AND status IN (?, ?)'
);

$stmt->execute([
    $userId,
    'PENDING',
    'PROCESSING'
]);

$pendingWithdrawals = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| Recent Conversions
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT
        c.id,
        c.worker_reward,
        c.network_payout,
        c.platform_margin,
        c.status,
        c.converted_at,
        c.created_at,
        cp.title
     FROM conversions c
     INNER JOIN campaigns cp
        ON cp.id = c.campaign_id
     WHERE c.worker_id = ?
     ORDER BY COALESCE(c.converted_at, c.created_at) DESC
     LIMIT 10'
);

$stmt->execute([$userId]);

$recentConversions = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Recent Withdrawals
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT
        id,
        amount,
        method,
        method_network,
        status,
        transaction_reference,
        created_at,
        processed_at
     FROM withdrawals
     WHERE user_id = ?
     ORDER BY created_at DESC
     LIMIT 10'
);

$stmt->execute([$userId]);

$recentWithdrawals = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Helper Functions
|--------------------------------------------------------------------------
*/

function userStatusClass(string $status): string
{
    return match (strtoupper($status)) {

        'ACTIVE' =>
            'status-active',

        'SUSPENDED' =>
            'status-suspended',

        'BANNED' =>
            'status-banned',

        'PENDING' =>
            'status-pending',

        default =>
            'status-default',
    };
}


function conversionStatusClass(string $status): string
{
    return match (strtoupper($status)) {

        'APPROVED' =>
            'status-approved',

        'PENDING' =>
            'status-pending',

        'REJECTED' =>
            'status-rejected',

        'REVERSED' =>
            'status-reversed',

        default =>
            'status-default',
    };
}


function withdrawalStatusClass(string $status): string
{
    return match (strtoupper($status)) {

        'PAID' =>
            'status-approved',

        'PROCESSING' =>
            'status-processing',

        'PENDING' =>
            'status-pending',

        'REJECTED' =>
            'status-rejected',

        'CANCELLED' =>
            'status-cancelled',

        default =>
            'status-default',
    };
}


/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= e($user['name']) ?> - User Details - PoketFlow Admin
    </title>

    <link
        rel="stylesheet"
        href="assets/user-view.css"
    >

</head>

<body>

<header class="admin-header">

    <div class="admin-brand">
        Poket<span>Flow</span> Admin
    </div>

    <div class="admin-header-right">

        <span class="admin-user-name">
            <?= e($adminUser['name']) ?>
        </span>

        <a href="../index.php">
            View Site
        </a>

        <a href="../logout.php">
            Logout
        </a>

    </div>

</header>


<div class="admin-layout">


    <!-- ==================================================
         SIDEBAR
    =================================================== -->

    <aside class="admin-sidebar">

        <div class="sidebar-title">
            Administration
        </div>

        <nav class="sidebar-nav">

            <a href="index.php">
                Dashboard
            </a>

            <a
                href="users.php"
                class="active"
            >
                Users
            </a>

            <a href="withdrawals.php">
                Withdrawals
            </a>

            <a href="conversions.php">
                Conversions
            </a>

            <a href="campaigns.php">
                Campaigns
            </a>

            <a href="wallet.php">
                Wallet
            </a>

            <a href="settings.php">
                Settings
            </a>

        </nav>

    </aside>


    <!-- ==================================================
         MAIN CONTENT
    =================================================== -->

    <main class="admin-content">


        <!-- PAGE HEADER -->

        <div class="page-top">

            <div>

                <a
                    href="users.php"
                    class="back-link"
                >
                    ← Back to Users
                </a>

                <h1>
                    User Details
                </h1>

                <p>
                    View account information, earnings,
                    conversions and withdrawals.
                </p>

            </div>


            <div class="page-actions">

                <a
                    href="users.php"
                    class="btn btn-secondary"
                >
                    All Users
                </a>

            </div>

        </div>


        <!-- ==================================================
             USER PROFILE
        =================================================== -->

        <section class="user-profile-panel">


            <div class="profile-main">

                <div class="avatar">

                    <?= e(
                        strtoupper(
                            substr(
                                trim($user['name']),
                                0,
                                1
                            )
                        )
                    ) ?>

                </div>


                <div class="profile-info">

                    <h2>
                        <?= e($user['name']) ?>
                    </h2>

                    <p class="email">
                        <?= e($user['email']) ?>
                    </p>


                    <div class="profile-meta">

                        <span class="role-badge">

                            <?= e(
                                $user['role']
                            ) ?>

                        </span>


                        <span
                            class="status-badge
                            <?= e(
                                userStatusClass(
                                    $user['status']
                                )
                            ) ?>"
                        >

                            <?= e(
                                $user['status']
                            ) ?>

                        </span>

                    </div>

                </div>

            </div>


            <div class="profile-actions">

                <?php if ($user['status'] === 'ACTIVE'): ?>

                    <button
                        type="button"
                        class="btn btn-danger"
                        disabled
                    >
                        Suspend User
                    </button>

                <?php elseif ($user['status'] === 'SUSPENDED'): ?>

                    <button
                        type="button"
                        class="btn btn-success"
                        disabled
                    >
                        Activate User
                    </button>

                <?php elseif ($user['status'] === 'PENDING'): ?>

                    <button
                        type="button"
                        class="btn btn-success"
                        disabled
                    >
                        Activate User
                    </button>

                <?php endif; ?>

            </div>

        </section>


        <!-- ==================================================
             STATISTICS
        =================================================== -->

        <section class="stats-grid">


            <div class="stat-card">

                <span class="stat-label">
                    Current Balance
                </span>

                <strong>
                    $<?= number_format(
                        $balance,
                        2
                    ) ?>
                </strong>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Total Earned
                </span>

                <strong>
                    $<?= number_format(
                        $totalEarned,
                        2
                    ) ?>
                </strong>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Pending Balance
                </span>

                <strong>
                    $<?= number_format(
                        $pendingBalance,
                        2
                    ) ?>
                </strong>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Conversions
                </span>

                <strong>
                    <?= number_format(
                        $totalConversions
                    ) ?>
                </strong>

                <small>
                    <?= number_format(
                        $approvedConversions
                    ) ?>
                    approved
                </small>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Withdrawals
                </span>

                <strong>
                    <?= number_format(
                        $totalWithdrawals
                    ) ?>
                </strong>

                <small>
                    <?= number_format(
                        $pendingWithdrawals
                    ) ?>
                    pending
                </small>

            </div>


        </section>


        <!-- ==================================================
             CONTENT GRID
        =================================================== -->

        <div class="content-grid">


            <!-- ==================================================
                 ACCOUNT INFORMATION
            =================================================== -->

            <section class="panel">

                <div class="panel-heading">

                    <h2>
                        Account Information
                    </h2>

                </div>


                <div class="info-list">


                    <div class="info-row">

                        <span>
                            User ID
                        </span>

                        <strong>
                            #<?= (int) $user['id'] ?>
                        </strong>

                    </div>


                    <div class="info-row">

                        <span>
                            Name
                        </span>

                        <strong>
                            <?= e(
                                $user['name']
                            ) ?>
                        </strong>

                    </div>


                    <div class="info-row">

                        <span>
                            Email
                        </span>

                        <strong>
                            <?= e(
                                $user['email']
                            ) ?>
                        </strong>

                    </div>


                    <div class="info-row">

                        <span>
                            Country
                        </span>

                        <strong>
                            <?= e(
                                $user['country']
                                    ?: 'Not provided'
                            ) ?>
                        </strong>

                    </div>


                    <div class="info-row">

                        <span>
                            Role
                        </span>

                        <strong>
                            <?= e(
                                $user['role']
                            ) ?>
                        </strong>

                    </div>


                    <div class="info-row">

                        <span>
                            Status
                        </span>

                        <strong>

                            <span
                                class="status-badge
                                <?= e(
                                    userStatusClass(
                                        $user['status']
                                    )
                                ) ?>"
                            >

                                <?= e(
                                    $user['status']
                                ) ?>

                            </span>

                        </strong>

                    </div>


                    <div class="info-row">

                        <span>
                            Referral Code
                        </span>

                        <strong>
                            <?= e(
                                $user['referral_code']
                            ) ?>
                        </strong>

                    </div>


                    <div class="info-row">

                        <span>
                            Referred By
                        </span>

                        <strong>

                            <?php if ($user['referred_by']): ?>

                                #<?= (int) $user['referred_by'] ?>

                            <?php else: ?>

                                Direct signup

                            <?php endif; ?>

                        </strong>

                    </div>


                    <div class="info-row">

                        <span>
                            Registered
                        </span>

                        <strong>

                            <?= e(
                                date(
                                    'M j, Y H:i',
                                    strtotime(
                                        $user['created_at']
                                    )
                                )
                            ) ?>

                        </strong>

                    </div>


                    <div class="info-row">

                        <span>
                            Last Updated
                        </span>

                        <strong>

                            <?= e(
                                date(
                                    'M j, Y H:i',
                                    strtotime(
                                        $user['updated_at']
                                    )
                                )
                            ) ?>

                        </strong>

                    </div>


                </div>

            </section>


            <!-- ==================================================
                 RECENT CONVERSIONS
            =================================================== -->

            <section class="panel">

                <div class="panel-heading">

                    <h2>
                        Recent Conversions
                    </h2>

                    <a
                        href="conversions.php?worker_id=<?= (int) $user['id'] ?>"
                    >
                        View All
                    </a>

                </div>


                <?php if (!$recentConversions): ?>

                    <div class="empty-state">
                        No conversions yet.
                    </div>

                <?php else: ?>


                    <div class="table-wrap">

                        <table>

                            <thead>

                                <tr>

                                    <th>
                                        Offer
                                    </th>

                                    <th>
                                        Reward
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Date
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php foreach (
                                $recentConversions
                                as $conversion
                            ): ?>


                                <tr>


                                    <td>

                                        <strong>

                                            <?= e(
                                                $conversion['title']
                                            ) ?>

                                        </strong>

                                    </td>


                                    <td class="amount-positive">

                                        +$<?= number_format(
                                            (float)
                                            $conversion[
                                                'worker_reward'
                                            ],
                                            2
                                        ) ?>

                                    </td>


                                    <td>

                                        <span
                                            class="status-badge
                                            <?= e(
                                                conversionStatusClass(
                                                    $conversion[
                                                        'status'
                                                    ]
                                                )
                                            ) ?>"
                                        >

                                            <?= e(
                                                $conversion[
                                                    'status'
                                                ]
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?= e(
                                            date(
                                                'M j, Y',
                                                strtotime(
                                                    $conversion[
                                                        'converted_at'
                                                    ]
                                                    ?: $conversion[
                                                        'created_at'
                                                    ]
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


            <!-- ==================================================
                 RECENT WITHDRAWALS
            =================================================== -->

            <section class="panel full-width">

                <div class="panel-heading">

                    <h2>
                        Recent Withdrawals
                    </h2>

                    <a
                        href="withdrawals.php?user_id=<?= (int) $user['id'] ?>"
                    >
                        View All
                    </a>

                </div>


                <?php if (!$recentWithdrawals): ?>

                    <div class="empty-state">
                        No withdrawals yet.
                    </div>

                <?php else: ?>


                    <div class="table-wrap">

                        <table>

                            <thead>

                                <tr>

                                    <th>
                                        Amount
                                    </th>

                                    <th>
                                        Method
                                    </th>

                                    <th>
                                        Network
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Reference
                                    </th>

                                    <th>
                                        Date
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php foreach (
                                $recentWithdrawals
                                as $withdrawal
                            ): ?>


                                <tr>


                                    <td>

                                        <strong>

                                            $<?= number_format(
                                                (float)
                                                $withdrawal[
                                                    'amount'
                                                ],
                                                2
                                            ) ?>

                                        </strong>

                                    </td>


                                    <td>

                                        <?= e(
                                            $withdrawal[
                                                'method'
                                            ]
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= e(
                                            $withdrawal[
                                                'method_network'
                                            ]
                                            ?: '—'
                                        ) ?>

                                    </td>


                                    <td>

                                        <span
                                            class="status-badge
                                            <?= e(
                                                withdrawalStatusClass(
                                                    $withdrawal[
                                                        'status'
                                                    ]
                                                )
                                            ) ?>"
                                        >

                                            <?= e(
                                                $withdrawal[
                                                    'status'
                                                ]
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?= e(
                                            $withdrawal[
                                                'transaction_reference'
                                            ]
                                            ?: '—'
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= e(
                                            date(
                                                'M j, Y',
                                                strtotime(
                                                    $withdrawal[
                                                        'created_at'
                                                    ]
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


        </div>

    </main>

</div>

</body>
</html>
