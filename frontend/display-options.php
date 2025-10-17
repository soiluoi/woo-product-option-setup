<?php
/**
 * Hiển thị options và extra info trên trang sản phẩm
 */

// Ngăn chặn truy cập trực tiếp
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Hook vào trước nút Add to Cart
 */
add_action('woocommerce_before_add_to_cart_button', 'woo_product_option_display_options', 10);

function woo_product_option_display_options() {
    global $product;
    
    if (!$product) {
        return;
    }
    
    // Lấy dữ liệu options với error handling
    $product_id = $product->get_id();
    if (!$product_id || $product_id <= 0) {
        error_log('Woo Product Option Setup: Invalid product ID');
        return;
    }
    
    $option_groups_data = get_post_meta($product_id, '_product_option_groups_data', true);
    if ($option_groups_data === false) {
        error_log('Woo Product Option Setup: Failed to get option groups data for product ID: ' . $product_id);
        return;
    }
    
    // Kiểm tra có options không
    $has_options = isset($option_groups_data['enabled']) && $option_groups_data['enabled'] && 
                   !empty($option_groups_data['selected_groups']);
    
    if (!$has_options) {
        return;
    }
    
    // Lấy dữ liệu từ settings với error handling
    $option_groups = get_option('woo_product_option_groups', array());
    if ($option_groups === false) {
        error_log('Woo Product Option Setup: Failed to get option groups from settings');
        return;
    }
    
    ?>
    
    <div class="woo-product-options-container">
        
        <div class="product-options-section">                
                <?php
                $selected_groups = $option_groups_data['selected_groups'];
                $selected_group_ids = isset($option_groups_data['selected_group_ids']) ? $option_groups_data['selected_group_ids'] : array();
                $total_additional_price = 0;
                
                foreach ($option_groups as $group):
                    // Chỉ hiển thị group nếu được tick (ưu tiên group trước)
                    if (!in_array($group['id'], $selected_group_ids)) {
                        continue;
                    }
                    
                    // Chỉ hiển thị options được tick trong group
                    if (!isset($selected_groups[$group['id']])) {
                        continue;
                    }
                    
                    $available_options = $selected_groups[$group['id']];
                    $group_options = array_filter($group['options'], function($option) use ($available_options) {
                        return in_array($option['id'], $available_options);
                    });
                    
                    if (empty($group_options)) {
                        continue;
                    }
                    ?>
                    
                    <div class="option-group" data-group-id="<?php echo esc_attr($group['id']); ?>" data-group-type="<?php echo esc_attr($group['type']); ?>">
                        <h4 class="group-title"><?php echo esc_html($group['name']); ?></h4>
                        
                        <div class="group-options">
                            <?php foreach ($group_options as $index => $option): ?>
                                <?php
                                $option_id = 'option_' . $group['id'] . '_' . $option['id'];
                                
                                // Lấy giá tùy chỉnh nếu có, nếu không thì dùng giá gốc
                                $custom_price = '';
                                if (isset($selected_groups[$group['id'] . '_prices'][$option['id']]) && 
                                    !empty($selected_groups[$group['id'] . '_prices'][$option['id']])) {
                                    $custom_price = intval($selected_groups[$group['id'] . '_prices'][$option['id']]);
                                }
                                
                                $final_price = $custom_price ?: $option['price'];
                                $price_display = $final_price > 0 ? ' (+' . $final_price . 'k)' : '';
                                $is_first = $index === 0;
                                ?>
                                
                                <div class="option-item">
                                    <label for="<?php echo esc_attr($option_id); ?>">
                                        <input type="<?php echo $group['type']; ?>" 
                                               id="<?php echo esc_attr($option_id); ?>" 
                                               name="product_options[<?php echo esc_attr($group['id']); ?>]<?php echo $group['type'] === 'checkbox' ? '[]' : ''; ?>" 
                                               value="<?php echo esc_attr($option['id']); ?>" 
                                               data-price="<?php echo esc_attr($final_price); ?>"
                                               <?php echo $is_first ? 'checked' : ''; ?>>
                                        
                                        <span class="option-name"><?php echo esc_html($option['name']); ?></span>
                                        <span class="option-price"><?php echo $price_display; ?></span>
                                    </label>
                                </div>
                                
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                <?php endforeach; ?>
                
                <div class="price-summary">
                    <div class="original-price">
                        <span class="label"><?php _e('Giá gốc:', 'woo-product-option-setup'); ?></span>
                        <span class="price" id="original-price" data-price="<?php echo esc_attr($product->get_price()); ?>"><?php echo number_format($product->get_price() / 1000, 0) . 'k'; ?></span>
                    </div>
                    <div class="additional-price" id="additional-price" style="display:none;">
                        <span class="label"><?php _e('Phụ phí:', 'woo-product-option-setup'); ?></span>
                        <span class="price" id="additional-price-amount">+0k</span>
                    </div>
                    <div class="total-price">
                        <span class="label"><?php _e('Tổng cộng:', 'woo-product-option-setup'); ?></span>
                        <span class="price" id="total-price" data-price="<?php echo esc_attr($product->get_price()); ?>"><?php echo number_format($product->get_price() / 1000, 0) . 'k'; ?></span>
                    </div>
                </div>
            </div>
        
        
    </div>
    
    <?php
}

/**
 * Thêm CSS cho frontend
 */
add_action('wp_head', 'woo_product_option_frontend_styles');

function woo_product_option_frontend_styles() {
    if (!is_product()) {
        return;
    }
    ?>
    <style>
    .woo-product-options-container {
        margin: 20px 0;
        padding: 20px;
        border: 1px solid #ddd;
        border-radius: 5px;
        background: #f9f9f9;
    }
    
    .product-options-section,
    .extra-info-section {
        margin-bottom: 20px;
    }
    
    .product-options-section h3,
    .extra-info-section h3 {
        margin-top: 0;
        color: #333;
        border-bottom: 2px solid #0073aa;
        padding-bottom: 5px;
    }
    
    .option-group {
        margin-bottom: 20px;
        padding: 15px;
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 3px;
    }
    
    .group-title {
        margin: 0 0 10px 0;
        font-weight: bold;
        color: #555;
    }
    
    .group-options {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .option-item label {
        display: flex;
        align-items: center;
        cursor: pointer;
        padding: 5px;
        border-radius: 3px;
        transition: background-color 0.2s;
    }
    
    .option-item label:hover {
        background-color: #f0f0f0;
    }
    
    .option-item input[type="radio"],
    .option-item input[type="checkbox"] {
        margin-right: 8px;
    }
    
    .option-name {
        font-weight: 500;
    }
    
    .option-price {
        color: #0073aa;
        font-weight: bold;
        margin-left: auto;
    }
    
    .price-summary {
        margin-top: 20px;
        padding: 15px;
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 3px;
    }
    
    .price-summary > div {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
    }
    
    .price-summary .total-price {
        font-weight: bold;
        font-size: 1.1em;
        color: #0073aa;
        border-top: 1px solid #e0e0e0;
        padding-top: 10px;
        margin-top: 10px;
    }
    
    .extra-info-item {
        margin-bottom: 15px;
    }
    
    .extra-info-item label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        color: #555;
    }
    
    .extra-info-input {
        width: 100%;
        max-width: 300px;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 3px;
    }
    
    .extra-info-input:focus {
        border-color: #0073aa;
        outline: none;
        box-shadow: 0 0 0 1px #0073aa;
    }
    
    /* CSS cho shortcode extra info */
    .woo-extra-info-display {
        margin: 15px 0;
    }
    
    .woo-extra-info-display .extra-info-item {
        display: flex;
        align-items: center;
        margin-bottom: 8px;
        padding: 5px 0;
    }
    
    .woo-extra-info-display .info-label {
        font-weight: 500;
        color: #555;
        margin-right: 10px;
        min-width: 120px;
    }
    
    .woo-extra-info-display .info-value {
        display: flex;
        align-items: center;
        gap: 2px;
    }
    
    .woo-extra-info-display .full {
        display: inline-block;
        width: 20px;
        height: 20px;
        background-color: #0073aa;
        border-radius: 2px;
        margin-right: 2px;
    }
    
    .woo-extra-info-display .half {
        display: inline-block;
        width: 10px;
        height: 20px;
        background-color: #0073aa;
        border-radius: 2px;
        margin-right: 2px;
    }
    
    @media (max-width: 768px) {
        .woo-product-options-container {
            margin: 15px 0;
            padding: 15px;
        }
        
        .group-options {
            gap: 5px;
        }
        
        .option-item label {
            padding: 3px;
        }
        
        .woo-extra-info-display .extra-info-item {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .woo-extra-info-display .info-label {
            min-width: auto;
            margin-bottom: 5px;
        }
    }
    </style>
    <?php
}

/**
 * Shortcode hiển thị extra info
 */
add_shortcode('woo_extra_info', 'woo_product_extra_info_shortcode');

function woo_product_extra_info_shortcode($atts) {
    global $product;
    
    // Lấy product ID với error handling
    $product_id = null;
    if (is_product() && $product) {
        $product_id = $product->get_id();
    } elseif (isset($atts['product_id'])) {
        $product_id = intval($atts['product_id']);
    } else {
        return '';
    }
    
    if (!$product_id || $product_id <= 0) {
        error_log('Woo Product Option Setup: Invalid product ID in shortcode');
        return '';
    }
    
    // Kiểm tra extra info có enabled không
    $extra_info_enabled = get_post_meta($product_id, '_extra_info_enabled', true);
    if ($extra_info_enabled === false) {
        error_log('Woo Product Option Setup: Failed to get extra info enabled status for product ID: ' . $product_id);
        return '';
    }
    
    if ($extra_info_enabled !== 'yes') {
        return '';
    }
    
    // Lấy danh sách extra info groups từ settings với error handling
    $extra_info_groups = get_option('woo_extra_info_groups', array());
    if ($extra_info_groups === false) {
        error_log('Woo Product Option Setup: Failed to get extra info groups from settings');
        return '';
    }
    
    if (empty($extra_info_groups)) {
        return '';
    }
    
    $output = '<div class="woo-extra-info-display">';
    
    foreach ($extra_info_groups as $info) {
        $slug = $info['slug'];
        $meta_key = '_extra_info_' . $slug;
        
        // Chỉ hiển thị nếu có meta key tồn tại (tức là đã tick)
        $current_value = get_post_meta($product_id, $meta_key, true);
        if ($current_value === false) {
            error_log('Woo Product Option Setup: Failed to get meta value for key: ' . $meta_key);
            continue;
        }
        
        if (empty($current_value)) {
            continue;
        }
        
        // Validate và sanitize value
        $value = floatval($current_value);
        if ($value < 0) {
            error_log('Woo Product Option Setup: Invalid negative value for ' . $meta_key . ': ' . $current_value);
            $value = 0;
        }
        
        $spans = generate_value_spans($value);
        
        $output .= '<div class="extra-info-item">';
        $output .= '<span class="info-label">' . esc_html($info['name']) . ':</span>';
        $output .= '<span class="info-value">' . $spans . '</span>';
        $output .= '</div>';
    }
    
    $output .= '</div>';
    
    return $output;
}

/**
 * Tạo các span dựa trên value
 */
function generate_value_spans($value) {
    // Validate input
    if (!is_numeric($value) || $value < 0) {
        error_log('Woo Product Option Setup: Invalid value in generate_value_spans: ' . $value);
        return '';
    }
    
    $spans = '';
    $full_count = intval($value);
    $has_half = ($value - $full_count) >= 0.5;
    
    // Giới hạn số lượng span để tránh performance issues
    if ($full_count > 100) {
        error_log('Woo Product Option Setup: Value too large in generate_value_spans: ' . $value);
        $full_count = 100;
    }
    
    // Thêm các span full
    for ($i = 0; $i < $full_count; $i++) {
        $spans .= '<span class="full"></span>';
    }
    
    // Thêm span half nếu có
    if ($has_half) {
        $spans .= '<span class="half"></span>';
    }
    
    return $spans;
}