<?php
/**
 * RUMI - Profile Setup
 * Form để hoàn thiện profile sau khi đăng nhập lần đầu
 */

// Set UTF-8 encoding for response
header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/User.php';

startSession();
requireLogin();

$userModel = new User();
$currentUser = $userModel->getById(getCurrentUserId());

// Check if profile already complete
if ($userModel->hasCompleteProfile(getCurrentUserId()) && !isset($_GET['edit'])) {
    redirect(BASE_URL . '/pages/swipe.php');
}

// Get districts
$districts = $userModel->getDistricts();

// Load ALL preferences from database (not just lifestyle)
$db = getDB();
$prefsStmt = $db->query("
    SELECT code, name_vi, name_en, icon, field_type, options_config, description_vi, category
    FROM preferences_list
    WHERE is_active = 1
    ORDER BY category ASC, weight DESC
");

$preferences = [];
$preferencesByCategory = [];
while ($pref = $prefsStmt->fetch(PDO::FETCH_ASSOC)) {
    $pref['options'] = !empty($pref['options_config']) ? json_decode($pref['options_config'], true) : null;
    $preferences[$pref['code']] = $pref;

    $category = $pref['category'] ?? 'other';
    if (!isset($preferencesByCategory[$category])) {
        $preferencesByCategory[$category] = [];
    }
    $preferencesByCategory[$category][] = $pref;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF
        if (!validateCSRF($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid CSRF token');
        }

        // Validate input
        $errors = [];

        if (empty($_POST['phone']) || !validatePhone($_POST['phone'])) {
            $errors[] = 'Số điện thoại không hợp lệ';
        }

        if (empty($_POST['gender']) || !in_array($_POST['gender'], array_keys(GENDERS))) {
            $errors[] = 'Giới tính không hợp lệ';
        }

        if (empty($_POST['age']) || $_POST['age'] < 18 || $_POST['age'] > 100) {
            $errors[] = 'Tuổi không hợp lệ';
        }

        if (empty($_POST['district_id'])) {
            $errors[] = 'Vui lòng chọn quận/huyện';
        }

        if (!empty($errors)) {
            throw new Exception(implode(', ', $errors));
        }

        // Build preferences dynamically from database structure
        $userPreferences = [];
        foreach ($preferences as $code => $pref) {
            switch ($pref['field_type']) {
                case 'enum':
                    $userPreferences[$code] = $_POST[$code] ?? null;
                    break;
                case 'scale':
                    $userPreferences[$code] = isset($_POST[$code]) ? (int)$_POST[$code] : 3;
                    break;
                case 'boolean':
                    $userPreferences[$code] = isset($_POST[$code]);
                    break;
                case 'range':
                    // Handle min/max separately
                    if ($code === 'budget') {
                        $userPreferences['budget_min'] = (int)($_POST['budget_min'] ?? 0);
                        $userPreferences['budget_max'] = (int)($_POST['budget_max'] ?? 0);
                    }
                    break;
            }
        }

        // Update profile
        $data = [
            'name' => sanitizeInput($_POST['name']),
            'phone' => $_POST['phone'],
            'gender' => $_POST['gender'],
            'age' => (int)$_POST['age'],
            'district_id' => (int)$_POST['district_id'],
            'bio' => sanitizeInput($_POST['bio'] ?? ''),
            'preferences' => $userPreferences,
            'search_mode' => $_POST['search_mode'] ?? 'find_roommate'
        ];

        if ($userModel->updateProfile(getCurrentUserId(), $data)) {
            setFlash('success', 'Profile đã được cập nhật!');
            redirect(BASE_URL . '/pages/swipe.php');
        } else {
            throw new Exception('Không thể cập nhật profile');
        }

    } catch (Exception $e) {
        setFlash('error', $e->getMessage());
    }
}

$pageTitle = 'Hoàn thiện profile';
include __DIR__ . '/../components/header.php';
?>

<div class="container container-xs" style="padding-top: var(--space-4); padding-bottom: var(--space-4);">
    <div class="card">
        <div class="card-body p-4">
            <h2 class="mb-3">Hoàn thiện profile</h2>
            <p class="text-secondary mb-4">Cho chúng tôi biết thêm về bạn để tìm match phù hợp nhất</p>

            <form method="POST" action="">
                <?= csrfField() ?>

                <!-- Tên -->
                <div class="form-group">
                    <label for="name" class="form-label required">Tên hiển thị</label>
                    <input type="text" id="name" name="name" class="form-control"
                           value="<?= e($currentUser['name'] ?? '') ?>" required>
                </div>

                <!-- Số điện thoại -->
                <div class="form-group">
                    <label for="phone" class="form-label required">Số điện thoại</label>
                    <input type="tel" id="phone" name="phone" class="form-control"
                           placeholder="0901234567"
                           value="<?= e($currentUser['phone'] ?? '') ?>" required>
                    <small class="text-secondary">Sẽ được chia sẻ khi match thành công</small>
                </div>

                <!-- Giới tính -->
                <div class="form-group">
                    <label for="gender" class="form-label required">Giới tính</label>
                    <select id="gender" name="gender" class="form-control" required>
                        <option value="">Chọn giới tính</option>
                        <?php foreach (GENDERS as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= ($currentUser['gender'] ?? '') === $value ? 'selected' : '' ?>>
                            <?= e($label) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Tuổi -->
                <div class="form-group">
                    <label for="age" class="form-label required">Tuổi</label>
                    <input type="number" id="age" name="age" class="form-control" min="18" max="100"
                           value="<?= e($currentUser['age'] ?? '') ?>" required>
                </div>

                <!-- Quận/Huyện -->
                <div class="form-group">
                    <label for="district_id" class="form-label required">Khu vực mong muốn</label>
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
                        <option value="<?= $district['id'] ?>" <?= ($currentUser['district_id'] ?? '') == $district['id'] ? 'selected' : '' ?>>
                            <?= e($district['name']) ?>
                        </option>
                        <?php endforeach; ?>
                        <?php if ($currentCity !== '') echo '</optgroup>'; ?>
                    </select>
                </div>

                <!-- Bio -->
                <div class="form-group">
                    <label for="bio" class="form-label">Giới thiệu bản thân</label>
                    <textarea id="bio" name="bio" class="form-control" rows="4"
                              placeholder="Kể về bản thân, sở thích, công việc..."><?= e($currentUser['bio'] ?? '') ?></textarea>
                </div>

                <!-- Search Mode -->
                <div class="form-group">
                    <label class="form-label required">Bạn muốn</label>
                    <div class="d-flex gap-2">
                        <?php foreach (SEARCH_MODES as $value => $label): ?>
                        <label class="flex-1" style="cursor: pointer;">
                            <input type="radio" name="search_mode" value="<?= e($value) ?>"
                                   <?= ($currentUser['search_mode'] ?? 'find_roommate') === $value ? 'checked' : '' ?> required>
                            <div class="card text-center" style="padding: var(--space-2);">
                                <div><?= e($label) ?></div>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <hr>

                <h3 style="font-size: var(--font-size-lg); margin-bottom: var(--space-3);">Preferences</h3>

                <!-- Dynamic Preferences from Database -->
                <?php foreach ($preferences as $code => $pref): ?>
                    <?php
                    $currentValue = $currentUser['preferences'][$code] ?? null;
                    $fieldType = $pref['field_type'] ?? 'scale';
                    ?>

                    <div class="form-group">
                        <?php if ($fieldType === 'enum'): ?>
                            <!-- Enum: Dropdown with options from database -->
                            <label for="<?= e($code) ?>" class="form-label">
                                <?= e($pref['icon']) ?> <?= e($pref['name_vi']) ?>
                            </label>
                            <?php if (!empty($pref['description_vi'])): ?>
                                <small class="text-secondary d-block mb-1"><?= e($pref['description_vi']) ?></small>
                            <?php endif; ?>
                            <select id="<?= e($code) ?>" name="<?= e($code) ?>" class="form-control">
                                <option value="">-- Chọn --</option>
                                <?php if (!empty($pref['options']['options'])): ?>
                                    <?php foreach ($pref['options']['options'] as $option): ?>
                                        <option value="<?= e($option['code']) ?>"
                                            <?= $currentValue === $option['code'] ? 'selected' : '' ?>>
                                            <?= e($option['icon'] ?? '') ?> <?= e($option['name_vi']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>

                        <?php elseif ($fieldType === 'scale'): ?>
                            <!-- Scale: 1-5 dropdown -->
                            <label for="<?= e($code) ?>" class="form-label">
                                <?= e($pref['icon']) ?> <?= e($pref['name_vi']) ?>
                            </label>
                            <?php if (!empty($pref['description_vi'])): ?>
                                <small class="text-secondary d-block mb-1"><?= e($pref['description_vi']) ?></small>
                            <?php endif; ?>
                            <select id="<?= e($code) ?>" name="<?= e($code) ?>" class="form-control">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?= $i ?>" <?= (int)$currentValue == $i || (is_null($currentValue) && $i == 3) ? 'selected' : '' ?>>
                                        <?= $i ?> - <?= ['Rất thấp', 'Thấp', 'Trung bình', 'Cao', 'Rất cao'][$i-1] ?>
                                    </option>
                                <?php endfor; ?>
                            </select>

                        <?php elseif ($fieldType === 'boolean'): ?>
                            <!-- Boolean: Checkbox -->
                            <label style="display: flex; align-items: center; gap: var(--space-1); cursor: pointer;">
                                <input type="checkbox" name="<?= e($code) ?>" value="1"
                                       <?= $currentValue ? 'checked' : '' ?>>
                                <span><?= e($pref['icon']) ?> <?= e($pref['name_vi']) ?></span>
                            </label>
                            <?php if (!empty($pref['description_vi'])): ?>
                                <small class="text-secondary d-block" style="margin-left: 2rem;"><?= e($pref['description_vi']) ?></small>
                            <?php endif; ?>

                        <?php elseif ($fieldType === 'range'): ?>
                            <!-- Range: Min/Max inputs -->
                            <label class="form-label">
                                <?= e($pref['icon']) ?> <?= e($pref['name_vi']) ?>
                            </label>
                            <?php if (!empty($pref['description_vi'])): ?>
                                <small class="text-secondary d-block mb-1"><?= e($pref['description_vi']) ?></small>
                            <?php endif; ?>
                            <div class="d-flex gap-2">
                                <input type="number" name="<?= e($code) ?>_min" class="form-control" placeholder="Tối thiểu"
                                       value="<?= e($currentUser['preferences'][$code . '_min'] ?? '') ?>">
                                <input type="number" name="<?= e($code) ?>_max" class="form-control" placeholder="Tối đa"
                                       value="<?= e($currentUser['preferences'][$code . '_max'] ?? '') ?>">
                            </div>

                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <button type="submit" class="btn btn-primary btn-block mt-4">
                    Hoàn tất và bắt đầu sử dụng RUMI
                </button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>
