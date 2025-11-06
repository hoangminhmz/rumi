<?php
/**
 * RUMI - Profile Setup
 * Form để hoàn thiện profile sau khi đăng nhập lần đầu
 */

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

// Load lifestyle preferences from database
$db = getDB();
$lifestylePrefsStmt = $db->query("
    SELECT code, name_vi, name_en, icon, field_type, options_config, description_vi
    FROM preferences_list
    WHERE code IN ('sleep_schedule', 'work_schedule', 'drinking', 'guests_policy')
      AND is_active = 1
    ORDER BY weight DESC
");
$lifestylePreferences = [];
while ($pref = $lifestylePrefsStmt->fetch(PDO::FETCH_ASSOC)) {
    $pref['options'] = !empty($pref['options_config']) ? json_decode($pref['options_config'], true) : null;
    $lifestylePreferences[$pref['code']] = $pref;
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

        // Build preferences
        $preferences = [
            'budget_min' => (int)($_POST['budget_min'] ?? 0),
            'budget_max' => (int)($_POST['budget_max'] ?? 0),
            'cleanliness' => (int)($_POST['cleanliness'] ?? 3),
            'noise_tolerance' => (int)($_POST['noise_tolerance'] ?? 3),
            'smoking' => isset($_POST['smoking']),
            'pets' => isset($_POST['pets']),
            // Lifestyle preferences
            'sleep_schedule' => $_POST['sleep_schedule'] ?? null,
            'work_schedule' => $_POST['work_schedule'] ?? null,
            'drinking' => $_POST['drinking'] ?? null,
            'guests_policy' => $_POST['guests_policy'] ?? null
        ];

        // Update profile
        $data = [
            'name' => sanitizeInput($_POST['name']),
            'phone' => $_POST['phone'],
            'gender' => $_POST['gender'],
            'age' => (int)$_POST['age'],
            'district_id' => (int)$_POST['district_id'],
            'bio' => sanitizeInput($_POST['bio'] ?? ''),
            'preferences' => $preferences,
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

                <!-- Lifestyle Preferences (Dynamic) -->
                <?php foreach ($lifestylePreferences as $code => $pref): ?>
                    <?php if (!empty($pref['options']['options'])): ?>
                    <div class="form-group">
                        <label for="<?= e($code) ?>" class="form-label">
                            <?= e($pref['icon']) ?> <?= e($pref['name_vi']) ?>
                        </label>
                        <?php if (!empty($pref['description_vi'])): ?>
                            <small class="text-secondary d-block mb-1"><?= e($pref['description_vi']) ?></small>
                        <?php endif; ?>
                        <select id="<?= e($code) ?>" name="<?= e($code) ?>" class="form-control">
                            <option value="">-- Chọn --</option>
                            <?php foreach ($pref['options']['options'] as $option): ?>
                                <option value="<?= e($option['code']) ?>"
                                    <?= ($currentUser['preferences'][$code] ?? '') === $option['code'] ? 'selected' : '' ?>>
                                    <?= e($option['icon'] ?? '') ?> <?= e($option['name_vi']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>

                <!-- Budget -->
                <div class="form-group">
                    <label class="form-label">Ngân sách (VND/tháng)</label>
                    <div class="d-flex gap-2">
                        <input type="number" name="budget_min" class="form-control" placeholder="Tối thiểu"
                               value="<?= e($currentUser['preferences']['budget_min'] ?? '') ?>">
                        <input type="number" name="budget_max" class="form-control" placeholder="Tối đa"
                               value="<?= e($currentUser['preferences']['budget_max'] ?? '') ?>">
                    </div>
                </div>

                <!-- Cleanliness -->
                <div class="form-group">
                    <label for="cleanliness" class="form-label">Mức độ sạch sẽ</label>
                    <select id="cleanliness" name="cleanliness" class="form-control">
                        <?php foreach (PREFERENCE_LEVELS as $value => $label): ?>
                        <option value="<?= $value ?>" <?= ($currentUser['preferences']['cleanliness'] ?? 3) == $value ? 'selected' : '' ?>>
                            <?= e($label) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Noise Tolerance -->
                <div class="form-group">
                    <label for="noise_tolerance" class="form-label">Dung nạp tiếng ồn</label>
                    <select id="noise_tolerance" name="noise_tolerance" class="form-control">
                        <?php foreach (PREFERENCE_LEVELS as $value => $label): ?>
                        <option value="<?= $value ?>" <?= ($currentUser['preferences']['noise_tolerance'] ?? 3) == $value ? 'selected' : '' ?>>
                            <?= e($label) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Smoking -->
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: var(--space-1); cursor: pointer;">
                        <input type="checkbox" name="smoking" value="1"
                               <?= ($currentUser['preferences']['smoking'] ?? false) ? 'checked' : '' ?>>
                        <span>Tôi hút thuốc</span>
                    </label>
                </div>

                <!-- Pets -->
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: var(--space-1); cursor: pointer;">
                        <input type="checkbox" name="pets" value="1"
                               <?= ($currentUser['preferences']['pets'] ?? false) ? 'checked' : '' ?>>
                        <span>Tôi có/thích nuôi thú cưng</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block mt-4">
                    Hoàn tất và bắt đầu sử dụng RUMI
                </button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>
