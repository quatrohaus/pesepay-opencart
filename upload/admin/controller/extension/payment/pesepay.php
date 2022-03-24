<?php

/**
 * @package Pesepay
 * @author Pesepay <merchantservices@pesepay.com>
 * @link http://pesepay.com
 */

/**
 * Back-end Controller
 * 
 * Class to handle setup of extension options
 * 
 * @author Pesepay <merchantservices@pesepay.com>
 * @since 1.0.0
 * @version 1.0.0
 */
class ControllerExtensionPaymentPesepay extends Controller
{

    /**
     * Extension Error
     *
     * @since 1.0.0
     * @version 1.0.0
     * @var array
     */
    private $error = array();

    /**
     * Length of the encryption key
     *
     * @since 1.0.0
     * @version 1.0.0
     * @var array|integer
     */
    private $KEY_LENGTH = array(16, 32);

    /**
     * Extension entry point (Admin side)
     *
     * @since 1.0.0
     * @version 1.0.0
     * @return void
     */
    public function index()
    {

        $this->load->language('extension/payment/pesepay');

        $this->document->setTitle($this->language->get('text_title'));

        #Models
        $this->load->model('setting/setting');
        $this->load->model('extension/payment/pesepay');

        $this->model_extension_payment_pesepay->install();

        //Save the settings if the user has submitted the admin form (ie if someone has pressed save).
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate_post_data()) {
            $this->model_setting_setting->editSetting('payment_pesepay', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }

        //errors
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (empty($this->config->get('payment_pesepay_title'))) {
            $data['error_pesepay_title'] = $this->language->get('error_title');
        } else {
            $data['error_pesepay_title'] = '';
        }

        if (empty($this->config->get('payment_pesepay_total'))) {
            $data['error_pesepay_total'] = $this->language->get('error_total');
        } else {
            $data['error_pesepay_total'] = '';
        }

        if (empty($this->config->get('payment_pesepay_sort_order'))) {
            $data['error_pesepay_sort_order'] = $this->language->get('error_sort_order');
        } else {
            $data['error_pesepay_sort_order'] = '';
        }

        if (empty($this->config->get('payment_pesepay_geo_zone_id'))) {
            $data['error_pesepay_geo_zone_id'] = $this->language->get('error_geo_zone_id');
        } else {
            $data['error_pesepay_geo_zone_id'] = '';
        }

        if (isset($this->error['error_pesepay_currency'])) {
            $data['error_pesepay_currency'] = $this->language->get('error_currency');
        } else {
            $data['error_pesepay_currency'] = '';
        }

        if (isset($this->error['error_pesepay_encryption_key'])) {
            $data['error_pesepay_encryption_key'] = $this->error['error_pesepay_encryption_key'];
        } else {
            $data['error_pesepay_encryption_key'] = '';
        }

        if (isset($this->error['error_pesepay_integration_key'])) {
            $data['error_pesepay_integration_key'] = $this->error['error_pesepay_integration_key'];
        } else {
            $data['error_pesepay_integration_key'] = '';
        }

        //bread crumps
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_home'),
            'href'      => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
            'separator' => false
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/pesepay', 'user_token=' . $this->session->data['user_token'], true)
        );

        //controls
        $data['action'] = $this->url->link('extension/payment/pesepay', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

        //data
        if (isset($this->request->post['payment_pesepay_status'])) {
            $data['payment_pesepay_status'] = $this->request->post['payment_pesepay_status'];
        } else {
            $data['payment_pesepay_status'] = $this->config->get('payment_pesepay_status');
        }

        if (isset($this->request->post['payment_pesepay_title'])) {
            $data['payment_pesepay_title'] = $this->request->post['payment_pesepay_title'];
        } else {
            $data['payment_pesepay_title'] = $this->config->get('payment_pesepay_title');
        }

        if (isset($this->request->post['payment_pesepay_logo'])) {
            $data['payment_pesepay_logo'] = $this->request->post['payment_pesepay_logo'];
        } else {
            $data['payment_pesepay_logo'] = $this->config->get('payment_pesepay_logo');
        }

        if (isset($this->request->post['payment_pesepay_total'])) {
            $data['payment_pesepay_total'] = $this->request->post['payment_pesepay_total'];
        } else {
            $data['payment_pesepay_total'] = $this->config->get('payment_pesepay_total');
        }

        if (isset($this->request->post['payment_pesepay_sort_order'])) {
            $data['payment_pesepay_sort_order'] = $this->request->post['payment_pesepay_sort_order'];
        } else {
            $data['payment_pesepay_sort_order'] = $this->config->get('payment_pesepay_sort_order');
        }

        if (isset($this->request->post['payment_pesepay_encryption_key'])) {
            $data['payment_pesepay_encryption_key'] = $this->request->post['payment_pesepay_encryption_key'];
        } else {
            $data['payment_pesepay_encryption_key'] = $this->config->get('payment_pesepay_encryption_key');
        }

        if (isset($this->request->post['payment_pesepay_integration_key'])) {
            $data['payment_pesepay_integration_key'] = $this->request->post['payment_pesepay_integration_key'];
        } else {
            $data['payment_pesepay_integration_key'] = $this->config->get('payment_pesepay_integration_key');
        }

        if (isset($this->request->post['payment_pesepay_order_status'])) {
            $data['payment_pesepay_order_status'] = $this->request->post['payment_pesepay_order_status'];
        } else {
            $data['payment_pesepay_order_status'] = $this->config->get('payment_pesepay_order_status');
        }

        if (isset($this->request->post['payment_pesepay_currency'])) {
            $data['payment_pesepay_currency'] = $this->request->post['payment_pesepay_currency'];
        } else {
            $data['payment_pesepay_currency'] = $this->config->get('payment_pesepay_currency');
        }

        #Additional data
        $this->load->model('localisation/order_status');
        $this->load->model('localisation/geo_zone');

        $data['payment_pesepay_order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
        $data['payment_pesepay_geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
        $data['payment_pesepay_currencies'] = $this->model_extension_payment_pesepay->getAvailableCurrencies();

        $data['payment_pesepay_encryption_key_length'] = $this->KEY_LENGTH[1];

        //content
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/pesepay', $data));
    }

    /**
     * 
     * This function is called to ensure that the settings chosen by the admin user are allowed/valid.
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return bool
     */
    private function validate_post_data()
    {
        if (!$this->user->hasPermission('modify', 'extension/payment/pesepay')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        $encryption = $this->request->post['payment_pesepay_encryption_key'];

        if (!$encryption) {
            $this->error['error_pesepay_encryption_key'] = $this->language->get('error_encryption_key');
        }

        #key length not documented
        if (!in_array(strlen($encryption), $this->KEY_LENGTH)) {
            $this->error['error_pesepay_encryption_key'] = $this->language->get('error_encryption_key_length');
        }

        if (!isset($this->request->post['payment_pesepay_integration_key']) || !$this->request->post['payment_pesepay_integration_key']) {
            $this->error['error_pesepay_integration_key'] = $this->language->get('error_integration_key');
        }

        if (!isset($this->request->post['payment_pesepay_currency']) || !$this->request->post['payment_pesepay_currency']) {
            $this->error['error_pesepay_currency'] = $this->language->get('error_currency');
        }

        return !$this->error;
    }
}