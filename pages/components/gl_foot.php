<footer class="footer mt-5 py-3 text-center">
    <div class="container">
        <span class="text-muted">© Stanford University 2020</span>
    </div>
</footer>
<script>
$(document).ready(function(){
    setInterval(function(){
        console.log("refresh session");
        $.post('<?= $module->getURL("pages/refresh_session.php", true, true); ?>');
    },180000); //refreshes the session every 5 minutes
});
</script>