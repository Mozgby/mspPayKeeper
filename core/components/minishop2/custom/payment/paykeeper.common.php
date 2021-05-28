<?php

class PaykeeperPayment {

    public $fiscal_cart = array(); //fz54 cart
    public $order_total = 0; //order total sum
    public $shipping_price = 0; //shipping price
    public $use_taxes = false;
    public $use_delivery = false;
    public $delivery_index = -1;
    public $single_item_index = -1;
    public $more_then_one_item_index = -1;
    public $order_params = NULL;
    public $discounts = array();

    public function setOrderParams($order_total = 0, $clientid="", $orderid="", $client_email="",
                                   $client_phone="", $service_name="", $form_url="", $secret_key="")
    {
        $this->setOrderTotal($order_total);
        $this->order_params = array(
            "sum" => $order_total,
            "clientid" => $clientid,
            "orderid" => $orderid,
            "client_email" => $client_email,
            "client_phone" => $client_phone,
            "phone" => $client_phone,
            "service_name" => $service_name,
            "form_url" => $form_url,
            "secret_key" => $secret_key,
        );
    }

    public function getOrderParams($value)
    {
        return array_key_exists($value, $this->order_params) ? $this->order_params["$value"] : False;
    }

    public function updateFiscalCart($ftype, $name="", $price=0, $quantity=0, $sum=0, $tax="none")
    {
        //update fz54 cart
        if ($ftype === "create") {
            $name = str_replace("\n ", "", $name);
            $name = str_replace("\r ", "", $name);
        }
        $this->fiscal_cart[] = array(
            "name" => $name,
            "price" => number_format($price, 2, ".", ""),
            "quantity" => $quantity,
            "sum" => number_format($sum, 2, ".", ""),
            "tax" => $tax
        );
    }

    public function getFiscalCart()
    {
        return $this->fiscal_cart;
    }

    public function setDiscounts($discount_enabled_flag)
    {
        $discount_modifier_value = 1;
        $shipping_included = false;
        //set discounts
        if ($discount_enabled_flag) {
            if ($this->getFiscalCartSum(false) > 0) {
                if ($this->getOrderTotal() >= $this->getShippingPrice()) {
                    $discount_modifier_value = ($this->getOrderTotal() - $this->getShippingPrice())/$this->getFiscalCartSum(false);
                }
                else {
                    $discount_modifier_value = $this->getOrderTotal()/$this->getFiscalCartSum(true);
                    $shipping_included = true;
                }

                if ($discount_modifier_value < 1) {
                    for ($pos=0; $pos<count($this->getFiscalCart()); $pos++) {//iterate fiscal cart with or without shipping
                        if (!$shipping_included && $pos == $this->delivery_index) {
                            continue;
                        }
                        $price = $this->fiscal_cart[$pos]["sum"]/$this->fiscal_cart[$pos]["quantity"];
                        $this->fiscal_cart[$pos]["price"] = number_format($price, 2, ".", "");
                        $sum = $this->fiscal_cart[$pos]["sum"] * $discount_modifier_value;
                        $this->fiscal_cart[$pos]["sum"] = number_format($sum, 2, ".", "");
                    }
                }
            }
        }
    }

    public function correctPrecision()
    {
        //handle possible precision problem
        $fiscal_cart_sum = $this->getFiscalCartSum(true, true);
        $total_sum = $this->getOrderTotal();
        $diff_value = $total_sum - $fiscal_cart_sum;
        //debug_info
        //echo "\ntotal: $total_sum - cart: $fiscal_cart_sum - diff: $diff_sum";
        if (abs($diff_value) >= 0.005) {
            $diff_sum = number_format($diff_value, 2, ".", "");
            if ($this->getUseDelivery()) { //delivery is used
                $this->correctPriceOfCartItem($diff_sum, count($this->fiscal_cart)-1);
            }
            else {
                if ($this->single_item_index >= 0) { //we got single cart element
                    $this->correctPriceOfCartItem($diff_sum, $this->single_item_index);
                }
                else if ($this->more_then_one_item_index >= 0) { //we got cart element with more then one quantity
                    $this->splitCartItem($this->more_then_one_item_index);
                    //add diff_sum to the last element (just separated) of fiscal cart
                    $this->correctPriceOfCartItem($diff_sum, count($this->fiscal_cart)-1);
                }
                else { //we only got cart elements with less than one quantity
                    $modify_value = ($diff_sum > 0) ? $total_sum/$fiscal_cart_sum : $fiscal_cart_sum/$total_sum;
                    for ($pos=0; $pos<count($this->getFiscalCart()); $pos++) {
                        $sum = $this->fiscal_cart[$pos]["sum"]*$modify_value;
                        $this->fiscal_cart[$pos]["sum"] *= number_format($sum, 2, ".", "");
                        $price = $this->fiscal_cart[$pos]["sum"]/$this->fiscal_cart[$pos]["quantity"];
                        $this->fiscal_cart[$pos]["price"] = number_format($price, 2, ".", "");
                    }
                }
            }
        }
    }

    public function setOrderTotal($value)
    {
        $this->order_total = $value;
    }

    public function getOrderTotal()
    {
        return $this->order_total;
    }

    public function setShippingPrice($value)
    {
        $this->shipping_price = $value;
    }

    public function getShippingPrice()
    {
        return $this->shipping_price;
    }

    public function getPaymentFormType()
    {
        if (strpos($this->order_params["form_url"], "/order/inline") == True)
            return "order";
        else
            return "create";
    }

    public function setUseTaxes()
    {
        $this->use_taxes = True;
    }

    public function getUseTaxes()
    {
        return $this->use_taxes;
    }

    public function setUseDelivery()
    {
        $this->use_delivery = True;
    }

    public function getUseDelivery()
    {
        return $this->use_delivery;
    }

    //$zero_value_as_none: if variable is set, then when tax_rate is zero, tax is equal to none
    public function setTaxes($tax_rate, $zero_value_as_none = true)
    {
        $taxes = array("tax" => "none", "tax_sum" => 0);
        switch(number_format(floatval($tax_rate), 0, ".", "")) {
            case 0:
                if (!$zero_value_as_none) {
                    $taxes["tax"] = "vat0";
                }
                break;
            case 10:
                $taxes["tax"] = "vat10";
                break;
            case 18:
                $taxes["tax"] = "vat18";
                break;
            case 20:
                $taxes["tax"] = "vat20";
                break;
        }
        return $taxes;
    }

    public function checkDeliveryIncluded($delivery_price, $delivery_name) {
        $index = 0;
        foreach ($this->getFiscalCart() as $item) {
            if ($item["name"] == $delivery_name
                && $item["price"] == $delivery_price
                && $item["quantity"] == 1) {
                $this->delivery_index = $index;
                return true;
            }
            $index++;
        }
        return false;
    }

    public function getFiscalCartSum($delivery_included) {
        $fiscal_cart_sum = 0;
        $index = 0;
        foreach ($this->getFiscalCart() as $item) {
            if (!$delivery_included && $index == $this->delivery_index)
                continue;
            $fiscal_cart_sum += $item["price"]*$item["quantity"];
            $index++;
        }
        return number_format($fiscal_cart_sum, 2, ".", "");
    }

    public function showDebugInfo($obj_to_debug)
    {
        echo "<pre>";
        var_dump($obj_to_debug);
        echo "</pre>";
    }

    public function correctPriceOfCartItem($corr_price_to_add, $item_position)
    {
        $item_sum = 0;
        $this->fiscal_cart[$item_position]["price"] += $corr_price_to_add;
        $item_sum = $this->fiscal_cart[$item_position]["price"]*$this->fiscal_cart[$item_position]["quantity"];
        $this->fiscal_cart[$item_position]["sum"] = $item_sum;
    }

    public function splitCartItem($cart_item_position)
    {
        $item_sum = 0;
        $item_price = 0;
        $item_quantity = 0;
        $item_price = $this->fiscal_cart[$cart_item_position]["price"];
        $item_quantity = $this->fiscal_cart[$cart_item_position]["quantity"]-1;
        $this->fiscal_cart[$cart_item_position]["quantity"] = $item_quantity; //decreese quantity by one
        $this->fiscal_cart[$cart_item_position]["sum"] = $item_price*$item_quantity; //new sum
        //add one cart item to the end of cart
        $this->updateFiscalCart(
            $this->getPaymentFormType(),
            $this->fiscal_cart[$cart_item_position]["name"],
            $item_price, 1, $item_price,
            $this->fiscal_cart[$cart_item_position]["tax"]);
    }
}
