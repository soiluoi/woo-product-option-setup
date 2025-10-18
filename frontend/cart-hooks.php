<?php
/**
 * Xử lý giỏ hàng và đơn hàng
 */

// Ngăn chặn truy cập trực tiếp
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Lưu dữ liệu options vào cart item data
 */
add_filter('woocommerce_add_cart_item_data', 'woo_product_option_add_cart_item_data', 10, 3);

/**
 * Thêm dữ liệu options vào cart item data
 *
 * @param array $cart_item_data Dữ liệu cart item hiện tại
 * @param int $product_id ID sản phẩm
 * @param int $variation_id ID biến thể sản phẩm
 * @return array Dữ liệu cart item đã được cập nhật
 */
function woo_product_option_add_cart_item_data($cart_item_data, $product_id, $variation_id) {
    // Kiểm tra có options không
    if (!isset($_POST['product_options'])) {
        return $cart_item_data;
    }
    
    // Lấy dữ liệu options từ product với error handling
    $option_groups_data = get_post_meta($product_id, '_product_option_groups_data', true);
    if ($option_groups_data === false) {
        error_log('Woo Product Option Setup: Failed to get option groups data for product ID: ' . $product_id);
        wc_add_notice(__('Lỗi: Không thể tải tùy chọn sản phẩm. Vui lòng thử lại.', 'woo-product-option-setup'), 'error');
        return $cart_item_data;
    }
    
    if (!$option_groups_data) {
        return $cart_item_data;
    }
    
    // Lấy dữ liệu từ settings với error handling
    $option_groups = get_option('woo_product_option_groups', array());
    if ($option_groups === false) {
        error_log('Woo Product Option Setup: Failed to get option groups from settings');
        return $cart_item_data;
    }
    
    $selected_options = array();
    $additional_price = 0;
    
    // Xử lý Product Options
    if (isset($_POST['product_options']) && !empty($_POST['product_options'])) {
        $selected_groups = $option_groups_data['selected_groups'];
        
        foreach ($_POST['product_options'] as $group_id => $option_ids) {
            // Đảm bảo group_id tồn tại trong selected_groups
            if (!isset($selected_groups[$group_id])) {
                continue;
            }
            
            // Tìm group trong settings
            $group = null;
            foreach ($option_groups as $g) {
                if ($g['id'] === $group_id) {
                    $group = $g;
                    break;
                }
            }
            
            if (!$group) {
                continue;
            }
            
            // Xử lý option_ids (có thể là string hoặc array)
            if (!is_array($option_ids)) {
                $option_ids = array($option_ids);
            }
            
            $group_options = array();
            foreach ($option_ids as $option_id) {
                // Tìm option trong group
                foreach ($group['options'] as $option) {
                    if ($option['id'] === $option_id) {
                        // Lấy giá tùy chỉnh nếu có
                        $custom_price = '';
                        if (isset($selected_groups[$group_id . '_prices'][$option['id']]) && 
                            !empty($selected_groups[$group_id . '_prices'][$option['id']])) {
                            $custom_price = intval($selected_groups[$group_id . '_prices'][$option['id']]);
                        }
                        
                        $final_price = $custom_price ?: $option['price'];
                        
                        // Validate price - kiểm tra kỹ hơn
                        if (!is_numeric($final_price) || $final_price < 0 || $final_price > WOO_PRODUCT_OPTION_SETUP_MAX_PRICE) {
                            error_log('Woo Product Option Setup: Invalid price for option: ' . $option['name'] . ', price: ' . $final_price);
                            $final_price = 0;
                        }
                        
                        // Đảm bảo giá là số nguyên
                        $final_price = intval($final_price);
                        
                        $price_in_cents = intval($final_price * WOO_PRODUCT_OPTION_SETUP_PRICE_MULTIPLIER);
                        
                        $group_options[] = array(
                            'id' => $option['id'],
                            'name' => $option['name'],
                            'price' => $price_in_cents
                        );
                        $additional_price += $price_in_cents;
                        break;
                    }
                }
            }
            
            if (!empty($group_options)) {
                $selected_options[] = array(
                    'group_id' => $group_id,
                    'group_name' => $group['name'],
                    'group_type' => $group['type'],
                    'options' => $group_options
                );
            }
        }
    }
    
    // Xử lý Matcha Gram Addition
    if (isset($_POST['matcha_extra_gram']) && !empty($_POST['matcha_extra_gram'])) {
        $extra_gram = intval($_POST['matcha_extra_gram']);
        
        // Validate gram (1-5)
        if ($extra_gram >= 1 && $extra_gram <= 5) {
            $matcha_gram_enabled = get_post_meta($product_id, '_matcha_gram_enabled', true);
            $matcha_price_per_gram = get_post_meta($product_id, '_matcha_price_per_gram', true);
            
            if ($matcha_gram_enabled === 'yes' && $matcha_price_per_gram > 0) {
                // Validate price
                $price_per_gram = floatval($matcha_price_per_gram);
                if ($price_per_gram < 0 || $price_per_gram > WOO_PRODUCT_OPTION_SETUP_MAX_PRICE) {
                    error_log('Woo Product Option Setup: Invalid matcha price per gram: ' . $price_per_gram);
                    $price_per_gram = 0;
                }
                
                if ($price_per_gram > 0) {
                    $gram_price = intval($extra_gram * $price_per_gram * WOO_PRODUCT_OPTION_SETUP_PRICE_MULTIPLIER);
                    
                    $cart_item_data['woo_matcha_extra_gram'] = $extra_gram;
                    $cart_item_data['woo_matcha_gram_price'] = $gram_price;
                    $additional_price += $gram_price;
                }
            }
        }
    }
    
    // Lưu vào cart item data
    if (!empty($selected_options)) {
        $cart_item_data['woo_product_options'] = $selected_options;
        $cart_item_data['woo_additional_price'] = $additional_price;
    } elseif (isset($cart_item_data['woo_matcha_gram_price']) && $cart_item_data['woo_matcha_gram_price'] > 0) {
        // Chỉ có matcha gram, không có options
        $cart_item_data['woo_additional_price'] = $additional_price;
    }
    
    return $cart_item_data;
}

/**
 * Tính giá sản phẩm với options
 */
add_action('woocommerce_before_calculate_totals', 'woo_product_option_calculate_totals', 10, 1);

/**
 * Tính tổng giá sản phẩm bao gồm options
 *
 * @param WC_Cart $cart Đối tượng giỏ hàng WooCommerce
 * @return void
 */
function woo_product_option_calculate_totals($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }
    
    foreach ($cart->get_cart() as $cart_item) {
        if (isset($cart_item['woo_additional_price']) && $cart_item['woo_additional_price'] > 0) {
            $additional_price = $cart_item['woo_additional_price'];
            $cart_item['data']->set_price($cart_item['data']->get_price() + $additional_price);
        }
    }
}

/**
 * Hiển thị thông tin options trong giỏ hàng
 */
add_filter('woocommerce_get_item_data', 'woo_product_option_display_item_data', 10, 2);

function woo_product_option_display_item_data($item_data, $cart_item) {
    // Hiển thị Product Options
    if (isset($cart_item['woo_product_options'])) {
        foreach ($cart_item['woo_product_options'] as $group) {
            $options_text = array();
            foreach ($group['options'] as $option) {
                $price_text = $option['price'] > 0 ? ' (+' . ($option['price'] / 1000) . 'k)' : '';
                $options_text[] = $option['name'] . $price_text;
            }
            
            $item_data[] = array(
                'key' => $group['group_name'],
                'value' => implode(', ', $options_text)
            );
        }
    }
    
    // Hiển thị Matcha Gram
    if (isset($cart_item['woo_matcha_extra_gram']) && $cart_item['woo_matcha_extra_gram'] > 0) {
        $gram = $cart_item['woo_matcha_extra_gram'];
        $price = isset($cart_item['woo_matcha_gram_price']) ? $cart_item['woo_matcha_gram_price'] : 0;
        $price_text = $price > 0 ? ' (+' . ($price / 1000) . 'k)' : '';
        
        $item_data[] = array(
            'key' => __('Thêm gram matcha', 'woo-product-option-setup'),
            'value' => '+' . $gram . 'g' . $price_text
        );
    }
    
    return $item_data;
}

/**
 * Lưu thông tin options vào order item meta
 * Hỗ trợ cả HPOS và legacy order storage
 */
add_action('woocommerce_checkout_create_order_line_item', 'woo_product_option_add_order_item_meta', 10, 4);
// Hook backup cho legacy order storage
add_action('woocommerce_add_order_item_meta', 'woo_product_option_add_order_item_meta_legacy', 10, 3);

function woo_product_option_add_order_item_meta($item, $cart_item_key, $values, $order) {
    // Lưu Product Options
    if (isset($values['woo_product_options'])) {
        foreach ($values['woo_product_options'] as $group) {
            $options_text = array();
            foreach ($group['options'] as $option) {
                $price_text = $option['price'] > 0 ? ' (+' . ($option['price'] / 1000) . 'k)' : '';
                $options_text[] = $option['name'] . $price_text;
            }
            
            $item->add_meta_data(
                $group['group_name'],
                implode(', ', $options_text)
            );
        }
    }
    
    // Lưu Matcha Gram
    if (isset($values['woo_matcha_extra_gram']) && $values['woo_matcha_extra_gram'] > 0) {
        $gram = $values['woo_matcha_extra_gram'];
        $price = isset($values['woo_matcha_gram_price']) ? $values['woo_matcha_gram_price'] : 0;
        $price_text = $price > 0 ? ' (+' . ($price / 1000) . 'k)' : '';
        
        $item->add_meta_data(
            __('Thêm gram matcha', 'woo-product-option-setup'),
            '+' . $gram . 'g' . $price_text
        );
    }
    
    // Lưu additional price nếu có
    if (isset($values['woo_additional_price']) && $values['woo_additional_price'] > 0) {
        $item->add_meta_data(
            __('Additional Price', 'woo-product-option-setup'),
            ($values['woo_additional_price'] / 1000) . 'k'
        );
    }
}

/**
 * Function backup cho legacy order storage (không HPOS)
 */
function woo_product_option_add_order_item_meta_legacy($item_id, $values, $cart_item_key) {
    // Chỉ chạy nếu HPOS không được sử dụng
    if (class_exists('\Automattic\WooCommerce\Utilities\OrderUtil') && 
        \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()) {
        return;
    }
    
    // Lưu Product Options
    if (isset($values['woo_product_options'])) {
        foreach ($values['woo_product_options'] as $group) {
            $options_text = array();
            foreach ($group['options'] as $option) {
                $price_text = $option['price'] > 0 ? ' (+' . ($option['price'] / 1000) . 'k)' : '';
                $options_text[] = $option['name'] . $price_text;
            }
            
            wc_add_order_item_meta($item_id, $group['group_name'], implode(', ', $options_text));
        }
    }
    
    // Lưu Matcha Gram
    if (isset($values['woo_matcha_extra_gram']) && $values['woo_matcha_extra_gram'] > 0) {
        $gram = $values['woo_matcha_extra_gram'];
        $price = isset($values['woo_matcha_gram_price']) ? $values['woo_matcha_gram_price'] : 0;
        $price_text = $price > 0 ? ' (+' . ($price / 1000) . 'k)' : '';
        
        wc_add_order_item_meta($item_id, __('Thêm gram matcha', 'woo-product-option-setup'), '+' . $gram . 'g' . $price_text);
    }
    
    // Lưu additional price nếu có
    if (isset($values['woo_additional_price']) && $values['woo_additional_price'] > 0) {
        wc_add_order_item_meta($item_id, __('Additional Price', 'woo-product-option-setup'), ($values['woo_additional_price'] / 1000) . 'k');
    }
}

/**
 * Hiển thị thông tin options trong email order
 */
add_filter('woocommerce_order_item_display_meta_key', 'woo_product_option_display_meta_key', 10, 3);

function woo_product_option_display_meta_key($display_key, $meta, $item) {
    // Không hiển thị "Additional Price" trong email
    if ($meta->key === __('Additional Price', 'woo-product-option-setup')) {
        return '';
    }
    
    return $display_key;
}

/**
 * Ẩn "Additional Price" khỏi email nhưng vẫn hiển thị trong admin
 */
add_filter('woocommerce_order_item_display_meta_value', 'woo_product_option_display_meta_value', 10, 3);

function woo_product_option_display_meta_value($display_value, $meta, $item) {
    // Ẩn "Additional Price" khỏi email
    if ($meta->key === __('Additional Price', 'woo-product-option-setup')) {
        return '';
    }
    
    return $display_value;
}

/**
 * Thêm CSS cho cart/checkout
 */
add_action('wp_head', 'woo_product_option_cart_styles');

function woo_product_option_cart_styles() {
    if (!is_cart() && !is_checkout()) {
        return;
    }
    ?>
    <style>
    .woocommerce-cart .cart_item .product-options,
    .woocommerce-checkout .cart_item .product-options {
        font-size: 0.9em;
        color: #666;
        margin-top: 5px;
    }
    
    .woocommerce-cart .cart_item .product-options .option-group,
    .woocommerce-checkout .cart_item .product-options .option-group {
        margin-bottom: 3px;
    }
    
    .woocommerce-cart .cart_item .product-options .option-group strong,
    .woocommerce-checkout .cart_item .product-options .option-group strong {
        color: #333;
    }
    </style>
    <?php
}

/**
 * Debug function - hiển thị thông tin cart item
 * Tương thích với HPOS
 */
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('woocommerce_cart_loaded_from_session', 'woo_product_option_debug_cart');
    
    function woo_product_option_debug_cart() {
        if (isset($_GET['debug_cart']) && current_user_can('manage_options')) {
            echo '<pre>';
            echo "=== DEBUG CART ITEMS ===\n";
            echo "HPOS Enabled: " . (class_exists('\Automattic\WooCommerce\Utilities\OrderUtil') && 
                \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ? 'Yes' : 'No') . "\n\n";
            
            foreach (WC()->cart->get_cart() as $cart_item) {
                if (isset($cart_item['woo_product_options']) || isset($cart_item['woo_extra_info'])) {
                    print_r($cart_item);
                }
            }
            echo '</pre>';
        }
    }
}
