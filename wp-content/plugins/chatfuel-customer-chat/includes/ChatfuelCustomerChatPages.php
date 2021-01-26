<?php

class ChatfuelCustomerChatPages {

	private $baseDomain;

	private $pageIdOption   = 'chtfl-chat-page-id';
	private $pageNameOption = 'chtfl-chat-page-name';
	private $showPagesOption = 'chtfl_chat_pages_option';
    private $showOptionForPost = 'chtfl_chat_option_post';
    private $nonceName = 'chatfuel-customer-chat-nonce';

	private $mainPageUrl;

	public function __construct( $domain ) {

		$this->mainPageUrl = add_query_arg( [ 'page' => 'customer-chat' ], admin_url( 'admin.php' ) );

		$this->baseDomain = $domain;
	}

	public function setPageId( $id ) {
		update_option( $this->pageIdOption, $id );
	}

	public function getPageId() {
		return trim( get_option( $this->pageIdOption, '' ) );
	}

	public function setPageName( $id ) {
		update_option( $this->pageNameOption, $id );
	}

	public function getPageName() {
		return trim( get_option( $this->pageNameOption, '' ) );
	}

	public function setShowPages( $value ) {
		update_option( $this->showPagesOption, $value );
	}

	public function getShowPages() {
		return trim( get_option( $this->showPagesOption, 'all' ) );
	}

	public function getShowPostById($post_id) {
        return trim( get_post_meta( $post_id, $this->showPagesOption . "-show", true ) ) == "yes";
	}

	public function getHidePostById($post_id) {
		return trim( get_post_meta( $post_id, $this->showPagesOption . "-hide", true ) ) == "yes";
	}

	public function setShowPostById($post_id, $show = true) {
        update_post_meta( $post_id,$this->showPagesOption . "-show", ($show ? "yes" : "no") );
	}

	public function setHidePostById($post_id, $hide = true) {
        update_post_meta( $post_id,$this->showPagesOption . "-hide", ($hide ? "yes" : "no") );
	}

	public function getShowPagesOption() {
		return $this->showPagesOption;
	}

	public function updateShowPagesOption() {


        if ( ! wp_verify_nonce( $_POST['nonce'], $this->nonceName ) )
            wp_die();

	    if(!empty($_POST['value'])){

            $value = strval( $_POST['value'] );

            $this->setShowPages( $value );

        }
        elseif (!empty($_POST['id']) && !empty($_POST['set'])){

	        $set = ($_POST['set'] == "true");

            $displayMode = $this->getShowPages();
            if($displayMode == "show"){
                $this->setHidePostById(intval($_POST['id']), $set);
            }
            if($displayMode == "hide"){
                $this->setShowPostById(intval($_POST['id']), $set);
            }
        }

        wp_die();
	}

	public function customerChatPage() {

		$this->processUpdatePageId();

		$page_id = $this->getPageId();

		?>
        <div class="wrap">

            <div class="main-plugin-container">

                <h1><?php _e( 'Chat Settings', 'chatfuel-customer-chat' ); ?></h1>

				<?php if ( empty( $page_id ) ) { ?>

                    <a class="connect-button margin-top" href="<?php
					echo add_query_arg(
						[
							'referrer' => urlencode( $this->mainPageUrl )
						]
						, $this->baseDomain )
					?>"><?php _e( 'Connect Facebook Page', 'chatfuel-customer-chat' ); ?></a>

				<?php } ?>

				<?php if ( ! empty( $page_id ) ) { ?>
                    <p class="howto">
                        <?php _e( 'Please sign into your Facebook Page admin panel to chat with your customers.', 'chatfuel-customer-chat' ); ?>
                    </p>

                    <h1 class="margin-top">
                        <?php _e( 'Your Page Connected:', 'chatfuel-customer-chat' ); ?>
                    </h1>

                    <div class="page-container">

                        <img src="https://customerchatapp.com/assets/images/icon.png">

                        <span class="page-title"><?php echo $this->getPageName(); ?></span>

                        <a class="disconnect-button" href="<?php
						echo add_query_arg( [ 'update_page_id' => true ], $this->mainPageUrl );
						?>"><?php _e( 'Disconnect', 'chatfuel-customer-chat' ); ?></a>

                    </div>
				<?php } ?>

                <h1 class="margin-top">
                    <?php _e( 'Plugin Settings:', 'chatfuel-customer-chat' ); ?>
                </h1>

                <div>
                    <?php $showPagesOption = $this->getShowPages(); ?>
                    <select id="<?php echo $this->showPagesOption; ?>" name="<?php echo $this->showPagesOption; ?>" title=""
                            data-nonce="<?php echo wp_create_nonce( $this->nonceName ); ?>"
                            style="width: 100%">
                        <option value="all" <?php selected($showPagesOption, "all")?>><?php _e( 'Show chat on all pages', 'chatfuel-customer-chat' ); ?></option>
                        <option value="show" <?php selected($showPagesOption, "show")?>><?php _e( 'Hide chat only for selected pages*', 'chatfuel-customer-chat' ); ?></option>
                        <option value="hide" <?php selected($showPagesOption, "hide")?>><?php _e( 'Show chat only for selected pages*', 'chatfuel-customer-chat' ); ?></option>
                    </select>

                    <p class="howto">
                        <?php _e( "*In settings of each separate page you can specify use the chat on it or not.", 'chatfuel-customer-chat' ); ?>
                    </p>

                </div>

                <h1 class="margin-top"><?php _e( 'Extra Features', 'chatfuel-customer-chat' ); ?></h1>

                <p class="howto">
					<?php echo sprintf( __( 'You can enhance your messenger experience by building a chatbot on %s for free or talk with %s to design and build more sophisticated chatbot logic.', 'chatfuel-customer-chat' ),
					                    '<a target="_blank" href="http://chatfuel.com/">www.chatfuel.com</a>',
					                    '<a target="_blank" href="http://masterofcode.com/">www.masterofcode.com</a>'
					); ?>
                </p>

                <div class="features-row">
                    <div class="feature-block chatfuel">
                        <div class="logo-container">
                        </div>
                        <div class="text-container">

                            <p>
	                            <?php echo sprintf( __( 'Set up automated messaging and custom conversational flows with %s to enhance the customer experience. Sign up for free today.', 'chatfuel-customer-chat' ),
	                                                '<a href="https://chatfuel.com/">Chatfuel</a><sup>TM</sup>'
	                            ); ?>
                            </p>
                            <a target="_blank" href="http://chatfuel.com/" class="feature-button">
	                            <?php _e( 'Sign up for Chatfuel', 'chatfuel-customer-chat' ); ?>
                            </a>
                        </div>
                    </div>

                    <div class="feature-block master-of-code">
                        <div class="logo-container">
                        </div>
                        <div class="text-container">

                            <p>
	                            <?php echo sprintf( __( 'Get a custom Facebook Messenger chatbot built with one of the world leaders in bot development – %s', 'chatfuel-customer-chat' ),
	                                                '<a href="https://masterofcode.com/">Master.of.Code</a>'
	                            ); ?>
                            </p>
                            <a target="_blank" href="http://masterofcode.com/" class="feature-button">
	                            <?php _e( 'Contact Master.of.Code', 'chatfuel-customer-chat' ); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script type="text/javascript">
            jQuery(document).ready(function(){
                jQuery("#<?php echo $this->showPagesOption; ?>").change(function(){
                    var value = jQuery(this).val();
                    var nonce = jQuery(this).data("nonce");
                    var data = {
                        'action': '<?php echo $this->showPagesOption; ?>',
                        'value': value,
                        'nonce': nonce
                    };
                    jQuery.post(ajaxurl, data, function(response) {});
                });
            });
        </script>
		<?php
	}

	private function processUpdatePageId() {

		if ( ! empty( $_REQUEST['update_page_id'] ) ) {

			$this->setPageId( esc_sql( trim( $_REQUEST['page_id'] ) ) );
			$this->setPageName( esc_sql( trim( $_REQUEST['page_name'] ) ) );

			?>
            <script type="text/javascript">
                window.location.href = '<?php echo $this->mainPageUrl; ?>'
            </script>
			<?php

		}
	}

    function addMetaBox(){
        add_meta_box( 'chatfuel-section', __( 'Chatfuel Customer Chat', 'chatfuel-customer-chat' ), [ $this, 'addMetaBoxCallback' ], null, "side", "high" );
    }

    function addMetaBoxCallback( $post, $meta )
    {
        $displayMode = $this->getShowPages();
        $showCurrent = $this->getShowPostById($post->ID);
        $hideCurrent = $this->getHidePostById($post->ID);

        $postType = $post->post_type;
        ?>
        <?php if($displayMode != "all"){ ?>
        <p>
            <label for="<?php echo $this->showOptionForPost; ?>">
                <input type="checkbox" id="<?php echo $this->showOptionForPost; ?>"
                       <?php checked(((($displayMode == "show") && $hideCurrent) || (($displayMode == "hide") && $showCurrent)))?>
                       name="<?php echo $this->showOptionForPost; ?>"
                       class="toggle-chat-checkbox" data-id="<?php echo $post->ID; ?>"
                       data-nonce="<?php echo wp_create_nonce( $this->nonceName ); ?>"
                       value="<?php echo (($displayMode == "show") ? "hide" : "show"); ?>"/>
                <?php echo sprintf(__((($displayMode == "show") ? "Hide chat on this %s" : "Show chat on this %s"), 'chatfuel-customer-chat'), $postType) ; ?>
            </label>
        </p>
        <?php } ?>

        <p class="howto">
            <?php
            $tips = sprintf(__( 'Chat will be shown on all %ss.', 'chatfuel-customer-chat' ), $postType);
            if($displayMode != "all"){
                $tips = sprintf(__( 'Chat is %s by default.', 'chatfuel-customer-chat' ),
                    ($displayMode == "show") ? __( 'shown', 'chatfuel-customer-chat' ) : __( 'hidden', 'chatfuel-customer-chat' )
                );
            }
            echo $tips;
            ?>
        </p>
        <?php
    }

    function savePostMeta( $post_id ) {

        if ( ! wp_verify_nonce( $_POST[$this->nonceName], plugin_basename(__FILE__) ) )
            return;

        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
            return;

        if( ! current_user_can( 'edit_post', $post_id ) )
            return;

        $displayChatMode = $this->getShowPages();

        $value = !empty($_POST[$this->showOptionForPost]);

        if($displayChatMode == "show"){
            $this->setHidePostById($post_id, $value);
        }

        if($displayChatMode == "hide"){
            $this->setShowPostById($post_id, $value);
        }
    }

    function displayPostChatVisibility( $column, $post_id ) {
        if ($column == 'chat_visibility'){
            $displayMode = $this->getShowPages();
            $showCurrent = $this->getShowPostById($post_id);
            $hideCurrent = $this->getHidePostById($post_id);

            echo '<input type="checkbox" class="toggle-chat-checkbox" data-nonce="'.wp_create_nonce( $this->nonceName ).'" data-id="'.$post_id.'" '. checked(((($displayMode == "show") && $hideCurrent) || (($displayMode == "hide") && $showCurrent)), true, false) .'/>';
        }
    }

    function addPostChatVisibilityColumn( $columns ) {
        $displayMode = $this->getShowPages();

        if($displayMode != "all"){
            $title = ($displayMode == "show") ? "Hide Chat" : "Show Chat";
            $columns = array_merge( $columns, ['chat_visibility' => __( $title, 'chatfuel-customer-chat' ) ] );
        }
        return $columns;
    }

    function adminFooterPrint() {
        ?>

        <script type="text/javascript">
            jQuery(document).ready(function(){
                jQuery(".toggle-chat-checkbox").change(function(){
                    var checked = jQuery(this).prop('checked');
                    var id = jQuery(this).data('id');
                    var nonce = jQuery(this).data('nonce');
                    var data = {
                        'action': '<?php echo $this->showPagesOption; ?>',
                        'nonce': nonce,
                        'set': checked,
                        'id': id,
                    };
                    jQuery.post(ajaxurl, data, function(response) { });
                });
            });
        </script>

        <style type="text/css">
            .column-chat_visibility {
                width: 70px;
                text-align: center;
            }
        </style>

        <?php
    }


}
