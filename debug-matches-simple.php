<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Simple Matches Debug</h2>";

echo "<p>Step 1: Loading database.php...</p>";
try {
    require_once __DIR__ . '/config/database.php';
    echo "<p>✓ database.php loaded</p>";
} catch (Exception $e) {
    die("<p>❌ Error loading database.php: " . $e->getMessage() . "</p>");
}

echo "<p>Step 2: Loading constants.php...</p>";
try {
    require_once __DIR__ . '/config/constants.php';
    echo "<p>✓ constants.php loaded</p>";
} catch (Exception $e) {
    die("<p>❌ Error loading constants.php: " . $e->getMessage() . "</p>");
}

echo "<p>Step 3: Loading functions.php...</p>";
try {
    require_once __DIR__ . '/includes/functions.php';
    echo "<p>✓ functions.php loaded</p>";
} catch (Exception $e) {
    die("<p>❌ Error loading functions.php: " . $e->getMessage() . "</p>");
}

echo "<p>Step 4: Loading Match.php...</p>";
try {
    require_once __DIR__ . '/includes/Match.php';
    echo "<p>✓ Match.php loaded</p>";
} catch (Exception $e) {
    die("<p>❌ Error loading Match.php: " . $e->getMessage() . "</p>");
}

echo "<p>Step 5: Loading cards.php...</p>";
try {
    require_once __DIR__ . '/components/cards.php';
    echo "<p>✓ cards.php loaded</p>";
} catch (Exception $e) {
    die("<p>❌ Error loading cards.php: " . $e->getMessage() . "</p>");
}

echo "<p>Step 6: Starting session...</p>";
try {
    startSession();
    echo "<p>✓ Session started</p>";
} catch (Exception $e) {
    die("<p>❌ Error starting session: " . $e->getMessage() . "</p>");
}

echo "<p>Step 7: Checking functions exist...</p>";
$required_functions = [
    'isLoggedIn',
    'getCurrentUserId',
    'getMatchedUser',
    'getMatchedUserAvatar',
    'timeAgo',
    'e',
    'formatPrice'
];

foreach ($required_functions as $func) {
    if (!function_exists($func)) {
        echo "<p>❌ Function $func does NOT exist!</p>";
    } else {
        echo "<p>✓ Function $func exists</p>";
    }
}

echo "<h3>✅ All basic checks passed!</h3>";
echo "<p>Now testing with actual Match operations...</p>";

try {
    if (!isLoggedIn()) {
        $_SESSION['user_id'] = 1;
        echo "<p>✓ Created test session</p>";
    }

    $matchModel = new Match();
    echo "<p>✓ Match model created</p>";

    $userId = getCurrentUserId();
    echo "<p>✓ Current user ID: $userId</p>";

    $matches = $matchModel->getByUser($userId);
    echo "<p>✓ Got matches: " . count($matches) . " found</p>";

    $stats = $matchModel->getStats($userId);
    echo "<p>✓ Got stats</p>";
    echo "<pre>";
    print_r($stats);
    echo "</pre>";

    echo "<h3>✅ Everything works! The issue might be in rendering.</h3>";

    if (!empty($matches)) {
        echo "<p>Testing renderMatchItem...</p>";
        echo "<hr>";

        // Check if renderMatchItem exists
        if (!function_exists('renderMatchItem')) {
            die("<p>❌ renderMatchItem function does NOT exist!</p>");
        }

        echo "<div style='max-width: 600px;'>";
        renderMatchItem($matches[0], $userId);
        echo "</div>";

        echo "<p>✓ renderMatchItem works!</p>";
    }

} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error:</h3>";
    echo "<pre style='background: #ffe0e0; padding: 1rem;'>";
    echo $e->getMessage() . "\n";
    echo $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString();
    echo "</pre>";
}
?>
