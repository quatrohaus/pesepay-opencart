<?php

/**
 * @package Pesepay
 * @author Pesepay <merchantservices@pesepay.com>
 * @link http://pesepay.com
 */

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;

/**
 * Front-end Model
 * 
 * Class to handle all front-end database operations
 * 
 * @author Pesepay <merchantservices@pesepay.com>
 * @since 1.0.0
 * @version 1.0.0
 */
class ModelExtensionPaymentPesepay extends Model
{

    /**
     * Transactions table
     *
     * @version 1.0.0
     * @since 1.0.0
     * @var string
     */
    private $table = "pesepay";

    /**
     * Initialise the payment gateway
     *
     * @param string $address
     * @param float $total
     * @return void
     */
    public function getMethod($address, $total)
    {

        $this->load->language('extension/payment/pesepay');

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('payment_pesepay_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

        if ($this->config->get('payment_pesepay_total') > 0 && $this->config->get('payment_pesepay_total') > $total) {
            $status = false;
        } elseif (empty($this->config->get('payment_pesepay_encryption_key')) && empty($this->config->get('payment_pesepay_encryption_key'))) {
            $status = false;
        } elseif (!$this->config->get('payment_pesepay_geo_zone_id')) {
            $status = true;
        } elseif ($query->num_rows) {
            $status = true;
        } else {
            $status = false;
        }

        $currencies = $this->config->get('payment_pesepay_currency');

        if (!in_array(strtoupper($this->session->data['currency']), $currencies)) {
            $status = false;
        }

        $method_data = array();

        if ($status) {

            $title = $this->config->get('payment_pesepay_title') ?: $this->language->get('text_title');

            if ($this->config->get('payment_pesepay_logo') == "on") {
                $title .= $this->language->get('text_logo');
            }

            $method_data = array(
                'code'       => $this->table,
                'title'      => $title,
                'terms'      => '',
                'sort_order' => $this->config->get('payment_pesepay_sort_order')
            );
        }

        return $method_data;
    }

    /**
     * Save transaction status
     *
     * @since 1.0.0
     * @version 1.0.0
     * @param int $order_id
     * @param array|string $data
     * @return void
     */
    public function insert_transaction($order_id, $data)
    {

        $data = $this->db_serialize_content($data);

        $this->db->query("INSERT INTO `" . DB_PREFIX . $this->table . "` (`order_id`, `payload`) VALUES ('" . $this->db->escape($order_id) . "', '" . $this->db->escape($data) . "')");
    }

    /**
     * Update the details of a transaction
     *
     * @since 1.0.0
     * @version 1.0.0
     * @param int $order_id
     * @param array $data
     * @return void
     */
    public function update_transaction($order_id, $data = array())
    {

        $data = $this->db_serialize_content($data);

        $this->db->query("UPDATE `" . DB_PREFIX . $this->table . "` SET `payload` = '" . $this->db->escape($data) . "' WHERE order_id='" . $this->db->escape($order_id) . "'");
    }

    /**
     * Retrieve transaction by order id
     *
     * @since 1.0.0
     * @version 1.0.0
     * @param int $order_id
     * @return array
     */
    public function retrieve_transaction($order_id)
    {
        $data =  $this->db->query("SELECT `payload` FROM `" . DB_PREFIX . $this->table . "` WHERE `order_id` = '" . $this->db->escape($order_id) . "';");

        return $this->db_unserialize_content($data->row["payload"]);
    }

    /**
     * Set the status of the order
     *
     * @since 1.0.0
     * @version 1.0.0
     * @param int $order_id
     * @param int $status_id
     * @return void
     */
    public function update_order_status($order_id, $status_id)
    {
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET `order_status_id` = '" . $this->db->escape($status_id) . "' WHERE `order_id` ='" . $this->db->escape($order_id) . "'");
    }

    /**
     * Serialize given data 
     *
     * @since 1.0.0
     * @version 1.0.0
     * @param array|string $data
     * @return string
     */
    private function db_serialize_content($data)
    {
        $data = serialize($data);

        return base64_encode($data);
    }

    /**
     * Unserialize given data
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @param string $data
     * @return array|string
     */
    private function db_unserialize_content($data)
    {
        $data = base64_decode($data);

        return unserialize($data);
    }

    /**
     * Api functions
     */

    /**
     * Length of encryption key
     *
     * @since 1.0.0
     * @version 1.0.0
     * @var array|integer
     */
    private $ENCRYPTION_KEY_LENGTH = array(16, 32);

    /**
     * The algorithm to use for the encryption
     *
     * @since 1.0.0
     * @version 1.0.0
     * @var string
     */
    private $ENCRYPTION_ALGORITHM = "aes-256-cbc";

    /**
     * The base url to build upon requests
     *
     * @since 1.0.0
     * @version 1.0.0
     * @var string
     */
    private $URL_BASE = "http://api.pesepay.com/api/payments-engine/";

    /**
     * Initiate a transaction on pesepay
     *
     * @param float $amount
     * @param string $currency
     * @param string $reason
     * @param array $links
     * @return array|bool
     */
    public function remote_init_transaction($amount, $currency, $reason, $links)
    {

        $url = "v1/payments/initiate";

        $data = array(
            "amountDetails" => array(
                "amount" => $amount,
                "currencyCode" => $currency
            ),
            "reasonForPayment" => $reason,
            "resultUrl" => $links["resultUrl"],
            "returnUrl" => $links["returnUrl"]
        );

        return $this->remote_request($url, $data);
    }

    /**
     * Check the status of a transaction
     *
     * @since 1.0.0
     * @version 1.0.0
     * @param string $reference
     * @return array|bool
     */
    public function remote_check_transaction($reference)
    {

        $url = "v1/payments/check-payment";

        $data = array(
            "referenceNumber" => $reference
        );

        $response = $this->remote_request($url, $data, "GET");

        $response["success"] = isset($response["data"]["transactionStatus"]) && $response["data"]["transactionStatus"] == "SUCCESS";

        return $response;
    }

    /**
     * Perform remote request to pesepay
     *
     * @since 1.0.0
     * @version 1.0.0
     * @param string $url
     * @param string $payload
     * @return array|bool
     */
    private function remote_request($url, $payload = "", $method = "POST")
    {

        $url = rtrim($url, "/\\");

        $headers = array(
            'Authorization' => $this->config->get('payment_pesepay_integration_key')
        );

        $config = array(
            "base_url" => $this->URL_BASE
        );

        $client = new GuzzleHttp\Client($config);

        /**
         * Post takes different params from get
         */
        try {

            switch (strtoupper($method)) {
                case "POST":
                    if (is_array($payload)) {
                        $payload = json_encode($payload);
                    }

                    $data = $this->content_encrypt($this->config->get('payment_pesepay_encryption_key'), $payload);
                    $payload = array("payload" => $data);

                    $response =   $client->post($url, array(
                        "body" => json_encode($payload),
                        "headers" => array_merge($headers, array(
                            'Content-Type' => 'application/json'
                        ))
                    ));
                    break;
                case "GET":

                    $url =   $this->url_append_param($url, $payload);

                    $response =   $client->get($url, array(
                        "headers" => $headers
                    ));
                    break;
            }
        } catch (ClientException | ConnectException $e) {
            $response = false;
        }

        if ($response && $response->getStatusCode() == 200) {

            $payload = $response->getBody()->getContents();

            if ($payload) {

                $payload = json_decode($payload, true);

                if (isset($payload["payload"])) {

                    $data = $this->content_decrypt($this->config->get('payment_pesepay_encryption_key'), $payload["payload"]);
                    $success = true;
                } else {
                    $data =  $payload["message"];
                    $success = false;
                }
                return array(
                    "success" => $success,
                    "data" => json_decode($data, true)
                );
            }
        }
        return false;
    }

    /**
     * Decrypt content with given key
     *
     * @since 1.0.0
     * @version 1.0.0
     * @param string $key
     * @param string $content
     * @return string
     */
    private function content_decrypt($key, $content = "")
    {

        $iv = $this->encryption_key_get_iv($key);

        return openssl_decrypt($content, $this->ENCRYPTION_ALGORITHM, $key, 0, $iv);
    }

    /**
     * Encrypt content with given key
     *
     * @since 1.0.0
     * @version 1.0.0
     * @param string $key
     * @param string $content
     * @return string
     */
    private function content_encrypt($key, $content = "")
    {

        $iv = $this->encryption_key_get_iv($key);

        return openssl_encrypt($content, $this->ENCRYPTION_ALGORITHM, $key, 0, $iv);
    }

    /**
     * Get initialisation vector for key
     *
     * @since 1.0.0
     * @version 1.0.0
     * @param string $key
     * @return string
     */
    private function encryption_key_get_iv($key)
    {
        return substr($key, 0, $this->ENCRYPTION_KEY_LENGTH[0]);
    }

    /**
     * Helper Functions
     */

    /**
     * Append params to an existing url
     * 
     * Handles cases where the url already has parameters
     *
     * @since 1.0.0
     * @version 1.0.0
     * @param string $url
     * @param array|string $params
     * @return string
     */
    public function url_append_param($url, $params)
    {

        if (is_array($params)) {
            $params = http_build_query($params);
        }

        if ($params) {
            if (parse_url($url, PHP_URL_QUERY)) {
                $url .= "&";
            } else {
                $url .= "?";
            }
            $url .=  $params;
        }

        return $url;
    }

    /**
     * Get a param from a url if it exists
     *
     * @since 1.0.0
     * @version 1.0.0
     * @param string $url
     * @param string $param
     * @return string
     */
    public function url_get_param($url, $param = "")
    {

        $value = "";

        $query = parse_url($url, PHP_URL_QUERY);
        parse_str($query, $args);

        if ($param && isset($args[$param])) {
            $value = $args["order_id"];
        }

        return $value;
    }

    /**
     * Redirect to given url, falls back to javascript
     *
     * @since 1.0.0
     * @version 1.0.0
     * @param string $url
     * @return void
     */
    public function url_redirect($url)
    {
        if (headers_sent()) {
            echo "<script>window.location = '" . $url . "';</script>";;
            ob_flush();
        } else {
            header('location:' . $url);
        }
    }

    /**
     * Undocumented function
     *
     * @param [type] $url
     * @return void
     */
    public function url_refresh_redirect($url)
    {
        if (headers_sent()) {
            #javascript work around
            echo '<meta http-equiv="refresh" content="5; url=' . $url . '">';
            ob_flush();
        } else {
            header('refresh: 5;url=' . $url);
        }
    }
}