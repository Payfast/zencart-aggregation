<?php

class payfastinstaller extends base
{
    private const INSERT_LITERAL = 'INSERT INTO ';
    private const CREATE_LITERAL = 'CREATE TABLE IF NOT EXISTS ';

    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function install()
    {
        $this->insertConfiguration();
        $this->createTables();
        $this->notifyInstallation();
    }

    private function insertConfiguration()
    {
        // Variable Initialization
        global $db;

        //// Insert configuration values
        // MODULE_PAYMENT_PF_STATUS (Default = False)
        $db->Execute(
            self::INSERT_LITERAL . TABLE_CONFIGURATION .
            "( configuration_title, configuration_key, configuration_value, configuration_description,
             configuration_group_id, sort_order, set_function, date_added )
            VALUES( 'Enable Payfast?', 'MODULE_PAYMENT_PF_STATUS', 'False',
             'Do you want to enable Payfast?', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now() )"
        );
        // MODULE_PAYMENT_PF_MERCHANT_ID (Default = Generic sandbox credentials)
        $db->Execute(
            self::INSERT_LITERAL . TABLE_CONFIGURATION .
            "( configuration_title, configuration_key, configuration_value, configuration_description,
 configuration_group_id, sort_order, date_added )
            VALUES( 'Merchant ID', 'MODULE_PAYMENT_PF_MERCHANT_ID', '10000100', 'Your Merchant ID from Payfast
            <br><span style=\"font-size: 0.9em; color: green;\">(Click <a href=\"https://my.payfast.co.za/login\"
             target=\"_blank\">here</a> to get yours. This is initially set to a test value for testing purposes.)
             </span>', '6', '0', now() )"
        );
        // MODULE_PAYMENT_PF_MERCHANT_KEY (Default = Generic sandbox credentials)
        $db->Execute(
            self::INSERT_LITERAL . TABLE_CONFIGURATION . "( configuration_title, configuration_key, configuration_value,
 configuration_description, configuration_group_id, sort_order, date_added )
            VALUES( 'Merchant Key', 'MODULE_PAYMENT_PF_MERCHANT_KEY', '46f0cd694581a',
             'Your Merchant Key from Payfast<br><span style=\"font-size: 0.9em; color: green;\">
             (Click <a href=\"https://my.payfast.co.za/login\" target=\"_blank\">here</a>
              to get yours. This is initially set to a test value for testing purposes.)</span>', '6', '0', now() )"
        );
        // MODULE_PAYMENT_PF_PASSPHRASE
        $db->Execute(
            self::INSERT_LITERAL . TABLE_CONFIGURATION . "( configuration_title, configuration_key, configuration_value,
             configuration_description, configuration_group_id, sort_order, date_added )
            VALUES( 'Passphrase', 'MODULE_PAYMENT_PF_PASSPHRASE', '',
             'Only enter a Passphrase if you have one set on your Payfast account', '6', '0', now() )"
        );
        // MODULE_PAYMENT_PF_SERVER (Default = Test)
        $db->Execute(
            self::INSERT_LITERAL . TABLE_CONFIGURATION . "( configuration_title, configuration_key, configuration_value,
             configuration_description, configuration_group_id, sort_order, set_function, date_added )
            VALUES( 'Transaction Server', 'MODULE_PAYMENT_PF_SERVER', 'Test', 'Select the Payfast server to use',
             '6', '0', 'zen_cfg_select_option(array(\'Live\', \'Test\'), ', now() )"
        );
        // MODULE_PAYMENT_PF_SORT_ORDER (Default = 0)
        $db->Execute(
            self::INSERT_LITERAL . TABLE_CONFIGURATION . "( configuration_title, configuration_key, configuration_value,
             configuration_description, configuration_group_id, sort_order, date_added )
            VALUES( 'Sort Display Order', 'MODULE_PAYMENT_PF_SORT_ORDER', '0', 'Sort order of display.
             Lowest is displayed first.', '6', '0', now())"
        );
        // MODULE_PAYMENT_PF_ZONE (Default = "-none-")
        $db->Execute(
            self::INSERT_LITERAL . TABLE_CONFIGURATION . "( configuration_title, configuration_key, configuration_value,
             configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added )
            VALUES( 'Payment Zone', 'MODULE_PAYMENT_PF_ZONE', '0', 'If a zone is selected, only enable this payment
             method for that zone.', '6', '2', 'zen_get_zone_class_title', 'zen_cfg_pull_down_zone_classes(', now())"
        );
        // MODULE_PAYMENT_PF_PREPARE_ORDER_STATUS_ID
        $db->Execute(
            self::INSERT_LITERAL . TABLE_CONFIGURATION . "( configuration_title, configuration_key, configuration_value,
             configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added )
            VALUES( 'Set Preparing Order Status', 'MODULE_PAYMENT_PF_PREPARE_ORDER_STATUS_ID', '1', 'Set the status
             of prepared orders made with Payfast to this value', '6', '0',
              'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())"
        );
        // MODULE_PAYMENT_PF_ORDER_STATUS_ID
        $db->Execute(
            self::INSERT_LITERAL . TABLE_CONFIGURATION . "( configuration_title, configuration_key, configuration_value,
             configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added )
            VALUES( 'Set Acknowledged Order Status', 'MODULE_PAYMENT_PF_ORDER_STATUS_ID', '2',
             'Set the status of orders made with Payfast to this value', '6', '0',
             'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())"
        );
        // MODULE_PAYMENT_PF_DEBUG (Default = False)
        $db->Execute(
            self::INSERT_LITERAL . TABLE_CONFIGURATION . "( configuration_title, configuration_key, configuration_value,
             configuration_description, configuration_group_id, sort_order, set_function, date_added )
            VALUES( 'Enable debugging?', 'MODULE_PAYMENT_PF_DEBUG', 'False', 'Do you want to enable debugging?',
             '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now() )"
        );
        // MODULE_PAYMENT_PF_DEBUG_EMAIL
        $db->Execute(
            self::INSERT_LITERAL . TABLE_CONFIGURATION . "( configuration_title, configuration_key, configuration_value,
             configuration_description, configuration_group_id, sort_order, date_added )
            VALUES( 'Debug email address', 'MODULE_PAYMENT_PF_DEBUG_EMAIL', '',
             'Where would you like debugging information emailed?', '6', '0', now() )"
        );
    }

    private function createTables()
    {
        $this->createPayfastTable();
        $this->createPaymentStatusTable();
        $this->createPaymentStatusHistoryTable();
        $this->createSessionTable();
        $this->createTestingTable();
    }

    private function createPayfastTable()
    {
        $this->db->Execute(
            self::CREATE_LITERAL . TABLE_PAYFAST . " (
                `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
                `m_payment_id` VARCHAR(36) NOT NULL,
                `pf_payment_id` VARCHAR(36) NOT NULL,
                `zc_order_id` INTEGER UNSIGNED DEFAULT NULL,
                `amount_gross` DECIMAL(14,2) DEFAULT NULL,
                `amount_fee` DECIMAL(14,2) DEFAULT NULL,
                `amount_net` DECIMAL(14,2) DEFAULT NULL,
                `payfast_data` TEXT DEFAULT NULL,
                `timestamp` DATETIME DEFAULT NULL,
                `status` VARCHAR(50) DEFAULT NULL,
                `status_date` DATETIME DEFAULT NULL,
                `status_reason` VARCHAR(255) DEFAULT NULL,
                PRIMARY KEY(`id`),
                KEY `idx_m_payment_id` (`m_payment_id`),
                KEY `idx_pf_payment_id` (`pf_payment_id`),
                KEY `idx_zc_order_id` (`zc_order_id`),
                KEY `idx_timestamp` (`timestamp`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1"
        );
    }

    private function createPaymentStatusTable()
    {
        $this->db->Execute(
            "CREATE TABLE IF NOT EXISTS " . TABLE_PAYFAST_PAYMENT_STATUS . " (
                `id` INTEGER UNSIGNED NOT NULL,
                `name` VARCHAR(50) NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1"
        );

        // Safe seed (won't fail if rows already exist)
        $this->db->Execute(
            "INSERT INTO " . TABLE_PAYFAST_PAYMENT_STATUS . " (`id`, `name`)
             VALUES (1, 'COMPLETE'), (2, 'PENDING'), (3, 'FAILED')
             ON DUPLICATE KEY UPDATE `name` = VALUES(`name`)"
        );
    }

    private function createPaymentStatusHistoryTable()
    {
        $this->db->Execute(
            self::CREATE_LITERAL . TABLE_PAYFAST_PAYMENT_STATUS_HISTORY . " (
                `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
                `pf_order_id` INTEGER UNSIGNED NOT NULL,
                `timestamp` DATETIME DEFAULT NULL,
                `status` VARCHAR(50) DEFAULT NULL,
                `status_reason` VARCHAR(255) DEFAULT NULL,
                PRIMARY KEY(`id`),
                KEY `idx_pf_order_id` (`pf_order_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1"
        );
    }

    private function createSessionTable()
    {
        $this->db->Execute(
            self::CREATE_LITERAL . TABLE_PAYFAST_SESSION . " (
                `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
                `session_id` VARCHAR(100) NOT NULL,
                `saved_session` MEDIUMBLOB NOT NULL,
                `expiry` DATETIME NOT NULL,
                PRIMARY KEY(`id`),
                KEY `idx_session_id` (`session_id`(36))
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1"
        );
    }

    private function createTestingTable()
    {
        $this->db->Execute(
            self::CREATE_LITERAL . TABLE_PAYFAST_TESTING . " (
                `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
                `m_payment_id` VARCHAR(36) NOT NULL,
                `pf_payment_id` VARCHAR(36) NOT NULL,
                `zc_order_id` INTEGER UNSIGNED DEFAULT NULL,
                `amount_gross` DECIMAL(14,2) DEFAULT NULL,
                `amount_fee` DECIMAL(14,2) DEFAULT NULL,
                `amount_net` DECIMAL(14,2) DEFAULT NULL,
                `payfast_data` TEXT DEFAULT NULL,
                `timestamp` DATETIME DEFAULT NULL,
                `status` VARCHAR(50) DEFAULT NULL,
                `status_date` DATETIME DEFAULT NULL,
                `status_reason` VARCHAR(255) DEFAULT NULL,
                PRIMARY KEY(`id`),
                KEY `idx_m_payment_id` (`m_payment_id`),
                KEY `idx_pf_payment_id` (`pf_payment_id`),
                KEY `idx_zc_order_id` (`zc_order_id`),
                KEY `idx_timestamp` (`timestamp`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1"
        );
    }

    private function notifyInstallation()
    {
        $this->notify('NOTIFY_PAYMENT_PAYFAST_INSTALLED');
    }

}

