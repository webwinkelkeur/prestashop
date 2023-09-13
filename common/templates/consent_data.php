<?php if (!empty($consent_flow_enabled)): ?>
<script id="<?= $system_key; ?>_order_completed">
    <?= $consent_data; ?>
</script>
<?php endif; ?>

