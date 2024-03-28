<?php

class ModelExtensionPaymentMono extends Model {
    public function install() {

        $this->db->query("
		CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "monopay_invoice`
        (
            `invoice_id`              VARCHAR(50) NOT NULL,
            `order_id`                INT         NOT NULL,
            `order_amount`            INT         NOT NULL,
            `order_ccy`               INT         NOT NULL,
            `payment_amount`          INT         NOT NULL,
            `payment_amount_refunded` INT                  DEFAULT 0 NOT NULL,
            `payment_amount_final`    INT                  DEFAULT 0 NOT NULL,
            `status`                  VARCHAR(50) NOT NULL,
            `payment_type`            VARCHAR(50) NOT NULL,
            `created`                 TIMESTAMP   NOT NULL DEFAULT NOW(),
            `modified`                DATETIME   NOT NULL,
            `finalized`               TIMESTAMP   NULL     DEFAULT NULL,
            `failure_reason`          TEXT        NULL     DEFAULT NULL,
            PRIMARY KEY (invoice_id)
        ) ENGINE = MyISAM
          DEFAULT CHARSET = utf8
          COLLATE = utf8_general_ci;");

        try {
            $this->db->query("CREATE INDEX monopay_invoice_order_id_index ON " . DB_PREFIX . "monopay_invoice (order_id);");
        } catch (\Exception $e) {
//            ignoring this, maybe just write something like create index if not exists
        }

        $this->db->query("
		CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "monopay_logs`
        (
            `key`            TEXT        NOT NULL DEFAULT '',
            `value`          TEXT        NOT NULL DEFAULT '',
            `module_version` VARCHAR(50) NOT NULL DEFAULT '',
            `timestamp`      TIMESTAMP   NOT NULL DEFAULT NOW()
        ) ENGINE = MyISAM
          DEFAULT CHARSET = utf8
          COLLATE = utf8_general_ci;");

        try {
            $this->db->query("CREATE INDEX monopay_logs_timestamp_index ON " . DB_PREFIX . "monopay_logs (timestamp);");
        } catch (\Exception $e) {
//            ignoring this, maybe just write something like create index if not exists
        }

        // $this->db->query("
        // 	CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "mono_orders` (
        // 		`Id` int NOT NULL AUTO_INCREMENT,
        // 		`InvoiceId` varchar(50) DEFAULT NULL,
        // 		`OrderId` int(10) DEFAULT NULL,
        // 		`SecretKey` varchar(51) DEFAULT NULL,
        // 		`is_refunded` int(10) DEFAULT 0,
        // 		`payment_amount_refunded` decimal(15,4) DEFAULT 0.0000,
        // 		`refund_status` varchar(51) DEFAULT NULL,
        // 		`is_hold` int(10) DEFAULT 0,
        // 		PRIMARY KEY (Id)
        // 	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        // ");

        $event_code = 'payment_mono';
        $trigger = 'admin/view/sale/order_info/before';
        $action = 'extension/payment/mono/order_info';

        if (VERSION < '3.0.0.0') {
            $this->load->model('extension/event');
            $events = $this->model_extension_event->getEvents(['code' => $event_code]);
            $event_model = $this->model_extension_event;
        } else {
            $this->load->model('setting/event');
            $events = $this->model_setting_event->getEvents(['code' => $event_code]);
            $event_model = $this->model_setting_event;
        }

        $total_payment_mono_events = 0;
        foreach ($events as $event) {
            if ($event['trigger'] === $trigger && $event['action'] === $action) {
                $total_payment_mono_events += 1;
            }
        }

        if ($total_payment_mono_events > 1) {
            $event_model->deleteEventByCode('payment_mono');
            $total_payment_mono_events = 0;
        }
        if ($total_payment_mono_events == 0) {
            $event_model->addEvent($event_code, $trigger, $action);
        }

    }

    public function uninstall() {
        if (VERSION < '3.0.0.0') {
            $this->load->model('extension/event');
            $this->model_extension_event->deleteEventByCode('payment_mono');
        } else {
            $this->load->model('setting/event');
            $this->model_setting_event->deleteEventByCode('payment_mono');
        }
    }

    public function getOrder($order_id) {
        $qry = $this->db->query("SELECT * FROM `" . DB_PREFIX . "mono_orders` WHERE `Orderid` = '" . (int)$order_id . "' LIMIT 1");

        if ($qry->num_rows) {
            $order = $qry->row;
            return $order;
        } else {
            return false;
        }
    }


    //////////////////////////////////////////////////////
    ///////////////////// MONOPAY ////////////////////////
    //////////////////////////////////////////////////////

    public function InsertLogs($key, $value, $module_version) {
        $sql = "INSERT INTO `" . DB_PREFIX . "monopay_logs` (`key`, `value`, `module_version`) 
                VALUES " . sprintf("('%s', '%s', '%s')", $this->db->escape($key), $this->db->escape($value), $this->db->escape($module_version));

        $this->db->query($sql);
    }

    public function DeleteLogs() {
        $sql = "DELETE
FROM `" . DB_PREFIX . "monopay_logs`
WHERE timestamp < DATE_SUB(NOW(), INTERVAL 5 DAY);
";

        $this->db->query($sql);
    }

    public function InvoiceInsert($invoice_id, $order_id, $payment_type, $order_amount, $payment_amount_refunded, $payment_amount_final, $status, $order_ccy, $payment_amount, $failure_reason) {
        $sql = "INSERT INTO `" . DB_PREFIX . "monopay_invoice` (invoice_id, order_id, payment_type, order_amount, 
        order_ccy, payment_amount, payment_amount_refunded, payment_amount_final, status, failure_reason) 
                VALUES (" . sprintf("'%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'",
                $this->db->escape($invoice_id), $this->db->escape($order_id), $this->db->escape($payment_type),
                $this->db->escape($order_amount), $this->db->escape($order_ccy), $this->db->escape($payment_amount),
                $this->db->escape($payment_amount_refunded), $this->db->escape($payment_amount_final),
                $this->db->escape($status), $this->db->escape($failure_reason)) . ")";

        $this->db->query($sql);
    }

    public function InvoiceFinalizeHold($invoice_id) {
        $sql = "UPDATE `" . DB_PREFIX . "monopay_invoice` 
                SET modified = now(), finalized = now() WHERE invoice_id = '" . $this->db->escape($invoice_id) . "'";

        $this->db->query($sql);
    }

    public function InvoiceGetById($invoice_id) {
        $sql = "SELECT * FROM `" . DB_PREFIX . "monopay_invoice` WHERE invoice_id = '" . $this->db->escape($invoice_id) . "'";

        $query = $this->db->query($sql);

        return $query->row;
    }

    public function InvoiceGetLastByOrderId($order_id) {
        $sql = "SELECT * FROM `" . DB_PREFIX . "monopay_invoice` WHERE order_id = " . $this->db->escape($order_id) . " ORDER BY created DESC LIMIT 1";

        $query = $this->db->query($sql);

        if ($query->num_rows) {
            return $query->row;
        } else {
            return null;
        }
    }


    public function UpdateSettingsCurrencyValue($currency_code, $currency_value) {
        $sql = "UPDATE `" . DB_PREFIX . "currency` SET value = '" . $this->db->escape($currency_value) . "' 
        WHERE code = '" . $this->db->escape($currency_code) . "'";

        $this->db->query($sql);
    }

    public function GetInvoices($status) {
        $where_cond = "";
        if ($status) {
            $where_cond = " WHERE status = '" . $status . "' ";
        }
        $sql = "SELECT order_id, invoice_id, status, created FROM `" . DB_PREFIX . "monopay_invoice` " . $where_cond . " 
        ORDER BY created DESC LIMIT 15";

        return $this->db->query($sql)->rows;
    }
}
