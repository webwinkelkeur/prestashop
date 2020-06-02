<script>
<?php
foreach ($settings as $name => $value) {
    printf("%s = %s;\n", $name, json_encode($value));
}
?>
</script>
<script async src="https://<?= $main_domain; ?>/js/sidebar.js"></script>
