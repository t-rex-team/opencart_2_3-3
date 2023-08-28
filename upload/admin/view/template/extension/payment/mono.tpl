<?php echo $header; ?><?php echo $column_left; ?>

<div id="content" style="background-color:#f4f4f3 !important;">
    <div class="row" style="margin-top: 2%">
        <div class="col-xs-1"></div>
        <div class="col-xs-10"
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
                               value="<?php echo $mono_merchant ;?>" id="input-merchant" class="mono-select" required/>
                        <?php if ($error_merchant) { ?>
                        <span class="error"><?php echo $error_merchant ;?></span>
                        <?php } ?>
                        <p class="mono-text"><?php echo $mono_text ;?> <a href="https://web.monobank.ua/"
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
                            <?php } ?>
                        </select>
                    </div>

                </div>
                <div class="row">
                    <div class="col-xs-10">
                        <button type="submit" form="form" data-toggle="tooltip"
                                class="save-btn"><?php echo $save_btn ;?></button>
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


<style>
    .mono-text {
        margin-bottom: 3%;
        margin-left: 19px;
        margin-top: 1%;
    }

    #content input[type="text"] {
        padding-left: 19px;
        height: 57px !important;
        margin-bottom: 3% !important;
    }

    .save-btn {
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

    option.selected {
        background-color: #Ea5357 !important;
    }
</style>
<?php echo $footer; ?> 