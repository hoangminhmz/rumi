<?php
/**
 * RUMI - Bottom Navigation (Mobile-first)
 * Tab bar navigation cho mobile
 */

$currentPage = getCurrentPage();
?>
<nav class="navbar">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/pages/swipe.php" class="nav-link <?= $currentPage === 'swipe' ? 'active' : '' ?>">
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
                <span>Swipe</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="<?= BASE_URL ?>/pages/matches.php" class="nav-link <?= $currentPage === 'matches' ? 'active' : '' ?>">
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span>Matches</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="<?= BASE_URL ?>/pages/post-room.php" class="nav-link <?= $currentPage === 'post-room' ? 'active' : '' ?>">
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Đăng phòng</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="<?= BASE_URL ?>/pages/profile.php" class="nav-link <?= $currentPage === 'profile' ? 'active' : '' ?>">
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span>Cá nhân</span>
            </a>
        </li>
    </ul>
</nav>

<!-- Add padding to body so content doesn't hide behind navbar -->
<style>
    body {
        padding-bottom: 70px;
    }

    /* Fix navbar layout */
    .navbar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        width: 100%;
        background: white;
        border-top: 1px solid #e5e7eb;
        box-shadow: 0 -2px 12px rgba(0, 0, 0, 0.08);
        z-index: 1000;
    }

    .navbar-nav {
        display: flex !important;
        flex-direction: row !important;
        justify-content: space-around;
        align-items: center;
        list-style: none;
        padding: 0.5rem 0;
        margin: 0;
        width: 100%;
    }

    .nav-item {
        flex: 1;
        text-align: center;
        display: flex;
        justify-content: center;
    }

    .nav-link {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.25rem;
        padding: 0.5rem;
        color: #6b7280;
        text-decoration: none;
        transition: color 0.2s;
    }

    .nav-link.active {
        color: #00D4AA;
    }

    .nav-icon {
        width: 24px;
        height: 24px;
    }

    .nav-link span {
        font-size: 0.75rem;
    }

    @media (min-width: 768px) {
        body {
            padding-bottom: 0;
            padding-top: 70px;
        }

        .navbar {
            position: sticky;
            top: 0;
            bottom: auto;
            border-top: none;
            border-bottom: 1px solid #e5e7eb;
        }

        .navbar-nav {
            justify-content: flex-end;
            gap: 1rem;
            padding: 1rem 1.5rem;
        }

        .nav-item {
            flex: none;
        }
    }
</style>
