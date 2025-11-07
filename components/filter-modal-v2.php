<?php
/**
 * RUMI - Enhanced Filter Modal V2
 * Tabbed interface with Mapbox location picker and lifestyle preferences
 */

// Load dynamic lifestyle preferences
require_once __DIR__ . '/filter-lifestyle-dynamic.php';
?>

<!-- Enhanced Filter Modal -->
<div id="filterModal" class="filter-modal" style="display: none;">
    <div class="filter-modal-content">
        <div class="filter-modal-header">
            <h2 class="filter-modal-title">🔍 Bộ lọc nâng cao</h2>
            <button class="filter-modal-close" onclick="closeFilterModal()">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Tabs -->
        <div class="filter-tabs">
            <button class="filter-tab active" data-tab="basic" onclick="switchFilterTab('basic')">
                🎯 Cơ bản
            </button>
            <button class="filter-tab" data-tab="lifestyle" onclick="switchFilterTab('lifestyle')">
                🌟 Lối sống
            </button>
            <button class="filter-tab" data-tab="room" onclick="switchFilterTab('room')">
                🏠 Chi tiết phòng
            </button>
        </div>

        <div class="filter-modal-body">
            <!-- TAB 1: BASIC -->
            <div class="filter-tab-content active" data-tab-content="basic">
                <!-- Budget -->
                <div class="filter-group">
                    <label class="filter-label">💰 Ngân sách (VND/tháng)</label>
                    <div class="filter-row">
                        <input type="number" id="filterBudgetMin" class="filter-input"
                               placeholder="Tối thiểu" value="<?= $userPreferences['budget_min'] ?? 0 ?>">
                        <span style="color: #9ca3af;">→</span>
                        <input type="number" id="filterBudgetMax" class="filter-input"
                               placeholder="Tối đa" value="<?= $userPreferences['budget_max'] ?? 10000000 ?>">
                    </div>
                </div>

                <!-- Location / Distance -->
                <div class="filter-group">
                    <label class="filter-label">📍 Vị trí</label>

                    <!-- District Dropdown -->
                    <select id="filterDistrict" class="filter-input" style="margin-bottom: 0.75rem;">
                        <option value="">Tất cả quận/huyện</option>
                        <?php
                        $userModel = new User();
                        $districts = $userModel->getDistricts('Hà Nội');
                        foreach ($districts as $district):
                        ?>
                            <option value="<?= $district['id'] ?>"><?= htmlspecialchars($district['name']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Mapbox Search -->
                    <div class="filter-location-search">
                        <input type="text" id="filterLocationSearch" class="filter-input"
                               placeholder="🔍 Tìm địa chỉ cụ thể (tùy chọn)">
                        <div id="filterLocationResults" class="location-results"></div>
                    </div>

                    <!-- Distance Slider (only shown when location selected) -->
                    <div id="filterDistanceGroup" style="display: none; margin-top: 1rem;">
                        <label class="filter-label-sm">
                            Khoảng cách: <span id="filterDistanceValue">5</span> km
                        </label>
                        <input type="range" id="filterDistance" class="filter-slider"
                               min="1" max="20" value="5" step="0.5">
                        <div class="filter-slider-labels">
                            <span>1km</span>
                            <span>20km</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: LIFESTYLE (Dynamic from Database) -->
            <div class="filter-tab-content" data-tab-content="lifestyle">
                <?php foreach ($dynamicLifestylePrefs as $pref): ?>
                    <?php renderPreferenceFilter($pref); ?>
                <?php endforeach; ?>
            </div>

            <!-- TAB 3: ROOM DETAILS -->
            <div class="filter-tab-content" data-tab-content="room">
                <!-- Room Type -->
                <div class="filter-group">
                    <label class="filter-label">🏠 Loại phòng</label>
                    <div class="filter-button-group">
                        <button class="filter-option-btn" data-value="" data-filter="room_type">Bất kỳ</button>
                        <button class="filter-option-btn" data-value="apartment" data-filter="room_type">🏢 Chung cư</button>
                        <button class="filter-option-btn" data-value="house" data-filter="room_type">🏘️ Nhà riêng</button>
                        <button class="filter-option-btn" data-value="mini_apartment" data-filter="room_type">🏡 Mini</button>
                        <button class="filter-option-btn" data-value="villa" data-filter="room_type">🏰 Villa</button>
                    </div>
                </div>

                <!-- Amenities -->
                <div class="filter-group">
                    <div class="filter-collapsible-header" onclick="toggleAmenities()">
                        <label class="filter-label" style="margin: 0;">⭐ Tiện nghi</label>
                        <svg id="amenitiesChevron" class="filter-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>

                    <div id="amenitiesContent" class="filter-amenities-grid" style="display: none; margin-top: 0.75rem;">
                        <?php
                        // Get amenities from database
                        $db = getDB();
                        $stmt = $db->query("SELECT * FROM amenities_list WHERE is_active = 1 ORDER BY sort_order");
                        $amenities = $stmt->fetchAll();
                        foreach ($amenities as $amenity):
                        ?>
                            <label class="filter-amenity-checkbox">
                                <input type="checkbox" name="amenities[]" value="<?= $amenity['code'] ?>">
                                <span><?= $amenity['icon'] ?> <?= htmlspecialchars($amenity['name_vi']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Move-in Date Range -->
                <div class="filter-group">
                    <label class="filter-label">📅 Ngày dọn vào</label>
                    <div class="filter-row">
                        <input type="date" id="filterMoveInFrom" class="filter-input"
                               placeholder="Từ ngày">
                        <span style="color: #9ca3af;">→</span>
                        <input type="date" id="filterMoveInTo" class="filter-input"
                               placeholder="Đến ngày">
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Actions -->
        <div class="filter-modal-footer">
            <button class="btn btn-outline" style="flex: 1;" onclick="resetFilters()">
                Đặt lại
            </button>
            <button class="btn btn-primary" style="flex: 1;" onclick="applyFilters()">
                Áp dụng
            </button>
        </div>
    </div>
</div>

<style>
/* Filter Modal V2 Styles */
.filter-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 1rem;
    animation: fadeIn 0.2s;
}

.filter-modal-content {
    background: white;
    border-radius: 20px;
    max-width: 600px;
    width: 100%;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.filter-modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.filter-modal-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-gray-900);
    margin: 0;
}

.filter-modal-close {
    background: none;
    border: none;
    padding: 0.5rem;
    cursor: pointer;
    color: var(--color-gray-500);
    border-radius: 8px;
    transition: all 0.2s;
}

.filter-modal-close:hover {
    background: #f3f4f6;
    color: var(--color-gray-700);
}

.filter-modal-close svg {
    width: 24px;
    height: 24px;
}

/* Tabs */
.filter-tabs {
    display: flex;
    border-bottom: 2px solid #e5e7eb;
    background: #f9fafb;
}

.filter-tab {
    flex: 1;
    padding: 1rem;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    font-weight: 600;
    color: var(--color-gray-600);
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.95rem;
}

.filter-tab:hover {
    color: var(--color-primary);
    background: white;
}

.filter-tab.active {
    color: var(--color-primary);
    border-bottom-color: var(--color-primary);
    background: white;
}

/* Tab Content */
.filter-modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
}

.filter-tab-content {
    display: none;
}

.filter-tab-content.active {
    display: block;
}

/* Filter Groups */
.filter-group {
    margin-bottom: 1.5rem;
}

.filter-label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.75rem;
    color: var(--color-gray-700);
    font-size: 0.95rem;
}

.filter-label-sm {
    display: block;
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: var(--color-gray-600);
    font-size: 0.85rem;
}

.filter-row {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.filter-input {
    flex: 1;
    padding: 0.75rem;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    font-size: 0.95rem;
    transition: all 0.2s;
}

.filter-input:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

/* Button Groups */
.filter-button-group {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.filter-option-btn {
    padding: 0.6rem 1rem;
    border: 2px solid #e5e7eb;
    background: white;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    color: var(--color-gray-700);
}

.filter-option-btn:hover {
    border-color: var(--color-primary);
    color: var(--color-primary);
}

.filter-option-btn.active {
    border-color: var(--color-primary);
    background: var(--color-primary);
    color: white;
}

/* Scale Buttons */
.filter-scale-group {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.filter-scale-btn {
    flex: 1;
    padding: 0.75rem;
    border: 2px solid #e5e7eb;
    background: white;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    color: var(--color-gray-700);
}

.filter-scale-btn:hover {
    border-color: var(--color-primary);
    transform: translateY(-2px);
}

.filter-scale-btn.active {
    border-color: var(--color-primary);
    background: var(--color-primary);
    color: white;
}

.filter-scale-labels {
    display: flex;
    justify-content: space-between;
    color: var(--color-gray-500);
    font-size: 0.8rem;
}

/* Slider */
.filter-slider {
    width: 100%;
    height: 6px;
    border-radius: 3px;
    background: #e5e7eb;
    outline: none;
    -webkit-appearance: none;
}

.filter-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--color-primary);
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(99, 102, 241, 0.4);
}

.filter-slider::-moz-range-thumb {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--color-primary);
    cursor: pointer;
    border: none;
    box-shadow: 0 2px 8px rgba(99, 102, 241, 0.4);
}

.filter-slider-labels {
    display: flex;
    justify-content: space-between;
    color: var(--color-gray-500);
    font-size: 0.8rem;
    margin-top: 0.25rem;
}

/* Location Search */
.filter-location-search {
    position: relative;
}

.location-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    margin-top: 0.5rem;
    max-height: 200px;
    overflow-y: auto;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    z-index: 10;
    display: none;
}

.location-results.active {
    display: block;
}

.location-result-item {
    padding: 0.75rem 1rem;
    cursor: pointer;
    border-bottom: 1px solid #f3f4f6;
    transition: background 0.2s;
}

.location-result-item:hover {
    background: #f9fafb;
}

.location-result-item:last-child {
    border-bottom: none;
}

/* Collapsible */
.filter-collapsible-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    user-select: none;
}

.filter-chevron {
    width: 20px;
    height: 20px;
    color: var(--color-gray-500);
    transition: transform 0.3s;
}

.filter-chevron.open {
    transform: rotate(180deg);
}

/* Amenities Grid */
.filter-amenities-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
}

.filter-amenity-checkbox {
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

.filter-amenity-checkbox:hover {
    border-color: var(--color-primary);
    background: #f9fafb;
}

.filter-amenity-checkbox input[type="checkbox"] {
    cursor: pointer;
}

.filter-amenity-checkbox input[type="checkbox"]:checked + span {
    color: var(--color-primary);
    font-weight: 600;
}

/* Footer */
.filter-modal-footer {
    padding: 1.5rem;
    border-top: 1px solid #e5e7eb;
    display: flex;
    gap: 1rem;
}

/* Mobile Responsive */
@media (max-width: 640px) {
    .filter-modal-content {
        max-height: 95vh;
    }

    .filter-tabs {
        font-size: 0.85rem;
    }

    .filter-tab {
        padding: 0.75rem 0.5rem;
    }

    .filter-amenities-grid {
        grid-template-columns: 1fr;
    }

    .filter-button-group {
        gap: 0.4rem;
    }

    .filter-option-btn {
        font-size: 0.85rem;
        padding: 0.5rem 0.75rem;
    }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
</style>

<script>
// Mapbox Access Token (replace with your token)
const MAPBOX_TOKEN = 'pk.eyJ1IjoiaG9hbmdtaW5obXoiLCJhIjoiY2xldzc0MXNzMDN4MTNwcGs2aWhnaHFxZiJ9.SdmGxY_wgOBXGv8PzmMB3w';

// Dynamic lifestyle preference codes from database
const LIFESTYLE_PREFS = <?= json_encode(array_map(function($p) {
    return ['code' => $p['code'], 'type' => $p['field_type']];
}, $dynamicLifestylePrefs)) ?>;

// Filter state - Initialize dynamically
let filterState = {
    budgetMin: <?= $userPreferences['budget_min'] ?? 0 ?>,
    budgetMax: <?= $userPreferences['budget_max'] ?? 10000000 ?>,
    district: '',
    location: null,
    distance: 5,
    roomType: '',
    amenities: [],
    moveInFrom: '',
    moveInTo: ''
};

// Add dynamic lifestyle preferences to filterState
LIFESTYLE_PREFS.forEach(pref => {
    filterState[pref.code] = (pref.type === 'scale') ? null : '';
});

// Open filter modal
function openFilterModal() {
    document.getElementById('filterModal').style.display = 'flex';
    initializeFilterUI();
}

// Close filter modal
function closeFilterModal() {
    document.getElementById('filterModal').style.display = 'none';
}

// Switch tabs
function switchFilterTab(tabName) {
    // Update tab buttons
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.classList.toggle('active', tab.dataset.tab === tabName);
    });

    // Update tab content
    document.querySelectorAll('.filter-tab-content').forEach(content => {
        content.classList.toggle('active', content.dataset.tabContent === tabName);
    });
}

// Initialize filter UI with current values
function initializeFilterUI() {
    // Budget
    document.getElementById('filterBudgetMin').value = filterState.budgetMin;
    document.getElementById('filterBudgetMax').value = filterState.budgetMax;

    // District
    const districtSelect = document.getElementById('filterDistrict');
    if (districtSelect && filterState.district) {
        districtSelect.value = filterState.district;
    }

    // Distance
    document.getElementById('filterDistance').value = filterState.distance;
    document.getElementById('filterDistanceValue').textContent = filterState.distance;

    // Update all button states
    updateButtonStates();
}

// Update button states based on filterState
function updateButtonStates() {
    document.querySelectorAll('.filter-option-btn, .filter-scale-btn').forEach(btn => {
        const filterType = btn.dataset.filter;
        const value = btn.dataset.value;

        if (!filterType) return;

        let isActive = false;

        // Check if this is a scale-type preference (has .filter-scale-btn class)
        if (btn.classList.contains('filter-scale-btn')) {
            isActive = filterState[filterType] === parseInt(value);
        } else {
            isActive = filterState[filterType] === value;
        }

        btn.classList.toggle('active', isActive);
    });
}

// Handle filter button clicks
document.addEventListener('click', (e) => {
    const btn = e.target.closest('.filter-option-btn, .filter-scale-btn');
    if (!btn) return;

    const filterType = btn.dataset.filter;
    const value = btn.dataset.value;

    // Handle scale-type buttons (always set to clicked value)
    if (btn.classList.contains('filter-scale-btn')) {
        filterState[filterType] = parseInt(value);
    } else {
        // Handle option buttons (toggle: if already selected, deselect)
        if (filterState[filterType] === value) {
            filterState[filterType] = '';
        } else {
            filterState[filterType] = value;
        }
    }

    updateButtonStates();
});

// Location search with Mapbox
let searchTimeout;
document.getElementById('filterLocationSearch')?.addEventListener('input', (e) => {
    clearTimeout(searchTimeout);
    const query = e.target.value.trim();

    if (query.length < 3) {
        document.getElementById('filterLocationResults').classList.remove('active');
        return;
    }

    searchTimeout = setTimeout(async () => {
        try {
            const response = await fetch(
                `https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(query)}.json?` +
                `access_token=${MAPBOX_TOKEN}&country=VN&limit=5&proximity=105.8342,21.0278`
            );
            const data = await response.json();

            displayLocationResults(data.features);
        } catch (error) {
            console.error('Location search error:', error);
        }
    }, 300);
});

function displayLocationResults(features) {
    const resultsDiv = document.getElementById('filterLocationResults');

    if (features.length === 0) {
        resultsDiv.classList.remove('active');
        return;
    }

    resultsDiv.innerHTML = features.map(feature => `
        <div class="location-result-item" onclick='selectLocation(${JSON.stringify(feature)})'>
            <div style="font-weight: 600;">${feature.text}</div>
            <div style="font-size: 0.85rem; color: #6b7280;">${feature.place_name}</div>
        </div>
    `).join('');

    resultsDiv.classList.add('active');
}

function selectLocation(feature) {
    filterState.location = {
        name: feature.place_name,
        latitude: feature.center[1],
        longitude: feature.center[0]
    };

    document.getElementById('filterLocationSearch').value = feature.text;
    document.getElementById('filterLocationResults').classList.remove('active');
    document.getElementById('filterDistanceGroup').style.display = 'block';
}

// Distance slider
document.getElementById('filterDistance')?.addEventListener('input', (e) => {
    filterState.distance = e.target.value;
    document.getElementById('filterDistanceValue').textContent = e.target.value;
});

// Toggle amenities
function toggleAmenities() {
    const content = document.getElementById('amenitiesContent');
    const chevron = document.getElementById('amenitiesChevron');
    const isOpen = content.style.display !== 'none';

    content.style.display = isOpen ? 'none' : 'block';
    chevron.classList.toggle('open', !isOpen);
}

// Apply filters
function applyFilters() {
    // Read values from inputs
    filterState.budgetMin = parseInt(document.getElementById('filterBudgetMin').value) || 0;
    filterState.budgetMax = parseInt(document.getElementById('filterBudgetMax').value) || 10000000;
    filterState.district = document.getElementById('filterDistrict').value;
    filterState.moveInFrom = document.getElementById('filterMoveInFrom').value;
    filterState.moveInTo = document.getElementById('filterMoveInTo').value;

    // Get selected amenities
    filterState.amenities = Array.from(document.querySelectorAll('input[name="amenities[]"]:checked'))
        .map(cb => cb.value);

    // Build URL params
    const params = new URLSearchParams(window.location.search);

    // Basic
    params.set('budget_min', filterState.budgetMin);
    params.set('budget_max', filterState.budgetMax);
    if (filterState.district) params.set('district', filterState.district);
    if (filterState.location) {
        params.set('lat', filterState.location.latitude);
        params.set('lng', filterState.location.longitude);
        params.set('distance', filterState.distance);
    }

    // Lifestyle - Dynamic preferences
    LIFESTYLE_PREFS.forEach(pref => {
        const value = filterState[pref.code];
        if (value !== null && value !== '' && value !== undefined) {
            params.set(pref.code, value);
        }
    });

    // Room details
    if (filterState.roomType) params.set('room_type', filterState.roomType);
    if (filterState.amenities.length > 0) params.set('amenities', filterState.amenities.join(','));
    if (filterState.moveInFrom) params.set('move_in_from', filterState.moveInFrom);
    if (filterState.moveInTo) params.set('move_in_to', filterState.moveInTo);

    // Reload with filters
    window.location.href = '?' + params.toString();
}

// Reset filters
function resetFilters() {
    const params = new URLSearchParams(window.location.search);
    const mode = params.get('mode') || 'find_roommate';
    window.location.href = '?mode=' + mode;
}

// Close modal when clicking outside
document.getElementById('filterModal')?.addEventListener('click', (e) => {
    if (e.target.id === 'filterModal') {
        closeFilterModal();
    }
});
</script>
