<script type="text/javascript">
(function() {
    <?php
    foreach($settings as $name => $value)
        printf("%s = %s;\n", $name, json_encode($value));
    ?>
    var js = document.createElement("script"); js.type = "text/javascript";
    js.async = true; js.src = "//www.evalor.es/js/sidebar.js";
    var s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(js, s);
})();
</script>
