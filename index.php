<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| PoketFlow Homepage
|--------------------------------------------------------------------------
| Premium dynamic homepage.
| Real featured offers are loaded through:
| includes/featured-offers.php
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/auth.php';

?>
<!doctype html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        PoketFlow — Turn Your Free Time Into Rewards
    </title>

    <meta
        name="description"
        content="Discover real offers and online activities, complete requirements, earn rewards, and cash out when eligible."
    >

    <meta
        name="theme-color"
        content="#080d1a"
    >

    <!--
    |--------------------------------------------------------------------------
    | Homepage CSS
    |--------------------------------------------------------------------------
    -->

    <link
        rel="stylesheet"
        href="assets/home.css"
    >

    <link
        rel="stylesheet"
        href="assets/featured-offers.css"
    >

</head>


<body class="home-page">


<!-- =========================================================
     HEADER
========================================================= -->

<header class="home-header">

    <div class="home-container home-header-inner">


        <!-- =================================================
             BRAND
        ================================================== -->

        <a
            class="home-brand"
            href="index.php"
            aria-label="PoketFlow Home"
        >

            <span class="home-brand-mark">
                P
            </span>

            <span class="home-brand-name">
                Poket<span>Flow</span>
            </span>

        </a>


        <!-- =================================================
             DESKTOP NAVIGATION
        ================================================== -->

        <nav
            class="home-desktop-nav"
            aria-label="Main navigation"
        >

            <a
                class="active"
                href="index.php"
            >
                Home
            </a>

            <a href="offers.php">
                Earn
            </a>

            <a href="#how-it-works">
                How It Works
            </a>

            <a href="#featured-offers">
                Opportunities
            </a>

            <a href="#faq">
                FAQ
            </a>

        </nav>


        <!-- =================================================
             DESKTOP ACTIONS
        ================================================== -->

        <div class="home-header-actions">

            <a
                class="home-btn home-btn-ghost"
                href="login.php"
            >
                Sign In
            </a>

            <a
                class="home-btn home-btn-primary"
                href="register.php"
            >
                Get Started

                <span aria-hidden="true">
                    →
                </span>

            </a>

        </div>


        <!-- =================================================
             MOBILE MENU BUTTON
        ================================================== -->

        <button
            class="home-mobile-menu-button"
            type="button"
            aria-label="Open navigation menu"
            aria-expanded="false"
            aria-controls="mobile-navigation"
            id="mobileMenuButton"
        >

            <span></span>
            <span></span>
            <span></span>

        </button>

    </div>


    <!-- =====================================================
         MOBILE NAVIGATION
    ====================================================== -->

    <div
        class="home-mobile-navigation"
        id="mobile-navigation"
        aria-hidden="true"
    >

        <div class="home-container home-mobile-navigation-inner">


            <nav
                class="home-mobile-nav"
                aria-label="Mobile navigation"
            >

                <a
                    class="active"
                    href="index.php"
                >
                    <span>⌂</span>
                    Home
                </a>

                <a href="offers.php">
                    <span>◎</span>
                    Earn
                </a>

                <a href="#how-it-works">
                    <span>01</span>
                    How It Works
                </a>

                <a href="#featured-offers">
                    <span>✦</span>
                    Opportunities
                </a>

                <a href="#faq">
                    <span>?</span>
                    FAQ
                </a>

            </nav>


            <div class="home-mobile-actions">

                <a
                    class="home-btn home-btn-outline"
                    href="login.php"
                >
                    Sign In
                </a>

                <a
                    class="home-btn home-btn-primary"
                    href="register.php"
                >
                    Get Started

                    <span aria-hidden="true">
                        →
                    </span>

                </a>

            </div>

        </div>

    </div>

</header>


<!-- =========================================================
     MAIN
========================================================= -->

<main>


<!-- =========================================================
     HERO
========================================================= -->

<section class="home-hero">

    <div class="home-container home-hero-grid">


        <!-- =================================================
             HERO CONTENT
        ================================================== -->

        <div class="home-hero-content">


            <div class="home-eyebrow">

                <span class="home-eyebrow-dot"></span>

                REAL OFFERS
                <span>•</span>
                REAL REWARDS
                <span>•</span>
                FREE TO JOIN

            </div>


            <h1>

                Turn Your Free Time
                <span>Into Rewards.</span>

            </h1>


            <p class="home-hero-description">

                Discover available offers, surveys and online
                activities matched to you. Complete the listed
                requirements and earn rewards when your activity
                is approved.

            </p>


            <div class="home-hero-actions">

                <a
                    class="home-btn home-btn-primary home-btn-large"
                    href="register.php"
                >

                    Start Earning

                    <span aria-hidden="true">
                        →
                    </span>

                </a>


                <a
                    class="home-btn home-btn-outline home-btn-large"
                    href="#how-it-works"
                >

                    See How It Works

                </a>

            </div>


            <div class="home-hero-note">

                <span>✓</span>
                Free account

                <span>✓</span>
                No subscription

                <span>✓</span>
                Browse available offers

            </div>


        </div>


        <!-- =================================================
             HERO VISUAL
        ================================================== -->

        <div class="home-hero-visual">


            <div class="home-hero-glow home-glow-one"></div>

            <div class="home-hero-glow home-glow-two"></div>


            <!-- Main dashboard preview -->

            <div class="home-dashboard-preview">


                <!-- Dashboard header -->

                <div class="home-dashboard-top">

                    <div class="home-mini-brand">

                        <span>
                            P
                        </span>

                        <strong>
                            PoketFlow
                        </strong>

                    </div>

                    <div class="home-dashboard-menu">
                        •••
                    </div>

                </div>


                <!-- Balance -->

                <div class="home-balance-card">

                    <div class="home-balance-top">

                        <span>
                            Available Balance
                        </span>

                        <span>
                            ● LIVE
                        </span>

                    </div>


                    <strong>
                        $8.75
                    </strong>


                    <div class="home-balance-details">

                        <span>
                            Available
                            <b>
                                $5.20
                            </b>
                        </span>

                        <span>
                            Pending
                            <b>
                                $3.55
                            </b>
                        </span>

                    </div>


                    <div class="home-balance-progress">

                        <span></span>

                    </div>

                </div>


                <!-- Dashboard heading -->

                <div class="home-preview-heading">

                    <div>

                        <small>
                            OPPORTUNITIES
                        </small>

                        <strong>
                            Ways to earn
                        </strong>

                    </div>

                    <span>
                        View all →
                    </span>

                </div>


                <!-- Mini offer -->

                <div class="home-preview-offer">

                    <div class="home-preview-icon purple">
                        ◎
                    </div>

                    <div class="home-preview-offer-info">

                        <strong>
                            App Offer
                        </strong>

                        <small>
                            Complete the listed steps
                        </small>

                    </div>

                    <b>
                        +$2.40
                    </b>

                </div>


                <div class="home-preview-offer">

                    <div class="home-preview-icon cyan">
                        ◇
                    </div>

                    <div class="home-preview-offer-info">

                        <strong>
                            Survey
                        </strong>

                        <small>
                            Share your opinion
                        </small>

                    </div>

                    <b>
                        +$1.85
                    </b>

                </div>


                <div class="home-preview-offer">

                    <div class="home-preview-icon green">
                        ✓
                    </div>

                    <div class="home-preview-offer-info">

                        <strong>
                            Special Offer
                        </strong>

                        <small>
                            Follow offer requirements
                        </small>

                    </div>

                    <b>
                        +$3.10
                    </b>

                </div>


            </div>


            <!-- Floating card -->

            <div class="home-floating-card home-floating-one">

                <div class="home-floating-icon">
                    ✓
                </div>

                <div>

                    <strong>
                        Offer Completed
                    </strong>

                    <small>
                        Reward pending approval
                    </small>

                </div>

            </div>


            <div class="home-floating-card home-floating-two">

                <div class="home-floating-icon cyan">
                    $
                </div>

                <div>

                    <strong>
                        Rewards
                    </strong>

                    <small>
                        Build your balance
                    </small>

                </div>

            </div>


        </div>

    </div>

</section>


<!-- =========================================================
     TRUST / STATS
========================================================= -->

<section class="home-trust">

    <div class="home-container home-trust-grid">


        <div class="home-trust-item">

            <span class="home-trust-icon">
                +
            </span>

            <div>

                <strong>
                    Free to Join
                </strong>

                <small>
                    Create your account in seconds
                </small>

            </div>

        </div>


        <div class="home-trust-item">

            <span class="home-trust-icon">
                ◎
            </span>

            <div>

                <strong>
                    Multiple Opportunities
                </strong>

                <small>
                    Explore available offers
                </small>

            </div>

        </div>


        <div class="home-trust-item">

            <span class="home-trust-icon">
                ✓
            </span>

            <div>

                <strong>
                    Clear Requirements
                </strong>

                <small>
                    Know what each offer requires
                </small>

            </div>

        </div>


        <div class="home-trust-item">

            <span class="home-trust-icon">
                $
            </span>

            <div>

                <strong>
                    Track Your Rewards
                </strong>

                <small>
                    Monitor your account balance
                </small>

            </div>

        </div>


    </div>

</section>


<!-- =========================================================
     HOW IT WORKS
========================================================= -->

<section
    class="home-section"
    id="how-it-works"
>

    <div class="home-container">


        <div class="home-section-heading">

            <div>

                <span class="home-kicker">
                    HOW IT WORKS
                </span>

                <h2>
                    From Sign Up to Rewards
                </h2>

                <p>
                    PoketFlow keeps the process simple. Create an
                    account, discover available opportunities,
                    complete the requirements and track your rewards.
                </p>

            </div>


            <a
                class="home-section-link"
                href="register.php"
            >
                Create your account →
            </a>

        </div>


        <div class="home-steps">


            <article class="home-step">

                <span class="home-step-number">
                    01
                </span>

                <div class="home-step-icon">
                    +
                </div>

                <h3>
                    Create an Account
                </h3>

                <p>
                    Sign up for a free PoketFlow account and
                    access the earning area.
                </p>

            </article>


            <article class="home-step">

                <span class="home-step-number">
                    02
                </span>

                <div class="home-step-icon">
                    ◎
                </div>

                <h3>
                    Discover Offers
                </h3>

                <p>
                    Browse the opportunities currently available
                    for your account.
                </p>

            </article>


            <article class="home-step">

                <span class="home-step-number">
                    03
                </span>

                <div class="home-step-icon">
                    ✓
                </div>

                <h3>
                    Complete the Requirements
                </h3>

                <p>
                    Follow the instructions provided with the
                    selected offer.
                </p>

            </article>


            <article class="home-step">

                <span class="home-step-number">
                    04
                </span>

                <div class="home-step-icon">
                    $
                </div>

                <h3>
                    Earn Your Reward
                </h3>

                <p>
                    Once the activity is approved, the applicable
                    reward can be reflected in your account.
                </p>

            </article>


        </div>

    </div>

</section>


<!-- =========================================================
     REAL DYNAMIC FEATURED OFFERS
========================================================= -->

<?php

require_once __DIR__ . '/includes/featured-offers.php';

?>


<!-- =========================================================
     WHY POKETFLOW
========================================================= -->

<section class="home-section home-benefits-section">

    <div class="home-container">


        <div class="home-section-heading centered">

            <div>

                <span class="home-kicker">
                    WHY POKETFLOW
                </span>

                <h2>
                    Built Around Simple Reward Discovery
                </h2>

                <p>
                    A clean place to discover available opportunities
                    without unnecessary complexity.
                </p>

            </div>

        </div>


        <div class="home-benefits-grid">


            <article class="home-benefit-card">

                <div class="home-benefit-icon">
                    ✦
                </div>

                <h3>
                    Live Opportunities
                </h3>

                <p>
                    Featured opportunities can be refreshed from
                    our connected offer sources so you can discover
                    what is currently available.
                </p>

            </article>


            <article class="home-benefit-card">

                <div class="home-benefit-icon">
                    ✓
                </div>

                <h3>
                    Clear Reward Information
                </h3>

                <p>
                    See the applicable reward information before
                    deciding which available activity you want
                    to explore.
                </p>

            </article>


            <article class="home-benefit-card">

                <div class="home-benefit-icon">
                    ◈
                </div>

                <h3>
                    One Simple Dashboard
                </h3>

                <p>
                    Keep your activities, rewards and account
                    information together in one place.
                </p>

            </article>


            <article class="home-benefit-card">

                <div class="home-benefit-icon">
                    $
                </div>

                <h3>
                    Track Your Progress
                </h3>

                <p>
                    Monitor your balance and eligible rewards as
                    you complete available activities.
                </p>

            </article>


        </div>

    </div>

</section>


<!-- =========================================================
     CTA
========================================================= -->

<section class="home-final-cta">

    <div class="home-container">

        <div class="home-final-cta-inner">


            <div>

                <span class="home-kicker">
                    READY WHEN YOU ARE
                </span>


                <h2>
                    Your next reward could start with one click.
                </h2>


                <p>
                    Create your free PoketFlow account and explore
                    the opportunities currently available to you.
                </p>

            </div>


            <a
                class="home-btn home-btn-primary home-btn-large"
                href="register.php"
            >

                Get Started

                <span aria-hidden="true">
                    →
                </span>

            </a>


        </div>

    </div>

</section>


<!-- =========================================================
     FAQ
========================================================= -->

<section
    class="home-section home-faq-section"
    id="faq"
>

    <div class="home-container">


        <div class="home-section-heading centered">

            <div>

                <span class="home-kicker">
                    FAQ
                </span>

                <h2>
                    Common Questions
                </h2>

                <p>
                    A few things you may want to know before getting started.
                </p>

            </div>

        </div>


        <div class="home-faq-list">


            <details>

                <summary>

                    <span>
                        Is PoketFlow free to join?
                    </span>

                    <b>
                        +
                    </b>

                </summary>

                <p>
                    Yes. Creating a PoketFlow account does not require
                    a subscription.
                </p>

            </details>


            <details>

                <summary>

                    <span>
                        How do I earn rewards?
                    </span>

                    <b>
                        +
                    </b>

                </summary>

                <p>
                    Browse available opportunities, select an offer,
                    read its requirements carefully and complete the
                    required activity. Rewards may be subject to
                    completion approval.
                </p>

            </details>


            <details>

                <summary>

                    <span>
                        Are all offers available to everyone?
                    </span>

                    <b>
                        +
                    </b>

                </summary>

                <p>
                    Availability can vary based on factors such as
                    location, device, eligibility and the current
                    offer inventory.
                </p>

            </details>


            <details>

                <summary>

                    <span>
                        When can I cash out?
                    </span>

                    <b>
                        +
                    </b>

                </summary>

                <p>
                    Cash-out eligibility depends on your available
                    balance, PoketFlow's minimum cash-out requirement,
                    payment method and any applicable verification
                    requirements.
                </p>

            </details>


            <details>

                <summary>

                    <span>
                        Where do the offers come from?
                    </span>

                    <b>
                        +
                    </b>

                </summary>

                <p>
                    PoketFlow can display opportunities supplied by
                    connected offer networks and approved advertising
                    partners. Availability may change over time.
                </p>

            </details>


        </div>

    </div>

</section>


</main>


<!-- =========================================================
     FOOTER
========================================================= -->

<footer class="home-footer">

    <div class="home-container">


        <div class="home-footer-main">


            <div class="home-footer-brand">

                <a
                    class="home-brand"
                    href="index.php"
                >

                    <span class="home-brand-mark">
                        P
                    </span>

                    <span class="home-brand-name">
                        Poket<span>Flow</span>
                    </span>

                </a>


                <p>
                    Discover available online opportunities,
                    complete activities and track your rewards.
                </p>

            </div>


            <div class="home-footer-column">

                <h4>
                    Platform
                </h4>

                <a href="offers.php">
                    Earn Rewards
                </a>

                <a href="register.php">
                    Create Account
                </a>

                <a href="login.php">
                    Sign In
                </a>

                <a href="#how-it-works">
                    How It Works
                </a>

            </div>


            <div class="home-footer-column">

                <h4>
                    Information
                </h4>

                <a href="#faq">
                    FAQ
                </a>

                <a href="#">
                    Terms
                </a>

                <a href="#">
                    Privacy
                </a>

                <a href="#">
                    Contact
                </a>

            </div>


        </div>


        <div class="home-footer-bottom">

            <span>
                © 2026 PoketFlow. All rights reserved.
            </span>

            <span>
                Built for simple reward discovery.
            </span>

        </div>


    </div>

</footer>


<!-- =========================================================
     MOBILE MENU JAVASCRIPT
========================================================= -->

<script>

(function () {

    'use strict';


    const menuButton =
        document.getElementById('mobileMenuButton');


    const mobileNavigation =
        document.getElementById('mobile-navigation');


    if (!menuButton || !mobileNavigation) {

        return;

    }


    const mobileLinks =
        mobileNavigation.querySelectorAll('a');


    function openMenu() {

        document.body.classList.add(
            'mobile-menu-open'
        );

        menuButton.classList.add(
            'is-open'
        );

        mobileNavigation.classList.add(
            'is-open'
        );

        menuButton.setAttribute(
            'aria-expanded',
            'true'
        );

        mobileNavigation.setAttribute(
            'aria-hidden',
            'false'
        );

    }


    function closeMenu() {

        document.body.classList.remove(
            'mobile-menu-open'
        );

        menuButton.classList.remove(
            'is-open'
        );

        mobileNavigation.classList.remove(
            'is-open'
        );

        menuButton.setAttribute(
            'aria-expanded',
            'false'
        );

        mobileNavigation.setAttribute(
            'aria-hidden',
            'true'
        );

    }


    function toggleMenu() {

        const isOpen =
            menuButton.getAttribute(
                'aria-expanded'
            ) === 'true';


        if (isOpen) {

            closeMenu();

        } else {

            openMenu();

        }

    }


    menuButton.addEventListener(
        'click',
        toggleMenu
    );


    mobileLinks.forEach(function (link) {

        link.addEventListener(
            'click',
            closeMenu
        );

    });


    document.addEventListener(
        'keydown',
        function (event) {

            if (event.key === 'Escape') {

                closeMenu();

            }

        }
    );


    window.addEventListener(
        'resize',
        function () {

            if (window.innerWidth > 850) {

                closeMenu();

            }

        }
    );


})();

</script>


</body>

</html>
