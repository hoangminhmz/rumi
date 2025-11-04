<?php
/**
 * RUMI - Enhanced Profile Setup (V2)
 * Form với lifestyle preferences cho two-stage matching
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

        // Build preferences JSON
        $preferences = [
            'budget_min' => (int)($_POST['budget_min'] ?? 0),
            'budget_max' => (int)($_POST['budget_max'] ?? 10000000),
            'cleanliness' => (int)($_POST['cleanliness'] ?? 3),
            'noise_tolerance' => (int)($_POST['noise_tolerance'] ?? 3),
            'smoking' => isset($_POST['smoking']),
            'pets' => isset($_POST['pets'])
        ];

        // Update profile with new fields
        $data = [
            'name' => sanitizeInput($_POST['name']),
            'phone' => $_POST['phone'],
            'gender' => $_POST['gender'],
            'age' => (int)$_POST['age'],
            'district_id' => (int)$_POST['district_id'],
            'bio' => sanitizeInput($_POST['bio'] ?? ''),
            'preferences' => $preferences,
            'search_mode' => $_POST['search_mode'] ?? 'find_roommate_first',

            // New lifestyle fields
            'sleep_schedule' => $_POST['sleep_schedule'] ?? null,
            'work_schedule' => $_POST['work_schedule'] ?? null,
            'drinking' => $_POST['drinking'] ?? null,
            'guests_policy' => $_POST['guests_policy'] ?? null,
            'occupation' => sanitizeInput($_POST['occupation'] ?? ''),
            'move_in_date' => $_POST['move_in_date'] ?? null,
            'stay_duration' => $_POST['stay_duration'] ?? null,

            // Social verification
            'facebook_url' => sanitizeInput($_POST['facebook_url'] ?? ''),
            'linkedin_url' => sanitizeInput($_POST['linkedin_url'] ?? '')
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

// Decode current preferences
$currentPrefs = is_string($currentUser['preferences'] ?? null)
    ? json_decode($currentUser['preferences'], true)
    : ($currentUser['preferences'] ?? []);

$pageTitle = 'Hoàn thiện profile';
include __DIR__ . '/../components/header.php';
?>

<style>
.profile-setup-container {
    max-width: 600px;
    margin: 2rem auto;
    padding: 0 1rem 4rem;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 2rem 0 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--color-primary);
}

.section-icon {
    font-size: 1.5rem;
}

.section-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--color-gray-900);
    margin: 0;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: var(--color-gray-700);
}

.form-label.required::after {
    content: ' *';
    color: #dc3545;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 1rem;
}

.form-control:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(0, 212, 170, 0.1);
}

.radio-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 0.75rem;
}

.radio-card {
    position: relative;
}

.radio-card input[type="radio"] {
    position: absolute;
    opacity: 0;
}

.radio-card label {
    display: block;
    padding: 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
}

.radio-card input[type="radio"]:checked + label {
    border-color: var(--color-primary);
    background: rgba(0, 212, 170, 0.05);
    font-weight: 600;
}

.radio-card label:hover {
    border-color: var(--color-primary);
}

.range-inputs {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 0.5rem;
    align-items: center;
}

.btn-submit {
    width: 100%;
    padding: 1rem;
    font-size: 1.1rem;
    font-weight: 600;
    margin-top: 2rem;
}

.help-text {
    font-size: 0.875rem;
    color: var(--color-gray-600);
    margin-top: 0.25rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 8px;
    transition: background 0.2s;
}

.checkbox-label:hover {
    background: #f9fafb;
}
</style>

<div class="profile-setup-container">
    <div style="text-align: center; margin-bottom: 2rem;">
        <h1 style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;">
            Hoàn thiện profile 👤
        </h1>
        <p style="color: var(--color-gray-600);">
            Càng chi tiết càng tìm được match phù hợp hơn!
        </p>
    </div>

    <?php if (hasFlash()): ?>
    <div class="alert alert-<?= getFlash('type') ?>">
        <?= getFlash('message') ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="">
        <?= csrfField() ?>

        <!-- ===== BASIC INFO ===== -->
        <div class="section-header">
            <span class="section-icon">📝</span>
            <h2 class="section-title">Thông tin cơ bản</h2>
        </div>

        <div class="form-group">
            <label for="name" class="form-label required">Tên hiển thị</label>
            <input type="text" id="name" name="name" class="form-control"
                   value="<?= e($currentUser['name'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="phone" class="form-label required">Số điện thoại</label>
            <input type="tel" id="phone" name="phone" class="form-control"
                   placeholder="0901234567"
                   value="<?= e($currentUser['phone'] ?? '') ?>" required>
            <div class="help-text">Sẽ được chia sẻ khi match thành công</div>
        </div>

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

        <div class="form-group">
            <label for="age" class="form-label required">Tuổi</label>
            <input type="number" id="age" name="age" class="form-control" min="18" max="100"
                   value="<?= e($currentUser['age'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="district_id" class="form-label required">Khu vực hiện tại</label>
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

        <div class="form-group">
            <label for="occupation" class="form-label">Nghề nghiệp</label>
            <input type="text" id="occupation" name="occupation" class="form-control"
                   placeholder="Ví dụ: Sinh viên, Developer, Designer..."
                   value="<?= e($currentUser['occupation'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="bio" class="form-label">Giới thiệu bản thân</label>
            <textarea id="bio" name="bio" class="form-control" rows="4"
                      placeholder="Kể về bản thân, sở thích, công việc..."><?= e($currentUser['bio'] ?? '') ?></textarea>
        </div>

        <!-- ===== SEARCH MODE ===== -->
        <div class="section-header">
            <span class="section-icon">🎯</span>
            <h2 class="section-title">Bạn muốn ưu tiên tìm gì trước?</h2>
        </div>

        <div class="radio-cards">
            <?php foreach (SEARCH_MODES as $value => $label): ?>
            <div class="radio-card">
                <input type="radio" id="mode_<?= e($value) ?>" name="search_mode" value="<?= e($value) ?>"
                       <?= ($currentUser['search_mode'] ?? 'find_roommate_first') === $value ? 'checked' : '' ?> required>
                <label for="mode_<?= e($value) ?>">
                    <?= $value === 'find_roommate_first' ? '👥' : '🏠' ?>
                    <div style="margin-top: 0.5rem;"><?= e($label) ?></div>
                </label>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- ===== LIFESTYLE PREFERENCES ===== -->
        <div class="section-header">
            <span class="section-icon">🌟</span>
            <h2 class="section-title">Thói quen sống</h2>
        </div>

        <div class="form-group">
            <label for="sleep_schedule" class="form-label">Lịch ngủ</label>
            <select id="sleep_schedule" name="sleep_schedule" class="form-control">
                <option value="">Chọn lịch ngủ</option>
                <option value="early_bird" <?= ($currentUser['sleep_schedule'] ?? '') === 'early_bird' ? 'selected' : '' ?>>
                    🌅 Đi ngủ sớm (trước 10pm)
                </option>
                <option value="night_owl" <?= ($currentUser['sleep_schedule'] ?? '') === 'night_owl' ? 'selected' : '' ?>>
                    🦉 Đi ngủ muộn (sau 12am)
                </option>
                <option value="flexible" <?= ($currentUser['sleep_schedule'] ?? '') === 'flexible' ? 'selected' : '' ?>>
                    🔄 Linh hoạt
                </option>
            </select>
        </div>

        <div class="form-group">
            <label for="work_schedule" class="form-label">Lịch làm việc</label>
            <select id="work_schedule" name="work_schedule" class="form-control">
                <option value="">Chọn lịch làm việc</option>
                <option value="office" <?= ($currentUser['work_schedule'] ?? '') === 'office' ? 'selected' : '' ?>>
                    👔 Văn phòng (9-5)
                </option>
                <option value="shift" <?= ($currentUser['work_schedule'] ?? '') === 'shift' ? 'selected' : '' ?>>
                    🔄 Ca xoay
                </option>
                <option value="wfh" <?= ($currentUser['work_schedule'] ?? '') === 'wfh' ? 'selected' : '' ?>>
                    🏠 Work from home
                </option>
                <option value="student" <?= ($currentUser['work_schedule'] ?? '') === 'student' ? 'selected' : '' ?>>
                    📚 Sinh viên
                </option>
            </select>
        </div>

        <div class="form-group">
            <label for="drinking" class="form-label">Uống rượu / Tiệc tùng</label>
            <select id="drinking" name="drinking" class="form-control">
                <option value="">Chọn preference</option>
                <option value="no" <?= ($currentUser['drinking'] ?? '') === 'no' ? 'selected' : '' ?>>
                    🚫 Không uống
                </option>
                <option value="social" <?= ($currentUser['drinking'] ?? '') === 'social' ? 'selected' : '' ?>>
                    🍻 Uống xã giao
                </option>
                <option value="frequent" <?= ($currentUser['drinking'] ?? '') === 'frequent' ? 'selected' : '' ?>>
                    🎉 Thường xuyên party
                </option>
            </select>
        </div>

        <div class="form-group">
            <label for="guests_policy" class="form-label">Chính sách khách</label>
            <select id="guests_policy" name="guests_policy" class="form-control">
                <option value="">Chọn preference</option>
                <option value="no_guests" <?= ($currentUser['guests_policy'] ?? '') === 'no_guests' ? 'selected' : '' ?>>
                    🚫 Không mời khách
                </option>
                <option value="occasional" <?= ($currentUser['guests_policy'] ?? '') === 'occasional' ? 'selected' : '' ?>>
                    👋 Thỉnh thoảng OK
                </option>
                <option value="frequent" <?= ($currentUser['guests_policy'] ?? '') === 'frequent' ? 'selected' : '' ?>>
                    🎊 Thường mời khách
                </option>
            </select>
        </div>

        <!-- ===== ROOM PREFERENCES ===== -->
        <div class="section-header">
            <span class="section-icon">💰</span>
            <h2 class="section-title">Preferences về phòng</h2>
        </div>

        <div class="form-group">
            <label class="form-label">Ngân sách (VND/tháng)</label>
            <div class="range-inputs">
                <input type="number" name="budget_min" class="form-control" placeholder="Tối thiểu"
                       value="<?= e($currentPrefs['budget_min'] ?? '') ?>">
                <span>-</span>
                <input type="number" name="budget_max" class="form-control" placeholder="Tối đa"
                       value="<?= e($currentPrefs['budget_max'] ?? '') ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="move_in_date" class="form-label">Ngày muốn chuyển vào</label>
            <input type="date" id="move_in_date" name="move_in_date" class="form-control"
                   value="<?= e($currentUser['move_in_date'] ?? '') ?>"
                   min="<?= date('Y-m-d') ?>">
        </div>

        <div class="form-group">
            <label for="stay_duration" class="form-label">Dự kiến ở bao lâu</label>
            <select id="stay_duration" name="stay_duration" class="form-control">
                <option value="">Chọn thời gian</option>
                <option value="1month" <?= ($currentUser['stay_duration'] ?? '') === '1month' ? 'selected' : '' ?>>
                    1 tháng
                </option>
                <option value="3months" <?= ($currentUser['stay_duration'] ?? '') === '3months' ? 'selected' : '' ?>>
                    3 tháng
                </option>
                <option value="6months" <?= ($currentUser['stay_duration'] ?? '') === '6months' ? 'selected' : '' ?>>
                    6 tháng
                </option>
                <option value="1year_plus" <?= ($currentUser['stay_duration'] ?? '') === '1year_plus' ? 'selected' : '' ?>>
                    1 năm trở lên
                </option>
            </select>
        </div>

        <div class="form-group">
            <label for="cleanliness" class="form-label">Mức độ sạch sẽ</label>
            <select id="cleanliness" name="cleanliness" class="form-control">
                <?php foreach (PREFERENCE_LEVELS as $value => $label): ?>
                <option value="<?= $value ?>" <?= ($currentPrefs['cleanliness'] ?? 3) == $value ? 'selected' : '' ?>>
                    <?= e($label) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="noise_tolerance" class="form-label">Dung nạp tiếng ồn</label>
            <select id="noise_tolerance" name="noise_tolerance" class="form-control">
                <?php foreach (PREFERENCE_LEVELS as $value => $label): ?>
                <option value="<?= $value ?>" <?= ($currentPrefs['noise_tolerance'] ?? 3) == $value ? 'selected' : '' ?>>
                    <?= e($label) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="smoking" value="1"
                       <?= ($currentPrefs['smoking'] ?? false) ? 'checked' : '' ?>>
                <span>🚬 Tôi hút thuốc</span>
            </label>
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="pets" value="1"
                       <?= ($currentPrefs['pets'] ?? false) ? 'checked' : '' ?>>
                <span>🐕 Tôi có/thích nuôi thú cưng</span>
            </label>
        </div>

        <!-- ===== SOCIAL VERIFICATION (Optional) ===== -->
        <div class="section-header">
            <span class="section-icon">🔗</span>
            <h2 class="section-title">Xác minh (Tùy chọn)</h2>
        </div>

        <div class="form-group">
            <label for="facebook_url" class="form-label">Facebook Profile</label>
            <input type="url" id="facebook_url" name="facebook_url" class="form-control"
                   placeholder="https://facebook.com/yourprofile"
                   value="<?= e($currentUser['facebook_url'] ?? '') ?>">
            <div class="help-text">Giúp tăng độ tin cậy của profile</div>
        </div>

        <div class="form-group">
            <label for="linkedin_url" class="form-label">LinkedIn Profile</label>
            <input type="url" id="linkedin_url" name="linkedin_url" class="form-control"
                   placeholder="https://linkedin.com/in/yourprofile"
                   value="<?= e($currentUser['linkedin_url'] ?? '') ?>">
        </div>

        <button type="submit" class="btn btn-primary btn-submit">
            <?= isset($_GET['edit']) ? 'Cập nhật profile' : 'Hoàn tất và bắt đầu tìm kiếm' ?> →
        </button>
    </form>
</div>

<?php
include __DIR__ . '/../components/footer.php';
?>
