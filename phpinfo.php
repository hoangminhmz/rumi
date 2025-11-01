<?php
/**
 * RUMI - PHP Info
 * QUAN TRỌNG: XÓA FILE NÀY SAU KHI DEBUG XONG!
 */

// Security: Only allow from localhost or specific IP
// Uncomment và thêm IP của bạn:
// $allowed_ips = ['127.0.0.1', 'YOUR_IP_HERE'];
// if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips)) {
//     die('Access denied');
// }

phpinfo();

// REMINDER: Xóa file này sau khi debug!
echo '<div style="background: #fee2e2; color: #991b1b; padding: 20px; text-align: center; font-weight: bold; margin-top: 20px;">';
echo '⚠️ CẢNH BÁO: Xóa file phpinfo.php sau khi debug xong vì lý do bảo mật!';
echo '</div>';
?>
