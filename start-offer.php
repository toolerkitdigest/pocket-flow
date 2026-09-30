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

$user = getUser(
    $pdo,
    $userId
);


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

    exit(
        'This offer is not available to you.'
    );
}


// --------------------------------------------------
// Verify external offer URL exists
// --------------------------------------------------

$networkOfferUrl = trim(
    (string) (
        $campaign['network_offer_url']
        ?? ''
    )
);


if ($networkOfferUrl === '') {

    http_response_code(502);

    exit(
        'This offer is temporarily unavailable.'
    );
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

    exit(
        'Unable to start this offer. Please try again.'
    );
}


// --------------------------------------------------
// Store tracking ID in session
// --------------------------------------------------

$_SESSION['active_offer_tracking_id'] = $trackingId;

$_SESSION['active_offer_campaign_id'] = $campaignId;


// --------------------------------------------------
// Attach PoketFlow tracking ID to OGAds URL
//
// OGAds supports aff_sub4 as a tracking parameter.
// We use it to carry our internal tracking ID.
// --------------------------------------------------

$separator = (
    strpos($networkOfferUrl, '?') !== false
)
    ? '&'
    : '?';

$networkOfferUrl .=
    $separator .
    'aff_sub4=' .
    rawurlencode($trackingId);


// --------------------------------------------------
// Redirect worker to the actual OGAds offer
// --------------------------------------------------

header(
    'Location: ' . $networkOfferUrl,
    true,
    302
);

exit;
