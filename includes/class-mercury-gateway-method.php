<?php
/**
 * Created by 2vModules.
 * User: dudenko.vadim@gmail.com
 * Date: 08.12.2020
 * Time: 15:14
 */

defined('ABSPATH') || exit;

use MercuryCash\SDK\Adapter;
use MercuryCash\SDK\Auth\APIKey;
use MercuryCash\SDK\Endpoints\Transaction;

class Mercury_Gateway_Method extends WC_Payment_Gateway
{
    public $crypto = [
        'ETH' => 'ethereum',
        'BTC' => 'bitcoin',
        'DASH' => 'dash'
    ];

    public $mercury_currence = [];

    public function __construct() {
        $this->id = 'mercury'; // payment gateway plugin ID
        $this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
        $this->has_fields = true; // in case you need a custom credit card form
        $this->method_title = 'Mercury Gateway';
        $this->method_description = 'Description of mercury payment gateway'; // will be displayed on the options
        // page

        // gateways can support subscriptions, refunds, saved payment methods,
        // but in this tutorial we begin with simple payments
        $this->supports = array(
            'products'
        );

        // Method with all the options fields
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();
        $this->title = $this->get_option( 'title' );
        $this->description = $this->get_option( 'description' );
        $this->mercury_success = $this->get_option( 'mercury_success' ) ?? 0;

        $this->BTC = $this->get_option( 'bitcoinmin' );
        $this->ETH = $this->get_option( 'ethereummin' );
        $this->DASH = $this->get_option( 'dashmin' );

        $this->pending_set = $this->get_option( 'pending_set' );

        $this->enabled = $this->get_option( 'enabled' );
        $this->order_button_text = $this->get_option( 'button_text' );
        $this->testmode = 'yes' === $this->get_option( 'testmode' );
        $this->private_key = $this->testmode ? $this->get_option( 'test_private_key' ) : $this->get_option( 'private_key' );
        $this->publishable_key = $this->testmode ? $this->get_option( 'test_publishable_key' ) : $this->get_option( 'publishable_key' );

        $this->getMercuryCur();

        add_action('wp_footer', function(){
             printf('<div id="mercury-cash"></div>');
        });

        // This action hook saves the settings
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

        //mercury_assets
        $script_url = MERCURY_GATEWAY_URL . "assets/js/mercury.js";
        $script_url_qr = MERCURY_GATEWAY_URL . "mercury-cash-react/build/static/js/main.9c786243.js";
        $style_url = MERCURY_GATEWAY_URL . "mercury-cash-react/build/static/css/main.5b50619d.css";

        wp_enqueue_script('woocommerce-mercury-qr', $script_url_qr, array('jquery'),  '1', true );
        wp_enqueue_script('woocommerce-mercury', $script_url, array('jquery'),  '1', true  );
        wp_enqueue_style('woocommerce-mercury', $style_url);

        wp_localize_script( 'woocommerce-mercury', 'mercury_param', array(
            'time' => $this->pending_set,
            'btc' => $this->BTC,
            'eth' => $this->ETH,
            'dash' => $this->DASH,
            'cart_price' => (float) WC()->cart->total,
            'currency' => get_option('woocommerce_currency'),
            'curr_symbol' => get_woocommerce_currency_symbol(get_option('woocommerce_currency'))
        ) );

        // get currences
        add_action( 'woocommerce_api_get_cur', array($this, 'get_currency'));

        // create and put transaction
        add_action( 'woocommerce_api_create_transaction', array($this, 'createTransaction'));

        // check status
        add_action( 'woocommerce_api_status', array($this, 'checkStatus'));

        // deleting paymentgateway if woo currency not exist in mercury currencies
        add_filter( 'woocommerce_available_payment_gateways', [$this, 'check_current_cur'] );

        add_action('woocommerce_after_checkout_validation', array($this, 'add_fake_error'));
    }

    public function add_fake_error() {
        if (isset($_POST['payment_method_mercury_validate']) || $_POST['payment_method_mercury_validate'] == "1") {
            wc_add_notice("<span class='mercury_fake_error'>mercury_fake_error</span>", 'error');
        }
    }


    public function admin_options(){
        parent::admin_options();

        if($this->publishable_key){
            $api_key = new APIKey($this->publishable_key, $this->private_key);
            $adapter = new Adapter($api_key, 'https://api-way.mercurydev.tk');
            $endpoint = new Transaction($adapter);

            try {
                $endpoint->status(1);
            } catch (\GuzzleHttp\Exception\ServerException $e) {
                $response = $e->getResponse();
                $this->add_error("Wrong keys for integration");
                $this->display_errors();
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                $response = $e->getResponse();
                if($response->getStatusCode() != 400) {
                    $this->add_error("Wrong keys for integration");
                }
                $this->display_errors();
            } catch (Exception $e) {

            }
        }
    }

    /**
     * Plugin options, we deal with it in Step 3 too
     */
    public function init_form_fields(){

        $this->form_fields = array(
            'enabled' => array(
                'title'       => 'Enable/Disable',
                'label'       => 'Enable Mercury Gateway',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ),
            'title' => array(
                'title'       => 'Title',
                'type'        => 'text',
                'description' => 'This controls the title which the user sees during checkout.',
                'default'     => 'Pay With Crypto',
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => 'Description',
                'type'        => 'textarea',
                'description' => 'This controls the description which the user sees during checkout.',
                'default'     => 'Powered by Mercury Gate',
            ),
            'button_text' => array(
                'title'       => 'Button text',
                'type'        => 'text',
                'description' => 'This control changes the button text on the chackout page',
                'default'     => 'Pay with Crypto',
            ),
            'bitcoinmin' => array(
                'title'       => 'Set min amount of cart for Bitcoin',
                'type'        => 'text',
                'default'     => '2',
            ),
            'ethereummin' => array(
                'title'       => 'Set min amount of cart for Ethereum',
                'type'        => 'text',
                'default'     => '2',
            ),
            'dashmin' => array(
                'title'       => 'Set min amount of cart for DASH',
                'type'        => 'text',
                'default'     => '2',
            ),
            'testmode' => array(
                'title'       => 'Test mode',
                'label'       => 'Enable Test Mode',
                'type'        => 'checkbox',
                'description' => 'Place the payment gateway in test mode using test API keys.',
                'default'     => 'yes',
                'desc_tip'    => true,
            ),
            'test_publishable_key' => array(
                'title'       => 'Test Publishable Key',
                'type'        => 'text'
            ),
            'test_private_key' => array(
                'title'       => 'Test Private Key',
                'type'        => 'password',
            ),
            'publishable_key' => array(
                'title'       => 'Live Publishable Key',
                'type'        => 'text'
            ),
            'private_key' => array(
                'title'       => 'Live Private Key',
                'type'        => 'password'
            ),
            'pending_set' => array(
                'title'       => 'Set milliseconds for pending',
                'type'        => 'number',
                'default'     => '2000',
            ),
        );

    }

    public function process_payment( $order_id ) {
        global $woocommerce;
        // we need it to get any order detailes
        $order = wc_get_order( $order_id );


        $order->payment_complete();
        wc_reduce_stock_levels($order_id);

        // some notes to customer (replace true with false to make it private)
        $order->add_order_note( 'Hey, your order is paid! Thank you!', true );

        // Empty cart
        $woocommerce->cart->empty_cart();

        // Redirect to the thank you page
        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url( $order )
        );
    }

    public function getMercuryCur(){

        if(empty($this->mercury_currence)) {
            $responce = wp_remote_get('https://api.mercury.cash/api/price');
            $body = wp_remote_retrieve_body($responce);
            $body = json_decode($body, true);
            $this->mercury_currence = $body['data'];
        }
        //todo запрос на получение валюты
        return $this->mercury_currence;
    }

    public function check_current_cur( $gateways ) {
        $current_currency = get_option('woocommerce_currency');
        $data = $this->getMercuryCur();
        if ( isset( $gateways['mercury'] ) &&  !array_key_exists($current_currency, $data) || !$this->publishable_key) {
            unset( $gateways['mercury'] );
        }
        return $gateways;
    }
    /**
     * return info about payment buttons
     */
    public function get_currency(){
        $order_price = WC()->cart->total;
        $data = $this->getMercuryCur();
        $current_currency = get_option('woocommerce_currency');
        $data = $data[$current_currency];

        foreach ($data as $key => $arr) {
            if ($key == 'exchange') continue;
            $arr['cart_amount'] = (float) $order_price;
            $arr['minprice'] = (float) $this->{$key};
            $arr['shop_currency'] = get_option('woocommerce_currency');
            $data[$key] = $arr;
        }
        wp_send_json_success($data);
    }

    public function checkStatus(){
        if(isset($_POST['uuid'])) {
            $uuid = $_POST['uuid'];
            $api_key = new APIKey($this->publishable_key, $this->private_key);
            $adapter = new Adapter($api_key, 'https://api-way.mercurydev.tk');
            $endpoint = new Transaction($adapter);

            $status = $endpoint->status($uuid);

            wp_send_json_success([
                'status' => $status->getStatus(),
                'confirmations' => $status->getConfirmations(),
            ]);
        }
    }

    public function createTransaction(){
        if(isset($_POST['email']) && isset($_POST['crypto']) && isset($_POST['currency'])) {
            $api_key = new APIKey($this->publishable_key, $this->private_key);
            $adapter = new Adapter($api_key, 'https://api-way.mercurydev.tk');
            $endpoint = new Transaction($adapter);


            $crypto_name = $this->crypto[$_POST['crypto']];
            $data = [
                'email' => $_POST['email'],
                'crypto' => $_POST['crypto'],
                'fiat' => $_POST['currency'],
                'amount' => (float) WC()->cart->total,
                'tip' => 0,
            ];
            $transaction = $endpoint->create($data);

            $endpoint->process($transaction->getUuid());

            $qrCodeText = "";
            $address = $transaction->getAddress();
            $amount = $transaction->getCryptoAmount();
            $qrCodeText .= $crypto_name . ":" . $address . "?";
            $qrCodeText .= "amount=" . $amount . "&";
            $qrCodeText .= "cryptoCurrency=" . $_POST['crypto'];

            wp_send_json_success([
                'uuid' => $transaction->getUuid(),
                'cryptoAmount' => $amount,
                'fiatIsoCode' => $transaction->getFiatIsoCode(),
                'fiatAmount' => $transaction->getFiatAmount(),
                'address' => $address,
                'networkFee' => $transaction->getFee(),
                'exchangeRate' => $transaction->getRate(),
                'cryptoCurrency' => $_POST['crypto'],
                'qrCodeText' => $qrCodeText,
            ]);
        }
    }

}
