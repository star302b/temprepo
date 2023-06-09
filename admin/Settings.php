<?php
// Custom Option Page Class
class Settings_Page {
    /**
     * Initialize the custom option page
     */
    public function init() {
        add_action('admin_menu', array($this, 'add_custom_option_page'));
        add_action('admin_init', array($this, 'register_custom_option_settings'));
    }

    /**
     * Add custom option page under the WooCommerce menu
     */
    public function add_custom_option_page() {
        add_submenu_page(
            'woocommerce',
            'Picanova API Settings',
            'Picanova API Settings',
            'manage_options',
            'custom-options',
            array($this, 'render_custom_option_page')
        );
    }

    /**
     * Render the custom option page content
     */
    public function render_custom_option_page() {
        ?>
        <div class="wrap">
            <h1>Picanova API Settings</h1>
            <form method="post" action="options.php">
                <?php
                // Output the settings fields
                settings_fields('custom_options_group');
                do_settings_sections('picanova-api-options');
                do_settings_sections('custom-options');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register the custom option settings and fields
     */
    public function register_custom_option_settings() {
        // Register a new settings section
        add_settings_section(
            'picanova_api_section',
            'Picanova API Settings',
            array(),
            'picanova-api-options'
        );

        // Register the custom option field
        add_settings_field(
            'picanova_API_User',
            'Picanova API User',
            array($this, 'picanova_API_User_field_callback'),
            'picanova-api-options',
            'picanova_api_section'
        );

        add_settings_field(
            'picanova_API_Key',
            'Picanova API Key',
            array($this, 'picanova_API_Key_field_callback'),
            'picanova-api-options',
            'picanova_api_section'
        );

        // Register a new settings section
        add_settings_section(
            'custom_options_section',
            'Picanova Products Percentage Change',
            array($this, 'custom_options_section_callback'),
            'custom-options'
        );

        // Register the custom option field
        add_settings_field(
            'picanova_percentage_change',
            'Picanova Products Percentage Change',
            array($this, 'custom_option_field_callback'),
            'custom-options',
            'custom_options_section'
        );

        // Register the settings group and field
        register_setting(
            'custom_options_group',
            'picanova_percentage_change',
        );
        register_setting(
            'custom_options_group',
            'picanova_api_user',
        );
        register_setting(
            'custom_options_group',
            'picanova_api_key',
        );
    }

    /**
     * Custom options section callback
     */
    public function custom_options_section_callback() {
        echo '<p>As we can’t control prices that get from API for options  (Canvas border, Stretcher frame, Frame, Easel Back), we have the ability to increase them by some percent(globally)</p>';
        echo '<p>Zero or empty value = no changes for the price</p>';
        echo '<p>Above zero value = increase the price</p>';
        echo '<p>Below zero value = decrease the price</p>';
    }

    /**
     * Custom option field callback
     */
    public function custom_option_field_callback() {
        $picanova_percentage_change = get_option('picanova_percentage_change');
        echo '<input type="text" name="picanova_percentage_change" value="' . esc_attr($picanova_percentage_change) . '">';
    }

    /**
     * Custom option field callback
     */
    public function picanova_API_User_field_callback() {
        $picanova_API_User = get_option('picanova_api_user');
        echo '<input type="text" name="picanova_api_user" value="' . esc_attr($picanova_API_User) . '">';
    }

    /**
     * Custom option field callback
     */
    public function picanova_API_Key_field_callback() {
        $picanova_API_Key = get_option('picanova_api_key');
        echo '<input type="text" name="picanova_api_key" value="' . esc_attr($picanova_API_Key) . '">';
    }
}

// Initialize the Custom Option Page
$custom_option_page = new Settings_Page();
$custom_option_page->init();
