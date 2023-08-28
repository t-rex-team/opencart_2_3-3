<style>
</style>


<div class="table-responsive card-body">
    <table class="table mono-subtable">
        <tr>
            <th>Order Id</th>
            <td class="value"><?php echo $order_id; ?></td>
        </tr>

        <tr>
            <th>Invoice Id</th>
            <td class="value"><?php echo $invoice_id; ?></td>
        </tr>
        <tr>
            <th><?php echo $text_invoice_amount; ?>
                <?php if($status == 'hold') { ?>
                <?php echo $text_invoice_on_hold; ?>
                <?php } ?></th>
            <td class="value"><?php echo $order_amount; ?> <b><?php echo $order_ccy; ?></b>
                <?php if($order_ccy != 'UAH') { ?>
                (<?php echo $payment_amount; ?> <b>UAH</b>)
                <?php } ?>
            </td>
        </tr>
        <?php //payment type is hold that has already been finalized; ?>
        <?php if(isset($finalized_amount) && $finalized_amount > 0) { ?>
        <tr>
            <th><?php echo $text_invoice_amount_finalized; ?></th>
            <td class="value"><?php echo $finalized_amount; ?> <b>UAH</b></td>
        </tr>
        <tr>
            <th><?php echo $text_invoice_finalized_at; ?></th>
            <td class="value"><?php echo $finalized_at; ?></td>
        </tr>
        <?php } elseif($status == 'hold') { ?>
        <?php //hold but not finalized; ?>
        <tr>
            <td colspan="2" class="value">
                <strong>
                    <div id="hold_span_<?php echo $invoice_id; ?>" class="text-left">
                        <a class="btn btn-primary"
                           href="javascript:void(0);"
                           onclick="$('#hold_span_<?php echo $invoice_id; ?>').hide();$('#hold_form_<?php echo $invoice_id; ?>').show();">
                            <?php echo $text_invoice_finalize_hold; ?>
                        </a>
                        <button type="submit" name="cancel_hold" id="mono_cancel"
                                class="btn btn-danger"><?php echo $text_invoice_cancel_hold; ?>
                        </button>
                    </div>
                    <form id="hold_form_<?php echo $invoice_id; ?>" method="post"
                          action="#"
                          class="form-horizontal" style="display:none" onsubmit="return false">
                        <div>
                            <label for="mono_amount" class="form-control-label label-on-top col-12">
                                <?php echo $text_enter_amount; ?><span class="text-danger">*</span>
                            </label>
                            <div class="col-sm">
                                <div class="input-group">
                                    <input type="text" id="mono_amount" required="required"
                                           value="<?php echo $payment_amount; ?>"
                                           class="form-control"/>
                                </div>
                            </div>
                        </div>
                        <br/>
                        <div class="text-left">
                            <button
                                    type="button"
                                    class="btn btn-secondary"
                                    onclick="$('#hold_span_<?php echo $invoice_id; ?>').show();$('#hold_form_<?php echo $invoice_id; ?>').hide();">
                                <?php echo $text_cancel; ?>
                            </button>

                            <input type="hidden" id="invoice_id" value="<?php echo $invoice_id; ?>"/>
                            <button type="submit" name="finalize_hold" id="finalize_hold"
                                    class="btn btn-primary"><?php echo $text_invoice_finalize_hold; ?>
                            </button>
                        </div>
                    </form>
                </strong>
            </td>
        </tr>
        <?php } ?>
        <?php if($payment_type == 'debit' || $finalized_amount > 0) { ?>
        <tr>
            <th><?php echo $text_invoice_amount_refunded; ?></th>
            <td class="value"><?php echo $payment_amount_refunded; ?> <b>UAH</b></td>
        </tr>
        <?php if($can_refund) { ?>
        <tr>
            <th><?php echo $text_invoice_amount_to_refund; ?></th>
            <td class="value"><?php echo $payment_amount_final; ?> <b>UAH</b></td>
        </tr>
        <tr>
            <td colspan="2" class="value">
                <strong>
                        <span id="refund_span_<?php echo $invoice_id; ?>">
                        <a class="btn btn-primary"
                           href="javascript:void(0);"
                           onclick="$('#refund_span_<?php echo $invoice_id; ?>').hide();$('#refund_form_<?php echo $invoice_id; ?>').show();">
                            <?php echo $text_invoice_refund; ?>
                        </a>
                        </span>
                    <form id="refund_form_<?php echo $invoice_id; ?>" method="post"
                          action="#"
                          class="form-horizontal" style="display:none" onsubmit="return false">
                        <div class="">
                            <label for="mono_amount" class="form-control-label label-on-top col-12">
                                <?php echo $text_enter_amount; ?> (UAH)<span class="text-danger">*</span>
                            </label>
                            <div class="col-sm">
                                <div class="input-group">
                                    <input type="text" id="mono_amount" required="required"
                                           value="<?php echo $payment_amount_final; ?>"
                                           class="form-control"/>
                                </div>
                            </div>
                        </div>
                        <br/>
                        <div class="text-left">
                            <button
                                    type="button"
                                    class="btn btn-secondary"
                                    onclick="$('#refund_span_<?php echo $invoice_id; ?>').show();$('#refund_form_<?php echo $invoice_id; ?>').hide();">
                                <?php echo $text_cancel; ?>
                            </button>

                            <input type="hidden" id="invoice_id" value="<?php echo $invoice_id; ?>"/>
                            <button type="submit" name="mono_refund" id="mono_cancel" class="btn btn-primary">
                                <?php echo $text_invoice_refund; ?>
                            </button>
                        </div>
                    </form>
                </strong>
            </td>
        </tr>
        <?php } ?>
        <?php } ?>
    </table>
</div>
<script type="text/javascript"><!--
    $(document).ready(function () {
        $('#finalize_hold').click(function (e) {
            const finalizationAmount = $('#mono_amount').val();
            if (parseFloat(finalizationAmount) <= 0) {
                alert('finalization amount must be positive');
            }
            const invoiceId = $('#invoice_id').val();

            $.ajax({
                url: 'index.php?route=extension/payment/mono/finalize_hold&token=<?php echo $token; ?>',
                type: 'post',
                data: 'order_id=<?php echo $order_id; ?>&invoice_id=' + invoiceId + '&amount=' + finalizationAmount,
                dataType: 'json',
                beforeSend: function () {
                    $('#finalize_hold').button('loading');
                },
                complete: function () {
                    $('#finalize_hold').button('reset');
                },
                success: function (json) {
                    $('#finalize_hold').button('reset');
                    if (json.errText) {
                        alert("Failed to finalize: " + json.errText);
                    }
                    document.location.reload();
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    document.location.reload();
                }
            });
        });
        $('#mono_cancel').click(function (e) {
            var mono_amount = $('#mono_amount').val();
            var invoice_id = $('#invoice_id').val();

            $.ajax({
                url: 'index.php?route=extension/payment/mono/cancel&token=<?php echo $token; ?>',
                type: 'post',
                data: 'order_id=<?php echo $order_id; ?>&invoice_id=' + invoice_id + '&mono_amount=' + mono_amount,
                dataType: 'json',
                beforeSend: function () {
                    $('#mono_refund').button('loading');
                },
                complete: function () {
                    $('#mono_refund').button('reset');
                },
                success: function (json) {
                    $('#mono_refund').button('reset');
                    if (json.errText) {
                        alert("Failed to refund: " + json.errText);
                    }
                    document.location.reload();
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    document.location.reload();
                }
            });
        });
    });
    //--></script>


<style>

    #content input[type="text"] {
        padding-left: 19px;
        height: 57px !important;
        margin-bottom: 3% !important;
    }
</style>
