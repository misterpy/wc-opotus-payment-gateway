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

add_filter("woocommerce_payment_gateways", "opotus_add_gateway_class");
function opotus_add_gateway_class($gateways)
{
    $gateways[] = "WC_Opotus_Payment_Gateway";
    return $gateways;
}

add_action("plugins_loaded", "opotus_init_gateway_class");
function opotus_init_gateway_class()
{

    class WC_Opotus_Payment_Gateway extends WC_Payment_Gateway
    {
        public function __construct()
        {
            $this->id = "opotus_payment_gateway";
            $this->icon = ""; // TODO URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields = true;
            $this->method_title = "Opotus Payment Gateway";
            $this->method_description = "Description of the payment gateway";

            $this->supports = ["products"];

            $this->init_form_fields();

            $this->init_settings();
            $this->title = $this->get_option("title");
            $this->description = $this->get_option("description");
            $this->enabled = $this->get_option("enabled");
            $this->secret_key = $this->get_option("private_key");
            $this->api_token = $this->get_option("api_token");


            add_action("woocommerce_update_options_payment_gateways_" . $this->id, array($this, "process_admin_options"));

            add_action("wp_enqueue_scripts", array($this, "payment_scripts"));


            add_action("woocommerce_api_opotus-payment-complete", array($this, "webhook"));
        }

        public function init_form_fields()
        {
            $this->form_fields = array(
                "enabled" => array(
                    "title" => "Enable/Disable",
                    "label" => "Enable Opotus Payment Gateway",
                    "type" => "checkbox",
                    "description" => "",
                    "default" => "no"
                ),
                "title" => array(
                    "title" => "Title",
                    "type" => "text",
                    "description" => "This controls the title which the user sees during checkout.",
                    "default" => "Credit Card",
                    "desc_tip" => true,
                ),
                "description" => array(
                    "title" => "Description",
                    "type" => "textarea",
                    "description" => "This controls the description which the user sees during checkout.",
                    "default" => "Pay with your credit card via our super-cool payment gateway.",
                ),
                "api_token" => array(
                    "title" => "API Token",
                    "type" => "text"
                ),
                "secret_key" => array(
                    "title" => "Secret key",
                    "type" => "password"
                )
            );
        }

        public function validate_fields()
        {
            return true;
        }

        public function payment_fields()
        {
            $widgetPlaceholderId = "opotusWidgetPlaceholder";
            ?>

            <button type="button" onclick="payNow()" id="paynowWithOpotus">Pay now</button>

            <div id="<?php echo $widgetPlaceholderId; ?>"></div>
            <script type="application/javascript">
                togglePlaceholderButton();

                jQuery("#payment").find("input[type='radio']").change(() => {
                    togglePlaceholderButton();
                });

                function togglePlaceholderButton() {
                    if (!!jQuery("#payment_method_opotus_payment_gateway:checked").val()) {
                        console.log("opotus");
                        jQuery("#place_order").hide();
                    } else {
                        console.log("not opotus");
                        jQuery("#place_order").show();
                    }
                }
                function payNow() {
                    jQuery("#paynowWithOpotus").attr("disabled", true);
                    jQuery.ajax({
                        url: '<?php echo get_site_url(); ?>?wc-ajax=checkout',
                        type: "POST",
                        data: jQuery(".woocommerce-checkout").serialize(),
                        success: (request) => {
                            jQuery("#paynowWithOpotus").hide();
                            initWidget(request.order_id, request.amount);
                        },
                        error: () => {
                            jQuery("#paynowWithOpotus").attr("disabled", false);
                        }
                    });
                }

                function onDone(transaction) {
                    window.location.href = parseUrl("<?php echo stripslashes_deep(esc_attr(get_site_url() . "/wc-api/opotus-payment-complete")); ?>", transaction);
                }

                function parseUrl(url, parameters) {
                    const parsedUrl = new URL(url);
                    const queryParams = parseQueryParams(parsedUrl.search);

                    return parsedUrl.origin + parsedUrl.pathname + appendQueryParams({...queryParams, ...parameters});
                }

                function parseQueryParams(queryString) {
                    if (!queryString.startsWith("?")) {
                        return {};
                    }

                    queryString = queryString.slice(1);
                    const params = {};
                    queryString.split("&").forEach(val => {
                        const valArray = val.split("=");
                        params[valArray[0]] = valArray[1];
                    });

                    return params;
                }

                function appendQueryParams(parameters) {
                    return "?" + Object.keys(parameters).map(key => `${key}=${parameters[key]}`).join("&");
                }

                function initWidget(orderId, amount) {
                    const config = {
                        apiKey: "<?php echo stripslashes_deep(esc_attr($this->get_option("api_token"))); ?>",
                        callbackUrl: "<?php echo stripslashes_deep(esc_attr(get_site_url())); ?>",
                        amount: Number(amount) * 100,
                        orderId: orderId,
                        placeholderId: "<?php echo $widgetPlaceholderId ?>",
                        transactionDoneCallback: onDone,
                    };

                    const widget = new OpotusWidget();
                    widget.init(config);
                }
            </script>
            <?php
        }

        public function process_payment($order_id)
        {
            global $woocommerce;
            $order = new WC_Order($order_id);
            return [
                "result"   => "success",
                "redirect" => "",
                "order_id" => $order_id,
                "amount" => $order->get_total() * 100,
            ];
        }

        public function payment_scripts()
        {
            if (!is_cart() && !is_checkout() && !isset($_GET["pay_for_order"])) {
                return;
            }

            if ("no" === $this->enabled) {
                return;
            }

            if (empty($this->api_token) || empty($this->secret_key)) {
                return;
            }

            if (!is_ssl()) {
                return;
            }

            wp_register_script("opotus-widget-npm-js", plugins_url("./node_modules/opotus-widget/dist/bundle.js", __FILE__));
            wp_enqueue_script("opotus-widget-npm-js");
        }

        public function webhook()
        {
            global $woocommerce;

            $order = wc_get_order($_GET["orderId"]);
            $transaction_id = $_GET["transactionId"];
            $status = $_GET["status"];
            $hash = $_GET["hash"];

            if ($status == "successful") {
                $order->payment_complete();
                $woocommerce->cart->empty_cart();
                $order->reduce_order_stock();

                $this->get_return_url($order);
            } else {
                wc_add_notice("Please try again.", "error");
                wp_redirect(wc_get_checkout_url());
            }

            update_option('webhook_debug', $_GET);
        }
    }
}