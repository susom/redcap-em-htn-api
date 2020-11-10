<?php
namespace Stanford\HTNtree;
/** @var \Stanford\HTNtree\HTNtree $module */

$page       = "help";
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
        <div id="help" class="container mt-5 mb-3">
            <div class="row">
                <h1 class="mt-5 mb-4 mr-3 ml-3 d-inline-block align-middle">Help</h1>
            </div>

            <div class="row patient_body">
                <div class="patient_details col-md-12 bg-light p-5 rounded">
                    <h3>What is this?</h3>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut feugiat leo eros, tristique efficitur eros placerat vel. Fusce velit nisl, porttitor eget odio eget, pellentesque lacinia nisi. Suspendisse potenti. Morbi non neque aliquet, maximus odio sit amet, ultricies ligula. Praesent ac laoreet velit. Vivamus id velit id turpis ultricies placerat. Praesent fringilla mauris nibh, sed vulputate diam ultrices quis. In dictum nibh ut mauris vulputate semper. Nunc sit amet odio mattis, mattis est vel, volutpat risus. Nullam scelerisque enim vel urna lobortis, eu aliquet sem sagittis. Morbi non sem eleifend, convallis dui vitae, scelerisque nisi. Suspendisse elementum enim eget augue ultrices, tincidunt fermentum est consequat.</p>

                    <h3>Why do i need to login?</h3>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut feugiat leo eros, tristique efficitur eros placerat vel. Fusce velit nisl, porttitor eget odio eget, pellentesque lacinia nisi. Suspendisse potenti. Morbi non neque aliquet, maximus odio sit amet, ultricies ligula. Praesent ac laoreet velit. Vivamus id velit id turpis ultricies placerat. Praesent fringilla mauris nibh, sed vulputate diam ultrices quis. In dictum nibh ut mauris vulputate semper. Nunc sit amet odio mattis, mattis est vel, volutpat risus. Nullam scelerisque enim vel urna lobortis, eu aliquet sem sagittis. Morbi non sem eleifend, convallis dui vitae, scelerisque nisi. Suspendisse elementum enim eget augue ultrices, tincidunt fermentum est consequat.</p>

                    <h3>How can i change my password?</h3>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut feugiat leo eros, tristique efficitur eros placerat vel. Fusce velit nisl, porttitor eget odio eget, pellentesque lacinia nisi. Suspendisse potenti. Morbi non neque aliquet, maximus odio sit amet, ultricies ligula. Praesent ac laoreet velit. Vivamus id velit id turpis ultricies placerat. Praesent fringilla mauris nibh, sed vulputate diam ultrices quis. In dictum nibh ut mauris vulputate semper. Nunc sit amet odio mattis, mattis est vel, volutpat risus. Nullam scelerisque enim vel urna lobortis, eu aliquet sem sagittis. Morbi non sem eleifend, convallis dui vitae, scelerisque nisi. Suspendisse elementum enim eget augue ultrices, tincidunt fermentum est consequat.</p>

                    <h3>Where is my data stored?</h3>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut feugiat leo eros, tristique efficitur eros placerat vel. Fusce velit nisl, porttitor eget odio eget, pellentesque lacinia nisi. Suspendisse potenti. Morbi non neque aliquet, maximus odio sit amet, ultricies ligula. Praesent ac laoreet velit. Vivamus id velit id turpis ultricies placerat. Praesent fringilla mauris nibh, sed vulputate diam ultrices quis. In dictum nibh ut mauris vulputate semper. Nunc sit amet odio mattis, mattis est vel, volutpat risus. Nullam scelerisque enim vel urna lobortis, eu aliquet sem sagittis. Morbi non sem eleifend, convallis dui vitae, scelerisque nisi. Suspendisse elementum enim eget augue ultrices, tincidunt fermentum est consequat.</p>

                    <h3>Can I input PHI?</h3>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut feugiat leo eros, tristique efficitur eros placerat vel. Fusce velit nisl, porttitor eget odio eget, pellentesque lacinia nisi. Suspendisse potenti. Morbi non neque aliquet, maximus odio sit amet, ultricies ligula. Praesent ac laoreet velit. Vivamus id velit id turpis ultricies placerat. Praesent fringilla mauris nibh, sed vulputate diam ultrices quis. In dictum nibh ut mauris vulputate semper. Nunc sit amet odio mattis, mattis est vel, volutpat risus. Nullam scelerisque enim vel urna lobortis, eu aliquet sem sagittis. Morbi non sem eleifend, convallis dui vitae, scelerisque nisi. Suspendisse elementum enim eget augue ultrices, tincidunt fermentum est consequat.</p>

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