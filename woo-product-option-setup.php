<?php
/**
 * Plugin Name: Woo Product Option Setup
 * Plugin URI: https://example.com
 * Description: Plugin WooCommerce cho phép cấu hình nhiều Product Option Groups (có giá cộng thêm) và Extra Info Groups (thông tin phụ không cộng tiền).
 * Version: 1.0.7
 * Author: Your Name
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
define('WOO_PRODUCT_OPTION_SETUP_VERSION', '1.0.7');
define('WOO_PRODUCT_OPTION_SETUP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WOO_PRODUCT_OPTION_SETUP_PLUGIN_URL', plugin_dir_url(__FILE__));

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
        
        // Admin hooks
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
        }
    }
    
    /**
     * Enqueue scripts và styles cho frontend
     */
    public function enqueue_frontend_scripts() {
        if (is_product()) {
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
                'priceFormat' => '%s' // Format đơn giản cho "k"
            ));
        }
    }
    
    /**
     * Enqueue scripts và styles cho admin
     */
    public function enqueue_admin_scripts($hook) {
        // Chỉ load trên trang settings và product edit
        if ($hook === 'settings_page_woo-product-options' || $hook === 'post.php' || $hook === 'post-new.php') {
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
