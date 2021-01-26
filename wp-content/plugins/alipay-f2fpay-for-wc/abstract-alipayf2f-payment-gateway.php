<?php
abstract class Abstract_XH_Alipay_F2F_Payment_Gateway extends WC_Payment_Gateway{
    public $instructions;
    
    public function woocommerce_add_gateway($gateways){
        $gateways[]=$this;
        return $gateways;
    }
    public function get_client_ip()
    {
        $ip = getenv('HTTP_CLIENT_IP');
        if ($ip && strcasecmp($ip, 'unknown')) {
            return preg_match('/[\d\.]{7,15}/', $ip, $matches) ? $matches[0] : null;
        }
    
        $ip = getenv('HTTP_X_FORWARDED_FOR');
        if ($ip && strcasecmp($ip, 'unknown')) {
            return preg_match('/[\d\.]{7,15}/', $ip, $matches) ? $matches[0] : null;
        }
    
        $ip = getenv('REMOTE_ADDR');
        if ($ip && strcasecmp($ip, 'unknown')) {
            return preg_match('/[\d\.]{7,15}/', $ip, $matches) ? $matches[0] : null;
        }
    
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
        if ($ip && strcasecmp($ip, 'unknown')) {
            return preg_match('/[\d\.]{7,15}/', $ip, $matches) ? $matches[0] : null;
        }
    
        return null;
    }

    /**
     * 
     * @param WC_Order $order
     */
    public function create_out_trade_no($order){
        $oid =$this->get_option('prefix'). date_i18n('YmdHis-'). $order->get_id();
        update_post_meta($order->get_id(), '__alipayf2f_out_trade_no', $oid);
        return $oid;
    }
    
    public function create_refund_out_trade_no($order){
        return 'f_'.$this->get_option('prefix'). date_i18n('YmdHis-'). $order->get_id();
    }
    
    public function get_out_trade_no($order){
        return (string)get_post_meta($order->get_id(), '__alipayf2f_out_trade_no', true);
    }
    
    public function get_order_id_from_out_trade_no($out_trade_no){
        return substr($out_trade_no, strlen($this->get_option('prefix'))+15);
    }
    public  function is_ios() {
        $ua = strtolower($_SERVER ['HTTP_USER_AGENT']);
        return strripos ( $ua, 'iphone' ) != false || strripos ( $ua, 'ipad' ) != false;
    }
    public function is_wechat_client(){
        return strripos($_SERVER['HTTP_USER_AGENT'],'micromessenger')!=false;
    }
    public function verify($data, $timestamp, $public_key, $sign) {
        $pubkey = openssl_pkey_get_public($public_key);
        ksort($data);
        $data_str = '';
        foreach ($data as $key => $item) {
            if (strlen($data_str) == 0) {
                $data_str .= $key . '=' . $item;
            } else {
                $data_str .= '&' . $key . '=' . $item;
            }
        }
        $data_str = utf8_encode($data_str);
      
        $data_str .= ',' . strval($timestamp); 
        
        return openssl_verify($data_str, base64_decode($sign), $pubkey, OPENSSL_ALGO_SHA256);
    }
    
    public function sign($data, $timestamp, $private_key) {
    
        $prikey = openssl_pkey_get_private($private_key);
    
        ksort($data);
    
        $signature_str = '';
    
        foreach ($data as $key => $item) {
            if (strlen($signature_str) == 0) {
                $signature_str .= $key . '=' . rawurlencode($item);
            } else {
                $signature_str .= '&' . $key . '=' . rawurlencode($item);
            }
        }
    
        $signature_str = utf8_encode($signature_str);
    
        $signature_str .= ',' . strval($timestamp);
    
        $alg = OPENSSL_ALGO_SHA256;
    
        openssl_sign($signature_str, $sign, $prikey, $alg);
    
        $sign = base64_encode($sign);
    
        return $sign;
    }
    
    
    public  function http_post($url,$data=null,$require_ssl=false,$ch = null,$post_field_is_array=false){
        if (! function_exists('curl_init')) {
            throw new Exception('php libs not found!', 500);
        }
        if(!$ch){
            $ch = curl_init();
        }
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_REFERER, home_url('/'));
        // 为保证第三方服务器与微信服务器之间数据传输的安全性，所有微信接口采用https方式调用，必须使用下面2行代码打开ssl安全校验。
        // 如果在部署过程中代码在此处验证失败，请到 http://curl.haxx.se/ca/cacert.pem 下载新的证书判别文件。
        if($require_ssl){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt( $ch, CURLOPT_CAINFO, ABSPATH . WPINC . '/certificates/ca-bundle.crt');
        }else{
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
    
        if(!empty($data)){
            if(!$post_field_is_array){
                if(is_array($data)){
                    $data = http_build_query($data);
                }
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
    
        $response = curl_exec($ch);
        $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if (apply_filters('xunhu_http_post_errcode', $httpStatusCode != 200,$httpStatusCode,$ch)) {
            throw new Exception("status:{$httpStatusCode},response:$response,error:" . $error, $httpStatusCode);
        }
    
        return $response;
    }
    public  function http_get($url,$require_ssl=false,$ch = null){
        if (! function_exists('curl_init')) {
            throw new Exception('php libs not found!', 500);
        }
        if(!$ch){
            $ch = curl_init();
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_REFERER, home_url('/'));
        if($require_ssl){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt( $ch, CURLOPT_CAINFO, ABSPATH . WPINC . '/certificates/ca-bundle.crt');
        }else{
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
    
        $response = curl_exec($ch);
        $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if (apply_filters('xunhu_http_post_errcode', $httpStatusCode != 200,$httpStatusCode,$ch)) {
            throw new Exception("status:{$httpStatusCode},response:$response,error:" . $error, $httpStatusCode);
        }
    
        return $response;
    }
    public function is_app_client(){
        if(!isset($_SERVER['HTTP_USER_AGENT'])){
            return false;
        }
    
        $u=strtolower($_SERVER['HTTP_USER_AGENT']);
        if($u==null||strlen($u)==0){
            return false;
        }
    
        preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/',$u,$res);
    
        if($res&&count($res)>0){
            return true;
        }
    
        if(strlen($u)<4){
            return false;
        }
    
        preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/',substr($u,0,4),$res);
        if($res&&count($res)>0){
            return true;
        }
    
        $ipadchar = "/(ipad|ipad2)/i";
        preg_match($ipadchar,$u,$res);
        if($res&&count($res)>0){
            return true;
        }
    
        return false;
    }
    /**
     * Output for the order received page.
     */
    public function thankyou_page() {
        if ( $this->instructions ) {
            echo wpautop( wptexturize( $this->instructions ) );
        }
    }
    
    /**
     * Add content to the WC emails.
     *
     * @access public
     * @param WC_Order $order
     * @param bool $sent_to_admin
     * @param bool $plain_text
     */
    public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
        $method = method_exists($order ,'get_payment_method')?$order->get_payment_method():$order->payment_method;
        if ( $this->instructions && ! $sent_to_admin && $this->id === $method ) {
            echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
        }
    }
    

    public function process_refund( $order_id, $amount = null, $reason = ''){
        $order = wc_get_order ($order_id );
        if(!$order){
            return new WP_Error( 'invalid_order', __('Wrong Order',XH_AL_F2F) );
        }
    
        $total = ( int ) ($order->get_total () * 100);
        $amount = ( int ) ($amount * 100);
        if($amount<=0||$amount>$total){
            return new WP_Error( 'invalid_order',__('Invalid Amount ' ,XH_AL_F2F) );
        }
    
        $partner_code = $this->get_option('partner_code');
        $time=time().'000';
        $nonce_str = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0,10);
        $credential_code = $this->get_option('credential_code');
        $valid_string="$partner_code&$time&$nonce_str&$credential_code";
        $sign=strtolower(hash('sha256',$valid_string));
    
        $ooid =get_post_meta($order_id, 'alipayf2f_order_id',true);
        $refund_id=time();
    
        $url ="https://pay.alipayf2f.com/api/v1.0/gateway/partners/$partner_code/orders/$ooid/refunds/$refund_id";
        $url.="?time=$time&nonce_str=$nonce_str&sign=$sign";
    
        $head_arr = array();
        $head_arr[] = 'Content-Type: application/json';
        $head_arr[] = 'Accept: application/json';
        $head_arr[] = 'Accept-Language: '.get_locale();
    
        $data =new stdClass();
        $data->fee = $amount;
        $data=json_encode($data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_PUT, true);
    
        //add for https
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt( $ch, CURLOPT_CAINFO, ABSPATH . WPINC . '/certificates/ca-bundle.crt');
    
        curl_setopt($ch, CURLOPT_HTTPHEADER, $head_arr);
        $temp = tmpfile();
        fwrite($temp, $data);
        fseek($temp, 0);
        curl_setopt($ch, CURLOPT_INFILE, $temp);
        curl_setopt($ch, CURLOPT_INFILESIZE, strlen($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        $result = curl_exec($ch);
        curl_close($ch);
    
        if($temp){
            fclose($temp);
            unset($temp);
        }
    
        $resArr = json_decode($result,false);
        if(!$resArr){
            return new WP_Error( 'refuse_error', $result);
        }
    
        if($resArr->result_code!='SUCCESS'){
            return new WP_Error( 'refuse_error', __(sprintf('ERROR CODE:%s',empty($resArr->result_code)?$resArr->return_code:$resArr->result_code),XH_AL_F2F));
        }
        return true;
    }
    
    /**
     *
     * @param WC_Order $order
     * @param INT $limit
     * @param string $trimmarker
     * @return string
     */
    public function get_order_title($order,$limit=32,$trimmarker='...'){
        $title ="";
        $order_items = $order->get_items();
        if($order_items){
            $qty = count($order_items);
            foreach ($order_items as $item_id =>$item){
                $title.="{$item['name']}";
                break;
            }
            if($qty>1){
                $title.='...';
            }
        }
    
        $title = mb_strimwidth($title, 0, $limit,$trimmarker,'utf-8');
        return apply_filters('xh-payment-get-order-title', $title,$order);
    }
}