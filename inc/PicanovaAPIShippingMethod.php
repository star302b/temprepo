<?php
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    function picanova_api_shipping_method()
    {
        if (!class_exists('Picanova_API_Shipping_Method')) {
            class Picanova_API_Shipping_Method extends WC_Shipping_Method
            {

                public function __construct()
                {
                    $this->id                 = 'picanova_api';
                    $this->method_title       = __('Picanova Shipping', 'picanova_api');
                    $this->method_description = __('Custom Shipping Method for Picanova', 'picanova_api');
                    $this->init();
                    $this->enabled = isset($this->settings['enabled']) ? $this->settings['enabled'] : 'yes';
                    $this->title = isset($this->settings['title']) ? $this->settings['title'] : __('Picanova Shipping', 'picanova_api');
                }

                function init()
                {
                    $this->init_form_fields();
                    $this->init_settings();

                    add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
                }

                function init_form_fields()
                {
                    $this->form_fields = array(
                        'enabled' => array(
                            'title' => __('Enable', 'picanova_api'),
                            'type' => 'checkbox',
                            'description' => __('Enable this shipping.', 'picanova_api'),
                            'default' => 'yes'
                        ),
                        'title' => array(
                            'title' => __('Title', 'picanova_api'),
                            'type' => 'text',
                            'description' => __('Title to be display on site', 'picanova_api'),
                            'default' => __('Picanova API Shipping', 'picanova_api')
                        ),
                    );
                }

                public function calculate_shipping($package = [])
                {

                    global $picanovaApi;

                    $country = $package["destination"]["country"];


                    $all_counties = get_transient('picanova_api_counties_list');
                    if (!$all_counties) {
                        $all_counties = $picanovaApi->getCountriesList();
                        set_transient('picanova_api_counties_list', $all_counties, DAY_IN_SECONDS);
                    }

                    $filteredCountries = array_filter($all_counties, function ($item) use ($country) {
                        return $item['country_code'] === $country;
                    });

                    $filteredCountries = reset($filteredCountries);

                    $country_id = $filteredCountries['country_id'];


                    $data = [];
                    foreach ($package['contents'] as $item_id => $values) {
                        $product = $values['data'];
                        $product_metadata = $product->get_meta('picanova_variation');

                        $variant_id = $picanovaApi->getVariationId($product->id, $product_metadata);
                        if ( key_exists( $variant_id, $data )) {
                            $data[$variant_id]['quantity'] += $values['quantity'];
                        } else {
                            $data[$variant_id] = [
                                'quantity' => $values['quantity'],
                                'variant_id' => $variant_id
                            ];
                        }
                    }

                    $shipping_prices = $picanovaApi->getShippingRates($country_id, $data);

                    foreach ($shipping_prices as $shipping_price) {
                        $rate = array(
                            'id' => $this->id . '_' . $shipping_price['code'],
                            'label' => $shipping_price['name'],
                            'cost' => $shipping_price['price']
                        );
                        $this->add_rate($rate);
                    }
                }
            }
        }
    }

    add_action('woocommerce_shipping_init', 'picanova_api_shipping_method');
    function add_picanova_api_shipping_method($methods)
    {
        $methods[] = 'Picanova_API_Shipping_Method';
        return $methods;
    }
    add_filter('woocommerce_shipping_methods', 'add_picanova_api_shipping_method');
}
