<?php

include "ChatfuelCustomerChatPages.php";

class ChatfuelCustomerChatPlugin {

    private $pages;

    private $appId = '722013358187209';

    private $domain = 'https://customerchatapp.com/';

    private $hooks = [];

    public function __construct() {

        load_plugin_textdomain( 'chatfuel-customer-chat', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

        $this->pages = new ChatfuelCustomerChatPages( $this->domain );

        $this->addAllActions();
    }

    private function addAllActions() {
        add_action( 'wp_footer', [ $this, 'onRenderFooter' ] );
        add_action( 'admin_menu', [ $this, 'adminMenu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'includeStyles' ] );
        add_action( 'admin_bar_menu', [ $this, 'adminBarRef' ], 900 );
        add_action( 'wp_ajax_'. $this->pages->getShowPagesOption(), [ $this->pages, 'updateShowPagesOption' ] );

        add_action( 'add_meta_boxes', [ $this->pages, 'addMetaBox' ]);
        add_action( 'admin_footer', [ $this->pages, 'adminFooterPrint' ]);

        foreach(get_post_types(['public' => true], 'names' , 'and') as $post_type) {
            add_action('manage_'.$post_type.'_posts_columns', [$this->pages, 'addPostChatVisibilityColumn']);
            add_action('manage_'.$post_type.'_posts_custom_column', [$this->pages, 'displayPostChatVisibility'], 10, 2);
        }
    }

    public function adminMenu() {

        $this->hooks[] = add_menu_page(
            __( 'Chatfuel Customer Chat', 'chatfuel-customer-chat' ), __( 'Chatfuel Customer Chat', 'chatfuel-customer-chat' ), 'manage_options', 'customer-chat',
            array(
                $this->pages,
                'customerChatPage'
            ), 'dashicons-format-chat', 100 );

        $this->hooks[] = add_submenu_page(
            'customer-chat', __( 'Chatfuel Customer Chat Settings', 'chatfuel-customer-chat' ), __( 'Chatfuel Customer Chat Settings', 'chatfuel-customer-chat' ), 'manage_options', 'customer-chat',
            array(
                $this->pages,
                'customerChatPage'
            ) );

    }

    public function includeStyles($hook) {
        if(in_array($hook, $this->hooks)) {
            wp_enqueue_style( 'customerchat-admin-part-css', CHTFL_CUST_CHAT_URL . "assets/css/customerchat.css" );
        }
    }



    public function slugify($text)
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = preg_replace('~[^-\w\d]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        if (empty($text)) {
            return 'n-a';
        }
        return $text;
    }

    public function getRef()
    {
        global $post;

        if(is_front_page()){
            return 'front-page';
        }

        if(is_404()){
            return 'not-found';
        }

        $title = wp_title('', false, 'right');
        $slugified = $this->slugify($title);

        if($slugified == 'n-a'){
            if(is_single() || is_page()){
                return $post->post_type . '-'. $post->ID;
            }
            if(is_tax() || is_tag() || is_category()){
                $term = get_queried_object();
                return $term->taxonomy . '-'. $term->term_id;
            }
        }


        return $slugified;
    }

    function onRenderFooter() {

        $page_id = $this->pages->getPageId();

        $page_ref = $this->getRef();

        $displayMode = $this->pages->getShowPages();

        $showChat = true;

        if(($displayMode != "all") && get_the_ID()){

            $showCurrent = $this->pages->getShowPostById(get_the_ID());
            $hideCurrent = $this->pages->getHidePostById(get_the_ID());

            $showChat = ($displayMode == "hide") ? $showCurrent : !$hideCurrent;
        }

        echo '<pre style="display: none">'.print_r($page_ref, true).'</pre>';

        if ( ! empty( $page_id ) ) {

            echo "		
				<script type='text/javascript'>
				window.fbAsyncInit = function() {
				    FB.init({
				        appId            : '" . $this->appId . "',
				        autoLogAppEvents : true,
				        xfbml            : true,
				        version          : 'v2.11'
				    });
				};
				
				(function(d, s, id) {
                  var js, fjs = d.getElementsByTagName(s)[0];
                  if (d.getElementById(id)) return;
                  js = d.createElement(s); js.id = id;
                  js.src = \"https://connect.facebook.net/en_US/sdk/xfbml.customerchat.js\";
                  fjs.parentNode.insertBefore(js, fjs);
                }(document, 'script', 'facebook-jssdk'));				
				
				</script>
			";

            if($showChat) {
                echo "	
				<div class='fb-customerchat'
				   page_id='" . $page_id . "'
				   ref='" . $page_ref . "'>
				</div>
			";
            }
        }
    }

    function adminBarRef($wp_admin_bar)
    {
        if(!is_admin()) {
            $args = array(
                'id'    => 'chatfuel-customer-chat',
                'title' => 'Page Chat Ref: <b style=" font-weight: bold; ">' . $this->getRef().'</b>',
                'meta'  => array('class' => 'first-toolbar-group'),
            );
            $wp_admin_bar->add_node($args);
        }
    }
}
