<?php 
define('WP_USE_THEMES', false);
require_once('../../../../wp-load.php');

$order_id = isset($_REQUEST['id'])?absint($_REQUEST['id']):0;
$wc_order = wc_get_order($order_id);
if(!$wc_order){
    wp_redirect(wc_get_checkout_url());
    exit;
}

if(strripos(strtolower($_SERVER['HTTP_USER_AGENT']),'qq')===false){
   wp_redirect($wc_order->get_checkout_payment_url(true));
   exit;
}

if ( ! $guessurl = site_url() ){
    $guessurl = wp_guess_url();
}
?>
<html>
<head>
<meta http-equiv="content-type" content="text/html;charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>支付宝</title>
<script src="https://open.mobile.qq.com/sdk/qqapi.js?_bid=152"></script>
 <script src="<?php echo $guessurl.'/wp-includes/js/jquery/jquery.js'; ?>"></script>
<script type="text/javascript">
jQuery(function($){
	if(mqq&& mqq.QQVersion!='0'){
		var html='';
		if(mqq.android){
			html='<img alt="支付宝" src="<?php print XH_AL_F2F_URL?>/images/android.jpg" style="max-width: 100%;">';
		}else{
			html='<img alt="支付宝" src="<?php print XH_AL_F2F_URL?>/images/ios.png" style="max-width: 100%;">';
		}
		$('#wp-content').html(html);
	}else{
		location.href='<?php echo $wc_order->get_checkout_payment_url(true)?>';
	}
});
</script>
</head>
<body style="padding:0;margin:0;" id="wp-content">
</body>
</html>