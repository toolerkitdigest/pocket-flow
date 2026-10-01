<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/admin-auth.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Conversions';

$status = strtoupper(trim((string) ($_GET['status'] ?? '')));
$search = trim((string) ($_GET['search'] ?? ''));

$allowedStatuses = [
    'PENDING',
    'APPROVED',
    'REJECTED',
    'REVERSED',
];

if ($status !== '' && !in_array($status, $allowedStatuses, true)) {
    $status = '';
}

/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

$perPage = 20;

$page = max(1, (int) ($_GET['page'] ?? 1));

$offset = ($page - 1) * $perPage;

/*
|--------------------------------------------------------------------------
| Build WHERE clause
|--------------------------------------------------------------------------
*/

$where = [];
$params = [];

if ($status !== '') {
    $where[] = 'c.status = ?';
    $params[] = $status;
}

if ($search !== '') {
    $where[] = '(
        CAST(c.id AS CHAR) LIKE ?
        OR u.name LIKE ?
        OR u.email LIKE ?
        OR cp.title LIKE ?
        OR cp.external_offer_id LIKE ?
        OR c.external_transaction_id LIKE ?
    )';

    $searchTerm = '%' . $search . '%';

    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereSql = '';

if ($where) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

/*
|--------------------------------------------------------------------------
| Statistics
|--------------------------------------------------------------------------
*/

$totalConversions = (int) $pdo->query(
    "SELECT COUNT(*) FROM conversions"
)->fetchColumn();

$pendingConversions = (int) $pdo->query(
    "SELECT COUNT(*) FROM conversions WHERE status = 'PENDING'"
)->fetchColumn();

$approvedConversions = (int) $pdo->query(
    "SELECT COUNT(*) FROM conversions WHERE status = 'APPROVED'"
)->fetchColumn();

$rejectedConversions = (int) $pdo->query(
    "SELECT COUNT(*) FROM conversions WHERE status = 'REJECTED'"
)->fetchColumn();

$reversedConversions = (int) $pdo->query(
    "SELECT COUNT(*) FROM conversions WHERE status = 'REVERSED'"
)->fetchColumn();

$totalWorkerRewards = (float) $pdo->query(
    "SELECT COALESCE(SUM(worker_reward), 0)
     FROM conversions
     WHERE status = 'APPROVED'"
)->fetchColumn();

$totalPlatformRevenue = (float) $pdo->query(
    "SELECT COALESCE(SUM(platform_margin), 0)
     FROM conversions
     WHERE status = 'APPROVED'"
)->fetchColumn();

/*
|--------------------------------------------------------------------------
| Total filtered records
|--------------------------------------------------------------------------
*/

$countSql = "
    SELECT COUNT(*)
    FROM conversions c
    INNER JOIN users u
        ON u.id = c.worker_id
    LEFT JOIN campaigns cp
        ON cp.id = c.campaign_id
    $whereSql
";

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);

$totalFiltered = (int) $countStmt->fetchColumn();

$totalPages = max(1, (int) ceil($totalFiltered / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

/*
|--------------------------------------------------------------------------
| Load conversions
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        c.id,
        c.campaign_id,
        c.worker_id,
        c.network_id,
        c.external_transaction_id,
        c.network_payout,
        c.reward_rate,
        c.worker_reward,
        c.platform_margin,
        c.status,
        c.converted_at,
        c.created_at,

        u.name AS worker_name,
        u.email AS worker_email,

        cp.title AS campaign_title,
        cp.external_offer_id,

        n.name AS network_name

    FROM conversions c

    INNER JOIN users u
        ON u.id = c.worker_id

    LEFT JOIN campaigns cp
        ON cp.id = c.campaign_id

    LEFT JOIN networks n
        ON n.id = c.network_id

    $whereSql

    ORDER BY
        CASE c.status
            WHEN 'PENDING' THEN 1
            WHEN 'APPROVED' THEN 2
            WHEN 'REJECTED' THEN 3
            WHEN 'REVERSED' THEN 4
            ELSE 5
        END,
        COALESCE(c.converted_at, c.created_at) DESC

    LIMIT $perPage OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$conversions = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function conversionStatusLabel(string $status): string
{
    return match ($status) {
        'APPROVED' => 'Approved',
        'PENDING' => 'Pending',
        'REJECTED' => 'Rejected',
        'REVERSED' => 'Reversed',
        default => ucfirst(strtolower($status)),
    };
}

function conversionStatusClass(string $status): string
{
    return match ($status) {
        'APPROVED' => 'status-approved',
        'PENDING' => 'status-pending',
        'REJECTED' => 'status-rejected',
        'REVERSED' => 'status-reversed',
        default => 'status-default',
    };
}

function conversionsQuery(array $extra = []): string
{
    $query = array_merge(
        [
            'search' => $_GET['search'] ?? '',
            'status' => $_GET['status'] ?? '',
        ],
        $extra
    );

    $query = array_filter(
        $query,
        static fn ($value): bool => $value !== null && $value !== ''
    );

    return http_build_query($query);
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<link rel="stylesheet" href="assets/conversions.css">

<div class="admin-page">

    <div class="page-header">
        <div>
            <h1>Conversions</h1>
            <p>
                Monitor offer conversions, worker rewards and platform revenue.
            </p>
        </div>
    </div>

    <!-- Statistics -->

    <div class="stats-grid">

        <div class="stat-card">
            <div class="stat-label">Total Conversions</div>
            <div class="stat-value">
                <?= number_format($totalConversions) ?>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Pending</div>
            <div class="stat-value">
                <?= number_format($pendingConversions) ?>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Approved</div>
            <div class="stat-value">
                <?= number_format($approvedConversions) ?>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Rejected</div>
            <div class="stat-value">
                <?= number_format($rejectedConversions) ?>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Reversed</div>
            <div class="stat-value">
                <?= number_format($reversedConversions) ?>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-label">
