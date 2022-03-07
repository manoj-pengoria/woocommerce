<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Gateway_Coinsuites_Pay class.
 *
 * @since 2.0.0
 * @extends WC_Payment_Gateway
 */
class WC_Gateway_Coinsuites_Pay extends WC_Payment_Gateway {
	
	/** @var string user name */
	var $username;

	/** @var string password */
	var $password;

	/** @var  string save debug information */
	var $debug;

	/** @var  string save order */
	var $order;

	/** @var  array save response */
	var $address;

	/**
	 * Constructor
	 */
	public function __construct() {
		// Register plugin information
		$this->id         = 'coinsuitespay';
		$this->has_fields = true;

		// Create plugin fields and settings
		$this->init_form_fields();
		$this->init_settings();

		// Get setting values
		foreach ( $this->settings as $key => $val ) $this->$key = $val;

		// Load plugin checkout icon
		//$this->icon = WC_CoinsuitesPay_PLUGIN_URL . '/images/cards.png';

		$this->response_url	    = add_query_arg( 'wc-api', 'WC_Gateway_Coinsuites_Pay', home_url( '/' ) );

		// Add hooks
		add_action( 'admin_notices',                                            array( $this, 'coinsuites_pay_commerce_ssl_check' ) );
		//add_action( 'woocommerce_before_my_account',                            array( $this, 'add_payment_method_options' ) );
		add_action( 'woocommerce_receipt_coinsuites_pay',                              array( $this, 'receipt_page' ) );
		add_action( 'woocommerce_update_options_payment_gateways',              array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

 		// Payment listener/API hook
 		//add_action( 'woocommerce_api_WC_Gateway_Coinsuites_Pay', array( $this, 'check_ipn_response' ) );
 		add_action('woocommerce_api_wc_' . $this->id, [$this, 'check_ipn_response']);

	}

	/**
	 * Check if SSL is enabled and notify the user.
	 */
	function logistic_pro_pay_commerce_ssl_check() {
		if ( get_option( 'woocommerce_force_ssl_checkout' ) == 'no' && $this->enabled == 'yes' ) {
			$admin_url = admin_url( 'admin.php?page=wc-settings&tab=checkout' );
			echo esc_html('<div class="error"><p>' . sprintf( __('Coinsuites Pay is enabled and the <a href="%s">force SSL option</a> is disabled; your checkout is not secure! Please enable SSL and ensure your server has a valid SSL certificate.', 'wc-gateway-coinsuites-pay' ), $admin_url ) . '</p></div>');
		}
	}

	/**
	 * Initialize Gateway Settings Form Fields.
	 */
	function init_form_fields() {

		$this->form_fields = array(
			'enabled'     => array(
				'title'       => __( 'Enable/Disable', 'wc-gateway-coinsuites-pay' ),
				'label'       => __( 'Enable Coinsuites Pay', 'wc-gateway-coinsuites-pay' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no'
			),
			'title'       => array(
				'title'       => __( 'Title', 'wc-gateway-coinsuites-pay' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'wc-gateway-coinsuites-pay' ),
				'default'     => __( 'Coinsuites Pay', 'wc-gateway-coinsuites-pay' )
			),
			'description' => array(
				'title'       => __( 'Description', 'wc-gateway-coinsuites-pay' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'wc-gateway-coinsuites-pay' ),
				'default'     => 'The most secure way to pay in crypto.'
			),
			/*
			'api_key'       => array(
				'title'       => __( 'Api key', 'wc-gateway-coinsuites-pay' ),
				'type'        => 'text',
				'description' => __( 'Key obtained from <a href="https://pay.coinsuites.com/" target="_blank"><b>Coinsuites Pay</b></a>, on <b>Settings</b> - <b>API Keys<b>.', 'wc-gateway-coinsuites-pay' ),
				'default'     => ''
			),
			*/
			'public_key' => array(
				'title'       => __( 'Public Key', 'wc-gateway-coinsuites-pay' ),
				'type'        => 'text',
				'description' => __( 'Key obtained from <a href="https://pay.coinsuites.com/" target="_blank"><b>Coinsuites Pay</b></a>, on <b>Settings</b> - <b>API Keys<b>.', 'wc-gateway-coinsuites-pay' ),
				'default'     => ''
			),
			'secret_key'    => array(
				'title'       => __( 'Secret Key', 'wc-gateway-coinsuites-pay' ),
				'type'        => 'text',
				'description' => __( 'Key obtained from <a href="https://pay.coinsuites.com/" target="_blank"><b>Coinsuites Pay</b></a>, on <b>Settings</b> - <b>API Keys<b>.', 'wc-gateway-coinsuites-pay' ),
				'default'     => ''
			),
			'merchant_id'    => array(
				'title'       => __( 'Merchant Id', 'wc-gateway-coinsuites-pay' ),
				'type'        => 'text',
				'description' => __( 'Merchant id in <a href="https://pay.coinsuites.com/" target="_blank"><b>Coinsuites Pay</b></a>, on <b>Settings</b> - <b>Account Information</b>.', 'wc-gateway-coinsuites-pay' ),
				'default'     => ''
			),
			'Crypto_Currencies'   => array(
				'title'       => __( 'Accepted Crypto Currencies', 'wc-gateway-coinsuites-pay' ),
				'type'        => 'multiselect',
				'description' => __( 'Select which crypto currencies to accept.', 'wc-gateway-coinsuites-pay' ),
				'default'     => '',
				'options'     => array(
					'BTC' => 'Bitcoin',
					'BCH' => 'Bitcoin Cash',
					'ETH' => 'Ethereum',
					'LTC' => 'Litecoin'
				),
			),
			'mode'    => array(
				'title'       => __( 'Posting Mode', 'wc-gateway-coinsuites-pay' ),
				'type'        => 'select',
				'description' => __( 'Mode of plugin posting.', 'wc-gateway-coinsuites-pay' ),
				'default'     => 'stage',
				'options'     => array(
					'stage' => 'Stage',
					'production' => 'Production'
				),
			),
			'debug'    => array(
				'title'       => __( 'Debug', 'wc-gateway-coinsuites-pay' ),
				'type'        => 'checkbox',
				'label'       => __( 'Write information to a debug log.', 'wc-gateway-coinsuites-pay' ),
				'description' => __( 'The log will be available via WooCommerce > System Status on the Logs tab with a name starting with \'CoinsuitesPay\'', 'wc-gateway-coinsuites-pay' ),
				'default'     => 'no'
			),

		);
	}


	/**
	 * UI - Admin Panel Options
	 */
	function admin_options() { ?>
		<h3><?php _e( 'Coinsuites Pay Commerce','wc-gateway-coinsuites-pay' ); ?></h3>
		<p><?php _e( 'The Coinsuites Pay Gateway is simple and powerful.  The plugin works by adding crypto currencies fields on the checkout page, and then sending the details to Coinsuites Pay for verification.', 'wc-gateway-coinsuites-pay' ); ?></p>
		<table class="form-table">
			<?php $this->generate_settings_html(); ?>
		</table>
		<?php
	}

	/**
	 * UI - Payment page fields for Coinsuites Pay Commerce.
	 */
	function payment_fields() {
		if ( $this->description ) { ?>
			<p><?php echo esc_html($this->description); ?></p>
		<?php } 
			// echo "<pre>";
			// var_dump($this);
			// echo "</pre>";
		?>
		<fieldset  style="padding-left: 40px;">
			<fieldset>
				<label for="Crypto Currencies"><?php echo __( 'Crypto Currencies', 'wc-gateway-coinsuites-pay' ) ?> <span class="required">*</span></label>
				<select name="Crypto_Currencies" id="Crypto_CurrenciesField" class="woocommerce-select input-text">
					<?php  foreach( $this->Crypto_Currencies as $Crypto_Currency ) { ?>
						<option value="<?php echo esc_html($Crypto_Currency); ?>">
							<?php 
								if($Crypto_Currency == "BTC"){
									echo "Bitcoin";
								}elseif($Crypto_Currency == "BCH"){
									echo "Bitcoin Cash";
								}elseif($Crypto_Currency == "ETH"){
									echo "Ethereum";
								}elseif($Crypto_Currency == "LTC"){
									echo "Litecoin";
								}
							?>
						</option>
					<?php } ?>
				</select>
			</fieldset>
		</fieldset>

		<!-- <script>
			document.getElementById("place_order").style.display = "none";
		</script> -->
		<?php
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int @order_id
	 * @return array
	 */
	function process_payment( $order_id ) {

		$new_customer_vault_id = '';
		$order = new WC_Order( $order_id );
		$user = new WP_User( $order->get_user_id() );

		$apikey = $this->public_key;
		$publickey = $this->secret_key;
		$secretkey = $this->secret_key;
		$merchantid = $this->merchant_id;
		$currency = $order->currency;
		$cryptocurrency = $this->get_post('Crypto_Currencies');
		$order_data = $order->get_data();
		$order_billing_first_name = $order_data['billing']['first_name'];

		//$ipn_url   = str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'WC_CoinsuitesPay', home_url( '/' ) ) );
		$ipn_url = get_site_url()."/?wc-api=WC_CoinsuitesPay";

		$data = [
			"api_key" => $apikey,
			"public_key" => $publickey,
			"secret_key" => $secretkey,
			"merchant_id" => $merchantid,
			"ref_id" => $order->id,
			"currency" => $currency,
			"cryptoCurrency" => $cryptocurrency,
			"amount" => $order->order_total,
			"ipn_url" => $ipn_url,
			"order" => $order_data,
			"firstname" => $order_billing_first_name,
			"lastname" => $order_billing_last_name,
			"company" => $order_billing_company,
			"email" => $order_billing_email,
			"phone" => $order_billing_phone,
			"address" => $order_billing_address_1,
			"city" => $order_billing_city,
			"state" => $order_billing_state,
			"postcode" => $order_billing_postcode,
			"country" => $order_billing_country,
			"cancel_url" => $order->get_cancel_order_url(),
			"success_url" => $order->get_checkout_order_received_url()
		];

		$payload = json_encode($data);

		//$order->add_order_note( __( 'Request JSON: ' , 'wc-gateway-coinsuites-pay' ) . $payload );

		$mode = $this->mode;

		$response = $this->post_and_get_response( $payload, $mode );

		//$order->add_order_note( __( 'Response JSON: ' , 'wc-gateway-coinsuites-pay' ) . $response );

		$result = json_decode($response);

		$order->add_order_note( __( 'Response Reference ID: ' , 'wc-gateway-coinsuites-pay' ) . $result->ref_id );
		$order->add_order_note( __( 'Response Price: ' , 'wc-gateway-coinsuites-pay' ) . $result->price );
		$order->add_order_note( __( 'Response Address: ' , 'wc-gateway-coinsuites-pay' ) . $result->address );
		$order->add_order_note( __( 'Response Coin: ' , 'wc-gateway-coinsuites-pay' ) . $result->coin );
		$order->add_order_note( __( 'Response Expire Date: ' , 'wc-gateway-coinsuites-pay' ) . $result->expires );
		$order->add_order_note( __( 'Response Total: ' , 'wc-gateway-coinsuites-pay' ) . $result->total );
		$order->add_order_note( __( 'Response Checkout URL: ' , 'wc-gateway-coinsuites-pay' ) . $result->checkout_url );

		//$order->payment_complete();

		update_post_meta( $order->id, 'transactionid', $result->ref_id );

		return array (
			'result'   => 'success',
			'redirect' => $result->checkout_url,
		);
	}

	/**
	 * Receipt page.
	 *
	 * Display text and a button to direct the user to Coinsuites Pay.
	 *
	 * @since 1.0.0
	 */
	public function receipt_page( $order_id ) {

		echo '<p>' . __( 'Thank you for your order.', 'coinsuites-pay' ) . '</p>';
	}

	/**
	 * Check Coinsuites Pay IPN response.
	 *
	 * @since 1.0.0
	 */
	public function check_ipn_response() {

		$_GET   = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
		$_POST  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

		$postedData = "";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $postedData = json_decode(file_get_contents('php://input'), true);
            if (!is_array($postedData)) {
				$postedData = array (
					"secret_key" => sanitize_text_field($_POST["secret_key"]),
					"ref_id" => sanitize_text_field($_POST["ref_id"]),
					"price" => sanitize_text_field($_POST["price"]),
					"amount" => sanitize_text_field($_POST["amount"]),
					"total" => sanitize_text_field($_POST["total"]),
					"date_time" => sanitize_text_field($_POST["date_time"]),
					"transaction_id" => sanitize_text_field($_POST["transaction_id"]),
					"coin" => sanitize_text_field($_POST["coin"]),
					"network" => sanitize_text_field($_POST["network"]),
					"currency" => sanitize_text_field($_POST["currency"])
				);
            }
        } else {
			$postedData = array (
				"secret_key" => sanitize_text_field($_GET["secret_key"]),
				"ref_id" => sanitize_text_field($_GET["ref_id"]),
				"price" => sanitize_text_field($_GET["price"]),
				"amount" => sanitize_text_field($_GET["amount"]),
				"total" => sanitize_text_field($_GET["total"]),
				"date_time" => sanitize_text_field($_GET["date_time"]),
				"transaction_id" => sanitize_text_field($_GET["transaction_id"]),
				"coin" => sanitize_text_field($_GET["coin"]),
				"network" => sanitize_text_field($_GET["network"]),
				"currency" => sanitize_text_field($_GET["currency"])
			);
        }

		//$postedData = $_REQUEST;

        /*
        echo "<pre>";
        var_dump($postedData);
        echo "</pre>";
        die();
		*/

		$this->handle_ipn_request( stripslashes_deep( $postedData ) );

		// Notify Coinsuites Pro Pay that information has been received
		header( 'HTTP/1.0 200 OK' );
		flush();

	}

	/**
	 * Check Coinsuites Pay IPN validity.
	 *
	 * @param array $data
	 * @since 1.0.0
	 */
	public function handle_ipn_request( $data ) {

		$this->log( PHP_EOL
			. '----------'
			. PHP_EOL . 'Coinsuites Pay IPN call received'
			. PHP_EOL . '----------'
		);
		$this->log( 'Get posted data' );
		$this->log( 'Coinsuites Pay data: ' . print_r( $data, true ) );

		$coinsuites_pay_error  = false;
		$coinsuites_pay_done   = false;
		$debug_email    = $this->get_option( 'debug_email', get_option( 'admin_email' ) );

		$vendor_name    = get_bloginfo( 'name', 'display' );
		$vendor_url     = home_url( '/' );

		if (isset($data["ref_id"]))
		{
			$order_id       = absint( $data["ref_id"] );
			$order          = wc_get_order( $order_id );
			$original_order = $order;

			$order->add_order_note( __( 'IPN response: '.print_r( $data, true ), 'wc-gateway-coinsuites-pay' ) );

			$this_secret_key = $this->secret_key;

			$secret_key = $data["secret_key"];
			$ref_id = $data["ref_id"];
			$price = $data["price"];
			$amount = $data["amount"];
			$total = $data["total"];
			$date_time = $data["date_time"];
			$transaction_id = $data["transaction_id"];
			$coin = $data["coin"];
			$network = $data["network"];
			$currency = $data["currency"];

			if ($secret_key == $this_secret_key)
			{

				$order->add_order_note( __( 'IPN ref_id: ' , 'wc-gateway-coinsuites-pay' ) . $ref_id);
				$order->add_order_note( __( 'IPN price: ' , 'wc-gateway-coinsuites-pay' ) . $price);
				$order->add_order_note( __( 'IPN amount: ' , 'wc-gateway-coinsuites-pay' ) . $amount);
				$order->add_order_note( __( 'IPN total: ' , 'wc-gateway-coinsuites-pay' ) . $total);
				$order->add_order_note( __( 'IPN date_time: ' , 'wc-gateway-coinsuites-pay' ) . $date_time);
				$order->add_order_note( __( 'IPN transaction_id: ' , 'wc-gateway-coinsuites-pay' ) . $transaction_id);
				$order->add_order_note( __( 'IPN coin: ' , 'wc-gateway-coinsuites-pay' ) . $coin);
				$order->add_order_note( __( 'IPN network: ' , 'wc-gateway-coinsuites-pay' ) . $network);
				$order->add_order_note( __( 'IPN currency: ' , 'wc-gateway-coinsuites-pay' ) . $currency);

				/*
				echo "<pre>";
				var_dump($data);
				echo "</pre>";
				*/

				if (
					($ref_id != "")
					&&
					($price != "")
					&&
					($amount != "")
					&&
					($total != "")
					&&
					($date_time != "")
					&&
					($transaction_id != "")
					&&
					($coin != "")
					&&
					($network != "")
					&&
					($currency != "")
				)
				{
					// Success
					$this->handle_ipn_payment_complete( $data, $order );
				}
			}
		}
	}

	/**
	 * This function handles payment complete request by Coinsuites Pay.
	 * @version 1.4.3 Subscriptions flag
	 *
	 * @param array $data should be from the Gatewy IPN callback.
	 * @param WC_Order $order
	 */
	public function handle_ipn_payment_complete( $data, $order ) {
		//$this->log( '- Complete' );
		$order_id = self::get_order_prop( $order, 'id' );
		$order = new WC_Order($order_id);

        if ($order->status != 'completed')
        {
			$order->payment_complete();
			$order->add_order_note(__('Payment completed successfully', 'wc-gateway-coinsuites-pay'));
        }

		$order->add_order_note( __( 'IPN payment completed', 'wc-gateway-coinsuites-pay' ) );
	}

	/**
	 * Check payment details for valid format
	 *
	 * @return bool
	 */
	function validate_fields() {

		if ( $this->get_post( 'coinsuites-pay-use-stored-payment-info' ) == 'yes' ) return true;

		global $woocommerce;

		// Check for saving payment info without having or creating an account
		if ( $this->get_post( 'saveinfo' )  && ! is_user_logged_in() && ! $this->get_post( 'createaccount' ) ) {
			wc_add_notice( __( 'Sorry, you need to create an account in order for us to save your payment information.', 'wc-gateway-coinsuites-pay'), $notice_type = 'error' );
			return false;
		}

		$Crypto_Currencies            = $this->get_post( 'Crypto_Currencies' );

		// Check card number
		if ( empty( $Crypto_Currencies )) {
			wc_add_notice( __( 'Choose a crypto currency.', 'wc-gateway-coinsuites-pay' ), $notice_type = 'error' );
			return false;
		}


		return true;

	}

	/**
	 * Send the payment data to the gateway server and return the response.
	 *
	 * @param $request
	 *
	 * @return null
	 */
	protected function post_and_get_response( $payload, $mode ) {

		$url = "";
		if($mode == "stage"){
			$url = "https://onboardingdev.coinsuites.com/api/payment";
		}
		elseif($mode == "production"){
			$url = "https://onboardingdev.coinsuites.com/api/payment";
		}

		$args = array(
			'method' => 'POST',
			'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
		    'body'        => $payload,
		    'timeout'     => '5',
		    'redirection' => '5',
		    'httpversion' => '1.0',
		    'sslverify' => false,
		    'blocking'    => true,
		    'cookies'     => array()
		);

		$result = wp_remote_post( $url, $args );
		$result = wp_remote_retrieve_body( $result );

		return $result;

	}

	/**
	 * Get order property with compatibility check on order getter introduced
	 * in WC 3.0.
	 *
	 * @since 1.4.1
	 *
	 * @param WC_Order $order Order object.
	 * @param string   $prop  Property name.
	 *
	 * @return mixed Property value
	 */
	public static function get_order_prop( $order, $prop ) {
		switch ( $prop ) {
			case 'order_total':
				$getter = array( $order, 'get_total' );
				break;
			default:
				$getter = array( $order, 'get_' . $prop );
				break;
		}

		return is_callable( $getter ) ? call_user_func( $getter ) : $order->{ $prop };
	}

	/**
	 * Get post data if set
	 *
	 * @param string $name
	 * @return string|null
	 */
	protected function get_post( $name ) {
		if ( isset( $_POST[ $name ] ) ) {
			return sanitize_text_field($_POST[ $name ]);
		}
		return null;
	}


	/**
	 * Log system processes.
	 * @since 1.0.0
	 */
	public function log( $message ) {
		if ( 'yes' === $this->get_option( 'testmode' )) {
			if ( empty( $this->logger ) ) {
				$this->logger = new WC_Logger();
			}
			$this->logger->add( 'coinsuites_pay', $message );
		}
	}

}
