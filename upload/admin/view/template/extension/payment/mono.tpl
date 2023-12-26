<?php echo $header; ?><?php echo $column_left; ?>

<div id="content" style="background-color:#f4f4f3 !important;">
    <!-- Tab links -->
    <div class="mono-tab">
        <button class="mono-tablinks" id="mono-settings-btn" onclick="openTab('mono-settings')"><?php echo $settings_text ;?></button>
        <button class="mono-tablinks" id="mono-invoices-btn" onclick="openTab('mono-invoices')"><?php echo $invoices_text ;?></button>
    </div>

    <!-- Tab content -->
    <div id="mono-settings" class="mono-tabcontent">
        <div class="row">
            <div class="col-xs-12"
                 style="padding: 50px; background-color:white; border: 1px solid #ccc; border-radius:24px;">
                <a href="https://www.monobank.ua/e-comm" target="_blank">
                    <img src="view/image/payment/monopay_logo.svg" alt="Monobank"
                         style="margin-bottom: 2%; width: 20%"/>
                </a>
                <div class="col-xs-12" style="margin-bottom: 2%">version <b><?php echo $version ;?></b></div>
                <form action="<?php echo $action ;?>" method="post" enctype="multipart/form-data" id="form">
                    <div class="row">
                        <div class="col-xs-12">
                            <label class="col-sm-4" for="input-status"
                                   style="position:absolute; margin-left: 6px; font-size: 14px; margin-top: 1px; font-weight:300;">
                                <span data-toggle="tooltip" title=""><?php echo $entry_status ;?></span>
                            </label>
                            <select name="mono_status" id="input-status" class="mono-select">
                                <?php if($mono_status) { ?>
                                <option class="mono-option" value="1"
                                        selected="selected"><?php echo $text_enabled ;?></option>
                                <option class="mono-option" value="0"><?php echo $text_disabled ;?></option>
                                <?php } else { ?>
                                <option class="mono-option" value="1"><?php echo $text_enabled ;?></option>
                                <option class="mono-option" value="0"
                                        selected="selected"><?php echo $text_disabled ;?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-xs-12">
                            <label class="col-sm-4" for="input-merchant"
                                   style="position:absolute; margin-left: 6px; font-size: 14px; margin-top: 1px; font-weight:300;">
                                <span data-toggle="tooltip" title=""><?php echo $entry_merchant ;?></span>
                            </label>
                            <input style="margin-bottom:0%;" type="text" name="mono_merchant"
                                   value="<?php echo $mono_merchant ;?>" id="input-merchant" class="mono-select"
                                   required/>
                            <?php if ($error_merchant) { ?>
                            <span class="error"><?php echo $error_merchant ;?></span>
                            <?php } ?>
                            <p class="mono-text" style="margin-bottom: 3%;margin-left: 19px;"><?php echo $mono_text ;?> <a href="https://web.monobank.ua/"
                                                                                                                           style="color:#EA5357;" target="_blank">web.monobank.ua</a>
                            </p>
                        </div>
                        <div class="col-xs-12">
                            <label class="col-sm-4 control-label" for="input-geo-zone"
                                   style="position:absolute; margin-left: 6px; font-size: 14px; margin-top: 1px; font-weight:300;"><?php echo $entry_geo_zone ;?>
                            </label>
                            <select name="mono_geo_zone_id" id="input-geo-zone" class="mono-select">
                                <option value="0"><?php echo $text_all_zones ;?></option>
                                <?php foreach($geo_zones as $geo_zone) { ?>
                                <option value="<?php echo $geo_zone['geo_zone_id'] ;?>"
                                <?php echo $geo_zone['geo_zone_id'] == $mono_geo_zone_id ? 'selected' : '' ;?>
                                ><?php echo $geo_zone['name'] ;?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-xs-12">
                            <label class="col-sm-4 control-label" for="input-sort-order"
                                   style="position:absolute; margin-left: 6px; font-size: 14px; margin-top: 1px; font-weight:300;"><?php echo $entry_sort_order ;?>
                            </label>
                            <input type="text" name="mono_sort_order" value="<?php echo $mono_sort_order ;?>"
                                   placeholder="<?php echo $entry_sort_order ;?>" id="input-sort-order"
                                   class="mono-select"/>
                        </div>
                        <div class="col-xs-12">
                            <label class="col-sm-4 control-label" for="input-order-status"
                                   style="position:absolute; margin-left: 6px; font-size: 14px; margin-top: 1px; font-weight:300;"><?php echo $entry_order_success_status ;?></label>
                            <select name="mono_order_success_status_id" id="input-order-status" class="mono-select">
                                <?php foreach($order_statuses as $order_status) { ?>
                                <option value="<?php echo $order_status['order_status_id'] ;?>"
                                <?php echo $order_status['order_status_id'] == $mono_order_success_status_id ? 'selected' : '' ;?>
                                ><?php echo $order_status['name'] ;?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-xs-12">
                            <label class="col-sm-4 control-label" for="input-order-status"
                                   style="position:absolute; margin-left: 6px; font-size: 14px; margin-top: 1px; font-weight:300;"><?php echo $entry_order_cancelled_status ;?></label>
                            <select name="mono_order_cancelled_status_id" id="input-order-status" class="mono-select">
                                <?php foreach($order_statuses as $order_status) { ?>
                                <option value="<?php echo $order_status['order_status_id'] ;?>"
                                <?php echo $order_status['order_status_id'] == $mono_order_cancelled_status_id ? 'selected' : '' ;?>
                                ><?php echo $order_status['name'] ;?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-xs-12">
                            <label class="col-sm-4 control-label" for="input-order-status"
                                   style="position:absolute; margin-left: 6px; font-size: 14px; margin-top: 1px; font-weight:300;"><?php echo $entry_order_process_status ;?></label>
                            <select name="mono_order_process_status_id" id="input-order-status" class="mono-select">
                                <?php foreach($order_statuses as $order_status) { ?>
                                <option value="<?php echo $order_status['order_status_id'] ;?>"
                                <?php echo $order_status['order_status_id'] == $mono_order_process_status_id ? 'selected' : '' ;?>
                                ><?php echo $order_status['name'] ;?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="col-xs-12">
                            <label class="col-sm-4" for="input-holds"
                                   style="position:absolute; margin-left: 6px; font-size: 14px; margin-top: 1px; font-weight:300;">
                                <span data-toggle="tooltip" title=""><?php echo $entry_hold ;?></span>
                            </label>
                            <select name="mono_use_holds" id="input-holds" class="mono-select">
                                <?php if($mono_use_holds) { ?>
                                <option class="mono-option" value="1"
                                        selected="selected"><?php echo $text_enabled ;?></option>
                                <option class="mono-option" value="0"><?php echo $text_disabled ;?></option>
                                <?php } else { ?>
                                <option class="mono-option" value="1"><?php echo $text_enabled ;?></option>
                                <option class="mono-option" value="0"
                                        selected="selected"><?php echo $text_disabled ;?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="col-xs-12">
                            <label class="col-sm-4 control-label" for="input-order-status"
                                   style="position:absolute; margin-left: 6px; font-size: 14px; margin-top: 1px; font-weight:300;"><?php echo $entry_order_hold_status ;?></label>
                            <select name="mono_order_hold_status_id" id="input-order-status" class="mono-select">
                                <?php foreach($order_statuses as $order_status) { ?>
                                <option value="<?php echo $order_status['order_status_id'] ;?>"
                                <?php echo $order_status['order_status_id'] == $mono_order_hold_status_id ? 'selected' : '' ;?>
                                ><?php echo $order_status['name'] ;?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="col-xs-12">
                            <label class="col-sm-4" for="input-merchant"
                                   style="position:absolute; margin-left: 6px; font-size: 14px; margin-top: 1px; font-weight:300;width: 100%;">
                                <span data-toggle="tooltip" title=""><?php echo $entry_destination ;?></span>
                            </label>
                            <input style="margin-bottom:0%;" type="text" name="mono_destination"
                                   value="<?php echo $mono_destination ;?>" id="input-destination" class="mono-select"/>

                        </div>

                        <div class="col-xs-12">
                            <label class="col-sm-12 control-label" for="input-order-status"
                                   style="position:absolute; margin-left: 6px; font-size: 14px; margin-top: 1px; font-weight:300;"><?php echo $entry_fiscalization_code_field ;?></label>
                            <select name="mono_fiscalization_code_field" id="input-order-status" class="mono-select">
                                <?php foreach($fiscalization_code_fields as $fiscalization_code_field) { ?>
                                <option value="<?php echo $fiscalization_code_field ;?>"
                                <?php echo $fiscalization_code_field == $mono_fiscalization_code_field ? 'selected' : '' ;?>
                                ><?php echo $fiscalization_code_field ;?></option>
                                ><?php echo $fiscalization_code_field ;?></option>
                                <?php } ?>
                            </select>
                        </div>

                    </div>
                    <div class="row">
                        <div class="col-xs-10">
                            <button type="submit" form="form" data-toggle="tooltip"
                                    class="mono-save-btn"><?php echo $save_btn ;?></button>
                        </div>
                        <div class="col-xs-2">
                            <img src="view/image/payment/cat.png" alt="Monobank"
                                 style="position:absolute;  margin-top:-25%; margin-left: -25%; width:200%;"/>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="mono-invoices" class="mono-tabcontent">
        <div id="mono-loader" style="display:none;">
            <div class="mono-loader-circle"></div>
        </div>

        <!-- Status Filter Dropdown -->
        <div>
            <select id="statusFilter" class="mono-select" onchange="filterPayments()">
                <option class="mono-option" value=""><?php echo $all_statuses_text; ?></option>
                <?php foreach ($statuses as $status => $status_desc) : ?>
                <option class="mono-option" value="<?php echo $status; ?>">
                    <?php echo $status; ?> — <?php echo $status_desc; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="invoices_table">

        </div>

    </div>
</div>

<?php echo $footer; ?>

<script>
    var invoicesLoaded = false;
    var token = '';

    // Login to the API
    $.ajax({
        url: '<?php echo $login_url; ?>',
        type: 'post',
        dataType: 'json',
        data: 'key=<?php echo $key; ?>',
        crossDomain: true,
        success: function (json) {
            $('.alert').remove();

            if (json['error']) {
                if (json['error']['key']) {
                    $('#content > .container-fluid').prepend('<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> ' + json['error']['key'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
                }

                if (json['error']['ip']) {
                    $('#content > .container-fluid').prepend('<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> ' + json['error']['ip'] + ' <button type="button" id="button-ip-add" data-loading-text="Loading..." class="btn btn-danger btn-xs pull-right"><i class="fa fa-plus"></i> Add IP</button></div>');
                }
            }

            if (json['api_token']) {
                token = json['api_token'];
            }
        },
        error: function (xhr, ajaxOptions, thrownError) {
            alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        }
    });

    function openTab(tabName) {
        var i, tabcontent, tablinks;
        tabcontent = document.getElementsByClassName("mono-tabcontent");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
            tabcontent[i].classList.remove("active");
        }
        tablinks = document.getElementsByClassName("mono-tablinks");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].classList.remove("active");
        }
        document.getElementById(tabName).style.display = "block";
        document.getElementById(tabName).classList.add("active");
        document.getElementById(tabName + "-btn").classList.add("active");

        if (tabName === 'mono-invoices' && !invoicesLoaded) {
            loadPayments('index.php?route=extension/payment/mono/invoices&token=<?php echo $token; ?>');
        }
    }

    function selectAllInvoices(source) {
        checkboxes = document.getElementsByName('selected_invoices[]');
        for (var i = 0, n = checkboxes.length; i < n; i++) {
            checkboxes[i].checked = source.checked;
        }
    }

    function getSelectedInvoices() {
        var selectedInvoices = [];
        var checkboxes = document.getElementsByName('selected_invoices[]');
        for (var i = 0; i < checkboxes.length; i++) {
            if (checkboxes[i].checked) {
                selectedInvoices.push(checkboxes[i].value);
            }
        }
        return selectedInvoices;
    }

    function filterPayments() {
        var status = document.getElementById('statusFilter').value;
        var url = 'index.php?route=extension/payment/mono/invoices&token=<?php echo $token; ?>';

        if (status) {
            url += '&status=' + encodeURIComponent(status);
        }
        loadPayments(url);
    }

    function loadPayments(url) {
        var tableHTML = '<a class="btn btn-primary" onclick="refreshInvoices();"><?php echo $refresh_invoices_btn_text; ?></a><table class="table"><thead><tr>' +
            '<th><input type="checkbox" id="select_all_invoices" onclick="selectAllInvoices(this)"/></th>' +
            '<th>Order id</th>' +
            '<th>mono invoice id</th>' +
            '<th><?php echo $status_text; ?></th>' +
            '<th><?php echo $created_text; ?></th>' +
            '</tr></thead><tbody>';
        jQuery.ajax({
            url: url,
            type: 'GET',
            headers: {
                "Accept": "application/json; charset=utf-8",
                "Content-Type": "application/json; charset=utf-8"
            },
            error: function (response) {
                document.getElementById('invoices_table').innerHTML = '<h2>Error getting response</h2>';
                return;
            },
            success: function (response) {
                if (!response.hasOwnProperty('invoices') || response.invoices === null || response.invoices.length == 0) {
                    tableHTML += '</tbody></table>';
                    document.getElementById('invoices_table').innerHTML = tableHTML;
                    return;
                }
                // Loop through the JSON data and create table rows
                for (let invoice of response.invoices) {
                    let params = (new URL(document.location)).searchParams;
                    let urlToken = params.get("token");

                    var orderUrl = "/admin/index.php?route=sale/order/info&token=" + urlToken + "&order_id=" + invoice.order_id
                    tableHTML += '<tr>' +
                        '<td><input type="checkbox" name="selected_invoices[]" value="' + invoice.invoice_id + '" /></td>' +
                        '<td><a href="' + orderUrl + '">' + invoice.order_id + '</a></td>' +
                        '<td><i class="fa fa-copy" onclick="copyToClipboard(\'' + invoice.invoice_id + '\')"></i> ' + invoice.invoice_id + '</td>' +
                        '<td>' + invoice.status + '</td>' +
                        '<td>' + invoice.created + '</td>' +
                        '</tr>';
                }

                // Update the flag so the data isn't reloaded on subsequent tab switches
                invoicesLoaded = true;

                tableHTML += '</tbody></table>';
                document.getElementById('invoices_table').innerHTML = tableHTML;
            },
        })
    }

    function refreshInvoices() {
        var selectedInvoices = getSelectedInvoices();
        if (selectedInvoices.length == 0) {
            return;
        }

        // Show loader
        document.getElementById('mono-loader').style.display = 'block';

        jQuery.ajax({
            url: '<?php echo $refresh_invoices_url; ?>&token=' + token,
            type: 'POST',
            data: JSON.stringify({invoices: selectedInvoices}),
            headers: {
                "Accept": "application/json; charset=utf-8",
                "Content-Type": "application/json; charset=utf-8"
            },
            success: function (response) {
                document.getElementById('mono-loader').style.display = 'none';

                filterPayments();
            },
            error: function () {
                // Hide loader in case of error
                document.getElementById('mono-loader').style.display = 'none';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', (event) => {
        openTab('mono-settings');
    });


    function copyToClipboard(text) {
        navigator.clipboard.writeText(text);
    }
</script>

<style>
    #content input[type="text"] {
        padding-left: 19px;
        height: 57px !important;
        margin-bottom: 3% !important;
    }

    .mono-save-btn {
        width: 100%;
        background-color: #EA5357;
        color: white;
        font-weight: 600;
        padding: 14px 16px;
        grid-auto-flow: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        font-style: normal;
        font-size: 16px;
        line-height: 24px;
        border: 1px solid transparent;
        border-radius: 16px;
        cursor: pointer;
    }

    .mono-select {
        outline: none;
        width: 100%;
        font-size: 16px;
        padding: 0 30px 0 15px;
        margin-bottom: 3% !important;
        border-radius: 0;
        font-weight: 600;
        height: 57px !important;
        border: 1px solid #e1e1e1;
    }

    .mono-select:focus-visible, .mono-select:hover, .mono-select:active, .mono-select:focus {
        border: 1px solid #e1e1e1;
    }

    .mono-tab {
        background-color: #fff;
        padding-left: 0;
    }

    /* Style the buttons inside the tab */
    .mono-tab button {
        background-color: inherit;
        border: none;
        outline: none;
        cursor: pointer;
        padding: 10px 20px;
        font-size: 16px;
        border-radius: 4px;
    }

    /* Change background color of buttons on hover */
    .mono-tab button:hover {
        background-color: #ddd;
    }

    .mono-tab button.active {
        background-color: #f4f4f4;
        border-bottom: 2px solid #1e91cf;
    }

    .mono-tabcontent {
        display: none;
        padding: 6px 12px;
        border-top: none;
    }

    .mono-tabcontent.active {
        display: block;
    }

    /* loader stuff */
    #mono-loader {
        display: flex;
        justify-content: center;
        align-items: center;
        position: fixed;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        background-color: rgba(255, 255, 255, 0.8);
        z-index: 1000;
    }

    .mono-loader-circle {
        border: 5px solid #f3f3f3;
        border-top: 5px solid #3498db;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        animation: spin 2s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }

</style>

<?php echo $footer; ?>
