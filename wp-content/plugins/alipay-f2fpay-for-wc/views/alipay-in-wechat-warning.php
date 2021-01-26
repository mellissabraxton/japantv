<?php
define('WP_USE_THEMES', false);
require_once('../../../../wp-load.php');

$api = XHALIF2FWC::instance();
$order_id = isset($_REQUEST['id'])?absint($_REQUEST['id']):0;
$wc_order = wc_get_order($order_id);
if(!$wc_order){
    wp_redirect(wc_get_checkout_url());exit;
}
if(!$api->is_wechat_client()){
   wp_redirect($wc_order->get_checkout_payment_url(true));exit;
}
?>
<html>
<head>
<meta http-equiv="content-type" content="text/html;charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>支付宝</title>
</head>
<body style="padding:0;margin:0;">
<?php 
if($api->is_ios()){
    ?>
	<img alt="支付宝" src="<?php echo XH_AL_F2F_URL?>/images/ios.png" style="max-width: 100%;">
	<?php 
}else{
	?>
	<img alt="支付宝" src="<?php echo XH_AL_F2F_URL?>/images/android.jpg" style="max-width: 100%;">
	<?php 
}
?>
</body>
</html>