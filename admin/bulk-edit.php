<?php
/**
 * Bulk Edit Options cho Products
 */

// Ngăn chặn truy cập trực tiếp
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Đăng ký bulk actions cho Products list
 */
add_filter('bulk_actions-edit-product', 'woo_product_option_add_bulk_actions');

function woo_product_option_add_bulk_actions($bulk_actions) {
    $bulk_actions['bulk_edit_options'] = __('Bulk Edit Product Options', 'woo-product-option-setup');
    $bulk_actions['bulk_edit_matcha'] = __('Bulk Edit Matcha Gram', 'woo-product-option-setup');
    $bulk_actions['bulk_edit_extra_info'] = __('Bulk Edit Extra Info', 'woo-product-option-setup');
    return $bulk_actions;
}

/**
 * Xử lý bulk actions
 */
add_filter('handle_bulk_actions-edit-product', 'woo_product_option_handle_bulk_actions', 10, 3);

function woo_product_option_handle_bulk_actions($redirect_url, $action, $post_ids) {
    if (!in_array($action, ['bulk_edit_options', 'bulk_edit_matcha', 'bulk_edit_extra_info'])) {
        return $redirect_url;
    }
    
    // Kiểm tra quyền
    if (!current_user_can('edit_products')) {
        wp_die(__('Bạn không có quyền thực hiện hành động này.', 'woo-product-option-setup'));
    }
    
    // Lưu product IDs vào transient
    $transient_key = 'woo_bulk_edit_products_' . wp_generate_password(12, false);
    set_transient($transient_key, $post_ids, 300); // 5 phút
    
    // Redirect đến trang bulk edit
    $edit_type = str_replace('bulk_edit_', '', $action);
    $redirect_url = admin_url('admin.php?page=woo-bulk-edit-options&edit_type=' . $edit_type . '&products=' . $transient_key);
    
    return $redirect_url;
}

/**
 * Thêm menu admin cho bulk edit
 */
add_action('admin_menu', 'woo_product_option_add_bulk_edit_menu');

function woo_product_option_add_bulk_edit_menu() {
    add_submenu_page(
        null, // Không hiển thị trong menu
        __('Bulk Edit Options', 'woo-product-option-setup'),
        __('Bulk Edit Options', 'woo-product-option-setup'),
        'edit_products',
        'woo-bulk-edit-options',
        'woo_product_option_bulk_edit_page'
    );
}

/**
 * Hiển thị trang bulk edit
 */
function woo_product_option_bulk_edit_page() {
    // Kiểm tra quyền
    if (!current_user_can('edit_products')) {
        wp_die(__('Bạn không có quyền truy cập trang này.', 'woo-product-option-setup'));
    }
    
    // Lấy tham số
    $edit_type = isset($_GET['edit_type']) ? sanitize_text_field($_GET['edit_type']) : '';
    $products_key = isset($_GET['products']) ? sanitize_text_field($_GET['products']) : '';
    
    // Validate edit_type
    if (!in_array($edit_type, ['options', 'matcha', 'extra'])) {
        wp_die(__('Loại chỉnh sửa không hợp lệ.', 'woo-product-option-setup'));
    }
    
    // Lấy product IDs
    $product_ids = get_transient($products_key);
    if (!$product_ids || empty($product_ids)) {
        wp_die(__('Không tìm thấy sản phẩm nào để chỉnh sửa.', 'woo-product-option-setup'));
    }
    
    // Xử lý form submit
    if (isset($_POST['submit']) && wp_verify_nonce($_POST['woo_bulk_edit_nonce'], 'woo_bulk_edit_action')) {
        $result = woo_product_option_process_bulk_edit($edit_type, $product_ids);
        
        if ($result['success']) {
            add_action('admin_notices', function() use ($result) {
                echo '<div class="notice notice-success"><p>' . 
                     sprintf(__('Đã cập nhật thành công %d sản phẩm!', 'woo-product-option-setup'), $result['updated_count']) . 
                     '</p></div>';
            });
        } else {
            add_action('admin_notices', function() use ($result) {
                echo '<div class="notice notice-error"><p>' . 
                     sprintf(__('Có lỗi xảy ra: %s', 'woo-product-option-setup'), $result['message']) . 
                     '</p></div>';
            });
        }
    }
    
    // Lấy thông tin sản phẩm
    $products = array();
    foreach ($product_ids as $product_id) {
        $product = wc_get_product($product_id);
        if ($product) {
            $products[] = array(
                'id' => $product_id,
                'name' => $product->get_name(),
                'sku' => $product->get_sku(),
                'image' => wp_get_attachment_image_url($product->get_image_id(), 'thumbnail'),
                'type' => $product->get_type()
            );
        }
    }
    
    // Xóa transient sau khi sử dụng
    delete_transient($products_key);
    
    // Hiển thị trang
    ?>
    <div class="wrap">
        <h1>
            <?php
            switch ($edit_type) {
                case 'options':
                    _e('Bulk Edit Product Options', 'woo-product-option-setup');
                    break;
                case 'matcha':
                    _e('Bulk Edit Matcha Gram', 'woo-product-option-setup');
                    break;
                case 'extra':
                    _e('Bulk Edit Extra Info', 'woo-product-option-setup');
                    break;
            }
            ?>
        </h1>
        
        <div class="woo-bulk-edit-container">
            <!-- Danh sách sản phẩm -->
            <div class="woo-bulk-edit-products">
                <h2><?php printf(__('Chỉnh sửa cho %d sản phẩm:', 'woo-product-option-setup'), count($products)); ?></h2>
                <div class="products-list">
                    <?php foreach ($products as $product): ?>
                        <div class="product-item">
                            <?php if ($product['image']): ?>
                                <img src="<?php echo esc_url($product['image']); ?>" alt="<?php echo esc_attr($product['name']); ?>" class="product-thumbnail">
                            <?php endif; ?>
                            <div class="product-info">
                                <strong><?php echo esc_html($product['name']); ?></strong>
                                <?php if ($product['sku']): ?>
                                    <br><small><?php printf(__('SKU: %s', 'woo-product-option-setup'), esc_html($product['sku'])); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Form chỉnh sửa -->
            <form method="post" action="">
                <?php wp_nonce_field('woo_bulk_edit_action', 'woo_bulk_edit_nonce'); ?>
                <input type="hidden" name="edit_type" value="<?php echo esc_attr($edit_type); ?>">
                
                <div class="woo-bulk-edit-form">
                    <?php
                    switch ($edit_type) {
                        case 'options':
                            woo_product_option_render_bulk_options_form();
                            break;
                        case 'matcha':
                            woo_product_option_render_bulk_matcha_form();
                            break;
                        case 'extra':
                            woo_product_option_render_bulk_extra_info_form();
                            break;
                    }
                    ?>
                </div>
                
                <p class="submit">
                    <input type="submit" name="submit" class="button button-primary" value="<?php printf(__('Áp dụng cho %d sản phẩm', 'woo-product-option-setup'), count($products)); ?>">
                    <a href="<?php echo admin_url('edit.php?post_type=product'); ?>" class="button"><?php _e('Hủy', 'woo-product-option-setup'); ?></a>
                </p>
            </form>
        </div>
    </div>
    <?php
}

/**
 * Render form cho Product Options
 */
function woo_product_option_render_bulk_options_form() {
    $option_groups = get_option('woo_product_option_groups', array());
    ?>
    <div class="woo-product-option-section">
        <h2><?php _e('Product Options', 'woo-product-option-setup'); ?></h2>
        
        <p>
            <label>
                <input type="checkbox" name="product_options_enabled" value="yes" class="options-enabled-toggle">
                <?php _e('Enable Product Options', 'woo-product-option-setup'); ?>
            </label>
        </p>
        
        <div class="options-content" style="display:none;">
            <?php if (empty($option_groups)): ?>
                <p class="no-groups">
                    <?php _e('Chưa có nhóm option nào. Vui lòng tạo nhóm option trong', 'woo-product-option-setup'); ?>
                    <a href="<?php echo admin_url('options-general.php?page=woo-product-options'); ?>" target="_blank">
                        <?php _e('Settings > Product Options', 'woo-product-option-setup'); ?>
                    </a>
                </p>
            <?php else: ?>
                <p class="description"><?php _e('Chọn các nhóm option và options cụ thể muốn áp dụng cho tất cả sản phẩm:', 'woo-product-option-setup'); ?></p>
                
                <?php foreach ($option_groups as $group): ?>
                    <div class="option-group-item">
                        <div class="group-header">
                            <h3>
                                <label>
                                    <input type="checkbox" 
                                           name="selected_option_groups[]" 
                                           value="<?php echo esc_attr($group['id']); ?>"
                                           class="group-checkbox">
                                    <span class="group-title"><?php echo esc_html($group['name']); ?></span>
                                    <span class="group-type">(<?php echo $group['type'] === 'radio' ? __('Radio', 'woo-product-option-setup') : __('Checkbox', 'woo-product-option-setup'); ?>)</span>
                                </label>
                            </h3>
                        </div>
                        
                        <div class="group-content">
                            <?php if (!empty($group['options'])): ?>
                                <div class="group-options-header">
                                    <label class="select-all-options">
                                        <input type="checkbox" class="select-all-checkbox" data-group="<?php echo esc_attr($group['id']); ?>">
                                        <strong><?php _e('Chọn tất cả options trong nhóm này', 'woo-product-option-setup'); ?></strong>
                                    </label>
                                </div>
                                
                                <div class="group-options-list">
                                    <?php foreach ($group['options'] as $option): ?>
                                        <div class="option-item">
                                            <label class="option-checkbox">
                                                <input type="checkbox" 
                                                       name="available_options[<?php echo esc_attr($group['id']); ?>][]" 
                                                       value="<?php echo esc_attr($option['id']); ?>"
                                                       class="option-availability-checkbox"
                                                       data-group="<?php echo esc_attr($group['id']); ?>">
                                                <span class="option-name"><?php echo esc_html($option['name']); ?></span>
                                            </label>
                                            
                                            <input type="number" 
                                                   name="option_prices[<?php echo esc_attr($group['id']); ?>][<?php echo esc_attr($option['id']); ?>]" 
                                                   value="<?php echo esc_attr($option['price']); ?>" 
                                                   placeholder="<?php echo esc_attr($option['price']); ?>"
                                                   min="0" 
                                                   step="1" 
                                                   class="option-price-input"
                                                   disabled>
                                            <span class="price-unit">k</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="no-options"><?php _e('Nhóm này chưa có tùy chọn nào.', 'woo-product-option-setup'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Render form cho Matcha Gram
 */
function woo_product_option_render_bulk_matcha_form() {
    ?>
    <div class="woo-product-option-section">
        <h2><?php _e('Matcha Gram Options', 'woo-product-option-setup'); ?></h2>
        
        <p>
            <label>
                <input type="checkbox" name="matcha_gram_enabled" value="yes" class="matcha-enabled-toggle">
                <?php _e('Enable Matcha Gram Addition', 'woo-product-option-setup'); ?>
            </label>
        </p>
        
        <div class="matcha-gram-content" style="display:none;">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="matcha_price_per_gram"><?php _e('Giá mỗi gram thêm (k)', 'woo-product-option-setup'); ?></label>
                    </th>
                    <td>
                        <input type="number" 
                               id="matcha_price_per_gram" 
                               name="matcha_price_per_gram" 
                               value="0" 
                               placeholder="0" 
                               min="0" 
                               step="0.5" 
                               class="regular-text">
                        <p class="description"><?php _e('Giá cộng thêm cho mỗi gram matcha (đơn vị: k = 1000đ)', 'woo-product-option-setup'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <?php
}

/**
 * Render form cho Extra Info
 */
function woo_product_option_render_bulk_extra_info_form() {
    $extra_info_groups = get_option('woo_extra_info_groups', array());
    ?>
    <div class="woo-product-option-section">
        <h2><?php _e('Extra Info', 'woo-product-option-setup'); ?></h2>
        
        <p>
            <label>
                <input type="checkbox" name="extra_info_enabled" value="yes" class="extra-info-enabled-toggle">
                <?php _e('Enable Extra Info', 'woo-product-option-setup'); ?>
            </label>
        </p>
        
        <div class="extra-info-content" style="display:none;">
            <?php if (empty($extra_info_groups)): ?>
                <p class="no-groups">
                    <?php _e('Chưa có Extra Info nào. Vui lòng tạo Extra Info trong', 'woo-product-option-setup'); ?>
                    <a href="<?php echo admin_url('options-general.php?page=woo-product-options'); ?>" target="_blank">
                        <?php _e('Settings > Product Options', 'woo-product-option-setup'); ?>
                    </a>
                </p>
            <?php else: ?>
                <div class="extra-info-list">
                    <?php foreach ($extra_info_groups as $info): ?>
                        <div class="option-item">
                            <label class="option-checkbox">
                                <input type="checkbox" 
                                       name="extra_info_display[<?php echo esc_attr($info['slug']); ?>]" 
                                       value="yes">
                                <span class="option-name"><?php echo esc_html($info['name']); ?></span>
                            </label>
                            
                            <input type="number" 
                                   name="extra_info_values[<?php echo esc_attr($info['slug']); ?>]" 
                                   value="<?php echo esc_attr($info['value']); ?>" 
                                   step="0.5" 
                                   min="0"
                                   class="option-price-input">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Xử lý bulk edit
 */
function woo_product_option_process_bulk_edit($edit_type, $product_ids) {
    $updated_count = 0;
    
    try {
        switch ($edit_type) {
            case 'options':
                $updated_count = woo_bulk_save_options($product_ids);
                break;
            case 'matcha':
                $updated_count = woo_bulk_save_matcha($product_ids);
                break;
            case 'extra':
                $updated_count = woo_bulk_save_extra_info($product_ids);
                break;
            default:
                return array('success' => false, 'message' => __('Loại chỉnh sửa không hợp lệ.', 'woo-product-option-setup'));
        }
        
        return array('success' => true, 'updated_count' => $updated_count);
        
    } catch (Exception $e) {
        return array('success' => false, 'message' => $e->getMessage());
    }
}

/**
 * Lưu Product Options hàng loạt
 */
function woo_bulk_save_options($product_ids) {
    $options_enabled = isset($_POST['product_options_enabled']) && $_POST['product_options_enabled'] === 'yes';
    $selected_groups = isset($_POST['selected_option_groups']) ? $_POST['selected_option_groups'] : array();
    $available_options = isset($_POST['available_options']) ? $_POST['available_options'] : array();
    $option_prices = isset($_POST['option_prices']) ? $_POST['option_prices'] : array();
    
    $option_groups_data = array(
        'enabled' => $options_enabled,
        'selected_groups' => array(),
        'selected_group_ids' => $selected_groups
    );
    
    if ($options_enabled) {
        foreach ($available_options as $group_id => $option_ids) {
            if (!empty($option_ids)) {
                $option_groups_data['selected_groups'][$group_id] = $option_ids;
                
                if (isset($option_prices[$group_id])) {
                    $custom_prices = array();
                    foreach ($option_ids as $option_id) {
                        if (isset($option_prices[$group_id][$option_id]) && !empty($option_prices[$group_id][$option_id])) {
                            $price_value = floatval($option_prices[$group_id][$option_id]);
                            if ($price_value >= 0 && $price_value <= WOO_PRODUCT_OPTION_SETUP_MAX_PRICE) {
                                $custom_prices[$option_id] = $price_value;
                            }
                        }
                    }
                    if (!empty($custom_prices)) {
                        $option_groups_data['selected_groups'][$group_id . '_prices'] = $custom_prices;
                    }
                }
            }
        }
    }
    
    $updated_count = 0;
    foreach ($product_ids as $product_id) {
        if (update_post_meta($product_id, '_product_option_groups_data', $option_groups_data)) {
            $updated_count++;
        }
    }
    
    return $updated_count;
}

/**
 * Lưu Matcha Gram hàng loạt
 */
function woo_bulk_save_matcha($product_ids) {
    $matcha_gram_enabled = isset($_POST['matcha_gram_enabled']) && $_POST['matcha_gram_enabled'] === 'yes';
    $matcha_price_per_gram = isset($_POST['matcha_price_per_gram']) ? floatval($_POST['matcha_price_per_gram']) : 0;
    
    if ($matcha_price_per_gram < 0 || $matcha_price_per_gram > WOO_PRODUCT_OPTION_SETUP_MAX_PRICE) {
        $matcha_price_per_gram = 0;
    }
    
    $updated_count = 0;
    foreach ($product_ids as $product_id) {
        if (update_post_meta($product_id, '_matcha_gram_enabled', $matcha_gram_enabled ? 'yes' : 'no') &&
            update_post_meta($product_id, '_matcha_price_per_gram', $matcha_price_per_gram)) {
            $updated_count++;
        }
    }
    
    return $updated_count;
}

/**
 * Lưu Extra Info hàng loạt
 */
function woo_bulk_save_extra_info($product_ids) {
    $extra_info_enabled = isset($_POST['extra_info_enabled']) && $_POST['extra_info_enabled'] === 'yes';
    $extra_info_display = isset($_POST['extra_info_display']) ? $_POST['extra_info_display'] : array();
    $extra_info_values = isset($_POST['extra_info_values']) ? $_POST['extra_info_values'] : array();
    
    $extra_info_groups = get_option('woo_extra_info_groups', array());
    
    $updated_count = 0;
    foreach ($product_ids as $product_id) {
        // Cập nhật trạng thái enabled
        update_post_meta($product_id, '_extra_info_enabled', $extra_info_enabled ? 'yes' : 'no');
        
        // Xử lý metadata dựa trên checkbox tick
        foreach ($extra_info_groups as $info) {
            $slug = $info['slug'];
            
            if (isset($extra_info_display[$slug]) && $extra_info_display[$slug] === 'yes') {
                $value_to_save = '';
                if (isset($extra_info_values[$slug]) && !empty($extra_info_values[$slug])) {
                    $value_to_save = sanitize_text_field($extra_info_values[$slug]);
                } else {
                    $value_to_save = $info['value'];
                }
                update_post_meta($product_id, '_extra_info_' . $slug, $value_to_save);
            } else {
                delete_post_meta($product_id, '_extra_info_' . $slug);
            }
        }
        
        $updated_count++;
    }
    
    return $updated_count;
}
