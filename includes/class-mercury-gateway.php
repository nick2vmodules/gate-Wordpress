<?php
/**
 * Created by 2vModules.
 * User: dudenko.vadim@gmail.com
 * Date: 08.12.2020
 * Time: 15:14
 */


defined('ABSPATH') || exit;

class Mercury_Gateway
{

    protected static $_instance = null;

    public function __construct()
    {
        $this->getModules('includes/class-mercury-gateway-method.php');
        add_action('init', array($this, 'mercury_gateway_init'), 5);
        add_filter( 'woocommerce_payment_gateways', array($this, 'addGateway') );
    }

    public static function instance()
    {
        if(self::$_instance === null)
        {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function mercury_gateway_init()
    {

        add_filter('woocommerce_payment_gateways', array($this, 'addGateway'), 10);
    }

    public function addGateway($gateways)
    {
        $gateways[] = 'Mercury_Gateway_Method';
        return $gateways;
    }

    public function getModules($mod)
    {
        $module = MERCURY_GATEWAY_PLUGIN_DIR . $mod;
        require_once $module;
    }
}
