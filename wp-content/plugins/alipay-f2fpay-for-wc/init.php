<?php
/*
 * Plugin Name:支付宝 - 当面付  For WooCommerce
 * Plugin URI: http://www.wpweixin.net
 * Description:支付宝 - 当面付 ，支持扫码支付，wap支付
 * Version: 1.0.2
 * Author: 迅虎网络 
 * Author URI:http://www.wpweixin.net 
 * Text Domain: Woocommerce 支付宝  当面付
 * WC tested up to: 3.4.3
 */
if (! defined ( 'ABSPATH' )) exit (); // Exit if accessed directly

if (! defined ( 'XH_AL_F2F' )) define ( 'XH_AL_F2F', 'XH_AL_F2F' ); else return;

define('XH_AL_F2F_FILE',__FILE__);
define('XH_AL_F2F_VERSION','1.0.2');
define('XH_AL_F2F_DIR',rtrim(plugin_dir_path(XH_AL_F2F_FILE),'/'));
define('XH_AL_F2F_URL',rtrim(plugin_dir_url(XH_AL_F2F_FILE),'/'));

require_once 'class-xh-alipayf2f-api.php';
$api = new XH_Alipay_F2F_Api();