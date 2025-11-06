<?php
/**
 * RUMI - API Documentation
 * Simple documentation page for API endpoints
 */
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RUMI API Documentation</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #111827;
            margin-bottom: 1rem;
            font-size: 2.5rem;
        }
        .subtitle {
            color: #6b7280;
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }
        .endpoint {
            background: #f9fafb;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #667eea;
        }
        .endpoint h2 {
            color: #111827;
            margin-bottom: 0.5rem;
            font-size: 1.5rem;
        }
        .method {
            display: inline-block;
            background: #10b981;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.85rem;
            margin-right: 0.5rem;
        }
        .url {
            background: #1f2937;
            color: #10b981;
            padding: 0.75rem;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            margin: 1rem 0;
            overflow-x: auto;
        }
        .params {
            margin-top: 1rem;
        }
        .params h3 {
            color: #374151;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        .param {
            background: white;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            margin-bottom: 0.5rem;
            border: 1px solid #e5e7eb;
        }
        .param code {
            background: #f3f4f6;
            padding: 0.2rem 0.5rem;
            border-radius: 3px;
            color: #667eea;
            font-weight: 600;
        }
        .example {
            margin-top: 1rem;
        }
        .example h3 {
            color: #374151;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        .btn {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 0.5rem;
            transition: background 0.2s;
        }
        .btn:hover {
            background: #5568d3;
        }
        pre {
            background: #1f2937;
            color: #10b981;
            padding: 1rem;
            border-radius: 6px;
            overflow-x: auto;
            font-size: 0.9rem;
        }
        .response-example {
            background: #1f2937;
            color: #e5e7eb;
            padding: 1rem;
            border-radius: 6px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 RUMI API Documentation</h1>
        <p class="subtitle">RESTful API endpoints for dynamic preference and amenity loading</p>

        <!-- Endpoint 1: Get Preferences -->
        <div class="endpoint">
            <h2>
                <span class="method">GET</span>
                Get Preferences
            </h2>
            <div class="url">
                <?= htmlspecialchars($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST']) ?>/rummi/api/get-preferences.php
            </div>

            <p style="margin: 1rem 0; color: #6b7280;">
                Returns all active preferences with their options, icons, and metadata. Supports filtering by category and grouped response.
            </p>

            <div class="params">
                <h3>Query Parameters (Optional)</h3>
                <div class="param">
                    <code>category</code> - Filter by category (lifestyle, financial, location, other)
                </div>
                <div class="param">
                    <code>grouped</code> - Set to "true" to group preferences by category
                </div>
            </div>

            <div class="example">
                <h3>Example Request</h3>
                <pre>GET /api/get-preferences.php?category=lifestyle&grouped=true</pre>
                <a href="get-preferences.php" class="btn" target="_blank">Try it Live →</a>
                <a href="get-preferences.php?category=lifestyle" class="btn" target="_blank">Lifestyle Only →</a>
                <a href="get-preferences.php?grouped=true" class="btn" target="_blank">Grouped →</a>
            </div>

            <div class="example">
                <h3>Response Example</h3>
                <div class="response-example">{
  "success": true,
  "data": [
    {
      "id": 1,
      "code": "sleep_schedule",
      "name_vi": "Lịch ngủ",
      "name_en": "Sleep Schedule",
      "icon": "😴",
      "field_type": "enum",
      "options": {
        "options": [
          {"code": "early_bird", "name_vi": "Dậy sớm", "icon": "🌅"},
          {"code": "night_owl", "name_vi": "Thức khuya", "icon": "🦉"}
        ]
      },
      "description_vi": "Lịch ngủ và thời gian hoạt động",
      "weight": 20,
      "category": "lifestyle"
    }
  ],
  "count": 9
}</div>
            </div>
        </div>

        <!-- Endpoint 2: Get Amenities -->
        <div class="endpoint">
            <h2>
                <span class="method">GET</span>
                Get Amenities
            </h2>
            <div class="url">
                <?= htmlspecialchars($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST']) ?>/rummi/api/get-amenities.php
            </div>

            <p style="margin: 1rem 0; color: #6b7280;">
                Returns all active amenities sorted by sort_order. Used for room posting forms and filters.
            </p>

            <div class="example">
                <h3>Example Request</h3>
                <pre>GET /api/get-amenities.php</pre>
                <a href="get-amenities.php" class="btn" target="_blank">Try it Live →</a>
            </div>

            <div class="example">
                <h3>Response Example</h3>
                <div class="response-example">{
  "success": true,
  "data": [
    {
      "id": 1,
      "code": "wifi",
      "name_vi": "Wifi",
      "name_en": "Wifi",
      "icon": "📶",
      "sort_order": 1
    },
    {
      "id": 2,
      "code": "ac",
      "name_vi": "Điều hòa",
      "name_en": "Air Conditioning",
      "icon": "❄️",
      "sort_order": 2
    }
  ],
  "count": 12
}</div>
            </div>
        </div>

        <!-- Features -->
        <div style="background: #e0e7ff; padding: 1.5rem; border-radius: 8px; margin-top: 2rem;">
            <h2 style="color: #3730a3; margin-bottom: 1rem;">✨ Features</h2>
            <ul style="color: #3730a3; line-height: 2; margin-left: 1.5rem;">
                <li><strong>UTF-8 Support:</strong> Proper Vietnamese character encoding with JSON_UNESCAPED_UNICODE</li>
                <li><strong>CORS Enabled:</strong> Can be called from any domain</li>
                <li><strong>JSON Format:</strong> Standard REST API response format</li>
                <li><strong>Error Handling:</strong> Returns proper HTTP status codes and error messages</li>
                <li><strong>Dynamic:</strong> Always returns latest data from database</li>
                <li><strong>Fast:</strong> Simple queries with proper indexing</li>
            </ul>
        </div>

        <!-- Use Cases -->
        <div style="background: #fef3c7; padding: 1.5rem; border-radius: 8px; margin-top: 1.5rem;">
            <h2 style="color: #92400e; margin-bottom: 1rem;">💡 Use Cases</h2>
            <ul style="color: #92400e; line-height: 2; margin-left: 1.5rem;">
                <li><strong>Dynamic Forms:</strong> Load preference fields in profile/room forms via AJAX</li>
                <li><strong>Mobile Apps:</strong> Fetch preferences for native iOS/Android apps</li>
                <li><strong>Third-party Integration:</strong> Allow partners to access RUMI preferences</li>
                <li><strong>Admin Dashboard:</strong> Real-time preference statistics and management</li>
                <li><strong>Progressive Enhancement:</strong> Load preferences after page load for faster initial render</li>
            </ul>
        </div>

        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 2px solid #e5e7eb; text-align: center; color: #6b7280;">
            <p>Built with ❤️ for RUMI Roommate Matching Platform</p>
            <p style="margin-top: 0.5rem;">
                <a href="../admin/preferences.php" style="color: #667eea;">Admin Panel</a> •
                <a href="../pages/profile-setup.php" style="color: #667eea;">Profile Setup</a> •
                <a href="../" style="color: #667eea;">Home</a>
            </p>
        </div>
    </div>
</body>
</html>
