<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Hoop Payment Gateway
 *
 * Provides a Hoop Payment Gateway, mainly for testing purposes.
 *
 * @class 		WC_Gateway_Wantspay
 * @extends		WC_Payment_Gateway
 * @version		2.1.0
 * @package		WooCommerce/Classes/Payment
 * @author 		WooThemes
 */
class WC_Gateway_Wantspay extends WC_Payment_Gateway {

	//默认支付网关
	private $wantspay_payment_url = "https://payment.wantspay.com/payment/api/payment";
    /**
     * Constructor for the gateway.
     */
	public function __construct() {
		$this->id                 = 'wantspay';
		$this->icon               = apply_filters('woocommerce_wantspay_icon', '');
		$this->has_fields         = false;
		$this->method_title       = __( 'WantsPay', 'woocommerce' );
		$this->method_description = __( 'Credit Card Payment Online', 'woocommerce' );
		//direct支付
		$this->has_fields = true;


		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );
		// wantspay merchant information
		$this->wantspay_merchant_no = $this->get_option( 'wantspay_merchant_no');
		$this->wantspay_hash = $this->get_option( 'wantspay_hash');
		$this->return_url = str_replace ( 'http:', 'https:', add_query_arg ( 'wc-api', 'WC_Gateway_Wantspay', home_url ( '/' ) ) );

		// Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_api_wc_gateway_wantspay', array( $this, 'check_ipn_response' ) );//設定鈎子return_url
    	//add_action( 'woocommerce_thankyou_cheque', array( $this, 'thankyou_page' ) );


		// Actions(前台支付样式)
		add_action ( 'woocommerce_receipt_wantspay', array (
				$this,
				'receipt_page'
		) );
    	// Customer Emails
    	//add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
    }


	// 前台页面显示的支付图标
	public function get_icon() {
		$icon = WC_HTTPS::force_https_url( WC()->plugin_url() . '/includes/gateways/wantspay/assets/images/card.png' );
		$icon_html = '<img src="' . esc_attr ( apply_filters ( 'woocommerce_wantspay_icon', $icon ) ) . '" alt="CreditPay" />';
		return apply_filters ( 'woocommerce_gateway_icon', $icon_html, $this->id );
	}

    /**
     * Initialise Gateway Settings Form Fields  \
	 * 后台表单
     */
    public function init_form_fields() {

    	$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Wantspay Payment', 'woocommerce' ),
				'default' => 'yes'
			),
			'title' => array(
				'title'       => __( 'Title', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'     => __( 'Credit Card Payment Online', 'woocommerce' ),
				'desc_tip'    => true,
			),
			/*
			'description' => array(
				'title'       => __( 'Description', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce' ),
				'default'     => __( 'Please send your cheque to Store Name, Store Street, Store Town, Store State / County, Store Postcode.', 'woocommerce' ),
				'desc_tip'    => true,
			),
			*/
			'merchant_no' => array(
				'title'       => __( 'Merchant No: ', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Wantspay Merchant No', 'woocommerce' ),
				'default'     => '',

			),

			'hash' => array(
				'title'       => __( 'Secure Code: ', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Wantspay Secure Code ', 'woocommerce' ),
				'default'     => '',

			),
			'gateway_url' => array(
				'title'       => __( 'Gateway Url: ', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Wantspay Gateway Url', 'woocommerce' ),
				'default'     => $this->wantspay_payment_url,

			),
    	    'order_prefix' => array(
    	        'title'       => __( 'Order Prefix: ', 'woocommerce' ),
    	        'type'        => 'text',
    	        'description' => __( 'Wantspay Order Prefix', 'woocommerce' ),
    	    ),
		);
    }


	/**
	 * Admin Panel Options
	 * -Options for bits like 'title' and availability on a country-by-country basic
	 *
	 * @access public
	 * @return void
	 */
	public function admin_options() {
    ?>
    <h3><?php _e('Wantspay Payment', 'woothemes'); ?></h3>
    <p><?php _e('Allows payments by fristonecc', 'woothemes'); ?></p>
    <table class="form-table">
    <?php
        // Generate the HTML For the settings form.
        $this->generate_settings_html();
    ?>
    </table>
    <?php
}
	//前台附加信用卡信息
    public function payment_fields() {
		global $woocommerce;
		?>


	  <fieldset id="wc-wantspay-cc-form" class="wc-credit-card-form wc-payment-form">
		    <div class="form-row form-row-wide woocommerce-validated creditDiv">
				<label for="wantspay-card-number" class="creditCardName" ><?php _e("Credit Card number", 'woothemes') ?> <span class="required">*</span></label>
				<input id="wantspay-card-number" class="input-text wc-credit-card-form-card-number" maxlength="16" autocomplete="off" style="width:235px" name="wantspay-card-number" type="text">
			</div>
    		<div class="form-row woocommerce-validated creditDiv">
    			<label for="wantspay-card-cvc" class="creditCardName"><?php _e("CVV ", 'woothemes') ?><span class="required">*</span></label>
    			<input id="wantspay-card-cvc" class="input-text wc-credit-card-form-card-cvc" autocomplete="off" name="wantspay-card-cvc" type="password" maxlength='4'/>
    		    <div id="what"><a></a><div class="what1"></div></div>
    		</div>

			<div class="form-row woocommerce-validated creditDiv">
				<label for="wantspay-card-expiry" class="creditCardName"><?php _e("Expiration date", 'woothemes') ?> <span class="required">*</span></label>
				<select name="wantspay_card_expiration_month" id="cc-expire-month" class="woocommerce-select woocommerce-cc-month" style="padding:8px 2px;">
                <option value=""><?php _e('Month', 'woothemes') ?></option>
                <?php
                $months = array();
                for ($i = 1; $i <= 12; $i++) :
                     $timestamp = mktime(0, 0, 0, $i, 1);
                     $months[date('m', $timestamp)] = date('F', $timestamp);
                endfor;
                foreach ($months as $num => $name) printf('<option value="%u">%s</option>', $num, $name);
                ?>
           </select>
           <select name="wantspay_card_expiration_year" style="padding:8px 6px;margin-left:8px;" id="cc-expire-year" class="woocommerce-select woocommerce-cc-year">
                <option value=""><?php _e('Year', 'woothemes') ?></option>
                <?php
                for ($i = date('Y'); $i <= date('Y') + 15; $i++) printf('<option value="%u">%u</option>', $i, $i);
                ?>
           </select>
			</div>						<div class="clear"></div>
		</fieldset>
		<style type="text/css">
            #payment #wc-wantspay-cc-form>div.creditDiv{ padding:0px; }
            #wc-wantspay-cc-form label.creditCardName{display:inline-block;float:left;line-height:44px;width:200px;}
            #wantspay-card-cvc{width:120px;float:left;}
            #wc-wantspay-cc-form select#cc-expire-month{width:121px;border:1px solid #C7C1C6;}
            #wc-wantspay-cc-form select#cc-expire-year{width:97px;border:1px solid #C7C1C6;}
            #wc-wantspay-cc-form select#cc-expire-month>option{width:100px;}
            #wc-wantspay-cc-form select#cc-expire-year>option{width:77px;}
            #what{line-height:15px; margin-left:-9px; position:relative; float:left;margin-top:9px;}
            #what a{cursor:pointer; background:url(<?php echo WC()->plugin_url();?>/includes/gateways/wantspay/assets/images/picture.jpg) 0 -64px no-repeat; width:48px; height:28px; display:block;border:0px;}
            #what .what1{display:none;}
            #what:hover .what1{display:block; position:absolute; top:29px; left:-1px; padding:10px; border:1px solid #dcdcdc; width:263px; height:178px;
            background:#fff url(<?php echo WC()->plugin_url();?>/includes/gateways/wantspay/assets/images/picture.jpg) 10px -92px no-repeat;z-index:1000;}
		</style>
      <?php
	}

	/**
	 * Validate frontend fields.
	 *
	 * Validate payment fields on the frontend.
	 *
	 * @return bool
	 */
    public function validate_fields() {
		global $woocommerce;



		//Card Validation Start

		$expM_reg = "/0[1-9]|1[0-2]/";
		$expY_reg = "/2([0-9]{3})/";
		$regV="/^4\d{15}$/";
		$regM1 = "/^5\d{15}$/";
		$regM2 = "/^2[2-7]\d{14}$/";
		$regJ = "/^35\d{14}$/";
		$regD = "/^3[068]\d{12}$/";
		$regAE = "/^3[47]\d{13}$/";
		$regDi = "/^6011\d{12}$/";
		$cvv_reg="/^[0-9]{3}$/";
		$cvv_regAE="/^[0-9]{4}$/";
		$mate = false;
		$mtCvv = false;
		if(preg_match($regV,$_POST['wantspay-card-number'])){
				$mate = true;
		}else if(preg_match($regM1,$_POST['wantspay-card-number'])){
			   $mate = true;
		}else if(preg_match($regM2,$_POST['wantspay-card-number'])){
			   $mate = true;
		}else if(preg_match($regJ,$_POST['wantspay-card-number'])){
			   $mate = true;
		}else if(preg_match($regD,$_POST['wantspay-card-number'])){
			   $mate = true;
		}else if(preg_match($regAE,$_POST['wantspay-card-number'])){
			   $mate = true;
		}else if(preg_match($regDi,$_POST['wantspay-card-number'])){
			   $mate = true;
		}
	if ((preg_match($cvv_reg,$_POST['wantspay-card-cvc']) && !preg_match($regAE,$_POST['wantspay-card-number'])) || (preg_match($cvv_regAE,$_POST['wantspay-card-cvc']) && preg_match($regAE,$_POST['wantspay-card-number']))){
		$mtCvv = true;
	}
		//补零
		$exp_month	 =	str_pad($_POST['wantspay_card_expiration_month'],2,"0",STR_PAD_LEFT);
		$form_fields = array();
		if(!$mate) {
			$form_fields[] = "Card number is incorrect. \n";
		}elseif (!$mtCvv) {
			$form_fields[] = "CVV2/CSC is incorrect. \n";
		}elseif (!preg_match($expY_reg,$_POST['wantspay_card_expiration_year'])) {
			$form_fields[] = "expirationYear is incorrect! \n";
		}elseif (!preg_match($expM_reg,$exp_month)) {
			$form_fields[] = "expirationMonth is incorrect!";
		}

		//Card Validation End
		if(sizeof($form_fields)>0) {
			foreach ( $form_fields as $form_field ) {
					wc_add_notice( $form_field, 'error' );
			}
		}else{
			$_SESSION['wantspay_card']['cardno']= $_POST['wantspay-card-number'];
			$_SESSION['wantspay_card']['cvv']= $_POST['wantspay-card-cvc'];
			$_SESSION['wantspay_card']['expmonth']= $exp_month;
			$_SESSION['wantspay_card']['expyear']= $_POST['wantspay_card_expiration_year'];
			return true;
		}

	}



    /**
     * Process the payment and return the result
     *
     * @param int $order_id
     * @return array
     */
	public function process_payment( $order_id ) {
		global $woocommerce;

		$order = wc_get_order( $order_id );

		$sendingData= $this->buildPostData( $order_id );
		$response = $this->curl_submit($sendingData);
		//wc_add_notice( $response, 'error' );
		$returnData = json_decode($response);

		return $this->notify_request($returnData , $order,$order_id);
	}

	public function notify_request($result, $order,$order_id){
		global $woocommerce;

		if (empty($result)){
			$error_message = ' Submit Data Failed! No Data Returned! ';
			$order->update_status ( 'failed',  sprintf ( __ ( $order_id .'Payment Failed, %s', 'woocommerce' ), $error_message ));
		}
		if(empty($result->tradeNo)){

			$respCode = $result->respCode;
			$respMsg = $result->respMsg;

			$error_message = $respCode . ' - ' .$respMsg;
			$order->update_status ( 'failed',  sprintf ( __ ( $order_id .'Payment Failed, %s', 'woocommerce' ), $error_message ));
		}
		$returnData = array(
			'transType' 	=> $result->transType,
			'orderNo'		=> $result->orderNo,
			'merNo'			=> $result->merNo,
//			'terNo'			=> $result->terNo,
			'currencyCode'	=> $result->currencyCode,
			'amount'		=> $result->amount,
			'tradeNo' 		=> $result->tradeNo,
			'respCode'		=> $result->respCode,
			'respMsg'		=> $result->respMsg
		);

        $skipTo3DURL = $result->skipTo3DURL;
        if ($skipTo3DURL) {
            return array(
                'result'=>'success',
                'redirect'=>$skipTo3DURL,
            );
        }
		$returnHashCode = $result->hashcode;
		$hash = trim( $this->get_option ( 'hash' ));
		$hashcode = $this->getHashCode($returnData,$hash);
		if($returnHashCode == $hashcode){
			if($returnData['respCode'] == '00') {
                $respCode = $_REQUEST['respCode'];
                $respMsg = $_REQUEST['respMsg'];
                $success_message = $respCode . ' - ' .$respMsg;
                $order->update_status ( 'completed', sprintf ( __ ( $_REQUEST['orderNo'] .'Payment Successful, %s', 'woocommerce' ), $success_message ));
                //Payment complete and Return thankyou redirect
				$order->payment_complete();
				// Remove cart
				WC()->cart->empty_cart();
				// Reduce stock levels
				//$order->reduce_order_stock();
                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order)
                );

			}else{

				$respCode = $result->respCode;
				$respMsg = $result->respMsg;

				$error_message = $respCode . ' - ' .$respMsg;
				$order->update_status ( 'failed',  sprintf ( __ ( $order_id .'Payment Failed, %s', 'woocommerce' ), $error_message ));
			}
		}else{

			$error_message = 'Validation failed!,The reason is : '.$respCode . ' - ' .$respMsg . ' Please pay again !';
			$order->update_status ( 'failed',  sprintf ( __ ( $order_id .'Payment Failed, %s', 'woocommerce' ), $error_message ));
		}

		// Return thank you page redirect
		return array(
				'result' 	=> 'success',
				'redirect'	=> $this->get_return_url( $order )
		);
	}

    //返回
    public function check_ipn_response(){
//        print_r($_REQUEST);die;
        $returnData = array(
            'transType' 	=> $_REQUEST['transType'],
            'orderNo'		=> $_REQUEST['orderNo'],
            'merNo'			=> $_REQUEST['merNo'],
//            'terNo'			=> $_REQUEST['terNo'],
            'currencyCode'	=> $_REQUEST['currencyCode'],
            'amount'		=> $_REQUEST['amount'],
            'tradeNo' 		=> $_REQUEST['tradeNo'],
            'respCode'		=> $_REQUEST['respCode'],
            'respMsg'		=> $_REQUEST['respMsg']
        );

        $returnHashCode = $_REQUEST['hashcode'];
        $class_wc = new WC_Gateway_Wantspay;
        $hash = trim( $class_wc->get_option ( 'hash' ));
        $hashcode = $this->getHashCode($returnData,$hash);
		$order_id = str_replace($this->get_option ( 'order_prefix' ),"",$_REQUEST['orderNo']);
        $order = wc_get_order( $order_id  );
        if($returnHashCode == $hashcode){
			
			$respCode = $_REQUEST['respCode'];
            $respMsg = $_REQUEST['respMsg'];
			
			if($respCode == '01' && $respMsg == 'pending_async'){
				$success_message = $respCode . ' - ' .$respMsg;
                $order->update_status ( 'processing', sprintf ( __ ( $_REQUEST['orderNo'] .'Payment Successful, %s', 'woocommerce' ), 'processing' ));
				wp_redirect($this->get_return_url($order));
                return;
			}
            if($returnData['respCode'] == '00') {
                $respCode = $_REQUEST['respCode'];
                $respMsg = $_REQUEST['respMsg'];
                $success_message = $respCode . ' - ' .$respMsg;
                $order->update_status ( 'completed', sprintf ( __ ( $_REQUEST['orderNo'] .'Payment Successful, %s', 'woocommerce' ), $success_message ));
                //Payment complete and Return thankyou redirect
                $order->payment_complete();
                // Remove cart
                WC()->cart->empty_cart();
                // Reduce stock levels
                //$order->reduce_order_stock();
                wp_redirect($this->get_return_url($order));
                return;
            }else{

                $respCode = $_REQUEST['respCode'];
                $respMsg = $_REQUEST['respMsg'];

                $error_message = $respCode . ' - ' .$respMsg;
                $order->update_status ( 'failed',  sprintf ( __ ( $_REQUEST['orderNo'] .'Payment Failed, %s', 'woocommerce' ), $error_message ));
                wc_add_notice( __('<strong>Payment Failure:</strong><br/>TradeNo:', 'Gateway_Wantspay' ).$returnData['tradeNo']. __('<br/>respMsg:', 'Gateway_Wantspay' ). $returnData['respCode'].$returnData['respMsg'], 'error' );
            }
        }else{

            $error_message = 'Validation failed!,The reason is : '.$_REQUEST['respCode'] . ' - ' .$_REQUEST['respMsg'] . ' Please pay again !';
            $order->update_status ( 'failed',  sprintf ( __ ( $_REQUEST['orderNo'] .'Payment Failed, %s', 'woocommerce' ), $error_message ));
            wc_add_notice( __('<strong>Payment Failure:</strong><br/>respMsg:Validation Failed', 'Gateway_Wantspay' ), 'error' );
        }
//        wp_redirect(get_permalink( woocommerce_get_page_id( 'cart' ) ));
//        return;
        // Return thank you page redirect
        return array(
            'result' 	=> 'success',
            'redirect'	=> $this->get_return_url( $order )
        );
    }

	private function curl_submit($data) {
		$url_server = trim ( $this->get_option ( 'gateway_url' ));
		if(empty($url_server)) $url_server = "https://payment.wantspay.com/payment/api/payment";

		if(function_exists('curl_init') && function_exists('curl_exec')) {
			$info = $this->vpost($url_server, $data);
		} else {
			$info = $this->hpost($url_server, $data);
		}

		return $info;
	}

	/*  use file_get_contents
	 *
	 *  @param string $url
	 *  @param string $data
	 *  @return object json
	 */
	private function hpost($url, $data){
		$website = $_SERVER['HTTP_HOST'];
		$cookie="";
	        foreach ($_COOKIE as $key => $value) {
	           $cookie.=$key."=".$value.";";
	        }
        $options  = array(
			'http' => array(
			'method' => "POST",
			'header' => "Accept-language: en\r\n" . "Cookie: $cookie\r\n" . "referer:$website \r\n",
			'content-type' => "multipart/form-data",
			'content' => $data,
			'timeout' => 15 * 60
            )
        );
        //创建并返回一个流的资源
        $context  = stream_context_create($options);
		//var_dump($options);exit;
        $result   = file_get_contents($url, false, $context);
        return $result;
    }

	/*  use curl
	 *
	 *  @param string $url
	 *  @param string $data
	 *  @return object json
	 */
	private function vpost($url, $data) {
		global  $messageStack;
		    $curl_cookie="";
	        foreach ($_COOKIE as $key => $value) {
	           $curl_cookie.=$key."=".$value.";";
	        }

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		curl_setopt($curl, CURLOPT_REFERER, $_SERVER['HTTP_HOST']);
		curl_setopt($curl, CURLOPT_POST, 1);
		if($url=="https://payment.wantspay.com/payment/api/payment"){
		    curl_setopt($curl, CURLOPT_PORT, 443);
		}
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		curl_setopt($curl, CURLOPT_TIMEOUT, 300);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_COOKIE,$curl_cookie);
		$tmpInfo = curl_exec($curl);
		if (curl_errno($curl)) {
			wp_die ( curl_error($curl) );
		}
		curl_close($curl);
		return $tmpInfo;
	}

	private function buildPostData($order_id) {

		$order = wc_get_order( $order_id );

		$cardNo				 = $_SESSION['wantspay_card']['cardno'];
		$cardCompanyCode	 = $_SESSION['wantspay_card']['cvv'];
		$cardExpireYear		 = $_SESSION['wantspay_card']['expyear'];
		$cardExpireMonth	 = $_SESSION['wantspay_card']['expmonth'];

		//商户号
		$merNo = trim ( $this->get_option ( 'merchant_no' ));
		//终端号
//		$terNo = trim( $this->get_option ( 'ter_no' ));
		//安全码
		$hash = trim( $this->get_option ( 'hash' ));

		//订单号
		$orderNo = $this->get_option ( 'order_prefix' ).$order_id;
		//[必填] 币种
		$currencyCode = strtoupper ( get_woocommerce_currency () );;
		//[必填] 金额，最多为小数点两位
		$amount = number_format($order->get_total(), 2, '.', '');
		//[选填] 货物信息
		$goodsString = '{"goodsInfo":'.$this->getProductsInfo( $order ).'}';


		//[必填] 持卡人姓
		$billFirstName = trim ( $order->billing_first_name );
		//[必填] 持卡人名
		$billLastName = trim ( $order->billing_last_name );
		//[必填] 详细地址
		$billAddress = empty ( $order->billing_address_2 ) ? $order->billing_address_1 : $order->billing_address_1 . $order->billing_address_2;
		//[必填] 城市
		$billCity = $order->billing_city;

			//[必填] 国家
		$billCountry = $order->billing_country;
		//[必填] 省份/州
		$billStatesArr   = WC()->countries->get_states($billCountry);
		$billState = $billStatesArr[$order->billing_state];

		//[必填] 邮编
		$billZip = $order->billing_postcode;

		//[必填] 持卡人邮箱,用户支付成功/失败发送邮件给持卡人
		$shipEmail = $order->billing_email;
		//[必填] 持卡人电话
		$phone = (isset ( $order->shipping_phone ) && $order->shipping_phone) ? $order->shipping_phone : $order->billing_phone;

		//[必填] 收货人姓
		$shipFirstName = trim ( $order->shipping_first_name );
		//[必填] 收货人名
		$shipLastName = trim ( $order->shipping_last_name );
		//[必填] 详细地址
		$delivery_address = empty ( $order->shipping_address_2 ) ? $order->shipping_address_1 : $order->shipping_address_1 . $order->shipping_address_2;
		$shipAddress = !empty($delivery_address) ? $delivery_address : $billAddress;
		//[必填] 城市
		$shipCity = $order->shipping_city;
        //[必填] 国家
        $shipCountry = $order->shipping_country;
		//[必填] 州省
		$shipStatesArr   = WC()->countries->get_states( $shipCountry );
		$shipState = $shipStatesArr[$order->shipping_state];
		//[必填] 邮编
		$shipZip = $order->shipping_postcode;

		//[必填] 支付结果返回的商户URL
//        print WC()->api_request_url( 'WC_Gateway_Wantspay' );
//        die;
        $returnURL =  $this->return_url;//plugins_url('class-wc-gateway-wantspay.php/validation',__FILE__);
        $merNotifyURL = $this->return_url;//plugins_url('class-wc-gateway-validation.php/validation',__FILE__);

		//[必填] 支付语言，默认为英文
		$language = $this->getLanguage();
		//持卡人IP
		$payIP = $this->getOnline_ip();

		$data = array(
    		'apiTpye'         	=> '1',
			'merremark'         => '',
			'returnURL'         => $returnURL,
            'merNotifyURL'      => $merNotifyURL,
            'merMgrURL'         => $_SERVER['HTTP_HOST'],
//            'merMgrURL'         => 'www.test.com',
    		'webInfo'           => $_SERVER['HTTP_USER_AGENT'],
    		'language'          => $language,
    		'cardCountry'       => $billCountry,
			'cardCity'          => $billCity,
			'cardAddress'       => $billAddress,
			'cardZipCode'       => $billZip,
            'grCountry'       	=> empty($shipCountry)?$billCountry:$shipCountry,
            'grCity'            => empty($shipCity)?$billCity:$shipCity,
            'grAddress'         => empty($shipAddress)?$billAddress:$shipAddress,
            'grZipCode'         => empty($shipZip)?$billZip:$shipZip,
			'grEmail'           => $shipEmail,
			'grphoneNumber'     => $phone,
            'grPerName'     	=> empty($shipFirstName)?$billFirstName.'.'.$billLastName:$shipFirstName.'.'.$shipLastName,
            'goodsString'       => $goodsString,
			'cardNO'            => $cardNo,
			'expYear'    		=> $cardExpireYear,
			'expMonth'  	    => $cardExpireMonth,
			'cvv'               => $cardCompanyCode,
			'cardFullName'      => $billFirstName.'.'.$billLastName,
			'cardFullPhone'     => $phone
    	 );
		if(!empty($billState)){
			 $data['cardState'] = $billState;
		 }
		 if(!empty($shipState)){
			 $data['grState'] = $shipState;
		 }

		$arrHashCode = array(
    		'EncryptionMode'    => 'SHA256',
    		'CharacterSet'    	=> 'UTF8',
    		'merNo'             => $merNo,
//    		'terNo'             => $terNo,
			'orderNo'           => $orderNo,
    		'currencyCode'      => $currencyCode,
    		'amount'            => $amount,
            'payIP'             => $payIP,
    		'transType'         => 'sales',
    		'transModel'        => 'M'
		);
		$strHashCode			= $this->array2String($arrHashCode).$hash;
		$arrHashCode['hashcode']= hash("sha256",$strHashCode);

		$strHashInfo 			= $this->array2String($arrHashCode);
		$strBaseInfo 			= $this->array2String($data);
		$post_data 				= $strBaseInfo.$strHashInfo;

		return $post_data;

	}

	/*
	 * @param object $order
     * @return json
	 */
	private function getProductsInfo( $order ){

		$arr_new = array();
        foreach($order->get_items() as $item){
            $arr_new[] = array('goodsName'=>$this->string_replace($item ['name']),'goodsPrice'=>number_format($order->get_item_subtotal ( $item, false ), 2, '.', ''),'quantity'=>$item ['qty']);
        }
        return empty($arr_new)?false:json_encode($arr_new);
	}

	private function getHashCode($data,$hash){
		ksort($data);
		$pre_post_data = $this->array2String($data).$hash;
		$hashcode = hash("sha256",$pre_post_data);
		return $hashcode;
	}

	/*
	 * @param array $arr
     * @return string
	 */
	private function array2String($arr){
		$str = '';
		$arr_length = count($arr)-1;
		foreach( $arr as $key => $value ){
				$str.=$key.'='.$value.'&';
		}
		return urldecode($str);

	}


	/**
	 * 使用空格替换换行符
	 * @param string string_before
	 * @return string string_after
	 */
	 private function string_replace($string_before){
		$string_after = str_replace('%','PP',$string_before);
        $string_after = str_replace('&','AND',$string_after);
        $string_after = str_replace(',','',$string_after);
		return $string_after;
	 }

	 // 获取浏览器的语言
	private function getLanguage() {
		$lang = substr ( $_SERVER ['HTTP_ACCEPT_LANGUAGE'], 0, 4 );
		$language = '';
		if (preg_match ( "/en/i", $lang ))
			$language = 'en-us'; // 英文
		elseif (preg_match ( "/fr/i", $lang ))
			$language = 'fr-fr'; // 法语
		elseif (preg_match ( "/de/i", $lang ))
			$language = 'de-de'; // 德语
		elseif (preg_match ( "/ja/i", $lang ))
			$language = 'ja-jp'; // 日语
		elseif (preg_match ( "/ko/i", $lang ))
			$language = 'ko-kr'; // 韩语
		elseif (preg_match ( "/es/i", $lang ))
			$language = 'es-es'; // 西班牙语
		elseif (preg_match ( "/ru/i", $lang ))
			$language = 'ru-ru'; // 俄罗斯
		elseif (preg_match ( "/it/i", $lang ))
			$language = 'it-it'; // 意大利语
		else
			$language = 'en-us'; // 英文
		return $language;
	}

	private function getOnline_ip(){
		if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
			$online_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		elseif(isset($_SERVER['HTTP_CLIENT_IP'])){
			$online_ip = $_SERVER['HTTP_CLIENT_IP'];
		}
		elseif(isset($_SERVER['HTTP_X_REAL_IP'])){
			$online_ip = $_SERVER['HTTP_X_REAL_IP'];
		}else{
			$online_ip = $_SERVER['REMOTE_ADDR'];
		}
		$ips = explode(",",$online_ip);
		return $ips[0];
	}

}
