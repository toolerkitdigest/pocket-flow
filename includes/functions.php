<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| PoketFlow Common Functions
|--------------------------------------------------------------------------
| Shared helper and business-logic functions used throughout PoketFlow.
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| HTML Escaping
|--------------------------------------------------------------------------
*/

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}


/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}


/*
|--------------------------------------------------------------------------
| Generate Unique Referral Code
|--------------------------------------------------------------------------
*/

function generateReferralCode(PDO $pdo, string $name): string
{
    $base = strtoupper(
        preg_replace('/[^A-Za-z0-9]/', '', $name)
    );

    $base = substr($base ?: 'USER', 0, 8);

    do {
        $code = $base . strtoupper(
            substr(bin2hex(random_bytes(4)), 0, 6)
        );

        $stmt = $pdo->prepare(
            'SELECT id
             FROM users
             WHERE referral_code = ?
             LIMIT 1'
        );

        $stmt->execute([$code]);

    } while ($stmt->fetch());

    return $code;
}


/*
|--------------------------------------------------------------------------
| Get System Setting
|--------------------------------------------------------------------------
| Reads a value from the settings table.
|
| Example:
| getSetting($pdo, 'minimum_withdrawal', '5.00');
|--------------------------------------------------------------------------
*/

function getSetting(
    PDO $pdo,
    string $key,
    ?string $default = null
): ?string {
    $stmt = $pdo->prepare(
        'SELECT setting_value
         FROM settings
         WHERE setting_key = ?
         LIMIT 1'
    );

    $stmt->execute([$key]);

    $value = $stmt->fetchColumn();

    if ($value === false) {
        return $default;
    }

    return (string) $value;
}


/*
|--------------------------------------------------------------------------
| Get User
|--------------------------------------------------------------------------
*/

function getUser(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT
            id,
            role,
            name,
            email,
            country,
            status,
            referral_code,
            referred_by,
            created_at
         FROM users
         WHERE id = ?
         LIMIT 1'
    );

    $stmt->execute([$userId]);

    $user = $stmt->fetch();

    return $user ?: null;
}


/*
|--------------------------------------------------------------------------
| Get User Wallet Balance
|--------------------------------------------------------------------------
| The wallet balance is calculated from the wallet ledger.
|
| Completed positive transactions increase the balance.
| Completed withdrawals reduce the balance.
|
| We use the transaction type and amount rather than storing a
| separate balance column in users.
|--------------------------------------------------------------------------
*/

function getUserBalance(PDO $pdo, int $userId): float
{
    $stmt = $pdo->prepare(
        'SELECT COALESCE(SUM(amount), 0)
         FROM wallet_transactions
         WHERE user_id = ?
           AND status = "COMPLETED"'
    );

    $stmt->execute([$userId]);

    return (float) $stmt->fetchColumn();
}


/*
|--------------------------------------------------------------------------
| Get User Pending Balance
|--------------------------------------------------------------------------
| Pending wallet transactions are kept separate from the available
| completed balance.
|--------------------------------------------------------------------------
*/

function getUserPendingBalance(PDO $pdo, int $userId): float
{
    $stmt = $pdo->prepare(
        'SELECT COALESCE(SUM(amount), 0)
         FROM wallet_transactions
         WHERE user_id = ?
           AND status = "PENDING"'
    );

    $stmt->execute([$userId]);

    return (float) $stmt->fetchColumn();
}


/*
|--------------------------------------------------------------------------
| Get User Total Earned
|--------------------------------------------------------------------------
| Total rewards from approved conversions.
|--------------------------------------------------------------------------
*/

function getUserTotalEarned(PDO $pdo, int $userId): float
{
    $stmt = $pdo->prepare(
        'SELECT COALESCE(SUM(worker_reward), 0)
         FROM conversions
         WHERE worker_id = ?
           AND status = "APPROVED"'
    );

    $stmt->execute([$userId]);

    return (float) $stmt->fetchColumn();
}


/*
|--------------------------------------------------------------------------
| Check Country Eligibility
|--------------------------------------------------------------------------
| campaigns.countries is stored as text.
|
| We support common formats such as:
|
| US,CA,GB
| US, CA, GB
| USA, Canada
| Worldwide
| ALL
|
| Empty countries means no country restriction.
|--------------------------------------------------------------------------
*/

function isCountryEligible(
    ?string $campaignCountries,
    ?string $userCountry
): bool {
    $campaignCountries = trim((string) $campaignCountries);
    $userCountry = trim((string) $userCountry);

    /*
    | No restriction.
    */
    if ($campaignCountries === '') {
        return true;
    }

    $countries = preg_split(
        '/[,|]+/',
        $campaignCountries
    );

    if (!$countries) {
        return true;
    }

    $userCountry = strtolower($userCountry);

    foreach ($countries as $country) {

        $country = strtolower(trim($country));

        if ($country === '') {
            continue;
        }

        /*
        | Global campaigns.
        */
        if (
            in_array(
                $country,
                ['all', 'worldwide', 'global', 'any'],
                true
            )
        ) {
            return true;
        }

        /*
        | Direct country-name/code match.
        */
        if ($country === $userCountry) {
            return true;
        }

        /*
        | Common country-code mappings.
        */
        $aliases = [
            'us' => ['usa', 'united states', 'united states of america'],
            'usa' => ['us', 'united states', 'united states of america'],

            'gb' => ['uk', 'united kingdom', 'great britain'],
            'uk' => ['gb', 'united kingdom', 'great britain'],

            'ng' => ['nigeria'],
            'ca' => ['canada'],
            'au' => ['australia'],
            'de' => ['germany'],
            'fr' => ['france'],
            'it' => ['italy'],
            'es' => ['spain'],
            'za' => ['south africa'],
            'in' => ['india'],
        ];

        if (
            isset($aliases[$country]) &&
            in_array($userCountry, $aliases[$country], true)
        ) {
            return true;
        }
    }

    return false;
}


/*
|--------------------------------------------------------------------------
| Check Campaign Offer Filter
|--------------------------------------------------------------------------
| Uses the offer_filters table instead of hard-coding prohibited
| categories into offers.php.
|--------------------------------------------------------------------------
*/

function isCampaignAllowed(
    PDO $pdo,
    array $campaign
): bool {
    $searchText = strtolower(
        trim(
            implode(
                ' ',
                [
                    (string) ($campaign['title'] ?? ''),
                    (string) ($campaign['description'] ?? ''),
                    (string) ($campaign['category'] ?? ''),
                    (string) ($campaign['instructions'] ?? ''),
                ]
            )
        )
    );

    if ($searchText === '') {
        return true;
    }

    $stmt = $pdo->query(
        'SELECT keyword
         FROM offer_filters
         WHERE action = "REJECT"
           AND active = 1'
    );

    $filters = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($filters as $keyword) {

        $keyword = strtolower(trim((string) $keyword));

        if ($keyword === '') {
            continue;
        }

        if (str_contains($searchText, $keyword)) {
            return false;
        }
    }

    return true;
}


/*
|--------------------------------------------------------------------------
| Get Active Campaigns
|--------------------------------------------------------------------------
| Returns worker-visible CPA/direct advertiser campaigns.
|
| IMPORTANT:
| network_payout is intentionally selected here only when needed
| internally. Worker-facing pages must not display it.
|--------------------------------------------------------------------------
*/

function getActiveCampaigns(
    PDO $pdo,
    ?string $country = null
): array {
    $stmt = $pdo->query(
        'SELECT
            id,
            source_type,
            title,
            description,
            category,
            instructions,
            worker_reward,
            countries,
            devices,
            os,
            image_url,
            incentive_allowed,
            status,
            approval_status,
            start_at,
            end_at
         FROM campaigns
         WHERE status = "ACTIVE"
           AND approval_status = "APPROVED"
           AND (start_at IS NULL OR start_at <= NOW())
           AND (end_at IS NULL OR end_at >= NOW())
         ORDER BY id DESC'
    );

    $campaigns = $stmt->fetchAll();

    $results = [];

    foreach ($campaigns as $campaign) {

        /*
        | Country filtering.
        */
        if (
            $country !== null &&
            !isCountryEligible(
                $campaign['countries'] ?? '',
                $country
            )
        ) {
            continue;
        }

        /*
        | Offer safety filtering.
        */
        if (!isCampaignAllowed($pdo, $campaign)) {
            continue;
        }

        $results[] = $campaign;
    }

    return $results;
}


/*
|--------------------------------------------------------------------------
| Get Single Campaign
|--------------------------------------------------------------------------
*/

function getCampaign(
    PDO $pdo,
    int $campaignId
): ?array {
    $stmt = $pdo->prepare(
        'SELECT
            id,
            source_type,
            image_url,
            advertiser_id,
            network_id,
            external_offer_id,
            network_offer_url,
            title,
            description,
            category,
            instructions,
            worker_reward,
            countries,
            devices,
            os,
            incentive_allowed,
            status,
            approval_status,
            start_at,
            end_at
         FROM campaigns
         WHERE id = ?
         LIMIT 1'
    );

    $stmt->execute([$campaignId]);

    $campaign = $stmt->fetch();

    return $campaign ?: null;
}


/*
|--------------------------------------------------------------------------
| Validate Campaign For Worker
|--------------------------------------------------------------------------
| Performs the security checks that should happen before an offer
| can be started.
|--------------------------------------------------------------------------
*/

function canStartCampaign(
    PDO $pdo,
    array $campaign,
    array $user
): bool {
    /*
    | Campaign must be active.
    */
    if (($campaign['status'] ?? '') !== 'ACTIVE') {
        return false;
    }

    /*
    | Campaign must be approved.
    */
    if (($campaign['approval_status'] ?? '') !== 'APPROVED') {
        return false;
    }

    /*
    | Start/end date.
    */
    if (
        !empty($campaign['start_at']) &&
        strtotime((string) $campaign['start_at']) > time()
    ) {
        return false;
    }

    if (
        !empty($campaign['end_at']) &&
        strtotime((string) $campaign['end_at']) < time()
    ) {
        return false;
    }

    /*
    | Country restriction.
    */
    if (
        !isCountryEligible(
            $campaign['countries'] ?? '',
            $user['country'] ?? ''
        )
    ) {
        return false;
    }

    /*
    | Offer safety filter.
    */
    if (!isCampaignAllowed($pdo, $campaign)) {
        return false;
    }

    return true;
}


/*
|--------------------------------------------------------------------------
| Generate Unique Tracking ID
|--------------------------------------------------------------------------
*/

function generateTrackingId(): string
{
    return bin2hex(random_bytes(16));
}


/*
|--------------------------------------------------------------------------
| Create Campaign Click
|--------------------------------------------------------------------------
| Records the worker starting an offer.
|--------------------------------------------------------------------------
*/

function createCampaignClick(
    PDO $pdo,
    int $campaignId,
    int $workerId,
    ?string $ipAddress = null,
    ?string $userAgent = null
): string {
    $trackingId = generateTrackingId();

    $ipHash = null;

    if ($ipAddress !== null && $ipAddress !== '') {
        $ipHash = hash(
            'sha256',
            $ipAddress
        );
    }

    $stmt = $pdo->prepare(
        'INSERT INTO campaign_clicks (
            campaign_id,
            worker_id,
            tracking_id,
            ip_hash,
            user_agent
        )
        VALUES (?, ?, ?, ?, ?)'
    );

    $stmt->execute([
        $campaignId,
        $workerId,
        $trackingId,
        $ipHash,
        $userAgent,
    ]);

    return $trackingId;
}
