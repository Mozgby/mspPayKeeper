<?php

/*
  =====================================================
  # Author: Michael Kochkin
  -----------------------------------------------------
  # http:
  -----------------------------------------------------
  # Copyright (c) 2021 PayKeeper
  =====================================================
 */

if (!class_exists('msPaymentInterface')) {
    require_once dirname(dirname(dirname(__FILE__))) . '/model/minishop2/mspaymenthandler.class.php';
}
require_once dirname(__FILE__) . '/paykeeper.common.php';

class Paykeeper extends msPaymentHandler implements msPaymentInterface {

    private $fiscal_cart = array(); //fz54 cart
    private $order_total = 0; //order total sum
    private $shipping_price = 0; //shipping price
    private $use_delivery = false;
    private $order_params = NULL;

    public $config;
    public $modx;

    function __construct(xPDOObject $object, $config = array()) {
        $this->modx = & $object->xpdo;
        $siteUrl = $this->modx->getOption('site_url');
        $assetsUrl = $this->modx->getOption('minishop2.assets_url', $config, $this->modx->getOption('assets_url') . 'components/minishop2/');
        $paymentUrl = $siteUrl . substr($assetsUrl, 1) . 'payment/paykeeper.php';

        if (isset($_POST['card'])) {
            $payId = $_POST['card'];
            $paySystemId = $this->modx->getOption('ms2_payment_paykeeper_paySystemId');
            preg_match_all("#([^,\s]+):([^,\s]+)#s", $paySystemId, $arrPay);
            unset($arrPay[0]);
            $arrPay = array_combine($arrPay[1], $arrPay[2]);
            foreach ($arrPay as $key => $value) {
                if ($key == $payId) {
                    $payId = $value;
                    break;
                }
            }
        }

        $this->config = array_merge(array(
            'paykeeper_status' => '',
            'paykeeper_order_sort' => '',
            'paykeeper_order_status_id' => '',
            'paykeeper_secret_key' => $this->modx->getOption('ms2_payment_paykeeper_secret_key'),
            'paykeeper_server_url' => $this->modx->getOption('ms2_payment_paykeeper_server_url'),
            //'paykeeper_success_id' => $this->modx->makeUrl($this->modx->getOption('ms2_payment_paykeeper_success_id', null, 0), $context, $params, 'full'),
            //'paykeeper_failure_id' => $this->modx->makeUrl($this->modx->getOption('ms2_payment_paykeeper_failure_id', null, 0), $context, $params, 'full'),
            'paykeeper_tax_id' => '',
            'paykeeper_tax_delivery' => $this->modx->getOption('ms2_payment_paykeeper_tax_delivery'),
            'paykeeper_tax_product' => $this->modx->getOption('ms2_payment_paykeeper_tax_product'),
            'paykeeper_force_discounts_check' => $this->modx->getOption('ms2_payment_paykeeper_force_discounts_check'),
            'paymentUrl' => $paymentUrl,
            'systemId' => $payId,
        ), $config);
    }

    public function send(msOrder $order) {

        $this->setOrder($order);
        $link = $this->setLink($order);
        return $this->success('', array('redirect' => $link));
    }

    private function setOrder(msOrder $order) {

        $order_id = $order->get('id');
        $order_total = floatval($order->get('cost'));

        $user = $this->modx->getObject('msOrderAddress', $order_id);

        $user_id = $order->user_id;
        $objProfile = $this->modx->getObject('modUserProfile', $user_id);
        $client_email = $objProfile->get('email');

        $this->order = array(
            'order' => $order,
            'clientid' => $user->receiver,
            'orderid' => $order_id,
            'service_name' => '',
            'order_total' => $order_total,
            'client_id' => $user->user_id,
            'client_email' => $client_email,
            'client_phone' => $user->phone,
        );
    }

    private function isDelivery(msOrder $order) {
        $delivery = $this->modx->getObject('msDelivery', $order->get('delivery'));
        if (count($delivery->toArray()) > 0) {
            return $delivery;
        } else {
            return false;
        }
    }

    public function callback(msOrder $order) {

        $POST = (isset($_POST) and ! empty($_POST)) ? $_POST : false;

        if (!$POST)
            die('NO POST DATA');

        $sign = md5($POST['id']
            . $POST['sum']
            . $POST['clientid']
            . $POST['orderid']
            . $this->config['paykeeper_secret_key']
        );
        if ($POST["sum"] != $order->get("cost"))
            die("Order sum mismatch!");

        if ($sign != $POST['key'])
            die('HASH MISMATCH');

        $miniShop2 = $this->modx->getService('miniShop2');
        @$this->modx->context->key = 'mgr';
        $miniShop2->changeOrderStatus($order->get('id'), 2);

        echo "OK " . md5($POST['id'] . $this->config['paykeeper_secret_key']);
    }

    private function modxError($text, $request = array()) {
        $this->modx->log(modX::LOG_LEVEL_ERROR, '[miniShop2:Paykeeper] ' . $text . ', request: ' . print_r($request, 1));
        header("HTTP/1.0 400 Bad Request");
        die('ERR: ' . $text);
    }

    private function setLink(msOrder $order) {


        //GENERATING PAYKEEPER PAYMENT FORM
        $pk_obj = new PaykeeperPayment();

        //set order params
        $pk_obj->setOrderParams(
        //sum
            $this->order['order_total'],
            //clientid
            $this->order['clientid'],
            //orderid
            $this->order['orderid'],
            //client_email
            $this->order['client_email'],
            //client_phone
            $this->order['client_phone'],
            //service_name
            $this->order['service_name'],
            //payment form url
            $this->config['paykeeper_server_url'],
            //secret key
            $this->config['paykeeper_secret_key']
        );

        //GENERATE FZ54 CART

        $cart_data = $this->modx->getCollection('msOrderProduct', array('order_id' => $this->order['orderid']));
        foreach ($cart_data as $product_k => $product_v) {
            $taxes = array("tax" => "none", "tax_sum" => 0);
            $product_id = $this->modx->getObject('msProduct', $product_v->product_id);
            $name = strval($product_id->pagetitle);
            $price = floatval($product_v->price);
            $quantity = floatval($product_v->count);
            $sum = $price*$quantity;
            $tax_rate = intval($this->config['paykeeper_tax_product']);
            $taxes = $pk_obj->setTaxes($tax_rate, false);
            $pk_obj->updateFiscalCart($pk_obj->getPaymentFormType(),
                $name, $price, $quantity, $sum, $taxes["tax"]);
        }

        //add shipping parameters to cart
        if (($delivery = $this->isDelivery($order)) != false) {
            $shipping_taxes = array("tax" => "none", "tax_sum" => 0);
            $pk_obj->setShippingPrice(floatval($order->delivery_cost));
            $shipping_name = $delivery->name;
            $shipping_tax_class_id = $shipping_method['quote'][$shipping_code]['tax_class_id'];

            if (!$pk_obj->checkDeliveryIncluded($pk_obj->getShippingPrice(), $shipping_name)
                && $pk_obj->getShippingPrice() > 0) {
                $shipping_tax_rate = intval($this->config['paykeeper_tax_product']);
                $shipping_taxes = $pk_obj->setTaxes($shipping_tax_rate, false);
                $pk_obj->setUseDelivery(); //for precision correct check
                $pk_obj->updateFiscalCart($pk_obj->getPaymentFormType(), $shipping_name,
                    $pk_obj->getShippingPrice(), 1, $pk_obj->getShippingPrice(), $shipping_taxes["tax"]);
                $pk_obj->delivery_index = count($pk_obj->getFiscalCart())-1;
            }
        }



        //set discounts
        $pk_obj->setDiscounts($this->config['paykeeper_force_discounts_check'] == "1");

        //handle possible precision problem
        $pk_obj->correctPrecision();

        $fiscal_cart_encoded = json_encode($pk_obj->getFiscalCart());

        $to_hash = number_format($pk_obj->getOrderTotal(), 2, ".", "") .
            $pk_obj->getOrderParams("clientid")     .
            $pk_obj->getOrderParams("orderid")      .
            $pk_obj->getOrderParams("service_name") .
            $pk_obj->getOrderParams("client_email") .
            $pk_obj->getOrderParams("client_phone") .
            $pk_obj->getOrderParams("secret_key");
        $sign = hash ('sha256' , $to_hash);

        $request = array(
            'sum' => $pk_obj->getOrderTotal(),
            'clientid' => $pk_obj->getOrderParams("clientid"),
            'orderid' => $pk_obj->getOrderParams("orderid"),
            'client_email' => $pk_obj->getOrderParams("client_email"),
            'client_phone' => $pk_obj->getOrderParams("client_phone"),
            'service_name' => $pk_obj->getOrderParams("service_name"),
            'sign' => $sign,
            'cart' => $fiscal_cart_encoded,
            'server' => $pk_obj->getOrderParams("form_url"),
            'payment_form_type' => $pk_obj->getPaymentFormType()
        );

        return 'manager/templates/paykeeper.php/?' . http_build_query($request);
    }
}
