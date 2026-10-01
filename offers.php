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

$user = getUser(
    $pdo,
    $userId
);

$ogadsError = null;


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
// Fetch LIVE visitor-specific OGAds offers
//
// IMPORTANT:
//
// We do NOT synchronize these offers into the
// global campaigns table here.
//
// OGAds returns inventory based on the visitor's
// IP, device, language, etc.
//
// We keep the current eligible offers in the
// session for the next step: start-offer.php.
// --------------------------------------------------

$campaigns = [];

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


    // --------------------------------------------------
    // Ask OGAds for this visitor's live inventory
    // --------------------------------------------------

    $ogadsOffers = fetchOgadsOffers(
        $ip,
        $userAgent,
        $language,
        $site,
        0,
        50
    );



    // --------------------------------------------------
    // Process visitor-specific offers
    // --------------------------------------------------

    foreach ($ogadsOffers as $offer) {

        $externalOfferId = trim(
            (string) ($offer['offerid'] ?? '')
        );


        // ----------------------------------------------
        // Offer must have an ID
        // ----------------------------------------------

        if ($externalOfferId === '') {
            continue;
        }


        // ----------------------------------------------
        // Clean offer information
        // ----------------------------------------------

        $title = cleanOgadsText(
            $offer['name_short']
                ?? $offer['name']
                ?? 'OGAds Offer'
        );


        $description = cleanOgadsText(
            $offer['description'] ?? ''
        );


        $instructions = cleanOgadsText(
            $offer['adcopy'] ?? ''
        );


        $category = getOgadsOfferCategory(
            $offer
        );


        $countries = trim(
            (string) ($offer['country'] ?? '')
        );


        $devices = trim(
            (string) ($offer['device'] ?? '')
        );


        $networkOfferUrl = trim(
            (string) ($offer['link'] ?? '')
        );


        $imageUrl = trim(
            (string) ($offer['picture'] ?? '')
        );


        $networkPayout = round(
            (float) ($offer['payout'] ?? 0),
            2
        );


        // ----------------------------------------------
        // Must have a payout
        // ----------------------------------------------

        if ($networkPayout <= 0) {
            continue;
        }


        // ----------------------------------------------
        // Must have an actual participation URL
        // ----------------------------------------------

        if ($networkOfferUrl === '') {
            continue;
        }

        // ----------------------------------------------
       // Apply PoketFlow safety filters to the
       // original OGAds offer BEFORE displaying it.
       // ----------------------------------------------

       if (!isOgadsOfferSafe(
           $pdo,
           $offer
       )) {
           continue;
        }


        

        // ----------------------------------------------
        // Calculate worker reward
        // ----------------------------------------------

        $rewards = calculateOgadsReward(
            $pdo,
            $networkPayout
        );


        // ----------------------------------------------
        // Build visitor-specific offer
        // ----------------------------------------------

        $campaigns[] = [

            /*
             * Temporary identifier.
             *
             * This is the OGAds external offer ID,
             * NOT a PoketFlow campaign ID.
             */
            'id' => $externalOfferId,

            'source_type' => 'CPA_NETWORK',

            'network_id' => null,

            'external_offer_id' => $externalOfferId,

            'network_offer_url' => $networkOfferUrl,

            'image_url' => $imageUrl,

            'title' => $title,

            'description' => $description,

            'category' => $category,

            'instructions' => $instructions,

            'network_payout' => $networkPayout,

            'reward_rate' => $rewards['reward_rate'],

            'worker_reward' => $rewards['worker_reward'],

            'platform_margin' => $rewards['platform_margin'],

            'countries' => $countries,

            'devices' => $devices,

            'os' => '',

            'incentive_allowed' => 1,

            'status' => 'ACTIVE',

            'approval_status' => 'APPROVED',

        ];
    }


    // --------------------------------------------------
    // Store the current visitor's eligible offers
    // in the session.
    //
    // start-offer.php will use this in Stage 2.
    // --------------------------------------------------

    $_SESSION['ogads_offers'] = $campaigns;


} catch (Throwable $e) {

    $ogadsError = $e->getMessage();

    /*
     * Clear old visitor-specific offers if the
     * current API request failed.
     */
    $_SESSION['ogads_offers'] = [];

    $campaigns = [];
}


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
        content="width=device-width, initial-scale=1"
    >

    <title>Offers — PoketFlow</title>

    <link
        rel="stylesheet"
        href="assets/poketflow.css"
    >

</head>


<body class="app-page">


<!-- ==================================================
     HEADER
================================================== -->

<header class="app-header">

    <a
        class="brand"
        href="index.php"
    >

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
            href="offers.php"
        >
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
            href="withdraw.php"
        >
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
                href="offers.php"
            >

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
             OGADS ERROR
        ================================================== -->

        <?php if ($ogadsError !== null): ?>

            <div class="info-card">

                <h3>
                    Offers temporarily unavailable
                </h3>

                <p>
                    We could not refresh the offer list right now.
                    Please try again shortly.
                </p>

            </div>

        <?php endif; ?>


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


            <!-- ==================================================
                 OFFER GRID
            ================================================== -->

            <div class="offer-grid dashboard-offers">


                <?php if (empty($campaigns)): ?>


                    <!-- ==================================================
                         NO OFFERS
                    ================================================== -->

                    <article class="offer-card">

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
                                available for your location.
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

                        // ------------------------------------------
                        // Offer information
                        // ------------------------------------------

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
                                $campaign['title']
                                ?? 'Available Offer'
                            )
                        );


                        $description = trim(
                            (string) (
                                $campaign['description']
                                ?? ''
                            )
                        );


                        $reward = (float) (
                            $campaign['worker_reward']
                            ?? 0
                        );


                        $imageUrl = trim(
                            (string) (
                                $campaign['image_url']
                                ?? ''
                            )
                        );

                        ?>


                        <!-- ==================================================
                             REAL OFFER CARD
                        ================================================== -->

                        <article
                            class="offer-card"
                            data-offer-category="<?= e(strtolower($category)) ?>"
                        >


                            <!-- Offer Image / Icon -->

                            <div
                                class="offer-icon <?= e($iconClass) ?>"
                            >

                                <?php if ($imageUrl !== ''): ?>

                                    <img
                                        src="<?= e($imageUrl) ?>"
                                        alt="<?= e($title) ?>"
                                        loading="lazy"
                                    >

                                <?php else: ?>

                                    <?= e($icon) ?>

                                <?php endif; ?>

                            </div>


                            <!-- Offer Information -->

                            <div class="offer-body">

                                <span class="tag">
                                    <?= e($category) ?>
                                </span>


                                <h3>
                                    <?= e($title) ?>
                                </h3>


                                <?php if ($description !== ''): ?>

                                    <p>
                                        <?= e($description) ?>
                                    </p>

                                <?php else: ?>

                                    <p>
                                        Complete this offer
                                        to earn your reward.
                                    </p>

                                <?php endif; ?>

                            </div>


                            <!-- Offer Reward / Start -->

                            <div class="offer-bottom">

                                <strong>
                                    Earn
                                    $<?= number_format($reward, 2) ?>
                                </strong>


                                <a
                                    href="start-offer.php?offer_id=<?= e((string) $campaign['external_offer_id']) ?>"
                                    class="btn btn-primary"
                                >
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

            <aside class="info-card">


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


<!-- ==================================================
     OFFER FILTER JAVASCRIPT
================================================== -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {


        const filterButtons =
            document.querySelectorAll(
                '.offer-tabs button'
            );


        const offerCards =
            document.querySelectorAll(
                '.dashboard-offers .offer-card[data-offer-category]'
            );


        filterButtons.forEach(
            function (button) {


                button.addEventListener(
                    'click',
                    function () {


                        const filter =
                            button.dataset.filter;


                        // ------------------------------------------
                        // Update selected button
                        // ------------------------------------------

                        filterButtons.forEach(
                            function (item) {

                                item.classList.remove(
                                    'selected'
                                );

                            }
                        );


                        button.classList.add(
                            'selected'
                        );


                        // ------------------------------------------
                        // Filter cards
                        // ------------------------------------------

                        offerCards.forEach(
                            function (card) {


                                const category =
                                    (
                                        card.dataset.offerCategory
                                        || ''
                                    ).toLowerCase();


                                let show = false;


                                if (filter === 'all') {

                                    show = true;

                                }


                                if (filter === 'app') {

                                    show =
                                        category.includes(
                                            'app'
                                        ) ||
                                        category.includes(
                                            'install'
                                        );

                                }


                                if (filter === 'survey') {

                                    show =
                                        category.includes(
                                            'survey'
                                        );

                                }


                                if (filter === 'other') {

                                    show =
                                        !category.includes(
                                            'app'
                                        ) &&
                                        !category.includes(
                                            'install'
                                        ) &&
                                        !category.includes(
                                            'survey'
                                        );

                                }


                                card.style.display =
                                    show ? '' : 'none';

                            }
                        );

                    }
                );

            }
        );

    }
);

</script>


</body>

</html>
