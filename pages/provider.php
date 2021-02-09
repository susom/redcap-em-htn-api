<?php
namespace Stanford\HTNapi;
/** @var \Stanford\HTNapi\HTNapi $module */

include("components/gl_checklogin.php");

if(isset($_POST)){
    $action = $_POST["action"];
    switch($action){
        case "edit_provider": 
            $account_edited = $module->editProvider($_POST); 
            $module->emDebug("account edited!");            
        break;
    }
}

$edit_id        = $_SESSION["logged_in_user"]["record_id"];
$edit_provider  = $module->getProvider($edit_id);

$provider       = array_shift($edit_provider);
$sponsor_id     = $provider["sponsor_id"];
if(!empty($sponsor_id)){
    $sponsor = $module->getProvider($sponsor_id);
    $module->emDebug("parent sponsor", $sponsor);
}
$provider_email     = $provider["provider_email"];
$provider_dea       = $provider["provider_dea"];
$provider_lic_num   = $provider["provider_lic_num"];
$provider_lic_type  = $provider["provider_lic_type"];
$provider_fname     = $provider["provider_fname"];
$provider_mname     = $provider["provider_mname"];
$provider_lname     = $provider["provider_lname"];
$provider_dob       = $provider["provider_dob"];
$provider_specialty = $provider["provider_specialty"];
$employer_name      = $provider["employer_name"];
$employer_address   = $provider["employer_address"];
$employer_city      = $provider["employer_city"];
$employer_state     = $provider["employer_state"];
$employer_zip       = $provider["employer_zip"];
$employer_phone     = $provider["employer_phone"];
$employer_med_dispense  = $provider["employer_med_dispense"];
$provider_profession    = null;
for($i=1; $i<11; $i++){
    if($provider["provider_profession___".$i]){
        $provider_profession = $i;
        break;
    }
}
$delegates = array();
if(count($edit_provider) > 1){
    foreach($edit_provider as $delegate){
        if($delegate["redcap_repeat_instrument"] == "provider_delegates"){
            $delegates[$delegate["delegate_id"]] = $delegate["provider_delegate"];
        }
    }
}

// TODO NEED TO SHOW SPONSOR


$module->emDebug($delegates);
$edit_provider = !empty($_SESSION["logged_in_user"]) ? "show_reg" : "hide_reg";
$edit_delegate = empty($sponsor_id) ? "show_del" : "hide_del";
$page = "login_reg";
?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <?php include("components/gl_meta.php") ?>
    <!-- Custom styles for this template -->
    <style>
        .hide_reg, .hide_del{
            display:none;
        }
        .show_reg, .show_del{
            display:block;
        }
    </style>
</head>
<body class="d-flex flex-column h-100">
    <?php include("components/gl_topnav.php") ?>

    <!-- Begin page content -->
    <main role="main" class="flex-shrink-0">
        <div id="registration" class="container mt-5">
            <div class="row">
                <div class="col-md-6 offset-md-3 mt-5">
                    <h1 class="mt-3 mr-3 ml-3 d-inline-block align-middle">Registration</h1>
                    <form action=<?=$module->getUrl("pages/provider.php", true, true);?> method="POST" class="mr-3 ml-3">
                        <input type="hidden" name="action" value="edit_provider"/>
                        <input type="hidden" name="record_id" value="<?=$edit_id?>"/>
                        <input type="hidden" name="provider_email" value="<?=$provider_email?>">
                        <h4 class="pt-4 pb-1 mb-4">Create an Account</h4>
                        <aside>
                            <div class="form-group">
                                <label for="exampleInputEmail1">Email address</label>
                                <input type="email" class="form-control"  disabled placeholder="johndoe@stanford.edu" aria-describedby="emailHelp" value="<?=$provider_email?>">
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
                                    <input class="form-check-input" <?= ($provider_profession == 1) ? "checked" : "" ?> type="checkbox" value="1"  name="provider_profession"  id="defaultCheck1">
                                    <label class="form-check-label" for="defaultCheck1">Physician (MD,DO)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" <?= ($provider_profession == 2) ? "checked" : "" ?> type="checkbox" value="2" name="provider_profession" id="defaultCheck2">
                                    <label class="form-check-label" for="defaultCheck1">Pharmacist</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" <?= ($provider_profession == 3) ? "checked" : "" ?> type="checkbox" value="3" name="provider_profession" id="defaultCheck3">
                                    <label class="form-check-label" for="defaultCheck1">Nurse Practitioner (NP)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" <?= ($provider_profession == 4) ? "checked" : "" ?> type="checkbox" value="4" name="provider_profession" id="defaultCheck4">
                                    <label class="form-check-label" for="defaultCheck1">Physician Assistant</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" <?= ($provider_profession == 5) ? "checked" : "" ?> type="checkbox" value="5" name="provider_profession" id="defaultCheck5">
                                    <label class="form-check-label" for="defaultCheck1">Registered Nurse (RN)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" <?= ($provider_profession == 6) ? "checked" : "" ?> type="checkbox" value="6" name="provider_profession" id="defaultCheck6">
                                    <label class="form-check-label" for="defaultCheck1">Medical Intern</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" <?= ($provider_profession == 7) ? "checked" : "" ?> type="checkbox" value="7" name="provider_profession" id="defaultCheck7">
                                    <label class="form-check-label" for="defaultCheck1">Pharmacy Technician</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" <?= ($provider_profession == 8) ? "checked" : "" ?> type="checkbox" value="8" name="provider_profession" id="defaultCheck7">
                                    <label class="form-check-label" for="defaultCheck1">Medical Assistant</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" <?= ($provider_profession == 9) ? "checked" : "" ?> type="checkbox" value="9" name="provider_profession" id="defaultCheck8">
                                    <label class="form-check-label" for="defaultCheck1">Researcher</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" <?= ($provider_profession == 10) ? "checked" : "" ?> type="checkbox" value="10" name="provider_profession" id="defaultCheck9">
                                    <label class="form-check-label" for="defaultCheck1">Other</label>
                                </div>
                            </div>
                        </aside>

                        <h4 class="pt-4 pb-1 mb-4">Personal Information</h4>
                        <aside>
                            <div class="form-group row">
                                <label for="exampleInputEmail1" class="col-md-6">First Name<span>*</span></label>
                                <input type="text" class="form-control col-md-5" id="provider_fname" name="provider_fname" placeholder="First Name" value="<?=$provider_fname?>" >
                            </div>
                            <div class="form-group row">
                                <label for="exampleInputEmail1" class="col-md-6">Middle Name</label>
                                <input type="text" class="form-control col-md-5" id="provider_mname" name="provider_mname"  placeholder="Middle Name" value="<?=$provider_mname?>">
                            </div>
                            <div class="form-group row">
                                <label for="exampleInputEmail1" class="col-md-6">Last Name<span>*</span></label>
                                <input type="text" class="form-control col-md-5" id="provider_lname" name="provider_lname" placeholder="Last Name" value="<?=$provider_lname?>">
                            </div>
                            <div class="form-group row">
                                <label for="exampleInputEmail1" class="col-md-6">Date of Birth<span>*</span></label>
                                <input type="text" class="form-control col-md-5" id="provider_dob" name="provider_dob" placeholder="MM/DD/YYYY" value="<?=$provider_dob?>">
                            </div>
                        </aside>


                        <div class="<?=$edit_provider?>">
                            <h4 class="pt-4 pb-1 mb-4">Professional License</h4>
                            <aside>
                                <div class="form-group row">
                                    <label for="provider_dea" class="col-md-6">DEA Numbers(s)</label>
                                    <input type="text" class="form-control col-md-5" id="provider_dea" name="provider_dea" placeholder="DEA #s" value="<?=$provider_dea?>">
                                </div>
                                <div class="form-group row">
                                    <label for="exampleInputEmail1" class="col-md-6">Professional License Number</label>
                                    <input type="text" class="form-control col-md-5" id="provider_lic_num" name="provider_lic_num"  placeholder="License #" value="<?=$provider_lic_num?>" >
                                </div>
                                <div class="form-group row">
                                    <label for="exampleInputEmail1" class="col-md-6">License Type</label>
                                    <input type="text" class="form-control col-md-5" id="provider_lic_type" name="provider_lic_type" placeholder="License Type" value="<?=$provider_lic_type?>">
                                </div>
                                <div class="form-group row">
                                    <label for="exampleInputEmail1" class="col-md-6">Healthcare Specialty</label>
                                    <input type="text" class="form-control col-md-5" id="provider_specialty" name="provider_specialty"  placeholder="Specialty" value="<?=$provider_specialty?>">
                                </div> 
                            </aside>

                            <h4 class="pt-4 pb-1 mb-4">Employer</h4>
                            <aside>
                                <div class="form-group row">
                                    <label for="exampleInputEmail1" class="col-md-6">Name</label>
                                    <input type="text" class="form-control col-md-5" id="employer_name" name="employer_name" placeholder="Employer Name" value="<?=$employer_name?>">
                                </div>
                                <div class="form-group row">
                                    <label for="exampleInputEmail1" class="col-md-6">Address</label>
                                    <input type="text" class="form-control col-md-5" id="employer_address" name="employer_address" placeholder="Employer Address" value="<?=$employer_address?>">
                                </div>
                                <div class="form-group row">
                                    <label for="exampleInputEmail1" class="col-md-6">City<span>*</span></label>
                                    <input type="text" class="form-control col-md-5" id="employer_city" name="employer_city" placeholder="Employer City" value="<?=$employer_city?>">
                                </div>
                                <div class="form-group row">
                                    <label for="exampleInputEmail1" class="col-md-6">State<span>*</span></label>
                                    <input type="text" class="form-control col-md-5" id="employer_state" name="employer_state" placeholder="Employer State" value="<?=$employer_state?>">
                                </div>
                                <div class="form-group row">
                                    <label for="exampleInputEmail1" class="col-md-6">Zip code<span>*</span></label>
                                    <input type="text" class="form-control col-md-5" id="employer_zip" name="employer_zip" placeholder="Employer Zip" value="<?=$employer_zip?>">
                                </div>
                                <div class="form-group row">
                                    <label for="exampleInputEmail1" class="col-md-6">Phone Number<span>*</span></label>
                                    <input type="text" class="form-control col-md-5" id="employer_phone" name="employer_phone" placeholder="Employer Phone" value="<?=$employer_phone?>">
                                </div>
                                <div class="form-group row">
                                    <label for="exampleInputEmail1" class="col-md-6">Do you dispense medication out of your office?<span>*</span></label>
                                    <label class="form-check-label d-inline col-md-2 pl-4" for="defaultCheck11"><input class="form-check-input" type="radio" <?= ($employer_med_dispense) ? "checked" : ""?> value="1" name="employer_med_dispense" id="defaultCheck11"> Yes</label>
                                    <label class="form-check-label d-inline col-md-2" for="defaultCheck12"><input class="form-check-input" type="radio" <?= ($employer_med_dispense=="0") ? "checked" : ""?> value="0" name="employer_med_dispense" id="defaultCheck12"> No</label>
                                </div>
                            </aside>
                        </div>

                        <div class="<?=$edit_delegate?>">
                            <h4 class="pt-4 pb-1 mb-4">Delegate</h4>
                            <aside>
                                <div class="form-group">
                                    <label for="exampleInputEmail1">I am a delegate for the following people<span>*</span>:</label>
                                </div>
                                <div class="form-group">
                                    <label for="exampleInputEmail1 d-block ">Email Address</label>
                                    <div class='row'>
                                        <input type="text" class="form-control col-md-8 ml-3" id="exampleInputEmail1" > <button id="add_people" class='btn btn-info btn-sm col-md-2 ml-3'>Add +</button>
                                    </div>
                                </div>

                                <div class="form-group" >
                                    <label for="exampleInputEmail1">Selected Personnel<span>*</span></label>
                                </div>
                                <div class="delegates">
                                    <?php
                                        foreach($delegates as $d_id => $delegate){
                                            echo '<div class="row mb-1">
                                                <input type="text" class="form-control col-md-8 ml-3" disabled value="'.$delegate.'"> <button class="btn btn-info btn-sm col-md-2 ml-3 remove_delegate">delete</button>
                                            </div>';
                                        }
                                    
                                    ?>
                                </div>
                            </aside>
                        </div>

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
$(document).ready(function(e){
    $("#add_people").click(function(e){
        e.preventDefault();
        var new_email = $("#exampleInputEmail1").val();

        if(new_email != ""){
            $("#exampleInputEmail1").val("");
            var new_delegate = $(delegate);
            new_delegate.find("input").val(new_email);
            $(".delegates").append(new_delegate);
        }
    });

    $(".delegates").on("click",".remove_delegate", function(e){
        e.preventDefault();
        $(this).closest(".form-group").fadeOut("medium");
    });
});
</script>