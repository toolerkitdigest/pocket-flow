<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$isLoggedIn = isLoggedIn();

$pageTitle = 'PoketFlow — Turn Your Free Time Into Rewards';
$pageDescription = 'Discover real opportunities, complete simple activities and earn rewards with PoketFlow.';

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <meta
        name="description"
        content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>"
    >

    <meta
        name="theme-color"
        content="#080d1a"
    >

    <link
        rel="stylesheet"
        href="assets/home.css"
    >

    <link
        rel="stylesheet"
        href="assets/featured-offers.css"
    >

</head>


<body>

<div class="home-page">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <header class="home-header">

        <div class="home-container home-header-inner">

            <!-- BRAND -->

            <a
                href="index.php"
                class="home-brand"
                aria-label="PoketFlow Home"
            >

                <span class="home-brand-mark">
                    P
                </span>

                <span class="home-brand-name">
                    Poket<span>Flow</span>
                </span>

            </a>


            <!-- DESKTOP NAVIGATION -->

            <nav
                class="home-desktop-nav"
                aria-label="Main navigation"
            >

                <a
                    href="index.php"
                    class="active"
                >
                    Home
                </a>

                <a href="offers.php">
                    Earn Rewards
                </a>

                <a href="how-it-works.php">
                    How It Works
                </a>

                <a href="blog/">
                    Blog
                </a>

            </nav>


            <!-- DESKTOP ACTIONS -->

            <div class="home-header-actions">

                <?php if ($isLoggedIn): ?>

                    <a
                        href="dashboard.php"
                        class="home-btn home-btn-outline"
                    >
                        Dashboard
                    </a>

                    <a
                        href="logout.php"
                        class="home-btn home-btn-primary"
                    >
                        Logout
                    </a>

                <?php else: ?>

                    <a
                        href="login.php"
                        class="home-btn home-btn-outline"
                    >
                        Login
                    </a>

                    <a
                        href="register.php"
                        class="home-btn home-btn-primary"
                    >
                        Get Started
                    </a>

                <?php endif; ?>

            </div>


            <!-- MOBILE MENU BUTTON -->

            <button
                type="button"
                class="home-mobile-menu-button"
                id="mobileMenuButton"
                aria-label="Open navigation menu"
                aria-expanded="false"
                aria-controls="mobile-navigation"
            >

                <span></span>
                <span></span>
                <span></span>

            </button>

        </div>


        <!-- MOBILE NAVIGATION -->

        <div
            class="home-mobile-navigation"
            id="mobile-navigation"
        >

            <nav aria-label="Mobile navigation">

                <a href="index.php">
                    Home
                </a>

                <a href="offers.php">
                    Earn Rewards
                </a>

                <a href="how-it-works.php">
                    How It Works
                </a>

                <a href="blog/">
                    Blog
                </a>


                <div class="home-mobile-actions">

                    <?php if ($isLoggedIn): ?>

                        <a
                            href="dashboard.php"
                            class="home-btn home-btn-outline"
                        >
                            Dashboard
                        </a>

                        <a
                            href="logout.php"
                            class="home-btn home-btn-primary"
                        >
                            Logout
                        </a>

                    <?php else: ?>

                        <a
                            href="login.php"
                            class="home-btn home-btn-outline"
                        >
                            Login
                        </a>

                        <a
                            href="register.php"
                            class="home-btn home-btn-primary"
                        >
                            Get Started
                        </a>

                    <?php endif; ?>

                </div>

            </nav>

        </div>

    </header>



    <!-- =====================================================
         MAIN
    ====================================================== -->

    <main>


        <!-- =================================================
             HERO
        ================================================== -->

        <section class="home-hero">

            <div class="home-hero-glow home-hero-glow-one"></div>
            <div class="home-hero-glow home-hero-glow-two"></div>

            <div class="home-hero-grid"></div>

            <div class="home-container">

                <div class="home-hero-content">

                    <div class="home-eyebrow">

                        <span class="home-eyebrow-dot"></span>

                        REAL OPPORTUNITIES
                        
                        <span class="home-eyebrow-divider">•</span>

                        REAL REWARDS

                    </div>


                    <h1>

                        Turn Your Free Time

                        <span>
                            Into Rewards.
                        </span>

                    </h1>


                    <p class="home-hero-description">

                        Discover opportunities from trusted offer
                        networks, complete simple activities and
                        earn rewards when your activity is verified.

                    </p>


                    <div class="home-hero-actions">

                        <a
                            href="register.php"
                            class="home-btn home-btn-primary home-btn-large"
                        >

                            Start Earning

                            <span class="home-btn-arrow">
                                →
                            </span>

                        </a>


                        <a
                            href="#how-it-works"
                            class="home-btn home-btn-outline home-btn-large"
                        >

                            How It Works

                        </a>

                    </div>


                    <div class="home-hero-trust">

                        <span>
                            <strong>✓</strong>
                            Free to join
                        </span>

                        <span>
                            <strong>✓</strong>
                            No subscription
                        </span>

                        <span>
                            <strong>✓</strong>
                            Opportunities vary by location
                        </span>

                    </div>

                </div>

            </div>

        </section>



        <!-- =================================================
             LIVE NETWORK OFFERS
        ================================================== -->

        <?php

        require_once __DIR__ . '/includes/hero-offers.php';

        ?>



        <!-- =================================================
             HOW IT WORKS
        ================================================== -->

        <section
            class="home-section home-how"
            id="how-it-works"
        >

            <div class="home-container">


                <div class="home-section-heading">

                    <span class="home-kicker">
                        HOW IT WORKS
                    </span>

                    <h2>
                        Three simple steps.
                        <span>One rewarding journey.</span>
                    </h2>

                    <p>
                        PoketFlow keeps the process simple. Create
                        your account, choose an opportunity and
                        complete the required activity.
                    </p>

                </div>


                <div class="home-steps">


                    <!-- STEP 1 -->

                    <article class="home-step-card">

                        <div class="home-step-top">

                            <span class="home-step-number">
                                01
                            </span>

                            <span class="home-step-line"></span>

                        </div>


                        <div class="home-step-icon">
                            <span>+</span>
                        </div>


                        <h3>
                            Create Your Account
                        </h3>


                        <p>
                            Join PoketFlow for free and create
                            your personal rewards account.
                        </p>

                    </article>



                    <!-- STEP 2 -->

                    <article class="home-step-card">

                        <div class="home-step-top">

                            <span class="home-step-number">
                                02
                            </span>

                            <span class="home-step-line"></span>

                        </div>


                        <div class="home-step-icon">
                            <span>⌕</span>
                        </div>


                        <h3>
                            Find an Opportunity
                        </h3>


                        <p>
                            Browse available opportunities and
                            choose one that fits your interests
                            and eligibility.
                        </p>

                    </article>



                    <!-- STEP 3 -->

                    <article class="home-step-card">

                        <div class="home-step-top">

                            <span class="home-step-number">
                                03
                            </span>

                            <span class="home-step-line"></span>

                        </div>


                        <div class="home-step-icon">
                            <span>✓</span>
                        </div>


                        <h3>
                            Complete & Earn
                        </h3>


                        <p>
                            Follow the requirements and receive
                            your applicable reward after the
                            activity is verified.
                        </p>

                    </article>


                </div>

            </div>

        </section>



        <!-- =================================================
             FEATURED OPPORTUNITIES
        ================================================== -->

        <?php

        require_once __DIR__ . '/includes/featured-offers.php';

        ?>



        <!-- =================================================
             BENEFITS
        ================================================== -->

        <section class="home-section home-benefits">

            <div class="home-container">


                <div class="home-section-heading">

                    <span class="home-kicker">
                        WHY POKETFLOW
                    </span>

                    <h2>
                        Everything you need to
                        <span>discover opportunities.</span>
                    </h2>

                    <p>
                        A straightforward rewards experience built
                        around discovery, clarity and convenience.
                    </p>

                </div>


                <div class="home-benefits-grid">


                    <article class="home-benefit-card">

                        <div class="home-benefit-icon">
                            <span>↗</span>
                        </div>

                        <h3>
                            Fresh Opportunities
                        </h3>

                        <p>
                            Explore available opportunities from
                            connected offer networks.
                        </p>

                    </article>



                    <article class="home-benefit-card">

                        <div class="home-benefit-icon">
                            <span>$</span>
                        </div>

                        <h3>
                            Clear Rewards
                        </h3>

                        <p>
                            See the applicable reward before you
                            decide which opportunity to explore.
                        </p>

                    </article>



                    <article class="home-benefit-card">

                        <div class="home-benefit-icon">
                            <span>✓</span>
                        </div>

                        <h3>
                            One Account
                        </h3>

                        <p>
                            Keep your opportunities, activity and
                            reward history together in one place.
                        </p>

                    </article>



                    <article class="home-benefit-card">

                        <div class="home-benefit-icon">
                            <span>⌁</span>
                        </div>

                        <h3>
                            Built for Mobile
                        </h3>

                        <p>
                            Discover and complete opportunities
                            comfortably from your phone or desktop.
                        </p>

                    </article>


                </div>

            </div>

        </section>



        <!-- =================================================
             CTA
        ================================================== -->

        <section class="home-cta">

            <div class="home-cta-glow"></div>

            <div class="home-container">

                <div class="home-cta-inner">

                    <div class="home-cta-badge">
                        READY WHEN YOU ARE
                    </div>


                    <h2>
                        Your next opportunity
                        <span>could be waiting.</span>
                    </h2>


                    <p>
                        Create your free PoketFlow account and
                        start exploring available opportunities.
                    </p>


                    <div class="home-hero-actions">

                        <a
                            href="register.php"
                            class="home-btn home-btn-primary home-btn-large"
                        >

                            Create Free Account

                            <span class="home-btn-arrow">
                                →
                            </span>

                        </a>


                        <a
                            href="offers.php"
                            class="home-btn home-btn-outline home-btn-large"
                        >

                            Browse Opportunities

                        </a>

                    </div>

                </div>

            </div>

        </section>



        <!-- =================================================
             FAQ
        ================================================== -->

        <section
            class="home-section home-faq"
            id="faq"
        >

            <div class="home-container">


                <div class="home-section-heading">

                    <span class="home-kicker">
                        FAQ
                    </span>

                    <h2>
                        Questions, answered.
                    </h2>

                    <p>
                        A few things you may want to know before
                        getting started.
                    </p>

                </div>


                <div class="home-faq-list">


                    <details class="home-faq-item">

                        <summary>

                            <span>
                                What is PoketFlow?
                            </span>

                            <span class="home-faq-plus">
                                +
                            </span>

                        </summary>

                        <p>
                            PoketFlow is a rewards platform where
                            members can discover available
                            opportunities, complete qualifying
                            activities and receive applicable rewards
                            when those activities are verified.
                        </p>

                    </details>



                    <details class="home-faq-item">

                        <summary>

                            <span>
                                Is it free to join?
                            </span>

                            <span class="home-faq-plus">
                                +
                            </span>

                        </summary>

                        <p>
                            Yes. Creating a PoketFlow account is free.
                            You can explore available opportunities
                            and choose the ones you want to complete.
                        </p>

                    </details>



                    <details class="home-faq-item">

                        <summary>

                            <span>
                                How do rewards work?
                            </span>

                            <span class="home-faq-plus">
                                +
                            </span>

                        </summary>

                        <p>
                            Each opportunity has its own requirements.
                            When you complete the required activity
                            and the connected network confirms the
                            conversion, the applicable reward can be
                            credited to your PoketFlow account.
                        </p>

                    </details>



                    <details class="home-faq-item">

                        <summary>

                            <span>
                                Can I use PoketFlow on my phone?
                            </span>

                            <span class="home-faq-plus">
                                +
                            </span>

                        </summary>

                        <p>
                            Yes. PoketFlow is designed to work across
                            modern smartphones, tablets and desktop
                            devices.
                        </p>

                    </details>


                </div>

            </div>

        </section>


    </main>



    <!-- =====================================================
         FOOTER
    ====================================================== -->

    <footer class="home-footer">

        <div class="home-container">


            <div class="home-footer-grid">


                <div class="home-footer-brand">

                    <a
                        href="index.php"
                        class="home-brand"
                    >

                        <span class="home-brand-mark">
                            P
                        </span>

                        <span class="home-brand-name">
                            Poket<span>Flow</span>
                        </span>

                    </a>


                    <p>
                        Discover opportunities.
                        Complete activities.
                        Earn rewards.
                    </p>

                </div>



                <div class="home-footer-column">

                    <h4>
                        Platform
                    </h4>

                    <a href="offers.php">
                        Earn Rewards
                    </a>

                    <a href="how-it-works.php">
                        How It Works
                    </a>

                    <a href="register.php">
                        Create Account
                    </a>

                    <a href="login.php">
                        Login
                    </a>

                </div>



                <div class="home-footer-column">

                    <h4>
                        Resources
                    </h4>

                    <a href="blog/">
                        Blog
                    </a>

                    <a href="#how-it-works">
                        Getting Started
                    </a>

                    <a href="#faq">
                        FAQ
                    </a>

                </div>



                <div class="home-footer-column">

                    <h4>
                        Legal
                    </h4>

                    <a href="privacy.php">
                        Privacy Policy
                    </a>

                    <a href="terms.php">
                        Terms of Service
                    </a>

                    <a href="contact.php">
                        Contact
                    </a>

                </div>


            </div>


            <div class="home-footer-bottom">

                <p>
                    © <?= date('Y') ?> PoketFlow.
                    All rights reserved.
                </p>

                <p>
                    Opportunities may vary by location,
                    device and eligibility.
                </p>

            </div>

        </div>

    </footer>


</div>



<!-- =====================================================
     MOBILE NAVIGATION
====================================================== -->

<script>

document.addEventListener('DOMContentLoaded', function () {

    const menuButton =
        document.getElementById('mobileMenuButton');

    const mobileNavigation =
        document.getElementById('mobile-navigation');


    if (!menuButton || !mobileNavigation) {
        return;
    }


    function closeMobileMenu() {

        menuButton.classList.remove(
            'mobile-menu-open'
        );

        mobileNavigation.classList.remove(
            'is-open'
        );

        menuButton.setAttribute(
            'aria-expanded',
            'false'
        );

    }


    function openMobileMenu() {

        menuButton.classList.add(
            'mobile-menu-open'
        );

        mobileNavigation.classList.add(
            'is-open'
        );

        menuButton.setAttribute(
            'aria-expanded',
            'true'
        );

    }


    menuButton.addEventListener(
        'click',
        function () {

            const isOpen =
                mobileNavigation.classList.contains(
                    'is-open'
                );

            if (isOpen) {
                closeMobileMenu();
            } else {
                openMobileMenu();
            }

        }
    );


    mobileNavigation
        .querySelectorAll('a')
        .forEach(function (link) {

            link.addEventListener(
                'click',
                closeMobileMenu
            );

        });


    document.addEventListener(
        'keydown',
        function (event) {

            if (event.key === 'Escape') {
                closeMobileMenu();
            }

        }
    );


    window.addEventListener(
        'resize',
        function () {

            if (window.innerWidth > 850) {
                closeMobileMenu();
            }

        }
    );

});

</script>


</body>
</html>
