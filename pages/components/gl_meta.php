<title>Hyper Tension Study - Provider Dashboard</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="description" content="">
<meta name="author" content="Irvin Szeto">
<!-- Bootstrap core CSS -->
<link href="<?php echo $module->getUrl('assets/styles/bootstrap.min.css') ?>" rel="stylesheet" crossorigin="anonymous">
<link href="<?php echo $module->getUrl('assets/styles/sticky-footer-navbar.css') ?>" rel="stylesheet">

<link href="<?php echo $module->getUrl('assets/styles/htn_gl.css') ?>" rel="stylesheet">
<link href="<?php echo $module->getUrl('assets/styles/htn.css') ?>" rel="stylesheet">

<script src="<?php echo $module->getUrl('assets/scripts/jquery.min.js') ?>" crossorigin="anonymous"></script>
<script src="<?php echo $module->getUrl('assets/scripts/bootstrap.bundle.min.js') ?>" crossorigin="anonymous"></script>

<script src="<?php echo $module->getUrl('assets/scripts/dashboard.js') ?>" crossorigin="anonymous"></script>
<script src="<?php echo $module->getUrl('assets/scripts/template.js') ?>" crossorigin="anonymous"></script>
<style>
    nav .dropdown-toggle::after{
        background:url(<?php echo $module->getUrl('assets/images/icon_anon.gif') ?>) 0 0 no-repeat;
        background-size:contain; 
    }
    .navbar-brand {
        background:url(<?= $module->getUrl('assets/images/logo_heartex.gif') ?>) 0 0 no-repeat;
        background-size: contain;
    }
    i.email {
        display:inline-block;
        font-size:100%;
        font-style:normal;
        background:url(<?= $module->getUrl('assets/images/icon_email.png') ?>) no-repeat;
        background-size:20px 20px;
        line-height:130%;
        cursor:pointer;
        padding-left:25px;
    }
</style>
