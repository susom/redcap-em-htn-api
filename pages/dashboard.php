<?php
namespace Stanford\HTNapi;
/** @var \Stanford\HTNapi\HTNapi $module */

$UI_INTF        = $module->getAllPatients();
$all_patients   = $UI_INTF["patients"];
$rx_change      = $UI_INTF["rx_change"];
$results_needed = $UI_INTF["results_needed"];
$data_needed    = $UI_INTF["data_needed"]; 
$alerts         = $UI_INTF["messages"];

$page = "dashboard";
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
                <h1 class="mt-0 mb-4 mr-3 ml-3 d-inline-block align-middle">Patients</h1>
                <div class="filter d-inline-block mt-0 pl-3">
                    <b class="filtered  d-inline-block align-middle pl-3 mr-3 "><span class="all_patients">All Patients</span></b>
                </div>
            </div>

            <div class="row patient_body">
                <?php include("components/nav_patients.php") ?>
                <div class="patient_details col-md-8 none_selected bg-light rounded">
                    <h1>No Patient Selected</h1>
                </div>
            </div>
        </div>
    </main>

    <?php include("components/gl_foot.php"); ?>
</body>
</html>
<script>
$(document).ready(function(){
    var new_tree        = {};
    var patient_list    = {};
    var treatment_trees = {};
    
    if(typeof(Storage) !== "undefined"){
        if(localStorage.getItem("patient_list")){
            patient_list    = JSON.parse(localStorage.getItem("patient_list"));

            $("#patient_list").empty();
            for(var patient_name in patient_list){
                var newa    = $("<a>").attr("href","#").text(patient_name);
                var newli   = $("<li>").append(newa);
                $("#patient_list").append(newli);
            }
        }

        if(localStorage.getItem("treatment_trees")){
            treatment_trees = JSON.parse(localStorage.getItem("treatment_trees"));

            $("#view_tree option:not(:first-child),#patient_tree option:not(:first-child)").remove();
            for(var treatment_name in treatment_trees){
                var newopt  = $("<option>").val(treatment_name).text(treatment_name);
                $("#view_tree").append(newopt);
                $("#patient_tree").append(newopt.clone());
            }
        }
        
        // localStorage.setItem("key",JSON.stringify(OB));
        // localStorage.removeItem("key");
        // localStorage.clear();
    }else{
        alert("no local storage support");
    }

    $(".navbar-brand").click(function(){
        console.log(patient_list);
        console.log(treatment_trees);
        return false;
    });

    $("#patient_list").on("click","a",function(e){
        loadPatient(patient_list, $(this).text())
        e.preventDefault();
    });

    $("#clear_storage").click(function(){
        $("#clear_patient").click();
        $("#patient_list").empty().append($("<li>No saved patients.</li>"));
        newTree();
        patient_list = {};
        treatment_trees = {};
        localStorage.clear();
        return;
    });
});


</script>