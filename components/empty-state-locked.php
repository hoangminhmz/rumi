<?php
/**
 * RUMI - Empty State Locked Tab Component
 * Shows when user needs to complete prerequisite before accessing tab
 */

/**
 * Render locked tab empty state
 * @param string $userMode User's search mode (find_roommate_first or find_room_first)
 * @param string $currentTab Current tab being viewed (roommate or room)
 */
function renderLockedTabState($userMode, $currentTab) {
    if ($userMode === 'find_roommate_first' && $currentTab === 'room') {
        // User chose to find roommate first, but hasn't matched yet
        ?>
        <div class="locked-tab-state">
            <div class="lock-container">
                <svg class="lock-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>

            <h3 class="lock-title">🔒 Tab phòng đang bị khóa</h3>

            <p class="lock-message">
                Bạn cần <strong>match với roommate</strong> trước khi xem danh sách phòng
            </p>

            <p class="lock-reason">
                💡 Lý do: Chúng tôi sẽ gợi ý phòng phù hợp với cả bạn và roommate của bạn
            </p>

            <a href="?mode=find_roommate" class="btn btn-primary btn-lg">
                <svg style="width: 20px; height: 20px; margin-right: 8px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                Tìm roommate ngay
            </a>

            <div class="lock-steps">
                <h4>Cách hoạt động:</h4>
                <ol>
                    <li>Swipe để tìm roommate phù hợp</li>
                    <li>Match với người bạn thích</li>
                    <li>Xem phòng phù hợp với cả hai</li>
                </ol>
            </div>
        </div>

        <style>
        .locked-tab-state {
            max-width: 500px;
            margin: 4rem auto;
            padding: 2rem;
            text-align: center;
        }

        .lock-container {
            width: 120px;
            height: 120px;
            margin: 0 auto 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
        }

        .lock-icon {
            width: 60px;
            height: 60px;
            color: white;
        }

        .lock-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--color-gray-900);
            margin-bottom: 1rem;
        }

        .lock-message {
            font-size: 1.1rem;
            color: var(--color-gray-700);
            margin-bottom: 1rem;
            line-height: 1.6;
        }

        .lock-message strong {
            color: var(--color-primary);
        }

        .lock-reason {
            background: #f8f9fa;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            border-left: 4px solid var(--color-primary);
            color: var(--color-gray-600);
            margin: 1.5rem 0;
            text-align: left;
        }

        .btn-lg {
            padding: 1rem 2rem;
            font-size: 1.1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 1rem 0;
        }

        .lock-steps {
            margin-top: 2rem;
            padding: 1.5rem;
            background: white;
            border-radius: 12px;
            border: 2px dashed #e5e7eb;
            text-align: left;
        }

        .lock-steps h4 {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--color-gray-600);
            margin-bottom: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .lock-steps ol {
            margin: 0;
            padding-left: 1.5rem;
            color: var(--color-gray-700);
        }

        .lock-steps li {
            margin-bottom: 0.5rem;
            line-height: 1.6;
        }

        @media (max-width: 640px) {
            .locked-tab-state {
                padding: 1rem;
                margin: 2rem auto;
            }

            .lock-container {
                width: 100px;
                height: 100px;
            }

            .lock-icon {
                width: 50px;
                height: 50px;
            }

            .lock-title {
                font-size: 1.25rem;
            }

            .lock-message {
                font-size: 1rem;
            }
        }
        </style>
        <?php
    }
    elseif ($userMode === 'find_room_first' && $currentTab === 'roommate') {
        // User chose to find room first, but hasn't liked any rooms yet
        ?>
        <div class="locked-tab-state">
            <div class="lock-container">
                <svg class="lock-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>

            <h3 class="lock-title">🔒 Tab roommate đang bị khóa</h3>

            <p class="lock-message">
                Bạn cần <strong>like ít nhất 1 phòng</strong> trước khi tìm roommate
            </p>

            <p class="lock-reason">
                💡 Lý do: Chúng tôi sẽ gợi ý những người cũng thích phòng đó
            </p>

            <a href="?mode=find_room" class="btn btn-primary btn-lg">
                <svg style="width: 20px; height: 20px; margin-right: 8px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Tìm phòng ngay
            </a>

            <div class="lock-steps">
                <h4>Cách hoạt động:</h4>
                <ol>
                    <li>Swipe để tìm phòng ưng ý</li>
                    <li>Like những phòng bạn thích</li>
                    <li>Gặp những người cũng thích phòng đó</li>
                </ol>
            </div>
        </div>

        <style>
        .locked-tab-state {
            max-width: 500px;
            margin: 4rem auto;
            padding: 2rem;
            text-align: center;
        }

        .lock-container {
            width: 120px;
            height: 120px;
            margin: 0 auto 2rem;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 40px rgba(245, 87, 108, 0.3);
        }

        .lock-icon {
            width: 60px;
            height: 60px;
            color: white;
        }

        .lock-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--color-gray-900);
            margin-bottom: 1rem;
        }

        .lock-message {
            font-size: 1.1rem;
            color: var(--color-gray-700);
            margin-bottom: 1rem;
            line-height: 1.6;
        }

        .lock-message strong {
            color: #f5576c;
        }

        .lock-reason {
            background: #fff5f7;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            border-left: 4px solid #f5576c;
            color: var(--color-gray-600);
            margin: 1.5rem 0;
            text-align: left;
        }

        .btn-lg {
            padding: 1rem 2rem;
            font-size: 1.1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 1rem 0;
        }

        .lock-steps {
            margin-top: 2rem;
            padding: 1.5rem;
            background: white;
            border-radius: 12px;
            border: 2px dashed #e5e7eb;
            text-align: left;
        }

        .lock-steps h4 {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--color-gray-600);
            margin-bottom: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .lock-steps ol {
            margin: 0;
            padding-left: 1.5rem;
            color: var(--color-gray-700);
        }

        .lock-steps li {
            margin-bottom: 0.5rem;
            line-height: 1.6;
        }

        @media (max-width: 640px) {
            .locked-tab-state {
                padding: 1rem;
                margin: 2rem auto;
            }

            .lock-container {
                width: 100px;
                height: 100px;
            }

            .lock-icon {
                width: 50px;
                height: 50px;
            }

            .lock-title {
                font-size: 1.25rem;
            }

            .lock-message {
                font-size: 1rem;
            }
        }
        </style>
        <?php
    }
}
