<?php
/**
 * Plugin Name: Woo Product Option Setup
 * Plugin URI: https://example.com
 * Description: Plugin WooCommerce cho phép cấu hình nhiều Product Option Groups (có giá cộng thêm) và Extra Info Groups (thông tin phụ không cộng tiền).
 * Version: 1.0.9
 * Author: Kien Tran
 * Text Domain: woo-product-option-setup
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * WC requires at least: 5.0
 * WC tested up to: 8.5
 */

// Ngăn chặn truy cập trực tiếp
if (!defined('ABSPATH')) {
    exit;
}

// Kiểm tra WooCommerce có active không
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', 'woo_product_option_setup_woocommerce_notice');
    return;
}

/**
 * Hiển thị thông báo khi WooCommerce chưa được kích hoạt
 */
function woo_product_option_setup_woocommerce_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php _e('Woo Product Option Setup cần WooCommerce plugin để hoạt động. Vui lòng kích hoạt WooCommerce trước.', 'woo-product-option-setup'); ?></p>
    </div>
    <?php
}

// Định nghĩa constants
define('WOO_PRODUCT_OPTION_SETUP_VERSION', '1.0.9');
define('WOO_PRODUCT_OPTION_SETUP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WOO_PRODUCT_OPTION_SETUP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WOO_PRODUCT_OPTION_SETUP_PRICE_MULTIPLIER', 1000); // Chuyển đổi từ k sang đơn vị nhỏ nhất
define('WOO_PRODUCT_OPTION_SETUP_MAX_PRICE', 999999); // Giá tối đa cho phép

/**
 * Class chính của plugin
 */
class Woo_Product_Option_Setup {
    
    /**
     * Instance duy nhất của class
     */
    private static $instance = null;
    
    /**
     * Lấy instance duy nhất
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Khởi tạo plugin
     */
    public function init() {
        // Include các file cần thiết
        $this->include_files();
        
        // Khởi tạo các hooks
        $this->init_hooks();
        
        // Hỗ trợ HPOS (High-Performance Order Storage)
        $this->declare_hpos_compatibility();
    }
    
    /**
     * Load text domain cho đa ngôn ngữ
     */
    public function load_textdomain() {
        load_plugin_textdomain('woo-product-option-setup', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Include các file cần thiết
     */
    private function include_files() {
        // Admin files
        if (is_admin()) {
            require_once WOO_PRODUCT_OPTION_SETUP_PLUGIN_DIR . 'admin/settings-page.php';
            require_once WOO_PRODUCT_OPTION_SETUP_PLUGIN_DIR . 'admin/meta-box.php';
            require_once WOO_PRODUCT_OPTION_SETUP_PLUGIN_DIR . 'admin/bulk-edit.php';
        }
        
        // Frontend files
        require_once WOO_PRODUCT_OPTION_SETUP_PLUGIN_DIR . 'frontend/display-options.php';
        require_once WOO_PRODUCT_OPTION_SETUP_PLUGIN_DIR . 'frontend/cart-hooks.php';
    }
    
    /**
     * Khởi tạo các hooks
     */
    private function init_hooks() {
        // Enqueue scripts và styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Elementor compatibility - FIXED
        add_action('elementor/frontend/before_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('elementor/editor/before_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        
        // AJAX hooks
        add_action('wp_ajax_woo_add_to_cart_with_options', array($this, 'ajax_add_to_cart_with_options'));
        add_action('wp_ajax_nopriv_woo_add_to_cart_with_options', array($this, 'ajax_add_to_cart_with_options'));
        
        
        // Admin hooks
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
        }
    }
    
    /**
     * Enqueue scripts và styles cho frontend
     */
    public function enqueue_frontend_scripts() {
        // Chỉ load khi có shortcode hoặc trang product/archive - FIXED
        if ($this->has_plugin_shortcode() || is_product() || is_archive() || $this->is_elementor_context()) {
            // Enqueue CSS
            wp_enqueue_style(
                'woo-product-option-frontend-css',
                WOO_PRODUCT_OPTION_SETUP_PLUGIN_URL . 'assets/frontend.css',
                array(),
                WOO_PRODUCT_OPTION_SETUP_VERSION
            );
            
            // Enqueue JS
            wp_enqueue_script(
                'woo-product-option-frontend',
                WOO_PRODUCT_OPTION_SETUP_PLUGIN_URL . 'assets/frontend.js',
                array('jquery'),
                WOO_PRODUCT_OPTION_SETUP_VERSION,
                true
            );
            
            // Localize script để truyền dữ liệu từ PHP sang JS
            wp_localize_script('woo-product-option-frontend', 'wooProductOption', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('woo_product_option_nonce'),
                'currencySymbol' => 'k',
                'priceFormat' => '%s'
            ));
        }
    }
    
    /**
     * Enqueue scripts và styles cho admin
     */
    public function enqueue_admin_scripts($hook) {
        // Chỉ load trên trang settings, product edit và bulk edit
        if ($hook === 'settings_page_woo-product-options' || 
            $hook === 'post.php' || 
            $hook === 'post-new.php' ||
            (isset($_GET['page']) && $_GET['page'] === 'woo-bulk-edit-options')) {
            wp_enqueue_script(
                'woo-product-option-admin',
                WOO_PRODUCT_OPTION_SETUP_PLUGIN_URL . 'assets/admin.js',
                array('jquery'),
                WOO_PRODUCT_OPTION_SETUP_VERSION,
                true
            );
            
            wp_enqueue_style(
                'woo-product-option-admin',
                WOO_PRODUCT_OPTION_SETUP_PLUGIN_URL . 'assets/admin.css',
                array(),
                WOO_PRODUCT_OPTION_SETUP_VERSION
            );
            
            // Localize script cho admin
            wp_localize_script('woo-product-option-admin', 'wooProductOptionAdmin', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('woo_product_option_admin_nonce')
            ));
        }
    }
    
    /**
     * Thêm menu admin
     */
    public function add_admin_menu() {
        add_options_page(
            __('Product Options', 'woo-product-option-setup'),
            __('Product Options', 'woo-product-option-setup'),
            'manage_options',
            'woo-product-options',
            'woo_product_option_settings_page'
        );
    }
    
    /**
     * Kích hoạt plugin
     */
    public function activate() {
        // Khởi tạo dữ liệu mặc định nếu cần
        $this->init_default_data();
    }
    
    /**
     * Hủy kích hoạt plugin
     */
    public function deactivate() {
        // Cleanup nếu cần
    }
    
    /**
     * Khởi tạo dữ liệu mặc định
     */
    private function init_default_data() {
        // Khởi tạo mảng rỗng cho options nếu chưa có
        if (!get_option('woo_product_option_groups')) {
            update_option('woo_product_option_groups', array());
        }
        
        if (!get_option('woo_extra_info_groups')) {
            update_option('woo_extra_info_groups', array());
        }
    }
    
    /**
     * AJAX handler cho Add to Cart với options
     */
    public function ajax_add_to_cart_with_options() {
        // Verify nonce - SECURITY FIXED
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'woo_product_option_nonce')) {
            wp_die('Security check failed');
        }
        
        // Validate request method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            wp_die('Invalid request method');
        }
        
        // Validate và sanitize input - SECURITY FIXED
        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $quantity = isset($_POST['quantity']) ? absint($_POST['quantity']) : 1;
        $variation_id = isset($_POST['variation_id']) ? absint($_POST['variation_id']) : 0;
        
        // Validate product_id
        if ($product_id <= 0) {
            wp_send_json_error('Invalid product ID');
        }
        
        // Validate quantity
        if ($quantity <= 0 || $quantity > 100) {
            wp_send_json_error('Invalid quantity');
        }
        
        // Validate product
        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error('Invalid product');
        }
        
        // Prepare cart item data
        $cart_item_data = array();
        
        // Add product options if provided - SECURITY FIXED
        if (isset($_POST['product_options']) && !empty($_POST['product_options'])) {
            // Sanitize product options
            $sanitized_options = array();
            foreach ($_POST['product_options'] as $group_id => $option_ids) {
                $group_id = sanitize_text_field($group_id);
                if (is_array($option_ids)) {
                    $sanitized_options[$group_id] = array_map('sanitize_text_field', $option_ids);
                } else {
                    $sanitized_options[$group_id] = sanitize_text_field($option_ids);
                }
            }
            $cart_item_data['woo_product_options'] = $sanitized_options;
        }
        
        // Add to cart
        $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, array(), $cart_item_data);
        
        if ($cart_item_key) {
            // Get updated cart fragments
            ob_start();
            woocommerce_mini_cart();
            $mini_cart = ob_get_clean();
            
            $fragments = array(
                'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>',
                'span.cart-contents-count' => '<span class="cart-contents-count">' . WC()->cart->get_cart_contents_count() . '</span>'
            );
            
            // Add cart total
            $fragments['span.cart-total'] = '<span class="cart-total">' . WC()->cart->get_cart_total() . '</span>';
            
            wp_send_json_success(array(
                'message' => __('Sản phẩm đã được thêm vào giỏ hàng!', 'woo-product-option-setup'),
                'cart_item_key' => $cart_item_key,
                'fragments' => $fragments,
                'cart_hash' => WC()->cart->get_cart_hash(),
                'cart_count' => WC()->cart->get_cart_contents_count()
            ));
        } else {
            wp_send_json_error('Failed to add to cart');
        }
    }
    
    /**
     * Kiểm tra có shortcode plugin trong content
     */
    private function has_plugin_shortcode() {
        global $post;
        
        // Kiểm tra post content
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'woo_extra_info')) {
            return true;
        }
        
        // Kiểm tra trong Elementor editor/preview - FIXED
        if (class_exists('\Elementor\Plugin')) {
            $elementor = \Elementor\Plugin::$instance;
            
            // Kiểm tra edit mode
            if ($elementor->editor->is_edit_mode() || $elementor->preview->is_preview_mode()) {
                return true;
            }
            
            // Kiểm tra Elementor frontend với template
            if ($elementor->frontend->is_built_with_elementor(get_the_ID())) {
                return true;
            }
            
            // Kiểm tra Elementor Pro widgets
            if (class_exists('\ElementorPro\Plugin')) {
                $elementor_pro = \ElementorPro\Plugin::instance();
                if ($elementor_pro->modules_manager->is_module_active('woocommerce')) {
                    return true;
                }
            }
        }
        
        // Kiểm tra JetThemeCore template
        if (isset($_GET['post_type']) && $_GET['post_type'] === 'jet-theme-core') {
            return true;
        }
        
        // Kiểm tra archive context (có thể có shortcode trong template)
        if (is_archive() || is_home()) {
            return true;
        }
        
        // Kiểm tra trong admin khi edit post
        if (is_admin() && isset($_GET['post']) && get_post_type($_GET['post']) === 'product') {
            return true;
        }
        
        return false;
    }
    
    /**
     * Kiểm tra context Elementor - FIXED
     */
    private function is_elementor_context() {
        // Kiểm tra Elementor editor
        if (class_exists('\Elementor\Plugin')) {
            $elementor = \Elementor\Plugin::$instance;
            
            // Kiểm tra edit mode
            if ($elementor->editor->is_edit_mode() || $elementor->preview->is_preview_mode()) {
                return true;
            }
            
            // Kiểm tra frontend với Elementor
            if (is_singular() && $elementor->frontend->is_built_with_elementor(get_the_ID())) {
                return true;
            }
            
            // Kiểm tra Elementor Pro WooCommerce widgets
            if (class_exists('\ElementorPro\Plugin')) {
                $elementor_pro = \ElementorPro\Plugin::instance();
                if ($elementor_pro->modules_manager->is_module_active('woocommerce')) {
                    return true;
                }
            }
        }
        
        // Kiểm tra trong admin khi edit Elementor template
        if (is_admin() && isset($_GET['action']) && $_GET['action'] === 'elementor') {
            return true;
        }
        
        return false;
    }

    /**
     * Khai báo tương thích với HPOS (High-Performance Order Storage)
     */
    private function declare_hpos_compatibility() {
        add_action('before_woocommerce_init', function() {
            if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
            }
        });
    }
}

// Khởi tạo plugin
Woo_Product_Option_Setup::get_instance();
