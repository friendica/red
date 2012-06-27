{{ if $classtoday }}
<script>
    $(document).ready(function() {
        $('#events-reminder').addClass($.trim('$classtoday'));
    });
</script>	
{{ endif }}
