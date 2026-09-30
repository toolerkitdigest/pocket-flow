<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';


// --------------------------------------------------
// Protect offer start
// --------------------------------------------------

if (!isLoggedIn()) {
    redirect('login.php');
}


// --------------------------------------------------
// Get logged-in user
// --------------------------------------------------

$userId = (int) $_SESSION['user_id'];

$user = getUser($pdo, $userId);


// --------------------------------------------------
// Safety check
// --------------------------------------------------

if (!$user) {

    $_SESSION = [];

    session_destroy();

    redirect('login.php');
}


// --------------------------------------------------
// Get campaign ID
// --------------------------------------------------

$campaignId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


// --------------------------------------------------
// Validate campaign ID
// --------------------------------------------------

if (!$campaignId || $campaignId < 1) {

    http_response_code(400);

    exit('Invalid offer.');
}


// --------------------------------------------------
// Get campaign
// --------------------------------------------------

$campaign = getCampaign(
    $pdo,
    $campaignId
);


// --------------------------------------------------
// Campaign must exist
// --------------------------------------------------

if (!$campaign) {

    http_response_code(404);

    exit('Offer not found.');
}


// --------------------------------------------------
// Verify campaign can be started
// --------------------------------------------------

if (!canStartCampaign(
    $pdo,
    $campaign,
    $user
)) {

    http_response_code(403);

    exit('This offer is not available to you.');
}


// --------------------------------------------------
// Create campaign click
// --------------------------------------------------

try {

    $trackingId = createCampaignClick(
        $pdo,
        $campaignId,
        $userId,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    );

} catch (Throwable $e) {

    http_response_code(500);

    exit('Unable to start this offer. Please try again.');
}


// --------------------------------------------------
// Temporary Stage 3 response
// --------------------------------------------------
//
// We are intentionally NOT redirecting to a CPA network yet.
//
// The next part will connect this tracking ID to the
// appropriate network tracking URL.
// --------------------------------------------------

?>

<!doctype html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width,initial-scale=1"
    >

    <title>Offer Started — PoketFlow</title>

    <link
        rel="stylesheet"
        href="assets/poketflow.css"
    >

</head>


<body class="app-page">


<main class="app-shell">


    <section class="app-content">


        <div class="page-title">

            <div>

                <span class="kicker">
                    OFFER STARTED
                </span>


                <h1>
                    <?= e($campaign['title']) ?>
                </h1>


                <p>
                    Your offer session has been created successfully.
                </p>

            </div>

        </div>


        <div class="info-card">

            <div class="info-icon">
                ✓
            </div>


            <h3>
                Tracking started
            </h3>


            <p>
                Your PoketFlow tracking session has been recorded.
                The external offer connection will be enabled
                in the next step.
            </p>


            <p>
                Tracking ID:
                <strong>
                    <?= e($trackingId) ?>
                </strong>
            </p>


            <a
                href="offers.php"
                class="btn btn-primary"
            >
                Back to Offers
            </a>

        </div>


    </section>


</main>


</body>

</html>
