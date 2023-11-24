<?php


$client_model = null;
$log = null;

const MONOBANK_PAYMENT_VERSION = 'Polia_2.2.0';

function clientHandleException($e, $m = null, $isInit = false) {
    global $client_model, $log;
    if ($isInit) {
        $log = new Log('error.log');
        $client_model = $m;
        return;
    }
    $e_message = $e->getMessage();
//    this is to be expected, do not log it
    if ($e_message == "Error: Duplicate key name 'monopay_invoice_order_id_index'<br />Error No: 1061<br />CREATE INDEX monopay_invoice_order_id_index ON oc_monopay_invoice (order_id);") {
        return;
    }

    if ($m == null) {
        $m = $client_model;
    } else if ($client_model == null) {
        $client_model = $m;
    }
    if ($log == null) {
        $log = new Log('error.log');
    }
    if ($m != null) {
        try {
            $m->InsertLogs($e_message, json_encode([
                'version' => VERSION,
                'stack' => $e->getTrace(),
            ]), MONOBANK_PAYMENT_VERSION);
            $m->DeleteLogs();
        } catch (\Throwable $th) {
            $log->write($e_message);
            $log->write(sprintf('failed to write error logs in client to db: %s', $th->getMessage()));
        }
    } else {
        $log->write($e_message);
    }
}

function clientFatalErrorHandler($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        // This error code is not included in error_reporting, so let it fall through to the standard PHP error handler
        return false;
    }
    try {
        throw new ErrorException($message, 0, $severity, $file, $line);
    } catch (\Throwable $th) {
        clientHandleException($th);
    }
    return true;
}

// Set the custom error handler
set_error_handler("clientFatalErrorHandler");

set_exception_handler("clientHandleException");


class ControllerExtensionPaymentMono extends Controller {
    private $prefix = '';
    private $settings_file_path = 'monopay_settings.json';
    private $rates_file_path = 'mono_rates.json';

    const RATE_CACHE_TIMEOUT_SEC = 600;

    const CURRENCY_CODE = [
        'UAH' => 980,
        'EUR' => 978,
        'USD' => 840,
    ];

    public function __construct($registry) {
        parent::__construct($registry);

        if (VERSION >= '3.0.0.0') {
            $this->prefix = 'payment_';
        }

        if (!key_exists('monopay_inited', $this->session->data)) {
            $this->load->model('checkout/order');
            $this->load->model('extension/payment/mono');

            try {
                throw new Exception("init");
            } catch (Exception $e) {
                clientHandleException($e, $this->model_extension_payment_mono, true);
            }
            $this->session->data['monopay_inited'] = true;
        }
    }

    public function index() {
        $this->load->model('extension/payment/mono');
        $data = $this->language->load('extension/payment/mono');

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        try {
            if (!isset(self::CURRENCY_CODE[strtoupper($order_info['currency_code'])])) {
                return;
            }

            // we do not allow custom rate, so we make force update of the rate
            // it's not pretty, але маємо те шо маємо
            $default_ccy = $this->config->get('config_currency');
            if ($default_ccy != 'UAH' && $order_info['currency_code'] == 'UAH') {
                $rates = $this->getRates();
                if (isset($rates['created']) && $rates['created'] >= self::RATE_CACHE_TIMEOUT_SEC) {
                    $rate_buy = 0;
                    foreach ($rates['rates'] as $rate) {
                        if ($rate['currencyCodeA'] == self::CURRENCY_CODE[$default_ccy] && $rate['currencyCodeB'] == 980) {
                            $rate_buy = $rate['rateBuy'];
                            break;
                        }
                    }
                    if ($rate_buy == 0) {
                        try {
                            throw new ErrorException(sprintf('Rate for currency %s not found!', $default_ccy));
                        } catch (Exception $e) {
                            $data['error_message'] = $this->language->get('text_general_error');
                            clientHandleException($e, $this->model_extension_payment_mono);
                            return $this->load->view('extension/payment/mono', $data);
                        }
                    }
                    $this->model_extension_payment_mono->UpdateSettingsCurrencyValue('UAH', $rate_buy);
                }
            }

            $data['checkout_url'] = $this->getCheckoutUrl($order_info, $default_ccy);
        } catch (Exception $e) {
            $data['error_message'] = $this->language->get('text_general_error');
            clientHandleException($e, $this->model_extension_payment_mono);
        }

        return $this->load->view('extension/payment/mono', $data);
    }

    public function response() {
        $this->language->load('extension/payment/mono');
        $this->load->model('extension/payment/mono');
        $this->load->model('checkout/order');

        $order_id = $this->session->data['order_id'];
        $order_info = $this->model_checkout_order->getOrder($order_id);

        switch ($order_info['order_status_id']) {
            case $this->config->get($this->prefix . 'mono_order_hold_status_id'):
            case $this->config->get($this->prefix . 'mono_order_success_status_id'):
            {
                $this->response->redirect($this->url->link('checkout/success', '', true));
                break;
            }
            case $this->config->get($this->prefix . 'mono_order_cancelled_status_id'):
            {
                $this->response->redirect($this->url->link('checkout/failure', '', true));
                break;
            }
            case $this->config->get($this->prefix . 'mono_order_process_status_id'):
            case '0':
            {
                $invoice_db = $this->model_extension_payment_mono->InvoiceGetLastByOrderId($order_id);
                if ($invoice_db == null) {
                    $invoice_db = $this->insertInvoiceFromOldTable($order_info);
                    if ($invoice_db == null) {
//                        something is wrong
                        return;
                    }
                } else {
                    $status_response = $this->getStatus($invoice_db['invoice_id']);
                    $final_amount = (key_exists('finalAmount', $status_response)) ? $status_response['finalAmount'] : 0;

                    if ($status_response['status'] != 'hold' && $status_response['status'] != 'failure' && $invoice_db['payment_amount_final'] == $final_amount) {
                        //everything was already updated, no need to update anything
                        $this->response->redirect($this->url->link('checkout/success', '', true));
                        break;
                    }

                    $invoice_db['status'] = $status_response['status'];
                    $invoice_db['payment_amount'] = $status_response['amount'];
                    $invoice_db['failure_reason'] = (isset($status_response['failureReason'])) ? $status_response['failureReason'] : null;
                    $invoice_db['payment_amount_refunded'] = ($invoice_db['payment_type'] == 'debit') ? $status_response['amount'] - $final_amount : $invoice_db['payment_amount_refunded'] + $invoice_db['payment_amount_final'] - $status_response['finalAmount'];
                    $invoice_db['payment_amount_final'] = $final_amount;
                    $this->model_extension_payment_mono->InvoiceUpdateStatus($invoice_db['invoice_id'], $invoice_db['status'],
                        $invoice_db['payment_amount_final'], $invoice_db['payment_amount'], $invoice_db['payment_amount_refunded'],
                        $invoice_db['failure_reason']);
                }

                switch ($invoice_db['status']) {
                    case 'success':
                        $this->model_checkout_order->addOrderHistory($order_id, $this->config->get($this->prefix . 'mono_order_success_status_id'), $this->language->get('text_status_success'), true);
                        $this->response->redirect($this->url->link('checkout/success', '', true));
                        break;
                    case 'hold':
                        $this->model_checkout_order->addOrderHistory($order_id, $this->config->get($this->prefix . 'mono_order_hold_status_id'), $this->language->get('text_status_hold'), true);
                        $this->response->redirect($this->url->link('checkout/success', '', true));
                        break;
                    case 'expired':
                        $invoice_db['failure_reason'] = $this->language->get('text_status_expired');
                    //               no break here, making it go to failure
                    case 'failure':
                        $this->model_checkout_order->addOrderHistory($order_id, $this->config->get($this->prefix . 'mono_order_cancelled_status_id'), sprintf($this->language->get('text_status_cancelled'), $invoice_db['failure_reason']), true);
                        $this->response->redirect($this->url->link('checkout/failure', '', true));
                        break;
                    case 'created':
                    case 'processing':
//                        $this->model_checkout_order->addOrderHistory($orderID, $this->config->get($this->prefix . 'mono_order_process_status_id'), $this->language->get('text_status_process'), true);
                        break;
                    default:
                        exit('Undefined order status');
                }
                break;
            }
            default:
            {
                exit('Undefined order status');
            }
        }
    }

    public function getStatus(string $invoiceId) {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.monobank.ua/api/merchant/invoice/status?invoiceId=' . $invoiceId,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array('Content-Type: application/json',
                'X-Token: ' . $this->config->get($this->prefix . 'mono_merchant'),
                'X-Cms: Opencart',
                'X-Cms-Version: ' . VERSION,
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true);
    }

    public function getPubKey() {
        $pubkey_data = $this->readSettingsFromFile($this->settings_file_path);
        if (isset($pubkey_data['key'])) {
            return $pubkey_data['key'];
        }
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.monobank.ua/api/merchant/pubkey',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'X-Token: ' . $this->config->get($this->prefix . 'mono_merchant'),
                'X-Cms: Opencart',
                'X-Cms-Version: ' . VERSION,
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        $response_decoded = json_decode($response, true);
        $this->writeSettingsToFile($this->settings_file_path, $response_decoded);
        return $response_decoded['key'];
    }

    public function getRates() {
        $rates_data = $this->readSettingsFromFile($this->rates_file_path);
        if (isset($rates_data['created']) && (time() - (int)$rates_data['created']) < self::RATE_CACHE_TIMEOUT_SEC) {
            return $rates_data;
        }
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.monobank.ua/bank/currency',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'X-Cms: Opencart',
                'X-Cms-Version: ' . VERSION,
            ),
        ));

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if (!$response) {
            throw new ErrorException('No response');
        }

        if ($httpcode == 429) {
            return $rates_data;
        }

        $response_decoded = json_decode($response, true);
        $rates_data = [
            'created' => time(),
            'rates' => $response_decoded,
        ];
        $this->writeSettingsToFile($this->rates_file_path, $rates_data);
        return $rates_data;
    }

    public function callback() {
        $this->response->addHeader('Content-Type: application/json');
        $this->language->load('extension/payment/mono');
        $this->load->model('checkout/order');
        $this->load->model('extension/payment/mono');

        $x_sign = $_SERVER['HTTP_X_SIGN'] ?? '';

        $request_bytes = file_get_contents("php://input");

        if (!$this->verifyMonopaySignature($x_sign, $request_bytes)) {
            $this->response->setOutput(json_encode([
                'info' => 'invalid X-Sign',
            ]));
            throw new ErrorException(sprintf("invalid in callback 'X-Sign': %s, request: %s", $x_sign, $request_bytes));
        }

        $invoice_webhook_request = json_decode($request_bytes, true);

        $status_response = $this->getStatus($invoice_webhook_request['invoiceId']);
        $failure_reason = (key_exists('failureReason', $status_response)) ? $status_response['failureReason'] : '';
        $final_amount = (key_exists('finalAmount', $status_response)) ? $status_response['finalAmount'] : 0;
        $invoice_db = $this->model_extension_payment_mono->InvoiceGetById($status_response['invoiceId']);
        $prev_invoice_status = ($invoice_db != null) ? $invoice_db['status'] : '';

        $amount_refunded = $status_response['amount'] - $final_amount;
        $need_insert_invoice = $invoice_db == null;

        $prev_amount_refunded = 0;
        $prev_amount_final = 0;

        if (!$need_insert_invoice) {
            $prev_amount_refunded = $invoice_db['payment_amount_refunded'];
            $prev_amount_final = $invoice_db['payment_amount_final'];
        }
        switch ($status_response['status']) {
            case 'created':
            case 'hold':
            case 'failure':
            case 'success':
            case 'expired':
                $amount_refunded = 0;
                break;
            case 'processing':
                if ($final_amount == 0) {
                    $amount_refunded = 0;
                    break;
                }
            case 'reversed':
                $amount_refunded = $prev_amount_refunded + $prev_amount_final - $final_amount;
                break;
        }

        if ($need_insert_invoice) {
            $mono_order = $this->model_extension_payment_mono->getOrderInfo($status_response['invoiceId']);

            $order_id = $mono_order['OrderId'];
            $order = $this->model_checkout_order->getOrder($order_id);

            $invoice_status = $this->getStatus($mono_order['InvoiceId']);
            $payment_type = ($mono_order['is_hold'] == 'hold') ? 'hold' : 'debit';
            $amount_refunded = $invoice_status['amount'] - $invoice_status['finalAmount'];

            $failure_reason = (key_exists('failureReason', $status_response)) ? $status_response['failureReason'] : '';
            $final_amount = (key_exists('finalAmount', $status_response)) ? $status_response['finalAmount'] : 0;
            $invoice_db = [
                'invoice_id' => $mono_order['InvoiceId'],
                'order_id' => $order_id,
                'payment_type' => $payment_type,
                'order_ccy' => self::CURRENCY_CODE[$order['currency_code']],
                'order_amount' => (int)($order['total'] * 100 + 0.5),
                'payment_amount' => $invoice_status['amount'],
                'payment_amount_refunded' => $amount_refunded,
                'payment_amount_final' => $final_amount,
                'status' => $invoice_status['status'],
                'failure_reason' => $failure_reason,
            ];
            $prev_invoice_status = $invoice_db['status'];

            $this->model_extension_payment_mono->InvoiceInsert($invoice_db['invoice_id'], $invoice_db['order_id'],
                $invoice_db['payment_type'], $invoice_db['order_amount'], $invoice_db['payment_amount_refunded'],
                $invoice_db['payment_amount_final'], $invoice_db['status'], $invoice_db['order_ccy'],
                $invoice_db['payment_amount']);
        } else {
            $invoice_db['failure_reason'] = $failure_reason;
            $order_id = $invoice_db['order_id'];
        }
        if (!$need_insert_invoice) {
            $this->model_extension_payment_mono->InvoiceUpdateStatus($status_response['invoiceId'],
                $status_response['status'], $final_amount, $status_response['amount'],
                $amount_refunded, $failure_reason);
        }
        if (!isset($order)) {
            $order = $this->model_checkout_order->getOrder($order_id);
        }

        switch ($status_response['status']) {
            case 'created':
                break;
            case 'success':
                if ($order['order_status_id'] != $this->config->get($this->prefix . 'mono_order_success_status_id')) {
                    $this->model_checkout_order->addOrderHistory($order_id, $this->config->get($this->prefix . 'mono_order_success_status_id'), $this->language->get('text_status_success'), true);
                }
                break;
            case 'hold':
                if ($order['order_status_id'] != $this->config->get($this->prefix . 'mono_order_hold_status_id')) {
                    $this->model_checkout_order->addOrderHistory($order_id, $this->config->get($this->prefix . 'mono_order_hold_status_id'), $this->language->get('text_status_hold'), true);
                }
                break;
            case 'processing':
                if ($prev_invoice_status == 'created') {
                    $this->model_checkout_order->addOrderHistory($order_id, $this->config->get($this->prefix . 'mono_order_process_status_id'),
                        $this->language->get('text_status_process'));
                }
                break;
            case 'expired':
                $invoice_db['failure_reason'] = $this->language->get('text_status_expired');
            //               no break here, making it go to failure
            case 'failure':
                if ($order['order_status_id'] != $this->config->get($this->prefix . 'mono_order_cancelled_status_id')) {
                    $this->model_checkout_order->addOrderHistory($order_id, $this->config->get($this->prefix . 'mono_order_cancelled_status_id'),
                        sprintf($this->language->get('text_status_cancelled'), $invoice_db['failure_reason']));
                }
                break;
            case 'reversed':
                if ($invoice_webhook_request['status'] == 'processing') {
                    break;
                }
                if ($order['order_status_id'] == $this->config->get($this->prefix . 'mono_order_success_status_id')) {
                    $this->model_checkout_order->addOrderHistory($order_id, $this->config->get($this->prefix . 'mono_order_success_status_id'),
                        $this->language->get('text_status_refund'));
                } else if ($order['order_status_id'] == $this->config->get($this->prefix . 'mono_order_hold_status_id')) {
                    $this->model_checkout_order->addOrderHistory($order_id, $this->config->get($this->prefix . 'mono_order_cancelled_status_id'),
                        $this->language->get('text_status_hold_cancelled'));
                }
                break;
            default:
                return $this->response->setOutput(json_encode([
                    'err' => 'undefined order status'
                ]));
        }
        return $this->response->setOutput(json_encode([
            'info' => "successful response",
        ]));
    }

    public function logs() {
        $this->response->addHeader('Content-Type: application/json');
        $this->language->load('extension/payment/mono');
        $this->load->model('checkout/order');
        $this->load->model('extension/payment/mono');

        if (!isset($_SERVER['HTTP_X_SIGN'])) {
            http_response_code(400);
            return $this->response->setOutput(json_encode([
                'err' => 'empty "X-Sign"',
            ]));
        }
        if (!isset($_SERVER['HTTP_X_TIME'])) {
            http_response_code(400);
            return $this->response->setOutput(json_encode([
                'err' => 'empty "X-Time"',
            ]));
        }
        $x_sign = $_SERVER['HTTP_X_SIGN'];
        $x_time = $_SERVER['HTTP_X_TIME'];
        $now = time();
        if (($now - (int)$x_time) > 10) {
            http_response_code(400);
            return $this->response->setOutput(json_encode([
                'err' => 'invalid "X-Time"',
            ]));
        }
        $request_path = $_SERVER['REQUEST_URI'];

        if (!$this->verifyMonopaySignature($x_sign, $x_time . $request_path)) {
            http_response_code(400);
            $this->response->setOutput(json_encode([
                'err' => 'invalid "X-Sign"',
            ]));
            throw new ErrorException(sprintf("invalid 'X-Sign': %s, request: %s", $x_sign, $x_time . $request_path));
        }

        $from = isset($this->request->get['from']) ? (int)$this->request->get['from'] : time() - 3600 * 48;
        $limit = isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : 500;
        $offset = isset($this->request->get['offset']) ? (int)$this->request->get['offset'] : 0;

        $logs = $this->model_extension_payment_mono->SelectLogs($from, $limit, $offset);

        $this->response->setOutput(json_encode([
            'logs' => $logs,
        ]));
    }

    function getCheckoutUrl($order_info, string $ccy) {
        $payment_type = 'debit';
        $hold_status = $this->config->get($this->prefix . 'mono_use_holds');
        if ($hold_status == 1) {
            $payment_type = 'hold';
        }
        $create_invoice_response = $this->createInvoice($order_info, $payment_type, $ccy);
        $this->model_extension_payment_mono->InvoiceInsert($create_invoice_response['invoiceId'], $order_info['order_id'], $payment_type, (int)($order_info['total'] * 100 + 0.5), 0, 0, 'created', self::CURRENCY_CODE[$ccy], 0);
        return $create_invoice_response['pageUrl'];
    }

    function createInvoice($order_info, string $payment_type, string $ccy) {
        if (VERSION < '3.0.0.0') {
            $prefix = '';
        } else {
            $prefix = 'payment_';
        }

        $destination = $this->config->get($prefix . 'mono_destination');

        $total = (int)($order_info['total'] * 100 + 0.5);
        $basket_order = $this->getEncodedProducts($order_info['order_id'], $total);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.monobank.ua/api/merchant/invoice/create',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                'amount' => $total,
                'ccy' => self::CURRENCY_CODE[$ccy],
                'merchantPaymInfo' => [
                    'reference' => (string)$order_info['order_id'],
                    'destination' => $destination,
                    'basketOrder' => $basket_order,
                ],
                'redirectUrl' => (string)$this->url->link('extension/payment/mono/response', '', true),
                'paymentType' => $payment_type,
                'webHookUrl' => str_replace('&amp;', '&', $this->removeMiddlePath($this->url->link('extension/payment/mono/callback'))),]),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'X-Token: ' . $this->config->get($this->prefix . 'mono_merchant'),
                'X-Cms: Opencart',
                'X-Cms-Version: ' . VERSION,
            ),
        ));

        $response = curl_exec($curl);

        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if (!$response) {
            throw new ErrorException('No response');
        }

        if ($httpcode != 200) {
            throw new ErrorException('Got an error: ' . $httpcode);
        }
        return json_decode($response, true);
    }

    function verifyMonopaySignature(string $x_sign, $request_bytes) {
        $signature = base64_decode($x_sign);
        $pubkey = $this->getPubKey();
        $public_key = openssl_get_publickey(base64_decode($pubkey));
        return openssl_verify($request_bytes, $signature, $public_key, OPENSSL_ALGO_SHA256);
    }

    function removeMiddlePath($url) {
        $parsed_url = parse_url($url);
        // Reconstruct the URL with the modified path
        $port = (isset($parsed_url['port']) && strlen($parsed_url['port']) > 0) ? ':' . $parsed_url['port'] : '';
        $new_url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $port . '/index.php';

        // Add the query string and fragment if they exist
        if (isset($parsed_url['query'])) {
            $new_url .= '?' . $parsed_url['query'];
        }

        if (isset($parsed_url['fragment'])) {
            $new_url .= '#' . $parsed_url['fragment'];
        }

        return $new_url;
    }

    function readSettingsFromFile($filePath) {
        $settings = [];

        // Check if the file exists
        if (file_exists($filePath)) {
            // Read the file contents
            $file_contents = file_get_contents($filePath);

            // Parse the contents into an associative array (assuming JSON format)
            $settings = json_decode($file_contents, true);
        }

        return $settings;
    }

    function writeSettingsToFile($file_path, $settings) {
        // Convert the settings array to a JSON string
        $file_contents = json_encode($settings, JSON_PRETTY_PRINT);

        // Write the contents to the file
        file_put_contents($file_path, $file_contents);
    }

    function insertInvoiceFromOldTable($order_info) {
        try {
            $mono_order = $this->model_extension_payment_mono->getOrder($order_info['order_id']);
        } catch (Exception $e) {
            clientHandleException($e);
            return null;
        }
        if (!$mono_order) {
            try {
                throw new ErrorException(sprintf("invoice not found for order_id: %s", $order_info['order_id']));
            } catch (Exception $e) {
                clientHandleException($e);
            }
            return null;
        }

        $status_response = $this->getStatus($mono_order['InvoiceId']);
        $payment_type = ($mono_order['is_hold'] == 'hold') ? 'hold' : 'debit';

        $failure_reason = (key_exists('failureReason', $status_response)) ? $status_response['failureReason'] : '';
        $final_amount = (key_exists('finalAmount', $status_response)) ? $status_response['finalAmount'] : 0;
        $amount_refunded = $status_response['amount'] - $final_amount;
        switch ($status_response['status']) {
            case 'created':
            case 'hold':
            case 'failure':
            case 'success':
            case 'expired':
                $amount_refunded = 0;
                break;
            case 'processing':
                if ($final_amount == 0) {
                    $amount_refunded = 0;
                    break;
                }
        }
        $invoice_db = [
            'invoice_id' => $mono_order['InvoiceId'],
            'order_id' => $mono_order['OrderId'],
            'payment_type' => $payment_type,
            'order_ccy' => self::CURRENCY_CODE[$order_info['currency_code']],
            'order_amount' => (int)($order_info['total'] * 100 + 0.5),
            'payment_amount' => $status_response['amount'],
            'payment_amount_refunded' => $amount_refunded,
            'payment_amount_final' => $final_amount,
            'status' => $status_response['status'],
            'failure_reason' => $failure_reason,
        ];
        $this->model_extension_payment_mono->InvoiceInsert($invoice_db['invoice_id'], $invoice_db['order_id'],
            $invoice_db['payment_type'], $invoice_db['order_amount'], $invoice_db['payment_amount_refunded'],
            $invoice_db['payment_amount_final'], $invoice_db['status'], $invoice_db['order_ccy'], $invoice_db['payment_amount'], $invoice_db['failure_reason']);
        return $invoice_db;
    }

    public function getEncodedProducts(int $orderId, int $order_total) {
        $this->load->model('checkout/order');

        $order_products = $this->model_extension_payment_mono->getOrderProducts($orderId);
        $product_ids = [];
        foreach ($order_products as $order_product) {
            $product_ids[] = $order_product['product_id'];
        }
        $products = $this->model_extension_payment_mono->getProducts($product_ids);
        $products_map = [];
        foreach ($products as $product) {
            $products_map[$product['product_id']] = $product;
        }

        $fiscalization_code_field = $this->config->get($this->prefix . 'mono_fiscalization_code_field');
        if (empty($fiscalization_code_field)) {
            $fiscalization_code_field = 'sku';
        }
        $total_from_basket = 0;
        $basket = [];
        foreach ($order_products as $order_product) {
            $sum = (int)($order_product['price'] * 100 + 0.5);
            $qty = (int)$order_product['quantity'];
            $p = $products_map[$order_product['product_id']];
            $basket[] = [
                'name' => $order_product['name'],
                'sum' => $sum,
                'qty' => $qty,
                'code' => $p[$fiscalization_code_field],
                'icon' => HTTPS_SERVER . "/image/" . $p['image'],
            ];
            $total_from_basket += $qty * $sum;
        }

        $discount = 0;
        if ($total_from_basket != $order_total) {
            $totals = $this->model_extension_payment_mono->getTotals($orderId);
            foreach ($totals as $total) {
                $code = $total['code'];
                if ($code == 'total' || $code == 'sub_total') {
                    continue;
                }
                if ($total['value'] < 0) {
                    $discount += (int)($total['value'] * 100 - 0.5);
                    continue;
                }
                $basket[] = [
                    'name' => $total['title'],
                    'sum' => (int)($total['value'] * 100 + 0.5),
                    'qty' => 1,
                    'code' => $code,
                ];
            }
        }
        $discount = abs($discount);
        if ($discount > 0) {
            foreach ($basket as &$item) {
                if ($discount >= $item['sum'] && $item['sum'] > 1) {
                    $discount -= $item['sum'] - 1;
                    $item['sum'] = 1;
                } else {
                    $item['sum'] -= $discount;
                    break;
                }
            }
            unset($item);
        }
        return $basket;
    }
}
