<?php

/**
 * payfast_itn_handler
 *
 * Callback handler for Payfast ITN
 *
 * Copyright (c) 2026 Payfast (Pty) Ltd
 * You (being anyone who is not Payfast (Pty) Ltd) may download and use this plugin / code in your own
 * website in conjunction with a registered and active Payfast account. If your Payfast account is terminated for any
 * reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code
 * or part thereof in any way.
 */

$show_all_errors   = false;
$current_page_base = 'payfastitn';
$loaderPrefix      = 'payfast_itn';

// Guard against multiple includes by checking if key constants are already defined
// These are defined in configure.php and defined_paths.php
if (!defined('DIR_FS_CATALOG')) {
    require_once 'includes/configure.php';
    require_once 'includes/defined_paths.php';
}
require_once 'includes/modules/payment/payfast/payfast_functions.php';
require_once 'includes/application_top.php';

if (!defined('TOPMOST_CATEGORY_PARENT_ID')) {
    define('TOPMOST_CATEGORY_PARENT_ID', 0);
}

// These classes are already loaded by application_top.php via InitSystem
// Explicitly requiring them again causes "Cannot redeclare" errors
// But we need to ensure they're available if not already loaded
if (!class_exists('order')) {
    require_once DIR_WS_CLASSES . 'order.php';
}
if (!class_exists('order_total')) {
    require_once DIR_WS_CLASSES . 'order_total.php';
}
if (!class_exists('Customer')) {
    require_once DIR_WS_CLASSES . 'customer.php';
}
if (!class_exists('shopping_cart')) {
    require_once DIR_WS_CLASSES . 'shopping_cart.php';
}
if (!class_exists('payment')) {
    require_once DIR_WS_CLASSES . 'payment.php';
}
require_once 'includes/modules/payment/payfast/vendor/autoload.php';
require_once 'includes/classes/PayfastConfig.php';
require_once 'includes/classes/PayfastLogger.php';
require_once 'includes/classes/PayfastITN.php';
require_once 'includes/classes/ZenCartOrderManager.php';

// Load email language constants (optional - we have fallbacks in getOrderConfirmationMessage)
$emailLangFile = DIR_WS_LANGUAGES . (isset($GLOBALS['language']) ? $GLOBALS['language'] : 'english') . '/email_template_checkout.php';
if ($emailLangFile && file_exists($emailLangFile)) {
    require_once $emailLangFile;
}

if (!defined('PF_SOFTWARE_NAME')) {
    define('PF_SOFTWARE_NAME', 'ZenCart');
}
if (!defined('PF_SOFTWARE_VER')) {
    define('PF_SOFTWARE_VER', PROJECT_VERSION_MAJOR . '.' . PROJECT_VERSION_MINOR);
}
if (!defined('PF_MODULE_NAME')) {
    define('PF_MODULE_NAME', 'Payfast_ZenCart');
}
if (!defined('PF_MODULE_VER')) {
    define('PF_MODULE_VER', '1.5.0');
}
if (!defined('MODULE_PAYMENT_PF_SERVER_LIVE')) {
    define('MODULE_PAYMENT_PF_SERVER_LIVE', 'payfast.co.za');
}
if (!defined('MODULE_PAYMENT_PF_SERVER_TEST')) {
    define('MODULE_PAYMENT_PF_SERVER_TEST', 'sandbox.payfast.co.za');
}
if (!defined('PF_DEBUG')) {
    define('PF_DEBUG', true);
}

class PayfastITNHandler
{
    private PayfastConfig $config;
    private PayfastLogger $logger;
    private PayfastITN $itn;
    private ZenCartOrderManager $orderManager;

    public function __construct(object $db)
    {
        $this->config       = new PayfastConfig();
        $this->itn          = new PayfastITN();
        $this->logger       = new PayfastLogger($this->itn->getPaymentRequest());
        $this->orderManager = new ZenCartOrderManager($db);
    }

    public function handleRequest(): void
    {
        try {
            header('HTTP/1.0 200 OK');
            flush();

            $data = $this->itn->getData();
            if ($data === false) {
                throw new Exception(PayfastITN::PF_ERR_BAD_ACCESS);
            }

            if (!$this->itn->isSignatureValid($this->config->getPassphrase())) {
                throw new Exception(PayfastITN::PF_ERR_INVALID_SIGNATURE);
            }
            if (!$this->itn->isDataValid($this->config->getModuleInfo(), $this->config->getServer())) {
                throw new Exception(PayfastITN::PF_ERR_BAD_ACCESS);
            }

            $this->processTransaction($data);
        } catch (Exception $e) {
            $this->handleError($e->getMessage(), $data ?? []);
        } finally {
            $this->logger->close();
        }
    }

    private function processTransaction(array $data): void
    {
        global $zco_notifier;
        $zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_BEGIN');
        list($pfOrderId, $zcOrderId, $txnType) = $this->orderManager->lookupTransaction($data);
        $ts = time();

        switch ($txnType) {
            case 'new':
                $this->handleNewTransaction($data, $ts);
                break;
            case 'cleared':
                $this->handleClearedTransaction($data, $pfOrderId, $zcOrderId, $ts);
                break;
            case 'update':
                $this->handleUpdateTransaction($data, $pfOrderId, $ts);
                break;
            case 'failed':
                $this->handleFailedTransaction($data, $pfOrderId, $zcOrderId, $ts);
                break;
            default:
                break;
        }

        if ($txnType !== 'new' && isset($newStatus)) {
            $this->orderManager->updateOrderStatusAndHistory($data, $zcOrderId, $txnType, $ts, $newStatus);
        }
    }

    private function handleNewTransaction(array $data, int $ts): void
    {
        global $order, $zco_notifier, $order_total_modules, $order_totals;
        list($zcSessName, $zcSessID) = explode('=', $data['custom_str2']);
        $session = $this->orderManager->retrieveSession($zcSessID);
        $this->orderManager->createOrderEnvironment($session);

        // Ensure customer_id is set
        if (!isset($session['customer_id'])) {
            if (isset($data['custom_int1'])) {
                $_SESSION['customer_id'] = $data['custom_int1'];
            } else {
                throw new Exception('Missing customer_id in session or Payfast data');
            }
        }

        // Initialize Customer object
        $customer = new Customer($_SESSION['customer_id']);

        if (!isset($session['cart']) || !is_object($session['cart'])) {
            $session['cart']  = new shoppingCart();
            $_SESSION['cart'] = $session['cart'];
        }

        if (!$this->orderManager->checkOrderData($data)) {
            throw new Exception(PayfastITN::PF_ERR_AMOUNT_MISMATCH);
        }

        $order = new order();
        if (is_null($order)) {
            throw new Exception('Failed to initialize order object');
        }

        $order_total_modules = new order_total();
        $zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_BEFORE_ORDER_TOTALS_PROCESS');
        $order_totals = $order_total_modules->process();
        $zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_AFTER_ORDER_TOTALS_PROCESS');

        $zcOrderId = $this->orderManager->createOrder($order, $order_totals);
        $pfOrderId = $this->orderManager->createPayfastOrder($data, $zcOrderId, $ts);
        $this->orderManager->createPayfastHistory($data, $pfOrderId, $ts);

        $newStatus = ($data['payment_status'] === 'PENDING') ?
            MODULE_PAYMENT_PF_PROCESSING_STATUS_ID : MODULE_PAYMENT_PF_ORDER_STATUS_ID;
        $this->orderManager->updateOrderStatus(
            $zcOrderId,
            $newStatus,
            'Payfast status: ' . $data['payment_status'],
            $ts
        );

        $this->orderManager->addProductsToOrder($order, $zcOrderId);

        // Send order confirmation email to customer
        $order = new order($zcOrderId);
        $zco_notifier->notify('NOTIFY_ORDER_CREATED', (int)$zcOrderId);

        // Ensure zcDate is initialized for email template processing
        global $zcDate;
        if (!isset($zcDate) || !is_object($zcDate)) {
            if (!class_exists('zcDate')) {
                require_once DIR_WS_CLASSES . 'zcDate.php';
            }
            $zcDate = new zcDate();
        }

        // Send order confirmation email via zen_mail
        zen_mail(
            $order->customer['name'],
            $order->customer['email_address'],
            defined(
                'EMAIL_TEXT_SUBJECT'
            ) ? EMAIL_TEXT_SUBJECT . EMAIL_ORDER_NUMBER_SUBJECT . $zcOrderId : 'Order Confirmation',
            $this->getOrderConfirmationMessage($order),
            STORE_OWNER,
            STORE_OWNER_EMAIL_ADDRESS,
            [],
            'checkout'
        );

        $this->orderManager->deleteSession($zcSessID);
        $this->logger->log('Payfast ITN Complete');
    }

    private function handleClearedTransaction(array $data, int $pfOrderId, int $zcOrderId, int $ts): void
    {
        $this->orderManager->createPayfastHistory($data, $pfOrderId, $ts);
        $newStatus = MODULE_PAYMENT_PF_ORDER_STATUS_ID;
        $this->orderManager->updateOrderStatus(
            $zcOrderId,
            $newStatus,
            'Payfast status: ' . $data['payment_status'],
            $ts
        );
    }

    private function handleUpdateTransaction(array $data, int $pfOrderId, int $ts): void
    {
        $this->orderManager->createPayfastHistory($data, $pfOrderId, $ts);
    }

    private function handleFailedTransaction(array $data, int $pfOrderId, int $zcOrderId, int $ts): void
    {
        $this->orderManager->createPayfastHistory($data, $pfOrderId, $ts);
        $newStatus = MODULE_PAYMENT_PF_PREPARE_ORDER_STATUS_ID;
        $this->orderManager->updateOrderStatus(
            $zcOrderId,
            $newStatus,
            'Payment failed (Payfast id = ' . $data['pf_payment_id'] . ')',
            $ts
        );

        $this->sendErrorEmail(
            'Payfast ITN Transaction on your site',
            "A failed Payfast transaction on your website requires attention\n" .
            "------------------------------------------------------------\n" .
            "Site: " . STORE_NAME . ' (' . HTTP_SERVER . DIR_WS_CATALOG . ")\n" .
            "Order ID: $zcOrderId\n" .
            "Payfast Transaction ID: " . $data['pf_payment_id'] . "\n" .
            "Payfast Payment Status: " . $data['payment_status'] . "\n" .
            "Order Status Code: $newStatus"
        );
    }

    private function handleError(string $errorMessage, array $data): void
    {
        $this->logger->log("Error: $errorMessage");
        header('HTTP/1.1 500 Internal Server Error');
        flush();

        $body = "An invalid Payfast transaction on your website requires attention\n" .
                "------------------------------------------------------------\n" .
                "Site: " . STORE_NAME . ' (' . HTTP_SERVER . DIR_WS_CATALOG . ")\n" .
                "Remote IP Address: " . $_SERVER['REMOTE_ADDR'] . "\n" .
                "Remote host name: " . gethostbyaddr($_SERVER['REMOTE_ADDR']) . "\n" .
                (isset($data['pf_payment_id']) ? "Payfast Transaction ID: " . $data['pf_payment_id'] . "\n" : '') .
                (isset($data['payment_status']) ? "Payfast Payment Status: " . $data['payment_status'] . "\n" : '') .
                "Error: $errorMessage";
        if ($errorMessage === PayfastITN::PF_ERR_AMOUNT_MISMATCH) {
            $body .= "\nValue received: " . $data['amount_gross'] . "\nValue should be: " . $_SESSION['payfast_amount'];
        }
        $this->sendErrorEmail("Payfast ITN error: $errorMessage", $body);
    }

    private function sendErrorEmail(string $subject, string $body): void
    {
        zen_mail(
            STORE_OWNER,
            $this->config->getDebugEmail(),
            $subject,
            "Hi,\n\n" . $body . "\n------------------------------------------------------------\n",
            STORE_OWNER,
            STORE_OWNER_EMAIL_ADDRESS,
            null,
            'debug'
        );
    }

    private function getOrderConfirmationMessage(object $order): string
    {
        // Define fallback constants if not already defined
        if (!defined('EMAIL_TEXT_SUBJECT_ORDER_CONFIRMATION')) {
            define('EMAIL_TEXT_SUBJECT_ORDER_CONFIRMATION', 'Order Confirmation');
        }
        if (!defined('EMAIL_TEXT_ORDER_NUMBER')) {
            define('EMAIL_TEXT_ORDER_NUMBER', 'Order #');
        }
        if (!defined('EMAIL_TEXT_INVOICE_URL')) {
            define('EMAIL_TEXT_INVOICE_URL', 'View Your Order:');
        }
        if (!defined('EMAIL_TEXT_PRODUCTS')) {
            define('EMAIL_TEXT_PRODUCTS', 'Products Ordered:');
        }
        if (!defined('EMAIL_TEXT_PAYMENT_METHOD')) {
            define('EMAIL_TEXT_PAYMENT_METHOD', 'Payment Method:');
        }

        $message = EMAIL_TEXT_SUBJECT_ORDER_CONFIRMATION . "\n\n";
        $message .= EMAIL_TEXT_ORDER_NUMBER . ' ' . $order->info['order_number'] . "\n";

        // Build order URL with fallback for constants
        $catalogServer = defined('HTTP_CATALOG_SERVER') ? HTTP_CATALOG_SERVER : (defined(
            'HTTP_SERVER'
        ) ? HTTP_SERVER : 'https://example.com');
        $wsRoot        = defined('DIR_WS_CATALOG') ? DIR_WS_CATALOG : '/';
        $message       .= EMAIL_TEXT_INVOICE_URL . ' ' . $catalogServer . $wsRoot . 'index.php?main_page=account&page=order&order_id=' . $order->info['orders_id'] . "\n\n";

        $message .= EMAIL_TEXT_PRODUCTS . "\n";
        $message .= "----\n";
        for ($i = 0; $i < sizeof($order->products); $i++) {
            $message .= $order->products[$i]['qty'] . ' x ' . $order->products[$i]['name'];
            if (isset($order->products[$i]['attributes']) && sizeof($order->products[$i]['attributes']) > 0) {
                for ($j = 0; $j < sizeof($order->products[$i]['attributes']); $j++) {
                    $message .= "\n    " . $order->products[$i]['attributes'][$j]['option'] . ' ' . $order->products[$i]['attributes'][$j]['value'];
                }
            }
            $message .= ' (' . $order->products[$i]['model'] . ') = ' . $order->products[$i]['final_price'] * $order->products[$i]['qty'] . "\n";
        }
        $message .= "----\n\n";

        for ($i = 0; $i < sizeof($order->totals); $i++) {
            $message .= $order->totals[$i]['title'] . ' ' . $order->totals[$i]['text'] . "\n";
        }

        $message .= "\n" . EMAIL_TEXT_PAYMENT_METHOD . "\n";
        $message .= $order->info['payment_method'] . "\n\n";

        return $message;
    }
}

$handler = new PayfastITNHandler($db);
$handler->handleRequest();
