<?php if (isset($error_message)) { ?>
<div class="alert alert-warning">
  <?php echo $error_message; ?>
  <button type="button" class="close" data-dismiss="alert">×</button>
</div>
<?php } else { ?>
  <div class="buttons">
    <div class="pull-right">
      <a href="<?php echo $checkout_url; ?>" id="button-confirm" class="btn btn-primary"><?php echo $button_confirm; ?></a>
    </div>
  </div>
<?php } ?>