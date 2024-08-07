<?php
namespace Stanford\HTNapi;
/** @var \Stanford\HTNapi\HTNapi $module */

include("components/gl_checklogin.php");

//FOR PASSING ALERTS AROUND
if(isset($_SESSION["buffer_alert"])){
    $error = $_SESSION["buffer_alert"];
    unset($_SESSION["buffer_alert"]);
}

// $module->evaluateOmronBPavg(1);
//$module->dailyOmronDataPull();

$provider_id    = !empty($_SESSION["logged_in_user"]["sponsor_id"]) ? $_SESSION["logged_in_user"]["sponsor_id"] : $_SESSION["logged_in_user"]["record_id"];
$sponsored      = !empty($_SESSION["logged_in_user"]["sponsor_id"]) ? true : false;

$dag_admin      = $_SESSION["logged_in_user"]["dag_admin"] ? "<span> - ".ucwords($_SESSION["logged_in_user"]["redcap_data_access_group"])."</span>"  : "";
$page           = "dashboard";
$home_active    = "active";
?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <?php include("components/gl_meta.php") ?>
    <!-- Custom styles for this template -->
</head>
<body class="d-flex flex-column h-100">
    <?php include("components/gl_topnav.php") ?>

    <!-- Begin page content -->
    <main role="main" class="flex-shrink-0">
        <?php include("components/mod_overview.php")?>

        <?php include("components/mod_alerts.php")?>

        <div id="patients" class="container mt-1">
            <div class="row">
                <h1 class="mt-0 mb-4 mr-3 ml-3 d-inline-block align-middle">Patients<?= $dag_admin ?></h1>
                <div class="filter d-inline-block mt-0 pl-3">
                    <b class="filtered  d-inline-block align-middle pl-3 mr-3 "><span class="all_patients">All Patients</o></span></b>
                </div>
            </div>

            <div class="row patient_body">
                <div id="patient_list" class="col-md-4">
                </div>
                <div id="patient_details" class="col-md-8">

                </div>
            </div>
        </div>
    </main>

    <?php include("components/gl_foot.php"); ?>
</body>
</html>
<script>
$(document).ready(function(){
    //make initial call to dashboard to grab data and draw out pertinent UI
    var urls = {
         "ajax_endpoint" : '<?=$module->getURL("endpoints/ajax_handler.php", true, true);?>'
        ,"anon_profile_src" : '<?=$module->getUrl('assets/images/icon_anon.gif', true, true)?>'
        ,"ptree_url" : '<?=$module->getUrl('pages/tree_view.php', true, true)?>'
        ,"edit_patient" : '<?=$module->getUrl('pages/add_patient.php', true, true)?>'
    };

    <?php
        if(!empty($error)){
    ?>
        setTimeout(function(){
            $(".alert").slideUp("medium");
        },4000);
    <?php
        }
    ?>

    var dash = new dashboard(<?=$provider_id?>,urls, <?=$sponsored?>);
});

function removeA(arr) {
    var what, a = arguments, L = a.length, ax;
    while (L > 1 && arr.length) {
        what = a[--L];
        while ((ax= arr.indexOf(what)) !== -1) {
            arr.splice(ax, 1);
        }
    }
    return arr;
}
</script>
