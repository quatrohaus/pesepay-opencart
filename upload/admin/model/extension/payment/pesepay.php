<?php

/**
 * @package Pesepay
 * @author Pesepay <merchantservices@pesepay.com>
 * @link http://pesepay.com
 */

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;

/**
 * Back-end Model
 * 
 * Class to handle all back-end database operations
 * 
 * @author Pesepay <merchantservices@pesepay.com>
 * @since 1.0.0
 * @version 1.0.0
 */
class ModelExtensionPaymentPesepay extends Model
{

    /**
     * Create table to store order payment status
     *
     * @since 1.0.0
     * @version 1.0.0
     * @return void
     */
    public function install()
    {

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "pesepay` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `order_id` INT NOT NULL,
            `payload` TEXT NOT NULL,
            `time_created` DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (`id`)
          ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
    }

    /**
     * Retrieve the list of currencies that can be processed by the system
     *
     * @since 1.0.0
     * @version 1.0.0
     * @return array
     */
    public function getAvailableCurrencies()
    {

        $url = "v1/currencies/active";

        $config = array(
            "base_url" => "https://api.pesepay.com/api/payments-engine/"
        );

        $client = new GuzzleHttp\Client($config);

        try {

            $response =   $client->get($url, array());
        } catch (ClientException | ConnectException $e) {
            $response = false;
        }

        $currencies = array("USD");
        if ($response && $response->getStatusCode() == 200) {

            $payload = $response->getBody()->getContents();

            if ($payload) {

                $payload = json_decode($payload, true);

                $currencies = array_column($payload, "code");
            }
        }

        $currencies = array_map("strtoupper", $currencies);

        return $currencies;
    }
}
