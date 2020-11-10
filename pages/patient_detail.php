<?php
namespace Stanford\HTNtree;
/** @var \Stanford\HTNtree\HTNtree $module */
// $API_TOKEN  = "7FDC083BFED646678E9613FFB48F203D";
// $API_URL    = "http://localhost/api/";

// $patient    = $module->getPatient(1,$API_TOKEN,$API_URL);
// $patients   = $module->getPatients($API_TOKEN,$API_URL);
// $tree       = $module->getPrescriptionTree(3,$API_TOKEN,$API_URL);
// $trees      = $module->getPrescriptionTrees($API_TOKEN,$API_URL);

if(isset($_GET["record_id"])){
    $record_id  = $_GET["record_id"];
    $patient    = $module->getPatientDetail($record_id);
    print_rr($patient);
}else{
    header("location:". $module->getURL("pages/dashboard.php"));
}

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
        
        <?php include("components/mod_overview.php")?>
        <?php include("components/mod_alerts.php")?>
        
        <div id="patients" class="container mt-1">
            <div class="row">
                <h1 class="mt-0 mb-5 mr-3 ml-3 d-inline-block align-middle">Patients</h1>
                <div class="filter d-inline-block mt-0 pl-3">

                </div>
            </div>

            <div class="row patient_body">
                <?php include("components/nav_patients.php"); ?>
                <?php include("components/mod_patient_details.php"); ?>
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

    $(".patient_details .nav-link").click(function(e){
        e.preventDefault();
        var tab = $(this).data("tab");

        console.log("tab", tab);

        return false;
    })
});

function clear_elements(element, exception_classes) {
  $(".hide").removeClass("hide");
  $(".delete_this").remove();

  element.find(':input').each(function() {
    for(var i in exception_classes){
        if($(this).attr("class") == exception_classes[i]){
            return;
        }
    }
    
    switch(this.type) {
        case 'password':
        case 'text':
        case 'textarea':
        case 'file':
        case 'select-one':
        case 'select-multiple':
        case 'date':
        case 'number':
        case 'tel':
        case 'email':
            $(this).val('');
            break;
        case 'checkbox':
        case 'radio':
            this.checked = false;
            break;
    }
  });
}
</script>