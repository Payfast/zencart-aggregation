<?php

/**
 * payfast.php
 *
 * Lanugage defines for Payfast payment module
 *
 * Copyright (c) 2026 Payfast (Pty) Ltd
 * You (being anyone who is not Payfast (Pty) Ltd) may download and use this plugin / code in your own website in
 * conjunction with a registered and active Payfast account. If your Payfast account is terminated for any reason,
 * you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code
 * or part thereof in any way.
 */

const MODULE_PAYMENT_PF_TEXT_ADMIN_TITLE   = 'Payfast';
const MODULE_PAYMENT_PF_TEXT_CATALOG_TITLE = 'Payfast';
const MODULE_PAYMENT_PF_BUTTON_IMG         = DIR_WS_IMAGES . 'payfast/payfast-logo.svg';

if (IS_ADMIN_FLAG === true) {
    define(
        'MODULE_PAYMENT_PF_TEXT_DESCRIPTION',
        '<img src="../' . MODULE_PAYMENT_PF_BUTTON_IMG . '" style="height:36px;"> <br>' .
        '<br />' .
        'Manage your ' .
        '<a href="https://my.payfast.io/login" target="_blank">Payfast account</a> or ' .
        '<a href="https://payfast.io" target="_blank"> register</a> for a Payfast account'
    );
} else {
    define('MODULE_PAYMENT_PF_TEXT_DESCRIPTION', '<strong>Payfast</strong>');
}

const MODULE_PAYMENT_PF_BUTTON_ALT           = 'Checkout with Payfast';
const MODULE_PAYMENT_PF_ACCEPTANCE_MARK_TEXT = '';

const MODULE_PAYMENT_PF_TEXT_CATALOG_LOGO = '<img src="' . MODULE_PAYMENT_PF_BUTTON_IMG . '"' .
                                            ' alt="' . MODULE_PAYMENT_PF_BUTTON_ALT . '"' .
                                            ' title="' . MODULE_PAYMENT_PF_BUTTON_ALT . '"' .
                                            ' style="vertical-align: text-bottom; border: 0px;
                                             height: 36px; margin-bottom: -1vw;"/>&nbsp;' .
                                            '<span class="smallText">' . MODULE_PAYMENT_PF_ACCEPTANCE_MARK_TEXT . '</span>';

const MODULE_PAYMENT_PF_ENTRY_FIRST_NAME      = 'First Name:';
const MODULE_PAYMENT_PF_ENTRY_LAST_NAME       = 'Last Name:';
const MODULE_PAYMENT_PF_ENTRY_BUSINESS_NAME   = 'Business Name:';
const MODULE_PAYMENT_PF_ENTRY_ADDRESS_NAME    = 'Address Name:';
const MODULE_PAYMENT_PF_ENTRY_ADDRESS_STREET  = 'Address Street:';
const MODULE_PAYMENT_PF_ENTRY_ADDRESS_CITY    = 'Address City:';
const MODULE_PAYMENT_PF_ENTRY_ADDRESS_STATE   = 'Address State:';
const MODULE_PAYMENT_PF_ENTRY_ADDRESS_ZIP     = 'Address Zip:';
const MODULE_PAYMENT_PF_ENTRY_ADDRESS_COUNTRY = 'Address Country:';
const MODULE_PAYMENT_PF_ENTRY_EMAIL_ADDRESS   = 'Payer Email:';
const MODULE_PAYMENT_PF_ENTRY_EBAY_ID         = 'Ebay ID:';
const MODULE_PAYMENT_PF_ENTRY_PAYER_ID        = 'Payer ID:';
const MODULE_PAYMENT_PF_ENTRY_PAYER_STATUS    = 'Payer Status:';
const MODULE_PAYMENT_PF_ENTRY_ADDRESS_STATUS  = 'Address Status:';

const MODULE_PAYMENT_PF_ENTRY_PAYMENT_TYPE   = 'Payment Type:';
const MODULE_PAYMENT_PF_ENTRY_PAYMENT_STATUS = 'Payment Status:';
const MODULE_PAYMENT_PF_ENTRY_PENDING_REASON = 'Pending Reason:';
const MODULE_PAYMENT_PF_ENTRY_INVOICE        = 'Invoice:';
const MODULE_PAYMENT_PF_ENTRY_PAYMENT_DATE   = 'Payment Date:';
const MODULE_PAYMENT_PF_ENTRY_CURRENCY       = 'Currency:';
const MODULE_PAYMENT_PF_ENTRY_GROSS_AMOUNT   = 'Gross Amount:';
const MODULE_PAYMENT_PF_ENTRY_PAYMENT_FEE    = 'Payment Fee:';
const MODULE_PAYMENT_PF_ENTRY_CART_ITEMS     = 'Cart items:';
const MODULE_PAYMENT_PF_ENTRY_TXN_TYPE       = 'Trans. Type:';
const MODULE_PAYMENT_PF_ENTRY_TXN_ID         = 'Trans. ID:';
const MODULE_PAYMENT_PF_ENTRY_PARENT_TXN_ID  = 'Parent Trans. ID:';

const MODULE_PAYMENT_PF_PURCHASE_DESCRIPTION_TITLE = STORE_NAME . ' purchase, Order #';
