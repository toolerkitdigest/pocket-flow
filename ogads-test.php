<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/ogads.php';

$offers = [];
$error = null;

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
        $_SERVER['REQUEST_URI'] ?? '/ogads-test.php'
    );

    $offers = fetchOgadsOffers(
        $ip,
        $userAgent,
        $language,
        $site,
        0,
        5
    );

} catch (Throwable $e) {

    $error = $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PoketFlow OGAds API Test</title>
</head>
<body>

<h1>PoketFlow OGAds API Test</h1>

<?php if ($error !== null): ?>

    <h2>API Test Failed</h2>

    <p>
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </p>

<?php else: ?>

    <h2>API Connection Successful</h2>

    <p>
        Offers returned:
        <strong><?= count($offers) ?></strong>
    </p>

    <?php if (empty($offers)): ?>

        <p>No offers were returned for this visitor.</p>

    <?php else: ?>

        <ol>

            <?php foreach ($offers as $offer): ?>

                <li>
                    <strong>
                        <?= htmlspecialchars(
                            (string) ($offer['name_short'] ?? $offer['name'] ?? 'Unnamed offer'),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </strong>

                    <br>

                    Offer ID:
                    <?= htmlspecialchars(
                        (string) ($offer['offerid'] ?? ''),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                    <br>

                    Country:
                    <?= htmlspecialchars(
                        (string) ($offer['country'] ?? ''),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                    <br>

                    Device:
                    <?= htmlspecialchars(
                        (string) ($offer['device'] ?? ''),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </li>

                <br>

            <?php endforeach; ?>

        </ol>

    <?php endif; ?>

<?php endif; ?>

</body>
</html>
