<?php

define('MODX_API_MODE', true);
require dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/index.php';

$modx->getService('error', 'error.modError');
$modx->setLogLevel(modX::LOG_LEVEL_ERROR);
$modx->setLogTarget('FILE');

$miniShop2 = $modx->getService('minishop2');
$miniShop2->loadCustomClasses('payment');

if (!class_exists('Paykeeper')) {
    exit('Error: could not load payment class "Paykeeper".');
}
$context = '';
$params = array();

$handler = new Paykeeper($modx->newObject('msOrder'));

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $POST = (isset($_POST) and ! empty($_POST)) ? $_POST : false;

    if ($order = $modx->getObject('msOrder', (int) $POST['orderid'])) {
        $handler->callback($order);
    }
}
die;
