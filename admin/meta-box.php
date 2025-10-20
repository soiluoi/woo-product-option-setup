<?php
/**
 * Meta box cho Product Edit Page
 */

// Ngăn chặn truy cập trực tiếp
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Thêm meta box vào trang edit product
 */
add_action('add_meta_boxes', 'woo_product_option_add_meta_boxes');

function woo_product_option_add_meta_boxes() {
    add_meta_box(
        'woo_product_option_meta_box',
        __('Custom Options & Extra Info', 'woo-product-option-setup'),
        'woo_product_option_meta_box_callback',
        'product',
        'normal',
        'high'
    );
}

/**
 * Helper function: Kiểm tra sản phẩm có phải matcha không
 */
function woo_is_matcha_product($product_id) {
    $terms = get_the_terms($product_id, 'product_cat');
    if ($terms && !is_wp_error($terms)) {
        foreach ($terms as $term) {
            if (stripos($term->name, 'matcha') !== false) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Callback hiển thị meta box
 */
function woo_product_option_meta_box_callback($post) {
    // Lấy dữ liệu hiện tại
    $option_groups_data = get_post_meta($post->ID, '_product_option_groups_data', true);
    $extra_info_enabled = get_post_meta($post->ID, '_extra_info_enabled', true);
    
    // Lấy dữ liệu Matcha Gram
    $matcha_gram_enabled = get_post_meta($post->ID, '_matcha_gram_enabled', true);
    $matcha_price_per_gram = get_post_meta($post->ID, '_matcha_price_per_gram', true);
    
    // Tự động tick nếu là sản phẩm matcha và chưa có setting
    $is_matcha = woo_is_matcha_product($post->ID);
    if ($is_matcha && empty($matcha_gram_enabled)) {
        $matcha_gram_enabled = 'yes';
    }
    
    // Lấy danh sách từ settings
    $option_groups = get_option('woo_product_option_groups', array());
    $extra_info_groups = get_option('woo_extra_info_groups', array());
    
    // Parse dữ liệu options
    $options_enabled = isset($option_groups_data['enabled']) ? $option_groups_data['enabled'] : false;
    $selected_groups = isset($option_groups_data['selected_groups']) ? $option_groups_data['selected_groups'] : array();
    $selected_group_ids = isset($option_groups_data['selected_group_ids']) ? $option_groups_data['selected_group_ids'] : array();
    
    wp_nonce_field('woo_product_option_meta_box', 'woo_product_option_meta_box_nonce');
    ?>
    
    <div class="woo-product-option-meta-box">
        
        <!-- Product Options Section -->
        <div class="woo-product-option-section">
            <h2><?php _e('Product Options', 'woo-product-option-setup'); ?></h2>
            
            <p>
                <label>
                    <input type="checkbox" 
                           name="product_options_enabled" 
                           value="yes" 
                           <?php checked($options_enabled, true); ?>>
                    <?php _e('Enable Product Options', 'woo-product-option-setup'); ?>
                </label>
            </p>
            
            <div class="options-content" <?php echo !$options_enabled ? 'style="display:none;"' : ''; ?>>
                <?php if (empty($option_groups)): ?>
                    <p class="no-groups">
                        <?php _e('Chưa có nhóm option nào. Vui lòng tạo nhóm option trong', 'woo-product-option-setup'); ?>
                        <a href="<?php echo admin_url('options-general.php?page=woo-product-options'); ?>" target="_blank">
                            <?php _e('Settings > Product Options', 'woo-product-option-setup'); ?>
                        </a>
                    </p>
                <?php else: ?>
                    <p class="description"><?php _e('Chọn các nhóm option muốn sử dụng cho sản phẩm này:', 'woo-product-option-setup'); ?></p>
                    
                    <?php foreach ($option_groups as $group): ?>
                        <div class="option-group-item <?php echo in_array($group['id'], $selected_group_ids) ? '' : 'collapsed'; ?>">
                            <div class="group-header">
                                <h3>
                                    <label>
                                        <input type="checkbox" 
                                               name="selected_option_groups[]" 
                                               value="<?php echo esc_attr($group['id']); ?>"
                                               <?php checked(in_array($group['id'], $selected_group_ids)); ?>
                                               class="group-checkbox">
                                        <span class="group-title"><?php echo esc_html($group['name']); ?></span>
                                        <span class="group-type">(<?php echo $group['type'] === 'radio' ? __('Radio', 'woo-product-option-setup') : __('Checkbox', 'woo-product-option-setup'); ?>)</span>
                                    </label>
                                </h3>
                            </div>
                            
                            <div class="group-content">
                                <?php 
                                // Chỉ hiển thị options khi group được enable (checked)
                                $is_group_enabled = in_array($group['id'], $selected_group_ids);
                                ?>
                                
                                <?php if ($is_group_enabled && !empty($group['options'])): ?>
                                    <p class="description"><?php _e('Chọn các options cụ thể muốn hiển thị:', 'woo-product-option-setup'); ?></p>
                                    <?php foreach ($group['options'] as $option): ?>
                                        <?php
                                        $available_options = isset($selected_groups[$group['id']]) ? $selected_groups[$group['id']] : array();
                                        $is_available = in_array($option['id'], $available_options);
                                        $custom_price = '';
                                        if (isset($selected_groups[$group['id'] . '_prices'][$option['id']])) {
                                            $custom_price = $selected_groups[$group['id'] . '_prices'][$option['id']];
                                        }
                                        ?>
                                        <div class="option-item">
                                            <label class="option-checkbox">
                                                <input type="checkbox" 
                                                       name="available_options[<?php echo esc_attr($group['id']); ?>][]" 
                                                       value="<?php echo esc_attr($option['id']); ?>"
                                                       <?php checked($is_available); ?>
                                                       class="option-availability-checkbox">
                                                <span class="option-name"><?php echo esc_html($option['name']); ?></span>
                                            </label>
                                            
                                            <input type="number" 
                                                   id="option_price_<?php echo esc_attr($group['id']); ?>_<?php echo esc_attr($option['id']); ?>" 
                                                   name="option_prices[<?php echo esc_attr($group['id']); ?>][<?php echo esc_attr($option['id']); ?>]" 
                                                   value="<?php echo esc_attr($custom_price); ?>" 
                                                   placeholder="<?php echo $option['price'] > 0 ? esc_attr($option['price']) : '0'; ?>"
                                                   min="0" 
                                                   step="1" 
                                                   class="option-price-input">
                                            <span class="price-unit">k</span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php elseif (!$is_group_enabled): ?>
                                    <p class="group-disabled-notice"><?php _e('Vui lòng bật nhóm này để chọn các options cụ thể.', 'woo-product-option-setup'); ?></p>
                                <?php else: ?>
                                    <p class="no-options"><?php _e('Nhóm này chưa có tùy chọn nào.', 'woo-product-option-setup'); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <hr>
        
        <!-- Matcha Gram Options Section -->
        <div class="woo-product-option-section">
            <h2><?php _e('Matcha Gram Options', 'woo-product-option-setup'); ?></h2>
            
            <p>
                <label>
                    <input type="checkbox" 
                           name="matcha_gram_enabled" 
                           value="yes" 
                           <?php checked($matcha_gram_enabled, 'yes'); ?>>
                    <?php _e('Enable Matcha Gram Addition', 'woo-product-option-setup'); ?>
                    <?php if ($is_matcha): ?>
                        <span style="color: #0073aa;"><?php _e('(Tự động phát hiện sản phẩm matcha)', 'woo-product-option-setup'); ?></span>
                    <?php endif; ?>
                </label>
            </p>
            
            <div class="matcha-gram-content" <?php echo $matcha_gram_enabled !== 'yes' ? 'style="display:none;"' : ''; ?>>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="matcha_price_per_gram"><?php _e('Giá mỗi gram thêm (k)', 'woo-product-option-setup'); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="matcha_price_per_gram" 
                                   name="matcha_price_per_gram" 
                                   value="<?php echo esc_attr($matcha_price_per_gram); ?>" 
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
        
        <hr>
        
        <!-- Extra Info Section -->
        <div class="woo-product-option-section">
            <h2><?php _e('Extra Info', 'woo-product-option-setup'); ?></h2>
            
            <p>
                <label>
                    <input type="checkbox" 
                           name="extra_info_enabled" 
                           value="yes" 
                           <?php checked($extra_info_enabled, 'yes'); ?>>
                    <?php _e('Enable Extra Info', 'woo-product-option-setup'); ?>
                </label>
            </p>
            
            <div class="extra-info-content" <?php echo $extra_info_enabled !== 'yes' ? 'style="display:none;"' : ''; ?>>
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
                            <?php
                            $current_value = get_post_meta($post->ID, '_extra_info_' . $info['slug'], true);
                            $is_displayed = !empty($current_value);
                            ?>
                            <div class="option-item">
                                <label class="option-checkbox">
                                    <input type="checkbox" 
                                           name="extra_info_display[<?php echo esc_attr($info['slug']); ?>]" 
                                           value="yes"
                                           <?php checked($is_displayed); ?>>
                                    <span class="option-name"><?php echo esc_html($info['name']); ?></span>
                                </label>
                                
                                <input type="number" 
                                       id="extra_info_value_<?php echo esc_attr($info['slug']); ?>" 
                                       name="extra_info_values[<?php echo esc_attr($info['slug']); ?>]" 
                                       value="<?php echo esc_attr($current_value ?: $info['value']); ?>" 
                                       step="0.5" 
                                       min="0"
                                       class="option-price-input">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
    
    <?php
}

/**
 * Lưu dữ liệu meta box
 */
add_action('save_post', 'woo_product_option_save_meta_box');

function woo_product_option_save_meta_box($post_id) {
    // Kiểm tra nonce
    if (!isset($_POST['woo_product_option_meta_box_nonce']) || 
        !wp_verify_nonce($_POST['woo_product_option_meta_box_nonce'], 'woo_product_option_meta_box')) {
        return;
    }
    
    // Kiểm tra quyền
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Kiểm tra autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Kiểm tra post type
    if (get_post_type($post_id) !== 'product') {
        return;
    }
    
    // Xử lý Product Options
    $options_enabled = isset($_POST['product_options_enabled']) && $_POST['product_options_enabled'] === 'yes';
    $selected_groups = isset($_POST['selected_option_groups']) ? $_POST['selected_option_groups'] : array();
    $available_options = isset($_POST['available_options']) ? $_POST['available_options'] : array();
    $option_prices = isset($_POST['option_prices']) ? $_POST['option_prices'] : array();
    
    // Lấy dữ liệu cũ để preserve khi disable
    $old_data = get_post_meta($post_id, '_product_option_groups_data', true);
    
    $option_groups_data = array(
        'enabled' => $options_enabled,
        'selected_groups' => array(),
        'selected_group_ids' => array()
    );
    
    if ($options_enabled) {
        // Khi enabled: xử lý data mới từ form
        foreach ($available_options as $group_id => $option_ids) {
            if (!empty($option_ids)) {
                $option_groups_data['selected_groups'][$group_id] = $option_ids;
                
                // Lưu giá tùy chỉnh cho các option được chọn
                if (isset($option_prices[$group_id])) {
                    $custom_prices = array();
                    foreach ($option_ids as $option_id) {
                        if (isset($option_prices[$group_id][$option_id]) && !empty($option_prices[$group_id][$option_id])) {
                            $price_value = floatval($option_prices[$group_id][$option_id]);
                            // Validate price range
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
        
        // Cập nhật selected_group_ids dựa trên selected_groups
        $option_groups_data['selected_group_ids'] = array_keys($option_groups_data['selected_groups']);
        
    } else {
        // Khi disabled: preserve data cũ nhưng set enabled = false
        if (!empty($old_data)) {
            $option_groups_data['selected_groups'] = isset($old_data['selected_groups']) ? $old_data['selected_groups'] : array();
            $option_groups_data['selected_group_ids'] = isset($old_data['selected_group_ids']) ? $old_data['selected_group_ids'] : array();
        }
    }
    
    update_post_meta($post_id, '_product_option_groups_data', $option_groups_data);
    
    // Xử lý Matcha Gram Options
    $matcha_gram_enabled = isset($_POST['matcha_gram_enabled']) && $_POST['matcha_gram_enabled'] === 'yes';
    $matcha_price_per_gram = isset($_POST['matcha_price_per_gram']) ? floatval($_POST['matcha_price_per_gram']) : 0;
    
    // Validate giá
    if ($matcha_price_per_gram < 0 || $matcha_price_per_gram > WOO_PRODUCT_OPTION_SETUP_MAX_PRICE) {
        $matcha_price_per_gram = 0;
    }
    
    update_post_meta($post_id, '_matcha_gram_enabled', $matcha_gram_enabled ? 'yes' : 'no');
    update_post_meta($post_id, '_matcha_price_per_gram', $matcha_price_per_gram);
    
    // Xử lý Extra Info
    $extra_info_enabled = isset($_POST['extra_info_enabled']) && $_POST['extra_info_enabled'] === 'yes';
    $extra_info_display = isset($_POST['extra_info_display']) ? $_POST['extra_info_display'] : array();
    $extra_info_values = isset($_POST['extra_info_values']) ? $_POST['extra_info_values'] : array();
    
    // Cập nhật trạng thái enabled
    update_post_meta($post_id, '_extra_info_enabled', $extra_info_enabled ? 'yes' : 'no');
    
    // Lấy danh sách extra info groups
    $extra_info_groups = get_option('woo_extra_info_groups', array());
    
    // Xử lý metadata dựa trên checkbox tick, không phụ thuộc enabled
    foreach ($extra_info_groups as $info) {
        $slug = $info['slug'];
        
        if (isset($extra_info_display[$slug]) && $extra_info_display[$slug] === 'yes') {
            // Lưu value (dù enabled hay không)
            $value_to_save = '';
            if (isset($extra_info_values[$slug]) && !empty($extra_info_values[$slug])) {
                $value_to_save = sanitize_text_field($extra_info_values[$slug]);
            } else {
                $value_to_save = $info['value'];
            }
            update_post_meta($post_id, '_extra_info_' . $slug, $value_to_save);
        } else {
            // Xóa metadata nếu không tick
            delete_post_meta($post_id, '_extra_info_' . $slug);
        }
    }
}
