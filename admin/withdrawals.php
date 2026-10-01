<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/admin-auth.php';

$allowedStatuses = [
    'PENDING',
    'PROCESSING',
    'PAID',
    'REJECTED',
    'CANCELLED',
];

$search = trim((string) ($_GET['search'] ?? ''));
$status = strtoupper(trim((string) ($_GET['status'] ?? '')));

if (!in_array($status, $allowedStatuses, true)) {
    $status = '';
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

/*
|--------------------------------------------------------------------------
| Statistics
|--------------------------------------------------------------------------
*/

$statsStmt = $pdo->query(
    "SELECT
        SUM(status = 'PENDING') AS pending_count,
        COALESCE(SUM(CASE WHEN status = 'PENDING' THEN amount ELSE 0 END), 0) AS pending_amount,

        SUM(status = 'PROCESSING') AS processing_count,
        COALESCE(SUM(CASE WHEN status = 'PROCESSING' THEN amount ELSE 0 END), 0) AS processing_amount,

        SUM(status = 'PAID') AS paid_count,
        COALESCE(SUM(CASE WHEN status = 'PAID' THEN amount ELSE 0 END), 0) AS paid_amount,

        SUM(status = 'REJECTED') AS rejected_count,
        COALESCE(SUM(CASE WHEN status = 'REJECTED' THEN amount ELSE 0 END), 0) AS rejected_amount,

        SUM(status = 'CANCELLED') AS cancelled_count,
        COALESCE(SUM(CASE WHEN status = 'CANCELLED' THEN amount ELSE 0 END), 0) AS cancelled_amount

     FROM withdrawals"
);

$stats = $statsStmt->fetch() ?: [];

$pendingCount = (int) ($stats['pending_count'] ?? 0);
$pendingAmount = (float) ($stats['pending_amount'] ?? 0);

$processingCount = (int) ($stats['processing_count'] ?? 0);
$processingAmount = (float) ($stats['processing_amount'] ?? 0);

$paidCount = (int) ($stats['paid_count'] ?? 0);
$paidAmount = (float) ($stats['paid_amount'] ?? 0);

$rejectedCount = (int) ($stats['rejected_count'] ?? 0);
$rejectedAmount = (float) ($stats['rejected_amount'] ?? 0);

$cancelledCount = (int) ($stats['cancelled_count'] ?? 0);
$cancelledAmount = (float) ($stats['cancelled_amount'] ?? 0);

/*
|--------------------------------------------------------------------------
| Build filters
|--------------------------------------------------------------------------
*/

$where = [];
$params = [];

if ($status !== '') {
    $where[] = 'w.status = ?';
    $params[] = $status;
}

if ($search !== '') {
    $where[] = '(
        u.name LIKE ?
        OR u.email LIKE ?
        OR w.method LIKE ?
        OR w.method_network LIKE ?
        OR w.transaction_reference LIKE ?
    )';

    $searchValue = '%' . $search . '%';

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
}

$whereSql = '';

if ($where) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

/*
|--------------------------------------------------------------------------
| Total rows
|--------------------------------------------------------------------------
*/

$countSql = "
    SELECT COUNT(*)
    FROM withdrawals w
    INNER JOIN users u ON u.id = w.user_id
    $whereSql
";

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);

$totalRows = (int) $countStmt->fetchColumn();

$totalPages = max(1, (int) ceil($totalRows / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

/*
|--------------------------------------------------------------------------
| Withdrawals
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        w.id,
        w.user_id,
        w.amount,
        w.method,
        w.method_network,
        w.status,
        w.transaction_reference,
        w.created_at,
        w.updated_at,

        u.name AS user_name,
        u.email AS user_email

    FROM withdrawals w

    INNER JOIN users u
        ON u.id = w.user_id

    $whereSql

    ORDER BY
        CASE w.status
            WHEN 'PENDING' THEN 1
            WHEN 'PROCESSING' THEN 2
            WHEN 'PAID' THEN 3
            WHEN 'REJECTED' THEN 4
            WHEN 'CANCELLED' THEN 5
            ELSE 6
        END,
        w.created_at DESC

    LIMIT $perPage OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$withdrawals = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function withdrawalStatusClass(string $status): string
{
    return match ($status) {
        'PENDING' => 'status-pending',
        'PROCESSING' => 'status-processing',
        'PAID' => 'status-paid',
        'REJECTED' => 'status-rejected',
        'CANCELLED' => 'status-cancelled',
        default => 'status-default',
    };
}

function withdrawalStatusLabel(string $status): string
{
    return match ($status) {
        'PENDING' => 'Pending',
        'PROCESSING' => 'Processing',
        'PAID' => 'Paid',
        'REJECTED' => 'Rejected',
        'CANCELLED' => 'Cancelled',
        default => ucfirst(strtolower($status)),
    };
}

function withdrawalsQuery(array $extra = []): string
{
    global $search, $status, $page;

    $query = [
        'search' => $search,
        'status' => $status,
        'page' => $page,
    ];

    foreach ($extra as $key => $value) {
        $query[$key] = $value;
    }

    $query = array_filter(
        $query,
        static fn ($value) => $value !== '' && $value !== null
    );

    return http_build_query($query);
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

    <title>Withdrawals - PoketFlow Admin</title>

    <link rel="stylesheet" href="assets/withdrawals.css">
</head>

<body>

<div class="admin-layout">

    <!-- Sidebar -->
    <aside class="admin-sidebar">

        <div class="sidebar-brand">
            <a href="index.php">
                Poket<span>Flow</span>
            </a>

            <small>Admin Panel</small>
        </div>

        <nav class="sidebar-nav">

            <a href="index.php">
                <span>▣</span>
                Dashboard
            </a>

            <a href="users.php">
                <span>♙</span>
                Users
            </a>

            <a href="withdrawals.php" class="active">
                <span>💳</span>
                Withdrawals
            </a>

            <a href="conversions.php">
                <span>↗</span>
                Conversions
            </a>

            <a href="campaigns.php">
                <span>▤</span>
                Campaigns
            </a>

            <a href="wallet.php">
                <span>◉</span>
                Wallet
            </a>

            <a href="settings.php">
                <span>⚙</span>
                Settings
            </a>

        </nav>

        <div class="sidebar-bottom">

            <a href="../index.php">
                <span>↗</span>
                View Site
            </a>

            <a href="../logout.php">
                <span>⇥</span>
                Logout
            </a>

        </div>

    </aside>


    <!-- Main -->
    <main class="admin-main">

        <!-- Header -->
        <header class="admin-header">

            <div>
                <h1>Withdrawals</h1>
                <p>
                    Manage and review user withdrawal requests.
                </p>
            </div>

            <div class="admin-user">

                <div class="admin-avatar">
                    <?= e(strtoupper(substr($adminUser['name'], 0, 1))) ?>
                </div>

                <div>
                    <strong><?= e($adminUser['name']) ?></strong>
                    <span>Administrator</span>
                </div>

            </div>

        </header>


        <!-- Statistics -->
        <section class="stats-grid">

            <div class="stat-card pending">
                <div class="stat-icon">⏳</div>

                <div>
                    <span>Pending</span>
                    <strong><?= number_format($pendingCount) ?></strong>
                    <small>
                        $<?= number_format($pendingAmount, 2) ?>
                    </small>
                </div>
            </div>


            <div class="stat-card processing">
                <div class="stat-icon">↻</div>

                <div>
                    <span>Processing</span>
                    <strong><?= number_format($processingCount) ?></strong>
                    <small>
                        $<?= number_format($processingAmount, 2) ?>
                    </small>
                </div>
            </div>


            <div class="stat-card paid">
                <div class="stat-icon">✓</div>

                <div>
                    <span>Paid</span>
                    <strong><?= number_format($paidCount) ?></strong>
                    <small>
                        $<?= number_format($paidAmount, 2) ?>
                    </small>
                </div>
            </div>


            <div class="stat-card rejected">
                <div class="stat-icon">!</div>

                <div>
                    <span>Rejected</span>
                    <strong><?= number_format($rejectedCount) ?></strong>
                    <small>
                        $<?= number_format($rejectedAmount, 2) ?>
                    </small>
                </div>
            </div>


            <div class="stat-card cancelled">
                <div class="stat-icon">×</div>

                <div>
                    <span>Cancelled</span>
                    <strong><?= number_format($cancelledCount) ?></strong>
                    <small>
                        $<?= number_format($cancelledAmount, 2) ?>
                    </small>
                </div>
            </div>

        </section>


        <!-- Filters -->
        <section class="filter-card">

            <form method="get" action="withdrawals.php">

                <div class="filter-group search-group">

                    <label for="search">Search</label>

                    <input
                        type="text"
                        id="search"
                        name="search"
                        value="<?= e($search) ?>"
                        placeholder="Name, email, method or reference..."
                    >

                </div>


                <div class="filter-group">

                    <label for="status">Status</label>

                    <select id="status" name="status">

                        <option value="">All statuses</option>

                        <?php foreach ($allowedStatuses as $option): ?>

                            <option
                                value="<?= e($option) ?>"
                                <?= $status === $option ? 'selected' : '' ?>
                            >
                                <?= e(withdrawalStatusLabel($option)) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="filter-actions">

                    <button type="submit" class="btn-primary">
                        Apply Filters
                    </button>

                    <a href="withdrawals.php" class="btn-secondary">
                        Reset
                    </a>

                </div>

            </form>

        </section>


        <!-- Withdrawal Table -->
        <section class="table-card">

            <div class="table-header">

                <div>
                    <h2>Withdrawal Requests</h2>

                    <p>
                        <?= number_format($totalRows) ?>
                        request<?= $totalRows === 1 ? '' : 's' ?>
                        found
                    </p>
                </div>

            </div>


            <?php if (!$withdrawals): ?>

                <div class="empty-state">

                    <div class="empty-icon">💳</div>

                    <h3>No withdrawals found</h3>

                    <p>
                        There are no withdrawal requests matching your
                        current filters.
                    </p>

                </div>

            <?php else: ?>

                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Network</th>
                                <th>Status</th>
                                <th>Reference</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>

                        </thead>

                        <tbody>

                        <?php foreach ($withdrawals as $withdrawal): ?>

                            <?php
                            $withdrawalStatus = strtoupper(
                                (string) $withdrawal['status']
                            );
                            ?>

                            <tr>

                                <td>
                                    <span class="withdrawal-id">
                                        #<?= (int) $withdrawal['id'] ?>
                                    </span>
                                </td>


                                <td>

                                    <div class="user-cell">

                                        <div class="user-avatar">
                                            <?= e(
                                                strtoupper(
                                                    substr(
                                                        (string) $withdrawal['user_name'],
                                                        0,
                                                        1
                                                    )
                                                )
                                            ) ?>
                                        </div>

                                        <div class="user-info">

                                            <strong>
                                                <?= e(
                                                    (string) $withdrawal['user_name']
                                                ) ?>
                                            </strong>

                                            <span>
                                                <?= e(
                                                    (string) $withdrawal['user_email']
                                                ) ?>
                                            </span>

                                        </div>

                                    </div>

                                </td>


                                <td>

                                    <strong class="amount">
                                        $<?= number_format(
                                            (float) $withdrawal['amount'],
                                            2
                                        ) ?>
                                    </strong>

                                </td>


                                <td>
                                    <?= e(
                                        (string) $withdrawal['method']
                                    ) ?>
                                </td>


                                <td>

                                    <?php if (
                                        !empty($withdrawal['method_network'])
                                    ): ?>

                                        <span class="network-badge">
                                            <?= e(
                                                (string) $withdrawal['method_network']
                                            ) ?>
                                        </span>

                                    <?php else: ?>

                                        <span class="muted">
                                            —
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <span
                                        class="status-badge <?= e(
                                            withdrawalStatusClass(
                                                $withdrawalStatus
                                            )
                                        ) ?>"
                                    >
                                        <?= e(
                                            withdrawalStatusLabel(
                                                $withdrawalStatus
                                            )
                                        ) ?>
                                    </span>

                                </td>


                                <td>

                                    <?php if (
                                        !empty(
                                            $withdrawal['transaction_reference']
                                        )
                                    ): ?>

                                        <span class="reference">
                                            <?= e(
                                                (string) $withdrawal[
                                                    'transaction_reference'
                                                ]
                                            ) ?>
                                        </span>

                                    <?php else: ?>

                                        <span class="muted">
                                            —
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <span class="date">

                                        <?= e(
                                            date(
                                                'M j, Y',
                                                strtotime(
                                                    (string) $withdrawal[
                                                        'created_at'
                                                    ]
                                                )
                                            )
                                        ) ?>

                                        <small>
                                            <?= e(
                                                date(
                                                    'H:i',
                                                    strtotime(
                                                        (string) $withdrawal[
                                                            'created_at'
                                                        ]
                                                    )
                                                )
                                            ) ?>
                                        </small>

                                    </span>

                                </td>


                                <td>

                                    <a
                                        href="withdrawal-view.php?id=<?= (int) $withdrawal['id'] ?>"
                                        class="view-btn"
                                    >
                                        View
                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>


                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>

                    <div class="pagination">

                        <?php if ($page > 1): ?>

                            <a
                                href="withdrawals.php?<?= e(
                                    withdrawalsQuery([
                                        'page' => $page - 1
                                    ])
                                ) ?>"
                                class="page-btn"
                            >
                                ← Previous
                            </a>

                        <?php endif; ?>


                        <div class="page-numbers">

                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            ?>

                            <?php for (
                                $pageNumber = $startPage;
                                $pageNumber <= $endPage;
                                $pageNumber++
                            ): ?>

                                <a
                                    href="withdrawals.php?<?= e(
                                        withdrawalsQuery([
                                            'page' => $pageNumber
                                        ])
                                    ) ?>"
                                    class="page-number <?= $pageNumber === $page ? 'active' : '' ?>"
                                >
                                    <?= $pageNumber ?>
                                </a>

                            <?php endfor; ?>

                        </div>


                        <?php if ($page < $totalPages): ?>

                            <a
                                href="withdrawals.php?<?= e(
                                    withdrawalsQuery([
                                        'page' => $page + 1
                                    ])
                                ) ?>"
                                class="page-btn"
                            >
                                Next →
                            </a>

                        <?php endif; ?>

                    </div>

                <?php endif; ?>

            <?php endif; ?>

        </section>

    </main>

</div>

</body>
</html>
