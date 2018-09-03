<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WC_Gateway_Postfinance class.
 * @extends WC_Payment_Gateway
 */
class WC_Gateway_Postfinance extends WC_Payment_Gateway {

    /**
     * Redirect for PostFinance Checkout
     * @var bool
     */
    public $checkout_redirection;

    /**
     * switch from test account to your production (live) account
     * @var bool
     */
    public $environment;

    /**
     * Logging enabled?
     * @var bool
     */
    public $logging;

    /**
     * PostFinance Merchant ID
     * @var string
     */
    public $pspid;

    /**
     * PostFinance SHA algorithm
     * @var string
     */
    public $sha_algo;

    /**
     * PostFinance SHA-IN passphrase
     * @var string
     */
    public $sha_in_signature;

    /**
     * PostFinance SHA-OUT passphrase
     * @var string
     */
    public $sha_out_signature;

    /**
     * Constructor for the gateway.
     */
    public function __construct() {
        $this->id                   = 'postfinance';
        $this->has_fields           = false;
        $this->method_title         = __( 'PostFinance', 'woocommerce-gateway-postfinance' );
        $this->method_description   = __( 'Have your customers pay with PostFinance Card and PostFinance E-finance payment methods.', 'woocommerce-gateway-postfinance' );

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables.
        $this->checkout_redirection = 'yes' === $this->get_option( 'redirection', 'yes' );
        $this->enabled              = $this->get_option( 'enabled' );
        $this->sha_algo             = $this->get_option( 'sha' );
        $this->logging              = 'yes' === $this->get_option( 'debug', 'no' );
        $this->pspid                = $this->get_option( 'pspid' );
        $this->sha_in_signature     = $this->get_option( 'sha_in_signature' );
        $this->sha_out_signature    = $this->get_option( 'sha_out_signature' );
        $this->environment          = 'test' === $this->get_option( 'environment', 'production' );

        // Define user set translation.
        // Maybe the whole approach is wrong. 
        $this->title                = $this->get_option( 'title' );
        $this->description          = $this->get_option( 'description' );
        $this->order_button_text    = $this->get_option( 'order_button' );
        $this->status_pending       = $this->get_option( 'status_pending' );
        $this->pay_button_text      = $this->get_option( 'pay_button' );
        $this->cancel_button_text   = $this->get_option( 'cancel_button' );
        $this->status_processing    = $this->get_option( 'status_processing' );
        $this->status_on_hold       = $this->get_option( 'status_on_hold' );
        $this->status_completed     = $this->get_option( 'status_completed' );
        $this->status_cancelled     = $this->get_option( 'status_cancelled' );
        $this->status_failed        = $this->get_option( 'status_failed' );

        $this->description  = trim( $this->description );

        add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_thankyou_postfinance', array( $this, 'thankyou_page' ) );
        add_action( 'woocommerce_api_wc_gateway_postfinance', array( $this, 'process_response' ) );
    }

    /**
     * Get gateway icon.
     * @return string
     */
    public function get_icon() {
        $icon_html = '<img src="' . WC_HTTPS::force_https_url( plugins_url( '/assets/images/yellownet_5_choice' . '.gif', WC_POSTFINANCE_MAIN_FILE ) ) . '" alt="PostFinance Card" />';

        return apply_filters( 'woocommerce_gateway_icon', $icon_html, $this->id );
    }
    /**
     * Load public scripts.
     */
    public function scripts() {
        if ( ! is_order_received_page() ) {
            return;
        }

        if ( ! $this->is_available() ) {
            return;
        }

        if ( $this->environment && $this->logging ) {
            return;
        }

        if ( $this->checkout_redirection ) {
            wp_enqueue_style( 'wc-postfinance-payment-request-css', plugins_url( 'assets/css/payment-request.css', WC_POSTFINANCE_MAIN_FILE ), array(), WC_POSTFINANCE_VERSION, false );
            wp_enqueue_script( 'wc-postfinance-payment-request', plugins_url( 'assets/js/payment-request.js', WC_POSTFINANCE_MAIN_FILE ), array(), WC_POSTFINANCE_VERSION, false );
        }

        /* wp_localize_script( */
        /*     'wc-postfinance-payment-request', */
        /*     'wcPostfinancePaymentRequestParams', */
        /*     array( 'thank_you_payment'  => */ 
        /*                 __( 'Many thanks for your order. You will be taken to a secure connection via the PostFinance website in order to process your payment. ', 'woocommerce-gateway-postfinance' ) */
        /*     ) */
        /* ); */
    }

    /**
     * Check if this gateway is enabled.
     * @return bool
     */
    public function is_available() {
        if ( 'yes' === $this->enabled ) {

            if ( ! is_ssl() ) {
                return false;
            }

            if ( ! $this->pspid || ! $this->sha_in_signature || ! $this->sha_out_signature ) {
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * Initialise Gateway Settings Form Fields.
     * @return null
     */
    public function init_form_fields() {
        $this->form_fields = include( 'settings-postfinance.php' );
    }

    /**
     * Process the payment and return the result.
     * @param  int $order_id
     * @return array
     */
    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );

        $this->payment_on_pending( $order, __( 'Awaiting PostFinance payment.', 'woocommerce-gateway-postfinance' ) );

        return array(
            'result'    => 'success',
            'redirect'  => $this->get_return_url( $order ),
        );
    }

    /**
     * Output for the order received page.
     * @param  int $order_id
     * @return null
     */
    public function thankyou_page( $order_id ) {
        $order = wc_get_order( $order_id );

        echo sprintf( 
          '<section class="woocommerce-gateway-posftfinance-details"><h2 class="postfinance-details-heading">%s</h2><ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">',
          __( 'PostFinance E-Payment', 'woocommerce-gateway-postfinance' ) 
        );

        if ( $order->has_status( 'pending' ) ) {
            echo $this->payment_form( $order );
        } else {
          echo sprintf( 
            '<li class="woocommerce-order-overview__order">%s: <strong>', 
            __( 'Status', 'woocommerce'  )
          );
            if ( $order->has_status( 'processing' ) ) {
                echo $this->status_processing;
            }
            if ( $order->has_status( 'on-hold' ) ) {
                echo $this->status_on_hold;
            } 
            if ( $order->has_status( 'completed' ) ) {
                echo $this->status_completed;
            }
            if ( $order->has_status( 'cancelled' ) ) {
                echo $this->status_cancelled;
            }
            if ( $order->has_status( 'failed' ) ) {
                echo $this->status_failed;
            }
            echo '</strong></li>'; 
        }
        echo '</ul></section>'; 
    }

    /**
     * Payment Form on thankyou page.
     * @param  WC_Order $order
     * @return string
     */
    public function payment_form( $order ) {
        $args = $this->get_postfinance_args( $order );
        $this->log( 'Generating Payment Form for order' . $order->get_order_number() . ': ' . wc_print_r( wc_clean( $args ), true ) );

        $form_args = array();
        $sha_string =  "";
        foreach ( $args as $key => $value ) {
            $form_args[] = '<input type="hidden" name="' . esc_attr( strtoupper( $key ) ) . '" value="'. esc_attr ( $value ) .'"/>';
            $sha_string .= esc_attr ( strtoupper( $key ) ) . '=' . esc_attr ( $value ) . $this->sha_in_signature;
        }

        $sha_digest = $this->return_digest( $this->sha_algo, $sha_string );

        $this->log( 'Generating ' . wc_clean( $this->sha_algo ). ' digest for order' . $order->get_order_number() . ': ' . wc_clean( $sha_digest ) );

        $form_html = sprintf( '
            <li>
                <strong>%s</strong>
                <form method="post" action="%s" id="postfinance-payment-form" name="postfinance-payment-form" target="_self">
                    %s
                    <input type="hidden" name="SHASIGN" value="%s"/>
                    <input type="submit" class="button button-default comment-submit" alt="" id="postfinance-payment-button" value="%s" />
                </form>
            </li>
            <li>
                <a class="button button-default button-cancel cancel" href="%s">%s</a>
            </li>',
            $this->status_pending,
            $this->get_request_url( $this->environment ),
            implode( '', $form_args ),
            $sha_digest,
            $this->pay_button_text,
            esc_url( $order->get_cancel_order_url() ),
            $this->cancel_button_text
        );

        return $form_html;
    }

    /**
     * Get PostFinance Args.
     * @param  WC_Order $order
     * @return array
     */
    protected function get_postfinance_args( $order ) {

        $this->log( 'Generating general parameters PSPID, ORDERID, AMOUNT, CURRENCY and LANGUAGE' );

        /**
         * https://e-payment-postfinance.v-psp.com/en/en/guides/integration%20guides/e-commerce
         *
         * 4.2 Form parameters
         *
         * Although strictly taken the PSPID, ORDERID, AMOUNT, CURRENCY and LANGUAGE fields are sufficient.
         */
        $args = array(
            'amount'    => $this->get_postfinance_amount( $order->get_total() ),
            'currency'  => get_woocommerce_currency(),
            'language'  => 'de_DE',
            'orderid'   => $order->get_id(),
            'pspid'     => $this->pspid,
        );

        /**
         * https://e-payment-postfinance.v-psp.com/en/en/guides/integration%20guides/e-commerce
         *
         * 7.2 Redirection depending on transaction result
         *
         * There are four URLs which our system can redirect the customer to after 
         * a transaction, depending on the result. These are 
         * "ACCEPTURL", "EXCEPTIONURL", "CANCELURL" and "DECLINEURL".
         *
         * 7.3 Redirection with database update
         *
         * You can use the redirection on the redirection URLs to trigger automatic 
         * back-office tasks such as database updates. When a transaction is 
         * executed, we can send the transaction parameters on your redirection URLs.
         * To use this functionality, you must activate this option in the 
         * Transaction feedback tab of your Technical information page,
         * "HTTP redirection in the browser":
         * 
         * “I would like to receive transaction feedback parameters on the redirection URLs”.
         */
        $this->log( 'Generating transaction feedback parameters ACCEPTURL, EXCEPTIONURL, CANCELURL and DECLINEURL' );
        $args = array_merge( $args, array(
            'accepturl'    => WC()->api_request_url( 'WC_Gateway_Postfinance' ),
            'cancelurl'    => WC()->api_request_url( 'WC_Gateway_Postfinance' ),
            'declineurl'   => WC()->api_request_url( 'WC_Gateway_Postfinance' ),
            'exceptionurl' => WC()->api_request_url( 'WC_Gateway_Postfinance' ),
        ));

        /**
         * https://e-payment-postfinance.v-psp.com/en/en/guides/integration%20guides/e-commerce
         *
         * 7.1 Default reaction
         *
         * In this page, we also add a link to your website and/or to your catalogue. 
         * Usually, these links are configured in your PostFinance account's 
         * Administrative details, which is where our system will fetch them. 
         * However, you can also override these URLs by submitting the 
         * HOMEURL and CATALOGURL fields
         */
        $this->log( 'Generating default reaction parameters HOMEURL and CATALOGURL' );
        $args = array_merge( $args, array(
            'catalogurl'   => get_site_url(),
            'homeurl'   => get_site_url(),
        ));

        /**
         * https://e-payment-postfinance.v-psp.com/en/en/guides/integration%20guides/e-commerce
         *
         * 9.1.2 How to return from the payment page to the payment method selection screen
         *
         * To redirect the customer to a URL on your own website, where he can 
         * select another payment method, you can use the "BACKURL".
         */
        $this->log( 'Generating return parameters BACKURL' );
        $args = array_merge( $args, array(
            'backurl'    => WC()->api_request_url( 'WC_Gateway_Postfinance' ),
        ));

        /**
         * https://e-payment-postfinance.v-psp.com/en/en/guides/integration%20guides/e-commerce
         *
         * 10.1 Operation
         *
         * Possible values for new orders:
         * RES: request for authorization
         * SAL: request for sale (payment)
         */
        $this->log( 'Generating operation code parameters OPERATION' );
        $args = array_merge( $args, array(
            'operation' => 'RES',
        ));

        /**
         * https://e-payment-postfinance.v-psp.com/en/en/guides/integration%20guides/e-commerce
         *
         * 7.7.2 Email to the customer
         *
         * You can also choose to send emails to the customer when the transaction 
         * is confirmed (data capture) and when a transaction is refunded, 
         * by ticking the corresponding boxes. As the sender ("From") email address 
         * for these emails, you can configure the 
         * "Support E-mail address to include in transaction-related e-mails". 
         */
        $this->log( 'Generating customer email parameters EMAIL' );
        $args = array_merge( $args, array(
            'email' => $order->billing_email,
        ));

        /**
         * https://e-payment-postfinance.v-psp.com/en/en/guides/integration%20guides/e-commerce
         *
         * 4.2 Form parameters
         *
         * Although strictly taken the PSPID, ORDERID, AMOUNT, CURRENCY and 
         * LANGUAGE fields are sufficient, we nevertheless strongly recommend 
         * you to also send us the customer name (CN), customer’s e-mail (EMAIL),
         * address (OWNERADDRESS), town/city (OWNERTOWN), postcode/ZIP (OWNERZIP), 
         * country (OWNERCTY) and telephone number (OWNERTELNO), as they can 
         * be useful tools for fraud prevention.
         */
        $this->log( 'Generating customer name parameters CN' );
        $args = array_merge( $args, array(
            'cn' => $order->billing_first_name . ' ' . $order->billing_last_name,
        ));

        /**
         * See the comments earlier, Section 4.2
         */
        $this->log( 'Generating customer address parameters OWNERADDRESS, OWNERTOWN, OWNERZIP, OWNERCTY and OWNERTELNO' );
        $args = array_merge( $args, array(
            'owneraddress' => $order->billing_address_1 . ' ' . $order->billing_address_2,
            'ownercty' => $order->billing_country,
            'ownertelno' => $order->billing_phone,
            'ownertown' => $order->billing_city,
            'ownerzip' => $order->billing_postcode,
        ));

        ksort($args);

        return apply_filters( 'woocommerce_postfinance_args', $args);
    }
    /**
     * Get the PostFinance request URL.
     * @param  bool $sandbox Check test mode
     * @return string
     */
    public function get_request_url( $sandbox = false ) {
        if ( $sandbox ) {
            return 'https://e-payment.postfinance.ch/ncol/test/orderstandard_utf8.asp';
        } 
        return 'https://e-payment.postfinance.ch/ncol/prod/orderstandard_utf8.asp';
    }

    /**
     * Get PostFinance amount to pay.
     * @param  float  $total Amount due.
     * @param  string $currency Accepted currency.
     * @return float|int
     */
    public function get_postfinance_amount( $total, $currency = '' ) {
        if ( ! $currency ) {
            $currency = get_woocommerce_currency();
        }

        $total = round( $total, 2 ) * 100; // In cents.

        return $total;
    }

    /**
     * Hash composed string with the SHA algorithm.
     * @param  string $sha SHA algorithm: SHA-1, SHA-256 or SHA-512
     * @param  string $string The string that will be hashed.
     * @return string
     */
    public function return_digest( $sha = 'sha512', $string ) {
        if ( $sha === 'sha512' ) {
            return hash( 'sha512', $string );
        } else if ( $sha === 'sha256' ) {
            return hash( 'sha256', $string );
        } else if ( $sha === 'sha1' ) {
            return hash( 'sha1', $string );
        } else {
            return;
        }
    }

    /**
     * Validate a PostFinance transaction to ensure its authentic.
     * @param  array $response 
     * @param  string $raw_sha_signature
     * @return bool
     */
    protected function validate_transaction( $response, $raw_sha_signature ) {
        if ( ! is_array( $response ) || empty( $raw_sha_signature ) ) {
            return false;
        }
        
        $string = "";
        foreach ( $response as $key => $value ) {
            $string .= strtoupper( $key ) . '=' . $value . $this->sha_out_signature;
        }

        ksort($string);

        $transaction_result = $this->return_digest( $this->sha_algo, $string );
        $this->log( 'Generating ' . wc_clean( $this->sha_algo ). ' digest: ' . wc_clean( strtoupper( $transaction_result ) ) );

        if ( $transaction_result !== strtolower( $raw_sha_signature ) ) {
            $this->log( 'Resulting digest (' . $transaction_result . ') and SHASIGN (' . wc_clean( stripslashes( $raw_sha_signature ) ) . ') do not match. Cheatin, uh?', 'warning' );
            return false;
        } 
        $this->log( 'Received valid response from PostFinance' );
        return true;
    }

    /**
     * Get the order from the PostFinance $_GET response.
     * @param  string $raw_orderID $_GET Data passed back by PostFinance
     * @return bool|WC_Order object
     */
    protected function get_postfinance_order( $raw_orderID ) {
        $order_id = (int) $raw_orderID;
        $order    = wc_get_order( $order_id );

        if ( ! $order ) {
            $this->log( 'Order ID (' . $order_id . ') not found.', 'error' );
            return false;
        }
        return $order;
    }

    /**
     * Capture payment when the order is changed from on-hold to complete or processing.
     * @return null
     */
    public function process_response() {
        $this->log( 'Processing feedback parameters' );
        if ( empty( filter_input( INPUT_GET, 'orderID', FILTER_VALIDATE_INT ) )
             || empty( filter_input( INPUT_GET, 'SHASIGN', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK ) )
            ) {
            $this->log( 'orderID or SHASIGN were not found in feedback.', 'error' );

            return;

        }
        // TODO: REFACTOR
        $order = $this->get_postfinance_order( $response['orderID'] );
        if ( $order && ! $order->has_status( 'pending' ) ) {
            $this->log( 'Aborting, Order ' . $order->get_id() . ' is already processed.', 'warning' );
            $this->log( 'Order status: ' . $order->get_status(), 'warning' );

            return;
        }

        /**
         * https://e-payment-postfinance.v-psp.com/en/en/guides/integration%20guides/e-commerce
         *
         * 7.5 Feedback parameters
         *
         * When a transaction is executed, we can send the following parameter 
         * list to your redirection URLs and/or post-payment feedback URLs.
         *
         * ACCEPTANCE   Acceptance code returned by the acquirer
         * AMOUNT       Order amount (not multiplied by 100)
         * BRAND        Card brand (our system derives this from the card number)
         * CARDNO       Masked card number
         * CN           Cardholder/customer name
         * CURRENCY     Order cposturrency
         * ED           Expiry date
         * NCERROR      Error code
         * ORDERID      Your order reference
         * PAYID        Payment reference in our system
         * PM           Payment method
         * SHASIGN      SHA signature calculated by our system (if SHA-OUT configured)
         * STATUS       Transaction status (see Status overview)
         * TRXDATE      Transaction date
         *
         * In order to verify the integrity of the submitted data, our system 
         * requires each request to include a SHA signature. This signature is 
         * built by hashing the contents of the request, in the 'parameter=value' 
         * format in alphabetical order.
         *
         * Example:
         *     ACCEPTANCE: 1234
         *     NCERROR: 0
         *     orderID: 12
         *     PAYID: 32100123
         *     STATUS: 9
         *
         * SHA-OUT Passphrase: Mysecretsig1875
         * 
         *     ACCEPTANCE=1234Mysecretsig1875NCERROR=0Mysecretsig1875ORDERID=12Mysecretsig1875PAYID=32100123Mysecretsig1875STATUS=9Mysecretsig1875
         *
         * Important! Remove empty paramter such as ACCEPTANCE, NCERROR, ED, CARNO or otherwise the SHA Digest and SHASIGN do not match!
         */
        $feedback = array(
            'ACCEPTANCE'    => array( 'filter'    => FILTER_SANITIZE_STRING, 
                                      'flags'     => FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK ),
            'amount'        => array( 'filter'    => FILTER_SANITIZE_STRING, 
                                      'flags'     => FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK ),
            'BRAND'         => array( 'filter'    => FILTER_SANITIZE_STRING, 
                                      'flags'     => FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK ),
            'CARDNO'        => array( 'filter'    => FILTER_SANITIZE_STRING, 
                                      'flags'     => FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK ),
            'CN'            => array( 'filter'    => FILTER_SANITIZE_STRING, 
                                      'flags'     => FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK ),
            'currency'      => array( 'filter'    => FILTER_SANITIZE_STRING, 
                                      'flags'     => FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK ),
            'ED'            => array( 'filter'    => FILTER_SANITIZE_STRING, 
                                      'flags'     => FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK ),
            'IP'            => FILTER_VALIDATE_IP,
            'NCERROR'       => array( 'filter'    => FILTER_SANITIZE_STRING, 
                                      'flags'     => FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK ),
            /* 'NCERRORPLUS'   => FILTER_SANITIZE_NUMBER_INT, */
            /* 'NCSTATUS'      => FILTER_SANITIZE_NUMBER_INT, */
            'orderID'       => FILTER_SANITIZE_NUMBER_INT,
            'PAYID'         => FILTER_SANITIZE_NUMBER_INT,
            'PM'            => array( 'filter'    => FILTER_SANITIZE_STRING, 
                                      'flags'     => FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK ),
            'STATUS'        => FILTER_SANITIZE_NUMBER_INT,
            'TRXDATE'       => array( 'filter'    => FILTER_SANITIZE_STRING, 
                                      'flags'     => FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK ),
        );

        // If empty or missing remove external variable from input array
        $accepted_parameters = array();
        $removed_parameters = array();
        foreach( array_keys($feedback) as $key ) {
            if ( ! isset( $_GET[$key] ) || $_GET[$key] == '' ) {
                $removed_parameters[] =  strtoupper( $key );
                unset( $feedback[$key] );
            } else  {
                $accepted_parameters[] =  strtoupper( $key );
            }

        }
        $this->log( 'Generating the SHA string based on the accepted parameters ' . implode(', ', $accepted_parameters ) );
        $this->log( 'Removing empty or missing parameters ' . implode(', ', $removed_parameters ) . ' from SHA string' );

        $sha_signature = filter_input( INPUT_GET, 'SHASIGN', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK );

        $response = filter_input_array(INPUT_GET, $feedback);

        $transaction_result = $this->validate_transaction( $response, $sha_signature );

        if ( $transaction_result ) {

            $this->log( 'PostFinance SHA Signature: ' .  wc_clean( stripslashes( $sha_signature ) ) );
            $this->log( 'PostFinance Transaction Result: ' . wc_print_r( wc_clean( $response ), true ) );

            $this->save_postfinance_transaction_data( $order, $response );

            $status_requested = array(9, 91, 92);
            $status_authorised = array(5, 50, 51, 52, 55, 95);
            $status_stored = array(4, 40, 41, 46, 99);
            $status_incomplete = array(0, 1, 2, 3, 93);
            /* $status_anything_else = array(56, 57, 59, 6, 61, 62, 63, 64, 7, 71, 72, 73, 74, 75, 8, 81, 82, 83, 84, 85, 94, 96, 99); */

            if ( in_array( $response['STATUS'], $status_requested ) ) {
                $this->log( 'PostFinance payment completed' );
                $this->payment_complete( $order, wc_clean( $response['PAYID'] ), __( 'PostFinance payment captured. Change payment status to processing or complete.', 'woocommerce-gateway-postfinance' ) );
            } elseif ( in_array( $response['STATUS'], $status_authorised ) ) {
                $this->log( 'PostFinance payment authorized' );
                $this->payment_on_hold( $order, wc_clean( $response['PAYID'] ), __( 'PostFinance payment pending. Change payment status to processing or complete.', 'woocommerce-gateway-postfinance' ) );
            } elseif ( in_array( $response['STATUS'], $status_stored ) ) {
                $this->log( 'PostFinance payment stored. The status is uncertain. Login to find out the actual result.', 'warning' );
                $this->payment_on_hold( $order, wc_clean( $response['PAYID'] ), __( 'Stored waiting external result. Login via the PostFinance website to find out the actual result.', 'woocommerce-gateway-postfinance' ) );
            } elseif ( in_array( $response['STATUS'], $status_incomplete ) ) {
                $this->log( 'PostFinance transaction is declined. It could be only a temporary technical problem. Login to find out the actual result.', 'warning' );
                $this->payment_on_failed( $order, wc_clean( $response['PAYID'] ), __( 'Refused or incomplete. The NCERROR and ACCEPTANCE fields give an explanation of the error.', 'woocommerce-gateway-postfinance' ) );
            // Hier müsste noch ein Canceled by Customer kommen. Dafär müchte die 1 aus status_incomplete raus. Dafür eine Abfrage hier rein. Damit klar ist das der Kunde abgebrochen hat.
            } else {
                $this->log( 'Payment failed! PostFinance response["status"] do not match.', 'warning' );
                $this->payment_on_failed( $order, wc_clean( $response['PAYID'] ), __( 'Validation error. Postfinance status response do not match. Payment failed!', 'woocommerce-gateway-postfinance' ) );
            }
            wp_redirect($order->get_checkout_order_received_url());
            exit;
        } 

        $this->log( 'Received invalid response.', 'warning' );
        wp_die( 'PostFinance Request Failure', 'PostFinance Transaction', array( 'response' => 500 ) );
    }

    /**
     * Save important data from the PostFinance response to the order.
     * @param WC_Order $order
     * @param array $response
     */
    protected function save_postfinance_transaction_data( $order, $response ) {
        if ( ! empty( $response['ACCEPTANCE'] ) ) {
            update_post_meta( $order->get_id(), 'PostFinance Acceptance', wc_clean( $response['ACCEPTANCE'] ) );
        } else {
            update_post_meta( $order->get_id(), 'PostFinance Acceptance', 'Empty' );
        }
        if ( ! empty( $response['amount'] ) ) {
            update_post_meta( $order->get_id(), 'PostFinance Amount', wc_clean( $response['amount'] ) );
        }
        if ( ! empty( $response['currency'] ) ) {
            update_post_meta( $order->get_id(), 'PostFinance Currency', wc_clean( $response['currency'] ) );
        }
        if ( ! empty( $response['IP'] ) ) {
            update_post_meta( $order->get_id(), 'PostFinance IP', wc_clean( $response['IP'] ) );
        }
        if ( ! empty( $response['NCERROR'] ) ) {
            update_post_meta( $order->get_id(), 'PostFinance NC Error', wc_clean( $response['NCERROR'] ) );
        } else {
            update_post_meta( $order->get_id(), 'PostFinance NC Error', 'Empty' );
        }
        /* if ( ! empty( $response['NCERRORPLUS'] ) ) { */
        /*     update_post_meta( $order->get_id(), 'PostFinance NC Error Plus', wc_clean( $response['NCERRORPLUS'] ) ); */
        /* } */
        /* if ( ! empty( $response['NCSTATUS'] ) ) { */
        /*     update_post_meta( $order->get_id(), 'PostFinance NC Status', wc_clean( $response['NCSTATUS'] ) ); */
        /* } */
        if ( ! empty( $response['PAYID'] ) ) {
            update_post_meta( $order->get_id(), 'PostFinance PayID', wc_clean( $response['PAYID'] ) );
        }
        if ( ! empty( $response['STATUS'] ) ) {
            update_post_meta( $order->get_id(), 'PostFinance Status', wc_clean( $response['STATUS'] ) );
        }
        if ( ! empty( $response['TRXDATE'] ) ) {
            update_post_meta( $order->get_id(), 'PostFinance transaction date', wc_clean( $response['TRXDATE'] ) );
        }
    }

    /**
     * Complete order, add transaction ID and notes.
     * @param  WC_Order $order
     * @param  string   $pay_id
     * @param  string   $note
     */
    protected function payment_complete( $order, $pay_id = '', $note = '' ) {
        // Vorsicht! Dies könnte den Lagerbestand doppelt reduzieren.
        wc_reduce_stock_levels( $order->get_id() );
        WC()->cart->empty_cart();
        $order->payment_complete( $pay_id );
        $this->add_order_notes( 'PayId: ' . $pay_id, $order);
        $this->add_order_notes( $note, $order);
    }

    /**
     * Hold order and add notes.
     * @param  WC_Order $order
     * @param  string   $reason
     */
    protected function payment_on_hold( $order, $pay_id = '', $note = '' ) {
        $order->update_status( 'on-hold' );
        $this->add_order_notes( 'PayId: ' . $pay_id, $order);
        $this->add_order_notes( $note, $order);
        WC()->cart->empty_cart();
    }

    /**
     * Pending order, add notes.
     * @param  WC_Order $order
     * @param  string   $reason
     */
    protected function payment_on_pending( $order, $note = '' ) {
        $order->add_order_note( $note );
        WC()->cart->empty_cart();
    }

    /**
     * Failed order, add notes.
     * @param  WC_Order $order
     * @param  string   $reason
     */
    protected function payment_on_failed( $order, $pay_id='', $note = '' ) {
        $order->update_status( 'failed' );
        $this->add_order_notes( 'PayId: ' . $pay_id, $order);
        $this->add_order_notes( $note, $order);
    }

    /**
     * Add order notes.
     * @param  string   $note
     * @param  WC_Order $order
     */
    protected function add_order_notes( $note, $order) {
        $order->add_order_note( $note );
    }

    /**
     * Sends the failed order email to admin.
     * @param  int $order_id
     * @return null
     */
    public function send_failed_order_email( $order_id ) {
        $emails = WC()->mailer()->get_emails();

        if ( ! empty( $emails ) && ! empty( $order_id ) ) {
            $emails['WC_Email_Failed_Order']->trigger( $order_id );
        }
    }

    /**
     * Logs
     * @param  string $message
     * @param  string $level
     * @return null
     */
    public function log( $message, $level = 'info' ) {
        if ( $this->logging ) {
            WC_Postfinance::log( $message, $level );
        }
    }
}
