<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$userId = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| User
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT
        id,
        name,
        email,
        country,
        status
     FROM users
     WHERE id = ?
     LIMIT 1'
);

$stmt->execute([$userId]);

$user = $stmt->fetch();

if (!$user) {
    $_SESSION = [];
    session_destroy();

    redirect('login.php');
}

/*
|--------------------------------------------------------------------------
| Wallet
|--------------------------------------------------------------------------
*/

$availableBalance = getUserBalance(
    $pdo,
    $userId
);

$minimumWithdrawal = (float) getSetting(
    $pdo,
    'minimum_withdrawal',
    '5.00'
);

$canWithdraw = $availableBalance >= $minimumWithdrawal;

/*
|--------------------------------------------------------------------------
| Country
|--------------------------------------------------------------------------
*/

$userCountry = strtoupper(
    trim((string) ($user['country'] ?? ''))
);

$isNigeria = in_array(
    $userCountry,
    [
        'NG',
        'NIGERIA',
    ],
    true
);

/*
|--------------------------------------------------------------------------
| Withdrawal methods
|--------------------------------------------------------------------------
*/

$withdrawalMethods = [];

if ($isNigeria) {

    $withdrawalMethods[] = [
        'id' => 'MONIEPOINT',
        'name' => 'Moniepoint',
        'description' => 'Receive your reward through your Nigerian account.',
        'icon' => '🇳🇬',
        'type' => 'bank',
    ];

    $withdrawalMethods[] = [
        'id' => 'PALMPAY',
        'name' => 'PalmPay',
        'description' => 'Receive your reward through your Nigerian account.',
        'icon' => '🇳🇬',
        'type' => 'bank',
    ];
}

$withdrawalMethods[] = [
    'id' => 'PAYPAL',
    'name' => 'PayPal',
    'description' => 'Receive your reward through your PayPal account.',
    'icon' => '💳',
    'type' => 'paypal',
];

$withdrawalMethods[] = [
    'id' => 'USDT',
    'name' => 'USDT',
    'description' => 'Receive your reward in Tether.',
    'icon' => '₮',
    'type' => 'crypto',
];

$withdrawalMethods[] = [
    'id' => 'USDC',
    'name' => 'USDC',
    'description' => 'Receive your reward in USD Coin.',
    'icon' => '💵',
    'type' => 'crypto',
];

/*
|--------------------------------------------------------------------------
| Supported crypto networks
|--------------------------------------------------------------------------
|
| We are starting with a small controlled list.
| We can expand this later after the payment
| workflow is fully tested.
|
*/

$cryptoNetworks = [
    'USDT' => [
        'TRC20',
        'ERC20',
    ],

    'USDC' => [
        'ERC20',
        'Polygon',
    ],
];

?>
<!doctype html>
<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width,initial-scale=1"
    >

    <title>Withdraw — PoketFlow</title>

    <link
        rel="stylesheet"
        href="assets/poketflow.css"
    >

    <style>

        .withdraw-methods {
            display: grid;
            gap: 12px;
            margin-top: 20px;
        }

        .withdraw-method {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px;
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 14px;
            background: rgba(255,255,255,.025);
            cursor: pointer;
            transition: .2s ease;
        }

        .withdraw-method:hover {
            background: rgba(255,255,255,.05);
            border-color: rgba(99,102,241,.45);
        }

        .withdraw-method input {
            accent-color: #6366f1;
        }

        .withdraw-method-icon {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            background: rgba(99,102,241,.12);
            font-size: 20px;
            flex-shrink: 0;
        }

        .withdraw-method-info {
            flex: 1;
        }

        .withdraw-method-name {
            display: block;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .withdraw-method-description {
            display: block;
            color: var(--text-muted, #718096);
            font-size: 13px;
            line-height: 1.5;
        }

        .withdraw-form {
            margin-top: 28px;
            padding: 24px;
            border-radius: 18px;
            background: var(--surface, #111827);
            border: 1px solid rgba(255,255,255,.07);
        }

        .withdraw-form h2 {
            margin-top: 0;
            margin-bottom: 20px;
        }

        .withdraw-field {
            margin-bottom: 18px;
        }

        .withdraw-field label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .withdraw-field input,
        .withdraw-field select {
            width: 100%;
            box-sizing: border-box;
            padding: 13px 14px;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,.1);
            background: #0c1324;
            color: #f8fafc;
        }

        .withdraw-field small {
            display: block;
            margin-top: 7px;
            color: #718096;
            line-height: 1.5;
        }

        .withdraw-notice {
            padding: 14px 16px;
            margin-top: 20px;
            border-radius: 12px;
            background: rgba(34,211,238,.07);
            border: 1px solid rgba(34,211,238,.18);
            color: #a7b1c2;
            font-size: 14px;
            line-height: 1.6;
        }

        .withdraw-warning {
            padding: 14px 16px;
            margin-top: 20px;
            border-radius: 12px;
            background: rgba(245,158,11,.08);
            border: 1px solid rgba(245,158,11,.2);
            color: #fbbf24;
            font-size: 14px;
            line-height: 1.6;
        }

        .withdraw-disabled {
            opacity: .6;
            pointer-events: none;
        }

        .withdraw-hidden {
            display: none;
        }

    </style>

</head>

<body class="app-page">

<header class="app-header">

    <a class="brand" href="index.html">

        <span class="brand-mark">P</span>

        <span>
            Poket<span>Flow</span>
        </span>

    </a>

    <nav>

        <a href="dashboard.php">
            Home
        </a>

        <a href="offers.php">
            Earn
        </a>

        <a href="history.php">
            History
        </a>

        <a href="referrals.php">
            Refer & Earn
        </a>

        <a class="active" href="withdraw.php">
            Withdraw
        </a>

    </nav>

</header>

<main class="app-shell">

    <aside class="sidebar">

        <div class="sidebar-nav">

            <a href="dashboard.php">
                ⌂ <span>Home</span>
            </a>

            <a href="offers.php">
                ▦ <span>Offers</span>
            </a>

            <a href="history.php">
                ◷ <span>History</span>
            </a>

            <a href="referrals.php">
                ♧ <span>Refer & Earn</span>
            </a>

            <a class="active" href="withdraw.php">
                ▣ <span>Withdraw</span>
            </a>

        </div>

    </aside>

    <section class="app-content">

        <span class="kicker">
            REWARDS
        </span>

        <h1>
            Withdraw
        </h1>

        <p class="lead">
            Choose how you would like to receive your PoketFlow rewards.
        </p>

        <div class="withdraw-card">

            <div>

                <span>
                    Available balance
                </span>

                <strong>
                    $<?= number_format(
                        $availableBalance,
                        2
                    ) ?>
                </strong>

            </div>

            <small>

                Minimum withdrawal:
                $<?= number_format(
                    $minimumWithdrawal,
                    2
                ) ?>.

                <?php if ($canWithdraw): ?>

                    You are eligible to request a withdrawal.

                <?php else: ?>

                    You need
                    $<?= number_format(
                        max(
                            0,
                            $minimumWithdrawal - $availableBalance
                        ),
                        2
                    ) ?>
                    more to reach the minimum withdrawal amount.

                <?php endif; ?>

            </small>

        </div>

        <div class="withdraw-form">

            <h2>
                Withdrawal method
            </h2>

            <?php if (!$canWithdraw): ?>

                <div class="withdraw-warning">

                    Your available balance has not yet reached the
                    minimum withdrawal amount.

                    Keep completing eligible offers to increase
                    your balance.

                </div>

            <?php endif; ?>

            <div
                class="<?= !$canWithdraw
                    ? 'withdraw-methods withdraw-disabled'
                    : 'withdraw-methods' ?>"
            >

                <?php foreach (
                    $withdrawalMethods
                    as $method
                ): ?>

                    <label class="withdraw-method">

                        <input
                            type="radio"
                            name="withdrawal_method"
                            value="<?= e($method['id']) ?>"
                            data-type="<?= e($method['type']) ?>"
                        >

                        <span class="withdraw-method-icon">
                            <?= e($method['icon']) ?>
                        </span>

                        <span class="withdraw-method-info">

                            <span class="withdraw-method-name">
                                <?= e($method['name']) ?>
                            </span>

                            <span class="withdraw-method-description">
                                <?= e($method['description']) ?>
                            </span>

                        </span>

                    </label>

                <?php endforeach; ?>

            </div>

            <div
                id="withdraw-details"
                class="<?= !$canWithdraw
                    ? 'withdraw-hidden withdraw-disabled'
                    : 'withdraw-hidden' ?>"
            >

                <h2>
                    Payment details
                </h2>

                <!-- Bank details -->

                <div
                    id="bank-fields"
                    class="withdraw-hidden"
                >

                    <div class="withdraw-field">

                        <label for="account_name">
                            Account name
                        </label>

                        <input
                            type="text"
                            id="account_name"
                            name="account_name"
                            autocomplete="name"
                            placeholder="Enter account name"
                        >

                    </div>

                    <div class="withdraw-field">

                        <label for="account_number">
                            Account number
                        </label>

                        <input
                            type="text"
                            id="account_number"
                            name="account_number"
                            inputmode="numeric"
                            placeholder="Enter account number"
                        >

                    </div>

                </div>

                <!-- PayPal -->

                <div
                    id="paypal-fields"
                    class="withdraw-hidden"
                >

                    <div class="withdraw-field">

                        <label for="paypal_email">
                            PayPal email
                        </label>

                        <input
                            type="email"
                            id="paypal_email"
                            name="paypal_email"
                            autocomplete="email"
                            placeholder="you@example.com"
                        >

                    </div>

                </div>

                <!-- Crypto -->

                <div
                    id="crypto-fields"
                    class="withdraw-hidden"
                >

                    <div class="withdraw-field">

                        <label for="crypto_network">
                            Network
                        </label>

                        <select
                            id="crypto_network"
                            name="crypto_network"
                        >

                            <option value="">
                                Select network
                            </option>

                        </select>

                        <small>
                            Make sure the network matches your
                            receiving wallet.
                        </small>

                    </div>

                    <div class="withdraw-field">

                        <label for="wallet_address">
                            Wallet address
                        </label>

                        <input
                            type="text"
                            id="wallet_address"
                            name="wallet_address"
                            autocomplete="off"
                            placeholder="Enter wallet address"
                        >

                    </div>

                    <div class="withdraw-warning">

                        Cryptocurrency transactions are generally
                        irreversible. Sending funds to an incorrect
                        address or network may result in permanent
                        loss of funds.

                    </div>

                </div>

                <!-- Amount -->

                <div class="withdraw-field">

                    <label for="withdrawal_amount">
                        Withdrawal amount
                    </label>

                    <input
                        type="number"
                        id="withdrawal_amount"
                        name="withdrawal_amount"
                        min="<?= e(
                            number_format(
                                $minimumWithdrawal,
                                2,
                                '.',
                                ''
                            )
                        ) ?>"
                        max="<?= e(
                            number_format(
                                $availableBalance,
                                2,
                                '.',
                                ''
                            )
                        ) ?>"
                        step="0.01"
                        placeholder="0.00"
                    >

                    <small>

                        Minimum:
                        $<?= number_format(
                            $minimumWithdrawal,
                            2
                        ) ?>

                        ·

                        Available:
                        $<?= number_format(
                            $availableBalance,
                            2
                        ) ?>

                    </small>

                </div>

                <div class="withdraw-notice">

                    Withdrawals are reviewed by PoketFlow before
                    payment is released. Processing time may vary
                    depending on the selected payment method.

                </div>

                <button
                    class="btn btn-primary"
                    type="button"
                    id="review-withdrawal"
                >
                    Review Withdrawal
                </button>

            </div>

        </div>

    </section>

</main>

<script>

const methodInputs =
    document.querySelectorAll(
        'input[name="withdrawal_method"]'
    );

const details =
    document.getElementById(
        'withdraw-details'
    );

const bankFields =
    document.getElementById(
        'bank-fields'
    );

const paypalFields =
    document.getElementById(
        'paypal-fields'
    );

const cryptoFields =
    document.getElementById(
        'crypto-fields'
    );

const cryptoNetwork =
    document.getElementById(
        'crypto_network'
    );

const cryptoNetworks =
    <?= json_encode(
        $cryptoNetworks,
        JSON_UNESCAPED_SLASHES
        | JSON_UNESCAPED_UNICODE
    ) ?>;

methodInputs.forEach(
    function (input) {

        input.addEventListener(
            'change',
            function () {

                details.classList.remove(
                    'withdraw-hidden'
                );

                bankFields.classList.add(
                    'withdraw-hidden'
                );

                paypalFields.classList.add(
                    'withdraw-hidden'
                );

                cryptoFields.classList.add(
                    'withdraw-hidden'
                );

                cryptoNetwork.innerHTML =
                    '<option value="">Select network</option>';

                if (
                    input.dataset.type === 'bank'
                ) {

                    bankFields.classList.remove(
                        'withdraw-hidden'
                    );

                }

                if (
                    input.dataset.type === 'paypal'
                ) {

                    paypalFields.classList.remove(
                        'withdraw-hidden'
                    );

                }

                if (
                    input.dataset.type === 'crypto'
                ) {

                    cryptoFields.classList.remove(
                        'withdraw-hidden'
                    );

                    const networks =
                        cryptoNetworks[input.value]
                        || [];

                    networks.forEach(
                        function (network) {

                            const option =
                                document.createElement(
                                    'option'
                                );

                            option.value =
                                network;

                            option.textContent =
                                network;

                            cryptoNetwork.appendChild(
                                option
                            );

                        }
                    );

                }

            }
        );

    }
);

</script>

</body>

</html>
