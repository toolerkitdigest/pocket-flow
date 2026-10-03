<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| PoketFlow Hero Offers
|--------------------------------------------------------------------------
| Loads real offers from the connected offer network.
| These are displayed in the homepage hero as an animated offer rail.
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/ogads.php';


/*
|--------------------------------------------------------------------------
| Fetch visitor-specific offers
|--------------------------------------------------------------------------
*/

$heroOffers = [];

try {

    $heroIp = $_SERVER['REMOTE_ADDR'] ?? '';

    $heroUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $heroLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';

    $heroScheme =
        (
            !empty($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] !== 'off'
        )
        ? 'https'
        : 'http';

    $heroHost =
        $_SERVER['HTTP_HOST']
        ?? 'poketflow.com';

    $heroSite =
        $heroScheme
        . '://'
        . $heroHost
        . '/';


    $rawHeroOffers = fetchOgadsOffers(
        $heroIp,
        $heroUserAgent,
        $heroLanguage,
        $heroSite,
        0,
        20
    );


    /*
    |--------------------------------------------------------------------------
    | Build safe display offers
    |--------------------------------------------------------------------------
    */

    foreach ($rawHeroOffers as $offer) {

        if (!is_array($offer)) {
            continue;
        }


        $externalId =
            (string) (
                $offer['id']
                ?? $offer['offer_id']
                ?? $offer['offerId']
                ?? ''
            );


        $title =
            trim(
                cleanOgadsText(
                    (string) (
                        $offer['name']
                        ?? $offer['name_short']
                        ?? $offer['title']
                        ?? 'Available Offer'
                    )
                )
            );


        $description =
            trim(
                cleanOgadsText(
                    (string) (
                        $offer['description']
                        ?? $offer['adcopy']
                        ?? $offer['short_description']
                        ?? ''
                    )
                )
            );


        $networkPayout =
            (float) (
                $offer['payout']
                ?? $offer['payout_amount']
                ?? $offer['amount']
                ?? 0
            );


        $offerUrl =
            trim(
                (string) (
                    $offer['url']
                    ?? $offer['link']
                    ?? $offer['click_url']
                    ?? ''
                )
            );


        $image =
            trim(
                (string) (
                    $offer['image']
                    ?? $offer['image_url']
                    ?? $offer['thumbnail']
                    ?? ''
                )
            );


        if (
            $externalId === ''
            || $title === ''
            || $networkPayout <= 0
            || $offerUrl === ''
        ) {
            continue;
        }


        /*
        |--------------------------------------------------------------------------
        | Safety filter
        |--------------------------------------------------------------------------
        */

        if (!isOgadsOfferSafe($pdo, $offer)) {
            continue;
        }


        /*
        |--------------------------------------------------------------------------
        | Calculate actual PoketFlow reward
        |--------------------------------------------------------------------------
        */

        $rewardData =
            calculateOgadsReward(
                $pdo,
                $networkPayout
            );


        $workerReward =
            (float) (
                $rewardData['worker_reward']
                ?? $rewardData['workerReward']
                ?? 0
            );


        if ($workerReward <= 0) {
            continue;
        }


        /*
        |--------------------------------------------------------------------------
        | Category
        |--------------------------------------------------------------------------
        */

        $category =
            getOgadsOfferCategory($offer);


        /*
        |--------------------------------------------------------------------------
        | Country / device
        |--------------------------------------------------------------------------
        */

        $country =
            trim(
                (string) (
                    $offer['country']
                    ?? $offer['country_code']
                    ?? ''
                )
            );


        $device =
            trim(
                (string) (
                    $offer['device']
                    ?? $offer['device_type']
                    ?? ''
                )
            );


        $heroOffers[] = [

            'id' => $externalId,

            'title' => $title,

            'description' => $description,

            'category' => $category,

            'network_payout' => $networkPayout,

            'worker_reward' => $workerReward,

            'image' => $image,

            'url' => $offerUrl,

            'country' => $country,

            'device' => $device,

        ];


        /*
        |--------------------------------------------------------------------------
        | We only need three hero cards.
        |--------------------------------------------------------------------------
        */

        if (count($heroOffers) >= 3) {
            break;
        }

    }

} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | Never break the homepage because the offer API is unavailable.
    |--------------------------------------------------------------------------
    */

    $heroOffers = [];

}


/*
|--------------------------------------------------------------------------
| Helper: offer destination
|--------------------------------------------------------------------------
*/

function heroOfferLink(string $offerId): string
{
    if (function_exists('isLoggedIn') && isLoggedIn()) {

        return 'start-offer.php?offer_id='
            . rawurlencode($offerId);

    }

    return 'register.php';

}


/*
|--------------------------------------------------------------------------
| Helper: short description
|--------------------------------------------------------------------------
*/

function heroOfferDescription(string $description): string
{
    $description =
        trim(
            preg_replace(
                '/\s+/',
                ' ',
                $description
            ) ?? ''
        );


    if ($description === '') {

        return 'Complete the listed requirements and earn the displayed reward.';

    }


    if (mb_strlen($description) > 105) {

        return
            mb_substr(
                $description,
                0,
                102
            )
            . '...';

    }


    return $description;

}


/*
|--------------------------------------------------------------------------
| Helper: money
|--------------------------------------------------------------------------
*/

function heroOfferMoney(float $amount): string
{
    return '$' . number_format(
        $amount,
        2
    );

}


/*
|--------------------------------------------------------------------------
| Helper: image fallback letter
|--------------------------------------------------------------------------
*/

function heroOfferInitial(string $title): string
{
    $title = trim($title);

    if ($title === '') {
        return 'P';
    }

    return mb_strtoupper(
        mb_substr(
            $title,
            0,
            1
        )
    );

}

?>


<section class="pf-hero-offers">

    <div class="home-container">


        <div class="pf-hero-offers-heading">

            <div>

                <span class="home-kicker">
                    LIVE OPPORTUNITIES
                </span>

                <h2>
                    Real offers. Real rewards.
                </h2>

                <p>
                    Explore some of the opportunities currently
                    available through PoketFlow.
                </p>

            </div>

            <span class="pf-live-indicator">
                <i></i>
                LIVE
            </span>

        </div>


        <?php if (!empty($heroOffers)): ?>


            <div
                class="pf-hero-offer-window"
                aria-label="Live PoketFlow offers"
            >

                <div class="pf-hero-offer-track">


                    <?php foreach ($heroOffers as $offer): ?>

                        <article class="pf-hero-offer-card">


                            <!-- Offer image -->

                            <div class="pf-hero-offer-image">

                                <?php if ($offer['image'] !== ''): ?>

                                    <img
                                        src="<?= htmlspecialchars(
                                            $offer['image'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        alt="<?= htmlspecialchars(
                                            $offer['title'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        loading="eager"
                                    >

                                <?php else: ?>

                                    <span>
                                        <?= htmlspecialchars(
                                            heroOfferInitial(
                                                $offer['title']
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>

                                <?php endif; ?>


                                <div class="pf-hero-offer-image-overlay"></div>


                                <span class="pf-hero-offer-category">

                                    <?= htmlspecialchars(
                                        $offer['category'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>

                            </div>


                            <!-- Card content -->

                            <div class="pf-hero-offer-content">


                                <div class="pf-hero-offer-topline">

                                    <span>
                                        FEATURED OPPORTUNITY
                                    </span>

                                    <b>
                                        ●
                                    </b>

                                </div>


                                <h3>

                                    <?= htmlspecialchars(
                                        $offer['title'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </h3>


                                <p>

                                    <?= htmlspecialchars(
                                        heroOfferDescription(
                                            $offer['description']
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </p>


                                <div class="pf-hero-offer-meta">

                                    <?php if ($offer['country'] !== ''): ?>

                                        <span>
                                            ◉
                                            <?= htmlspecialchars(
                                                $offer['country'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>

                                    <?php endif; ?>


                                    <?php if ($offer['device'] !== ''): ?>

                                        <span>
                                            ◇
                                            <?= htmlspecialchars(
                                                $offer['device'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>

                                    <?php endif; ?>

                                </div>


                                <div class="pf-hero-offer-bottom">


                                    <div class="pf-hero-reward">

                                        <small>
                                            YOU CAN EARN
                                        </small>

                                        <strong>
                                            +<?= htmlspecialchars(
                                                heroOfferMoney(
                                                    $offer['worker_reward']
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </strong>

                                    </div>


                                    <a
                                        class="pf-hero-offer-button"
                                        href="<?= htmlspecialchars(
                                            heroOfferLink(
                                                $offer['id']
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                    >

                                        Explore

                                        <span>
                                            →
                                        </span>

                                    </a>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>


                </div>

            </div>


            <div class="pf-hero-carousel-note">

                <span>
                    ←
                </span>

                More opportunities are available inside PoketFlow

                <span>
                    →
                </span>

            </div>


        <?php else: ?>


            <div class="pf-hero-offers-empty">

                <div>
                    ◎
                </div>

                <strong>
                    New opportunities are arriving
                </strong>

                <p>
                    Create your free account and check your
                    earning area for available offers.
                </p>

                <a
                    class="home-btn home-btn-primary"
                    href="register.php"
                >
                    Create Free Account →
                </a>

            </div>


        <?php endif; ?>


    </div>

</section>
