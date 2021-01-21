<?php
namespace Stanford\HTNapi;
/** @var \Stanford\HTNapi\HTNapi $module */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("components/gl_checklogin.php");

if(!empty($_POST)){
    $patient_added = $module->addPatient($_POST);

    if(empty($patient_added["errors"])){
        header("location:".$module->getUrl("pages/dashboard.php"));
    }
    exit;   
}

if(!empty($_GET["patient"])){
    $patient_id = $_GET["patient"];
    $patient    = $module->getPatientDetails($patient_id);

    $patient_name       = $patient["patient_fname"] . " "  . $patient["patient_mname"] . " " . $patient["patient_lname"];
    $tree_id            = $patient["current_treatment_plan_id"];
    $current_step_idx   = $patient["patient_treatment_status"];
    $rec_step_idx       = $patient["patient_rec_tree_step"];
}

$provider_trees = $_SESSION["logged_in_user"]["provider_trees"];

$page       = "patient_detail";
$showhide   = $page !== "dashboard" ? "hide" : "";
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
        <div id="patients" class="container mt-5">
            <div class="row pt-5">
                <h1 class="mt-0 mb-3 mr-3 ml-3 d-inline-block align-middle">New Patient</h1>
            </div>

            <div class="row patient_body">
                <div class="patient_details col-md-12">
                    <div class="bg-light rounded-right rounded-bottom rounded-left p-3 profile panels">
                        <div class="patient_detail mb-5">
                            
                        </div>
                        
                    </div>
                    
                </div>
            </div>
        </div>
    </main>

    <?php include("components/gl_foot.php"); ?>
</body>
</html>