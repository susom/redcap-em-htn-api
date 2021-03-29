<title>Hyper Tension Study - Provider Dashboard</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="description" content="">
<meta name="author" content="Irvin Szeto">
<!-- Bootstrap core CSS -->
<link href="<?php echo $module->getUrl('assets/styles/bootstrap.min.css', true, true) ?>" rel="stylesheet" crossorigin="anonymous">
<link href="<?php echo $module->getUrl('assets/styles/sticky-footer-navbar.css', true, true) ?>" rel="stylesheet">

<link href="<?php echo $module->getUrl('assets/styles/htn_gl.css', true, true) ?>" rel="stylesheet">
<link href="<?php echo $module->getUrl('assets/styles/htn.css', true, true) ?>" rel="stylesheet">

<script src="<?php echo $module->getUrl('assets/scripts/jquery.min.js', true, true) ?>" crossorigin="anonymous"></script>
<script src="<?php echo $module->getUrl('assets/scripts/bootstrap.bundle.min.js', true, true) ?>" crossorigin="anonymous"></script>

<script src="<?php echo $module->getUrl('assets/scripts/dashboard.js', true, true) ?>" crossorigin="anonymous"></script>
<script src="<?php echo $module->getUrl('assets/scripts/template.js', true, true) ?>" crossorigin="anonymous"></script>

<script src="https://d3js.org/d3.v3.min.js" charset="utf-8"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/c3/0.4.11/c3.js"></script>
<link  href="https://cdnjs.cloudflare.com/ajax/libs/c3/0.4.11/c3.css"rel="stylesheet" type="text/css"></link>
<style>
    nav .dropdown-toggle::after{
        background:url(<?php echo $module->getUrl('assets/images/icon_anon.gif', true, true) ?>) 0 0 no-repeat;
        background-size:contain; 
    }
    .navbar-brand {
        background:url(<?= $module->getUrl('assets/images/logo_heartex.gif', true, true) ?>) 0 0 no-repeat;
        background-size: contain;
    }
    i.email {
        display:inline-block;
        font-size:100%;
        font-style:normal;
        background:url(<?= $module->getUrl('assets/images/icon_email.png', true, true) ?>) no-repeat;
        background-size:20px 20px;
        line-height:130%;
        cursor:pointer;
        padding-left:25px;
    }
    .patient_detail .edit_patient{
        background:url(<?= $module->getUrl('assets/images/icon_edit.png', true, true) ?>) no-repeat;
        background-size:20px 20px;
    }
    .delete_patient{
        background:url(<?= $module->getUrl('assets/images/icon_trash.png', true, true) ?>) 50% no-repeat;
        background-size:contain;
    }

    .patient_status b{
        display:inline-block;
        background:url(<?= $module->getUrl('assets/images/icon_bp_cuff_color.png', true, true) ?>) no-repeat;
        background-size:20px 20px;
        line-height:130%;
        padding-left:25px;
    }

    .clear_filters{
        background:url(<?= $module->getUrl('assets/images/icon_clear_filters.png', true, true) ?>) 100% no-repeat;
        background-size:19px 28px;
        padding-right:23px;
    }
    .loading_patient{
        text-indent:-5000px;
        background-image:url(<?= $module->getUrl('assets/images/loading_patient2.gif', true, true) ?>);
        background-position:50%;
        background-repeat:no-repeat;
    }
    .step li.note{
        background-image:url(<?= $module->getUrl('assets/images/icon_note.png', true, true) ?>);
        background-repeat:no-repeat;
        background-position:0 50%;
        background-size:15px 15px;
    }
</style>
