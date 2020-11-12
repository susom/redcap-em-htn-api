<?php
namespace Stanford\HTNapi;
/** @var \Stanford\HTNapi\HTNapi $module */

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
                <h1 class="mt-0 mb-4 mr-3 ml-3 d-inline-block align-middle">Patients</h1>
                <div class="filter d-inline-block mt-0 pl-3">
                    <b class="filtered  d-inline-block align-middle pl-3 mr-3 "><span class="all_patients">All Patients</span></b>
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
    if(typeof(Storage) !== "undefined"){
        if(localStorage.getItem("patient_list")){
            patient_list    = JSON.parse(localStorage.getItem("patient_list"));
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
        console.log("no local storage support");
    }

    $("#clear_storage").click(function(){
        localStorage.clear();
        return;
    });

    //make initial call to dashboard to grab data and draw out pertinent UI
    var urls = {
         "ajax_endpoint" : '<?=$module->getURL("pages/ajax_handler.php", true, true);?>'
        ,"anon_profile_src" : '<?=$module->getUrl('assets/images/icon_anon.gif')?>'
        
    };
    var dash = new dashboard(urls);
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