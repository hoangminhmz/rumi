<?php
/**
 * RUMI Debug PHP
 * File này phải chạy để biết PHP hoạt động
 */
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>RUMI PHP Debug</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .box {
            background: white;
            padding: 20px;
            margin: 10px 0;
            border-radius: 8px;
            border-left: 4px solid #10B981;
        }
        h1 { color: #10B981; }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #e5e5e5;
        }
        td:first-child {
            font-weight: bold;
            width: 250px;
        }
    </style>
</head>
<body>
    <h1>✅ PHP WORKS!</h1>

    <div class="box">
        <h2>🎉 Nếu thấy trang này → PHP đã hoạt động!</h2>
        <p>Bây giờ tất cả files .php khác cũng phải works.</p>
    </div>

    <div class="box">
        <h3>📊 Server Info:</h3>
        <table>
            <tr>
                <td>PHP Version:</td>
                <td><strong><?= phpversion() ?></strong>
                    <?= version_compare(phpversion(), '8.1.0', '>=') ? '✅ OK' : '❌ Cần >= 8.1' ?>
                </td>
            </tr>
            <tr>
                <td>Server Software:</td>
                <td><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></td>
            </tr>
            <tr>
                <td>Document Root:</td>
                <td><?= $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown' ?></td>
            </tr>
            <tr>
                <td>Script Filename:</td>
                <td><?= $_SERVER['SCRIPT_FILENAME'] ?? 'Unknown' ?></td>
            </tr>
            <tr>
                <td>Current Directory (__DIR__):</td>
                <td><?= __DIR__ ?></td>
            </tr>
            <tr>
                <td>This File (__FILE__):</td>
                <td><?= __FILE__ ?></td>
            </tr>
            <tr>
                <td>Request URI:</td>
                <td><?= $_SERVER['REQUEST_URI'] ?? 'Unknown' ?></td>
            </tr>
            <tr>
                <td>Server Name:</td>
                <td><?= $_SERVER['SERVER_NAME'] ?? 'Unknown' ?></td>
            </tr>
        </table>
    </div>

    <div class="box">
        <h3>📁 Files Check:</h3>
        <table>
            <?php
            $files_to_check = [
                'index.php',
                'test.php',
                'hello.php',
                'phpinfo.php',
                '.htaccess',
                'config/database.php',
                'pages/login.php'
            ];

            foreach ($files_to_check as $file):
                $path = __DIR__ . '/' . $file;
                $exists = file_exists($path);
                $readable = $exists ? is_readable($path) : false;
            ?>
            <tr>
                <td><?= htmlspecialchars($file) ?>:</td>
                <td>
                    <?php if ($exists): ?>
                        <?= $readable ? '✅ Exists & Readable' : '⚠️ Exists but NOT readable' ?>
                    <?php else: ?>
                        ❌ NOT found
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="box">
        <h3>🔐 PHP Extensions:</h3>
        <table>
            <?php
            $required_extensions = ['pdo', 'pdo_mysql', 'curl', 'json', 'mbstring'];
            foreach ($required_extensions as $ext):
            ?>
            <tr>
                <td><?= $ext ?>:</td>
                <td><?= extension_loaded($ext) ? '✅ Loaded' : '❌ NOT loaded' ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="box">
        <h3>🎯 Next Steps:</h3>
        <ol>
            <li>✅ PHP works - Bây giờ test các file khác:
                <ul>
                    <li><a href="test.php">test.php</a></li>
                    <li><a href="index.php">index.php</a></li>
                    <li><a href="hello.php">hello.php</a></li>
                </ul>
            </li>
            <li>Nếu files trên vẫn 404 → Check .htaccess đã disable chưa</li>
            <li>Nếu tất cả works → Setup database và Zalo config</li>
        </ol>
    </div>

    <div class="box">
        <p style="text-align: center;">
            <a href="path-debug.html" style="color: #00D4AA;">← Back to Path Debug</a> |
            <a href="index.php" style="color: #00D4AA;">Go to RUMI Home →</a>
        </p>
    </div>
</body>
</html>
