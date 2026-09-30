<?php

declare(strict_types=1);

function getOgadsConfig(): array
{
    $configPath = '/home/u541027683/private/poketflow-config.php';

    if (!file_exists($configPath)) {
        throw new RuntimeException(
            'PoketFlow private configuration file was not found.'
        );
    }

    $config = require $configPath;

    if (
        !is_array($config) ||
        empty($config['ogads']['api_key']) ||
        empty($config['ogads']['endpoint'])
    ) {
        throw new RuntimeException(
            'OGAds configuration is missing or invalid.'
        );
    }

    return $config['ogads'];
}


function fetchOgadsOffers(
    string $ip,
    string $userAgent,
    string $language,
    string $site,
    int $ctype = 0,
    int $max = 50
): array {
    $config = getOgadsConfig();

    $params = [
        'ip' => $ip,
        'user_agent' => $userAgent,
        'lang' => $language,
        'site' => $site,
        'ctype' => $ctype,
        'max' => $max,
    ];

    $url = $config['endpoint'] . '?' . http_build_query($params);

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $config['api_key'],
            'Accept: application/json',
        ],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        $error = curl_error($ch);

        curl_close($ch);

        throw new RuntimeException(
            'OGAds API request failed: ' . $error
        );
    }

    $httpCode = curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );

    curl_close($ch);

    if ($httpCode < 200 || $httpCode >= 300) {
        throw new RuntimeException(
            'OGAds API returned HTTP status ' . $httpCode . '.'
        );
    }

    try {
        $data = json_decode(
            $response,
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    } catch (JsonException $e) {
        throw new RuntimeException(
            'OGAds API returned invalid JSON.'
        );
    }

    if (
        !isset($data['success']) ||
        $data['success'] !== true
    ) {
        $error = $data['error'] ?? 'Unknown OGAds API error.';

        throw new RuntimeException(
            'OGAds API error: ' . $error
        );
    }

    return $data['offers'] ?? [];
}
