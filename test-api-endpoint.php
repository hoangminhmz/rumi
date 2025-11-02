<?php
/**
 * TEST SWIPE API ENDPOINT DIRECTLY
 * Simulate a real API call
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>TEST SWIPE API ENDPOINT</h1>";
echo "<p>This simulates what JavaScript does when you swipe</p>";

// Setup session first
session_start();
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    echo "<p style='color: orange;'>⚠️ Created session with user_id = 1</p>";
} else {
    echo "<p style='color: green;'>✅ Session exists. User ID: {$_SESSION['user_id']}</p>";
}

echo "<hr>";

// TEST 1: GET request (should fail - API only accepts POST)
echo "<h2>TEST 1: GET Request (Should Fail)</h2>";
echo "<p>Trying to access API with GET...</p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://hoangminhmz.com/rummi/api/swipe-user.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP Code: $httpCode</p>";
echo "<p>Response: <code>$response</code></p>";

if ($httpCode == 200) {
    $data = json_decode($response, true);
    if (isset($data['success']) && $data['success'] === false) {
        echo "<p style='color: green;'>✅ Correctly rejected GET request</p>";
    }
}

echo "<hr>";

// TEST 2: POST request without data (should fail)
echo "<h2>TEST 2: POST Request Without Data (Should Fail)</h2>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://hoangminhmz.com/rummi/api/swipe-user.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP Code: $httpCode</p>";
echo "<p>Response: <code>$response</code></p>";

echo "<hr>";

// TEST 3: POST request with valid data (should work)
echo "<h2>TEST 3: POST Request With Valid Data (Should Work)</h2>";

require_once __DIR__ . '/config/database.php';
$db = getDB();

// Get a target user
$stmt = $db->prepare("SELECT id FROM users WHERE id != ? AND is_active = 1 LIMIT 1");
$stmt->execute([1]);
$targetUser = $stmt->fetch();

if (!$targetUser) {
    die("<p style='color: red;'>❌ No target user found for test</p>");
}

$targetId = $targetUser['id'];
echo "<p>Target user ID: $targetId</p>";

// Count before
$stmt = $db->prepare("SELECT COUNT(*) as count FROM user_swipes WHERE user_id = 1");
$stmt->execute();
$beforeCount = $stmt->fetch()['count'];
echo "<p>Swipes BEFORE API call: <strong>$beforeCount</strong></p>";

// Make API call
$postData = json_encode([
    'target_id' => $targetId,
    'is_like' => true
]);

echo "<p>Sending POST data: <code>$postData</code></p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://hoangminhmz.com/rummi/api/swipe-user.php');
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

echo "<p>HTTP Code: $httpCode</p>";
echo "<p>Response: <code>$response</code></p>";

// Decode response
$data = json_decode($response, true);
if ($data) {
    echo "<h3>Decoded Response:</h3>";
    echo "<pre>";
    print_r($data);
    echo "</pre>";

    if (isset($data['success'])) {
        if ($data['success']) {
            echo "<p style='color: green;'>✅ API returned success!</p>";
        } else {
            echo "<p style='color: red;'>❌ API returned failure: {$data['message']}</p>";
        }
    }
} else {
    echo "<p style='color: red;'>❌ Could not decode JSON response</p>";
}

// Count after
$stmt = $db->prepare("SELECT COUNT(*) as count FROM user_swipes WHERE user_id = 1");
$stmt->execute();
$afterCount = $stmt->fetch()['count'];
echo "<p>Swipes AFTER API call: <strong>$afterCount</strong></p>";

$diff = $afterCount - $beforeCount;
if ($diff > 0) {
    echo "<p style='color: green; font-size: 1.2rem; font-weight: bold;'>✅✅✅ API WORKS! Swipe was saved! (+$diff)</p>";
} else {
    echo "<p style='color: red; font-size: 1.2rem; font-weight: bold;'>❌❌❌ API FAILED! Swipe was NOT saved!</p>";
    echo "<p>This means the API endpoint has a problem.</p>";
}

echo "<hr>";
echo "<h2>Summary</h2>";
if ($diff > 0) {
    echo "<p style='color: green;'>✅ API endpoint works when called with curl.</p>";
    echo "<p style='color: orange;'>⚠️ If API works here but not from swipe.php, the problem is in JavaScript!</p>";
    echo "<p>Next step: Check browser Console (F12) when swiping for errors.</p>";
} else {
    echo "<p style='color: red;'>❌ API endpoint does NOT work even with curl.</p>";
    echo "<p>The problem is in the API code itself.</p>";
}

echo "<p><a href='reset-swipes.php'>Check in Reset Swipes</a></p>";
?>
