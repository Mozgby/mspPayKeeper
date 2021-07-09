<?php

//generate payment form
$form = "";
if ($_GET["payment_form_type"] == "create") { //create form
    $form = '
        <h3>Сейчас Вы будете перенаправлены на страницу банка.</h3> 
        <form name="payment" id="pay_form" action="'.$_GET["server"].'" accept-charset="utf-8" method="post">
        <input type="hidden" name="sum" value = "'.$_GET["sum"].'"/>
        <input type="hidden" name="orderid" value = "'.$_GET["orderid"].'"/>
        <input type="hidden" name="clientid" value = "'.$_GET["clientid"].'"/>
        <input type="hidden" name="client_email" value = "'.$_GET["client_email"].'"/>
        <input type="hidden" name="client_phone" value = "'.$_GET["client_phone"].'"/>
        <input type="hidden" name="service_name" value = "'.$_GET["service_name"].'"/>
        <input type="hidden" name="cart" value = \''.htmlentities($_GET["cart"],ENT_QUOTES).'\' />
        <input type="hidden" name="sign" value = "'.$_GET["sign"].'"/>
        <input type="submit" id="button-confirm" value="Оплатить"/>
        </form>
        <!--
        <script type="text/javascript">                                                                                         
        window.onload=function(){
            setTimeout(fSubmit, 2000);
        }
        function fSubmit() {
            document.forms["pay_form"].submit();
        }
        </script>-->';
}
else { //order form
    $payment_parameters = array(
        "clientid"=>$_GET["clientid"],
        "orderid"=>$_GET["orderid"],
        "sum"=>$_GET["sum"],
        "phone"=>$_GET["client_phone"],
        "client_email"=>$_GET["client_email"],
        "cart"=>$_GET["cart"]
    );
    $query = http_build_query($payment_parameters);
    $query_options = array("http"=>array(
        "method"=>"POST",
        "header"=>"Content-type: application/x-www-form-urlencoded",
        "content"=>$query
    ));
    $context = stream_context_create($query_options);

    $err_num = $err_text = NULL;
    if( function_exists( "curl_init" )) { //using curl
        $CR = curl_init();
        curl_setopt($CR, CURLOPT_URL, $_GET["server"]);
        curl_setopt($CR, CURLOPT_POST, 1);
        curl_setopt($CR, CURLOPT_FAILONERROR, true);
        curl_setopt($CR, CURLOPT_POSTFIELDS, $query);
        curl_setopt($CR, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($CR, CURLOPT_SSL_VERIFYPEER, 0);
        $result = curl_exec( $CR );
        $error = curl_error( $CR );
        if( !empty( $error )) {
            $form = "<br/><span class=message>"."INTERNAL ERROR:".$error."</span>";
            return false;
        }
        else {
            $form = $result;
        }
        curl_close($CR);
    }
    else { //using file_get_contents
        if (!ini_get('allow_url_fopen')) {
            $form_html = "<br/><span class=message>"."INTERNAL ERROR: Option allow_url_fopen is not set in php.ini"."</span>";
        }
        else {
            $form = file_get_contents($server, false, $context);
        }
    }
}
if ($form  == "") {
    $form = '<h3>Произошла ошибка при инциализации платежа! Невозможно отобразить форму оплаты!</h3>';
}

echo $form;
