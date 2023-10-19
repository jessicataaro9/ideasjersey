<?php

if (!defined('ABSPATH')) exit;// Exit if accessed directly

//define("CI_WC_ALI_PATH", plugins_url('', __FILE__));
//define("CI_WC_PATH", plugin_dir_path(__FILE__));

/**
 * Easypay_direct_creditcard Payment Gateway
 * Provides a Easypay_direct_creditcard Payment Gateway.
 * Author: PariTECH
 * @class   WC_Easypay_direct_creditcard
 * @extends WC_Payment_Gateway
 * @version v4.5
 */
class WC_Easypay_direct_creditcard extends WC_Payment_Gateway
{
    var $current_currency;
    var $multi_currency_enabled;

    /**
     * Constructor for the gateway.
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        global $woocommerce;

        $this->current_currency = $this->current_currency();
        $this->multi_currency_enabled = in_array('woocommerce-multilingual/wpml-woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))
            && get_option('icl_enable_multi_currency') == 'yes';

        $this->id = 'easypay-direct-creditcard-woocommerce';
        $this->has_fields = true;

        // Load the form fields.
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

        // Define user set variables
        $this->title       = $this->settings['title'];
        $this->description = $this->settings['description'];
        $this->mode        = $this->settings['mode'];
        $this->transtype   = $this->settings['transtype'];
        $this->iframe      = $this->settings['iframe'];
        $this->cardtypes   = $this->settings['cardtypes'];
        $this->account     = $this->settings['account'];
        $this->secretkey   = $this->settings['secretkey'];
        $this->paymentmethod = $this->settings['paymentmethod'];

        // Actions
        //add_action('admin_notices', array($this, 'requirement_checks'));
        //add_action('woocommerce_api_wc_onlinepay_authorizepay', array($this, 'check_onlinepay_authorizepay_response'));
        add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));               // WC <= 1.6.6
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));  // WC >= 2.0
        //add_action('woocommerce_thankyou_onlinepay_authorizepay', array($this, 'thankyou_page'));
        //add_action('woocommerce_receipt_onlinepay_authorizepay', array($this, 'receipt_page'));
        
    }

    /**
     * Get gateway icon.
     *
     * @return string
     */
    public function get_icon()
    {
        $icon = apply_filters('woocommerce_onlinepay_authorizepay_icon', plugins_url('images/authorizepay.png', __FILE__));
        $icon_html = '<img src="' . esc_attr($icon) . '" alt="' . esc_attr__('AuthorizePay acceptance mark', 'woocommerce') . '" />';
        return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
    }

    /**
     * Admin Panel Options
     *
     * - Options for bits like 'title' and account etc.
     * @since 1.0
     */
    public function admin_options()
    {
        ?>
        <h3><?php _e('Easypay_direct_creditcard', 'easypay-direct-creditcard-woocommerce'); ?></h3>
        <p><?php _e('Setting parameters', 'easypay-direct-creditcard-woocommerce'); ?></p>
        <table class="form-table">
            <?php
            // Generate the HTML For the settings form.
            $this->generate_settings_html();
            ?>
        </table><!--/.form-table-->
        <?php
    }

    /**
     * Initialise Gateway Settings Form Fields
     *
     * @access public
     * @return void
     */
    function init_form_fields()
    {
        global $woocommerce;

        $this->form_fields = array(
            'title'       => array(
                'title'       => __( 'Title', 'patsatech-woo-creditcards-server' ),
                'type'        => 'text',
                'description' => __( 'This is the title displayed to the user during checkout.', 'patsatech-woo-creditcards-server' ),
                'default'     => __( 'Credit Cards', 'patsatech-woo-creditcards-server' ),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __( 'Description', 'patsatech-woo-creditcards-server' ),
                'type'        => 'textarea',
                'description' => __( 'This is the description which the user sees during checkout.', 'patsatech-woo-creditcards-server' ),
                'default'     => __( 'After clicking "Place order", you will be redirected to Credit Cards to complete your purchase securely.', 'patsatech-woo-creditcards-server' ),
                'desc_tip'    => true,
            ),
            'mode'        => array(
                'title'       => __( 'Mode Type', 'patsatech-woo-creditcards-server' ),
                'type'        => 'select',
                'options'     => array(
                    'test' => 'Test',
                    'live' => 'Live',
                ),
                'default'     => 'test',
                'description' => __( 'Select Test or Live modes.', 'patsatech-woo-creditcards-server' ),
                'desc_tip'    => true,
            ),
            'account'       => array(
                'title'       => __( 'Account', 'patsatech-woo-creditcards-server' ),
                'type'        => 'text',
                'description' => __( 'This is the account to request creditcards.', 'patsatech-woo-creditcards-server' ),
                'default'     => __( '', 'patsatech-woo-creditcards-server' ),
                'desc_tip'    => true,
            ),
            'secretkey'       => array(
                'title'       => __( 'Secret Key', 'patsatech-woo-creditcards-server' ),
                'type'        => 'text',
                'description' => __( 'This is the secret key to accept.', 'patsatech-woo-creditcards-server' ),
                'default'     => __( '', 'patsatech-woo-creditcards-server' ),
                'desc_tip'    => true,
            ),
            'paymentmethod'       => array(
                'title'       => __( 'Payment Method', 'patsatech-woo-creditcards-server' ),
                'type'        => 'text',
                'description' => __( 'This is the payment method to request creditcards.', 'patsatech-woo-creditcards-server' ),
                'default'     => __( '', 'patsatech-woo-creditcards-server' ),
                'desc_tip'    => true,
            ),
            'iframe'      => array(
                'title'       => __( 'Enable/Disable', 'patsatech-woo-creditcards-server' ),
                'type'        => 'checkbox',
                'label'       => __( 'Enable i-Frame Mode', 'patsatech-woo-creditcards-server' ),
                'default'     => 'yes',
                'description' => __( 'Make sure your site is SSL Protected before using this feature.', 'patsatech-woo-creditcards-server' ),
                'desc_tip'    => true,
            ),
            'transtype'   => array(
                'title'       => __( 'Transaction Type', 'patsatech-woo-creditcards-server' ),
                'type'        => 'select',
                'options'     => array(
                    'PAYMENT'      => __( 'Payment', 'patsatech-woo-creditcards-server' ),
                    'DEFFERRED'    => __( 'Deferred', 'patsatech-woo-creditcards-server' ),
                    'AUTHENTICATE' => __( 'Authenticate', 'patsatech-woo-creditcards-server' ),
                ),
                'description' => __( 'Select Payment, Deferred or Authenticated.', 'patsatech-woo-creditcards-server' ),
                'desc_tip'    => true,
            ),
            'cardtypes'   => array(
                'title'       => __( 'Accepted Cards', 'woothemes' ),
                'class'       => 'wc-enhanced-select',
                'type'        => 'multiselect',
                'description' => __( 'Select which card types to accept.', 'woothemes' ),
                'default'     => 'VISA',
//                'options'     => $this->card_type_options,
                'desc_tip'    => true,
            ),
        );
    }

    /**
     * Check the main currency
     *
     * @access public
     * @return string
     */
    function current_currency()
    {
        $currency = get_option('woocommerce_currency');
        return $currency;
    }

 function payment_fields() {
	echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';
 
	// 如果希望您的自定义支付网关支持它，请添加此操作挂钩 
	//do_action( 'woocommerce_credit_card_form_start', $this->id );
 
	echo '<div class="form-row form-row-wide"><label>Card Number <span class="required">*</span></label>
		<input id="authorizepay_number" name="authorizepay_number" maxlength="16" type="text" autocomplete="off">
		</div>
		<div class="form-row form-row-first">
			<label>Expiry (MM/YYYY) <span class="required">*</span></label>
			<input id="authorizepay_expires" name="authorizepay_expires" type="text" maxlength="7" autocomplete="off" placeholder="MM/YYYY" onkeyup="if(this.value.length==2){this.value+=\'/\';};">
		</div>
		<div class="form-row form-row-last">
			<label>Card code <span class="required">*</span></label>
			<input id="authorizepay_checkcode" name="authorizepay_checkcode" type="password" maxlength="4" autocomplete="off" placeholder="">
		</div>
		<div class="clear"></div>';
 
	//do_action( 'woocommerce_credit_card_form_end', $this->id );
 
	echo '<div class="clear"></div></fieldset>';
 
}

function validate_fields(){
 
	if( empty( $_POST[ 'authorizepay_number' ]) ) {
		wc_add_notice(  'Card Number is required!', 'error' );
		return false;
	}
	if( empty( $_POST[ 'authorizepay_expires' ]) ) {
		wc_add_notice(  'Expiry is required!', 'error' );
		return false;
	}
	if(strpos($_POST[ 'authorizepay_expires' ],'/')===false)
	{
		wc_add_notice(  'Expiry is invalid (MM/YYYY)!', 'error' );
		return false;
	}
	if( empty( $_POST[ 'authorizepay_checkcode' ]) ) {
		wc_add_notice(  'Card code is required!', 'error' );
		return false;
	}
	return true;
 
}

    /**
     * Process the payment and return the result
     *
     * @access public
     * @param int $order_id
     * @return array
     */
    function process_payment($order_id)
    {
        global $woocommerce;
        // 获取订单信息，并将订单信息写入session
        $order = new WC_Order( $order_id );
        $time_stamp = date( 'ymdHis' );
        $a1=$_POST[ 'authorizepay_number' ];
        $a2=$_POST[ 'authorizepay_expires' ];
        $a3=$_POST[ 'authorizepay_checkcode' ];
        $sd_arg = array(
            'account' => substr($this->account, 0, 6),// 商户号
            'accountNo' => $this->account, // 商户终端号
            'paymentMethod' => $this->paymentmethod, // 支付方式
            'orderNo' => $order->get_id().'ep'. $time_stamp , // 商户订单号
            'orderAmount' => number_format(trim($order->get_total()), 2, '.', ''), // 交易金额
            'orderCurrency' => $order->get_currency(), // 交易币种
            'billingFirstName' => $order->get_billing_first_name(), // billingFirstName
            'billingLastName' => $order->get_billing_last_name(), // billingLastName
            'billingAddress' => $order->get_billing_address_1(), // billingAddress
            'billingCity' => $order->get_billing_city(), // billingCity
            'billingState' => $order->get_billing_state(), // billingState
            'billingZip' => $order->get_billing_postcode(), // billingZip
            'billingCountry' => $order->get_billing_country(), // billingCountry
            'billingEmail' => $order->get_billing_email(), // billingEmail
            'billingPhone' => $order->get_billing_phone(), // billingPhone
            'shippingFirstName' => $order->get_shipping_first_name(), // shippingFirstName
            'shippingLastName' => $order->get_shipping_last_name(), // shippingLastName
            'shippingAddress' => $order->get_shipping_address_1(), // shippingAddress
            'shippingCity' => $order->get_shipping_city(), // shippingCity
            'shippingState' => $order->get_shipping_state(), // shippingState
            'shippingZip' => $order->get_shipping_postcode(), // shippingZip
            'shippingCountry' => $order->get_shipping_country(), // shippingCountry
            'shippingEmail' => $order->get_billing_email(), // shippingEmail
            'shippingPhone' => $order->get_billing_phone(), // shippingPhone
            'customerIP' => $this->get_client_ip(), // customerIP
            'systemName' => 'wordpress',
            'securityToken' => '',
            'a1' => $a1,
            'a2' => $a2,
            'a3' => $a3,
            'orderNoticeUrl' => "",
            'signInfo' => hash("sha256", substr($this->account, 0, 6).$this->account.$order->get_id().'ep'. $time_stamp.number_format(trim($order->get_total()), 2, '.', '').
                $order->get_currency().$order->get_billing_first_name().$order->get_billing_last_name().$this->secretkey),
        );

        $post_values = json_encode($sd_arg);
        $response = wp_remote_post(
            "https://www.wangs98trading.com/EasyPay/checkout/pay",
            array(
                'body'      => $post_values,
                'timeout'     => 45,
                'method'    => 'POST',
                'headers'   => array( 'Content-Type' => 'application/json' ),
                'sslverify' => false,
            )
        );
        $error_message = "Sorry, please check your inputs and try again.<br />";
        if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ){
            print_r(json_decode($response['body']));
            $response_content = json_decode($response['body']);
            $response_header = $response_content->HEAD;
            $response_body = $response_content->BODY;
            if ($response_header->hrtnCod === 'SUC0000') {
                $eaOrderNo = $response_body->DirectConnectionBank_Z1[0]->orderNo;
                $transStatus = $response_body->DirectConnectionBank_Z1[0]->transStatus;
                $transDetails = $response_body->DirectConnectionBank_Z1[0]->transDetails;
                $merOrderNo = substr($response_body->DirectConnectionBank_Z1[0]->orderMerNo, 0, strpos($response_body->DirectConnectionBank_Z1[0]->orderMerNo, "ep"));
                // 交易成功
                if ($transStatus === 'A') {
                    $order->update_status("processing");
                    $woocommerce->cart->empty_cart();
                    wc_add_notice( $transDetails, 'success' );
                }else{
                    if (!empty( $merOrderNo ) ) {
                        $order->set_transaction_id( $merOrderNo );
                    }
                    if($transDetails !== false){
                        wc_add_notice($transDetails, 'error' );
                        error_log($transDetails);
                    }else{
                        wc_add_notice($error_message , 'error' );
                        error_log($error_message);
                    }
                    return;
                }
            } else {
                print_r(json_decode($response['body']));
                $response_error_content = json_decode($response['body']);
                $response_error_body = $response_error_content->BODY;
                // 报错信息
                wc_add_notice( $response_error_body->message, 'error' );
                return;
            }
        } else{
            print_r(json_decode($response['body']));
            $response_error_content = json_decode($response['body']);
            $response_error_body = $response_error_content->BODY;
            // 报错信息
            wc_add_notice( $response_error_body->message, 'error' );
            return;
        }

        return array(
            'result'   => 'success',
            'redirect' => $this->get_return_url($order),
        );

    }

    /**
     * Check if requirements are met and display notices
     *
     * @access public
     * @return void
     */
    function requirement_checks()
    {
    
    }

    /**
     * Check if gateway is available
     *
     * @access public
     * @return bool
     */
    function is_available()
    {
        return parent::is_available();
    }

    /**
     * Output for the order received page.
     *
     * @param array $order
     * @access public
     * @return void
     */
    function receipt_page($order)
    {
        echo '<p>' . __('Thank you for your order, please click the button below to pay with easypay-direct-creditcard-woocommerce.', 'easypay-direct-creditcard-woocommerce') . '</p>';
        echo $this->generate_online_form($order);
    }

    /**
     * Return page of easypay-direct-creditcard-woocommerce, show easypay-direct-creditcard-woocommerce Trade No.
     *
     * @access public
     * @param mixed Sync Notification
     * @return void
     */

    function thankyou_page($order_id)
    {
        global $woocommerce;

        if (isset($order_id)) {
            // 处理返回结果
            $this->check_onlinepay_authorizepay_response();
        }
    }

    function check_onlinepay_authorizepay_response()
    {}

    function successful_request($posted)
    {}

    function fail_request($posted)
    {}

    function get_client_ip()
    {
        if (isset($_SERVER)) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
                $ip = $_SERVER['HTTP_X_FORWARDED'];
            } elseif (isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
            } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
                $ip = $_SERVER['HTTP_FORWARDED_FOR'];
            } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
                $ip = $_SERVER['HTTP_FORWARDED'];
            } elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
                $ip = $_SERVER['HTTP_X_REAL_IP'];
            } else {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        } else {
            if (getenv('HTTP_X_FORWARDED_FOR')) {
                $ip = getenv('HTTP_X_FORWARDED_FOR');
            } elseif (getenv('HTTP_CLIENT_IP')) {
                $ip = getenv('HTTP_CLIENT_IP');
            } else {
                $ip = getenv('REMOTE_ADDR');
            }
        }

        $ips = explode(",", $ip);
        return $ips[0];
    }
}

?>