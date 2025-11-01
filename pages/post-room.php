<?php
/**
 * RUMI - Post Room
 * Form để chủ nhà đăng phòng (có listing fee)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/Room.php';

startSession();
requireLogin();

$userModel = new User();
$roomModel = new Room();
$districts = $userModel->getDistricts();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF
        if (!validateCSRF($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid CSRF token');
        }

        // Validate input
        if (empty($_POST['title']) || empty($_POST['price']) || empty($_POST['district_id']) || empty($_POST['address'])) {
            throw new Exception('Vui lòng điền đầy đủ thông tin bắt buộc');
        }

        // Build amenities array
        $amenities = [];
        foreach (AMENITIES as $key => $label) {
            $amenities[$key] = isset($_POST['amenities'][$key]);
        }

        // Create room
        $data = [
            'title' => sanitizeInput($_POST['title']),
            'description' => sanitizeInput($_POST['description'] ?? ''),
            'price' => (int)$_POST['price'],
            'area' => !empty($_POST['area']) ? (float)$_POST['area'] : null,
            'district_id' => (int)$_POST['district_id'],
            'address' => sanitizeInput($_POST['address']),
            'amenities' => $amenities,
            'images' => [] // Will be handled after payment
        ];

        $roomId = $roomModel->create(getCurrentUserId(), $data);

        if ($roomId) {
            setFlash('success', 'Phòng đã được tạo! Vui lòng thanh toán phí đăng tin.');
            redirect(BASE_URL . '/pages/room-payment.php?room_id=' . $roomId);
        } else {
            throw new Exception('Không thể tạo phòng');
        }

    } catch (Exception $e) {
        setFlash('error', $e->getMessage());
    }
}

$pageTitle = 'Đăng phòng';
include __DIR__ . '/../components/header.php';
?>

<div class="container container-xs" style="padding-top: var(--space-4); padding-bottom: var(--space-8);">
    <div class="card">
        <div class="card-body p-4">
            <h2 class="mb-3">Đăng phòng trọ</h2>
            <p class="text-secondary mb-4">
                Phí đăng tin: <strong><?= formatPrice(ROOM_LISTING_FEE) ?></strong> cho <?= ROOM_LISTING_DURATION ?> ngày
            </p>

            <form method="POST" action="">
                <?= csrfField() ?>

                <!-- Tiêu đề -->
                <div class="form-group">
                    <label for="title" class="form-label required">Tiêu đề</label>
                    <input type="text" id="title" name="title" class="form-control"
                           placeholder="VD: Phòng đẹp gần công viên, full nội thất"
                           value="<?= e($_POST['title'] ?? '') ?>" required>
                </div>

                <!-- Giá -->
                <div class="form-group">
                    <label for="price" class="form-label required">Giá thuê (VND/tháng)</label>
                    <input type="number" id="price" name="price" class="form-control"
                           placeholder="3000000"
                           value="<?= e($_POST['price'] ?? '') ?>" required>
                </div>

                <!-- Diện tích -->
                <div class="form-group">
                    <label for="area" class="form-label">Diện tích (m²)</label>
                    <input type="number" id="area" name="area" class="form-control" step="0.1"
                           placeholder="25"
                           value="<?= e($_POST['area'] ?? '') ?>">
                </div>

                <!-- Quận/Huyện -->
                <div class="form-group">
                    <label for="district_id" class="form-label required">Quận/Huyện</label>
                    <select id="district_id" name="district_id" class="form-control" required>
                        <option value="">Chọn quận/huyện</option>
                        <?php
                        $currentCity = '';
                        foreach ($districts as $district):
                            if ($district['city_name'] !== $currentCity):
                                if ($currentCity !== '') echo '</optgroup>';
                                echo '<optgroup label="' . e($district['city_name']) . '">';
                                $currentCity = $district['city_name'];
                            endif;
                        ?>
                        <option value="<?= $district['id'] ?>" <?= ($_POST['district_id'] ?? '') == $district['id'] ? 'selected' : '' ?>>
                            <?= e($district['name']) ?>
                        </option>
                        <?php endforeach; ?>
                        <?php if ($currentCity !== '') echo '</optgroup>'; ?>
                    </select>
                </div>

                <!-- Địa chỉ -->
                <div class="form-group">
                    <label for="address" class="form-label required">Địa chỉ cụ thể</label>
                    <input type="text" id="address" name="address" class="form-control"
                           placeholder="VD: 123 Nguyễn Trãi"
                           value="<?= e($_POST['address'] ?? '') ?>" required>
                </div>

                <!-- Mô tả -->
                <div class="form-group">
                    <label for="description" class="form-label">Mô tả chi tiết</label>
                    <textarea id="description" name="description" class="form-control" rows="5"
                              placeholder="Mô tả về phòng, khu vực xung quanh, tiện ích..."><?= e($_POST['description'] ?? '') ?></textarea>
                </div>

                <!-- Amenities -->
                <div class="form-group">
                    <label class="form-label">Tiện nghi</label>
                    <div class="row">
                        <?php foreach (AMENITIES as $key => $label): ?>
                        <div class="col-6 mb-2">
                            <label style="display: flex; align-items: center; gap: var(--space-1); cursor: pointer;">
                                <input type="checkbox" name="amenities[<?= e($key) ?>]" value="1"
                                       <?= isset($_POST['amenities'][$key]) ? 'checked' : '' ?>>
                                <span><?= e($label) ?></span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="alert" style="background: var(--color-gray-100); padding: var(--space-2); border-radius: var(--radius-md); margin-bottom: var(--space-3);">
                    <strong>Lưu ý:</strong> Sau khi tạo, bạn sẽ cần thanh toán phí đăng tin <?= formatPrice(ROOM_LISTING_FEE) ?> để phòng được hiển thị.
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    Tiếp tục thanh toán
                </button>
            </form>
        </div>
    </div>
</div>

<?php
include __DIR__ . '/../components/navigation.php';
include __DIR__ . '/../components/footer.php';
?>
