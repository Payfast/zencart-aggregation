<?php

/**
 * Payfast ITN specific session stuff
 *
 * @package initSystem
 * Copyright (c) 2026 Payfast (Pty) Ltd
 * You (being anyone who is not Payfast (Pty) Ltd) may download and use this plugin / code in your own website in
 * conjunction with a registered and active Payfast account. If your Payfast account is terminated for any reason,
 * you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code
 * or part thereof in any way.
 * @version $Id: init_Payfast_sessions.php
 */
require_once 'includes/modules/payment/payfast/vendor/autoload.php';

// phpcs:enable

use Payfast\PayfastCommon\Aggregator\Request\PaymentRequest;

$paymentRequest = new PaymentRequest();

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

/**
 * Begin processing. Add notice to log if logging enabled.
 */
$paymentRequest->pflog(
    'ITN processing initiated. ' . "\n" .
    '- Originating IP: ' . $_SERVER['REMOTE_ADDR'] . ' ' .
    (SESSION_IP_TO_HOST_ADDRESS == 'true' ? @gethostbyaddr($_SERVER['REMOTE_ADDR']) : '')
);

if (!$_POST) {
    $paymentRequest->pflog(
        'ITN Fatal Error :: No POST data available -- ' .
        'Most likely initiated by browser and not Payfast.'
    );
}

$session_post    = $_POST['custom_str2'] ?? '=';
$session_stuff   = explode('=', $session_post);
$itnFoundSession = true;
