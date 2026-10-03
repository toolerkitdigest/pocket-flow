<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$isLoggedIn = isLoggedIn();

$pageTitle = 'PoketFlow — Turn Your Free Time Into Rewards';
$pageDescription = 'Discover real opportunities, complete simple activities and earn rewards with PoketFlow.';

require_once __DIR__ . '/includes/header.php';
?>

<link rel="stylesheet" href="assets/home.css">
<link rel="stylesheet" href="assets/featured-offers.css">

<div class="home-page">

    <!-- =====================================================
         HEADER
    ====================================================== -->
    <header class="home-header">

        <div class="home-container home-header-inner">

            <a href="index.php" class="home-brand" aria-label="PoketFlow Home">

                <span class="home-brand-mark">P</span>

                <span class="home-brand-name">
                    Poket<span>Flow</span>
                </span>

            </a>


            <!-- DESKTOP NAVIGATION -->
            <nav class="home-desktop-nav" aria-label="Main navigation">

                <a href="index.php" class="active">
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


    <main>


        <!-- =================================================
             HERO
        ================================================== -->
        <section class="home-hero">

            <div class="home-container">

                <div class="home-hero-content">

                    <div class="home-eyebrow">
                        REAL OPPORTUNITIES • REAL REWARDS
                    </div>


                    <h1>
                        Turn Your Free Time
                        <span>Into Rewards.</span>
                    </h1>


                    <p>
                        Discover real opportunities, complete simple
                        activities and build your reward balance —
                        all from one PoketFlow account.
                    </p>


                    <div class="home-hero-actions">

                        <a
                            href="register.php"
                            class="home-btn home-btn-primary home-btn-large"
                        >
                            Start Earning
                            <span>→</span>
                        </a>


                        <a
                            href="#how-it-works"
                            class="home-btn home-btn-outline home-btn-large"
                        >
                            See How It Works
                        </a>

                    </div>


                    <div class="home-hero-note">

                        <span>
                            ✓
                        </span>
                        Free to join

                        <span>
                            ✓
                        </span>
                        No subscription

                        <span>
                            ✓
                        </span>
                        New opportunities regularly

                    </div>

                </div>

            </div>

        </section>


        <!-- =================================================
             REAL LIVE OFFERS
             THIS APPEARS IMMEDIATELY AFTER HERO
        ================================================== -->

        <?php
        require_once __DIR__ . '/includes/hero-offers.php';
        ?>


        <!-- =================================================
             PLATFORM ACTIVITY
        ================================================== -->

        <section class="pf-activity-stats">

            <div class="home-container">

                <div class="pf-activity-heading">

                    <span class="home-kicker">
                        THE POKETFLOW COMMUNITY
                    </span>

                    <h2>
                        Rewards in motion.
                    </h2>

                    <p>
                        PoketFlow brings opportunities and rewards
                        together in one simple platform.
                    </p>

                </div>


                <div class="pf-activity-grid">

                    <div class="pf-activity-card">

                        <div class="pf-activity-icon">
                            👥
                        </div>

                        <div class="pf-count-number" data-count-type="users">
                            0
                        </div>

                        <div class="pf-activity-label">
                            Registered Users
                        </div>

                    </div>


                    <div class="pf-activity-card">

                        <div class="pf-activity-icon">
                            💰
                        </div>

                        <div class="pf-count-number" data-count-type="rewards">
                            $0
                        </div>

                        <div class="pf-activity-label">
                            Rewards Processed
                        </div>

                    </div>

                </div>

            </div>

        </section>


        <!-- =================================================
             HOW IT WORKS
        ================================================== -->

        <section
            class="home-section"
            id="how-it-works"
        >

            <div class="home-container">

                <div class="home-section-heading">

                    <span class="home-kicker">
                        HOW IT WORKS
                    </span>

                    <h2>
                        Start earning in three simple steps.
                    </h2>

                    <p>
                        No complicated setup. Create your account,
                        choose an opportunity and complete the
                        required activity.
                    </p>

                </div>


                <div class="home-steps">


                    <article class="home-step-card">

                        <div class="home-step-number">
                            01
                        </div>

                        <div class="home-step-icon">
                            👤
                        </div>

                        <h3>
                            Create Your Account
                        </h3>

                        <p>
                            Join PoketFlow for free and create your
                            personal rewards account.
                        </p>

                    </article>


                    <article class="home-step-card">

                        <div class="home-step-number">
                            02
                        </div>

                        <div class="home-step-icon">
                            🔎
                        </div>

                        <h3>
                            Choose an Opportunity
                        </h3>

                        <p>
                            Browse available offers and select an
                            opportunity that interests you.
                        </p>

                    </article>


                    <article class="home-step-card">

                        <div class="home-step-number">
                            03
                        </div>

                        <div class="home-step-icon">
                            🎁
                        </div>

                        <h3>
                            Complete & Earn
                        </h3>

                        <p>
                            Follow the offer requirements and receive
                            your reward after the activity is verified.
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
                        A simpler way to discover rewards.
                    </h2>

                    <p>
                        PoketFlow is designed to make discovering
                        and completing reward opportunities simple.
                    </p>

                </div>


                <div class="home-benefits-grid">


                    <article class="home-benefit-card">

                        <div class="home-benefit-icon">
                            ⚡
                        </div>

                        <h3>
                            Fresh Opportunities
                        </h3>

                        <p>
                            Discover available opportunities from
                            our connected offer networks.
                        </p>

                    </article>


                    <article class="home-benefit-card">

                        <div class="home-benefit-icon">
                            💎
                        </div>

                        <h3>
                            Clear Rewards
                        </h3>

                        <p>
                            See the reward associated with an offer
                            before deciding which opportunity to explore.
                        </p>

                    </article>


                    <article class="home-benefit-card">

                        <div class="home-benefit-icon">
                            🛡️
                        </div>

                        <h3>
                            One Account
                        </h3>

                        <p>
                            Keep your opportunities, rewards and
                            account activity together in one place.
                        </p>

                    </article>


                    <article class="home-benefit-card">

                        <div class="home-benefit-icon">
                            📱
                        </div>

                        <h3>
                            Built for Mobile
                        </h3>

                        <p>
                            Discover and complete opportunities
                            conveniently from your phone or computer.
                        </p>

                    </article>


                </div>

            </div>

        </section>


        <!-- =================================================
             FINAL CTA
        ================================================== -->

        <section class="home-cta">

            <div class="home-container">

                <div class="home-cta-inner">

                    <span class="home-kicker">
                        READY TO GET STARTED?
                    </span>

                    <h2>
                        Your next opportunity
                        could be waiting.
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
                            <span>→</span>
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

        <section class="home-section home-faq">

            <div class="home-container">

                <div class="home-section-heading">

                    <span class="home-kicker">
                        FAQ
                    </span>

                    <h2>
                        Frequently asked questions.
                    </h2>

                </div>


                <div class="home-faq-list">


                    <details class="home-faq-item">

                        <summary>
                            What is PoketFlow?
                        </summary>

                        <p>
                            PoketFlow is a rewards platform where
                            members can discover available opportunities,
                            complete qualifying activities and receive
                            rewards when those activities are verified.
                        </p>

                    </details>


                    <details class="home-faq-item">

                        <summary>
                            Is it free to join?
                        </summary>

                        <p>
                            Yes. Creating a PoketFlow account is free.
                            You can browse available opportunities and
                            choose the ones you want to explore.
                        </p>

                    </details>


                    <details class="home-faq-item">

                        <summary>
                            How do rewards work?
                        </summary>

                        <p>
                            Each opportunity has its own requirements.
                            When you complete the required activity and
                            the network confirms the conversion, the
                            applicable reward can be credited to your
                            PoketFlow account.
                        </p>

                    </details>


                    <details class="home-faq-item">

                        <summary>
                            Can I use PoketFlow on my phone?
                        </summary>

                        <p>
                            Yes. PoketFlow is designed to work across
                            modern phones, tablets and desktop devices.
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
     MOBILE MENU SCRIPT
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

        menuButton.classList.remove('mobile-menu-open');

        mobileNavigation.classList.remove('is-open');

        menuButton.setAttribute(
            'aria-expanded',
            'false'
        );

    }


    function openMobileMenu() {

        menuButton.classList.add('mobile-menu-open');

        mobileNavigation.classList.add('is-open');

        menuButton.setAttribute(
            'aria-expanded',
            'true'
        );

    }


    menuButton.addEventListener(
        'click',
        function () {

            const isOpen =
                mobileNavigation.classList.contains('is-open');

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
