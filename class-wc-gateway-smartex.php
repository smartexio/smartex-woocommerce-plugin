<?php
/*
    Plugin Name: Smartex for WooCommerce
    Plugin URI:  https://smartex.io
    Description: Enable your WooCommerce store to enable Ethereum powered payments with Smartex.io
    Author:      smartex
    Author URI:  https://smartex.io

    Version:           1.0.0
    License:           Copyright 2016 Smartex.io Ltd., MIT License
    License URI:       https://github.com/smartexio/woocommerce-plugin/blob/master/LICENSE
    GitHub Plugin URI: https://github.com/smartexio/woocommerce-plugin
 */

// Exit if accessed directly
if (false === defined('ABSPATH')) {
    exit;
}

$autoloader_param = __DIR__ . '/lib/Smartex/Autoloader.php';

// Load up the Smartex library
if (true === file_exists($autoloader_param) &&
    true === is_readable($autoloader_param))
{
    require_once $autoloader_param;
    \Smartex\Autoloader::register();
} else {
    throw new \Exception('The Smartex payment plugin was not installed correctly or the files are corrupt. Please reinstall the plugin. If this message persists after a reinstall, contact support@smartex.io with this message.');
}

// Exist for quirks in object serialization...
if (false === class_exists('PrivateKey')) {
    include_once(__DIR__ . '/lib/Smartex/PrivateKey.php');
}

if (false === class_exists('PublicKey')) {
    include_once(__DIR__ . '/lib/Smartex/PublicKey.php');
}

if (false === class_exists('Token')) {
    include_once(__DIR__ . '/lib/Smartex/Token.php');
}

// Ensures WooCommerce is loaded before initializing the Smartex plugin
add_action('plugins_loaded', 'woocommerce_smartex_init', 0);
register_activation_hook(__FILE__, 'woocommerce_smartex_activate');

function woocommerce_smartex_init()
{
    if (true === class_exists('WC_Gateway_Smartex')) {
        return;
    }

    if (false === class_exists('WC_Payment_Gateway')) {
        return;
    }

    // Exist for quirks in object serialization...
    if (false === class_exists('PrivateKey')) {
        include_once(__DIR__ . '/lib/Smartex/PrivateKey.php');
    }

    if (false === class_exists('PublicKey')) {
        include_once(__DIR__ . '/lib/Smartex/PublicKey.php');
    }

    if (false === class_exists('Token')) {
        include_once(__DIR__ . '/lib/Smartex/Token.php');
    }

    class WC_Gateway_Smartex extends WC_Payment_Gateway
    {
        private $is_initialized = false;

        /**
         * Constructor for the gateway.
         */
        public function __construct()
        {
            // General
            $this->id                 = 'smartex.io';
            $this->icon               = plugin_dir_url(__FILE__).'assets/img/icon.png';
            $this->has_fields         = false;
            $this->order_button_text  = __('Proceed to Smartex', 'smartex');
            $this->method_title       = 'Smartex';
            $this->method_description = 'Smartex allows you to accept Ethereum based payments on your WooCommerce store.';

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->title              = $this->get_option('title');
            $this->description        = $this->get_option('description');
            $this->order_states       = $this->get_option('order_states');
            $this->debug              = 'yes' === $this->get_option('debug', 'no');

            // Define Smartex settings
            $this->api_key            = get_option('woocommerce_smartex_key');
            $this->api_pub            = get_option('woocommerce_smartex_pub');
            $this->api_sin            = get_option('woocommerce_smartex_sin');
            $this->api_token          = get_option('woocommerce_smartex_token');
            $this->api_token_label    = get_option('woocommerce_smartex_label');
            $this->api_network        = get_option('woocommerce_smartex_network');

            // Define debugging & informational settings
            $this->debug_php_version    = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
            $this->debug_plugin_version = get_option('woocommerce_smartex_version');

            $this->log('Smartex Woocommerce payment plugin object constructor called. Plugin is v' . $this->debug_plugin_version . ' and server is PHP v' . $this->debug_php_version);
            $this->log('    [Info] $this->api_key            = ' . $this->api_key);
            $this->log('    [Info] $this->api_pub            = ' . $this->api_pub);
            $this->log('    [Info] $this->api_sin            = ' . $this->api_sin);
            $this->log('    [Info] $this->api_token          = ' . $this->api_token);
            $this->log('    [Info] $this->api_token_label    = ' . $this->api_token_label);
            $this->log('    [Info] $this->api_network        = ' . $this->api_network);

            // Process Credentials
            if (false === empty($this->api_key)) {
                try {
                    $this->api_key    = $this->smartex_decrypt($this->api_key);

                    if (false === empty($this->api_key)) {
                        $this->log('    [Info] Private Key decrypted successfully.');
                    } else {
                        $this->log('    [Error] Private Key decrypted successfully BUT the value itself is null or empty!');
                    }
                } catch (\Exception $e) {
                    $this->log('    [Error] Private Key corrupt. Message is: ' . $e->getMessage());
                }
            } else {

            }

            if (false === empty($this->api_pub)) {
                try {
                    $this->api_pub    = $this->smartex_decrypt($this->api_pub);

                    if (false === empty($this->api_pub)) {
                        $this->log('    [Info] Public Key decrypted successfully.');
                    } else {
                        $this->log('    [Error] Public Key decrypted successfully BUT the value itself is null or empty!');
                    }
                } catch (\Exception $e) {
                    $this->log('    [Error] Public Key corrupt. Message is: ' . $e->getMessage());
                }
            }

            if (false === empty($this->api_token)) {
                try {
                    $this->api_token    = $this->smartex_decrypt($this->api_token);

                    if (true === isset($this->api_token) && false === empty($this->api_token)) {
                        $this->log('    [Info] API Token decrypted successfully.');
                    } else {
                        $this->log('    [Error] API Token decrypted successfully BUT the value itself is null or empty!');
                    }
                } catch (\Exception $e) {
                    $this->log('    [Error] API Token corrupt. Message is: ' . $e->getMessage());
                }
            }

            // Check API Credentials
            if (!($this->api_key instanceof \Smartex\PrivateKey)) {
                $this->api_key        = null;
                $this->log('    [Error] The API Key was NOT an instance of PrivateKey!  Instead, it appears to be a ' . gettype($this->api_key) . ' value.');
            }

            if (!($this->api_pub instanceof \Smartex\PublicKey)) {
                $this->api_pub        = null;
                $this->log('    [Error] The Public Key was NOT an instance of PublicKey!  Instead, it appears to be a ' . gettype($this->api_pub) . ' value.');
            }

            if (!($this->api_token instanceof \Smartex\Token)) {
                $this->api_token      = null;
                $this->log('    [Error] The API Token was NOT an instance of Token!  Instead, it appears to be a ' . gettype($this->api_token) . ' value.');
            }

            $this->transaction_speed  = $this->get_option('transaction_speed');
            $this->log('    [Info] Transaction speed is now set to: ' . $this->transaction_speed);

            // Actions
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'save_order_states'));

            // Valid for use and IPN Callback
            if (false === $this->is_valid_for_use()) {
                $this->enabled = 'no';
                $this->log('    [Info] The plugin is NOT valid for use!');
            } else {
                $this->enabled = 'yes';
                $this->log('    [Info] The plugin is ok to use.');
                add_action('woocommerce_api_wc_gateway_smartex', array($this, 'ipn_callback'));
            }

            $this->is_initialized = true;
        }

        public function __destruct()
        {
        }

        public function is_valid_for_use()
        {
            // Check that API credentials are set
            if (true === is_null($this->api_key) ||
                true === is_null($this->api_pub) ||
                true === is_null($this->api_sin) ||
                true === is_null($this->api_token))
            {
                return false;
            }

            // Ensure the currency is supported by Smartex
            try {
                $currency = new \Smartex\Currency(get_woocommerce_currency());

                if (false === isset($currency) || true === empty($currency)) {
                    $this->log('    [Error] The Smartex payment plugin was called to check if it was valid for use but could not instantiate a currency object.');
                    throw new \Exception('The Smartex payment plugin was called to check if it was valid for use but could not instantiate a currency object. Cannot continue!');
                }
            } catch (\Exception $e) {
                $this->log('    [Error] In is_valid_for_use: ' . $e->getMessage());
                return false;
            }

            $this->log('    [Info] Plugin is valid for use.');

            return true;
        }

        /**
         * Initialise Gateway Settings Form Fields
         */
        public function init_form_fields()
        {
            $this->log('    [Info] Entered init_form_fields()...');
            $log_file = 'smartex-' . sanitize_file_name( wp_hash( 'smartex' ) ) . '-log';
            $logs_href = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wc-status&tab=logs&log_file=' . $log_file;

            $this->form_fields = array(
                'enabled' => array(
                    'title'   => __('Enable/Disable', 'smartex'),
                    'type'    => 'checkbox',
                    'label'   => __('Enable Ethereum based payments via Smartex', 'smartex'),
                    'default' => 'yes'
               ),
                'title' => array(
                    'title'       => __('Title', 'smartex'),
                    'type'        => 'text',
                    'description' => __('Controls the name of this payment method as displayed to the customer during checkout.', 'smartex'),
                    'default'     => __('Ethereum', 'smartex'),
                    'desc_tip'    => true,
               ),
                'description' => array(
                    'title'       => __('Customer Message', 'smartex'),
                    'type'        => 'textarea',
                    'description' => __('Message to explain how the customer will be paying for the purchase.', 'smartex'),
                    'default'     => 'You will be redirected to smartex.io to complete your purchase.',
                    'desc_tip'    => true,
               ),
                'api_token' => array(
                    'type'        => 'api_token'
               ),
                'order_states' => array(
                    'type' => 'order_states'
               ),
                'debug' => array(
                    'title'       => __('Debug Log', 'smartex'),
                    'type'        => 'checkbox',
                    'label'       => sprintf(__('Enable logging <a href="%s" class="button">View Logs</a>', 'smartex'), $logs_href),
                    'default'     => 'no',
                    'description' => sprintf(__('Log Smartex events, such as IPN requests, inside <code>%s</code>', 'smartex'), wc_get_log_file_path('smartex')),
                    'desc_tip'    => true,
               ),
                'notification_url' => array(
                    'title'       => __('Notification URL', 'smartex'),
                    'type'        => 'url',
                    'description' => __('Smartex will send IPNs for orders to this URL with the Smartex invoice data', 'smartex'),
                    'default'     => '',
                    'placeholder' => WC()->api_request_url('WC_Gateway_Smartex'),
                    'desc_tip'    => true,
               ),
                'redirect_url' => array(
                    'title'       => __('Redirect URL', 'smartex'),
                    'type'        => 'url',
                    'description' => __('After paying the Smartex invoice, users will be redirected back to this URL', 'smartex'),
                    'default'     => '',
                    'placeholder' => $this->get_return_url(),
                    'desc_tip'    => true,
               ),
                'support_details' => array(
		            'title'       => __( 'Plugin & Support Information', 'smartex' ),
		            'type'        => 'title',
		            'description' => sprintf(__('This plugin version is %s and your PHP version is %s. If you need assistance, please contact support@smartex.io.  Thank you for using Smartex!', 'smartex'), get_option('woocommerce_smartex_version'), PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION),
	           ),
           );

            $this->log('    [Info] Initialized form fields: ' . var_export($this->form_fields, true));
            $this->log('    [Info] Leaving init_form_fields()...');
        }

        /**
         * HTML output for form field type `api_token`
         */
        public function generate_api_token_html()
        {
            $this->log('    [Info] Entered generate_api_token_html()...');

            ob_start();

            // TODO: CSS Imports aren't optimal, but neither is this.  Maybe include the css to be css-minimized?
            wp_enqueue_style('font-awesome', '//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css');
            wp_enqueue_style('smartex-token', plugins_url('assets/css/style.css', __FILE__));
            wp_enqueue_script('smartex-pairing', plugins_url('assets/js/pairing.js', __FILE__), array('jquery'), null, true);
            wp_localize_script( 'smartex-pairing', 'SmartexAjax', array(
                'ajaxurl'     => admin_url( 'admin-ajax.php' ),
                'pairNonce'   => wp_create_nonce( 'smartex-pair-nonce' ),
                'revokeNonce' => wp_create_nonce( 'smartex-revoke-nonce' )
                )
            );

            $pairing_form = file_get_contents(plugin_dir_path(__FILE__).'templates/pairing.tpl');
            $token_format = file_get_contents(plugin_dir_path(__FILE__).'templates/token.tpl');

            ?>
            <tr valign="top">
                <th scope="row" class="titledesc">API Token:</th>
                <td class="forminp" id="smartex_api_token">
                    <div id="smartex_api_token_form">
                        <?php
                            if (true === empty($this->api_token)) {
                                echo sprintf($pairing_form, 'visible');
                                echo sprintf($token_format, 'hidden', '','');
                            } else {
                                echo sprintf($pairing_form, 'hidden');
                                echo sprintf($token_format, $this->api_network, $this->api_token_label, $this->api_sin);
                            }

                        ?>
                    </div>
                       <script type="text/javascript">
                        var ajax_loader_url = '<?php echo plugins_url('assets/img/ajax-loader.gif', __FILE__); ?>';
                    </script>
                </td>
            </tr>
            <?php

            $this->log('    [Info] Leaving generate_api_token_html()...');

            return ob_get_clean();
        }

        /**
         * HTML output for form field type `order_states`
         */
        public function generate_order_states_html()
        {
            $this->log('    [Info] Entered generate_order_states_html()...');

            ob_start();

            $smartex_statuses = array('new'=>'New Order', 'paid'=>'Paid', 'confirmed'=>'Confirmed', 'complete'=>'Complete', 'invalid'=>'Invalid');
            $df_statuses = array('new'=>'wc-on-hold', 'paid'=>'wc-processing', 'confirmed'=>'wc-processing', 'complete'=>'wc-completed', 'invalid'=>'wc-failed');

            $wc_statuses = wc_get_order_statuses();

            ?>
            <tr valign="top">
                <th scope="row" class="titledesc">Order States:</th>
                <td class="forminp" id="smartex_order_states">
                    <table cellspacing="0">
                        <?php

                            foreach ($smartex_statuses as $smartex_state => $smartex_name) {
                            ?>
                            <tr>
                            <th><?php echo $smartex_name; ?></th>
                            <td>
                                <select name="woocommerce_smartex_order_states[<?php echo $smartex_state; ?>]">
                                <?php

                                $order_states = get_option('woocommerce_smartex_settings');
                                $order_states = $order_states['order_states'];
                                foreach ($wc_statuses as $wc_state => $wc_name) {
                                    $current_option = $order_states[$smartex_state];

                                    if (true === empty($current_option)) {
                                        $current_option = $df_statuses[$smartex_state];
                                    }

                                    if ($current_option === $wc_state) {
                                        echo "<option value=\"$wc_state\" selected>$wc_name</option>\n";
                                    } else {
                                        echo "<option value=\"$wc_state\">$wc_name</option>\n";
                                    }
                                }

                                ?>
                                </select>
                            </td>
                            </tr>
                            <?php
                        }

                        ?>
                    </table>
                </td>
            </tr>
            <?php

            $this->log('    [Info] Leaving generate_order_states_html()...');

            return ob_get_clean();
        }

        /**
         * Save order states
         */
        public function save_order_states()
        {
            $this->log('    [Info] Entered save_order_states()...');

            $smartex_statuses = array(
                'new'      => 'New Order',
                'paid'      => 'Paid',
                'confirmed' => 'Confirmed',
                'complete'  => 'Complete',
                'invalid'   => 'Invalid',
            );

            $wc_statuses = wc_get_order_statuses();

            if (true === isset($_POST['woocommerce_smartex_order_states'])) {

                $smartex_settings = get_option('woocommerce_smartex_settings');
                $order_states = $smartex_settings['order_states'];

                foreach ($smartex_statuses as $smartex_state => $smartex_name) {
                    if (false === isset($_POST['woocommerce_smartex_order_states'][ $smartex_state ])) {
                        continue;
                    }

                    $wc_state = $_POST['woocommerce_smartex_order_states'][ $smartex_state ];

                    if (true === array_key_exists($wc_state, $wc_statuses)) {
                        $this->log('    [Info] Updating order state ' . $smartex_state . ' to ' . $wc_state);
                        $order_states[$smartex_state] = $wc_state;
                    }

                }
                $smartex_settings['order_states'] = $order_states;
                update_option('woocommerce_smartex_settings', $smartex_settings);
            }

            $this->log('    [Info] Leaving save_order_states()...');
        }

        /**
         * Validate API Token
         */
        public function validate_api_token_field()
        {
            return '';
        }

        /**
         * Validate Support Details
         */
        public function validate_support_details_field()
        {
            return '';
        }

        /**
         * Validate Order States
         */
        public function validate_order_states_field()
        {
            $order_states = $this->get_option('order_states');

            if ( isset( $_POST[ $this->plugin_id . $this->id . '_order_states' ] ) ) {
                $order_states = $_POST[ $this->plugin_id . $this->id . '_order_states' ];
            }
            return $order_states;
        }

        /**
         * Validate Notification URL
         */
        public function validate_url_field($key)
        {
            $url = $this->get_option($key);

            if ( isset( $_POST[ $this->plugin_id . $this->id . '_' . $key ] ) ) {
                 if (filter_var($_POST[ $this->plugin_id . $this->id . '_' . $key ], FILTER_VALIDATE_URL) !== false) {
                     $url = $_POST[ $this->plugin_id . $this->id . '_' . $key ];
                 } else {
                     $url = '';
                 }
             }
             return $url;
        }

        /**
         * Validate Redirect URL
         */
        public function validate_redirect_url_field()
        {
            $redirect_url = $this->get_option('redirect_url', '');

            if ( isset( $_POST['woocommerce_smartex_redirect_url'] ) ) {
                 if (filter_var($_POST['woocommerce_smartex_redirect_url'], FILTER_VALIDATE_URL) !== false) {
                     $redirect_url = $_POST['woocommerce_smartex_redirect_url'];
                 } else {
                     $redirect_url = '';
                 }
             }
             return $redirect_url;
        }

        /**
         * Output for the order received page.
         */
        public function thankyou_page($order_id)
        {
            $this->log('    [Info] Entered thankyou_page with order_id =  ' . $order_id);

            // Intentionally blank.

            $this->log('    [Info] Leaving thankyou_page with order_id =  ' . $order_id);
        }

        /**
         * Process the payment and return the result
         *
         * @param   int     $order_id
         * @return  array
         */
        public function process_payment($order_id)
        {
            $this->log('    [Info] Entered process_payment() with order_id = ' . $order_id . '...');

            if (true === empty($order_id)) {
                $this->log('    [Error] The Smartex payment plugin was called to process a payment but the order_id was missing.');
                throw new \Exception('The Smartex payment plugin was called to process a payment but the order_id was missing. Cannot continue!');
            }

            $order = wc_get_order($order_id);

            if (false === $order) {
                $this->log('    [Error] The Smartex payment plugin was called to process a payment but could not retrieve the order details for order_id ' . $order_id);
                throw new \Exception('The Smartex payment plugin was called to process a payment but could not retrieve the order details for order_id ' . $order_id . '. Cannot continue!');
            }

            $notification_url = $this->get_option('notification_url', WC()->api_request_url('WC_Gateway_Smartex'));
            $this->log('    [Info] Generating payment form for order ' . $order->get_order_number() . '. Notify URL: ' . $notification_url);

            // Mark new order according to user settings (we're awaiting the payment)
            $new_order_states = $this->get_option('order_states');
            $new_order_status = $new_order_states['new'];
            $order->update_status($new_order_status, 'Awaiting payment notification from Smartex.');

            $thanks_link = $this->get_return_url($order);

            $this->log('    [Info] The variable thanks_link = ' . $thanks_link . '...');

            // Redirect URL & Notification URL
            $redirect_url = $this->get_option('redirect_url', $thanks_link);
            $this->log('    [Info] The variable redirect_url = ' . $redirect_url  . '...');

            $this->log('    [Info] Notification URL is now set to: ' . $notification_url . '...');

            // Setup the currency
            $currency_code = get_woocommerce_currency();

            $this->log('    [Info] The variable currency_code = ' . $currency_code . '...');

            $currency = new \Smartex\Currency($currency_code);

            if (false === isset($currency) && true === empty($currency)) {
                $this->log('    [Error] The Smartex payment plugin was called to process a payment but could not instantiate a Currency object.');
                throw new \Exception('The Smartex payment plugin was called to process a payment but could not instantiate a Currency object. Cannot continue!');
            }

            // Get a Smartex Client to prepare for invoice creation
            $client = new \Smartex\Client\Client();

            if (false === isset($client) && true === empty($client)) {
                $this->log('    [Error] The Smartex payment plugin was called to process a payment but could not instantiate a client object.');
                throw new \Exception('The Smartex payment plugin was called to process a payment but could not instantiate a client object. Cannot continue!');
            }

            if ('livenet' === $this->api_network) {
                $client->setNetwork(new \Smartex\Network\Livenet());
                $this->log('    [Info] Set network to Livenet...');
            } else {
                $client->setNetwork(new \Smartex\Network\Testnet());
                $this->log('    [Info] Set network to Testnet...');
            }

            $curlAdapter = new \Smartex\Client\Adapter\CurlAdapter();

            if (false === isset($curlAdapter) || true === empty($curlAdapter)) {
                $this->log('    [Error] The Smartex payment plugin was called to process a payment but could not instantiate a CurlAdapter object.');
                throw new \Exception('The Smartex payment plugin was called to process a payment but could not instantiate a CurlAdapter object. Cannot continue!');
            }

            $client->setAdapter($curlAdapter);

            if (false === empty($this->api_key)) {
                $client->setPrivateKey($this->api_key);
            } else {
                $this->log('    [Error] The Smartex payment plugin was called to process a payment but could not set client->setPrivateKey to this->api_key. The empty() check failed!');
                throw new \Exception(' The Smartex payment plugin was called to process a payment but could not set client->setPrivateKey to this->api_key. The empty() check failed!');
            }

            if (false === empty($this->api_pub)) {
                $client->setPublicKey($this->api_pub);
            } else {
                $this->log('    [Error] The Smartex payment plugin was called to process a payment but could not set client->setPublicKey to this->api_pub. The empty() check failed!');
                throw new \Exception(' The Smartex payment plugin was called to process a payment but could not set client->setPublicKey to this->api_pub. The empty() check failed!');
            }

            if (false === empty($this->api_token)) {
                $client->setToken($this->api_token);
            } else {
                $this->log('    [Error] The Smartex payment plugin was called to process a payment but could not set client->setToken to this->api_token. The empty() check failed!');
                throw new \Exception(' The Smartex payment plugin was called to process a payment but could not set client->setToken to this->api_token. The empty() check failed!');
            }

            $this->log('    [Info] Key and token empty checks passed.  Parameters in client set accordingly...');

            // Setup the Invoice
            $invoice = new \Smartex\Invoice();

            if (false === isset($invoice) || true === empty($invoice)) {
                $this->log('    [Error] The Smartex payment plugin was called to process a payment but could not instantiate an Invoice object.');
                throw new \Exception('The Smartex payment plugin was called to process a payment but could not instantiate an Invoice object. Cannot continue!');
            } else {
                $this->log('    [Info] Invoice object created successfully...');
            }

            $order_number = $order->get_order_number();
            $invoice->setOrderId((string)$order_number);
            $invoice->setCurrency($currency);
            $invoice->setFullNotifications(true);

            // Add a priced item to the invoice
            $item = new \Smartex\Item();

            if (false === isset($item) || true === empty($item)) {
                $this->log('    [Error] The Smartex payment plugin was called to process a payment but could not instantiate an item object.');
                throw new \Exception('The Smartex payment plugin was called to process a payment but could not instantiate an item object. Cannot continue!');
            } else {
                $this->log('    [Info] Item object created successfully...');
            }

            if (true === isset($order->order_total) && false === empty($order->order_total)) {
                $item->setPrice($order->order_total);
            } else {
                $this->log('    [Error] The Smartex payment plugin was called to process a payment but could not set item->setPrice to order->order_total. The empty() check failed!');
                throw new \Exception('The Smartex payment plugin was called to process a payment but could not set item->setPrice to order->order_total. The empty() check failed!');
            }

            $invoice->setItem($item);

            // Add the Redirect and Notification URLs
            $invoice->setRedirectUrl($redirect_url);
            $invoice->setNotificationUrl($notification_url);
            $invoice->setTransactionSpeed($this->transaction_speed);

            try {
                $this->log('    [Info] Attempting to generate invoice for ' . $order->get_order_number() . '...');

                $invoice = $client->createInvoice($invoice);

                if (false === isset($invoice) || true === empty($invoice)) {
                    $this->log('    [Error] The Smartex payment plugin was called to process a payment but could not instantiate an invoice object.');
                    throw new \Exception('The Smartex payment plugin was called to process a payment but could not instantiate an invoice object. Cannot continue!');
                } else {
                    $this->log('    [Info] Call to generate invoice was successful: ' . $client->getResponse()->getBody());
                }
            } catch (\Exception $e) {
                $this->log('    [Error] Error generating invoice for ' . $order->get_order_number() . ', "' . $e->getMessage() . '"');
                error_log($e->getMessage());

                return array(
                    'result'    => 'success',
                    'messages'  => 'Sorry, but Ethereum checkout with Smartex does not appear to be working.'
                );
            }

            // Reduce stock levels
            $order->reduce_order_stock();

            // Remove cart
            WC()->cart->empty_cart();

            $this->log('    [Info] Leaving process_payment()...');

            // Redirect the customer to the Smartex invoice
            return array(
                'result'   => 'success',
                'redirect' => $invoice->getUrl(),
            );
        }

        public function ipn_callback()
        {
            $this->log('    [Info] Entered ipn_callback()...');

            // Retrieve the Invoice ID and Network URL from the supposed IPN data
            $post = file_get_contents("php://input");

            if (true === empty($post)) {
                $this->log('    [Error] No post data sent to IPN handler!');
                error_log('[Error] Smartex plugin received empty POST data for an IPN message.');

                wp_die('No post data');
            } else {
                $this->log('    [Info] The post data sent to IPN handler is present...');
            }

            $json = json_decode($post, true);

            if (true === empty($json)) {
                $this->log('    [Error] Invalid JSON payload sent to IPN handler: ' . $post);
                error_log('[Error] Smartex plugin received an invalid JSON payload sent to IPN handler: ' . $post);

                wp_die('Invalid JSON');
            } else {
                $this->log('    [Info] The post data was decoded into JSON...');
            }

            if (false === array_key_exists('id', $json)) {
                $this->log('    [Error] No invoice ID present in JSON payload: ' . var_export($json, true));
                error_log('[Error] Smartex plugin did not receive an invoice ID present in JSON payload: ' . var_export($json, true));

                wp_die('No Invoice ID');
            } else {
                $this->log('    [Info] Invoice ID present in JSON payload...');
            }

            if (false === array_key_exists('url', $json)) {
                $this->log('    [Error] No invoice URL present in JSON payload: ' . var_export($json, true));
                error_log('[Error] Smartex plugin did not receive an invoice URL present in JSON payload: ' . var_export($json, true));

                wp_die('No Invoice URL');
            } else {
                $this->log('    [Info] Invoice URL present in JSON payload...');
            }

            // Get a Smartex Client to prepare for invoice fetching
            $client = new \Smartex\Client\Client();

            if (false === isset($client) && true === empty($client)) {
                $this->log('    [Error] The Smartex payment plugin was called to handle an IPN but could not instantiate a client object.');
                throw new \Exception('The Smartex payment plugin was called to handle an IPN but could not instantiate a client object. Cannot continue!');
            } else {
                $this->log('    [Info] Created new Client object in IPN handler...');
            }

            if (false === strpos($json['url'], 'test')) {
                $network = new \Smartex\Network\Livenet();
                $this->log('    [Info] Set network to Livenet.');
            } else {
                $network = new \Smartex\Network\Testnet();
                $this->log('    [Info] Set network to Testnet.');
            }

            $this->log('    [Info] Checking IPN response is valid via ' . $network->getName() . '...');

            $client->setNetwork($network);

            $curlAdapter = new \Smartex\Client\Adapter\CurlAdapter();

            if (false === isset($curlAdapter) && true === empty($curlAdapter)) {
                $this->log('    [Error] The Smartex payment plugin was called to handle an IPN but could not instantiate a CurlAdapter object.');
                throw new \Exception('The Smartex payment plugin was called to handle an IPN but could not instantiate a CurlAdapter object. Cannot continue!');
            } else {
                $this->log('    [Info] Created new CurlAdapter object in IPN handler...');
            }

            // Setting the Adapter param to a new Smartex CurlAdapter object
            $client->setAdapter($curlAdapter);

            if (false === empty($this->api_key)) {
                $client->setPrivateKey($this->api_key);
            } else {
                $this->log('    [Error] The Smartex payment plugin was called to handle an IPN but could not set client->setPrivateKey to this->api_key. The empty() check failed!');
                throw new \Exception('The Smartex payment plugin was called to handle an IPN but could not set client->setPrivateKey to this->api_key. The empty() check failed!');
            }

            if (false === empty($this->api_pub)) {
                $client->setPublicKey($this->api_pub);
            } else {
                $this->log('    [Error] The Smartex payment plugin was called to handle an IPN but could not set client->setPublicKey to this->api_pub. The empty() check failed!');
                throw new \Exception('The Smartex payment plugin was called to handle an IPN but could not set client->setPublicKey to this->api_pub. The empty() check failed!');
            }

            if (false === empty($this->api_token)) {
                $client->setToken($this->api_token);
            } else {
                $this->log('    [Error] The Smartex payment plugin was called to handle an IPN but could not set client->setToken to this->api_token. The empty() check failed!');
                throw new \Exception('The Smartex payment plugin was called to handle an IPN but could not set client->setToken to this->api_token. The empty() check failed!');
            }

            $this->log('    [Info] Key and token empty checks passed.  Parameters in client set accordingly...');

            // Fetch the invoice from Smartex's server to update the order
            try {
                $invoice = $client->getInvoice($json['id']);

                if (true === isset($invoice) && false === empty($invoice)) {
                    $this->log('    [Info] The IPN check appears to be valid.');
                } else {
                    $this->log('    [Error] The IPN check did not pass!');
                    wp_die('Invalid IPN');
                }
            } catch (\Exception $e) {
                $error_string = 'IPN Check: Can\'t find invoice ' . $json['id'];
                $this->log("    [Error] $error_string");
                $this->log("    [Error] " . $e->getMessage());

                wp_die($e->getMessage());
            }

            $order_id = $invoice->getOrderId();

            if (false === isset($order_id) && true === empty($order_id)) {
                $this->log('    [Error] The Smartex payment plugin was called to process an IPN message but could not obtain the order ID from the invoice.');
                throw new \Exception('The Smartex payment plugin was called to process an IPN message but could not obtain the order ID from the invoice. Cannot continue!');
            } else {
                $this->log('    [Info] Order ID is: ' . $order_id);
            }

            // Creating a new WooCommerce Order object with $order_id
            $order = wc_get_order($order_id);

            if (false === isset($order) && true === empty($order)) {
                $this->log('    [Error] The Smartex payment plugin was called to process an IPN message but could not retrieve the order details for order_id ' . $order_id);
                throw new \Exception('The Smartex payment plugin was called to process an IPN message but could not retrieve the order details for order_id ' . $order_id . '. Cannot continue!');
            } else {
                $this->log('    [Info] Order details retrieved successfully...');
            }

            $current_status = $order->get_status();

            if (false === isset($current_status) && true === empty($current_status)) {
                $this->log('    [Error] The Smartex payment plugin was called to process an IPN message but could not obtain the current status from the order.');
                throw new \Exception('The Smartex payment plugin was called to process an IPN message but could not obtain the current status from the order. Cannot continue!');
            } else {
                $this->log('    [Info] The current order status for this order is ' . $current_status);
            }

            $order_states = $this->get_option('order_states');

            $new_order_status = $order_states['new'];
            $paid_status      = $order_states['paid'];
            $confirmed_status = $order_states['confirmed'];
            $complete_status  = $order_states['complete'];
            $invalid_status   = $order_states['invalid'];

            $checkStatus = $invoice->getStatus();

            if (false === isset($checkStatus) && true === empty($checkStatus)) {
                $this->log('    [Error] The Smartex payment plugin was called to process an IPN message but could not obtain the current status from the invoice.');
                throw new \Exception('The Smartex payment plugin was called to process an IPN message but could not obtain the current status from the invoice. Cannot continue!');
            } else {
                $this->log('    [Info] The current order status for this invoice is ' . $checkStatus);
            }

            // Based on the payment status parameter for this
            // IPN, we will update the current order status.
            switch ($checkStatus) {

                // The "paid" IPN message is received almost
                // immediately after the Smartex invoice is paid.
                case 'paid':

                    $this->log('    [Info] IPN response is a "paid" message.');

                    if ($current_status == $complete_status       ||
                        'wc_'.$current_status == $complete_status ||
                        $current_status == 'completed')
                    {
                        $error_string = 'Paid IPN, but order has status: '.$current_status;
                        $this->log("    [Warning] $error_string");

                    } else {
                        $this->log('    [Info] This order has not been updated yet so setting new status...');

                        $order->update_status($paid_status);
                        $order->add_order_note(__('Smartex invoice paid. Awaiting network confirmation and payment completed status.', 'smartex'));
                    }

                    break;

                // The "confirmed" status is sent when the payment is
                // confirmed based on your transaction speed setting.
                case 'confirmed':

                    $this->log('    [Info] IPN response is a "confirmed" message.');

                    if ($current_status == $complete_status       ||
                        'wc_'.$current_status == $complete_status ||
                        $current_status == 'completed')
                    {
                        $error_string = 'Confirmed IPN, but order has status: '.$current_status;
                        $this->log("    [Warning] $error_string");

                    } else {
                        $this->log('    [Info] This order has not been updated yet so setting confirmed status...');

                        $order->update_status($confirmed_status);
                        $order->add_order_note(__('Smartex invoice confirmed. Awaiting payment completed status.', 'smartex'));
                    }

                    break;

                // The complete status is when the Ethereum network
                // obtains 15 confirmations for this transaction.
                case 'complete':

                    $this->log('    [Info] IPN response is a "complete" message.');

                    if ($current_status == $complete_status       ||
                        'wc_'.$current_status == $complete_status ||
                        $current_status == 'completed')
                    {
                        $error_string = 'Complete IPN, but order has status: '.$current_status;
                        $this->log("    [Warning] $error_string");

                    } else {
                        $this->log('    [Info] This order has not been updated yet so setting complete status...');

                        $order->payment_complete();
                        $order->update_status($complete_status);
                        $order->add_order_note(__('Smartex invoice payment completed. Payment credited to your merchant account.', 'smartex'));
                    }

                    break;

                // This order is invalid for some reason.
                // Either it's a double spend or some other
                // problem occurred.
                case 'invalid':

                    $this->log('    [Info] IPN response is a "invalid" message.');

                    if ($current_status == $complete_status       ||
                        'wc_'.$current_status == $complete_status ||
                        $current_status == 'completed')
                    {
                        $error_string = 'Paid IPN, but order has status: ' . $current_status;
                        $this->log("    [Warning] $error_string");

                    } else {
                        $this->log('    [Info] This order has a problem so setting "invalid" status...');

                        $order->update_status($invalid_status, __('Payment is invalid for this order! The payment was not confirmed by the network within 1 hour. Do not ship the product for this order!', 'smartex'));
                    }

                    break;

                // There was an unknown message received.
                default:

                    $this->log('    [Info] IPN response is an unknown message type. See error message below:');

                    $error_string = 'Unhandled invoice status: ' . $invoice->getStatus();
                    $this->log("    [Warning] $error_string");
            }

            $this->log('    [Info] Leaving ipn_callback()...');
        }

        public function log($message)
        {
            if (true === isset($this->debug) && 'yes' == $this->debug) {
                if (false === isset($this->logger) || true === empty($this->logger)) {
                    $this->logger = new WC_Logger();
                }

                $this->logger->add('smartex', $message);
            }
        }

        public function smartex_encrypt($data)
        {
            if (false === isset($data) || true === empty($data)) {
                throw new \Exception('The Smartex payment plugin was called to encrypt data but no data was passed!');
            }

            $this->log('    [Info] Entered smartex_encrypt...');

            $mcrypt_ext = new \Smartex\Crypto\McryptExtension();
            $fingerprint = sha1(sha1(__DIR__));

            if (true === isset($fingerprint) &&
                true === isset($mcrypt_ext)  &&
                strlen($fingerprint) > 24)
            {
                $fingerprint = substr($fingerprint, 0, 24);

                if (false === isset($fingerprint) || true === empty($fingerprint)) {
                    throw new \Exception('The Smartex payment plugin was called to encrypt data but could not generate a fingerprint parameter!');
                }

                $encrypted = $mcrypt_ext->encrypt(base64_encode(serialize($data)), $fingerprint, '00000000');

                if (true === empty($encrypted)) {
                    throw new \Exception('The Smartex payment plugin was called to encrypt a serialized object and failed!');
                }

                $this->log('    [Info] Leaving class level smartex_encrypt...');

                return $encrypted;
            } else {
                $this->log('    [Error] Invalid server fingerprint generated in smartex_encrypt()');
                wp_die('Invalid server fingerprint generated');
            }
        }

        public function smartex_decrypt($encrypted)
        {
            if (false === isset($encrypted) || true === empty($encrypted)) {
                throw new \Exception('The Smartex payment plugin was called to decrypt data but no data was passed!');
            }

            $this->log('    [Info] Entered class level smartex_decrypt...');

            $mcrypt_ext = new \Smartex\Crypto\McryptExtension();
            $fingerprint = sha1(sha1(__DIR__));

            if (true === isset($fingerprint) &&
                true === isset($mcrypt_ext)  &&
                strlen($fingerprint) > 24)
            {
                $fingerprint = substr($fingerprint, 0, 24);

                if (false === isset($fingerprint) || true === empty($fingerprint)) {
                    throw new \Exception('The Smartex payment plugin was called to decrypt data but could not generate a fingerprint parameter!');
                }

                $decrypted = base64_decode($mcrypt_ext->decrypt($encrypted, $fingerprint, '00000000'));

                // Strict base64 char check
                if (false === base64_decode($decrypted, true)) {
                    $this->log('    [Warning] In smartex_decrypt: data appears to have already been decrypted. Strict base64 check failed.');
                } else {
                    $decrypted = base64_decode($decrypted);
                }

                if (true === empty($decrypted)) {
                    throw new \Exception('The Smartex payment plugin was called to unserialize a decrypted object and failed! The decrypt function was called with "' . $encrypted . '"');
                }

                $this->log('    [Info] Leaving class level smartex_decrypt...');

                return unserialize($decrypted);
            } else {
                $this->log('    [Error] Invalid server fingerprint generated in smartex_decrypt()');
                wp_die('Invalid server fingerprint generated');
            }
        }
    }

    /**
    * Add Smartex Payment Gateway to WooCommerce
    **/
    function wc_add_smartex($methods)
    {
        $methods[] = 'WC_Gateway_Smartex';

        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'wc_add_smartex');

    /**
     * Add Settings link to the plugin entry in the plugins menu
     **/
    add_filter('plugin_action_links', 'smartex_plugin_action_links', 10, 2);

    function smartex_plugin_action_links($links, $file)
    {
        static $this_plugin;

        if (false === isset($this_plugin) || true === empty($this_plugin)) {
            $this_plugin = plugin_basename(__FILE__);
        }

        if ($file == $this_plugin) {
            $log_file = 'smartex-' . sanitize_file_name( wp_hash( 'smartex' ) ) . '-log';
            $settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wc-settings&tab=checkout&section=wc_gateway_smartex">Settings</a>';
            $logs_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wc-status&tab=logs&log_file=' . $log_file . '">Logs</a>';
            array_unshift($links, $settings_link, $logs_link);
        }

        return $links;
    }

    // TODO: Try to find a way to make it work within the WC_Gateway_Smartex class
    add_action('wp_ajax_smartex_pair_code', 'ajax_smartex_pair_code');
    add_action('wp_ajax_smartex_revoke_token', 'ajax_smartex_revoke_token');
    add_action('wp_ajax_smartex_create_invoice', 'ajax_smartex_create_invoice');

    function ajax_smartex_pair_code()
    {
        $nonce = $_POST['pairNonce'];
        if ( ! wp_verify_nonce( $nonce, 'smartex-pair-nonce' ) ) {
            die ( 'Unauthorized!');
        }

        if ( current_user_can( 'manage_options' ) ) {

            if (true === isset($_POST['pairing_code']) && trim($_POST['pairing_code']) !== '') {
                // Validate the Pairing Code
                $pairing_code = trim($_POST['pairing_code']);
            } else {
                wp_send_json_error("Pairing Code is required");
                return;
            }

            if (!preg_match('/^[a-zA-Z0-9]{7}$/', $pairing_code)) {
                wp_send_json_error("Invalid Pairing Code");
                return;
            }

            // Validate the Network
            $network = ($_POST['network'] === 'livenet') ? 'livenet' : 'testnet';

            // Generate Private Key
            $key = new \Smartex\PrivateKey();

            if (true === empty($key)) {
                throw new \Exception('The Smartex payment plugin was called to process a pairing code but could not instantiate a PrivateKey object. Cannot continue!');
            }

            $key->generate();

            // Generate Public Key
            $pub = new \Smartex\PublicKey();

            if (true === empty($pub)) {
                throw new \Exception('The Smartex payment plugin was called to process a pairing code but could not instantiate a PublicKey object. Cannot continue!');
            }

            $pub->setPrivateKey($key);
            $pub->generate();

            // Get SIN Format
            $sin = new \Smartex\SinKey();

            if (true === empty($sin)) {
                throw new \Exception('The Smartex payment plugin was called to process a pairing code but could not instantiate a SinKey object. Cannot continue!');
            }

            $sin->setPublicKey($pub);
            $sin->generate();

            // Create an API Client
            $client = new \Smartex\Client\Client();

            if (true === empty($client)) {
                throw new \Exception('The Smartex payment plugin was called to process a pairing code but could not instantiate a Client object. Cannot continue!');
            }

            if ($network === 'livenet') {
                $client->setNetwork(new \Smartex\Network\Livenet());
            } else {
                $client->setNetwork(new \Smartex\Network\Testnet());
            }

            $curlAdapter = new \Smartex\Client\Adapter\CurlAdapter();

            if (true === empty($curlAdapter)) {
                throw new \Exception('The Smartex payment plugin was called to process a pairing code but could not instantiate a CurlAdapter object. Cannot continue!');
            }

            $client->setAdapter($curlAdapter);

            $client->setPrivateKey($key);
            $client->setPublicKey($pub);

            // Sanitize label
            $label = preg_replace('/[^a-zA-Z0-9 \-\_\.]/', '', get_bloginfo());
            $label = substr('WooCommerce - '.$label, 0, 59);

            try {
                $token = $client->createToken(
                    array(
                        'id'          => (string) $sin,
                        'pairingCode' => $pairing_code,
                        'label'       => $label,
                    )
                );
            } catch (\Exception $e) {
                wp_send_json_error($e->getMessage());
                return;
            }

            update_option('woocommerce_smartex_key', smartex_encrypt($key));
            update_option('woocommerce_smartex_pub', smartex_encrypt($pub));
            update_option('woocommerce_smartex_sin', (string)$sin);
            update_option('woocommerce_smartex_token', smartex_encrypt($token));
            update_option('woocommerce_smartex_label', $label);
            update_option('woocommerce_smartex_network', $network);

            wp_send_json(array('sin' => (string) $sin, 'label' => $label, 'network' => $network));
        }
        exit;
    }

    function ajax_smartex_revoke_token()
    {
        $nonce = $_POST['revokeNonce'];
        if ( ! wp_verify_nonce( $nonce, 'smartex-revoke-nonce' ) ) {
            die ( 'Unauthorized!');
        }

        if ( current_user_can( 'manage_options' ) ) {
            update_option('woocommerce_smartex_key', null);
            update_option('woocommerce_smartex_pub', null);
            update_option('woocommerce_smartex_sin', null);
            update_option('woocommerce_smartex_token', null);
            update_option('woocommerce_smartex_label', null);
            update_option('woocommerce_smartex_network', 'testnet');

            wp_send_json(array('success'=>'Token Revoked!'));
        }
        exit;
    }

    function smartex_encrypt($data)
    {
        if (false === isset($data) || true === empty($data)) {
            throw new \Exception('The Smartex payment plugin was called to encrypt data but no data was passed!');
        }

        $mcrypt_ext = new \Smartex\Crypto\McryptExtension();
        $fingerprint = sha1(sha1(__DIR__));

        if (true === isset($fingerprint) &&
            true === isset($mcrypt_ext)  &&
            strlen($fingerprint) > 24)
        {
            $fingerprint = substr($fingerprint, 0, 24);

            if (false === isset($fingerprint) || true === empty($fingerprint)) {
                throw new \Exception('The Smartex payment plugin was called to encrypt data but could not generate a fingerprint parameter!');
            }

            $encrypted = $mcrypt_ext->encrypt(base64_encode(serialize($data)), $fingerprint, '00000000');

            if (true === empty($encrypted)) {
                throw new \Exception('The Smartex payment plugin was called to serialize an encrypted object and failed!');
            }

            return $encrypted;
        } else {
            wp_die('Invalid server fingerprint generated');
        }
    }

    function smartex_decrypt($encrypted)
    {
        if (false === isset($encrypted) || true === empty($encrypted)) {
            throw new \Exception('The Smartex payment plugin was called to decrypt data but no data was passed!');
        }

        $mcrypt_ext = new \Smartex\Crypto\McryptExtension();
        $fingerprint = sha1(sha1(__DIR__));

        if (true === isset($fingerprint) &&
            true === isset($mcrypt_ext)  &&
            strlen($fingerprint) > 24)
        {
            $fingerprint = substr($fingerprint, 0, 24);

            if (false === isset($fingerprint) || true === empty($fingerprint)) {
                throw new \Exception('The Smartex payment plugin was called to decrypt data but could not generate a fingerprint parameter!');
            }

            $decrypted = base64_decode($mcrypt_ext->decrypt($encrypted, $fingerprint, '00000000'));

            // Strict base64 char check
            if (false === base64_decode($decrypted, true)) {
                $error_string .= '    [Warning] In smartex_decrypt: data appears to have already been decrypted. Strict base64 check failed.';
            } else {
                $decrypted = base64_decode($decrypted);
            }

            if (true === empty($decrypted)) {
                throw new \Exception('The Smartex payment plugin was called to unserialize a decrypted object and failed! The decrypt function was called with "' . $encrypted . '"');
            }

            return unserialize($decrypted);
        } else {
            wp_die('Invalid server fingerprint generated');
        }
    }

}

function woocommerce_smartex_failed_requirements()
{
    global $wp_version;
    global $woocommerce;

    $errors = array();

    // PHP 5.4+ required
    if (true === version_compare(PHP_VERSION, '5.4.0', '<')) {
        $errors[] = 'Your PHP version is too old. The Smartex payment plugin requires PHP 5.4 or higher to function. Please contact your web server administrator for assistance.';
    }

    // Wordpress 3.9+ required
    if (true === version_compare($wp_version, '3.9', '<')) {
        $errors[] = 'Your WordPress version is too old. The Smartex payment plugin requires Wordpress 3.9 or higher to function. Please contact your web server administrator for assistance.';
    }

    // WooCommerce required
    if (true === empty($woocommerce)) {
        $errors[] = 'The WooCommerce plugin for WordPress needs to be installed and activated. Please contact your web server administrator for assistance.';
    }elseif (true === version_compare($woocommerce->version, '2.2', '<')) {
        $errors[] = 'Your WooCommerce version is too old. The Smartex payment plugin requires WooCommerce 2.2 or higher to function. Your version is '.$woocommerce->version.'. Please contact your web server administrator for assistance.';
    }

    // GMP or BCMath required
    if (false === extension_loaded('gmp') && false === extension_loaded('bcmath')) {
        $errors[] = 'The Smartex payment plugin requires the GMP or BC Math extension for PHP in order to function. Please contact your web server administrator for assistance.';
    }

    // Curl required
    if (false === extension_loaded('curl')) {
        $errors[] = 'The Smartex payment plugin requires the Curl extension for PHP in order to function. Please contact your web server administrator for assistance.';
    }

    if (false === empty($errors)) {
        return implode("<br>\n", $errors);
    } else {
        return false;
    }

}

// Activating the plugin
function woocommerce_smartex_activate()
{
    // Check for Requirements
    $failed = woocommerce_smartex_failed_requirements();

    $plugins_url = admin_url('plugins.php');

    // Requirements met, activate the plugin
    if ($failed === false) {

        // Deactivate any older versions that might still be present
        $plugins = get_plugins();

        foreach ($plugins as $file => $plugin) {
            if ('Smartex Woocommerce' === $plugin['Name'] && true === is_plugin_active($file)) {
                deactivate_plugins(plugin_basename(__FILE__));
                wp_die('Smartex for WooCommerce requires that the old plugin, <b>Smartex Woocommerce</b>, is deactivated and deleted.<br><a href="'.$plugins_url.'">Return to plugins screen</a>');

            }
        }

        // Fix transaction_speed from older versions
        $settings = get_option('woocommerce_smartex');
        if (true === isset($settings) && true === is_string($settings)) {
            $settings_array = @unserialize($settings);
            if (false !== $settings_array && true === isset($settings_array['transactionSpeed'])) {
                $settings_array['transaction_speed'] = $settings_array['transactionSpeed'];
                unset($settings_array['transactionSpeed']);
                update_option('woocommerce_smartex', serialize($settings));
            }
        }

        update_option('woocommerce_smartex_version', '1.0.0');

    } else {
        // Requirements not met, return an error message
        wp_die($failed . '<br><a href="'.$plugins_url.'">Return to plugins screen</a>');
    }
}
