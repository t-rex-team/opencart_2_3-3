<?php

class ModelExtensionPaymentMono extends Model
{
    const CURRENCY_CODE = [
        'UAH' => 980,
        'EUR' => 978,
        'USD' => 840,
    ];

    public function getMethod($address, $total) {
        if (VERSION < '3.0.0.0') {
            $prefix = '';
        } else {
            $prefix = 'payment_';
        }

        $this->load->language('extension/payment/mono');

        $default_currency_code = $this->config->get('config_currency');
        if (empty($default_currency_code) || !array_key_exists($default_currency_code, self::CURRENCY_CODE)) {
            return [];
        }
        $mono_geo_zone = $this->config->get($prefix . 'mono_geo_zone_id');
        if ($mono_geo_zone == '0') {
            $show_monopay = true;
        } else {
            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$mono_geo_zone . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");
            if ($query->num_rows) {
                $show_monopay = true;
            } else {
                $show_monopay = false;
            }
        }
        if (!$show_monopay) {
            return [];
        }

        return [
            'code' => 'mono',
            'terms' => '',
            'title' => $this->language->get('text_title') . ' <img src="/catalog/view/theme/default/image/plata.svg" style="width:120px;" alt="plata"/>',
            'sort_order' => $this->config->get($prefix . 'mono_sort_order')
        ];
    }

    public function addOrder($args) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "mono_orders` WHERE OrderId = '" . (int)$args['order_id'] . "'");

        if ($query->num_rows) {
            $this->db->query("UPDATE `" . DB_PREFIX . "mono_orders` SET SecretKey = '" . $this->db->escape($args['randKey']) . "', InvoiceId = '" . $this->db->escape($args['InvoiceId']) . "' WHERE OrderId = '" . (int)$args['order_id'] . "'");
        } else {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "mono_orders` (InvoiceId, OrderId, SecretKey) VALUES('" . $args['InvoiceId'] . "'," . $args['order_id'] . ",'" . $args['randKey'] . "')");
        }
    }

    public function getInvoiceId(int $OrderId) {
        $q = $this->db->query("SELECT * FROM `" . DB_PREFIX . "mono_orders` WHERE OrderId = '" . $OrderId . "'");

        return $q->num_rows ? $q->row : false;
    }

    public function getOrderInfo(string $InvoiceId) {
        $q = $this->db->query("SELECT * FROM `" . DB_PREFIX . "mono_orders` WHERE InvoiceId = '" . $this->db->escape($InvoiceId) . "'");

        return $q->num_rows ? $q->row : false;
    }

    public function getProducts(array $productIds) {
        $q = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product` WHERE product_id IN (" . implode(",", $productIds) . ")");

        return $q->num_rows ? $q->rows : [];
    }

    public function getTotals(int $orderId) {
        $q = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_total` WHERE order_id =  '" . $orderId . "'");

        return $q->num_rows ? $q->rows : [];
    }

    public function getOrderProducts(int $orderId) {
        if (VERSION < '3.0.0.0') {
            return $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . $orderId . "'")->rows;
        } else {
            return $this->model_checkout_order->getOrderProducts($orderId);
        }
    }


    public function InvoiceInsert(string $invoiceId, int $orderId, string $paymentType, int $orderAmount, int $paymentAmountRefunded, int $paymentAmountFinal, string $status, int $orderCcy, int $paymentAmount) {
        $sql = "INSERT INTO `" . DB_PREFIX . "monopay_invoice` (invoice_id, order_id, payment_type, order_amount, order_ccy, payment_amount, payment_amount_refunded, payment_amount_final, status) 
                VALUES(" . sprintf("'%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'", $this->db->escape($invoiceId), $this->db->escape($orderId), $this->db->escape($paymentType),
                $this->db->escape($orderAmount), $this->db->escape($orderCcy), $this->db->escape($paymentAmount), $this->db->escape($paymentAmountRefunded), $this->db->escape($paymentAmountFinal), $this->db->escape($status)) . ")";

        $this->db->query($sql);
    }

    public function InvoiceSelectByOrderId(int $order_id) {
        $sql = "SELECT * FROM `" . DB_PREFIX . "monopay_invoice` WHERE order_id = " . $this->db->escape($order_id) . " ORDER BY created DESC";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function InvoiceGetLastByOrderId(int $order_id) {
        $sql = "SELECT * FROM `" . DB_PREFIX . "monopay_invoice` WHERE order_id = " . $this->db->escape($order_id) . " ORDER BY created DESC LIMIT 1";

        $query = $this->db->query($sql);

        if ($query->num_rows) {
            return $query->row;
        } else {
            return null;
        }
    }


    public function InvoiceUpdateStatus(string $invoice_id, string $status, int $finalAmount, int $paymentAmount, $paymentAmountRefunded, $failureReason) {
        $sql = "UPDATE `" . DB_PREFIX . "monopay_invoice` 
                SET modified = now(), status = '" . $this->db->escape($status) . "', payment_amount_final = '" . $this->db->escape($finalAmount) . "', 
                failure_reason = '" . $this->db->escape($failureReason) . "', 
                payment_amount = '" . $this->db->escape($paymentAmount) . "', 
                payment_amount_refunded = '" . $this->db->escape($paymentAmountRefunded) . "' 
                                WHERE invoice_id = '" . $this->db->escape($invoice_id) . "'";

        $this->db->query($sql);
    }

    public function InsertLogs($key, $value, string $moduleVersion) {
        $sql = "INSERT INTO `" . DB_PREFIX . "monopay_logs` (`key`, `value`, `module_version`) 
                VALUES " . sprintf("('%s', '%s', '%s')", $this->db->escape($key), $this->db->escape($value), $this->db->escape($moduleVersion));

        $this->db->query($sql);
    }

    public function DeleteLogs() {
        $sql = "DELETE
FROM `" . DB_PREFIX . "monopay_logs`
WHERE timestamp < DATE_SUB(NOW(), INTERVAL 2 DAY);
";

        $this->db->query($sql);
    }

    public function SelectLogs(int $from, int $limit, int $offset) {
        $sql = "SELECT * FROM `" . DB_PREFIX . "monopay_logs` 
        WHERE timestamp >= '" . $this->db->escape($from) . "' 
        ORDER BY timestamp DESC 
        LIMIT " . $this->db->escape($limit) . " 
        OFFSET " . $this->db->escape($offset);

        return $this->db->query($sql)->rows;
    }

    public function InvoiceGetById(string $invoice_id) {
        $sql = "SELECT * FROM `" . DB_PREFIX . "monopay_invoice` WHERE invoice_id = '" . $this->db->escape($invoice_id) . "'";

        $query = $this->db->query($sql);

        return $query->row;
    }

    public function UpdateSettingsCurrencyValue(string $currencyCode, $currencyValue) {
        $sql = "UPDATE `" . DB_PREFIX . "currency` SET value = '" . $this->db->escape($currencyValue) . "' WHERE code = '" . $this->db->escape($currencyCode) . "'";

        $this->db->query($sql);
    }

//    deprecated
    public function getOrder($order_id) {
        $qry = $this->db->query("SELECT * FROM `" . DB_PREFIX . "mono_orders` WHERE `Orderid` = '" . (int)$order_id . "' LIMIT 1");

        if ($qry->num_rows) {
            $order = $qry->row;
            return $order;
        } else {
            return false;
        }
    }
}