<?php

/**
 * Load the IPN checkout-language data
 * see {@link  http://www.zen-cart.com/wiki/index.php/Developers_API_Tutorials#InitSystem wikitutorials}
 * for more details.
 *
 * @package initSystem
 * Copyright (c) 2026 Payfast (Pty) Ltd
 * You (being anyone who is not Payfast (Pty) Ltd) may download and use this plugin / code in your own website in
 * conjunction with a registered and active Payfast account. If your Payfast account is terminated for any reason,
 * you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code
 * or part thereof in any way.
 * @version $Id: init_ipn_postcfg.php 6548 2007-07-05 03:40:59Z drbyte $
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

/**
 * Require language defines
 *
 * require( 'includes/languages/english/checkout_process.php' );
 */
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'english';
}

global $template_dir_select;
if (!$template_dir_select) {
    $spider_flag         = true;
    $template_dir_select = 'lang.';
}

$langBase = DIR_WS_LANGUAGES . $_SESSION['language'];
if (file_exists($langBase . '/' . $template_dir_select . 'checkout_process.php')) {
    require_once $langBase . '/' . $template_dir_select . 'checkout_process.php';
} else {
    require_once $langBase . '/checkout_process.php';
}
