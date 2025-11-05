<?php
/**
 * RUMI - Test Autofill Location Feature
 * Tests Mapbox Geocoding API integration
 */

require_once __DIR__ . '/includes/GeoLocationService.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';

$geoService = new GeoLocationService();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Autofill Location - RUMI</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
        }

        .content {
            padding: 40px;
        }

        .test-section {
            margin-bottom: 40px;
            padding: 30px;
            background: #f9fafb;
            border-radius: 12px;
            border: 2px solid #e5e7eb;
        }

        .test-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }

        .input-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .input-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn {
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn:active {
            transform: translateY(0);
        }

        .result {
            margin-top: 20px;
            padding: 20px;
            border-radius: 8px;
            display: none;
        }

        .result.success {
            background: #d1fae5;
            border: 2px solid #10b981;
            color: #065f46;
            display: block;
        }

        .result.error {
            background: #fee2e2;
            border: 2px solid #ef4444;
            color: #991b1b;
            display: block;
        }

        .result-title {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 10px;
        }

        .result-data {
            background: white;
            padding: 15px;
            border-radius: 6px;
            margin-top: 10px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .test-list {
            list-style: none;
            padding: 0;
        }

        .test-item {
            padding: 15px;
            background: white;
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .test-item .address {
            font-weight: 600;
            color: #111827;
        }

        .test-item .result-badge {
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .result-badge.success {
            background: #d1fae5;
            color: #065f46;
        }

        .result-badge.error {
            background: #fee2e2;
            color: #991b1b;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .suggestions-list {
            list-style: none;
            padding: 0;
            margin-top: 10px;
        }

        .suggestion-item {
            padding: 12px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .suggestion-item:hover {
            background: #f3f4f6;
            border-color: #667eea;
            transform: translateX(5px);
        }

        .suggestion-item .place-name {
            font-weight: 600;
            color: #111827;
        }

        .suggestion-item .place-context {
            font-size: 0.85rem;
            color: #6b7280;
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📍 Test Autofill Location</h1>
            <p>Test Mapbox Geocoding API Integration</p>
        </div>

        <div class="content">
            <!-- Test 1: Basic Geocoding -->
            <div class="test-section">
                <div class="test-title">
                    <span>🔍</span>
                    Test 1: Geocode Address (Address → Coordinates)
                </div>

                <div class="input-group">
                    <label for="address-input">Enter Vietnamese Address:</label>
                    <input type="text" id="address-input" placeholder="e.g., 268 Lý Thường Kiệt, Quận 10, TP.HCM">
                </div>

                <button class="btn" onclick="testGeocode()">🔍 Geocode Address</button>

                <div id="geocode-result" class="result"></div>
            </div>

            <!-- Test 2: Search Address -->
            <div class="test-section">
                <div class="test-title">
                    <span>🔎</span>
                    Test 2: Search Address (Autocomplete)
                </div>

                <div class="input-group">
                    <label for="search-input">Type to search:</label>
                    <input type="text" id="search-input" placeholder="e.g., Đại học Bách Khoa" oninput="searchAddress()">
                </div>

                <div id="suggestions-container"></div>
            </div>

            <!-- Test 3: Reverse Geocoding -->
            <div class="test-section">
                <div class="test-title">
                    <span>📌</span>
                    Test 3: Reverse Geocode (Coordinates → Address)
                </div>

                <div class="input-group">
                    <label for="lat-input">Latitude:</label>
                    <input type="text" id="lat-input" value="10.7769" placeholder="e.g., 10.7769">
                </div>

                <div class="input-group">
                    <label for="lng-input">Longitude:</label>
                    <input type="text" id="lng-input" value="106.7009" placeholder="e.g., 106.7009">
                </div>

                <button class="btn" onclick="testReverseGeocode()">📍 Reverse Geocode</button>

                <div id="reverse-result" class="result"></div>
            </div>

            <!-- Test 4: Predefined Addresses -->
            <div class="test-section">
                <div class="test-title">
                    <span>✅</span>
                    Test 4: Batch Test with Predefined Addresses
                </div>

                <p style="margin-bottom: 15px; color: #6b7280;">Testing multiple Vietnamese addresses:</p>

                <button class="btn" onclick="testBatchGeocode()">🚀 Run Batch Test</button>

                <div id="batch-results" style="margin-top: 20px;"></div>
            </div>

            <!-- API Status -->
            <div class="test-section" style="background: #e0e7ff; border-color: #667eea;">
                <div class="test-title" style="color: #3730a3;">
                    <span>ℹ️</span>
                    API Configuration Status
                </div>

                <div class="result-data">
API Key: <?= !empty(MAPBOX_API_KEY) ? '✓ Configured' : '✗ Not configured' ?>

Status: <?= !empty(MAPBOX_API_KEY) ? 'Ready to use' : 'Please add MAPBOX_API_KEY to constants.php' ?>

<?php if (!empty(MAPBOX_API_KEY)): ?>
Key Preview: <?= substr(MAPBOX_API_KEY, 0, 10) ?>...<?= substr(MAPBOX_API_KEY, -5) ?>

<?php else: ?>
⚠️ To use this feature:
1. Get free API key from https://mapbox.com
2. Add to config/constants.php: define('MAPBOX_API_KEY', 'your_key_here');
<?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        const apiKey = '<?= MAPBOX_API_KEY ?>';

        function testGeocode() {
            const address = document.getElementById('address-input').value;
            const resultDiv = document.getElementById('geocode-result');

            if (!address) {
                resultDiv.className = 'result error';
                resultDiv.innerHTML = '<div class="result-title">❌ Error</div>Please enter an address';
                return;
            }

            resultDiv.innerHTML = '<div class="loading"></div> Geocoding...';
            resultDiv.className = 'result';
            resultDiv.style.display = 'block';

            const url = `https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(address)}.json?access_token=${apiKey}&country=VN&limit=1`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.features && data.features.length > 0) {
                        const feature = data.features[0];
                        const [lng, lat] = feature.geometry.coordinates;

                        resultDiv.className = 'result success';
                        resultDiv.innerHTML = `
                            <div class="result-title">✅ Geocoding Successful!</div>
                            <div class="result-data">
Address: ${feature.place_name}

Coordinates:
• Latitude: ${lat}
• Longitude: ${lng}

Accuracy: ${feature.place_type.join(', ')}
                            </div>
                        `;
                    } else {
                        resultDiv.className = 'result error';
                        resultDiv.innerHTML = '<div class="result-title">❌ No Results</div>Address not found';
                    }
                })
                .catch(error => {
                    resultDiv.className = 'result error';
                    resultDiv.innerHTML = `<div class="result-title">❌ Error</div>${error.message}`;
                });
        }

        let searchTimeout;
        function searchAddress() {
            const query = document.getElementById('search-input').value;
            const container = document.getElementById('suggestions-container');

            clearTimeout(searchTimeout);

            if (query.length < 3) {
                container.innerHTML = '';
                return;
            }

            searchTimeout = setTimeout(() => {
                const url = `https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(query)}.json?access_token=${apiKey}&country=VN&limit=5`;

                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        if (data.features && data.features.length > 0) {
                            let html = '<ul class="suggestions-list">';
                            data.features.forEach(feature => {
                                const contextStr = feature.context ? feature.context.map(c => c.text).join(', ') : '';
                                html += `
                                    <li class="suggestion-item" onclick="selectAddress('${feature.place_name}', ${feature.geometry.coordinates[1]}, ${feature.geometry.coordinates[0]})">
                                        <div class="place-name">${feature.text}</div>
                                        <div class="place-context">${contextStr || feature.place_name}</div>
                                    </li>
                                `;
                            });
                            html += '</ul>';
                            container.innerHTML = html;
                        } else {
                            container.innerHTML = '<p style="color: #6b7280;">No suggestions found</p>';
                        }
                    })
                    .catch(error => {
                        container.innerHTML = `<p style="color: #ef4444;">Error: ${error.message}</p>`;
                    });
            }, 500);
        }

        function selectAddress(address, lat, lng) {
            alert(`Selected:\n${address}\n\nLat: ${lat}\nLng: ${lng}`);
        }

        function testReverseGeocode() {
            const lat = document.getElementById('lat-input').value;
            const lng = document.getElementById('lng-input').value;
            const resultDiv = document.getElementById('reverse-result');

            if (!lat || !lng) {
                resultDiv.className = 'result error';
                resultDiv.innerHTML = '<div class="result-title">❌ Error</div>Please enter both latitude and longitude';
                return;
            }

            resultDiv.innerHTML = '<div class="loading"></div> Reverse geocoding...';
            resultDiv.className = 'result';
            resultDiv.style.display = 'block';

            const url = `https://api.mapbox.com/geocoding/v5/mapbox.places/${lng},${lat}.json?access_token=${apiKey}&types=address,place`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.features && data.features.length > 0) {
                        const feature = data.features[0];

                        resultDiv.className = 'result success';
                        resultDiv.innerHTML = `
                            <div class="result-title">✅ Reverse Geocoding Successful!</div>
                            <div class="result-data">
Coordinates: ${lat}, ${lng}

Address: ${feature.place_name}

Type: ${feature.place_type.join(', ')}
                            </div>
                        `;
                    } else {
                        resultDiv.className = 'result error';
                        resultDiv.innerHTML = '<div class="result-title">❌ No Results</div>Location not found';
                    }
                })
                .catch(error => {
                    resultDiv.className = 'result error';
                    resultDiv.innerHTML = `<div class="result-title">❌ Error</div>${error.message}`;
                });
        }

        function testBatchGeocode() {
            const addresses = [
                '268 Lý Thường Kiệt, Quận 10, TP.HCM',
                'Đại học Bách Khoa, Quận 10, TP.HCM',
                '01 Võ Văn Ngân, Thủ Đức, TP.HCM',
                'Chợ Bến Thành, Quận 1, TP.HCM',
                'Nhà Hát Thành Phố, Quận 1, TP.HCM'
            ];

            const resultsDiv = document.getElementById('batch-results');
            resultsDiv.innerHTML = '<div class="loading"></div> Running batch test...';

            let html = '<ul class="test-list">';
            let completed = 0;

            addresses.forEach((address, index) => {
                const url = `https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(address)}.json?access_token=${apiKey}&country=VN&limit=1`;

                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        completed++;
                        if (data.features && data.features.length > 0) {
                            const [lng, lat] = data.features[0].geometry.coordinates;
                            html += `
                                <li class="test-item">
                                    <div>
                                        <div class="address">${address}</div>
                                        <div style="font-size: 0.85rem; color: #6b7280; margin-top: 4px;">
                                            ${lat.toFixed(6)}, ${lng.toFixed(6)}
                                        </div>
                                    </div>
                                    <span class="result-badge success">✓ Success</span>
                                </li>
                            `;
                        } else {
                            html += `
                                <li class="test-item">
                                    <div class="address">${address}</div>
                                    <span class="result-badge error">✗ Failed</span>
                                </li>
                            `;
                        }

                        if (completed === addresses.length) {
                            html += '</ul>';
                            resultsDiv.innerHTML = html;
                        }
                    })
                    .catch(error => {
                        completed++;
                        html += `
                            <li class="test-item">
                                <div class="address">${address}</div>
                                <span class="result-badge error">✗ Error</span>
                            </li>
                        `;

                        if (completed === addresses.length) {
                            html += '</ul>';
                            resultsDiv.innerHTML = html;
                        }
                    });
            });
        }
    </script>
</body>
</html>
