<?php
/**
 * Loads system settings into build
 * @var modX $modx
 * @package msppaykeeper
 * @subpackage build
 */
$settings = [];

$tmp = [
    'server_url' => [
        'xtype' => 'textfield',
        'value' => '',
    ],
    'secret_key' => [
        'xtype' => 'textfield',
        'value' => '',
    ],
    'tax_delivery' => [
        'xtype' => 'textfield',
        'value' => 'none',
    ],
    'tax_product' => [
        'xtype' => 'textfield',
        'value' => 'none',
    ],
    'force_discounts_check' => [
        'xtype' => 'textfield',
        'value' => '0',
    ],

];

foreach ($tmp as $k => $v) {
    /* @var modSystemSetting $setting */
    $setting = $modx->newObject('modSystemSetting');
    $setting->fromArray(array_merge(
        [
            'key' => 'ms2_payment_paykeeper_' . $k,
            'namespace' => 'minishop2',
            'area' => 'ms2_payment',
        ], $v
    ), '', true, true);

    $settings[] = $setting;
}

unset($tmp);
return $settings;