<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once 'abstract-alipayf2f-payment-gateway.php';

class XHALIF2FWC extends Abstract_XH_Alipay_F2F_Payment_Gateway{
	private static $_instance;
	/**
	 * @return XHALIF2FWC
	 */
	public static function instance(){
	    if(!self::$_instance){
	        self::$_instance = new self();
	    }
	    
	    return self::$_instance;
	}
	
	protected function __construct(){
		$this->id = strtolower(get_called_class());
		$this->supports[]='refunds';
		$this->icon = XH_AL_F2F_URL. '/images/alipay.png';
	
		$this->method_title='支付宝 - 当面付';
		$this->method_description='支付宝 - 当面付，支持支付宝扫码支付、移动端支付';
		
		$this->init_form_fields ();
		$this->init_settings ();
		
		$this->title = $this->get_option ( 'title' );
		$this->description = $this->get_option ( 'description' );
		$this->instructions  = $this->get_option( 'instructions');	
	}
	
	function init_form_fields() {
		$this->form_fields = array (
				'enabled' => array (
						'title' => __('Enable/Disable','woocommerce'),
						'type' => 'checkbox',
						'default' => 'yes',
						'section'=>'default'
				),
				'title' => array (
						'title' => __('Title','woocommerce'),
						'type' => 'text',
						'default' =>  __('Alipay',XH_AL_F2F),
						'desc_tip' => true,
						'css' => 'width:400px',
						'section'=>'default'
				),
				'description' => array (
						'title' => __('Description','woocommerce'),
						'type' => 'textarea',
						'desc_tip' => true,
						'css' => 'width:400px',
						'section'=>'default'
				),
				'instructions' => array(
					'title'       => __( 'Instructions', 'woocommerce' ),
					'type'        => 'textarea',
					'css' => 'width:400px',
					'description' => __( 'Instructions that will be added to the thank you page.', 'woocommerce' ),
					'default'     => '',
					'section'=>'default'
				),
                'appid' => array (
                    'title' => __ ( 'APP ID', XH_AL_F2F ),
                    'type' => 'text',
                    'description' => '<a href="https://www.wpweixin.net/blog/2140.html" target="_blank">帮助文档</a>',
                    'required' => true,
                    'css' => 'width:400px',
                    'description'=>'此处填写的是应用私钥',
                ),
                'private_key' => array (
                    'title' => __ ( 'Private.key', XH_AL_F2F ),
                    'type' => 'textarea',
                    'css' => 'width:400px',
                    'required' => true,
                    'desc_tip' => false
                ),
                'public_key' => array (
                    'title' => __ ( 'Public.key', XH_AL_F2F ),
                    'type' => 'textarea',
                    'css' => 'width:400px',
                    'description'=>'此处填写的是<span style="color:red;">支付宝公钥</span>(非应用公钥)',
                    'required' => true,
                    'desc_tip' => false
                ),
				'prefix' => array (
						'title' => __('Order ID Prefix',XH_AL_F2F),
						'type' => 'text',
						'css' => 'width:400px',
						'default'=>'ali_',
				),
				'exchange_rate' => array (
						'title' => '汇率',
						'type' => 'text',
						'css' => 'width:400px',
						'default'=>'1',
				        'description'=>'设置转换成人民币的汇率，默认为1（如果当前货币为美元，那么此处应填6.8）'
				)
			);
	}
	
	public function process_payment($order_id){
		$wc_order = wc_get_order($order_id);
		if(!$wc_order){
		    return array(
		        'result'   => 'success',
		        'redirect' => wc_get_checkout_url()
		    );
		}
		if($wc_order->is_paid()){
			return array(
	             'result'   => 'success',
	             'redirect' => $this->get_return_url($wc_order)
	         );
		}
		
		if($this->is_wechat_client()){
		    return array(
		        'result'   => 'success',
		        'redirect' =>XH_AL_F2F_URL.'/views/alipay-in-wechat-warning.php?id='.$wc_order->get_id()
		    );
		}
		
		if(strripos(strtolower($_SERVER['HTTP_USER_AGENT']),'qq')!==false){
		    return array(
		        'result'   => 'success',
		        'redirect' =>XH_AL_F2F_URL.'/views/alipay-in-qq-warning.php?id='.$wc_order->get_id()
		    );
		}
		
		return array(
		    'result'   => 'success',
		    'redirect' =>$wc_order->get_checkout_payment_url(true)
		);
	}

	public function woocommerce_receipt($order_id){
	    $wc_order = wc_get_order($order_id);
	    if(!$wc_order){
	        ?>
	        <script type="text/javascript">
					location.href='<?php echo wc_get_checkout_url();?>';
				</script>
	        <?php 
	        return;
	    }
	    if($wc_order->is_paid()){
	        ?>
	           <script type="text/javascript">
					location.href='<?php echo $this->get_return_url($wc_order);?>';
				</script>
	        <?php 
	        return;
	    }
	    
	    $api = $this;
	    $appid =$api->get_option('appid');
	    $private_key =$api->get_option('private_key');
	    
	    $exchange_rate = round(floatval($api->get_option('exchange_rate')),3);
	    if($exchange_rate<=0){
	        $exchange_rate = 1;
	    }
	    
	    $parameter = array (
	        'app_id' =>$appid,
	        'method'=>'alipay.trade.precreate',
	        'charset'=>'utf-8',
	        'sign_type'=>'RSA2',
	        'timestamp'=>date_i18n('Y-m-d H:i:s'),
	        'version'=>'1.0',
	        'notify_url'=>XH_AL_F2F_URL.'/views/alipay-notify.php',
	        'biz_content'=>json_encode(array(
	            'out_trade_no'=>$wc_order->get_id(),
	            'total_amount'=>round($wc_order->get_total()*$exchange_rate,2),
	            'subject'=> $this->get_order_title($wc_order)
	        ))
	    );
	    
	    try {
    	    $parameter['sign'] = $this->generate_sign($parameter,$private_key);
            $response = $this->http_post('https://openapi.alipay.com/gateway.do',$parameter,false,null,true);
            $response = iconv("GB2312","UTF-8",$response);
            $response = json_decode($response,true);
            if(!$response||!is_array($response)){
                $response=array();
            }
            
            if(!isset($response['alipay_trade_precreate_response']['code'])||$response['alipay_trade_precreate_response']['code']!=10000){
                throw new Exception(print_r($response,true));
            }
	       
	        $qrUrl = $response['alipay_trade_precreate_response']['qr_code'];
	        if($this->is_app_client()){
	            ?>
	           <div id="btn-refresh" style="display:none;"><a href="">已支付？点击此处刷新！</a></div>
	           <script type="text/javascript">
    				(function ($) {
    				    function queryOrderStatus() {
    				        $.ajax({
    				            type: "GET",
    				            url: '<?php echo admin_url('admin-ajax.php')?>',
    				            data: {
    				                id: <?php print $order_id?>,
    				                action: 'xh_alipayf2f_alipay_order_status'
    				            },
    				            timeout:6000,
    				            cache:false,
    				            dataType:'json',
    				            success:function(data){
    				                if (data && data.status === "paid") {
    				                    location.href = '<?php echo $this->get_return_url($wc_order)?>';
    				                    return;
    				                }
    				                
    				                setTimeout(queryOrderStatus, 1500);
    				            },
    				            error:function(){
    				            	setTimeout(queryOrderStatus, 1500);
    				            }
    				        });
    				    }
    				    queryOrderStatus();
    				    setTimeout(function(){
    				    	 $('#btn-refresh').css('display','block');
    	          			location.href='<?php echo esc_url($response['alipay_trade_precreate_response']['qr_code'])?>';
    	              	},1000);
    				})(jQuery);
				</script>
	            <?php 
	            return;
	        }
            ?>
    		<script src="<?php print XH_AL_F2F_URL?>/assets/js/qrcode.js"></script>	
    		<style type="text/css">
            .pay-weixin-design{ display: block;background: #fff;/*padding:100px;*/overflow: hidden;}
              .page-wrap {padding: 50px 0;min-height: auto !important;  }
              .pay-weixin-design #WxQRCode{width:196px;height:auto}
              .pay-weixin-design .p-w-center{ display: block;overflow: hidden;margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee;}
              .pay-weixin-design .p-w-center h3{    font-family: Arial,微软雅黑;margin: 0 auto 10px;display: block;overflow: hidden;}
              .pay-weixin-design .p-w-center h3 font{ display: block;font-size: 14px;font-weight: bold;    float: left;margin: 10px 10px 0 0;}
              .pay-weixin-design .p-w-center h3 strong{position: relative;text-align: center;line-height: 40px;border: 2px solid #3879d1;display: block;font-weight: normal;width: 130px;height: 44px; float: left;}
              .pay-weixin-design .p-w-center h3 strong #img1{margin-top: 10px;display: inline-block;width: 22px;vertical-align: top;}
              .pay-weixin-design .p-w-center h3 strong span{    display: inline-block;font-size: 14px;vertical-align: top;}
              .pay-weixin-design .p-w-center h3 strong #img2{    position: absolute;right: 0;bottom: 0;}
              .pay-weixin-design .p-w-center h4{font-family: Arial,微软雅黑;      margin: 0; font-size: 14px;color: #666;}
              .pay-weixin-design .p-w-left{ display: block;overflow: hidden;float: left;}
              .pay-weixin-design .p-w-left p{ display: block;width:196px;background:#3879d1;color: #fff;text-align: center;line-height:2.4em; font-size: 12px; }
              .pay-weixin-design .p-w-left img{ margin-bottom: 10px;}
              .pay-weixin-design .p-w-right{ margin-left: 50px; display: block;float: left;}
            </style>
            		
            <div class="pay-weixin-design">
                 <div class="p-w-center">
                    <h3>
            		   <font>支付方式已选择支付宝</font>
            		   <strong>
            		      <img id="img1" src="<?php print XH_AL_F2F_URL?>/images/alipay-small.png">
            			  <span>支付宝</span>
            			  <img id="img2" src="<?php print XH_AL_F2F_URL?>/images/ep_new_sprites1.png">
            		   </strong>
            		</h3>
            	    <h4>通过支付宝首页左上角扫一扫，扫描二维码支付。本页面将在支付完成后自动刷新。</h4>
            	 </div>
            		
                 <div class="p-w-left">		  
            		<div id="WxQRCode"></div>
            		<p>使用支付宝扫描二维码进行支付</p>
                 </div>
            
            	 <div class="p-w-right">
            	    <img src="<?php print XH_AL_F2F_URL?>/images/alipay-sys.png" style="width:200px;">
            	 </div>
            </div>	
		    
			<script type="text/javascript">
				(function ($) {
				    function queryOrderStatus() {
				        $.ajax({
				            type: "GET",
				            url: '<?php echo admin_url('admin-ajax.php')?>',
				            data: {
				                id: <?php print $order_id?>,
				                action: 'xh_alipayf2f_alipay_order_status'
				            },
				            timeout:6000,
				            cache:false,
				            dataType:'json',
				            success:function(data){
				                if (data && data.status === "paid") {
				                    location.href = '<?php echo $this->get_return_url($wc_order)?>';
				                    return;
				                }
				                
				                setTimeout(queryOrderStatus, 1500);
				            },
				            error:function(){
				            	setTimeout(queryOrderStatus, 1500);
				            }
				        });
				    }
				    
				    var qrcode = new QRCode(document.getElementById("WxQRCode"), {width : 200,height : 200});
				
					 qrcode.makeCode("<?php print $qrUrl?>");
					 setTimeout(function(){queryOrderStatus();},3000); 
					 
				})(jQuery);
			</script>
            <?php 
	    } catch (Exception $e) {
	        ?><ul class="woocommerce-error">
        			<li><?php echo $e->getMessage();?></li>
        	</ul><?php 
	       
	    }
	}
	
	public function process_refund( $order_id, $amount = null, $reason = ''){
	    $wc_order = wc_get_order ($order_id );
	    if(!$wc_order){
	        return new WP_Error( 'invalid_order', __('Wrong Order') );
	    }
	
	    $total = $wc_order->get_total ();
	    if($amount<=0||$amount>$total){
	        return new WP_Error( 'invalid_order',__('Invalid Amount ' ) );
	    }
	    $api = $this;
	    $appid =$api->get_option('appid');
	    $private_key =$api->get_option('private_key');
	     
	    $exchange_rate = round(floatval($api->get_option('exchange_rate')),3);
	    if($exchange_rate<=0){
	        $exchange_rate = 1;
	    }

	    $parameter = array (
	        'app_id' =>$appid,
	        'method'=>'alipay.trade.refund',
	        'charset'=>'utf-8',
	        'sign_type'=>'RSA2',
	        'timestamp'=>date_i18n('Y-m-d H:i:s'),
	        'version'=>'1.0',
	        'notify_url'=>XH_AL_F2F_URL.'/views/alipay-notify.php',
	        'biz_content'=>json_encode(array(
	            'out_trade_no'=>$wc_order->get_id(),
	            'trade_no'=>$wc_order->get_transaction_id(),
	            'refund_amount'=>round($amount*$exchange_rate,2),
	            'out_request_no'=> $this->create_refund_out_trade_no($wc_order),
	            'refund_reason'=>$reason
	        ))
	    );
	    
	    
	    try {
	        $parameter['sign'] = $this->generate_sign($parameter,$private_key);
            $response =  $this->http_post('https://openapi.alipay.com/gateway.do',$parameter,false,null,true);
            $response = iconv("GB2312","UTF-8",$response);
            $response = json_decode($response,true);
            if(!$response||!is_array($response)){
                $response=array();
            }
            
            if(!isset($response['alipay_trade_refund_response']['code'])||$response['alipay_trade_refund_response']['code']!=10000){
                throw new Exception(print_r($response,true));
            }
	    }catch(Exception $e){
	        return new WP_Error( 'refuse_error', $e->getMessage());
	    }
	     
	    return true;
	}
	

	public function validate_sign(array $params,$publickey){
	    $sign = $params['sign'];
	    $signType = $params['sign_type'];
	    unset($params['sign_type']);
	    unset($params['sign']);
	
	    $args = '';
	    $i = 0;
	    ksort($params);
	    reset($params);
	    foreach ($params as $k => $v) {
	        if (!is_null($v)&&$v!=='') {
	            if ($i == 0) {
	                $args .= "{$k}={$v}";
	            } else {
	                $args .= "&{$k}={$v}" ;
	            }
	            $i++;
	        }
	    }
	    unset ($k, $v);
	    if(strpos($publickey, '-----BEGIN PUBLIC KEY-----')===false)
	        $publickey = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($publickey, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
	     
	    return (bool)openssl_verify($args, base64_decode($sign), $publickey, version_compare(PHP_VERSION,'5.4.0', '<') ? SHA256 : OPENSSL_ALGO_SHA256);
	}
	
	public function generate_sign(array $params,$private_key) {
	    ksort($params);
	    reset($params);
	
	    $args = '';
	    $i = 0;
	    foreach ($params as $k => $v) {
	        if (!is_null($v)&&$v!=='') {
	            if ($i == 0) {
	                $args .= "{$k}={$v}";
	            } else {
	                $args .= "&{$k}={$v}" ;
	            }
	            $i++;
	        }
	    }
	    unset ($k, $v);
	
	    if(strpos($private_key, '-----BEGIN RSA PRIVATE KEY-----')===false)
	        $private_key = "-----BEGIN RSA PRIVATE KEY-----\n" . wordwrap($private_key, 64, "\n", true) . "\n-----END RSA PRIVATE KEY-----";
	
	    openssl_sign($args, $sign, $private_key, version_compare(PHP_VERSION,'5.4.0', '<') ? SHA256 : OPENSSL_ALGO_SHA256); //OPENSSL_ALGO_SHA256是php5.4.8以上版本才支持
	
	    return base64_encode($sign);
	}
}