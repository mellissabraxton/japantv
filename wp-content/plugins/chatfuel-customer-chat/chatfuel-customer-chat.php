<?php
/*
Plugin Name: Chatfuel Customer Chat
Description: Chatfuel Customer Chat plugin lets you install a free Facebook chat directly on your WordPress site page. With one click, your website visitors can have a Facebook Messenger conversation with you. A native Facebook chat button is easily recognizable by anyone and encourages trustful communication with all the features that a Chatfuel Customer Chat can offer.
Author: Chatfuel & Master of Code
Tags: support chat, live support chat, customer support chat, customer chat plugin, chat plugin, messenger, chat, chatfuel chatbot, masterofcode chatbot, live chat, facebook chat, facebook messenger, chatbot, facebook, messenger chat, messenger chatbot, facebook chatbot, messenger chat plugin, messenger customer chat plugin, messenger live chat plugin
Text Domain: chatfuel-customer-chat
Domain Path: /languages
Version: 1.1.1
*/

define( "CHTFL_CUST_CHAT_PREFIX", "chatfuel_customer_chat" );
define( "CHTFL_CUST_CHAT_PATH", plugin_dir_path(__FILE__) );
define( "CHTFL_CUST_CHAT_MAIN_FILE_PATH", __FILE__ );
define( "CHTFL_CUST_CHAT_URL", plugin_dir_url(__FILE__) );

require "includes/ChatfuelCustomerChatPlugin.php";
$chatfuelChatPlugin = new ChatfuelCustomerChatPlugin();
