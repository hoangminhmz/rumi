<?php
/**
 * TEST SWIPE FROM UI
 * Page to test swipe with debug API
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/User.php';

startSession();

if (!isLoggedIn()) {
    $_SESSION['user_id'] = 1;
}

$userModel = new User();
$users = $userModel->getPotentialMatches(getCurrentUserId(), 3);
$currentUser = $userModel->getById(getCurrentUserId());

$pageTitle = 'Test Swipe with Debug';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link href="<?= ASSETS_URL ?>/css/style.css" rel="stylesheet">
    <link href="<?= ASSETS_URL ?>/css/components.css" rel="stylesheet">
    <style>
        body { padding: 2rem; max-width: 800px; margin: 0 auto; }
        .debug-panel {
            background: #f3f4f6;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            font-family: monospace;
            font-size: 0.9rem;
            max-height: 400px;
            overflow-y: auto;
        }
        .card-test {
            border: 2px solid #e5e7eb;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        .btn-test {
            padding: 0.75rem 1.5rem;
            margin: 0.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
        }
        .btn-like { background: #00D4AA; color: white; }
        .btn-nope { background: #ef4444; color: white; }
    </style>
</head>
<body>
    <h1>🔍 Test Swipe with Debug API</h1>

    <div style="background: #fef3c7; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
        <strong>⚠️ This page uses DEBUG API</strong><br>
        You'll see detailed logs of what happens when you swipe.
    </div>

    <div class="card-test">
        <h3>Your Info</h3>
        <p>User ID: <?= getCurrentUserId() ?></p>
        <p>Name: <?= e($currentUser['name']) ?></p>
        <p>Session: <?= session_id() ?></p>
    </div>

    <?php if (empty($users)): ?>
        <p>No users to swipe. <a href="reset-swipes.php">Reset swipes</a></p>
    <?php else: ?>
        <?php foreach ($users as $user): ?>
        <div class="card-test" id="user-<?= $user['id'] ?>">
            <h2><?= e($user['name']) ?>, <?= $user['age'] ?></h2>
            <p>User ID: <?= $user['id'] ?></p>
            <p><?= e($user['district_name']) ?>, <?= e($user['city_name']) ?></p>
            <p><?= e($user['bio']) ?></p>

            <div>
                <button class="btn-test btn-like" onclick="testSwipe(<?= $user['id'] ?>, true)">
                    ❤️ LIKE
                </button>
                <button class="btn-test btn-nope" onclick="testSwipe(<?= $user['id'] ?>, false)">
                    ❌ NOPE
                </button>
            </div>

            <div class="debug-panel" id="debug-<?= $user['id'] ?>">
                <div style="color: #6b7280;">Waiting for swipe...</div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div style="margin-top: 2rem; padding-top: 1rem; border-top: 2px solid #e5e7eb;">
        <h3>After Testing:</h3>
        <a href="reset-swipes.php" class="btn btn-primary">Check Reset Swipes Page</a>
        <a href="test-swipe-minimal.php" class="btn btn-primary">Check Database</a>
    </div>

    <script>
        const API_URL = '<?= BASE_URL ?>/api';

        async function testSwipe(targetId, isLike) {
            const debugPanel = document.getElementById('debug-' + targetId);
            debugPanel.innerHTML = '<div style="color: #f59e0b;">⏳ Sending request...</div>';

            try {
                const endpoint = `${API_URL}/swipe-user-debug.php`;
                const data = {
                    target_id: targetId,
                    is_like: isLike
                };

                console.log('Sending to:', endpoint);
                console.log('Data:', data);

                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                console.log('Response status:', response.status);

                const result = await response.json();
                console.log('Response data:', result);

                // Display debug log
                if (result.data && result.data.debug) {
                    let html = '<div style="color: ' + (result.success ? '#059669' : '#dc2626') + '; font-weight: bold; margin-bottom: 0.5rem;">';
                    html += result.success ? '✅ SUCCESS' : '❌ FAILED';
                    html += '</div>';

                    result.data.debug.forEach(log => {
                        html += '<div>' + log + '</div>';
                    });

                    debugPanel.innerHTML = html;
                } else {
                    debugPanel.innerHTML = '<pre>' + JSON.stringify(result, null, 2) + '</pre>';
                }

                // Hide card if success
                if (result.success) {
                    setTimeout(() => {
                        document.getElementById('user-' + targetId).style.opacity = '0.3';
                    }, 2000);

                    if (result.data.matched) {
                        alert('🎉 IT\'S A MATCH!');
                    }
                }

            } catch (error) {
                console.error('Error:', error);
                debugPanel.innerHTML = '<div style="color: #dc2626;">❌ ERROR: ' + error.message + '</div>';
            }
        }
    </script>
</body>
</html>
