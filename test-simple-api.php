<?php
/**
 * TEST SIMPLE API
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>TEST SIMPLE SWIPE API</h1>";
echo "<p>This tests the simplified API version</p>";

// Setup session
session_start();
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    echo "<p style='color: orange;'>⚠️ Created session with user_id = 1</p>";
} else {
    echo "<p style='color: green;'>✅ Session exists. User ID: {$_SESSION['user_id']}</p>";
}

require_once __DIR__ . '/config/database.php';
$db = getDB();

// Get count before
$stmt = $db->prepare("SELECT COUNT(*) as count FROM user_swipes WHERE user_id = 1");
$stmt->execute();
$beforeCount = $stmt->fetch()['count'];

echo "<h2>Before API Call</h2>";
echo "<p>Swipes in database: <strong>$beforeCount</strong></p>";

echo "<hr>";
echo "<h2>Calling Simple API...</h2>";

// Make API call
$postData = json_encode([
    'target_id' => 2,
    'is_like' => true
]);

echo "<p>Sending: <code>$postData</code></p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://hoangminhmz.com/rummi/api/swipe-user-simple.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($postData)
]);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP Code: <strong>$httpCode</strong></p>";
echo "<p>Response:</p>";
echo "<pre style='background: #f3f4f6; padding: 1rem; border-radius: 8px; max-height: 400px; overflow-y: auto;'>";
echo htmlspecialchars($response);
echo "</pre>";

// Try to decode
$data = json_decode($response, true);
if ($data) {
    echo "<h3>Decoded Response:</h3>";
    echo "<pre style='background: #f3f4f6; padding: 1rem; border-radius: 8px;'>";
    print_r($data);
    echo "</pre>";

    if (isset($data['success'])) {
        if ($data['success']) {
            echo "<p style='color: green; font-size: 1.2rem; font-weight: bold;'>✅ API returned success!</p>";
        } else {
            echo "<p style='color: red; font-size: 1.2rem; font-weight: bold;'>❌ API returned failure</p>";
            if (isset($data['message'])) {
                echo "<p>Message: {$data['message']}</p>";
            }
            if (isset($data['errors']) && !empty($data['errors'])) {
                echo "<h4>Errors:</h4><pre>";
                print_r($data['errors']);
                echo "</pre>";
            }
        }
    }
} else {
    echo "<p style='color: red;'>❌ Could not decode JSON response</p>";
    echo "<p>JSON Error: " . json_last_error_msg() . "</p>";
}

echo "<hr>";
echo "<h2>After API Call</h2>";

// Get count after
$stmt = $db->prepare("SELECT COUNT(*) as count FROM user_swipes WHERE user_id = 1");
$stmt->execute();
$afterCount = $stmt->fetch()['count'];

echo "<p>Swipes in database: <strong>$afterCount</strong></p>";

$diff = $afterCount - $beforeCount;
if ($diff > 0) {
    echo "<p style='color: green; font-size: 1.5rem; font-weight: bold;'>✅✅✅ SUCCESS! Swipe was saved! (+$diff)</p>";
} else {
    echo "<p style='color: red; font-size: 1.5rem; font-weight: bold;'>❌❌❌ FAILED! Swipe was NOT saved!</p>";
}

echo "<hr>";
echo "<p><a href='reset-swipes.php'>Check Reset Swipes</a></p>";
?>
