<?php
/**
 * Helper to render dynamic lifestyle filter options
 */

// Load dynamic lifestyle preferences from database
$db = getDB();
$db->exec("SET NAMES utf8mb4");

$lifestylePrefsStmt = $db->query("
    SELECT code, name_vi, icon, field_type, options_config
    FROM preferences_list
    WHERE category = 'lifestyle' AND is_active = 1
    ORDER BY weight DESC
");

$dynamicLifestylePrefs = [];
while ($pref = $lifestylePrefsStmt->fetch(PDO::FETCH_ASSOC)) {
    $pref['parsed_options'] = !empty($pref['options_config']) ? json_decode($pref['options_config'], true) : null;
    $dynamicLifestylePrefs[] = $pref;
}

/**
 * Render a single preference filter field
 */
function renderPreferenceFilter($pref) {
    $code = $pref['code'];
    $name = $pref['name_vi'];
    $icon = $pref['icon'];
    $fieldType = $pref['field_type'];
    $options = $pref['parsed_options'];

    echo '<div class="filter-group">';
    echo '<label class="filter-label">' . htmlspecialchars($icon . ' ' . $name) . '</label>';

    if ($fieldType === 'enum' && !empty($options['options'])) {
        // Enum: Button group with options
        echo '<div class="filter-button-group">';
        echo '<button class="filter-option-btn" data-value="" data-filter="' . htmlspecialchars($code) . '">Bất kỳ</button>';

        foreach ($options['options'] as $option) {
            $optionCode = $option['code'];
            $optionName = $option['name_vi'];
            $optionIcon = $option['icon'] ?? '';

            echo '<button class="filter-option-btn" data-value="' . htmlspecialchars($optionCode) . '" data-filter="' . htmlspecialchars($code) . '">';
            echo htmlspecialchars($optionIcon . ' ' . $optionName);
            echo '</button>';
        }

        echo '</div>';
    } elseif ($fieldType === 'scale') {
        // Scale: 1-5 buttons
        echo '<div class="filter-scale-group">';
        for ($i = 1; $i <= 5; $i++) {
            echo '<button class="filter-scale-btn" data-value="' . $i . '" data-filter="' . htmlspecialchars($code) . '">';
            echo $i;
            echo '</button>';
        }
        echo '</div>';
        echo '<div class="filter-scale-labels">';
        echo '<span>Thoải mái</span>';
        echo '<span>Rất cao</span>';
        echo '</div>';
    } elseif ($fieldType === 'boolean') {
        // Boolean: Yes/No/Any buttons
        echo '<div class="filter-button-group">';
        echo '<button class="filter-option-btn" data-value="" data-filter="' . htmlspecialchars($code) . '">Bất kỳ</button>';
        echo '<button class="filter-option-btn" data-value="0" data-filter="' . htmlspecialchars($code) . '">Không</button>';
        echo '<button class="filter-option-btn" data-value="1" data-filter="' . htmlspecialchars($code) . '">Có</button>';
        echo '</div>';
    }

    echo '</div>';
}
