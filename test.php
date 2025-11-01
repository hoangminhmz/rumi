<?php
/**
 * RUMI - Test File
 * Kiểm tra xem PHP có hoạt động không
 */
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RUMI Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success {
            color: #10B981;
            font-size: 24px;
            font-weight: bold;
        }
        .info {
            background: #e0f2fe;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #e5e5e5;
        }
        td:first-child {
            font-weight: bold;
            width: 200px;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="success">✅ RUMI Test - PHP Hoạt động OK!</div>

        <div class="info">
            <strong>🎉 Nếu bạn thấy trang này → PHP đang chạy bình thường!</strong>
        </div>

        <h3>📊 Thông tin Server:</h3>
        <table>
            <tr>
                <td>PHP Version:</td>
                <td><?= phpversion() ?></td>
            </tr>
            <tr>
                <td>Server Software:</td>
                <td><?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?></td>
            </tr>
            <tr>
                <td>Document Root:</td>
                <td><?= $_SERVER['DOCUMENT_ROOT'] ?? 'N/A' ?></td>
            </tr>
            <tr>
                <td>Script Path:</td>
                <td><?= __FILE__ ?></td>
            </tr>
            <tr>
                <td>Current Directory:</td>
                <td><?= __DIR__ ?></td>
            </tr>
            <tr>
                <td>Server Name:</td>
                <td><?= $_SERVER['SERVER_NAME'] ?? 'N/A' ?></td>
            </tr>
        </table>

        <h3>🔐 File Permissions Check:</h3>
        <table>
            <?php
            $files_to_check = [
                'index.php',
                'config/database.php',
                'pages/login.php',
                '.htaccess'
            ];

            foreach ($files_to_check as $file):
                $path = __DIR__ . '/' . $file;
                $exists = file_exists($path);
                $readable = $exists ? is_readable($path) : false;
                $perms = $exists ? substr(sprintf('%o', fileperms($path)), -4) : 'N/A';
            ?>
            <tr>
                <td><?= $file ?>:</td>
                <td>
                    <?php if ($exists): ?>
                        <?= $readable ? '✅' : '❌' ?>
                        <?= $readable ? 'Readable' : 'Not Readable' ?>
                        (Permissions: <?= $perms ?>)
                    <?php else: ?>
                        ❌ File không tồn tại
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <h3>📁 Directory Structure:</h3>
        <div class="info">
            <?php
            $dirs = ['api', 'assets', 'components', 'config', 'includes', 'pages'];
            echo "<ul>";
            foreach ($dirs as $dir) {
                $path = __DIR__ . '/' . $dir;
                $exists = is_dir($path);
                echo "<li>" . ($exists ? '✅' : '❌') . " $dir/</li>";
            }
            echo "</ul>";
            ?>
        </div>

        <hr style="margin: 30px 0;">

        <div style="text-align: center;">
            <h3>🧪 Test Navigation:</h3>
            <p>
                <a href="test.php" style="color: #10B981;">📄 test.php (Current)</a> |
                <a href="index.php" style="color: #00D4AA;">🏠 index.php</a> |
                <a href="phpinfo.php" style="color: #059669;">ℹ️ phpinfo.php</a>
            </p>
        </div>
    </div>
</body>
</html>
