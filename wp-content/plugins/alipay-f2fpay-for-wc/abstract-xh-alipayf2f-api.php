<?php
if (! defined('ABSPATH'))
    exit();

abstract class Abstract_XH_Alipay_F2F_Api{
    public $v,$t,$f,$i,$dir,$key,$u_u,$get;
    const ID='xh_alipayf2f_for_wc';
    
    public function __construct(){
        $this->i = self::ID;
        $this->v=XH_AL_F2F_VERSION;
        $this->f=XH_AL_F2F_FILE;
        $this->dir=XH_AL_F2F_DIR;
        $this->get = $_GET;
        $this->u_u = admin_url("admin.php?page=woo_alipayf2f_license");
        
        register_activation_hook ( XH_AL_F2F_FILE, array($this,'register_activation_hook') );
        register_deactivation_hook ( XH_AL_F2F_FILE, array($this,'register_deactivation_hook') );
      
        add_action('admin_menu',array($this,'admin_menu'));
       
        if(isset($_POST['action'])
            &&isset($_POST['license_key'])
            &&$_POST['action']===md5(self::ID)){
            $license_key = trim($_POST['license_key']);
            update_option(self::ID, $license_key);
        }
        
        add_action( 'admin_init', array($this,'option_init'));
       
        add_action ( "wp_ajax_xh_alipayf2f_alipay_order_status", array($this,'alipay_order_is_paid'));
        add_action ( "wp_ajax_nopriv_xh_alipayf2f_alipay_order_status", array($this,'alipay_order_is_paid'));
    }
    
    public function wechat_order_is_paid(){
        $order_id = isset($_GET['id'])?$_GET['id']:0;
        if(!$order_id){
            echo json_encode(array(
                'status'=>'unpaid'
            ));
            exit;
        }
        
        $order = wc_get_order($order_id);
        if(!$order){
            echo json_encode(array(
                'status'=>'unpaid'
            ));
            exit;
        }
        
        if((method_exists($order, 'is_paid')?$order->is_paid():in_array($order->get_status(),  array( 'processing', 'completed' )))){
            echo json_encode(array(
                'status'=>'paid'
            ));
            exit;
        }
        
        echo json_encode(array(
            'status'=>'unpaid'
        ));
        exit;
    }
    
    public function alipay_order_is_paid(){
        $order_id = isset($_GET['id'])?$_GET['id']:0;
        if(!$order_id){
            echo json_encode(array(
                'status'=>'unpaid'
            ));
            exit;
        }
    
        $order = wc_get_order($order_id);
        if(!$order){
            echo json_encode(array(
                'status'=>'unpaid'
            ));
            exit;
        }
    
        if((method_exists($order, 'is_paid')?$order->is_paid():in_array($order->get_status(),  array( 'processing', 'completed' )))){
            echo json_encode(array(
                'status'=>'paid'
            ));
            exit;
        }
    
        echo json_encode(array(
            'status'=>'unpaid'
        ));
        exit;
    }
    
    public function option_init(){
        register_setting( 'general', self::ID);
        add_settings_field(self::ID,'支付宝- 当面付 for WooCommerce授权',array($this,'general_default_callback'),'general','default');
    }
    
    public function general_default_callback() {
        $id=self::ID;
        $hash =esc_attr( get_option( self::ID) );
        echo "<input name=\"{$id}\" type=\"text\" id=\"{$id}\" value=\"{$hash}\" class=\"regular-text code\" style=\"width: 25em;\" />";
        
    }
    public function register_deactivation_hook(){
        //清除
        $actions = array(
            'plugin_latest_version'
        );
         
        foreach ($actions as $action){
            $cache_key =md5("xh_updater,id:{$this->i},action:{$action},version:{$this->v}");
            delete_site_transient( $cache_key );
        }
    }
    
    public function inc($b=null){
        if(is_null($b)){
            return $GLOBALS[self::ID];
        }
        $GLOBALS[self::ID]=$b;
    }
    
    public function after_init(){
        
    }
    
    public function admin_menu(){
        if(!isset($_GET['page'])||$_GET['page']!='woo_alipayf2f_license'){
            if($GLOBALS[self::ID]){
                return;
            }
        }
        
        add_menu_page( '支付宝- 当面付 for WooCommerce 授权', '支付宝- 当面付 for WooCommerce授权', 'administrator', 'woo_alipayf2f_license',array($this,'woo_alipayf2f_license'));   
    }
    
    public function woo_alipayf2f_license(){
        $id=md5(self::ID);
        $license_key = get_option(self::ID);
        ob_start();
		?>
        <div class="wrap about-wrap gform_installation_progress_step_wrap">
			<h2>许可证密钥</h2>
			
			<form action="" method="POST">	
				<input type="hidden" name="action" value="<?php print $id;?>"/>		
				<div class="about-text">
    				<p>感谢支持！请在下面输入您的<a href="https://www.wpweixin.net" target="_blank">支付宝- 当面付 for WooCommerce</a>许可证密钥（已随订单邮件发给您了）！如有任何疑问，请访问我们的官网<a href="https://www.wpweixin.net" target="_blank">迅虎网络</a>或直接咨询<a href="http://wpa.qq.com/msgrd?v=3&uin=6347007&site=qq&menu=yes" target="_blank">售前客服</a>。</p>
            		<div>
            			<input type="text" class="regular-text" value="<?php print esc_attr($license_key )?>" name="license_key" placeholder="输入您的许可证密钥">
            		</div>
    			</div>
		
				<div><input class="button button-primary" type="submit" value="提交">
				<?php 
				if(isset($_POST['action'])&&!$GLOBALS[self::ID]){
				    ?><span style="color:red;">许可证密钥验证失败！</span><?php 
				}else if(isset($_POST['action'])&&$GLOBALS[self::ID]){
				    ?><span style="color:green;">许可证密钥验证成功！</span><?php
				} 
				?>
				</div>
			</form>
		</div>
		<?php 
		if($GLOBALS[self::ID]){
		    ?>
		    <script type="text/javascript">
				location.href='<?php print admin_url('admin.php?page=wc-settings&tab=checkout&section='.XHALIF2FWC::instance()->id);?>';
		    </script>
		    <?php 
		}
        print ob_get_clean();
    }
    
    public function register_activation_hook(){
        global $wpdb;
       
        $current = get_site_transient('update_plugins');
        if ( $current ) {
            set_site_transient( 'update_plugins', $current );
        }
    }
}