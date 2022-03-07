<?php
/**
 * Plugin Name: Coinsuites Pay
 * Plugin URI: https://thepatientoffer.com/wordpress/woocommerce/
 * Description: Accept all major crypto currencies directly on your WooCommerce site in a seamless and secure checkout environment with Coinsuites Pay.
 * Version: 1.0.0
 * Author: Coinsuites Pay
 * Author URI: https://coinsuites.com/
 * License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: coinsuites-pay
 * 
 * @package WordPress
 * @author Coinsuites Pay
 * @since 1.0.0
 */



/**
 * Coinsuites Pay Commerce Class
 */
class WC_CoinsuitesPay {


	/**
	 * Constructor
	 */
	public function __construct(){
		define( 'WC_CoinsuitesPay_VERSION', '1.0.0' );
		define( 'WC_CoinsuitesPay_TEMPLATE_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/templates/' );
		define( 'WC_CoinsuitesPay_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
		define( 'WC_CoinsuitesPay_PLUGIN_DIR', plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) . '/' );
		define( 'WC_CoinsuitesPay_MAIN_FILE', __FILE__ );

		// Actions
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
		add_action( 'plugins_loaded', array( $this, 'init' ), 0 );
		add_filter( 'woocommerce_payment_gateways', array( $this, 'register_gateway' ) );
		//add_action( 'wp_enqueue_scripts', array( $this, 'add_bac_scripts' ) );

	}

	/**
	 * Add links to plugins page for settings and documentation
	 * @param  array $links
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$subscriptions = ( class_exists( 'WC_Subscriptions_Order' ) ) ? '_subscriptions' : '';
		if ( class_exists( 'WC_Subscriptions_Order' ) && ! function_exists( 'wcs_create_renewal_order' ) ) {
			$subscriptions = '_subscriptions_deprecated';
		}
		$plugin_links = array(
			'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_gateway_coinsuites_pay' . $subscriptions ) . '">' . __( 'Settings', 'wc-gateway-coinsuites-pay' ) . '</a>',
			'<a href="https://coinsuites.com/">' . __( 'Support', 'wc-gateway-coinsuites-pay' ) . '</a>',
			'<a href="https://coinsuites.com/">' . __( 'Docs', 'wc-gateway-coinsuites-pay' ) . '</a>'
		);
		return array_merge( $plugin_links, $links );
	}

	/**
	 * Init localisations and files
	 */
	public function init() {

		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}

		// Includes
		include_once( 'includes/class-wc-gateway-coinsuites-pay.php' );

		// Localisation
		load_plugin_textdomain( 'wc-gateway-coinsuites-pay', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	}

	/**
	 * Register the gateway for use
	 */
	public function register_gateway( $methods ) {

		$methods[] = 'WC_Gateway_Coinsuites_Pay';

		return $methods;

	}


	/**
	 * Include jQuery and our scripts
	 */
	function add_coinsuites_pay_scripts() {

		wp_enqueue_style( 'coinsuitespaystyle', WC_CoinsuitesPay_PLUGIN_DIR . 'css/coinsuitespaystyle.css', false );
		wp_enqueue_script( 'coinsuitespayscript', WC_CoinsuitesPay_PLUGIN_DIR . 'js/coinsuitesScript.js', array( 'jquery' ), WC_CoinsuitesPay_VERSION, true );

	}

	/**
	 * Check if the user has any billing records in the Customer Vault
	 *
	 * @param $user_id
	 *
	 * @return bool
	 */
	function user_has_stored_data( $user_id ) {
		return get_user_meta( $user_id, 'customer_vault_ids', true ) != null;
	}


}

new WC_CoinsuitesPay();
