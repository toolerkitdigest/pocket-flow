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

/**
 * Check whether a campaign is safe and eligible to be displayed.
 *
 * This is a HARD safety gate.
 *
 * An offer must:
 * 1. Be ACTIVE
 * 2. Be APPROVED
 * 3. Not match any active safety filter
 *
 * Safety filtering checks:
 * - title
 * - description
 * - category
 * - instructions
 * - network offer URL
 */
function isCampaignAllowed(PDO $pdo, array $campaign): bool
{
    /*
    |--------------------------------------------------------------------------
    | 1. Campaign status
    |--------------------------------------------------------------------------
    */

    $campaignStatus = strtoupper(
        trim((string) ($campaign['status'] ?? ''))
    );

    if ($campaignStatus !== 'ACTIVE') {
        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | 2. Approval status
    |--------------------------------------------------------------------------
    |
    | CPA campaigns should not become publicly visible simply because
    | their status is ACTIVE.
    |
    */

    $approvalStatus = strtoupper(
        trim((string) ($campaign['approval_status'] ?? ''))
    );

    if ($approvalStatus !== 'APPROVED') {
        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | 3. Build searchable offer text
    |--------------------------------------------------------------------------
    |
    | We deliberately inspect several fields.
    |
    | A dangerous offer may not reveal its category in the title.
    | The description, instructions or destination URL may reveal it.
    |
    */

    $searchableFields = [
        'title',
        'description',
        'category',
        'instructions',
        'network_offer_url',
    ];

    $searchableText = '';

    foreach ($searchableFields as $field) {

        $value = trim(
            (string) ($campaign[$field] ?? '')
        );

        if ($value !== '') {
            $searchableText .= ' ' . $value;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | 4. Normalize the text
    |--------------------------------------------------------------------------
    |
    | This helps us catch variations such as:
    |
    | Sports-Betting
    | sports_betting
    | sports betting
    | SPORTS BETTING
    |
    */

    $searchableText = strtolower($searchableText);

    $searchableText = str_replace(
        [
            '-',
            '_',
            '/',
            '\\',
            '.',
            ',',
            ':',
            ';',
            '|',
            '(',
            ')',
            '[',
            ']',
            '{',
            '}',
        ],
        ' ',
        $searchableText
    );

    $searchableText = preg_replace(
        '/\s+/u',
        ' ',
        $searchableText
    );

    $searchableText = trim(
        (string) $searchableText
    );


    /*
    |--------------------------------------------------------------------------
    | 5. Load active safety filters
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->query(
        'SELECT
            keyword,
            category,
            action,
            reason
         FROM offer_filters
         WHERE active = 1
         ORDER BY
            CHAR_LENGTH(keyword) DESC,
            id ASC'
    );

    $filters = $stmt->fetchAll(PDO::FETCH_ASSOC);


    /*
    |--------------------------------------------------------------------------
    | 6. Check every active filter
    |--------------------------------------------------------------------------
    */

    foreach ($filters as $filter) {

        $action = strtoupper(
            trim((string) ($filter['action'] ?? ''))
        );

        /*
        We only enforce REJECT filters here.
        Other future actions can be handled separately.
        */

        if ($action !== 'REJECT') {
            continue;
        }

        $keyword = strtolower(
            trim((string) ($filter['keyword'] ?? ''))
        );

        if ($keyword === '') {
            continue;
        }


        /*
        Normalize the filter keyword in exactly the same way
        as the campaign text.
        */

        $keyword = str_replace(
            [
                '-',
                '_',
                '/',
                '\\',
                '.',
                ',',
                ':',
                ';',
                '|',
                '(',
                ')',
                '[',
                ']',
                '{',
                '}',
            ],
            ' ',
            $keyword
        );

        $keyword = preg_replace(
            '/\s+/u',
            ' ',
            $keyword
        );

        $keyword = trim(
            (string) $keyword
        );


        /*
        |--------------------------------------------------------------------------
        | 7. Reject immediately when a dangerous keyword is found
        |--------------------------------------------------------------------------
        */

        if (
            $keyword !== ''
            && str_contains(
                $searchableText,
                $keyword
            )
        ) {
            return false;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | 8. Offer passed every safety check
    |--------------------------------------------------------------------------
    */

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
