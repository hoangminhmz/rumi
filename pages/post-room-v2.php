<?php
/**
 * RUMI - Enhanced Room Posting V2
 * Features: Image upload, Mapbox address picker, room type selection
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
$db = getDB();

// Get amenities from database
$stmt = $db->query("SELECT * FROM amenities_list WHERE is_active = 1 ORDER BY sort_order");
$amenities = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF
        if (!validateCSRF($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid CSRF token');
        }

        // Validate required fields
        if (empty($_POST['title']) || empty($_POST['price']) || empty($_POST['address'])) {
            throw new Exception('Vui lòng điền đầy đủ thông tin bắt buộc');
        }

        // Build amenities array
        $roomAmenities = [];
        if (isset($_POST['amenities']) && is_array($_POST['amenities'])) {
            foreach ($_POST['amenities'] as $amenityCode) {
                $roomAmenities[$amenityCode] = true;
            }
        }

        // Handle image uploads
        $uploadedImages = [];
        if (isset($_FILES['images'])) {
            $uploadDir = __DIR__ . '/../assets/uploads/rooms/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileCount = count($_FILES['images']['name']);
            for ($i = 0; $i < min($fileCount, 10); $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
                    $filename = 'room_' . uniqid() . '_' . time() . '.' . $ext;
                    $uploadPath = $uploadDir . $filename;

                    if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $uploadPath)) {
                        $uploadedImages[] = $filename;
                    }
                }
            }
        }

        // Create room
        $data = [
            'title' => sanitizeInput($_POST['title']),
            'description' => sanitizeInput($_POST['description'] ?? ''),
            'price' => (int)$_POST['price'],
            'area' => !empty($_POST['area']) ? (float)$_POST['area'] : null,
            'district_id' => !empty($_POST['district_id']) ? (int)$_POST['district_id'] : null,
            'address' => sanitizeInput($_POST['address']),
            'ward' => sanitizeInput($_POST['ward'] ?? ''),
            'latitude' => !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null,
            'longitude' => !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null,
            'room_type' => $_POST['room_type'] ?? null,
            'amenities' => $roomAmenities,
            'images' => $uploadedImages
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

<style>
.post-room-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem 1rem 4rem;
}

.post-room-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
    padding: 2rem;
}

.post-room-header {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid #f3f4f6;
}

.post-room-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--color-gray-900);
    margin-bottom: 0.5rem;
}

.post-room-subtitle {
    color: var(--color-gray-600);
    font-size: 0.95rem;
}

.post-room-subtitle strong {
    color: var(--color-primary);
}

/* Form Sections */
.form-section {
    margin-bottom: 2rem;
}

.form-section-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--color-gray-800);
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-group {
    margin-bottom: 1.25rem;
}

.form-label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--color-gray-700);
    font-size: 0.95rem;
}

.form-label.required::after {
    content: " *";
    color: #ef4444;
}

.form-input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    font-size: 0.95rem;
    transition: all 0.2s;
}

.form-input:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.form-textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    font-size: 0.95rem;
    resize: vertical;
    min-height: 120px;
    font-family: inherit;
}

.form-textarea:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.form-select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    font-size: 0.95rem;
    cursor: pointer;
}

.form-select:focus {
    outline: none;
    border-color: var(--color-primary);
}

/* Image Upload Area */
.image-upload-area {
    border: 2px dashed #d1d5db;
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    background: #f9fafb;
    cursor: pointer;
    transition: all 0.2s;
}

.image-upload-area:hover {
    border-color: var(--color-primary);
    background: #f3f4f6;
}

.image-upload-area.dragover {
    border-color: var(--color-primary);
    background: rgba(99, 102, 241, 0.05);
}

.image-upload-icon {
    width: 60px;
    height: 60px;
    margin: 0 auto 1rem;
    color: var(--color-gray-400);
}

.image-upload-text {
    color: var(--color-gray-700);
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.image-upload-hint {
    color: var(--color-gray-500);
    font-size: 0.85rem;
}

/* Image Previews */
.image-previews {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.image-preview {
    position: relative;
    aspect-ratio: 1;
    border-radius: 10px;
    overflow: hidden;
    border: 2px solid #e5e7eb;
}

.image-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.image-preview-remove {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.image-preview-remove:hover {
    background: #ef4444;
    transform: scale(1.1);
}

/* Room Type Cards */
.room-type-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.room-type-card {
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 1rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    background: white;
}

.room-type-card:hover {
    border-color: var(--color-primary);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.room-type-card.selected {
    border-color: var(--color-primary);
    background: rgba(99, 102, 241, 0.05);
}

.room-type-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.room-type-label {
    font-weight: 600;
    color: var(--color-gray-700);
    font-size: 0.9rem;
}

.room-type-card.selected .room-type-label {
    color: var(--color-primary);
}

/* Amenities Grid */
.amenities-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
}

.amenity-checkbox {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.9rem;
}

.amenity-checkbox:hover {
    border-color: var(--color-primary);
    background: #f9fafb;
}

.amenity-checkbox input[type="checkbox"] {
    cursor: pointer;
}

.amenity-checkbox input[type="checkbox"]:checked + span {
    color: var(--color-primary);
    font-weight: 600;
}

/* Submit Button */
.btn-submit {
    width: 100%;
    padding: 1rem;
    font-size: 1.1rem;
    font-weight: 600;
    border-radius: 12px;
    background: var(--color-primary);
    color: white;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-submit:hover {
    background: var(--color-accent);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(99, 102, 241, 0.3);
}

.btn-submit:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

/* Alert Box */
.alert-info {
    background: #dbeafe;
    border-left: 4px solid #3b82f6;
    padding: 1rem;
    border-radius: 8px;
    color: #1e40af;
    font-size: 0.9rem;
    margin-bottom: 1.5rem;
}

/* Mobile Responsive */
@media (max-width: 640px) {
    .post-room-container {
        padding: 1rem 0.5rem 3rem;
    }

    .post-room-card {
        padding: 1.5rem 1rem;
    }

    .room-type-grid {
        grid-template-columns: 1fr;
    }

    .amenities-grid {
        grid-template-columns: 1fr;
    }

    .image-previews {
        grid-template-columns: repeat(3, 1fr);
    }
}
</style>

<div class="post-room-container">
    <div class="post-room-card">
        <div class="post-room-header">
            <h1 class="post-room-title">🏠 Đăng phòng trọ</h1>
            <p class="post-room-subtitle">
                Phí đăng tin: <strong><?= formatPrice(ROOM_LISTING_FEE) ?></strong> cho <?= ROOM_LISTING_DURATION ?> ngày
            </p>
        </div>

        <form method="POST" action="" enctype="multipart/form-data" id="postRoomForm">
            <?= csrfField() ?>

            <!-- SECTION 1: IMAGES -->
            <div class="form-section">
                <h3 class="form-section-title">📸 Hình ảnh phòng</h3>

                <div class="image-upload-area" id="imageUploadArea">
                    <svg class="image-upload-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="image-upload-text">Kéo thả hoặc nhấn để chọn ảnh</p>
                    <p class="image-upload-hint">Tối đa 10 ảnh, mỗi ảnh không quá 5MB</p>
                    <input type="file" id="imageInput" name="images[]" accept="image/*" multiple hidden>
                </div>

                <div class="image-previews" id="imagePreviews"></div>
            </div>

            <!-- SECTION 2: BASIC INFO -->
            <div class="form-section">
                <h3 class="form-section-title">📝 Thông tin cơ bản</h3>

                <div class="form-group">
                    <label class="form-label required" for="title">Tiêu đề</label>
                    <input type="text" id="title" name="title" class="form-input"
                           placeholder="VD: Phòng đẹp gần công viên, full nội thất"
                           value="<?= e($_POST['title'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Loại phòng</label>
                    <div class="room-type-grid">
                        <div class="room-type-card" data-type="apartment">
                            <div class="room-type-icon">🏢</div>
                            <div class="room-type-label">Chung cư</div>
                        </div>
                        <div class="room-type-card" data-type="house">
                            <div class="room-type-icon">🏘️</div>
                            <div class="room-type-label">Nhà riêng</div>
                        </div>
                        <div class="room-type-card" data-type="mini_apartment">
                            <div class="room-type-icon">🏡</div>
                            <div class="room-type-label">Căn hộ mini</div>
                        </div>
                        <div class="room-type-card" data-type="villa">
                            <div class="room-type-icon">🏰</div>
                            <div class="room-type-label">Villa</div>
                        </div>
                    </div>
                    <input type="hidden" id="room_type" name="room_type" value="">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label required" for="price">Giá thuê (VND/tháng)</label>
                        <input type="number" id="price" name="price" class="form-input"
                               placeholder="3000000"
                               value="<?= e($_POST['price'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="area">Diện tích (m²)</label>
                        <input type="number" id="area" name="area" class="form-input" step="0.1"
                               placeholder="25"
                               value="<?= e($_POST['area'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Mô tả chi tiết</label>
                    <textarea id="description" name="description" class="form-textarea"
                              placeholder="Mô tả về phòng, khu vực xung quanh, tiện ích..."><?= e($_POST['description'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- SECTION 3: LOCATION -->
            <div class="form-section">
                <h3 class="form-section-title">📍 Vị trí</h3>

                <div class="form-group">
                    <label class="form-label required" for="addressSearch">Tìm địa chỉ</label>
                    <input type="text" id="addressSearch" class="form-input"
                           placeholder="🔍 Nhập địa chỉ để tìm kiếm...">
                    <div id="addressResults" style="position: relative;">
                        <!-- Results will be displayed here -->
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label required" for="address">Địa chỉ đầy đủ</label>
                    <input type="text" id="address" name="address" class="form-input"
                           placeholder="Địa chỉ sẽ được tự động điền"
                           value="<?= e($_POST['address'] ?? '') ?>" required readonly>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label" for="ward">Phường/Xã</label>
                        <input type="text" id="ward" name="ward" class="form-input"
                               placeholder="Tự động điền"
                               value="<?= e($_POST['ward'] ?? '') ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="district_id">Quận/Huyện</label>
                        <input type="text" id="district_display" class="form-input"
                               placeholder="Tự động điền" readonly>
                        <input type="hidden" id="district_id" name="district_id" value="">
                    </div>
                </div>

                <input type="hidden" id="latitude" name="latitude" value="">
                <input type="hidden" id="longitude" name="longitude" value="">
            </div>

            <!-- SECTION 4: AMENITIES -->
            <div class="form-section">
                <h3 class="form-section-title">⭐ Tiện nghi</h3>

                <div class="amenities-grid">
                    <?php foreach ($amenities as $amenity): ?>
                        <label class="amenity-checkbox">
                            <input type="checkbox" name="amenities[]" value="<?= $amenity['code'] ?>">
                            <span><?= $amenity['icon'] ?> <?= htmlspecialchars($amenity['name_vi']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="alert-info">
                <strong>💡 Lưu ý:</strong> Sau khi tạo, bạn sẽ cần thanh toán phí đăng tin <?= formatPrice(ROOM_LISTING_FEE) ?> để phòng được hiển thị.
            </div>

            <button type="submit" class="btn-submit" id="submitBtn">
                Tiếp tục thanh toán
            </button>
        </form>
    </div>
</div>

<script>
// Mapbox Token
const MAPBOX_TOKEN = 'pk.eyJ1IjoiaG9hbmdtaW5obXoiLCJhIjoiY2xldzc0MXNzMDN4MTNwcGs2aWhnaHFxZiJ9.SdmGxY_wgOBXGv8PzmMB3w';

// ===== IMAGE UPLOAD =====
const imageUploadArea = document.getElementById('imageUploadArea');
const imageInput = document.getElementById('imageInput');
const imagePreviews = document.getElementById('imagePreviews');
let selectedImages = [];

// Click to upload
imageUploadArea.addEventListener('click', () => {
    imageInput.click();
});

// Drag and drop
imageUploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    imageUploadArea.classList.add('dragover');
});

imageUploadArea.addEventListener('dragleave', () => {
    imageUploadArea.classList.remove('dragover');
});

imageUploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    imageUploadArea.classList.remove('dragover');
    const files = Array.from(e.dataTransfer.files).filter(f => f.type.startsWith('image/'));
    handleFiles(files);
});

// File input change
imageInput.addEventListener('change', (e) => {
    const files = Array.from(e.target.files);
    handleFiles(files);
});

function handleFiles(files) {
    // Limit to 10 images
    const remainingSlots = 10 - selectedImages.length;
    const filesToAdd = files.slice(0, remainingSlots);

    filesToAdd.forEach(file => {
        if (file.size > 5 * 1024 * 1024) {
            alert(`File ${file.name} quá lớn (>5MB)`);
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            selectedImages.push({
                file: file,
                dataUrl: e.target.result
            });
            renderImagePreviews();
        };
        reader.readAsDataURL(file);
    });
}

function renderImagePreviews() {
    imagePreviews.innerHTML = selectedImages.map((img, index) => `
        <div class="image-preview">
            <img src="${img.dataUrl}" alt="Preview ${index + 1}">
            <button type="button" class="image-preview-remove" onclick="removeImage(${index})">
                ✕
            </button>
        </div>
    `).join('');

    // Update file input
    const dataTransfer = new DataTransfer();
    selectedImages.forEach(img => {
        dataTransfer.items.add(img.file);
    });
    imageInput.files = dataTransfer.files;
}

function removeImage(index) {
    selectedImages.splice(index, 1);
    renderImagePreviews();
}

// ===== ROOM TYPE SELECTION =====
document.querySelectorAll('.room-type-card').forEach(card => {
    card.addEventListener('click', () => {
        document.querySelectorAll('.room-type-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
        document.getElementById('room_type').value = card.dataset.type;
    });
});

// ===== MAPBOX ADDRESS SEARCH =====
const addressSearch = document.getElementById('addressSearch');
const addressResults = document.getElementById('addressResults');
let searchTimeout;

addressSearch.addEventListener('input', (e) => {
    clearTimeout(searchTimeout);
    const query = e.target.value.trim();

    if (query.length < 3) {
        addressResults.innerHTML = '';
        return;
    }

    searchTimeout = setTimeout(async () => {
        try {
            const response = await fetch(
                `https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(query)}.json?` +
                `access_token=${MAPBOX_TOKEN}&country=VN&limit=5&proximity=105.8342,21.0278&language=vi`
            );
            const data = await response.json();
            displayAddressResults(data.features);
        } catch (error) {
            console.error('Address search error:', error);
        }
    }, 300);
});

function displayAddressResults(features) {
    if (features.length === 0) {
        addressResults.innerHTML = '';
        return;
    }

    addressResults.innerHTML = `
        <div style="position: absolute; top: 0.5rem; left: 0; right: 0; background: white; border: 1px solid #e5e7eb; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); max-height: 300px; overflow-y: auto; z-index: 10;">
            ${features.map(feature => `
                <div style="padding: 0.75rem 1rem; cursor: pointer; border-bottom: 1px solid #f3f4f6; transition: background 0.2s;"
                     onmouseover="this.style.background='#f9fafb'"
                     onmouseout="this.style.background='white'"
                     onclick='selectAddress(${JSON.stringify(feature)})'>
                    <div style="font-weight: 600; color: var(--color-gray-800);">${feature.text}</div>
                    <div style="font-size: 0.85rem; color: var(--color-gray-600);">${feature.place_name}</div>
                </div>
            `).join('')}
        </div>
    `;
}

async function selectAddress(feature) {
    // Set coordinates
    document.getElementById('latitude').value = feature.center[1];
    document.getElementById('longitude').value = feature.center[0];

    // Set full address
    document.getElementById('address').value = feature.place_name;
    document.getElementById('addressSearch').value = feature.text;

    // Parse address components
    const context = feature.context || [];

    // Extract ward (locality)
    const locality = context.find(c => c.id.startsWith('locality'));
    if (locality) {
        document.getElementById('ward').value = locality.text;
    }

    // Extract district (place)
    const place = context.find(c => c.id.startsWith('place'));
    if (place) {
        const districtName = place.text;
        document.getElementById('district_display').value = districtName;

        // Find matching district ID from database
        // We'll need to make a simple API call or use a mapping
        // For now, we'll store the name and handle it on backend
        document.getElementById('district_id').value = ''; // Will be resolved on backend
    }

    // Clear results
    addressResults.innerHTML = '';
}

// Close results when clicking outside
document.addEventListener('click', (e) => {
    if (!addressSearch.contains(e.target) && !addressResults.contains(e.target)) {
        addressResults.innerHTML = '';
    }
});

// ===== FORM VALIDATION =====
document.getElementById('postRoomForm').addEventListener('submit', (e) => {
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Đang xử lý...';
});
</script>

<?php
include __DIR__ . '/../components/navigation.php';
include __DIR__ . '/../components/footer.php';
?>
