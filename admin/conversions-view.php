<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/admin-auth.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Conversion Details';
$currentPage = 'conversion-view.php';

$conversionId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$conversionId || $conversionId < 1) {
    http_response_code(404);
    exit('Conversion not found.');
}

/*
|--------------------------------------------------------------------------
| Load Conversion
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT
        c.id,
        c.campaign_id,
        c.worker_id,
        c.network_id,
        c.external_transaction_id,
        c.network_payout,
        c.reward_rate,
        c.worker_reward,
        c.platform_margin,
        c.status,
        c.converted_at,
        c.created_at,
        c.updated_at,

        u.name AS worker_name,
        u.email AS worker_email,
        u.country AS worker_country,
        u.role AS worker_role,
        u.status AS worker_status,

        cp.title AS campaign_title,
        cp.category AS campaign_category,
        cp.external_offer_id,
        cp.source_type,
        cp.status AS campaign_status,

        n.name AS network_name,
        n.slug AS network_slug

     FROM conversions c

     INNER JOIN users u
        ON u.id = c.worker_id

     LEFT JOIN campaigns cp
        ON cp.id = c.campaign_id

     LEFT JOIN networks n
        ON n.id = c.network_id

     WHERE c.id = ?

     LIMIT 1'
);

$stmt->execute([$conversionId]);

$conversion = $stmt->fetch();

if (!$conversion) {
    http_response_code(404);
    exit('Conversion not found.');
}

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function conversionStatusLabel(string $status): string
{
    return match ($status) {
        'APPROVED' => 'Approved',
        'PENDING' => 'Pending',
        'REJECTED' => 'Rejected',
        'REVERSED' => 'Reversed',
        default => ucfirst(strtolower($status)),
    };
}

function conversionStatusClass(string $status): string
{
    return match ($status) {
        'APPROVED' => 'status-approved',
        'PENDING' => 'status-pending',
        'REJECTED' => 'status-rejected',
        'REVERSED' => 'status-reversed',
        default => 'status-default',
    };
}

function formatConversionMoney(?float $amount): string
{
    return '$' . number_format((float) $amount, 2);
}

function formatConversionDate(?string $date): string
{
    if (!$date) {
        return '—';
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return $date;
    }

    return date('M j, Y · g:i A', $timestamp);
}

/*
|--------------------------------------------------------------------------
| Prepared Values
|--------------------------------------------------------------------------
*/

$status = strtoupper((string) ($conversion['status'] ?? ''));

$networkPayout = (float) ($conversion['network_payout'] ?? 0);
$workerReward = (float) ($conversion['worker_reward'] ?? 0);
$platformMargin = (float) ($conversion['platform_margin'] ?? 0);
$rewardRate = (float) ($conversion['reward_rate'] ?? 0);

$adminName = (string) ($adminUser['name'] ?? 'Admin');

$workerName = (string) ($conversion['worker_name'] ?? 'Unknown Worker');

$workerInitial = strtoupper(
    substr($workerName, 0, 1)
);

$campaignTitle = (string) (
    $conversion['campaign_title']
    ?: 'Unknown Campaign'
);

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
        Conversion #<?= e((string) $conversion['id']) ?>
        · PoketFlow Admin
    </title>

    <link
        rel="stylesheet"
        href="assets/conversions-view.css"
    >

    <link
        rel="stylesheet"
        href="assets/conversion-view.css"
    >

</head>

<body>

<div class="admin-layout">

    <!-- =========================================================
         SIDEBAR
    ========================================================== -->

    <aside class="admin-sidebar">

        <div class="admin-brand">

            <a href="index.php">
                PoketFlow
                <span>Admin</span>
            </a>

        </div>


        <nav class="admin-nav">

            <div class="nav-section-title">
                Overview
            </div>

            <a
                href="index.php"
                class="<?= $currentPage === 'index.php' ? 'active' : '' ?>"
            >
                <span>▦</span>
                Dashboard
            </a>


            <div class="nav-section-title">
                Users
            </div>

            <a
                href="users.php"
                class="<?= $currentPage === 'users.php' || $currentPage === 'user-view.php' ? 'active' : '' ?>"
            >
                <span>◉</span>
                Users
            </a>


            <div class="nav-section-title">
                Finance
            </div>

            <a
                href="withdrawals.php"
                class="<?= $currentPage === 'withdrawals.php' || $currentPage === 'withdrawal-view.php' ? 'active' : '' ?>"
            >
                <span>⇩</span>
                Withdrawals
            </a>

            <a
                href="conversions.php"
                class="<?= $currentPage === 'conversions.php' || $currentPage === 'conversion-view.php' ? 'active' : '' ?>"
            >
                <span>↗</span>
                Conversions
            </a>

            <a
                href="wallet.php"
                class="<?= $currentPage === 'wallet.php' ? 'active' : '' ?>"
            >
                <span>◈</span>
                Wallet
            </a>


            <div class="nav-section-title">
                Offers
            </div>

            <a
                href="campaigns.php"
                class="<?= $currentPage === 'campaigns.php' ? 'active' : '' ?>"
            >
                <span>▤</span>
                Campaigns
            </a>


            <div class="nav-section-title">
                System
            </div>

            <a
                href="settings.php"
                class="<?= $currentPage === 'settings.php' ? 'active' : '' ?>"
            >
                <span>⚙</span>
                Settings
            </a>

        </nav>


        <div class="sidebar-bottom">

            <a
                href="../index.php"
                class="sidebar-link"
                target="_blank"
            >
                <span>↗</span>
                View Site
            </a>

            <a
                href="../logout.php"
                class="sidebar-link logout-link"
            >
                <span>⇥</span>
                Logout
            </a>

        </div>

    </aside>


    <!-- =========================================================
         MAIN
    ========================================================== -->

    <main class="admin-main">

        <header class="admin-topbar">

            <div class="topbar-title">

                <div class="mobile-menu-placeholder"></div>

                <div>

                    <h1>
                        <?= e($pageTitle) ?>
                    </h1>

                    <p>
                        Admin Control Center
                    </p>

                </div>

            </div>


            <div class="admin-user">

                <div class="admin-avatar">
                    <?= e(
                        strtoupper(
                            substr($adminName, 0, 1)
                        )
                    ) ?>
                </div>

                <div class="admin-user-info">

                    <strong>
                        <?= e($adminName) ?>
                    </strong>

                    <span>
                        Administrator
                    </span>

                </div>

            </div>

        </header>


        <!-- =====================================================
             CONTENT
        ====================================================== -->

        <div class="admin-content">

            <a
                href="conversions.php"
                class="detail-back"
            >
                ← Back to Conversions
            </a>


            <!-- =================================================
                 CONVERSION HERO
            ================================================== -->

            <section class="conversion-hero">

                <div class="conversion-hero-left">

                    <div class="conversion-kicker">
                        Conversion Record
                    </div>

                    <h2>
                        <?= e($campaignTitle) ?>
                    </h2>

                    <div class="conversion-id">
                        Conversion #<?= e(
                            (string) $conversion['id']
                        ) ?>
                    </div>

                </div>


                <div class="conversion-hero-right">

                    <span
                        class="status-large <?= e(
                            conversionStatusClass($status)
                        ) ?>"
                    >
                        <?= e(
                            conversionStatusLabel($status)
                        ) ?>
                    </span>

                </div>

            </section>


            <div class="detail-grid">


                <!-- =================================================
                     LEFT COLUMN
                ================================================== -->

                <div class="detail-column">


                    <!-- WORKER -->

                    <section class="detail-card">

                        <div class="detail-card-header">

                            <h3>
                                Worker
                            </h3>

                            <p>
                                The user who completed the offer.
                            </p>

                        </div>


                        <div class="worker-profile">

                            <div class="worker-avatar">
                                <?= e($workerInitial) ?>
                            </div>

                            <div>

                                <strong>
                                    <?= e($workerName) ?>
                                </strong>

                                <span>
                                    <?= e(
                                        (string) $conversion['worker_email']
                                    ) ?>
                                </span>

                            </div>

                        </div>


                        <div class="detail-list">

                            <div class="detail-row">

                                <div class="detail-label">
                                    Worker ID
                                </div>

                                <div class="detail-value">
                                    <strong>
                                        #<?= e(
                                            (string) $conversion['worker_id']
                                        ) ?>
                                    </strong>
                                </div>

                            </div>


                            <div class="detail-row">

                                <div class="detail-label">
                                    Country
                                </div>

                                <div class="detail-value">
                                    <?= e(
                                        (string) (
                                            $conversion['worker_country']
                                            ?: 'Not specified'
                                        )
                                    ) ?>
                                </div>

                            </div>


                            <div class="detail-row">

                                <div class="detail-label">
                                    Account Status
                                </div>

                                <div class="detail-value">
                                    <?= e(
                                        (string) $conversion['worker_status']
                                    ) ?>
                                </div>

                            </div>


                            <div class="detail-row">

                                <div class="detail-label">
                                    Worker Role
                                </div>

                                <div class="detail-value">
                                    <?= e(
                                        (string) $conversion['worker_role']
                                    ) ?>
                                </div>

                            </div>

                        </div>

                    </section>


                    <!-- OFFER -->

                    <section class="detail-card">

                        <div class="detail-card-header">

                            <h3>
                                Offer Information
                            </h3>

                            <p>
                                Campaign and network information.
                            </p>

                        </div>


                        <div class="detail-list">

                            <div class="detail-row">

                                <div class="detail-label">
                                    Campaign
                                </div>

                                <div class="detail-value">

                                    <strong>
                                        <?= e($campaignTitle) ?>
                                    </strong>

                                </div>

                            </div>


                            <div class="detail-row">

                                <div class="detail-label">
                                    Campaign ID
                                </div>

                                <div class="detail-value">

                                    <strong>
                                        #<?= e(
                                            (string) $conversion['campaign_id']
                                        ) ?>
                                    </strong>

                                </div>

                            </div>


                            <div class="detail-row">

                                <div class="detail-label">
                                    External Offer ID
                                </div>

                                <div class="detail-value">

                                    <?php if (!empty($conversion['external_offer_id'])): ?>

                                        <code>
                                            <?= e(
                                                (string) $conversion['external_offer_id']
                                            ) ?>
                                        </code>

                                    <?php else: ?>

                                        —

                                    <?php endif; ?>

                                </div>

                            </div>


                            <div class="detail-row">

                                <div class="detail-label">
                                    Category
                                </div>

                                <div class="detail-value">

                                    <?= e(
                                        (string) (
                                            $conversion['campaign_category']
                                            ?: 'Uncategorized'
                                        )
                                    ) ?>

                                </div>

                            </div>


                            <div class="detail-row">

                                <div class="detail-label">
                                    Source Type
                                </div>

                                <div class="detail-value">

                                    <?= e(
                                        (string) (
                                            $conversion['source_type']
                                            ?: '—'
                                        )
                                    ) ?>

                                </div>

                            </div>


                            <div class="detail-row">

                                <div class="detail-label">
                                    Campaign Status
                                </div>

                                <div class="detail-value">

                                    <?= e(
                                        (string) (
                                            $conversion['campaign_status']
                                            ?: '—'
                                        )
                                    ) ?>

                                </div>

                            </div>


                            <div class="detail-row">

                                <div class="detail-label">
                                    Network
                                </div>

                                <div class="detail-value">

                                    <strong>
                                        <?= e(
                                            (string) (
                                                $conversion['network_name']
                                                ?: 'Unknown'
                                            )
                                        ) ?>
                                    </strong>

                                    <?php if (!empty($conversion['network_slug'])): ?>

                                        <small>
                                            <?= e(
                                                (string) $conversion['network_slug']
                                            ) ?>
                                        </small>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </div>

                    </section>


                    <!-- TRACKING -->

                    <section class="detail-card">

                        <div class="detail-card-header">

                            <h3>
                                Tracking Information
                            </h3>

                            <p>
                                Identifiers associated with this conversion.
                            </p>

                        </div>


                        <div class="detail-list">

                            <div class="detail-row">

                                <div class="detail-label">
                                    Conversion ID
                                </div>

                                <div class="detail-value">

                                    <code>
                                        <?= e(
                                            (string) $conversion['id']
                                        ) ?>
                                    </code>

                                </div>

                            </div>


                            <div class="detail-row">

                                <div class="detail-label">
                                    External Transaction ID
                                </div>

                                <div class="detail-value">

                                    <?php if (!empty($conversion['external_transaction_id'])): ?>

                                        <code>
                                            <?= e(
                                                (string) $conversion['external_transaction_id']
                                            ) ?>
                                        </code>

                                    <?php else: ?>

                                        —

                                    <?php endif; ?>

                                </div>

                            </div>


                            <div class="detail-row">

                                <div class="detail-label">
                                    Network ID
                                </div>

                                <div class="detail-value">

                                    <?= !empty($conversion['network_id'])
                                        ? '#' . e(
                                            (string) $conversion['network_id']
                                        )
                                        : '—'
                                    ?>

                                </div>

                            </div>

                        </div>

                    </section>

                </div>


                <!-- =================================================
                     RIGHT COLUMN
                ================================================== -->

                <div class="detail-column">


                    <!-- FINANCIAL -->

                    <section class="detail-card">

                        <div class="detail-card-header">

                            <h3>
                                Financial Breakdown
                            </h3>

                            <p>
                                How this conversion was divided.
                            </p>

                        </div>


                        <div class="money-card">

                            <div class="money-main">

                                <div class="money-main-label">
                                    Network Payout
                                </div>

                                <strong>
                                    <?= e(
                                        formatConversionMoney(
                                            $networkPayout
                                        )
                                    ) ?>
                                </strong>

                            </div>


                            <div class="money-breakdown">

                                <div class="money-line">

                                    <span>
                                        Reward Rate
                                    </span>

                                    <span>
                                        <?= e(
                                            number_format(
                                                $rewardRate,
                                                2
                                            )
                                        ) ?>%
                                    </span>

                                </div>


                                <div class="money-line">

                                    <span>
                                        Worker Reward
                                    </span>

                                    <span>
                                        <?= e(
                                            formatConversionMoney(
                                                $workerReward
                                            )
                                        ) ?>
                                    </span>

                                </div>


                                <div class="money-line">

                                    <span>
                                        Platform Margin
                                    </span>

                                    <span>
                                        <?= e(
                                            formatConversionMoney(
                                                $platformMargin
                                            )
                                        ) ?>
                                    </span>

                                </div>


                                <div class="money-line total">

                                    <span>
                                        Total Accounted
                                    </span>

                                    <span>
                                        <?= e(
                                            formatConversionMoney(
                                                $workerReward
                                                + $platformMargin
                                            )
                                        ) ?>
                                    </span>

                                </div>

                            </div>

                        </div>

                    </section>


                    <!-- TIMELINE -->

                    <section class="detail-card">

                        <div class="detail-card-header">

                            <h3>
                                Timeline
                            </h3>

                            <p>
                                Important timestamps.
                            </p>

                        </div>


                        <div class="timeline">

                            <div class="timeline-item">

                                <div class="timeline-title">
                                    Conversion Created
                                </div>

                                <div class="timeline-date">
                                    <?= e(
                                        formatConversionDate(
                                            $conversion['created_at']
                                            ?? null
                                        )
                                    ) ?>
                                </div>

                            </div>


                            <div class="timeline-item">

                                <div class="timeline-title">
                                    Conversion Recorded
                                </div>

                                <div class="timeline-date">
                                    <?= e(
                                        formatConversionDate(
                                            $conversion['converted_at']
                                            ?? null
                                        )
                                    ) ?>
                                </div>

                            </div>


                            <div class="timeline-item">

                                <div class="timeline-title">
                                    Last Updated
                                </div>

                                <div class="timeline-date">
                                    <?= e(
                                        formatConversionDate(
                                            $conversion['updated_at']
                                            ?? null
                                        )
                                    ) ?>
                                </div>

                            </div>

                        </div>

                    </section>


                    <!-- STATUS -->

                    <section class="detail-card">

                        <div class="detail-card-header">

                            <h3>
                                Current Status
                            </h3>

                            <p>
                                Current state of this conversion.
                            </p>

                        </div>


                        <div class="detail-list">

                            <div class="detail-row">

                                <div class="detail-label">
                                    Status
                                </div>

                                <div class="detail-value">

                                    <span
                                        class="status-large <?= e(
                                            conversionStatusClass($status)
                                        ) ?>"
                                    >
                                        <?= e(
                                            conversionStatusLabel($status)
                                        ) ?>
                                    </span>

                                </div>

                            </div>


                            <div class="detail-row">

                                <div class="detail-label">
                                    Worker Reward
                                </div>

                                <div class="detail-value">

                                    <strong>
                                        <?= e(
                                            formatConversionMoney(
                                                $workerReward
                                            )
                                        ) ?>
                                    </strong>

                                </div>

                            </div>


                            <div class="detail-row">

                                <div class="detail-label">
                                    Platform Revenue
                                </div>

                                <div class="detail-value">

                                    <strong>
                                        <?= e(
                                            formatConversionMoney(
                                                $platformMargin
                                            )
                                        ) ?>
                                    </strong>

                                </div>

                            </div>

                        </div>

                    </section>

                </div>

            </div>

        </div>

    </main>

</div>

</body>
</html>
