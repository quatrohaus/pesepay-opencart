<?php

/**
 * @package Pesepay
 * @author Pesepay <merchantservices@pesepay.com>
 * @link http://pesepay.com
 */

/**
 * Front-end Controller
 * 
 * Class to handle customer checkout
 * 
 * @author Pesepay <merchantservices@pesepay.com>
 * @since 1.0.0
 * @version 1.0.0
 */
class ControllerExtensionPaymentPesepay extends Controller
{

    /**
     * Front end entry point (Checkout)
     * 
     * @version 1.0.0
     * @since 1.0.0
     * @return string
     */
    public function index()
    {

		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		
		$data['ref'] = $this->config->get('payment_paynow_store_name') . ': Order' . $order_info['order_id'];
        $data['action'] = $this->url->link('extension/payment/pesepay/checkout');
        $data['method'] = 'POST';

        return $this->load->view('extension/payment/pesepay', $data);
    }

    /**
     * Handle checkout
     *
     * @version 1.0.0
     * @since 1.0.0
     * @return void
     */
    public function checkout()
    {
        if ($this->request->post) {

            #Models
            $this->load->model('checkout/order');
            $this->load->model('setting/setting');
            $this->load->model('extension/payment/pesepay');

 $this->load->language('extension/payment/pesepay');

            $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

            $links = array(
                "returnUrl" => $this->url->link('extension/payment/pesepay/callback/?order_id=' . $order_info['order_id']),
                "resultUrl" => $this->url->link('extension/payment/pesepay/result/?order_id=' . $order_info['order_id'])
            );

         $title = $this->config->get('payment_pesepay_title') ?: $this->language->get('text_title');

            $ref = $title . ' : Order #' . $order_info['order_id'];

    $total = $order_info['total'] * $order_info["currency_value"];

            $response = $this->model_extension_payment_pesepay->remote_init_transaction($total, $order_info['currency_code'], $ref, $links);           

            if ($response) {

                if ($response["success"]) {
                    # Save the reference number and/or poll url (used to check the status of a transaction)
                    $data = array(
                        "reference_number" => $response["data"]["referenceNumber"],
                        "poll_url" => $response["data"]["pollUrl"]
                    );

                    $this->model_extension_payment_pesepay->insert_transaction($order_info['order_id'], $data);

                    $this->model_extension_payment_pesepay->url_redirect($response["data"]["redirectUrl"]);
                } else {
                    # Get error message
                    echo $response["data"]["transactionStatusDescription"];
                }
            } else {
                # Get generic error message
                echo $this->language->get('error_pesepay_init_transaction');
            }
        }
    }

    /**
     * Handle redirection from pesepay
     * 
     * Confirm if payment was actually made and redirect accordingly
     *
     * @since 1.0.0
     * @version 1.0.0
     * @return void
     */
    public function callback()
    {

        #Models
        $this->load->model('setting/setting');
        $this->load->model('extension/payment/pesepay');

        $order_id = $this->model_extension_payment_pesepay->url_get_param($this->request->get['route'], "order_id");

        if ($order_id && is_numeric($order_id)) {

            $transaction = $this->model_extension_payment_pesepay->retrieve_transaction($order_id);

            $response = $this->model_extension_payment_pesepay->remote_check_transaction($transaction["reference_number"]);

            if ($response) {

                if ($response["success"]) {

                    # Save the reference number and/or poll url (used to check the status of a transaction)
                    $data = array(
                        "reference_number" => $response["data"]["referenceNumber"],
                        "poll_url" => $response["data"]["pollUrl"]
                    );

                    $this->model_extension_payment_pesepay->insert_transaction($order_id, $data);

                    #
                    switch (strtoupper($response["data"]["transactionStatus"])) {
                        case "CANCELLED":
                            //TODO: dynamically set status based on name instead
                            $this->model_extension_payment_pesepay->update_order_status($order_id, 7);

                            $this->model_extension_payment_pesepay->url_redirect($this->url->link('checkout/failure'));
                            break;
                        case "SUCCESS":
                            $status =  $this->config->get('payment_pesepay_order_status');
                            $this->model_extension_payment_pesepay->update_order_status($order_id, $status);

                            $this->model_extension_payment_pesepay->url_redirect($this->url->link('checkout/success'));
                            break;
                        case "FAILED":
                        default:
                            //TODO: dynamically set status based on name instead
                            $this->model_extension_payment_pesepay->update_order_status($order_id, 10);

                            $this->model_extension_payment_pesepay->url_redirect($this->url->link('checkout/failure'));
                    }
                } else {
                    # Get error message
                    $this->model_extension_payment_pesepay->url_refresh_redirect($this->url->link('checkout/checkout'));
                    echo '<h1 style="text-align: center;">' . $response["data"]["transactionStatusDescription"] . '</h1>';
                }
            } else {
                # Get generic error message
                $this->load->language('extension/payment/pesepay');

                $this->model_extension_payment_pesepay->url_refresh_redirect($this->url->link('checkout/checkout'));
                echo '<h1 style="text-align: center;">' . $this->language->get('error_pesepay_check_transaction') . '</h1>';
            }
        }
    }

    /**
     * Handle order state updates
     *
     * @since 1.0.0
     * @version 1.0.0
     * @return void
     */
    public function result()
    {

        #Models
        $this->load->model('setting/setting');
        $this->load->model('extension/payment/pesepay');

        $order_id = $this->model_extension_payment_pesepay->url_get_param($this->request->get['route'], "order_id");

        if ($order_id && is_numeric($order_id)) {

            $transaction = $this->model_extension_payment_pesepay->retrieve_transaction($order_id);

            $response = $this->model_extension_payment_pesepay->remote_check_transaction($transaction["reference_number"]);

            if ($response && $response["success"]) {

                # Save the reference number and/or poll url (used to check the status of a transaction)
                $data = array(
                    "reference_number" => $response["data"]["referenceNumber"],
                    "poll_url" => $response["data"]["pollUrl"]
                );

                $this->model_extension_payment_pesepay->insert_transaction($order_id, $data);

                #
                switch (strtoupper($response["data"]["transactionStatus"])) {
                    case "CANCELLED":
                        //TODO: dynamically set status based on name instead
                        $this->model_extension_payment_pesepay->update_order_status($order_id, 7);
                        break;
                    case "SUCCESS":
                        $status =  $this->config->get('payment_pesepay_order_status');
                        $this->model_extension_payment_pesepay->update_order_status($order_id, $status);
                        break;
                    case "FAILED":
                    default:
                        //TODO: dynamically set status based on name instead
                        $this->model_extension_payment_pesepay->update_order_status($order_id, 10);
                }
            }
        }
    }
}
