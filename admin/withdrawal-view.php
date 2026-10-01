<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/admin-auth.php';

$withdrawalId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$withdrawalId || $withdrawalId < 1) {
    redirect('withdrawals.php');
}

/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['admin_csrf_token'];

$errors = [];
$success = '';

/*
|--------------------------------------------------------------------------
| Handle Admin Actions
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $submittedToken = (string) ($_POST['csrf_token'] ?? '');

    if (
        !hash_equals(
            $csrfToken,
            $submittedToken
        )
    ) {
        $errors[] = 'Invalid security token. Please refresh the page and try again.';
    } else {

        $action = strtoupper(
            trim((string) ($_POST['action'] ?? ''))
        );

        $adminNote = trim(
            (string) ($_POST['admin_note'] ?? '')
        );

        $transactionReference = trim(
            (string) ($_POST['transaction_reference'] ?? '')
        );

        try {

            $pdo->beginTransaction();

            /*
            |--------------------------------------------------------------------------
            | Lock Withdrawal
            |--------------------------------------------------------------------------
            */

            $lockStmt = $pdo->prepare(
                'SELECT *
                 FROM withdrawals
                 WHERE id = ?
                 FOR UPDATE'
            );

            $lockStmt->execute([$withdrawalId]);

            $withdrawal = $lockStmt->fetch();

            if (!$withdrawal) {
                throw new RuntimeException(
                    'Withdrawal request was not found.'
                );
            }

            $currentStatus = strtoupper(
                (string) $withdrawal['status']
            );

            /*
            |--------------------------------------------------------------------------
            | PROCESS
            |--------------------------------------------------------------------------
            */

            if ($action === 'PROCESS') {

                if ($currentStatus !== 'PENDING') {
                    throw new RuntimeException(
                        'Only pending withdrawals can be moved to processing.'
                    );
                }

                $stmt = $pdo->prepare(
                    "UPDATE withdrawals
                     SET
                        status = 'PROCESSING',
                        admin_note = ?,
                        processed_by = ?,
                        processed_at = NOW(),
                        updated_at = NOW()
                     WHERE id = ?
                       AND status = 'PENDING'"
                );

                $stmt->execute([
                    $adminNote !== '' ? $adminNote : null,
                    (int) $adminUser['id'],
                    $withdrawalId,
                ]);

                if ($stmt->rowCount() !== 1) {
                    throw new RuntimeException(
                        'The withdrawal could not be moved to processing.'
                    );
                }

                $success = 'Withdrawal moved to processing.';

            /*
            |--------------------------------------------------------------------------
            | MARK AS PAID
            |--------------------------------------------------------------------------
            */

            } elseif ($action === 'PAY') {

                if ($currentStatus !== 'PROCESSING') {
                    throw new RuntimeException(
                        'Only processing withdrawals can be marked as paid.'
                    );
                }

                if ($transactionReference === '') {
                    throw new RuntimeException(
                        'A transaction reference is required before marking a withdrawal as paid.'
                    );
                }

                if (strlen($transactionReference) > 190) {
                    throw new RuntimeException(
                        'The transaction reference is too long.'
                    );
                }

                $stmt = $pdo->prepare(
                    "UPDATE withdrawals
                     SET
                        status = 'PAID',
                        admin_note = ?,
                        transaction_reference = ?,
                        processed_by = ?,
                        processed_at = NOW(),
                        updated_at = NOW()
                     WHERE id = ?
                       AND status = 'PROCESSING'"
                );

                $stmt->execute([
                    $adminNote !== '' ? $adminNote : null,
                    $transactionReference,
                    (int) $adminUser['id'],
                    $withdrawalId,
                ]);

                if ($stmt->rowCount() !== 1) {
                    throw new RuntimeException(
                        'The withdrawal could not be marked as paid.'
                    );
                }

                $success = 'Withdrawal marked as paid successfully.';

            /*
            |--------------------------------------------------------------------------
            | REJECT + REFUND
            |--------------------------------------------------------------------------
            */

            } elseif ($action === 'REJECT') {

                if (
                    !in_array(
                        $currentStatus,
                        ['PENDING', 'PROCESSING'],
                        true
                    )
                ) {
                    throw new RuntimeException(
                        'Only pending or processing withdrawals can be rejected.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Find the original wallet reservation.
                |
                | The withdrawal process creates a negative COMPLETED
                | wallet transaction. We refund that exact amount once.
                |--------------------------------------------------------------------------
                */

                $walletStmt = $pdo->prepare(
                    "SELECT *
                     FROM wallet_transactions
                     WHERE user_id = ?
                       AND type = 'WITHDRAWAL'
                       AND reference_type = 'withdrawal'
                       AND reference_id = ?
                       AND status = 'COMPLETED'
                     ORDER BY id ASC
                     LIMIT 1
                     FOR UPDATE"
                );

                $walletStmt->execute([
                    (int) $withdrawal['user_id'],
                    $withdrawalId,
                ]);

                $withdrawalTransaction = $walletStmt->fetch();

                if (!$withdrawalTransaction) {
                    throw new RuntimeException(
                        'The original wallet reservation could not be found. No refund was made.'
                    );
                }

                $reservedAmount = (float) $withdrawalTransaction['amount'];

                /*
                |--------------------------------------------------------------------------
                | Make sure the original transaction is actually negative.
                |--------------------------------------------------------------------------
                */

                if ($reservedAmount >= 0) {
                    throw new RuntimeException(
                        'The withdrawal reservation has an invalid wallet amount. No refund was made.'
                    );
                }

                $refundAmount = abs($reservedAmount);

                /*
                |--------------------------------------------------------------------------
                | Prevent duplicate refund.
                |--------------------------------------------------------------------------
                */

                $refundCheckStmt = $pdo->prepare(
                    "SELECT id
                     FROM wallet_transactions
                     WHERE user_id = ?
                       AND type = 'REVERSAL'
                       AND reference_type = 'withdrawal'
                       AND reference_id = ?
                       AND status = 'COMPLETED'
                     LIMIT 1
                     FOR UPDATE"
                );

                $refundCheckStmt->execute([
                    (int) $withdrawal['user_id'],
                    $withdrawalId,
                ]);

                if ($refundCheckStmt->fetch()) {
                    throw new RuntimeException(
                        'This withdrawal has already been refunded.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Get current balance
                |--------------------------------------------------------------------------
                */

                $balanceStmt = $pdo->prepare(
                    "SELECT
                        COALESCE(
                            SUM(
                                CASE
                                    WHEN status = 'COMPLETED'
                                    THEN amount
                                    ELSE 0
                                END
                            ),
                            0
                        )
                     FROM wallet_transactions
                     WHERE user_id = ?"
                );

                $balanceStmt->execute([
                    (int) $withdrawal['user_id'],
                ]);

                $balanceBefore = (float) $balanceStmt->fetchColumn();
                $balanceAfter = $balanceBefore + $refundAmount;

                /*
                |--------------------------------------------------------------------------
                | Create refund transaction.
                |--------------------------------------------------------------------------
                */

                $refundDescription =
                    'Refund for rejected withdrawal #' .
                    $withdrawalId;

                $refundStmt = $pdo->prepare(
                    "INSERT INTO wallet_transactions
                    (
                        user_id,
                        type,
                        reference_type,
                        reference_id,
                        amount,
                        currency,
                        balance_before,
                        balance_after,
                        status,
                        description
                    )
                    VALUES
                    (
                        ?,
                        'REVERSAL',
                        'withdrawal',
                        ?,
                        ?,
                        'USD',
                        ?,
                        ?,
                        'COMPLETED',
                        ?
                    )"
                );

                $refundStmt->execute([
                    (int) $withdrawal['user_id'],
                    $withdrawalId,
                    $refundAmount,
                    $balanceBefore,
                    $balanceAfter,
                    $refundDescription,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Mark withdrawal rejected.
                |--------------------------------------------------------------------------
                */

                $rejectStmt = $pdo->prepare(
                    "UPDATE withdrawals
                     SET
                        status = 'REJECTED',
                        admin_note = ?,
                        processed_by = ?,
                        processed_at = NOW(),
                        updated_at = NOW()
                     WHERE id = ?
                       AND status IN ('PENDING', 'PROCESSING')"
                );

                $rejectStmt->execute([
                    $adminNote !== '' ? $adminNote : null,
                    (int) $adminUser['id'],
                    $withdrawalId,
                ]);

                if ($rejectStmt->rowCount() !== 1) {
                    throw new RuntimeException(
                        'The withdrawal status could not be updated.'
                    );
                }

                $success =
                    'Withdrawal rejected and $' .
                    number_format($refundAmount, 2) .
                    ' refunded to the user.';
            }

            if (
                !in_array(
                    $action,
                    ['PROCESS', 'PAY', 'REJECT'],
                    true
                )
            ) {
                throw new RuntimeException(
                    'Invalid withdrawal action.'
                );
            }

            $pdo->commit();

        } catch (Throwable $exception) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] = $exception->getMessage();
        }
    }
}

/*
|--------------------------------------------------------------------------
| Load Withdrawal
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT
        w.*,

        u.name AS user_name,
        u.email AS user_email,
        u.country AS user_country,
        u.status AS user_status,
        u.referral_code,

        a.name AS processor_name

     FROM withdrawals w

     INNER JOIN users u
        ON u.id = w.user_id

     LEFT JOIN users a
        ON a.id = w.processed_by

     WHERE w.id = ?

     LIMIT 1"
);

$stmt->execute([$withdrawalId]);

$withdrawal = $stmt->fetch();

if (!$withdrawal) {
    redirect('withdrawals.php');
}

/*
|--------------------------------------------------------------------------
| User Balance
|--------------------------------------------------------------------------
*/

$userBalance = getUserBalance(
    $pdo,
    (int) $withdrawal['user_id']
);

$userTotalEarned = getUserTotalEarned(
    $pdo,
    (int) $withdrawal['user_id']
);

$userPendingBalance = getUserPendingBalance(
    $pdo,
    (int) $withdrawal['user_id']
);

/*
|--------------------------------------------------------------------------
| Original Withdrawal Wallet Transaction
|--------------------------------------------------------------------------
*/

$reservationStmt = $pdo->prepare(
    "SELECT *
     FROM wallet_transactions
     WHERE user_id = ?
       AND type = 'WITHDRAWAL'
       AND reference_type = 'withdrawal'
       AND reference_id = ?
     ORDER BY id ASC
     LIMIT 1"
);

$reservationStmt->execute([
    (int) $withdrawal['user_id'],
    $withdrawalId,
]);

$reservation = $reservationStmt->fetch();

/*
|--------------------------------------------------------------------------
| Check Refund
|--------------------------------------------------------------------------
*/

$refundStmt = $pdo->prepare(
    "SELECT *
     FROM wallet_transactions
     WHERE user_id = ?
       AND type = 'REVERSAL'
       AND reference_type = 'withdrawal'
       AND reference_id = ?
       AND status = 'COMPLETED'
     ORDER BY id DESC
     LIMIT 1"
);

$refundStmt->execute([
    (int) $withdrawal['user_id'],
    $withdrawalId,
]);

$refundTransaction = $refundStmt->fetch();

$status = strtoupper(
    (string) $withdrawal['status']
);

$statusClass = match ($status) {
    'PENDING' => 'status-pending',
    'PROCESSING' => 'status-processing',
    'PAID' => 'status-paid',
    'REJECTED' => 'status-rejected',
    'CANCELLED' => 'status-cancelled',
    default => 'status-default',
};

$statusLabel = match ($status) {
    'PENDING' => 'Pending',
    'PROCESSING' => 'Processing',
    'PAID' => 'Paid',
    'REJECTED' => 'Rejected',
    'CANCELLED' => 'Cancelled',
    default => ucfirst(strtolower($status)),
};

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Withdrawal #<?= (int) $withdrawal['id'] ?>
        - PoketFlow Admin
    </title>

    <link
        rel="stylesheet"
        href="assets/withdrawal-view.css"
    >

</head>

<body>

<div class="admin-layout">

    <!-- =====================================================
         SIDEBAR
         ===================================================== -->

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

            <a
                href="withdrawals.php"
                class="active"
            >
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


    <!-- =====================================================
         MAIN
         ===================================================== -->

    <main class="admin-main">

        <!-- Header -->

        <header class="admin-header">

            <div>

                <div class="breadcrumb">
                    <a href="withdrawals.php">
                        Withdrawals
                    </a>

                    <span>›</span>

                    <span>
                        #<?= (int) $withdrawal['id'] ?>
                    </span>
                </div>

                <h1>
                    Withdrawal #<?= (int) $withdrawal['id'] ?>
                </h1>

                <p>
                    Review the request and manage its payment status.
                </p>

            </div>


            <div class="admin-user">

                <div class="admin-avatar">
                    <?= e(
                        strtoupper(
                            substr(
                                (string) $adminUser['name'],
                                0,
                                1
                            )
                        )
                    ) ?>
                </div>

                <div>

                    <strong>
                        <?= e($adminUser['name']) ?>
                    </strong>

                    <span>
                        Administrator
                    </span>

                </div>

            </div>

        </header>


        <!-- Messages -->

        <?php if ($success !== ''): ?>

            <div class="alert alert-success">
                <strong>Success</strong>
                <span><?= e($success) ?></span>
            </div>

        <?php endif; ?>


        <?php if ($errors): ?>

            <div class="alert alert-error">

                <strong>Action not completed</strong>

                <ul>

                    <?php foreach ($errors as $error): ?>

                        <li><?= e($error) ?></li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endif; ?>


        <!-- =================================================
             Status Banner
             ================================================= -->

        <section class="status-banner">

            <div>

                <span class="status-caption">
                    Current Status
                </span>

                <span
                    class="status-badge <?= e($statusClass) ?>"
                >
                    <?= e($statusLabel) ?>
                </span>

            </div>


            <div class="status-amount">

                <span>
                    Withdrawal Amount
                </span>

                <strong>
                    $<?= number_format(
                        (float) $withdrawal['amount'],
                        2
                    ) ?>
                </strong>

            </div>

        </section>


        <div class="content-grid">

            <!-- =================================================
                 LEFT
                 ================================================= -->

            <div class="main-column">

                <!-- User -->

                <section class="panel">

                    <div class="panel-header">

                        <div>
                            <h2>User</h2>
                            <p>Account connected to this withdrawal.</p>
                        </div>

                        <a
                            href="user-view.php?id=<?= (int) $withdrawal['user_id'] ?>"
                            class="panel-link"
                        >
                            View User
                        </a>

                    </div>


                    <div class="user-profile">

                        <div class="large-avatar">
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


                        <div class="user-details">

                            <h3>
                                <?= e(
                                    (string) $withdrawal['user_name']
                                ) ?>
                            </h3>

                            <p>
                                <?= e(
                                    (string) $withdrawal['user_email']
                                ) ?>
                            </p>

                            <div class="user-meta">

                                <?php if (
                                    !empty($withdrawal['user_country'])
                                ): ?>

                                    <span>
                                        🌍
                                        <?= e(
                                            (string) $withdrawal['user_country']
                                        ) ?>
                                    </span>

                                <?php endif; ?>

                                <span>
                                    Status:
                                    <?= e(
                                        (string) $withdrawal['user_status']
                                    ) ?>
                                </span>

                                <span>
                                    Referral:
                                    <?= e(
                                        (string) $withdrawal['referral_code']
                                    ) ?>
                                </span>

                            </div>

                        </div>

                    </div>

                </section>


                <!-- Payment -->

                <section class="panel">

                    <div class="panel-header">

                        <div>
                            <h2>Payment Details</h2>
                            <p>
                                Information supplied by the user.
                            </p>
                        </div>

                    </div>


                    <div class="details-grid">

                        <div class="detail-item">

                            <span>Method</span>

                            <strong>
                                <?= e(
                                    (string) $withdrawal['method']
                                ) ?>
                            </strong>

                        </div>


                        <div class="detail-item">

                            <span>Network</span>

                            <strong>

                                <?php if (
                                    !empty(
                                        $withdrawal['method_network']
                                    )
                                ): ?>

                                    <?= e(
                                        (string) $withdrawal[
                                            'method_network'
                                        ]
                                    ) ?>

                                <?php else: ?>

                                    Not specified

                                <?php endif; ?>

                            </strong>

                        </div>


                        <div class="detail-item full-width">

                            <span>Payment Details</span>

                            <div class="payment-details">

                                <?php

                                $paymentDetails = json_decode(
                                    (string) $withdrawal['payment_details'],
                                    true
                                );

                                if (
                                    is_array($paymentDetails)
                                    && $paymentDetails
                                ):

                                ?>

                                    <?php foreach (
                                        $paymentDetails as $key => $value
                                    ): ?>

                                        <div class="payment-row">

                                            <span>
                                                <?= e(
                                                    ucwords(
                                                        str_replace(
                                                            '_',
                                                            ' ',
                                                            (string) $key
                                                        )
                                                    )
                                                ) ?>
                                            </span>

                                            <strong>
                                                <?= e(
                                                    is_scalar($value)
                                                        ? (string) $value
                                                        : json_encode(
                                                            $value
                                                        )
                                                ) ?>
                                            </strong>

                                        </div>

                                    <?php endforeach; ?>

                                <?php else: ?>

                                    <pre><?= e(
                                        (string) $withdrawal['payment_details']
                                    ) ?></pre>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                </section>


                <!-- Request Information -->

                <section class="panel">

                    <div class="panel-header">

                        <div>
                            <h2>Request Information</h2>
                            <p>
                                Withdrawal lifecycle information.
                            </p>
                        </div>

                    </div>


                    <div class="timeline">

                        <div class="timeline-item">

                            <div class="timeline-dot"></div>

                            <div>

                                <strong>
                                    Request Created
                                </strong>

                                <span>
                                    <?= e(
                                        date(
                                            'M j, Y H:i',
                                            strtotime(
                                                (string) $withdrawal[
                                                    'created_at'
                                                ]
                                            )
                                        )
                                    ) ?>
                                </span>

                            </div>

                        </div>


                        <?php if (
                            !empty($withdrawal['processed_at'])
                        ): ?>

                            <div class="timeline-item">

                                <div class="timeline-dot"></div>

                                <div>

                                    <strong>
                                        Last Processed
                                    </strong>

                                    <span>
                                        <?= e(
                                            date(
                                                'M j, Y H:i',
                                                strtotime(
                                                    (string) $withdrawal[
                                                        'processed_at'
                                                    ]
                                                )
                                            )
                                        ) ?>
                                    </span>

                                </div>

                            </div>

                        <?php endif; ?>


                        <?php if (
                            !empty($withdrawal['processor_name'])
                        ): ?>

                            <div class="timeline-item">

                                <div class="timeline-dot"></div>

                                <div>

                                    <strong>
                                        Processed By
                                    </strong>

                                    <span>
                                        <?= e(
                                            (string) $withdrawal[
                                                'processor_name'
                                            ]
                                        ) ?>
                                    </span>

                                </div>

                            </div>

                        <?php endif; ?>

                    </div>

                </section>


                <!-- Admin Note -->

                <section class="panel">

                    <div class="panel-header">

                        <div>
                            <h2>Admin Note</h2>
                            <p>
                                Internal note attached to this request.
                            </p>
                        </div>

                    </div>


                    <div class="note-box">

                        <?php if (
                            !empty($withdrawal['admin_note'])
                        ): ?>

                            <?= nl2br(
                                e(
                                    (string) $withdrawal['admin_note']
                                )
                            ) ?>

                        <?php else: ?>

                            <span class="muted">
                                No admin note has been added.
                            </span>

                        <?php endif; ?>

                    </div>

                </section>

            </div>


            <!-- =================================================
                 RIGHT
                 ================================================= -->

            <aside class="side-column">

                <!-- User Wallet -->

                <section class="panel">

                    <div class="panel-header">

                        <div>
                            <h2>User Wallet</h2>
                        </div>

                    </div>


                    <div class="wallet-stat">

                        <span>Current Balance</span>

                        <strong>
                            $<?= number_format(
                                $userBalance,
                                2
                            ) ?>
                        </strong>

                    </div>


                    <div class="wallet-row">

                        <span>Total Earned</span>

                        <strong>
                            $<?= number_format(
                                $userTotalEarned,
                                2
                            ) ?>
                        </strong>

                    </div>


                    <div class="wallet-row">

                        <span>Pending</span>

                        <strong>
                            $<?= number_format(
                                $userPendingBalance,
                                2
                            ) ?>
                        </strong>

                    </div>

                </section>


                <!-- Reservation -->

                <section class="panel">

                    <div class="panel-header">

                        <div>
                            <h2>Wallet Reservation</h2>
                        </div>

                    </div>


                    <?php if ($reservation): ?>

                        <div class="reservation-box">

                            <span>
                                Reserved Amount
                            </span>

                            <strong>
                                $<?= number_format(
                                    abs(
                                        (float) $reservation['amount']
                                    ),
                                    2
                                ) ?>
                            </strong>

                            <small>
                                Wallet transaction
                                #<?= (int) $reservation['id'] ?>
                            </small>

                        </div>

                    <?php else: ?>

                        <div class="warning-box">
                            Original wallet reservation not found.
                        </div>

                    <?php endif; ?>


                    <?php if ($refundTransaction): ?>

                        <div class="refund-box">

                            <strong>
                                Refund Already Issued
                            </strong>

                            <span>
                                $<?= number_format(
                                    (float) $refundTransaction['amount'],
                                    2
                                ) ?>
                            </span>

                            <small>
                                Transaction
                                #<?= (int) $refundTransaction['id'] ?>
                            </small>

                        </div>

                    <?php endif; ?>

                </section>


                <!-- Actions -->

                <?php if (
                    in_array(
                        $status,
                        ['PENDING', 'PROCESSING'],
                        true
                    )
                ): ?>

                    <section class="panel action-panel">

                        <div class="panel-header">

                            <div>
                                <h2>Admin Actions</h2>
                                <p>
                                    These actions change financial state.
                                </p>
                            </div>

                        </div>


                        <?php if ($status === 'PENDING'): ?>

                            <form
                                method="post"
                                class="action-form"
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= e($csrfToken) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="PROCESS"
                                >

                                <label>
                                    Admin Note

                                    <textarea
                                        name="admin_note"
                                        rows="4"
                                        placeholder="Optional processing note..."
                                    ></textarea>

                                </label>

                                <button
                                    type="submit"
                                    class="action-btn process-btn"
                                >
                                    Move to Processing
                                </button>

                            </form>

                        <?php endif; ?>


                        <?php if ($status === 'PROCESSING'): ?>

                            <form
                                method="post"
                                class="action-form"
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= e($csrfToken) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="PAY"
                                >

                                <label>

                                    Transaction Reference
                                    <span class="required">*</span>

                                    <input
                                        type="text"
                                        name="transaction_reference"
                                        maxlength="190"
                                        required
                                        placeholder="Payment transaction ID..."
                                    >

                                </label>


                                <label>

                                    Admin Note

                                    <textarea
                                        name="admin_note"
                                        rows="4"
                                        placeholder="Optional payment note..."
                                    ></textarea>

                                </label>


                                <button
                                    type="submit"
                                    class="action-btn pay-btn"
                                >
                                    Mark as Paid
                                </button>

                            </form>

                        <?php endif; ?>


                        <div class="danger-divider"></div>


                        <form
                            method="post"
                            class="action-form"
                            onsubmit="return confirm(
                                'Reject this withdrawal and refund the reserved amount to the user?'
                            );"
                        >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e($csrfToken) ?>"
                            >

                            <input
                                type="hidden"
                                name="action"
                                value="REJECT"
                            >

                            <label>

                                Rejection / Refund Note

                                <textarea
                                    name="admin_note"
                                    rows="4"
                                    placeholder="Reason for rejecting this withdrawal..."
                                    required
                                ></textarea>

                            </label>


                            <button
                                type="submit"
                                class="action-btn reject-btn"
                            >
                                Reject &amp; Refund
                            </button>

                        </form>

                    </section>

                <?php endif; ?>


                <!-- Completed State -->

                <?php if ($status === 'PAID'): ?>

                    <section class="panel completed-panel">

                        <div class="completed-icon">
                            ✓
                        </div>

                        <h3>
                            Payment Completed
                        </h3>

                        <p>
                            This withdrawal has already been marked
                            as paid.
                        </p>


                        <?php if (
                            !empty(
                                $withdrawal['transaction_reference']
                            )
                        ): ?>

                            <div class="reference-box">

                                <span>
                                    Transaction Reference
                                </span>

                                <strong>
                                    <?= e(
                                        (string) $withdrawal[
                                            'transaction_reference'
                                        ]
                                    ) ?>
                                </strong>

                            </div>

                        <?php endif; ?>

                    </section>

                <?php endif; ?>


                <!-- Rejected State -->

                <?php if ($status === 'REJECTED'): ?>

                    <section class="panel rejected-panel">

                        <div class="rejected-icon">
                            !
                        </div>

                        <h3>
                            Withdrawal Rejected
                        </h3>

                        <p>
                            This request has been rejected.
                        </p>


                        <?php if ($refundTransaction): ?>

                            <div class="reference-box">

                                <span>
                                    Refund Issued
                                </span>

                                <strong>
                                    $<?= number_format(
                                        (float) $refundTransaction[
                                            'amount'
                                        ],
                                        2
                                    ) ?>
                                </strong>

                            </div>

                        <?php endif; ?>

                    </section>

                <?php endif; ?>

            </aside>

        </div>

    </main>

</div>

</body>
</html>
