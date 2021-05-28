<?php

$_lang['setting_ms2_payment_paykeeper_server_url'] = 'Адрес формы оплаты';
$_lang['setting_ms2_payment_paykeeper_server_url_desc'] = 'Адрес для отправки запросов на удалённый сервис PayKeeper';

$_lang['setting_ms2_payment_paykeeper_secret_key'] = 'Секретное слово';
$_lang['setting_ms2_payment_paykeeper_secret_key_desc'] = 'Секретное слово, которое вы установили в настройках личного кабинета PayKeeper.';

$_lang['setting_ms2_payment_paykeeper_success_id'] = 'Страница успешной оплаты';
$_lang['setting_ms2_payment_paykeeper_success_id_desc'] = 'Пользователь будет отправлен на эту страницу после завершения оплаты. Рекомендуется указать id страницы с информацией, что заказ успешно оплачен.';

$_lang['setting_ms2_payment_paykeeper_failure_id'] = 'Страница неуспешной оплаты';
$_lang['setting_ms2_payment_paykeeper_failure_id_desc'] = 'Пользователь будет отправлен на эту страницу при неудачной оплате. Рекомендуется указать id страницы с информацией, что оплата не была произведена или во время оплаты возникла ошибка';

$_lang['setting_ms2_payment_paykeeper_tax_product'] = 'Ставка НДС для всех товаров каталога';
$_lang['setting_ms2_payment_paykeeper_tax_product_desc'] = 'Используемая ставка НДС для продуктов магазина. <br/>Ставка НДС, допустимые значения: <br/><b>20</b> - НДС 20%<br/><b>10</b> - НДС 10%<br/><b>120</b> - НДС по формуле 20/120<br/><b>110</b> - НДС по формуле 10/110<br/><b>0</b> - НДС 0%<br/><b>none</b> - НДС не облагается';

$_lang['setting_ms2_payment_paykeeper_tax_delivery'] = 'Общая ставка НДС для всех доставок';
$_lang['setting_ms2_payment_paykeeper_tax_delivery_desc'] = 'Используемая ставка НДС для всех способов доставки магазина, если не отмечен параметр «Использование индивидуальных названий способов доставки». <br/>Ставка НДС, допустимые значения: <br/><b>20</b> - НДС 20%<br/><b>10</b> - НДС 10%<br/><b>120</b> - НДС по формуле 20/120<br/><b>110</b> - НДС по формуле 10/110<br/><b>0</b> - НДС 0%<br/><b>none</b> - НДС не облагается';

$_lang['setting_ms2_payment_paykeeper_force_discounts_check'] = 'Force discounts check';
$_lang['setting_ms2_payment_paykeeper_force_discounts_check_desc'] = 'If option is enabled, discounts will be checked anyway. Please, report about this option to support@paykeeper.ru';
