<?php
namespace Stanford\HTNtree;
/** @var \Stanford\HTNtree\HTNtree $module */
if(isset($_POST["action"])){
    $action = $_POST["action"];

    switch($action){
        case "register_provider": 
            //TODO THIS IS JUST A PROTOTYPE, WILL NEED CLEANING UP
            $account_created = $module->registerProvider();
            if(empty($account_created["errors"])){
                header("Location: " . $module->getUrl("pages/dashboard.php"));
                exit;
            }else{
                $errors = $account_created["errors"];
            }   
        break;
    }
}

$page = "login_reg";
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
        <div id="registration" class="container mt-5">
            <div class="row">
                <div class="col-md-6 offset-md-3 mt-5">
                    <h1 class="mt-3 mr-3 ml-3 d-inline-block align-middle">Registration</h1>
                    <form method="POST" class="mr-3 ml-3">
                        <input type="hidden" name="action" value="register_provider"/>
                        <h4 class="pt-4 pb-1 mb-4">Create an Account</h4>
                        <aside>
                            <div class="form-group">
                                <label for="exampleInputEmail1">Email address</label>
                                <input type="email" class="form-control" id="provider_email" name="provider_email" placeholder="johndoe@stanford.edu" aria-describedby="emailHelp">
                            </div>
                            <div class="form-group">
                                <label for="exampleInputPassword1">Password</label>
                                <input type="password" class="form-control" id="provider_pw" name="provider_pw" placeholder="secret password" >
                            </div>
                            <div class="form-group">
                                <label for="exampleInputPassword1">Password Confirmation</label>
                                <input type="password" class="form-control" id="provider_pw2" name="provider_pw2"  placeholder="confirm password" >
                            </div>
                            <div class="form-group">
                                <label for="exampleInputPassword1">Healthcare Professional</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1"  name="provider_profession"  id="defaultCheck1">
                                    <label class="form-check-label" for="defaultCheck1">Physician (MD,DO)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="2" name="provider_profession" id="defaultCheck2">
                                    <label class="form-check-label" for="defaultCheck1">Pharmacist</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="3" name="provider_profession" id="defaultCheck3">
                                    <label class="form-check-label" for="defaultCheck1">Nurse Practitioner (NP)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="4" name="provider_profession" id="defaultCheck4">
                                    <label class="form-check-label" for="defaultCheck1">Physician Assistant</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="5" name="provider_profession" id="defaultCheck5">
                                    <label class="form-check-label" for="defaultCheck1">Registered Nurse (RN)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="6" name="provider_profession" id="defaultCheck6">
                                    <label class="form-check-label" for="defaultCheck1">Medical Intern</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="7" name="provider_profession" id="defaultCheck7">
                                    <label class="form-check-label" for="defaultCheck1">Pharmacy Technician</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="8" name="provider_profession" id="defaultCheck7">
                                    <label class="form-check-label" for="defaultCheck1">Medical Assistant</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="9" name="provider_profession" id="defaultCheck8">
                                    <label class="form-check-label" for="defaultCheck1">Researcher</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="10" name="provider_profession" id="defaultCheck9">
                                    <label class="form-check-label" for="defaultCheck1">Other</label>
                                </div>
                            </div>
                        </aside>

                        <h4 class="pt-4 pb-1 mb-4">Personal Information</h4>
                        <aside>
                            <div class="form-group row">
                                <label for="provider_dea" class="col-md-6">DEA Numbers(s)</label>
                                <input type="text" class="form-control col-md-5" id="provider_dea" name="provider_dea" placeholder="DEA #s" >
                            </div>
                            <div class="form-group row">
                                <label for="exampleInputEmail1" class="col-md-6">Professional License Number</label>
                                <input type="text" class="form-control col-md-5" id="provider_lic_num" name="provider_lic_num"  placeholder="License #"  >
                            </div>
                            <div class="form-group row">
                                <label for="exampleInputEmail1" class="col-md-6">License Type</label>
                                <input type="text" class="form-control col-md-5" id="provider_lic_type" name="provider_lic_type" placeholder="License Type" >
                            </div>
                            <div class="form-group row">
                                <label for="exampleInputEmail1" class="col-md-6">First Name<span>*</span></label>
                                <input type="text" class="form-control col-md-5" id="provider_fname" name="provider_fname" placeholder="First Name"  >
                            </div>
                            <div class="form-group row">
                                <label for="exampleInputEmail1" class="col-md-6">Middle Name</label>
                                <input type="text" class="form-control col-md-5" id="provider_mname" name="provider_mname"  placeholder="Middle Name" >
                            </div>
                            <div class="form-group row">
                                <label for="exampleInputEmail1" class="col-md-6">Last Name<span>*</span></label>
                                <input type="text" class="form-control col-md-5" id="provider_lname" name="provider_lname" placeholder="Last Name" >
                            </div>
                            <div class="form-group row">
                                <label for="exampleInputEmail1" class="col-md-6">Date of Birth<span>*</span></label>
                                <input type="text" class="form-control col-md-5" id="provider_dob" name="provider_dob" placeholder="MM/DD/YYYY" >
                            </div>
                            <div class="form-group row">
                                <label for="exampleInputEmail1" class="col-md-6">Healthcare Specialty</label>
                                <input type="text" class="form-control col-md-5" id="provider_specialty" name="provider_specialty"  placeholder="Specialty" >
                            </div>
                        </aside>

                        <h4 class="pt-4 pb-1 mb-4">Employer</h4>
                        <aside>
                            <div class="form-group row">
                                <label for="exampleInputEmail1" class="col-md-6">Name</label>
                                <input type="text" class="form-control col-md-5" id="employer_name" name="employer_name" placeholder="Employer Name" >
                            </div>
                            <div class="form-group row">
                                <label for="exampleInputEmail1" class="col-md-6">Address</label>
                                <input type="text" class="form-control col-md-5" id="employer_address" name="employer_address" placeholder="Employer Address" >
                            </div>
                            <div class="form-group row">
                                <label for="exampleInputEmail1" class="col-md-6">City<span>*</span></label>
                                <input type="text" class="form-control col-md-5" id="employer_city" name="employer_city" placeholder="Employer City" >
                            </div>
                            <div class="form-group row">
                                <label for="exampleInputEmail1" class="col-md-6">State<span>*</span></label>
                                <input type="text" class="form-control col-md-5" id="employer_state" name="employer_state" placeholder="Employer State" >
                            </div>
                            <div class="form-group row">
                                <label for="exampleInputEmail1" class="col-md-6">Zip code<span>*</span></label>
                                <input type="text" class="form-control col-md-5" id="employer_zip" name="employer_zip" placeholder="Employer Zip" >
                            </div>
                            <div class="form-group row">
                                <label for="exampleInputEmail1" class="col-md-6">Phone Number<span>*</span></label>
                                <input type="text" class="form-control col-md-5" id="employer_phone" name="employer_phone" placeholder="Employer Phone" >
                            </div>
                            <div class="form-group row">
                                <label for="exampleInputEmail1" class="col-md-6">Do you dispense medication out of your office?<span>*</span></label>
                                <label class="form-check-label d-inline col-md-2 pl-4" for="defaultCheck11"><input class="form-check-input" type="radio" value="1" name="employer_med_dispense" id="defaultCheck11"> Yes</label>
                                <label class="form-check-label d-inline col-md-2" for="defaultCheck12"><input class="form-check-input" type="radio" value="0" name="employer_med_dispense" id="defaultCheck12"> No</label>
                            </div>
                            
                        </aside>

                        <h4 class="pt-4 pb-1 mb-4">Delegate</h4>
                        <aside>
                            <div class="form-group">
                                <label for="exampleInputEmail1">I am a delegate for the following people<span>*</span>:</label>
                            </div>
                            <div class="form-group">
                                <label for="exampleInputEmail1 d-block ">Email Address</label>
                                <div class='row'>
                                    <input type="text" class="form-control col-md-8 ml-3" id="exampleInputEmail1" > <button class='btn btn-info btn-sm col-md-2 ml-3'>Add +</button>
                                </div>
                            </div>

                            <div class="form-group ">
                                <label for="exampleInputEmail1">Selected Personnel<span>*</span></label>
                                <!-- <input type="text" class="form-control" disabled> -->
                            </div>
                            <!-- <div class="form-group ">
                                <input type="text" class="form-control" disabled>
                            </div> -->
                        </aside>

                        <div class="btns pt-4 pb-4">
                            <button type="submit" class="btn btn-primary btn-block">Submit Registration</button>
                        </div>
                    </form>
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

    $("#clear_storage").click(function(){
        $("#clear_patient").click();
        $("#patient_list").empty().append($("<li>No saved patients.</li>"));
        newTree();
        patient_list = {};
        treatment_trees = {};
        localStorage.clear();
        return;
    });
    
    $(":input").change(function(){
        console.log($(this).attr("name"));
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