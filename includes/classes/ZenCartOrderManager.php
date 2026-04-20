<?php

class ZenCartOrderManager
{
    private object $db;

    public function __construct(object $db)
    {
        $this->db = $db;
    }

    public function lookupTransaction(array $pfData): array
    {
        return pf_lookupTransaction($pfData);
    }

    public function retrieveSession(string $sessionId): array
    {
        $sql    = 'SELECT * FROM ' . TABLE_PAYFAST_SESSION . ' WHERE session_id = :sessionId';
        $sql    = $this->db->bindVars($sql, ':sessionId', $sessionId, 'string');
        $result = $this->db->Execute($sql);
        if ($result->RecordCount() > 0) {
            return unserialize(base64_decode($result->fields['saved_session']));
        }
        throw new Exception(PayfastITN::PF_ERR_NO_SESSION);
    }

    public function createOrderEnvironment(array $session): void
    {
        $_SESSION = $session;
        require_once DIR_WS_CLASSES . 'Customer.php';
        require_once DIR_WS_CLASSES . 'shipping.php';
        require_once DIR_WS_CLASSES . 'payment.php';
        require_once DIR_WS_CLASSES . 'order.php';
        require_once DIR_WS_CLASSES . 'order_total.php';
        // Initialize payment and shipping modules as in original code
        if (isset($_SESSION['payment'])) {
            $payment_modules = new payment($_SESSION['payment']);
        }
        if (isset($_SESSION['shipping'])) {
            try {
                $shipping_modules = new shipping($_SESSION['shipping']);
            } catch (Throwable $e) {
                // Suppress shipping module initialization errors that occur in ITN context
                // The session already has the saved shipping method, so full re-initialization isn't critical
            }
        }
    }

    public function checkOrderData(array $pfData): bool
    {
        $amountGross = round(floatval($pfData['amount_gross']), 2);
        $orderTotal  = round(floatval($_SESSION['payfast_amount']), 2);

        return (new PayfastITN())->amountsEqual($amountGross, $orderTotal);
    }

    public function createOrder(object $order, array $orderTotals): int
    {
        global $zco_notifier;
        $zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_AFTER_PAYMENT_MODULES_BEFOREPROCESS');
        $zcOrderId = $order->create($orderTotals);
        if (!empty($_SESSION['is_guest_checkout']) && $_SESSION['is_guest_checkout']) {
            updateGuestOrder($zcOrderId, $_SESSION);
        }
        $zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_AFTER_ORDER_CREATE');

        return $zcOrderId;
    }

    public function createPayfastOrder(array $pfData, int $zcOrderId, int $ts): int
    {
        $sqlArray = pf_createOrderArray($pfData, $zcOrderId, $ts);
        zen_db_perform(pf_getActiveTable(), $sqlArray);

        return $this->db->insert_ID();
    }

    public function createPayfastHistory(array $pfData, int $pfOrderId, int $ts): void
    {
        $sqlArray = pf_createOrderHistoryArray($pfData, $pfOrderId, $ts);
        zen_db_perform(TABLE_PAYFAST_PAYMENT_STATUS_HISTORY, $sqlArray);
    }

    public function updateOrderStatus(int $zcOrderId, int $newStatus, string $comments, int $ts): void
    {
        $sql = 'UPDATE ' . TABLE_ORDERS . ' SET orders_status = :status WHERE orders_id = :orderId';
        $sql = $this->db->bindVars($sql, ':status', $newStatus, 'integer');
        $sql = $this->db->bindVars($sql, ':orderId', $zcOrderId, 'integer');
        $this->db->Execute($sql);

        $sqlArray = [
            'orders_id'         => $zcOrderId,
            'orders_status_id'  => $newStatus,
            'date_added'        => date(PF_FORMAT_DATETIME_DB, $ts),
            'customer_notified' => '0',
            'comments'          => $comments,
        ];
        zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sqlArray);
    }

    public function addProductsToOrder(object $order, int $zcOrderId): void
    {
        global $zco_notifier;
        $order->create_add_products($zcOrderId, 2);
        $zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_AFTER_ORDER_CREATE_ADD_PRODUCTS');
    }

    public function deleteSession(string $sessionId): void
    {
        $sql = 'DELETE FROM ' . TABLE_PAYFAST_SESSION . ' WHERE session_id = :sessionId';
        $sql = $this->db->bindVars($sql, ':sessionId', $sessionId, 'string');
        $this->db->Execute($sql);
    }

    public function updateOrderStatusAndHistory(
        array $pfData,
        int $zcOrderId,
        string $txnType,
        int $ts,
        int $newStatus
    ): void {
        pf_updateOrderStatusAndHistory($pfData, $zcOrderId, $txnType, $ts, $newStatus);
    }
}
