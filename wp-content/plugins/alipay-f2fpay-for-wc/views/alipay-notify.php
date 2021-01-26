<?php
define('WP_USE_THEMES', false);
require_once('../../../../wp-load.php');

$request =stripslashes_deep($_POST) ;
 
$out_trade_no = $request['out_trade_no'];
$transaction_id = $request['trade_no'];
$trade_status = $request['trade_status'];
$order = wc_get_order($out_trade_no);
if(!$order){
    //可能是其他支付方式的回调
    echo 'failed';exit;
}

$api = XHALIF2FWC::instance();
$sign = $request["sign"];
$notify_id = $request['notify_id'];
$partner = $api->get_option('appid');
$public_key  = $api->get_option('public_key');

if(!$api->validate_sign($request, $public_key)){
    echo 'failed';exit;
}
 
try {
    if ($trade_status == 'TRADE_FINISHED' || $trade_status == 'TRADE_SUCCESS') {
        $order->payment_complete($transaction_id);
        
    }
} catch (Exception $e) {
    echo 'faild';
    exit;
}

echo 'success';
exit;