<?php

/**
 * payfast.php
 *
 * Main module file which is responsible for installing, editing and deleting
 * module details from DB and sending data to Payfast.
 *
 * Copyright (c) 2026 Payfast (Pty) Ltd
 * You (being anyone who is not Payfast (Pty) Ltd) may download and use this plugin / code in your own website in
 * conjunction with a registered and active Payfast account. If your Payfast account is terminated for any reason,
 * you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or
 * part thereof in any way.
 */


// Load dependency files
if (defined('MODULE_PAYMENT_PF_DEBUG') && !defined('PF_DEBUG')) {
    define('PF_DEBUG', MODULE_PAYMENT_PF_DEBUG == 'True');
}
// phpcs:disable
include_once (IS_ADMIN_FLAG === true ? DIR_FS_CATALOG_MODULES : DIR_WS_MODULES) . 'payment/payfast/payfast_functions.php';

require_once __DIR__ . '/payfast/vendor/autoload.php';
require_once __DIR__ . '/payfast/payfastinstaller.php';

// phpcs:enable

use Payfast\PayfastCommon\Aggregator\Request\PaymentRequest;

if (!defined('PF_MODULE_NAME')) {
    define('PF_MODULE_NAME', 'Payfast_ZenCart');
}
if (!defined('PF_MODULE_VER')) {
    define('PF_MODULE_VER', '1.5.0');
}

/**
 * payfast
 *
 * Class for Payfast
 *
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 */
class payfast extends base
{
    /**
     * payfast
     *
     * Constructor
     *
     * >> Standard ZenCart
     *
     * @param int $id
     *
     * @return payfast
     * @author Payfast (Pty) Ltd
     */

    private const DELETE_LITERAL = 'DELETE FROM ';
    private const CREATE_LITERAL = 'CREATE TABLE ';
    private const INSERT_LITERAL = 'INSERT INTO ';

    /**
     * $code string repesenting the payment method
     * @var string
     */
    public string $code;

    /**
     * $title is the displayed name for this payment method
     * @var string
     */
    public string $title;

    /**
     * $description is a soft name for this payment method
     * @var string
     */
    public string $description;

    /**
     * $enabled determines whether this module shows or not... in catalog.
     *
     * @var bool
     */
    public bool $enabled;
    public string $codeVersion;
    public string $transaction_currency;
    public string $form_action_url;
    public int $order_status;
    public int|string $sort_order;

    /**
     * @param string $id
     */
    public function __construct(string $id = '')
    {
        global $order, $messageStack;
        $this->code            = 'payfast';
        $this->codeVersion     = '1.5.8';
        $this->form_action_url = '';
        $this->sort_order      = 0;

        if (IS_ADMIN_FLAG === true) {
            $this->title = 'Payfast Aggregation';
            if (defined('MODULE_PAYMENT_PF_SERVER')) {
                $this->title .= $this->getTestModeAlert();
            } else {
                $this->title .= '<span class="alert"> (test mode active)</span>';
            }
        } else {
            $this->title = MODULE_PAYMENT_PF_TEXT_CATALOG_TITLE;
        }

        $this->description = MODULE_PAYMENT_PF_TEXT_DESCRIPTION;

        if (defined('MODULE_PAYMENT_PF_SORT_ORDER')) {
            $this->sort_order = MODULE_PAYMENT_PF_SORT_ORDER;
        }

        if (defined('MODULE_PAYMENT_PF_STATUS')) {
            $this->enabled = (MODULE_PAYMENT_PF_STATUS == 'True');
        }

        $this->setOrderStatus();

        if (is_object($order)) {
            $this->update_status();
        }

        $this->setFormActionUrl();
    }

    /**
     * @return string
     */
    private function getTestModeAlert(): string
    {
        if (MODULE_PAYMENT_PF_SERVER == 'Test') {
            return '<span class="alert"> (test mode active)</span>';
        }

        return '';
    }

    /**
     * @return void
     */
    private function setOrderStatus(): void
    {
        if (defined('MODULE_PAYMENT_PF_ORDER_STATUS_ID') && (int)MODULE_PAYMENT_PF_ORDER_STATUS_ID > 0) {
            $this->order_status = MODULE_PAYMENT_PF_ORDER_STATUS_ID;
        }
    }

    /**
     * @return void
     */
    private function setFormActionUrl(): void
    {
        if (defined('MODULE_PAYMENT_PF_SERVER')) {
            $this->form_action_url = 'https://';
            $this->form_action_url .= (MODULE_PAYMENT_PF_SERVER == 'Test') ? MODULE_PAYMENT_PF_SERVER_TEST : MODULE_PAYMENT_PF_SERVER_LIVE;
        }
        $this->form_action_url .= '/eng/process';
    }


    /**
     * update_status
     *
     * Calculate zone matches and flag settings to determine whether this
     * module should display to customers or not.
     *
     * @author Payfast (Pty) Ltd
     * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
     */
    public function update_status(): void
    {
        global $order, $db;

        if ($this->enabled && ((int)MODULE_PAYMENT_PF_ZONE > 0)) {
            $check_flag  = false;
            $check_query = $db->Execute(
                'SELECT `zone_id`
                FROM ' . TABLE_ZONES_TO_GEO_ZONES . "
                WHERE `geo_zone_id` = '" . MODULE_PAYMENT_PF_ZONE . "'
                  AND `zone_country_id` = '" . $order->billing['country']['id'] . "'
                ORDER BY `zone_id`"
            );

            while (!$check_query->EOF) {
                if (
                    $check_query->fields['zone_id'] < 1 ||
                    $check_query->fields['zone_id'] == $order->billing['zone_id']
                ) {
                    $check_flag = true;
                    break;
                }
                $check_query->MoveNext();
            }

            if (!$check_flag) {
                $this->enabled = false;
            }
        }
    }

    /**
     * javascript_validation
     *
     * JS validation which does error-checking of data-entry if this module is selected for use
     * (Number, Owner, and CVV Lengths)
     *
     * >> Standard ZenCart
     * @return bool|string
     * @author Payfast (Pty) Ltd
     * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
     */
    public function javascript_validation(): bool|string
    {
        return false;
    }

    /**
     * selection
     *
     * Displays payment method name along with Credit Card Information
     * Submission Fields (if any) on the Checkout Payment Page.
     *
     * >> Standard ZenCart
     * @return array
     * @author Payfast (Pty) Ltd
     */
    public function selection(): array
    {
        return [
            'id'     => $this->code,
            'module' => MODULE_PAYMENT_PF_TEXT_CATALOG_LOGO,
            'icon'   => MODULE_PAYMENT_PF_TEXT_CATALOG_LOGO
        ];
    }

    /**
     * pre_confirmation_check
     *
     * Normally evaluates the Credit Card Type for acceptance and the validity of the Credit Card Number &
     * Expiration Date
     * Since payfast module is not collecting info, it simply skips this step.
     *
     * >> Standard ZenCart
     * @return bool
     * @author Payfast (Pty) Ltd
     */
    public function pre_confirmation_check(): bool
    {
        return false;
    }

    /**
     * confirmation
     *
     * Display Credit Card Information on the Checkout Confirmation Page
     * Since none is collected for payfast before forwarding to payfast site, this is skipped
     *
     * >> Standard ZenCart
     * @return bool
     * @author Payfast (Pty) Ltd
     */
    public function confirmation(): bool
    {
        return false;
    }

    /**
     * process_button
     *
     * Build the data and actions to process when the "Submit" button is
     * pressed on the order-confirmation screen.
     *
     * This sends the data to the payment gateway for processing.
     * (These are hidden fields on the checkout confirmation page)
     *
     * >> Standard ZenCart
     * @return string
     * @author Payfast (Pty) Ltd
     */
    public function process_button(): string
    {
        // Variable initialization
        global $db, $order, $currencies, $currency;
        $buttonArray = [];

        $paymentRequest = new PaymentRequest(true);

        $merchantId  = MODULE_PAYMENT_PF_MERCHANT_ID;
        $merchantKey = MODULE_PAYMENT_PF_MERCHANT_KEY;

        // Create URLs
        $returnUrl = zen_href_link(FILENAME_CHECKOUT_PROCESS, 'referer=payfast', 'SSL');
        $cancelUrl = zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL');
        $notifyUrl = zen_href_link('payfast_itn_handler.php', '', 'SSL', false, false, true);

        //// Set the currency and get the order amount
        $currency                   = 'ZAR';
        $currencyDecPlaces          = $currencies->get_decimal_places($currency);
        $totalsum                   = $order->info['total'];
        $this->transaction_currency = $currency;
        $transaction_amount         = ($totalsum * $currencies->get_value($currency));

        //// Generate the order description
        $orderDescription = '';

        foreach ($order->products as $product) {
            $price    = round($product['final_price'] * (100 + $product['tax']) / 100, 2);
            $priceStr = number_format($price, $currencyDecPlaces);

            $productName = html_entity_decode(
                strip_tags($product['name']),
                ENT_QUOTES,
                'UTF-8'
            );

            $productName      = preg_replace('/\s+/', ' ', $productName);
            $productName      = trim($productName);
            $orderDescription .= $product['qty'] . ' x ' . $productName;

            if ($product['qty'] > 1) {
                $linePrice    = $price * $product['qty'];
                $linePriceStr = number_format($linePrice, $currencyDecPlaces);

                $orderDescription .= ' @ ' . $priceStr . 'ea = ' . $linePriceStr;
            } else {
                $orderDescription .= ' = ' . $priceStr;
            }

            $orderDescription .= '; ';
        }

        $orderDescription .= 'Shipping = ' . number_format($order->info['shipping_cost'], $currencyDecPlaces) . '; ';
        $orderDescription .= 'Total= ' . number_format($transaction_amount, $currencyDecPlaces) . '; ';


        //// Save the session (and remove expired sessions)
        pf_removeExpiredSessions();
        $tsExpire = strtotime('+' . PF_SESSION_LIFE . ' days');


        // Delete existing record (if it exists)
        $sql =
            self::DELETE_LITERAL . TABLE_PAYFAST_SESSION . "
            WHERE `session_id` = '" . zen_db_input(zen_session_id()) . "'";
        $db->Execute($sql);

        // patch for multi-currency - AGB 19/07/13 - see also the ITN handler
        $_SESSION['payfast_amount'] = number_format($transaction_amount, $currencyDecPlaces, '.', '');

        // remove amp; before POSTing to Payfast
        $cancelUrl = str_replace('amp;', '', $cancelUrl);
        $returnUrl = str_replace('amp;', '', $returnUrl);

        //// Set the data
        $mPaymentId = pf_createUUID();
        $data       = [
            // Merchant fields
            'merchant_id'   => $merchantId,
            'merchant_key'  => $merchantKey,
            'return_url'    => $returnUrl,
            'cancel_url'    => $cancelUrl,
            'notify_url'    => $notifyUrl,

            // Customer details
            'name_first'    => replace_accents($order->customer['firstname']),
            'name_last'     => replace_accents($order->customer['lastname']),
            'email_address' => $order->customer['email_address'],

            'm_payment_id'     => $mPaymentId,
            'amount'           => number_format($transaction_amount, $currencyDecPlaces, '.', ''),

            // Item Details
            'item_name'        => MODULE_PAYMENT_PF_PURCHASE_DESCRIPTION_TITLE . $mPaymentId,
            'item_description' => substr($orderDescription, 0, 254),
            'custom_str1'      => PF_MODULE_NAME . '_' . PF_MODULE_VER,
            'custom_str2'      => zen_session_name() . '=' . zen_session_id(),
        ];

        $_SESSION['guest_detail'] = json_encode($_POST);

        $sql =
            self::INSERT_LITERAL . TABLE_PAYFAST_SESSION . "
                ( session_id, saved_session, expiry )
            VALUES (
                '" . zen_db_input(zen_session_id()) . "',
                '" . base64_encode(serialize($_SESSION)) . "',
                '" . date(PF_FORMAT_DATETIME_DB, $tsExpire) . "' )";
        $db->Execute($sql);

        $pfOutput = '';
        // Create output string
        foreach ($data as $name => $value) {
            $pfOutput .= $name . '=' . urlencode(trim($value)) . '&';
        }

        $passPhrase = MODULE_PAYMENT_PF_PASSPHRASE;

        $pfOutput = substr($pfOutput, 0, -1);

        if (!empty($passPhrase)) {
            $pfOutput = $pfOutput . '&passphrase=' . urlencode($passPhrase);
        }

        $data['signature'] = md5($pfOutput);
        $paymentRequest->pflog("Data to send:\n" . print_r($data, true));


        //// Check the data and create the process button array

        foreach ($data as $name => $value) {
            // Remove quotation marks
            $value = str_replace('"', '', $value);

            $buttonArray[] = zen_draw_hidden_field($name, $value);
        }

        return implode("\n", $buttonArray) . "\n";
    }

    /**
     * before_process
     *
     * Store transaction info to the order and process any results that come
     * back from the payment gateway
     *
     * >> Standard ZenCart
     * >> Called when the user is returned from the payment gateway
     * @author Payfast (Pty) Ltd
     */
    public function before_process(): void
    {
        $pre            = __METHOD__ . ' : ';
        $paymentRequest = new PaymentRequest(true);

        $paymentRequest->pflog($pre . 'bof');

        // Variable initialization
        global $db, $order_total_modules, $insert_id;

        // If page was called correctly with "referer" tag
        if (isset($_GET['referer']) && strcasecmp($_GET['referer'], 'payfast') == 0) {
            $this->notify('NOTIFY_PAYMENT_PAYFAST_RETURN_TO_STORE');

            $this->notify('NOTIFY_CHECKOUT_PROCESS_BEFORE_CART_RESET', $insert_id);

            // Reset all session variables
            $_SESSION['cart']->reset(true);
            unset($_SESSION['sendto']);
            unset($_SESSION['billto']);
            unset($_SESSION['shipping']);
            unset($_SESSION['payment']);
            unset($_SESSION['comments']);
            unset($_SESSION['cot_gv']);
            $order_total_modules->clear_posts();

            $this->notify('NOTIFY_HEADER_END_CHECKOUT_PROCESS');

            // Redirect to the checkout success page
            zen_redirect(zen_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'SSL'));
        } else {
            $this->notify('NOTIFY_PAYMENT_PAYFAST_CANCELLED_DURING_CHECKOUT');

            // Remove the pending Payfast transaction from the table
            if (isset($_SESSION['pf_m_payment_id'])) {
                $sql =
                    self::DELETE_LITERAL . pf_getActiveTable() . '
                    WHERE `m_payment_id` = ' . $_SESSION['pf_m_payment_id'] . '
                    LIMIT 1';
                $db->Execute($sql);

                unset($_SESSION['pf_m_payment_id']);
            }

            // Redirect to the payment page
            zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
        }
    }

    /**
     * check_referrer
     *
     * Checks referrer
     *
     * >> Standard ZenCart
     *
     * @param string $zf_domain
     *
     * @return bool
     * @author Payfast (Pty) Ltd
     */
    public function check_referrer(string $zf_domain): bool
    {
        return true;
    }

    /**
     * after_process
     *
     * Post-processing activities
     *
     * >> Standard ZenCart
     * @return bool
     * @author Payfast (Pty) Ltd
     */
    public function after_process(): bool
    {
        $pre            = __METHOD__ . ' : ';
        $paymentRequest = new PaymentRequest(true);

        $paymentRequest->pflog($pre . 'bof');

        $this->notify('NOTIFY_HEADER_START_CHECKOUT_PROCESS');

        // Set 'order not created' flag
        $_SESSION['order_created'] = '';

        return false;
    }

    /**
     * Used to display error message details
     *
     * @return bool
     * @author Payfast (Pty) Ltd
     */
    public function output_error(): bool
    {
        return false;
    }

    /**
     * Check to see whether module is installed
     *
     * >> Standard ZenCart
     * @return bool
     * @author Payfast (Pty) Ltd
     */
    public function check(): bool
    {
        // Variable initialization
        global $db;

        if (!isset($this->_check)) {
            $check_query  = $db->Execute(
                'SELECT `configuration_value`
                FROM ' . TABLE_CONFIGURATION . "
                WHERE `configuration_key` = 'MODULE_PAYMENT_PF_STATUS'"
            );
            $this->_check = $check_query->RecordCount();
        }

        return $this->_check;
    }

    /**
     * install
     *
     * Installs Payfast payment module in osCommerce and creates necessary
     * configuration fields which need to be supplied by store owner.
     *
     * >> Standard ZenCart
     * @author Payfast (Pty) Ltd
     */
    public function install(): void
    {
        // Variable Initialization
        global $db;
        $installer = new payfastinstaller($db);
        $installer->install();
    }

    /**
     * remove
     *
     * Remove the module and all its settings. Leaves the tables which were
     * created as they will have information from past orders which is still
     * relevant and required.
     *
     * >> Standard ZenCart
     * @author Payfast (Pty) Ltd
     */
    public function remove(): void
    {
        // Variable Initialization
        global $db;

        // Remove all configuration variables
        $db->Execute(
            self::DELETE_LITERAL . TABLE_CONFIGURATION . "
            WHERE `configuration_key` LIKE 'MODULE\_PAYMENT\_PF\_%'"
        );

        $this->notify('NOTIFY_PAYMENT_PAYFAST_UNINSTALLED');
    }

    /**
     * keys
     *
     * Returns an array of the configuration keys for the module
     *
     * >> Standard osCommerce
     * @return array
     * @author Payfast (Pty) Ltd
     */
    public function keys(): array
    {
        // Variable initialization
        return [
            'MODULE_PAYMENT_PF_STATUS',
            'MODULE_PAYMENT_PF_MERCHANT_ID',
            'MODULE_PAYMENT_PF_MERCHANT_KEY',
            'MODULE_PAYMENT_PF_PASSPHRASE',
            'MODULE_PAYMENT_PF_SERVER',
            'MODULE_PAYMENT_PF_SORT_ORDER',
            'MODULE_PAYMENT_PF_ZONE',
            'MODULE_PAYMENT_PF_PREPARE_ORDER_STATUS_ID',
            'MODULE_PAYMENT_PF_ORDER_STATUS_ID',
            'MODULE_PAYMENT_PF_DEBUG',
            'MODULE_PAYMENT_PF_DEBUG_EMAIL',
        ];
    }

    /**
     * after_order_create
     *
     * >> Standard osCommerce
     * @author Payfast (Pty) Ltd
     */
    public function after_order_create($insert_id): bool
    {
        $pre            = __METHOD__ . ' : ';
        $paymentRequest = new PaymentRequest(true);

        $paymentRequest->pflog($pre . 'bof');

        return false;
    }
}
