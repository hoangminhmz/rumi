<?php
/**
 * Debug file cho matches.php
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Testing Matches Page Components</h2>";

try {
    echo "<p>✓ Step 1: Loading config files...</p>";
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/config/constants.php';
    require_once __DIR__ . '/includes/functions.php';
    require_once __DIR__ . '/includes/Match.php';
    require_once __DIR__ . '/components/cards.php';
    echo "<p>✓ All files loaded successfully</p>";

    echo "<p>✓ Step 2: Starting session...</p>";
    startSession();
    echo "<p>✓ Session started</p>";

    // Check if logged in
    echo "<p>Step 3: Checking login status...</p>";
    if (!isLoggedIn()) {
        echo "<p style='color: orange;'>⚠ Not logged in. Creating test session...</p>";
        $_SESSION['user_id'] = 1; // Use dummy user 1
        echo "<p>✓ Test session created with user_id = 1</p>";
    } else {
        echo "<p>✓ Already logged in. User ID: " . getCurrentUserId() . "</p>";
    }

    echo "<p>Step 4: Creating Match model...</p>";
    $matchModel = new Match();
    echo "<p>✓ Match model created</p>";

    $userId = getCurrentUserId();
    echo "<p>Current User ID: $userId</p>";

    echo "<p>Step 5: Getting matches...</p>";
    $matches = $matchModel->getByUser($userId);
    echo "<p>✓ Found " . count($matches) . " matches</p>";

    if (!empty($matches)) {
        echo "<pre>";
        print_r($matches[0]); // Show first match
        echo "</pre>";
    }

    echo "<p>Step 6: Getting stats...</p>";
    $stats = $matchModel->getStats($userId);
    echo "<p>✓ Stats retrieved:</p>";
    echo "<ul>";
    echo "<li>Total matches: " . ($stats['total_matches'] ?? 0) . "</li>";
    echo "<li>Pending matches: " . ($stats['pending_matches'] ?? 0) . "</li>";
    echo "<li>Connected matches: " . ($stats['connected_matches'] ?? 0) . "</li>";
    echo "</ul>";

    echo "<p>Step 7: Testing renderMatchItem function...</p>";
    if (!empty($matches)) {
        echo "<div style='max-width: 600px;'>";
        renderMatchItem($matches[0], $userId);
        echo "</div>";
        echo "<p>✓ renderMatchItem works!</p>";
    } else {
        echo "<p style='color: orange;'>⚠ No matches to render</p>";
    }

    echo "<h3 style='color: green;'>✅ All tests passed! matches.php should work now.</h3>";
    echo "<p><a href='pages/matches.php'>Go to Matches Page</a></p>";

} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error occurred:</h3>";
    echo "<pre style='background: #ffe0e0; padding: 1rem; border: 1px solid red;'>";
    echo "Error: " . $e->getMessage() . "\n\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n\n";
    echo "Stack trace:\n" . $e->getTraceAsString();
    echo "</pre>";
}
?>
