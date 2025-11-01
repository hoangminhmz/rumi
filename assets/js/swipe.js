/**
 * RUMI - Swipe Functionality
 * Tinder-style swipe với Hammer.js
 */

(function() {
    'use strict';

    // Configuration
    const SWIPE_THRESHOLD = 120; // pixels to trigger swipe
    const ROTATION_ANGLE = 15; // max rotation in degrees

    let currentCardIndex = 0;
    let cards = [];

    /**
     * Initialize swipe functionality
     */
    function initSwipe() {
        const cardElements = document.querySelectorAll('.swipe-card');
        if (cardElements.length === 0) {
            console.log('No cards to swipe');
            return;
        }

        cards = Array.from(cardElements).reverse(); // Reverse so first card is on top
        currentCardIndex = 0;

        // Position cards
        cards.forEach((card, index) => {
            card.style.zIndex = cards.length - index;
            if (index > 0) {
                card.style.transform = `scale(${1 - (index * 0.05)}) translateY(${index * 10}px)`;
                card.style.opacity = 1 - (index * 0.2);
            }
        });

        // Add swipe to current card
        if (cards[currentCardIndex]) {
            addSwipeListeners(cards[currentCardIndex]);
        }
    }

    /**
     * Add Hammer.js swipe listeners to card
     */
    function addSwipeListeners(card) {
        const hammer = new Hammer(card);

        hammer.on('pan', function(event) {
            if (event.deltaX === 0) return;

            card.classList.add('swiping');

            const xMulti = event.deltaX * 0.03;
            const yMulti = event.deltaY / 80;
            const rotate = xMulti * yMulti;

            card.style.transform = `translate(${event.deltaX}px, ${event.deltaY}px) rotate(${rotate}deg)`;

            // Show like/nope overlay
            const likeOverlay = card.querySelector('.swipe-overlay.like');
            const nopeOverlay = card.querySelector('.swipe-overlay.nope');

            if (event.deltaX > 50) {
                card.classList.add('swiping-right');
                card.classList.remove('swiping-left');
                if (likeOverlay) likeOverlay.style.opacity = Math.min(event.deltaX / SWIPE_THRESHOLD, 1);
                if (nopeOverlay) nopeOverlay.style.opacity = 0;
            } else if (event.deltaX < -50) {
                card.classList.add('swiping-left');
                card.classList.remove('swiping-right');
                if (nopeOverlay) nopeOverlay.style.opacity = Math.min(Math.abs(event.deltaX) / SWIPE_THRESHOLD, 1);
                if (likeOverlay) likeOverlay.style.opacity = 0;
            } else {
                card.classList.remove('swiping-left', 'swiping-right');
                if (likeOverlay) likeOverlay.style.opacity = 0;
                if (nopeOverlay) nopeOverlay.style.opacity = 0;
            }
        });

        hammer.on('panend', function(event) {
            card.classList.remove('swiping');

            const moveOutWidth = document.body.clientWidth * 1.5;
            const isLike = event.deltaX > SWIPE_THRESHOLD;
            const isNope = event.deltaX < -SWIPE_THRESHOLD;

            if (isLike) {
                swipeCard(card, 'right');
            } else if (isNope) {
                swipeCard(card, 'left');
            } else {
                // Return to position
                card.style.transform = '';
                card.classList.remove('swiping-left', 'swiping-right');
                const overlays = card.querySelectorAll('.swipe-overlay');
                overlays.forEach(overlay => overlay.style.opacity = 0);
            }
        });
    }

    /**
     * Swipe card in direction
     */
    function swipeCard(card, direction) {
        const isLike = direction === 'right';
        const moveOutWidth = document.body.clientWidth * 1.5;

        card.classList.add('removed');
        card.style.transform = `translateX(${isLike ? moveOutWidth : -moveOutWidth}px) rotate(${isLike ? ROTATION_ANGLE : -ROTATION_ANGLE}deg)`;

        // Get card data
        const userId = card.getAttribute('data-user-id');
        const roomId = card.getAttribute('data-room-id');

        // Send swipe to server
        handleSwipe(userId, roomId, isLike);

        // Move to next card
        setTimeout(() => {
            card.remove();
            currentCardIndex++;

            if (currentCardIndex < cards.length) {
                addSwipeListeners(cards[currentCardIndex]);
            } else {
                showNoMoreCards();
            }
        }, 300);
    }

    /**
     * Handle swipe action - send to API
     */
    async function handleSwipe(userId, roomId, isLike) {
        try {
            const endpoint = window.SEARCH_MODE === 'find_roommate'
                ? `${window.API_URL}/swipe-user.php`
                : `${window.API_URL}/swipe-room.php`;

            const data = {
                target_id: userId || roomId,
                is_like: isLike
            };

            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success && result.data.matched) {
                showMatchModal(result.data.match);
            }

        } catch (error) {
            console.error('Swipe error:', error);
        }
    }

    /**
     * Show match modal
     */
    function showMatchModal(matchData) {
        const modal = document.getElementById('matchModal');
        if (!modal) return;

        const avatar1 = document.getElementById('matchAvatar1');
        const avatar2 = document.getElementById('matchAvatar2');
        const matchName = document.getElementById('matchName');

        if (avatar1) avatar1.src = matchData.user1_avatar || `${window.ASSETS_URL}/images/default-avatar.png`;
        if (avatar2) avatar2.src = matchData.user2_avatar || `${window.ASSETS_URL}/images/default-avatar.png`;
        if (matchName) matchName.textContent = matchData.matched_user_name;

        modal.style.display = 'flex';
    }

    /**
     * Close match modal
     */
    window.closeMatchModal = function() {
        const modal = document.getElementById('matchModal');
        if (modal) {
            modal.style.display = 'none';
        }
    };

    /**
     * Show no more cards message
     */
    function showNoMoreCards() {
        const container = document.getElementById('swipeCards');
        if (container) {
            container.innerHTML = `
                <div class="empty-state">
                    <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="empty-title">Hết cards rồi!</h3>
                    <p class="empty-text">Quay lại sau để xem thêm match mới nhé</p>
                    <a href="${window.location.pathname}" class="btn btn-primary">Tải lại</a>
                </div>
            `;
        }
    }

    /**
     * Button click handlers
     */
    function initButtons() {
        const btnLike = document.getElementById('btnLike');
        const btnNope = document.getElementById('btnNope');

        if (btnLike) {
            btnLike.addEventListener('click', function() {
                if (cards[currentCardIndex]) {
                    swipeCard(cards[currentCardIndex], 'right');
                }
            });
        }

        if (btnNope) {
            btnNope.addEventListener('click', function() {
                if (cards[currentCardIndex]) {
                    swipeCard(cards[currentCardIndex], 'left');
                }
            });
        }
    }

    /**
     * Initialize on DOM ready
     */
    document.addEventListener('DOMContentLoaded', function() {
        initSwipe();
        initButtons();
    });

})();
