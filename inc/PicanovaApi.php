<?php

class PicanovaApi
{

    private $product_url = 'https://api.picanova.com/api/beta/products';
    private $variations_url = 'https://api.picanova.com/api/beta/products/';
    private $countries_url = 'https://api.picanova.com/api/beta/countries';
    private $shipping_rates = 'https://api.picanova.com/api/beta/shipping/rates';

    private $apiCreds;

    public function __construct() {
        $this->apiCreds = array(
            'API_User' => get_option('picanova_api_user'),
            'API_Key' => get_option('picanova_api_key'),
        );
    }

    public function getProducts()
    {
        return json_decode($this->request($this->product_url ));
    }

    public function getVariations($id)
    {
        return json_decode($this->request($this->variations_url . $id ));
    }


    public function getVariationId($product_id, $code) {
        $id = get_post_meta($product_id, 'picanova_product', true);
        
        $picanonaVariations = $this->getVariations($id);

        foreach ($picanonaVariations->data as $picanonaVariation) {
            if($picanonaVariation->code == $code) {
                $variant_id = $picanonaVariation->id;
            }
        }
        return $variant_id;
    }

    public function getCountriesList(){
        return json_decode($this->request($this->countries_url ), true)["data"] ?? [];
    }

    public function getShippingRates( $country_id, $card_items ){

        $data = [
            'shipping' => [
                'country' => $country_id
            ],
            'items' => $card_items
        ];

        return json_decode($this->request($this->shipping_rates, $data ), true)['data'] ?? [];
    }

    private function request($url, $data = [] )
    {
        $result = "";
        try {
            $ch = curl_init();

            // Check if initialization had gone wrong*
            if ($ch === false) {
                throw new Exception('failed to initialize');
            }
            $headers = array(
                'accept: application/json',
                'Authorization: Basic '.base64_encode($this->apiCreds['API_User'].":".$this->apiCreds['API_Key'])
            );

            if( ! empty( $data ) ) {

                $jsonData = json_encode($data);

                curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt( $ch, CURLOPT_POSTFIELDS, $jsonData);

                $headers[] = 'Content-Type: application/json';

            }

            
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
         //   curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);

            $result = curl_exec($ch);
            // Check the return value of curl_exec(), too
            if ($result === false) {
                throw new Exception(curl_error($ch), curl_errno($ch));
            }

            /* Process $content here */

            // Close curl handle
            curl_close($ch);
        } catch(Exception $e) {

            trigger_error(sprintf(
                'Curl failed with error #%d: %s',
                $e->getCode(), $e->getMessage()),
                E_USER_ERROR);

        }

        return $result;
    }

}
