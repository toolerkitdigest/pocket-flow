<?php

declare(strict_types=1);

/**
 * =========================================================
 * POKETFLOW — FEATURED OFFERS
 * =========================================================
 *
 * Public homepage component.
 *
 * This file:
 * - Fetches visitor-specific OGAds offers
 * - Applies PoketFlow safety filters
 * - Calculates the worker reward
 * - Displays a limited number of featured offers
 *
 * It does NOT expose the raw OGAds participation URL.
 *
 * Visitor:
 *     register.php
 *
 * Logged-in user:
 *     start-offer.php
 * =========================================================
 */


// =========================================================
// REQUIRED FILES
// =========================================================

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/ogads.php';


// =========================================================
// CONFIGURATION
// =========================================================

$featuredOffers = [];

$featuredOffersError = null;

$featuredOfferLimit = 6;


// =========================================================
// FETCH LIVE VISITOR-SPECIFIC OFFERS
// =========================================================

try {

    /*
     * Visitor information used by OGAds
     * to return relevant inventory.
     */

    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';


    /*
     * Determine current request scheme.
     */

    $scheme = (
        !empty($_SERVER['HTTPS']) &&
        $_SERVER['HTTPS'] !== 'off'
    ) ? 'https' : 'http';


    /*
     * Build the current homepage URL.
     *
     * We intentionally use the homepage rather
     * than offers.php because this is a public
     * featured-offers request.
     */

    $site = $scheme . '://' . (
        $_SERVER['HTTP_HOST'] ?? 'poketflow.com'
    ) . '/';


    // =====================================================
    // ASK OGADS FOR LIVE INVENTORY
    // =====================================================

    $ogadsOffers = fetchOgadsOffers(
        $ip,
        $userAgent,
        $language,
        $site,
        0,
        50
    );


    // =====================================================
    // PROCESS OFFERS
    // =====================================================

    foreach ($ogadsOffers as $offer) {

        // -------------------------------------------------
        // External OGAds offer ID
        // -------------------------------------------------

        $externalOfferId = trim(
            (string) ($offer['offerid'] ?? '')
        );


        if ($externalOfferId === '') {
            continue;
        }


        // -------------------------------------------------
        // Clean basic information
        // -------------------------------------------------

        $title = cleanOgadsText(
            $offer['name_short']
                ?? $offer['name']
                ?? 'Available Offer'
        );


        $description = cleanOgadsText(
            $offer['description'] ?? ''
        );


        $category = getOgadsOfferCategory(
            $offer
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


        // -------------------------------------------------
        // Basic validation
        // -------------------------------------------------

        if ($networkPayout <= 0) {
            continue;
        }


        if ($networkOfferUrl === '') {
            continue;
        }


        // -------------------------------------------------
        // Safety filtering
        // -------------------------------------------------

        if (!isOgadsOfferSafe(
            $pdo,
            $offer
        )) {
            continue;
        }


        // -------------------------------------------------
        // Calculate worker reward
        // -------------------------------------------------

        $rewards = calculateOgadsReward(
            $pdo,
            $networkPayout
        );


        // -------------------------------------------------
        // Build featured offer
        // -------------------------------------------------

        $featuredOffers[] = [

            'id' => $externalOfferId,

            'external_offer_id' => $externalOfferId,

            'title' => $title,

            'description' => $description,

            'category' => $category,

            'image_url' => $imageUrl,

            'network_payout' => $networkPayout,

            'worker_reward' => $rewards['worker_reward'],

        ];


        // -------------------------------------------------
        // Stop once we have enough featured offers
        // -------------------------------------------------

        if (
            count($featuredOffers)
            >= $featuredOfferLimit
        ) {
            break;
        }
    }


} catch (Throwable $e) {

    /*
     * Do not expose API errors to visitors.
     */

    $featuredOffersError =
        'Featured offers are temporarily unavailable.';

    $featuredOffers = [];
}


// =========================================================
// HELPER: SHORT DESCRIPTION
// =========================================================

function getFeaturedOfferDescription(
    string $description
): string {

    $description = trim($description);


    /*
     * Remove technical OGAds metadata.
     */

    $technicalMarkers = [

        '/\bConversion\s*:/i',

        '/\bofferwall_description\s*=/i',

        '/\bofferwall_instructions\s*=/i',

        '/\bofferwall_category\s*=/i',

        '/\btracking_type\s*=/i',

        '/\bofferwall_/i',

    ];


    foreach ($technicalMarkers as $pattern) {

        $cleaned = preg_split(
            $pattern,
            $description,
            2
        );


        if (
            is_array($cleaned) &&
            isset($cleaned[0])
        ) {

            $description = trim(
                $cleaned[0]
            );
        }
    }


    /*
     * Normalize whitespace.
     */

    $description = preg_replace(
        '/\s+/u',
        ' ',
        $description
    );


    $description = trim(
        (string) $description
    );


    /*
     * Fallback description.
     */

    if ($description === '') {

        return 'Complete this offer to earn your reward.';
    }


    /*
     * Keep homepage cards compact.
     */

    if (
        function_exists('mb_strlen') &&
        mb_strlen($description) > 115
    ) {

        $description = mb_substr(
            $description,
            0,
            115
        );


        $description = rtrim(
            $description,
            " \t\n\r\0\x0B.,;:-"
        );


        $description .= '...';
    }


    return $description;
}


// =========================================================
// HELPER: OFFER ICON
// =========================================================

function getFeaturedOfferIcon(
    string $category
): string {

    $category = strtolower(
        trim($category)
    );


    return match (true) {

        str_contains($category, 'app'),
        str_contains($category, 'install')
            => '◎',

        str_contains($category, 'survey')
            => '▤',

        str_contains($category, 'signup')
            => '◇',

        default
            => '◆',
    };
}


// =========================================================
// HELPER: OFFER CATEGORY LABEL
// =========================================================

function getFeaturedOfferCategory(
    string $category
): string {

    $category = trim($category);


    if ($category === '') {
        return 'Special Offer';
    }


    return $category;
}

?>


<!-- =====================================================
     FEATURED OFFERS SECTION
====================================================== -->

<section
    class="home-section featured-offers-section"
    id="featured-offers"
>


    <!-- =================================================
         SECTION HEADER
    ================================================== -->

    <div class="home-section-heading">

        <div>

            <span class="home-kicker">
                LIVE OPPORTUNITIES
            </span>


            <h2>
                Featured Offers
            </h2>


            <p>
                Discover selected opportunities available
                for visitors in your location.
            </p>

        </div>


        <a
            href="offers.php"
            class="home-section-link"
        >
            View all offers
            <span>→</span>
        </a>

    </div>


    <!-- =================================================
         ERROR STATE
    ================================================== -->

    <?php if ($featuredOffersError !== null): ?>

        <div class="featured-offers-status">

            <div class="featured-status-icon">
                ◷
            </div>


            <div>

                <strong>
                    Featured offers are temporarily unavailable
                </strong>


                <p>
                    Please check back shortly for available
                    earning opportunities.
                </p>

            </div>

        </div>


    <?php elseif (empty($featuredOffers)): ?>


        <!-- =============================================
             EMPTY STATE
        ============================================== -->

        <div class="featured-offers-status">

            <div class="featured-status-icon">
                ◷
            </div>


            <div>

                <strong>
                    New opportunities are coming
                </strong>


                <p>
                    There are no featured offers available
                    for your location right now.
                </p>

            </div>

        </div>


    <?php else: ?>


        <!-- =============================================
             FEATURED OFFER GRID
        ============================================== -->

        <div class="featured-offers-grid">


            <?php foreach ($featuredOffers as $offer): ?>


                <?php

                $category =
                    getFeaturedOfferCategory(
                        (string) (
                            $offer['category'] ?? ''
                        )
                    );


                $title = trim(
                    (string) (
                        $offer['title']
                        ?? 'Available Offer'
                    )
                );


                $description =
                    getFeaturedOfferDescription(
                        (string) (
                            $offer['description']
                            ?? ''
                        )
                    );


                $reward = (float) (
                    $offer['worker_reward']
                    ?? 0
                );


                $imageUrl = trim(
                    (string) (
                        $offer['image_url']
                        ?? ''
                    )
                );


                $icon =
                    getFeaturedOfferIcon(
                        $category
                    );


                /*
                 * IMPORTANT:
                 *
                 * We deliberately do NOT use
                 * $offer['network_offer_url']
                 * as the visitor-facing link.
                 *
                 * The offer must pass through
                 * PoketFlow's account/start flow.
                 */

                if (isLoggedIn()) {

                    $offerUrl =
                        'start-offer.php?offer_id='
                        . rawurlencode(
                            (string) $offer[
                                'external_offer_id'
                            ]
                        );

                } else {

                    $offerUrl =
                        'register.php';

                }

                ?>


                <article class="featured-offer-card">


                    <!-- =================================
                         OFFER IMAGE
                    ================================== -->

                    <div class="featured-offer-image">


                        <?php if ($imageUrl !== ''): ?>

                            <img
                                src="<?= e($imageUrl) ?>"
                                alt=""
                                loading="lazy"
                            >


                        <?php else: ?>

                            <span>
                                <?= e($icon) ?>
                            </span>

                        <?php endif; ?>


                        <span class="featured-offer-category">
                            <?= e($category) ?>
                        </span>

                    </div>


                    <!-- =================================
                         OFFER BODY
                    ================================== -->

                    <div class="featured-offer-body">


                        <h3>
                            <?= e($title) ?>
                        </h3>


                        <p>
                            <?= e($description) ?>
                        </p>


                    </div>


                    <!-- =================================
                         OFFER FOOTER
                    ================================== -->

                    <div class="featured-offer-footer">


                        <div class="featured-reward">

                            <small>
                                Earn
                            </small>


                            <strong>
                                $<?= number_format(
                                    $reward,
                                    2
                                ) ?>
                            </strong>

                        </div>


                        <a
                            href="<?= e($offerUrl) ?>"
                            class="featured-offer-button"
                        >

                            <?= isLoggedIn()
                                ? 'Start Offer'
                                : 'Get Started'
                            ?>

                            <span>
                                →
                            </span>

                        </a>


                    </div>


                </article>


            <?php endforeach; ?>


        </div>


        <!-- =============================================
             BOTTOM LINK
        ============================================== -->

        <div class="featured-offers-bottom">

            <p>
                More opportunities may be available
                after you create your account.
            </p>


            <a href="offers.php">
                Explore all available offers →
            </a>

        </div>


    <?php endif; ?>


</section>
