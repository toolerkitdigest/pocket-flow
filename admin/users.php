
<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/admin-auth.php';

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$search = trim((string) ($_GET['search'] ?? ''));
$role = strtoupper(trim((string) ($_GET['role'] ?? '')));
$status = strtoupper(trim((string) ($_GET['status'] ?? '')));

$allowedRoles = [
    'WORKER',
    'ADVERTISER',
    'ADMIN',
];

$allowedStatuses = [
    'ACTIVE',
    'SUSPENDED',
    'PENDING',
    'BANNED',
];

if (!in_array($role, $allowedRoles, true)) {
    $role = '';
}

if (!in_array($status, $allowedStatuses, true)) {
    $status = '';
}

/*
|--------------------------------------------------------------------------
| Statistics
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    'SELECT COUNT(*) FROM users'
);

$totalUsers = (int) $stmt->fetchColumn();


$stmt = $pdo->query(
    'SELECT COUNT(*)
     FROM users
     WHERE status = "ACTIVE"'
);

$activeUsers = (int) $stmt->fetchColumn();


$stmt = $pdo->query(
    'SELECT COUNT(*)
     FROM users
     WHERE status = "SUSPENDED"'
);

$suspendedUsers = (int) $stmt->fetchColumn();


$stmt = $pdo->query(
    'SELECT COUNT(*)
     FROM users
     WHERE status = "BANNED"'
);

$bannedUsers = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

$perPage = 20;

$page = filter_input(
    INPUT_GET,
    'page',
    FILTER_VALIDATE_INT
);

$page = $page && $page > 0
    ? $page
    : 1;

$offset = ($page - 1) * $perPage;


/*
|--------------------------------------------------------------------------
| Build User Query
|--------------------------------------------------------------------------
*/

$where = [];
$params = [];

if ($search !== '') {

    $where[] = '(u.name LIKE ? OR u.email LIKE ?)';

    $searchTerm = '%' . $search . '%';

    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($role !== '') {

    $where[] = 'u.role = ?';

    $params[] = $role;
}

if ($status !== '') {

    $where[] = 'u.status = ?';

    $params[] = $status;
}

$whereSql = '';

if (!empty($where)) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}


/*
|--------------------------------------------------------------------------
| Total Matching Users
|--------------------------------------------------------------------------
*/

$countSql = "
    SELECT COUNT(*)
    FROM users u
    $whereSql
";

$stmt = $pdo->prepare($countSql);
$stmt->execute($params);

$totalMatchingUsers = (int) $stmt->fetchColumn();

$totalPages = max(
    1,
    (int) ceil($totalMatchingUsers / $perPage)
);

if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}


/*
|--------------------------------------------------------------------------
| User List
|--------------------------------------------------------------------------
*/

$userSql = "
    SELECT
        u.id,
        u.name,
        u.email,
        u.role,
        u.country,
        u.status,
        u.created_at,

        COALESCE(
            (
                SELECT SUM(wt.amount)
                FROM wallet_transactions wt
                WHERE wt.user_id = u.id
                  AND wt.status = 'COMPLETED'
            ),
            0
        ) AS balance,

        (
            SELECT COUNT(*)
            FROM conversions c
            WHERE c.worker_id = u.id
        ) AS conversion_count,

        (
            SELECT COUNT(*)
            FROM withdrawals w
            WHERE w.user_id = u.id
        ) AS withdrawal_count

    FROM users u

    $whereSql

    ORDER BY u.created_at DESC

    LIMIT $perPage
    OFFSET $offset
";

$stmt = $pdo->prepare($userSql);
$stmt->execute($params);

$users = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Helper: Preserve Filters
|--------------------------------------------------------------------------
*/

function usersQuery(array $changes = []): string
{
    $query = $_GET;

    foreach ($changes as $key => $value) {

        if ($value === null || $value === '') {
            unset($query[$key]);
        } else {
            $query[$key] = $value;
        }
    }

    return http_build_query($query);
}

?>
<!doctype html>
<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Users — PoketFlow Admin</title>

    <link
        rel="stylesheet"
        href="assets/admin.css"
    >

</head>

<body class="admin-page">

<header class="admin-header">

    <a href="index.php" class="admin-brand">

        <span class="brand-mark">P</span>

        <span>
            Poket<span>Flow</span>
        </span>

    </a>


    <div class="admin-header-right">

        <span class="admin-user">
            <?= e($adminUser['name']) ?>
        </span>

        <a href="../dashboard.php">
            View Site
        </a>

        <a href="../logout.php">
            Logout
        </a>

    </div>

</header>


<div class="admin-layout">


    <!-- SIDEBAR -->

    <aside class="admin-sidebar">

        <nav>

            <a href="index.php">
                📊
                <span>Dashboard</span>
            </a>

            <a class="active" href="users.php">
                👥
                <span>Users</span>
            </a>

            <a href="withdrawals.php">
                💳
                <span>Withdrawals</span>
            </a>

            <a href="conversions.php">
                ✓
                <span>Conversions</span>
            </a>

            <a href="campaigns.php">
                🎯
                <span>Campaigns</span>
            </a>

            <a href="wallet.php">
                💰
                <span>Wallet</span>
            </a>

            <a href="settings.php">
                ⚙
                <span>Settings</span>
            </a>

        </nav>

    </aside>


    <!-- CONTENT -->

    <main class="admin-content">


        <div class="page-heading">

            <span class="kicker">
                USER MANAGEMENT
            </span>

            <h1>
                Users
            </h1>

            <p>
                Manage PoketFlow users, accounts and activity.
            </p>

        </div>


        <!-- USER STATISTICS -->

        <section class="stats-grid">

            <div class="stat-card">

                <span class="stat-label">
                    Total Users
                </span>

                <strong>
                    <?= number_format($totalUsers) ?>
                </strong>

                <small>
                    All registered accounts
                </small>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Active
                </span>

                <strong>
                    <?= number_format($activeUsers) ?>
                </strong>

                <small>
                    Active accounts
                </small>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Suspended
                </span>

                <strong>
                    <?= number_format($suspendedUsers) ?>
                </strong>

                <small>
                    Temporarily restricted
                </small>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Banned
                </span>

                <strong>
                    <?= number_format($bannedUsers) ?>
                </strong>

                <small>
                    Permanently restricted
                </small>

            </div>

        </section>


        <!-- SEARCH / FILTERS -->

        <section class="admin-panel">

            <div class="panel-heading">

                <div>

                    <h2>
                        Find Users
                    </h2>

                    <p>
                        Search and filter registered accounts.
                    </p>

                </div>

            </div>


            <form
                method="get"
                class="admin-filters"
            >

                <div class="filter-field">

                    <label for="search">
                        Search
                    </label>

                    <input
                        type="search"
                        id="search"
                        name="search"
                        value="<?= e($search) ?>"
                        placeholder="Name or email"
                    >

                </div>


                <div class="filter-field">

                    <label for="role">
                        Role
                    </label>

                    <select id="role" name="role">

                        <option value="">
                            All roles
                        </option>

                        <?php foreach ($allowedRoles as $itemRole): ?>

                            <option
                                value="<?= e($itemRole) ?>"
                                <?= $role === $itemRole ? 'selected' : '' ?>
                            >
                                <?= e($itemRole) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="filter-field">

                    <label for="status">
                        Status
                    </label>

                    <select id="status" name="status">

                        <option value="">
                            All statuses
                        </option>

                        <?php foreach ($allowedStatuses as $itemStatus): ?>

                            <option
                                value="<?= e($itemStatus) ?>"
                                <?= $status === $itemStatus ? 'selected' : '' ?>
                            >
                                <?= e($itemStatus) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="filter-actions">

                    <button
                        type="submit"
                        class="admin-button primary"
                    >
                        Search
                    </button>

                    <a
                        href="users.php"
                        class="admin-button secondary"
                    >
                        Reset
                    </a>

                </div>

            </form>

        </section>


        <!-- USER TABLE -->

        <section class="admin-panel">

            <div class="panel-heading">

                <div>

                    <h2>
                        User Accounts
                    </h2>

                    <p>
                        Showing <?= number_format($totalMatchingUsers) ?> matching users.
                    </p>

                </div>

            </div>


            <?php if (empty($users)): ?>

                <div class="admin-empty">

                    No users matched your search.

                </div>

            <?php else: ?>

                <div class="table-wrap">

                    <table>

                        <thead>

                        <tr>

                            <th>
                                User
                            </th>

                            <th>
                                Role
                            </th>

                            <th>
                                Country
                            </th>

                            <th>
                                Balance
                            </th>

                            <th>
                                Conversions
                            </th>

                            <th>
                                Withdrawals
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                        </thead>


                        <tbody>

                        <?php foreach ($users as $item): ?>

                            <tr>

                                <td>

                                    <strong>
                                        <?= e($item['name']) ?>
                                    </strong>

                                    <small>
                                        <?= e($item['email']) ?>
                                    </small>

                                </td>


                                <td>

                                    <span class="status-badge">
                                        <?= e($item['role']) ?>
                                    </span>

                                </td>


                                <td>
                                    <?= e($item['country'] ?: '—') ?>
                                </td>


                                <td>

                                    <strong>
                                        $<?= number_format(
                                            (float) $item['balance'],
                                            2
                                        ) ?>
                                    </strong>

                                </td>


                                <td>
                                    <?= number_format(
                                        (int) $item['conversion_count']
                                    ) ?>
                                </td>


                                <td>
                                    <?= number_format(
                                        (int) $item['withdrawal_count']
                                    ) ?>
                                </td>


                                <td>

                                    <span class="status-badge">

                                        <?= e($item['status']) ?>

                                    </span>

                                </td>


                                <td>

                                    <a
                                        href="user-view.php?id=<?= (int) $item['id'] ?>"
                                        class="admin-button secondary"
                                    >
                                        View
                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>


                <!-- PAGINATION -->

                <?php if ($totalPages > 1): ?>

                    <div class="pagination">

                        <?php if ($page > 1): ?>

                            <a
                                href="?<?= e(usersQuery(['page' => $page - 1])) ?>"
                            >
                                ← Previous
                            </a>

                        <?php endif; ?>


                        <span>
                            Page <?= $page ?> of <?= $totalPages ?>
                        </span>


                        <?php if ($page < $totalPages): ?>

                            <a
                                href="?<?= e(usersQuery(['page' => $page + 1])) ?>"
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
