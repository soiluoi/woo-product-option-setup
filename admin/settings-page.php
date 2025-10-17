<?php
/**
 * Trang Settings Admin cho Product Option Setup
 */

// Ngăn chặn truy cập trực tiếp
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Hiển thị trang settings
 */
function woo_product_option_settings_page() {
    // Xử lý form submit
    if (isset($_POST['submit']) && wp_verify_nonce($_POST['woo_product_option_nonce'], 'woo_product_option_settings')) {
        woo_product_option_save_settings();
    }
    
    // Lấy dữ liệu hiện tại
    $option_groups = get_option('woo_product_option_groups', array());
    $extra_info_groups = get_option('woo_extra_info_groups', array());
    ?>
    
    <div class="wrap">
        <h1><?php _e('Product Options Settings', 'woo-product-option-setup'); ?></h1>
        
        <form method="post" action="">
            <?php wp_nonce_field('woo_product_option_settings', 'woo_product_option_nonce'); ?>
            
            <!-- Product Option Groups Section -->
            <div class="woo-product-option-section">
                <h2><?php _e('Product Option Groups', 'woo-product-option-setup'); ?></h2>
                <p class="description"><?php _e('Tạo các nhóm option có thể cộng thêm giá cho sản phẩm.', 'woo-product-option-setup'); ?></p>
                
                <div id="option-groups-container">
                    <?php
                    if (empty($option_groups)) {
                        echo '<p class="no-groups">' . __('Chưa có nhóm option nào. Nhấn "Thêm nhóm" để bắt đầu.', 'woo-product-option-setup') . '</p>';
                    } else {
                        foreach ($option_groups as $index => $group) {
                            woo_product_option_render_group($group, $index);
                        }
                    }
                    ?>
                </div>
                
                <button type="button" id="add-option-group" class="button button-secondary">
                    <?php _e('+ Thêm nhóm', 'woo-product-option-setup'); ?>
                </button>
            </div>
            
            <hr>
            
            <!-- Extra Info Groups Section -->
            <div class="woo-product-option-section">
                <h2><?php _e('Extra Info Groups', 'woo-product-option-setup'); ?></h2>
                <p class="description"><?php _e('Tạo các thông tin phụ không cộng tiền cho sản phẩm.', 'woo-product-option-setup'); ?></p>
                
                <div id="extra-info-groups-container">
                    <?php
                    if (empty($extra_info_groups)) {
                        echo '<p class="no-groups">' . __('Chưa có Extra Info nào. Nhấn "Thêm Extra Info" để bắt đầu.', 'woo-product-option-setup') . '</p>';
                    } else {
                        foreach ($extra_info_groups as $index => $info) {
                            woo_product_option_render_extra_info($info, $index);
                        }
                    }
                    ?>
                </div>
                
                <button type="button" id="add-extra-info-group" class="button button-secondary">
                    <?php _e('+ Thêm Extra Info', 'woo-product-option-setup'); ?>
                </button>
            </div>
            
            <?php submit_button(__('Lưu cài đặt', 'woo-product-option-setup')); ?>
        </form>
    </div>
    
    <!-- Template cho nhóm option mới -->
    <script type="text/template" id="option-group-template">
        <?php echo woo_product_option_get_group_template(); ?>
    </script>
    
    <!-- Template cho Extra Info mới -->
    <script type="text/template" id="extra-info-template">
        <?php echo woo_product_option_get_extra_info_template(); ?>
    </script>
    
    <?php
}

/**
 * Render một nhóm option
 */
function woo_product_option_render_group($group, $index) {
    $group_id = isset($group['id']) ? $group['id'] : 'group_' . $index;
    $group_name = isset($group['name']) ? esc_attr($group['name']) : '';
    $group_type = isset($group['type']) ? $group['type'] : 'radio';
    $options = isset($group['options']) ? $group['options'] : array();
    ?>
    
    <div class="option-group-item" data-index="<?php echo $index; ?>">
        <div class="group-header">
            <h3>
                <span class="group-title"><?php echo $group_name ?: __('Nhóm mới', 'woo-product-option-setup'); ?></span>
                <button type="button" class="button button-small remove-group"><?php _e('Xóa', 'woo-product-option-setup'); ?></button>
            </h3>
        </div>
        
        <div class="group-content">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="group_name_<?php echo $index; ?>"><?php _e('Tên nhóm', 'woo-product-option-setup'); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               id="group_name_<?php echo $index; ?>" 
                               name="option_groups[<?php echo $index; ?>][name]" 
                               value="<?php echo $group_name; ?>" 
                               class="regular-text group-name-input" 
                               required>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="group_type_<?php echo $index; ?>"><?php _e('Loại', 'woo-product-option-setup'); ?></label>
                    </th>
                    <td>
                        <select id="group_type_<?php echo $index; ?>" name="option_groups[<?php echo $index; ?>][type]" class="group-type-select">
                            <option value="radio" <?php selected($group_type, 'radio'); ?>><?php _e('Radio (chọn 1)', 'woo-product-option-setup'); ?></option>
                            <option value="checkbox" <?php selected($group_type, 'checkbox'); ?>><?php _e('Checkbox (chọn nhiều)', 'woo-product-option-setup'); ?></option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label><?php _e('Các tùy chọn', 'woo-product-option-setup'); ?></label>
                    </th>
                    <td>
                        <div class="options-container">
                            <?php
                            if (empty($options)) {
                                echo '<p class="no-options">' . __('Chưa có tùy chọn nào. Nhấn "Thêm tùy chọn" để bắt đầu.', 'woo-product-option-setup') . '</p>';
                            } else {
                                foreach ($options as $opt_index => $option) {
                                    woo_product_option_render_option($option, $index, $opt_index);
                                }
                            }
                            ?>
                        </div>
                        
                        <button type="button" class="button button-small add-option" data-group-index="<?php echo $index; ?>">
                            <?php _e('+ Thêm tùy chọn', 'woo-product-option-setup'); ?>
                        </button>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    
    <?php
}

/**
 * Render một option trong nhóm
 */
function woo_product_option_render_option($option, $group_index, $option_index) {
    $option_id = isset($option['id']) ? $option['id'] : 'opt_' . $option_index;
    $option_name = isset($option['name']) ? esc_attr($option['name']) : '';
    $option_price = isset($option['price']) ? esc_attr($option['price']) : '';
    ?>
    
    <div class="option-item" data-option-index="<?php echo $option_index; ?>">
        <input type="text" 
               name="option_groups[<?php echo $group_index; ?>][options][<?php echo $option_index; ?>][name]" 
               value="<?php echo $option_name; ?>" 
               placeholder="<?php _e('Tên tùy chọn', 'woo-product-option-setup'); ?>" 
               class="option-name" 
               required>
        
        <input type="number" 
               name="option_groups[<?php echo $group_index; ?>][options][<?php echo $option_index; ?>][price]" 
               value="<?php echo $option_price; ?>" 
               placeholder="<?php _e('Giá cộng thêm (k)', 'woo-product-option-setup'); ?>" 
               class="option-price" 
               min="0" 
               step="1">
        
        <span class="price-unit">k</span>
        
        <button type="button" class="button button-small remove-option"><?php _e('Xóa', 'woo-product-option-setup'); ?></button>
    </div>
    
    <?php
}

/**
 * Render một Extra Info
 */
function woo_product_option_render_extra_info($info, $index) {
    $info_id = isset($info['id']) ? $info['id'] : 'info_' . $index;
    $info_name = isset($info['name']) ? esc_attr($info['name']) : '';
    $info_type = isset($info['type']) ? $info['type'] : 'number';
    $info_step = isset($info['step']) ? esc_attr($info['step']) : '0.5';
    ?>
    
    <div class="option-item" data-index="<?php echo $index; ?>">
        <input type="text" 
               name="extra_info_groups[<?php echo $index; ?>][name]" 
               value="<?php echo $info_name; ?>" 
               placeholder="<?php _e('Tên Extra Info', 'woo-product-option-setup'); ?>" 
               class="option-name" 
               required>
        
        <input type="number" 
               name="extra_info_groups[<?php echo $index; ?>][value]" 
               value="<?php echo isset($info['value']) ? esc_attr($info['value']) : ''; ?>" 
               placeholder="<?php _e('Giá trị mặc định', 'woo-product-option-setup'); ?>" 
               class="option-price" 
               step="0.5" 
               min="0">
        
        <button type="button" class="button button-small remove-option"><?php _e('Xóa', 'woo-product-option-setup'); ?></button>
        
        <input type="hidden" name="extra_info_groups[<?php echo $index; ?>][type]" value="number">
        <input type="hidden" name="extra_info_groups[<?php echo $index; ?>][step]" value="0.5">
    </div>
    
    <?php
}

/**
 * Lưu settings
 */
function woo_product_option_save_settings() {
    // Xử lý Product Option Groups
    if (isset($_POST['option_groups'])) {
        $option_groups = array();
        foreach ($_POST['option_groups'] as $group) {
            if (!empty($group['name'])) {
                $group_id = 'group_' . uniqid();
                $options = array();
                
                if (isset($group['options'])) {
                    foreach ($group['options'] as $option) {
                        if (!empty($option['name'])) {
                            $option_id = 'opt_' . uniqid();
                            $options[] = array(
                                'id' => $option_id,
                                'name' => sanitize_text_field($option['name']),
                                'price' => intval($option['price']) // Giữ nguyên giá trị từ input
                            );
                        }
                    }
                }
                
                $option_groups[] = array(
                    'id' => $group_id,
                    'name' => sanitize_text_field($group['name']),
                    'type' => sanitize_text_field($group['type']),
                    'options' => $options
                );
            }
        }
        
        update_option('woo_product_option_groups', $option_groups);
    }
    
    // Xử lý Extra Info Groups
    if (isset($_POST['extra_info_groups'])) {
        $extra_info_groups = array();
        foreach ($_POST['extra_info_groups'] as $info) {
            if (!empty($info['name'])) {
                $info_id = 'info_' . uniqid();
                $slug = sanitize_title($info['name']);
                
                $extra_info_groups[] = array(
                    'id' => $info_id,
                    'slug' => $slug,
                    'name' => sanitize_text_field($info['name']),
                    'value' => floatval($info['value']),
                    'type' => 'number',
                    'step' => 0.5
                );
            }
        }
        
        update_option('woo_extra_info_groups', $extra_info_groups);
    }
    
    add_action('admin_notices', function() {
        echo '<div class="notice notice-success"><p>' . __('Cài đặt đã được lưu thành công!', 'woo-product-option-setup') . '</p></div>';
    });
}

/**
 * Lấy template cho nhóm option mới
 */
function woo_product_option_get_group_template() {
    ob_start();
    woo_product_option_render_group(array(), '{{INDEX}}');
    return ob_get_clean();
}

/**
 * Lấy template cho Extra Info mới
 */
function woo_product_option_get_extra_info_template() {
    ob_start();
    woo_product_option_render_extra_info(array(), '{{INDEX}}');
    return ob_get_clean();
}
