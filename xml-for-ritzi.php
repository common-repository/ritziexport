<?php 

defined('ABSPATH') OR exit;

/**
 * @package RitziExport
 * @version 1.0
 */

 /**
 * Plugin Name:       RitziExport
 * Plugin URI:        https://ritzi.live/
 * Description:       Plugin allows to export products catalog into the Ritzi
 * Version:           1.0.0
 * Requires at least: 5.7.1
 * Requires PHP:      5.6
 * Author:            ritziapp
 * Author URI:        https://ritzi.live/#kontakt
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       xfr
 * Domain Path:       /languages
 */

define('xfr_DIR', plugin_dir_path(__FILE__)); 

class XmlForRitzi 
{
    const XML_FILE = 'xml-for-ritzi.xml';

    public function __construct()
    {
        register_activation_hook(__FILE__, [$this, 'xfr_activation']);
        register_deactivation_hook(__FILE__, [$this, 'xfr_deactivation']);

        add_action('admin_enqueue_scripts', [$this, 'xfr_register_assets']);
        add_action('admin_enqueue_scripts', [$this, 'xfr_load_assets']);
        add_action('admin_menu', [$this, 'xfr_show_nav_item']);
        add_action('admin_init', [$this, 'xfr_settings_init']);
        add_action('init', [$this, 'xfr_start_session']);
        add_action('init', [$this, 'xfr_add_settings_error']);
        add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), [$this, 'xfr_plugin_action_links']);
        add_action('init', [$this, 'xfr_catch_request'], 20);
    }

    /**
     * Activate plugin
     *
     * @return void
     */
    public static function xfr_activation()
    {
        if ( !class_exists( 'WooCommerce')) {
            deactivate_plugins(basename( __FILE__ ));
            wp_die( __( "WooCommerce is required for this plugin to work properly. Please activate WooCommerce.", 'xml-for-ritzi' ), "", array( 'back_link' => 1 ) );
        }

        flush_rewrite_rules();
    }

    /**
     * Deactivate plugin
     *
     * @return void
     */
    public static function xfr_deactivation()
    {
        flush_rewrite_rules();
    }

    /**
     * Start session
     *
     * @retun void
     */
    public function xfr_start_session()
    {
        if(!session_id()) session_start();
    }

    /**
     * Connection css and js
     * 
     * @return void
     */
    public function xfr_register_assets()
    {
        wp_register_style('xfr_styles', plugins_url('assets/css/style.css', __FILE__));
        wp_register_script('xfr_scripts', plugins_url('assets/js/plugin.js', __FILE__));
    }

    /**
     * Load assets files
     * 
     * @param string $hook
     * @return void|null;
     */
    public function xfr_load_assets($hook)
    {
        if ($hook != "toplevel_page_xfr-settings") {
            return;
        }

        wp_enqueue_style('xfr_styles');
        wp_enqueue_script('xfr_scripts');
    }

    /**
     * Adding a plugin to the menu
     * 
     * @return void
     */
    public function xfr_show_nav_item()
    {   
        add_menu_page(
            esc_html__('RitziExport', 'xfr'), 
            esc_html__('RitziExport', 'xfr'), 
            'manage_options', 
            'xfr-settings', 
            [$this, 'xfr_settings'], 
            'dashicons-cloud-upload', 
            26
        );
    }

    /**
     * Adding links to a plugin in the list
     * 
     * @param array $links
     * @return array
     */
    public function xfr_plugin_action_links($links) 
    {
        $plugin_links = [
            "<a href=\"/wp-admin/admin.php?page=xfr-settings\">". esc_html__('Settings', 'xfr').'</a>'
        ];

        return array_merge($plugin_links, $links);
    }

    /**
     * View settings
     * 
     * @return void
     */
    public function xfr_settings()
    {
        require_once xfr_DIR . '/settings.php';
    }

    /**
     * Init settings form
     * 
     * @return void
     */
    public function xfr_settings_init()
    {
        register_setting('xfr_settings', 'xfr_settings_options');
        add_settings_section('xfr_settings_section', esc_html__('Settings', 'xfr'), [$this, 'xfr_settings_section'], 'xfr-settings');
        add_settings_field('quantity_products', esc_html__('Quantity products unloading', 'xfr'), [$this, 'xfr_quantity_products_html'], 'xfr-settings', 'xfr_settings_section');
    }

    /**
     * Init settings section
     * 
     * @return void
     */
    public function xfr_settings_section()
    {
        echo esc_html__("", "xfr");
    }

    /**
     * Added field qantity_products
     * 
     * @return void
     */
    public function xfr_quantity_products_html()
    {
        $value = "";
        $options = get_option('xfr_settings_options');
        if (isset($options['quantity_products'])) {
            $value = $options['quantity_products'];
        }
       
        echo "<input type=\"number\" name=\"xfr_settings_options[quantity_products]\" value=\"" . $value . "\"/>";
    }

    /**
     * Catch request
     *
     * @return void
     */
    public function xfr_catch_request()
    {
        if (!empty($_GET['action']) && !empty($_GET[ 'page' ]) && $_GET['page'] == 'xfr-settings') {
            switch ($_GET['action']) {
                case "export" :
                    include_once(xfr_DIR . 'export.php');
                    try {
                        (new XfrExport(get_option('xfr_settings_options')))->export();
                        $this->xfr_added_notice('xfr_settings_options', 200, 'File generated successfully.' , 'success');
                    } catch (\Exception $th) {
                        (new XfrExport(get_option('xfr_settings_options')))->export();
                        $this->xfr_added_notice('xfr_settings_options', 400, 'File generation error. Please try again or contact technical support.', 'error');
                    }
                    wp_redirect('/wp-admin/admin.php?page=xfr-settings');
                    break;
            }
        }
    }

    /**
     * Added notice
     *
     * @param string $setting
     * @param integer $code
     * @param string $message
     * @param string $type
     * @return void
     */
    protected function xfr_added_notice($setting, $code, $message, $type = "error")
    {
        $_SESSION['xfr_settings_notice'] = [
            'setting' => $setting,
            'code'    => $code,
            'message' => $message,
            'type'    => $type,
        ];
    }

    /**
     * Adding settings error
     *
     * @return void
     */
    public function xfr_add_settings_error()
    {
        global $wp_settings_errors;

        if (isset($_SESSION['xfr_settings_notice'])) {
            $sanitize_xfr_settings_notice = [];
            foreach ($_SESSION['xfr_settings_notice'] as $key => $value) {
                $sanitize_xfr_settings_notice[$key] = sanitize_text_field($value);
            }
            $wp_settings_errors[] = $sanitize_xfr_settings_notice;
            unset($_SESSION["xfr_settings_notice"]);
        }
    }
}

new XmlForRitzi();

