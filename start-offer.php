<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/ogads.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$userId = (int) $_SESSION['user_id'];

$user = getUser(
    $pdo,
    $userId
);

if (!$user) {
    $_SESSION = [];
    session_destroy();

    redirect('login.php');
}

/*
 * Read the live OGAds offer ID.
 */
$offerId = filter_input(
    INPUT_GET,
    'offer_id',
    FILTER_VALIDATE_INT
);

if (!$offerId || $offerId < 1) {
    http_response_code(400);
    exit('Invalid offer.');
}

$offerId = (string) $offerId;

/*
 * The offers page stores the visitor-specific
 * OGAds inventory in the session.
 */
$sessionOffers = $_SESSION['ogads_offers'] ?? null;

if (
    !is_array($sessionOffers) ||
    empty($sessionOffers)
) {
    http_response_code(404);

    exit(
        'Your offer list has expired. Please return to the Offers page and try again.'
    );
}

/*
 * Find the selected offer inside the visitor's
 * current OGAds offer list.
 */
$selectedOffer = null;

foreach ($sessionOffers as $offer) {

    $externalOfferId = trim(
        (string) (
            $offer['external_offer_id']
            ?? ''
        )
    );

    if ($externalOfferId === $offerId) {
        $selectedOffer = $offer;
        break;
    }
}

if ($selectedOffer === null) {
    http_response_code(404);

    exit(
        'This offer is no longer available. Please return to the Offers page and try again.'
    );
}

/*
 * Validate the offer again before starting it.
 *
 * We intentionally do NOT call canStartCampaign()
 * here because that function checks the user's
 * account country.
 *
 * OGAds has already supplied this offer specifically
 * for the visitor's current IP/GEO/device.
 */
$networkOfferUrl = trim(
    (string) (
        $selectedOffer['network_offer_url']
        ?? ''
    )
);

$networkPayout = (float) (
    $selectedOffer['network_payout']
    ?? 0
);

if ($networkOfferUrl === '') {
    http_response_code(502);

    exit(
        'This offer is temporarily unavailable.'
    );
}

if ($networkPayout <= 0) {
    http_response_code(400);

    exit(
        'This offer is currently unavailable.'
    );
}

/*
 * Run PoketFlow safety filters one more time.
 */
$campaignForFilter = [
    'title' => $selectedOffer['title'] ?? '',
    'description' => $selectedOffer['description'] ?? '',
    'category' => $selectedOffer['category'] ?? '',
    'instructions' => $selectedOffer['instructions'] ?? '',
];

if (!isCampaignAllowed(
    $pdo,
    $campaignForFilter
)) {
    http_response_code(403);

    exit(
        'This offer is not available.'
    );
}

/*
 * Get the OGAds network record.
 */
try {

    $networkId = getOgadsNetworkId(
        $pdo
    );

} catch (Throwable $e) {

    http_response_code(500);

    exit(
        'Unable to connect the offer network. Please try again.'
    );
}

/*
 * Convert our session offer back into the
 * structure expected by syncOgadsOffer().
 *
 * This allows us to create/update a campaign
 * record only when a visitor actually starts
 * an offer.
 */
$ogadsOffer = [
    'offerid' => $offerId,

    'name_short' => (
        $selectedOffer['title']
        ?? 'OGAds Offer'
    ),

    'name' => (
        $selectedOffer['title']
        ?? 'OGAds Offer'
    ),

    'description' => (
        $selectedOffer['description']
        ?? ''
    ),

    'adcopy' => (
        $selectedOffer['instructions']
        ?? ''
    ),

    'country' => (
        $selectedOffer['countries']
        ?? ''
    ),

    'device' => (
        $selectedOffer['devices']
        ?? ''
    ),

    'link' => $networkOfferUrl,

    'picture' => (
        $selectedOffer['image_url']
        ?? ''
    ),

    'payout' => $networkPayout,
];

/*
 * Create or update the campaign record.
 *
 * We only persist the offer here when the visitor
 * actually starts it. We are NOT importing the
 * entire live OGAds inventory into campaigns.
 */
try {

    $campaignId = syncOgadsOffer(
        $pdo,
        $networkId,
        $ogadsOffer
    );

} catch (Throwable $e) {

    http_response_code(500);

    exit(
        'Unable to prepare this offer. Please try again.'
    );
}

if (!$campaignId) {
    http_response_code(502);

    exit(
        'This offer could not be prepared.'
    );
}

/*
 * Load the campaign record.
 */
$campaign = getCampaign(
    $pdo,
    $campaignId
);

if (!$campaign) {
    http_response_code(404);

    exit(
        'Offer not found.'
    );
}

/*
 * Final campaign safety/status check.
 *
 * We do not check account-country eligibility here.
 * The offer came directly from the visitor-specific
 * OGAds inventory stored in the session.
 */
if (
    ($campaign['status'] ?? '') !== 'ACTIVE' ||
    ($campaign['approval_status'] ?? '') !== 'APPROVED'
) {
    http_response_code(403);

    exit(
        'This offer is not currently available.'
    );
}

if (!isCampaignAllowed(
    $pdo,
    $campaign
)) {
    http_response_code(403);

    exit(
        'This offer is not available.'
    );
}

/*
 * Create a unique tracking ID.
 *
 * This ID will be sent to OGAds as aff_sub4.
 *
 * Later:
 *
 * OGAds
 *   ↓
 * postback.php
 *   ↓
 * campaign_clicks
 *   ↓
 * conversions
 *   ↓
 * wallet_transactions
 */
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

/*
 * Keep the active tracking information in the session.
 */
$_SESSION['active_offer_tracking_id'] =
    $trackingId;

$_SESSION['active_offer_campaign_id'] =
    $campaignId;

/*
 * Add our tracking ID to the real OGAds URL.
 */
$separator = (
    strpos($networkOfferUrl, '?') !== false
)
    ? '&'
    : '?';

$networkOfferUrl .=
    $separator .
    'aff_sub4=' .
    rawurlencode($trackingId);

/*
 * Send the visitor to the actual OGAds offer.
 */
header(
    'Location: ' . $networkOfferUrl,
    true,
    302
);

exit;
