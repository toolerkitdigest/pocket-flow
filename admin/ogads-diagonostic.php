<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/ogads.php';


/*
|--------------------------------------------------------------------------
| Admin Protection
|--------------------------------------------------------------------------
*/

if (!isLoggedIn()) {
    redirect('../login.php');
}

$userId = (int) ($_SESSION['user_id'] ?? 0);

$user = getUser($pdo, $userId);

if (!$user) {
    redirect('../login.php');
}

if (($user['role'] ?? '') !== 'ADMIN') {
    http_response_code(403);
    exit('Access denied.');
}


/*
|--------------------------------------------------------------------------
| Visitor Request Information
|--------------------------------------------------------------------------
*/

$visitorIp = $_SERVER['REMOTE_ADDR'] ?? '';

$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

$language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en';

$site = $_SERVER['HTTP_HOST'] ?? 'poketflow.com';


/*
|--------------------------------------------------------------------------
| Fetch OGAds Offers
|--------------------------------------------------------------------------
*/

$offers = [];

$error = null;

try {

    $offers = fetchOgadsOffers(
        $visitorIp,
        $userAgent,
        $language,
        $site,
        0,
        50
    );

} catch (Throwable $e) {

    $error = $e->getMessage();
}


/*
|--------------------------------------------------------------------------
| Offer Country Summary
|--------------------------------------------------------------------------
*/

$countrySummary = [];

foreach ($offers as $offer) {

    $country = trim(
        (string) ($offer['country'] ?? 'Unknown')
    );

    if ($country === '') {
        $country = 'Unknown';
    }

    if (!isset($countrySummary[$country])) {
        $countrySummary[$country] = 0;
    }

    $countrySummary[$country]++;
}

ksort($countrySummary);


/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function h(?string $value): string
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>OGAds GEO Diagnostic — PoketFlow</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px;
            background: #080d1a;
            color: #f8fafc;
            font-family:
                Arial,
                Helvetica,
                sans-serif;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            margin-bottom: 8px;
        }

        .subtitle {
            color: #a7b1c2;
            margin-bottom: 30px;
        }

        .grid {
            display: grid;
            grid-template-columns:
                repeat(auto-fit, minmax(250px, 1fr));
            gap: 18px;
            margin-bottom: 25px;
        }

        .card {
            background: #111827;
            border: 1px solid #1f2937;
            border-radius: 14px;
            padding: 20px;
        }

        .label {
            display: block;
            color: #718096;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .value {
            color: #f8fafc;
            font-size: 15px;
            word-break: break-word;
        }

        .big {
            font-size: 28px;
            font-weight: 700;
        }

        .success {
            color: #22d3ee;
        }

        .error {
            background: #35151a;
            border: 1px solid #7f1d1d;
            color: #fecaca;
            padding: 18px;
            border-radius: 12px;
            margin-bottom: 25px;
        }

        .summary {
            margin-bottom: 30px;
        }

        .summary-item {
            display: inline-block;
            background: #151e31;
            border: 1px solid #273449;
            border-radius: 10px;
            padding: 10px 14px;
            margin: 5px;
        }

        .summary-item strong {
            color: #818cf8;
        }

        .table-wrapper {
            overflow-x: auto;
            background: #111827;
            border: 1px solid #1f2937;
            border-radius: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        th,
        td {
            padding: 13px 14px;
            text-align: left;
            border-bottom: 1px solid #1f2937;
            vertical-align: top;
        }

        th {
            background: #151e31;
            color: #a7b1c2;
            font-size: 13px;
        }

        td {
            font-size: 14px;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .offer-name {
            font-weight: 600;
        }

        .muted {
            color: #718096;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 6px;
            background: #1a253b;
            color: #a7b1c2;
            font-size: 12px;
        }

        .back {
            display: inline-block;
            margin-bottom: 25px;
            color: #818cf8;
            text-decoration: none;
        }

        .back:hover {
            color: #22d3ee;
        }

    </style>

</head>

<body>

<div class="container">

    <a
        class="back"
        href="../dashboard.php"
    >
        ← Back to Dashboard
    </a>


    <h1>OGAds GEO Diagnostic</h1>

    <p class="subtitle">
        This page tests the offers returned directly by OGAds
        for the current visitor. It does not synchronize anything
        into the PoketFlow database.
    </p>


    <?php if ($error !== null): ?>

        <div class="error">

            <strong>OGAds API Error</strong>

            <br><br>

            <?= h($error) ?>

        </div>

    <?php endif; ?>


    <!-- REQUEST INFORMATION -->

    <div class="grid">

        <div class="card">

            <span class="label">
                Detected IP
            </span>

            <div class="value">
                <?= h($visitorIp) ?>
            </div>

        </div>


        <div class="card">

            <span class="label">
                Logged-in Account Country
            </span>

            <div class="value">

                <?= h(
                    $user['country'] ?? 'Not set'
                ) ?>

            </div>

        </div>


        <div class="card">

            <span class="label">
                Accept-Language
            </span>

            <div class="value">
                <?= h($language) ?>
            </div>

        </div>


        <div class="card">

            <span class="label">
                Host / Site
            </span>

            <div class="value">
                <?= h($site) ?>
            </div>

        </div>


        <div class="card">

            <span class="label">
                Offers Returned
            </span>

            <div class="value big success">
                <?= count($offers) ?>
            </div>

        </div>

    </div>


    <!-- USER AGENT -->

    <div class="card" style="margin-bottom: 25px;">

        <span class="label">
            User Agent
        </span>

        <div class="value">
            <?= h($userAgent) ?>
        </div>

    </div>


    <!-- COUNTRY SUMMARY -->

    <?php if (!empty($countrySummary)): ?>

        <div class="card summary">

            <h2>
                Offer Country Summary
            </h2>

            <p class="muted">
                This is the country information returned by OGAds
                for the current request.
            </p>


            <?php foreach ($countrySummary as $country => $count): ?>

                <span class="summary-item">

                    <?= h($country) ?>

                    <strong>
                        <?= $count ?>
                    </strong>

                </span>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>


    <!-- OFFER TABLE -->

    <div class="table-wrapper">

        <table>

            <thead>

            <tr>

                <th>
                    #
                </th>

                <th>
                    Offer ID
                </th>

                <th>
                    Offer
                </th>

                <th>
                    Country
                </th>

                <th>
                    Device
                </th>

                <th>
                    Payout
                </th>

                <th>
                    Category
                </th>

                <th>
                    EPC
                </th>

            </tr>

            </thead>

            <tbody>

            <?php if (empty($offers)): ?>

                <tr>

                    <td
                        colspan="8"
                        class="muted"
                    >
                        No offers were returned by OGAds
                        for this request.
                    </td>

                </tr>

            <?php else: ?>

                <?php foreach ($offers as $index => $offer): ?>

                    <tr>

                        <td>
                            <?= $index + 1 ?>
                        </td>


                        <td>

                            <?= h(
                                (string) (
                                    $offer['offerid'] ?? ''
                                )
                            ) ?>

                        </td>


                        <td>

                            <div class="offer-name">

                                <?= h(
                                    $offer['name_short']
                                    ?? $offer['name']
                                    ?? 'Unnamed Offer'
                                ) ?>

                            </div>

                            <?php if (!empty($offer['name'])): ?>

                                <div class="muted">

                                    <?= h(
                                        (string)
                                        $offer['name']
                                    ) ?>

                                </div>

                            <?php endif; ?>

                        </td>


                        <td>

                            <span class="badge">

                                <?= h(
                                    (string) (
                                        $offer['country']
                                        ?? 'Unknown'
                                    )
                                ) ?>

                            </span>

                        </td>


                        <td>

                            <?= h(
                                (string) (
                                    $offer['device']
                                    ?? 'Unknown'
                                )
                            ) ?>

                        </td>


                        <td>

                            $<?= number_format(
                                (float) (
                                    $offer['payout']
                                    ?? 0
                                ),
                                2
                            ) ?>

                        </td>


                        <td>

                            <?= h(
                                getOgadsOfferCategory($offer)
                            ) ?>

                        </td>


                        <td>

                            <?= h(
                                (string) (
                                    $offer['epc']
                                    ?? '—'
                                )
                            ) ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>

</html>
