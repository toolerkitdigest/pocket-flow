<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/ogads.php';


// --------------------------------------------------
// Protect offers page
// --------------------------------------------------

if (!isLoggedIn()) {
    redirect('login.php');
}


// --------------------------------------------------
// Get logged-in user
// --------------------------------------------------

$userId = (int) $_SESSION['user_id'];

$user = getUser($pdo, $userId);

$ogadsError = null;

try {

    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';

    $scheme = (
        !empty($_SERVER['HTTPS']) &&
        $_SERVER['HTTPS'] !== 'off'
    ) ? 'https' : 'http';

    $site = $scheme . '://' . (
        $_SERVER['HTTP_HOST'] ?? 'poketflow.com'
    ) . (
        $_SERVER['REQUEST_URI'] ?? '/offers.php'
    );

    $ogadsOffers = fetchOgadsOffers(
        $ip,
        $userAgent,
        $language,
        $site,
        0,
        50
    );

    syncOgadsOffers(
        $pdo,
        $ogadsOffers
    );

} catch (Throwable $e) {

    $ogadsError = $e->getMessage();
}



// --------------------------------------------------
// Safety check
// --------------------------------------------------

if (!$user) {

    $_SESSION = [];

    session_destroy();

    redirect('login.php');
}


// --------------------------------------------------
// Get real wallet balance
// --------------------------------------------------

$availableBalance = getUserBalance(
    $pdo,
    $userId
);


// --------------------------------------------------
// Get active campaigns
//
// This automatically applies:
//
// 1. ACTIVE status
// 2. APPROVED status
// 3. Start/end dates
// 4. Country eligibility
// 5. Offer safety filters
// --------------------------------------------------

$campaigns = getActiveCampaigns(
    $pdo,
    $user['country'] ?? null
);


// --------------------------------------------------
// Determine offer icon
// --------------------------------------------------

function getOfferIcon(string $category): string
{
    $category = strtolower(trim($category));

    return match (true) {

        str_contains($category, 'app'),
        str_contains($category, 'install')
            => '◎',

        str_contains($category, 'survey')
            => '▤',

        str_contains($category, 'submit')
            => '◇',

        default
            => '◆',
    };
}


// --------------------------------------------------
// Determine icon class
// --------------------------------------------------

function getOfferIconClass(string $category): string
{
    $category = strtolower(trim($category));

    return match (true) {

        str_contains($category, 'survey')
            => 'orange',

        str_contains($category, 'special'),
        str_contains($category, 'featured')
            => 'cyan',

        default
            => '',
    };
}


// --------------------------------------------------
// Format offer category
// --------------------------------------------------

function getOfferCategory(array $campaign): string
{
    $category = trim(
        (string) ($campaign['category'] ?? '')
    );

    if ($category !== '') {
        return $category;
    }

    return match ($campaign['source_type'] ?? '') {

        'DIRECT_ADVERTISER'
            => 'Special Offer',

        default
            => 'Offer',
    };
}

?>

<!doctype html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width,initial-scale=1">

    <title>Offers — PoketFlow</title>

    <link
        rel="stylesheet"
        href="assets/poketflow.css">

    </head>


<body class="app-page">


<!-- ==================================================
     HEADER
================================================== -->

<header class="app-header">


    <a class="brand"
        href="index.php">

        <span class="brand-mark">
            P
        </span>

        <span>
            Poket<span>Flow</span>
        </span>

    </a>


    <nav>

        <a href="dashboard.php">
            Home
        </a>

        <a
            class="active"
            href="offers.php">
            Earn
        </a>

        <a href="history.php">
            History
        </a>

        <a href="referrals.php">
            Refer & Earn
        </a>

        <a href="withdraw.php">
            Withdraw
        </a>

    </nav>


    <div class="header-actions">

        <a
            class="btn btn-primary"
            href="withdraw.php">
            Balance $<?= number_format($availableBalance, 2) ?>
        </a>

    </div>

</header>


<!-- ==================================================
     APP LAYOUT
================================================== -->

<main class="app-shell">


    <!-- ==================================================
         SIDEBAR
    ================================================== -->

    <aside class="sidebar">


        <div class="sidebar-nav">


            <a href="dashboard.php">

                ⌂

                <span>
                    Home
                </span>

            </a>


            <a
                class="active"
                href="offers.php">

                ▦

                <span>
                    Offers
                </span>

            </a>


            <a href="history.php">

                ◷

                <span>
                    History
                </span>

            </a>


            <a href="referrals.php">

                ♧

                <span>
                    Refer & Earn
                </span>

            </a>


            <a href="withdraw.php">

                ▣

                <span>
                    Withdraw
                </span>

            </a>


            <a href="logout.php">

                ↪

                <span>
                    Log Out
                </span>

            </a>


        </div>


        <!-- Balance -->

        <div class="side-balance">

            <small>
                Your Balance
            </small>


            <strong>
                $<?= number_format($availableBalance, 2) ?>
            </strong>


            <span>
                Available to withdraw
            </span>


            <a href="withdraw.php">
                Withdraw Funds →
            </a>

        </div>


    </aside>


    <!-- ==================================================
         MAIN CONTENT
    ================================================== -->

    <section class="app-content">


        <!-- Page Title -->

        <div class="page-title">

            <div>

                <span class="kicker">
                    EARN REWARDS
                </span>


                <h1>
                    Offers
                </h1>


                <p>
                    Complete available offers to grow your balance.
                    The list refreshes automatically.
                </p>

            </div>

        </div>


        <!-- ==================================================
             OFFER FILTERS
        ================================================== -->

    
<div class="offer-tabs">

    <button
        type="button"
        class="selected"
        data-filter="all"
    >
        All Offers
    </button>

    <button
        type="button"
        data-filter="app"
    >
        App Install
    </button>

    <button
        type="button"
        data-filter="survey"
    >
        Survey
    </button>

    <button
        type="button"
        data-filter="other"
    >
        Other Offers
    </button>

</div>
        <!-- ==================================================
             OFFER AREA
        ================================================== -->

        <div class="dashboard-grid">


            <div class="offer-grid dashboard-offers">


                <?php if (empty($campaigns)): ?>


                    <!-- ==================================================
                         NO OFFERS
                    ================================================== -->

                    <article
    class="offer-card"
    data-offer-category="<?= e(strtolower($category)) ?>"
>

                        <div class="offer-icon">
                            ◷
                        </div>


                        <div class="offer-body">

                            <span class="tag">
                                No Offers
                            </span>


                            <h3>
                                No offers available right now
                            </h3>


                            <p>
                                There are currently no offers
                                available for your country.
                                Please check again later.
                            </p>

                        </div>


                        <div class="offer-bottom">

                            <strong>
                                Check back soon
                            </strong>

                        </div>

                    </article>


                <?php else: ?>


                    <?php foreach ($campaigns as $campaign): ?>

                        <?php

                        $category = getOfferCategory(
                            $campaign
                        );

                        $icon = getOfferIcon(
                            $category
                        );

                        $iconClass = getOfferIconClass(
                            $category
                        );

                        $title = trim(
                            (string) (
                                $campaign['title'] ?? 'Available Offer'
                            )
                        );

                        $description = trim(
                            (string) (
                                $campaign['description'] ?? ''
                            )
                        );

                        $reward = (float) (
                            $campaign['worker_reward'] ?? 0
                        );

                        ?>

                        <!-- ==================================================
                             REAL OFFER
                        ================================================== -->

                        <article class="offer-card">


                            <div class="offer-icon <?= e($iconClass) ?>">

    <?php if (!empty($campaign['image_url'])): ?>

        <img
            src="<?= e($campaign['image_url']) ?>"
            alt="<?= e($title) ?>"
            loading="lazy"
        >

    <?php else: ?>

        <?= e($icon) ?>

    <?php endif; ?>

</div>

                            


                            <div class="offer-body">

                                <span class="tag">
                                    <?= e($category) ?>
                                </span>


                                <h3>
                                    <?= e($title) ?>
                                </h3>


                                <p>
                                    <?= e($description) ?>
                                </p>

                            </div>


                            <div class="offer-bottom">

                                <strong>
                                    Earn
                                    $<?= number_format($reward, 2) ?>
                                </strong>


                                <!--
                                Stage 3 will replace this disabled
                                button with the secure start-offer.php
                                flow.
                                -->

                                <a href="start-offer.php?id=<?= (int) $campaign['id'] ?>" class="btn btn-primary">
                                    Start →
                                </a>
                                </div>


                        </article>

                    <?php endforeach; ?>


                <?php endif; ?>


            </div>


            <!-- ==================================================
                 INFORMATION CARD
            ================================================== -->

            <a side class="info-card">


                <div class="info-icon">
                    ◷
                </div>


                <h3>
                    Track your completions
                </h3>


                <p>
                    Visit Offer History to see your recent
                    activity and completion status.
                </p>


                <a href="history.php">
                    View Offer History →
                </a>


                <hr>


                <small>
                    Completion tracking can take some time
                    depending on the offer.
                </small>


            </aside>


        </div>


    </section>


</main>
            
</body>
                
<script>
document.addEventListener('DOMContentLoaded', function () {

    const filterButtons = document.querySelectorAll(
        '.offer-tabs button'
    );

    const offerCards = document.querySelectorAll(
        '.dashboard-offers .offer-card[data-offer-category]'
    );

    filterButtons.forEach(function (button) {

        button.addEventListener('click', function () {

            const filter = button.dataset.filter;

            filterButtons.forEach(function (item) {
                item.classList.remove('selected');
            });

            button.classList.add('selected');

            offerCards.forEach(function (card) {

                const category = (
                    card.dataset.offerCategory || ''
                ).toLowerCase();

                let show = false;

                if (filter === 'all') {
                    show = true;
                }

                if (filter === 'app') {
                    show = category.includes('app');
                }

                if (filter === 'survey') {
                    show = category.includes('survey');
                }

                if (filter === 'other') {
                    show =
                        !category.includes('app') &&
                        !category.includes('survey');
                }

                card.style.display = show
                    ? ''
                    : 'none';
            });
        });
    });

});
</script>

                

</html>
