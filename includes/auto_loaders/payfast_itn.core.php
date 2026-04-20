<?php

/**
 * Autoloader array for catalog application_top.php
 * See {@link  http://www.zen-cart.com/wiki/index.php/Developers_API_Tutorials#InitSystem wikitutorials}
 * for more details.
 *
 * @package initSystem
 * Copyright (c) 2026 Payfast (Pty) Ltd
 * You (being anyone who is not Payfast (Pty) Ltd) may download and use this plugin / code in your own website in
 * conjunction with a registered and active Payfast account. If your Payfast account is terminated for any reason,
 * you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code
 * or part thereof in any way.
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

/**
 * Breakpoint 0.
 */
$autoLoadConfig[0][] = [
    'autoType' => 'class',
    'loadFile' => 'class.base.php'
];
$autoLoadConfig[0][] = [
    'autoType' => 'class',
    'loadFile' => 'class.notifier.php'
];
$autoLoadConfig[0][] = [
    'autoType'   => 'classInstantiate',
    'className'  => 'notifier',
    'objectName' => 'zco_notifier'
];
$autoLoadConfig[0][] = [
    'autoType' => 'class',
    'loadFile' => 'class.phpmailer.php'
];
$autoLoadConfig[0][] = [
    'autoType' => 'class',
    'loadFile' => 'class.smtp.php'
];
$autoLoadConfig[0][] = [
    'autoType' => 'class',
    'loadFile' => 'template_func.php'
];
$autoLoadConfig[0][] = [
    'autoType' => 'class',
    'loadFile' => 'language.php'
];
$autoLoadConfig[0][] = [
    'autoType' => 'class',
    'loadFile' => 'cache.php'
];
$autoLoadConfig[0][] = [
    'autoType' => 'class',
    'loadFile' => 'sniffer.php'
];
$autoLoadConfig[0][] = [
    'autoType' => 'class',
    'loadFile' => 'shopping_cart.php'
];
$autoLoadConfig[0][] = [
    'autoType' => 'class',
    'loadFile' => 'navigation_history.php'
];
$autoLoadConfig[0][] = [
    'autoType' => 'class',
    'loadFile' => 'currencies.php'
];
$autoLoadConfig[0][] = [
    'autoType' => 'class',
    'loadFile' => 'message_stack.php'
];
$autoLoadConfig[0][] = [
    'autoType' => 'class',
    'loadFile' => 'breadcrumb.php'
];

/**
 * Breakpoint 10.
 *
 * require('includes/init_includes/init_database.php');
 * require('includes/version.php');
 *
 */
$autoLoadConfig[10][] = [
    'autoType' => 'init_script',
    'loadFile' => 'init_file_db_names.php'
];
$autoLoadConfig[10][] = [
    'autoType' => 'init_script',
    'loadFile' => 'init_database.php'
];

/**
 * Breakpoint 20.
 *
 * require( 'includes/init_includes/init_file_db_names.php' );
 */
$autoLoadConfig[20][] = [
    'autoType' => 'include',
    'loadFile' => DIR_WS_INCLUDES . 'version.php'
];

/**
 * Breakpoint 30.
 *
 * $zc_cache = new cache();
 */
$autoLoadConfig[30][] = [
    'autoType'   => 'classInstantiate',
    'className'  => 'cache',
    'objectName' => 'zc_cache'
];

/**
 * Breakpoint 40.
 *
 * require( 'includes/init_includes/init_db_config_read.php' );
 */
$autoLoadConfig[40][] = [
    'autoType' => 'init_script',
    'loadFile' => 'init_db_config_read.php'
];

/**
 * Breakpoint 50.
 *
 * $sniffer = new sniffer();
 * require( 'includes/init_includes/init_sefu.php' );
 * $phpBB = new phpBB();
 */
$autoLoadConfig[50][] = [
    'autoType'   => 'classInstantiate',
    'className'  => 'sniffer',
    'objectName' => 'sniffer'
];

$autoLoadConfig[50][] = [
    'autoType' => 'init_script',
    'loadFile' => 'init_sefu.php'
];

/**
 * Breakpoint 60.
 *
 * require( 'includes/init_includes/init_general_funcs.php' );
 * require( 'includes/init_includes/init_tlds.php' );
 *
 */
$autoLoadConfig[60][] = [
    'autoType' => 'init_script',
    'loadFile' => 'init_general_funcs.php'
];
$autoLoadConfig[60][] = [
    'autoType' => 'init_script',
    'loadFile' => 'init_tlds.php'
];
/**
 * Include Payfast-specific functions
 * require( 'includes/modules/payment/payfast/payfast_functions.php' );
 */

$autoLoadConfig[60][] = [
    'autoType' => 'include',
    'loadFile' => DIR_WS_MODULES . 'payment/payfast/payfast_functions.php'
];

/**
 * Breakpoint 70.
 *
 * require( 'includes/init_includes/init_sessions.php' );
 */
$autoLoadConfig[70][] = [
    'autoType' => 'init_script',
    'loadFile' => 'init_sessions.php'
];
$autoLoadConfig[71][] = [
    'autoType' => 'init_script',
    'loadFile' => 'init_payfast_itn_sessions.php'
];

/**
 * Breakpoint 80.
 *
 * if(!$_SESSION['cart']) $_SESSION['cart'] = new shoppingCart();
 */
$autoLoadConfig[80][] = [
    'autoType'          => 'classInstantiate',
    'className'         => 'shoppingCart',
    'objectName'        => 'cart',
    'checkInstantiated' => true,
    'classSession'      => true
];

/**
 * Breakpoint 90.
 *
 * currencies = new currencies();
 */
$autoLoadConfig[90][] = [
    'autoType'   => 'classInstantiate',
    'className'  => 'currencies',
    'objectName' => 'currencies'
];

/**
 * Breakpoint 100.
 *
 * require( 'includes/init_includes/init_sanitize.php' );
 * $template = new template_func();
 */
$autoLoadConfig[100][] = [
    'autoType'   => 'classInstantiate',
    'className'  => 'template_func',
    'objectName' => 'template'
];
$autoLoadConfig[100][] = [
    'autoType' => 'init_script',
    'loadFile' => 'init_sanitize.php'
];

/**
 * Breakpoint 110.
 *
 * require( 'includes/init_includes/init_languages.php' );
 * require( 'includes/init_includes/init_templates.php' );
 */
$autoLoadConfig[110][] = [
    'autoType' => 'init_script',
    'loadFile' => 'init_languages.php'
];
$autoLoadConfig[110][] = [
    'autoType' => 'init_script',
    'loadFile' => 'init_templates.php'
];

/**
 * Breakpoint 120.
 *
 * require( 'includes/init_includes/init_currencies.php' );
 *
 */
$autoLoadConfig[120][] = [
    'autoType' => 'init_script',
    'loadFile' => 'init_currencies.php'
];

/**
 * Breakpoint 170.
 *
 * require( 'includes/languages/english/checkout_process.php' );
 *
 */
$autoLoadConfig[170][] = [
    'autoType' => 'init_script',
    'loadFile' => 'init_payfast_itn_postcfg.php'
];
