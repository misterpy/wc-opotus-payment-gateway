<?php
/**
 * Woocoomerce Opotus Widget plugin is a plugin to enable the user to easily integrate Opotus Payment Dropins
 * into your Woocommerce Store
 *
 * @package Woocommerce Opotus Widget Plugin
 * @author Opotus
 * @license GPL-2.0+
 * @link https://opotus.net
 * @copyright 2019 Standard Systems Ventures
 *
 * @wordpress-plugin
 *            Plugin Name: Woocommerce Opotus Widget Plugin
 *            Plugin URI: https://opotus.net/developers/wordpress
 *            Description: Woocoomerce Opotus Widget plugin is a plugin to enable the user to easily integrate Opotus Payment Dropins into your Woocommerce Store
 *            Version: 1.0
 *            Author: Opotus
 *            Author URI: https://opotus.net
 *            Text Domain: opotus-widget
 *            Contributors: Opotus, Standard Systems
 *            License: GPL-2.0+
 *            License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

add_filter( "woocommerce_payment_gateways", "opotus_add_gateway_class" );
function opotus_add_gateway_class( $gateways ) {
    $gateways[] = "WC_Opotus_Payment_Gateway";
    return $gateways;
}

add_action( "plugins_loaded", "opotus_init_gateway_class" );
function opotus_init_gateway_class() {

    class WC_Opotus_Payment_Gateway extends WC_Payment_Gateway {
        public function __construct() {
            $this->id = "opotus_payment_gateway";
            $this->icon = ""; // TODO URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields = true;
            $this->method_title = "Opotus Payment Gateway";
            $this->method_description = "Description of the payment gateway";

            $this->supports = ["products"];

            // Method with all the options fields
            $this->init_form_fields();

            // Load the settings.
            $this->init_settings();
            $this->title = $this->get_option( "title" );
            $this->description = $this->get_option( "description" );
            $this->enabled = $this->get_option( "enabled" );
            $this->testmode = "yes" === $this->get_option( "testmode" );
            $this->private_key = $this->testmode ? $this->get_option( "test_private_key" ) : $this->get_option( "private_key" );
            $this->publishable_key = $this->testmode ? $this->get_option( "test_publishable_key" ) : $this->get_option( "publishable_key" );


            add_action( "woocommerce_update_options_payment_gateways_" . $this->id, array( $this, "process_admin_options" ) );

            add_action( "wp_enqueue_scripts", array( $this, "payment_scripts" ) );

            // You can also register a webhook here
            // add_action( "woocommerce_api_{webhook name}", array( $this, "webhook" ) );
        }

        public function init_form_fields(){
            $this->form_fields = array(
                "enabled" => array(
                    "title"       => "Enable/Disable",
                    "label"       => "Enable Opotus Payment Gateway",
                    "type"        => "checkbox",
                    "description" => "",
                    "default"     => "no"
                ),
                "title" => array(
                    "title"       => "Title",
                    "type"        => "text",
                    "description" => "This controls the title which the user sees during checkout.",
                    "default"     => "Credit Card",
                    "desc_tip"    => true,
                ),
                "description" => array(
                    "title"       => "Description",
                    "type"        => "textarea",
                    "description" => "This controls the description which the user sees during checkout.",
                    "default"     => "Pay with your credit card via our super-cool payment gateway.",
                ),
                "publishable_key" => array(
                    "title"       => "API Token",
                    "type"        => "text"
                ),
                "private_key" => array(
                    "title"       => "Secret key",
                    "type"        => "password"
                )
            );
        }

        /**
         * You will need it if you want your custom credit card form, Step 4 is about it
         */
        public function payment_fields() {
        }

        public function payment_scripts() {
            wp_enqueue_script( "opotus-widget-npm-js", plugins_url( "./node_modules/opotus-widget/dist/bundle.js", __FILE__ ));
        }

        /*
          * Fields validation, more in Step 5
         */
        public function validate_fields() {
        }

        /*
         * We"re processing the payments here, everything about it is in Step 5
         */
        public function process_payment( $order_id ) {
        }

        /*
         * In case you need a webhook, like PayPal IPN etc
         */
        public function webhook() {
        }
    }
}