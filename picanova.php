<?php
/*
    Plugin Name: Picanova
    Description: Picanova Settings
    Version: 0.3.1
    Author: NextG
*/

class Picanova_Plugin
{
    private $picanovaApi;

    public function __construct()
    {
        global $picanovaApi;

        require_once 'inc/PicanovaApi.php';
        require_once 'inc/PicanovaProductAdmin.php';
        require_once 'inc/PicanovaAPIShippingMethod.php';
        require_once 'admin/Settings.php';

        $this->picanovaApi = new PicanovaApi();
        $picanovaApi = $this->picanovaApi;

        register_activation_hook(__FILE__, array($this, 'activate'));

        add_action('admin_enqueue_scripts', array($this, 'load_admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'load_front_scripts'));

        add_action('woocommerce_save_product_variation', array($this, 'save_custom_field_variations'), 10, 2);

        add_filter('woocommerce_add_cart_item_data', array($this, 'add_cart_item_data'), 10, 3);
        add_action('woocommerce_before_calculate_totals', array($this, 'before_calculate_totals'), 10, 1);
        add_filter('woocommerce_cart_item_name', array($this, 'add_custom_session'), 1, 3);
    }

    public function activate()
    {
    }

    public function load_admin_scripts()
    {
        wp_enqueue_script('picanova-pl-admin', plugins_url('/assets/js/admin.js', __FILE__), array('jquery'));
        wp_localize_script('picanova-pl-admin', 'ajax_object', array(
            'ajaxurl' => plugins_url('picanova/inc/picanova_functions.php'),
        ));
    }

    public function load_front_scripts()
    {
        wp_register_style('picanova-pl-style', plugins_url('/assets/css/style.css', __FILE__));
        wp_enqueue_style('picanova-pl-style');
        wp_enqueue_script('picanova-pl-front', plugins_url('/assets/js/front.js', __FILE__), array('jquery'));
        if (is_product()) {
            global $post;
            $id = get_post_meta($post->ID, 'picanova_product', true);

            if ($id) {
                $percentage_rate = get_option('picanova_percentage_change') ?? '';

                $result = array();
                $picanonaVariations = $this->picanovaApi->getVariations($id);

                $variations = wc_get_products(
                    array(
                        'status'  => array('private', 'publish'),
                        'type'    => 'variation',
                        'parent'  => $post->ID,
                        'limit'   => 10,
                        'page'    => 1,
                        'orderby' => array(
                            'menu_order' => 'ASC',
                            'ID'         => 'DESC',
                        ),
                        'return'  => 'objects',
                    )
                );

                $usedVariations = array();
                foreach ($variations as $variation) {
                    $picanova_variation_code = get_post_meta($variation->get_id(), 'picanova_variation', true);
                    if (!empty($picanova_variation_code)) {
                        $usedVariations[$picanova_variation_code] = $variation->get_id();
                    }
                }

                foreach ($picanonaVariations->data as $picanonaVariation) {
                    if (isset($usedVariations[$picanonaVariation->code])) {
                        if ($percentage_rate !== '' && $percentage_rate != 0) {
                            foreach ($picanonaVariation->options as $item) {
                                foreach ($item->values as $option) {
                                    $option->price = $this->calculate_new_price($percentage_rate, $option->price);
                                }
                            }
                        }

                        $result[$usedVariations[$picanonaVariation->code]] = $picanonaVariation->options;
                    }
                }

                wp_localize_script(
                    'picanova-pl-front',
                    'envData',
                    array(
                        'url'     => admin_url('admin-ajax.php'),
                        'options' => $result,
                    )
                );
            }
        }
    }

    public function save_custom_field_variations($variation_id, $i)
    {
        $custom_field = $_POST['picanova_variation'][$i];
        if (!empty($custom_field)) {
            update_post_meta($variation_id, 'picanova_variation', esc_attr($custom_field));
        } else {
            delete_post_meta($variation_id, 'picanova_variation');
        }
    }

    public function add_cart_item_data($cart_item_data, $product_id, $variation_id)
    {
        if (isset($_POST["add_option"]) && isset($_POST["variation_id"])) {
            $id = get_post_meta($_POST["product_id"], 'picanova_product', true);
            $picanonaVariations = $this->picanovaApi->getVariations($id);
            $picanonaVariations = $picanonaVariations->data;
            $picanova_variation_code = get_post_meta($_POST["variation_id"], 'picanova_variation', true);
            $picanovaOptions = array();

            foreach ($picanonaVariations as $picanonaVariation) {
                if ($picanonaVariation->code == $picanova_variation_code) {
                    $picanovaOptions = $picanonaVariation->options;
                }
            }

            if (!empty($picanovaOptions)) {
                $percentage_rate = get_option('picanova_percentage_change') ?? '';

                foreach ($_POST["add_option"] as $key => $option) {
                    foreach ($picanovaOptions->{$key}->values as $value) {
                        if ($value->id == $option) {
                            $cart_item_data['addons'][$key] = array(
                                "id"          => $option,
                                "price"       => $this->calculate_new_price($percentage_rate, $value->price),
                                "item_name"   => $value->name,
                                "option_name" => $picanovaOptions->{$key}->name,
                            );
                        }
                    }
                }
            }
        }
        return $cart_item_data;
    }

    public function before_calculate_totals($cart_obj)
    {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        foreach ($cart_obj->get_cart() as $key => $cart_item) {
            if (isset($cart_item["addons"])) {
                $price = $cart_item['data']->get_price();
                foreach ($cart_item["addons"] as $addon) {
                    $price += $addon["price"];
                }
                $cart_item['data']->set_price($price);
            }
        }
    }

    public function add_custom_session($product_name, $values, $cart_item_key)
    {
        $return_string = $product_name . "<br />";

        foreach ($values["addons"] as $addon) {
            $return_string .= "<b>" . $addon['option_name'] . ":</b> " . $addon['item_name'];
            if ($addon['price'] != "") {
                $return_string .= " (+" . wc_price($addon['price']) . ")";
            }
            $return_string .= "<br />";
        }

        return $return_string;
    }

    public function calculate_new_price($percentage_rate, $base_price)
    {
        if ($percentage_rate > 0) {
            $base_price = $base_price * (1 + ($percentage_rate / 100));
        } else {
            $base_price = $base_price * (1 - (-$percentage_rate / 100));
        }

        return round($base_price, 2);
    }
}

new Picanova_Plugin();
