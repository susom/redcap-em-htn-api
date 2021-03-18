<?php
namespace Stanford\HTNapi;
/** @var \Stanford\HTNapi\HTNapi $module */


if(isset($_REQUEST)){
    if( isset($_GET["verify"]) ){
        $action = "verify";
        $verification_token = !empty($_GET["verify"]) ? filter_var($_GET["verify"], FILTER_SANITIZE_STRING) : null;
        $verification_email = !empty($_GET["email"]) ? filter_var($_GET["email"], FILTER_SANITIZE_STRING) : null;
    }else{
        $action = $_POST["action"];
    }
    
    switch($action){
        case "register_provider": 
            $module->emDebug("delegate passes through here right?", $_POST);
            $error = $module->registerProvider($_POST); 
            if(empty($error["errors"])){
                if(empty($_POST["record_id"])){
                    $_SESSION["buffer_alert"] = array("errors" => null , "success" => "Account succesfully created.  Please check your email to verify.");
                }
                header("Location: " . $module->getUrl("pages/dashboard.php", true, true));
                exit;
            }

            $provider = $_POST;
            $pf     = "provider_profession_" . $provider["provider_profession"];
            $$pf    = "checked";
        break;
    
        case "verify":
            $verify_account = $module->verifyAccount($verification_email,$verification_token);
            if(array_key_exists("errors",$verify_account) && empty($verify_account["errors"])){
                if(array_key_exists("sponsor_id",$verify_account["provider"]) && !empty($verify_account["provider"]["sponsor_id"])){
                    // stay on reg page, need to complete registration
                    $_SESSION["buffer_alert"] = array("errors" => null , "success" => "Please complete your account registration.");
                    $provider     = $verify_account["provider"];
                }else{
                    $module->loginProvider($verify_account["provider"]["provider_email"],$verify_account["provider"]["provider_pw"], true);
                    $_SESSION["buffer_alert"] = array("errors" => null , "success" => "Account verified.  Welcome to HeartEx! ");
                    header("Location: " . $module->getUrl("pages/dashboard.php", true, true));
                    exit;
                }
            }else{
                $module->emDebug("Sorry token expired");
            }  
        break;
    }
}

//FOR PASSING ALERTS AROUND
if(isset($_SESSION["buffer_alert"])){
    $error = $_SESSION["buffer_alert"];
    unset($_SESSION["buffer_alert"]);
}

$edit_provider = !empty($_SESSION["logged_in_user"]) ? "show_reg" : "hide_reg";
$edit_delegate = empty($provider["record_id"]) ? "show_del" : "hide_del";
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
                <?php
                    if(!empty($error)){
                        $greenred   = !empty($error["errors"]) ? "danger" : "success";
                        $msg        = !empty($error["errors"]) ? $error["errors"] : $error["success"];
                        echo "<div class='col-sm-12 alert alert-".$greenred."'>".$msg."</div>";
                    }
                ?>
                    <h1 class="mt-3 mr-3 ml-3 d-inline-block align-middle">Registration</h1>
                    <p class="text-muted lead small mx-3">Fields marked with * are required</p>

                    <form action=<?=$module->getUrl("pages/registration.php", true, true);?> method="POST" class="mr-3 ml-3">
                        <input type="hidden" name="action" value="register_provider"/>
                        <input type="hidden" name="record_id" value="<?=$provider["record_id"]?>"/>
                        <h4 class="pt-4 pb-1 mb-4">Create an Account</h4>
                        <aside>
                            <div class="form-group">
                                <label for="exampleInputEmail1">Email address*</label>
                                <input type="email" class="form-control" id="provider_email" name="provider_email" placeholder="johndoe@stanford.edu" aria-describedby="emailHelp" value="<?=$provider["provider_email"]?>">
                            </div>
                            <div class="form-group">
                                <label for="exampleInputPassword1">Password*</label>
                                <input type="password" class="form-control" id="provider_pw" name="provider_pw" placeholder="secret password" >
                            </div>
                            <div class="form-group">
                                <label for="exampleInputPassword1">Password Confirmation*</label>
                                <input type="password" class="form-control" id="provider_pw2" name="provider_pw2"  placeholder="confirm password" >
                            </div>
                            <div class="form-group">
                                <label for="exampleInputPassword1">Healthcare Professional*</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" <?=$provider_profession_1?> value="1"  name="provider_profession"  id="defaultCheck1">
                                    <label class="form-check-label" for="defaultCheck1">Physician (MD,DO)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" <?=$provider_profession_2?> value="2" name="provider_profession" id="defaultCheck2">
                                    <label class="form-check-label" for="defaultCheck1">Pharmacist</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" <?=$provider_profession_3?> value="3" name="provider_profession" id="defaultCheck3">
                                    <label class="form-check-label" for="defaultCheck1">Nurse Practitioner (NP)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" <?=$provider_profession_4?> value="4" name="provider_profession" id="defaultCheck4">
                                    <label class="form-check-label" for="defaultCheck1">Physician Assistant</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" <?=$provider_profession_5?> value="5" name="provider_profession" id="defaultCheck5">
                                    <label class="form-check-label" for="defaultCheck1">Registered Nurse (RN)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" <?=$provider_profession_6?> value="6" name="provider_profession" id="defaultCheck6">
                                    <label class="form-check-label" for="defaultCheck1">Medical Intern</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" <?=$provider_profession_7?> value="7" name="provider_profession" id="defaultCheck7">
                                    <label class="form-check-label" for="defaultCheck1">Pharmacy Technician</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" <?=$provider_profession_8?> value="8" name="provider_profession" id="defaultCheck7">
                                    <label class="form-check-label" for="defaultCheck1">Medical Assistant</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" <?=$provider_profession_9?> value="9" name="provider_profession" id="defaultCheck8">
                                    <label class="form-check-label" for="defaultCheck1">Researcher</label>
                                </div>
                            </div>
                        </aside>

                        <h4 class="pt-4 pb-1 mb-4">Personal Information</h4>
                        <aside>
                            <div class="form-group row">
                                <label for="exampleInputEmail1" class="col-md-6">First Name<span>*</span></label>
                                <input type="text" class="form-control col-md-5" id="provider_fname" name="provider_fname" placeholder="First Name" value="<?=$provider["provider_fname"]?>" >
                            </div>
                            <div class="form-group row">
                                <label for="exampleInputEmail1" class="col-md-6">Middle Name</label>
                                <input type="text" class="form-control col-md-5" id="provider_mname" name="provider_mname"  placeholder="Middle Name" value="<?=$provider["provider_mname"]?>" >
                            </div>
                            <div class="form-group row">
                                <label for="exampleInputEmail1" class="col-md-6">Last Name<span>*</span></label>
                                <input type="text" class="form-control col-md-5" id="provider_lname" name="provider_lname" placeholder="Last Name" value="<?=$provider["provider_lname"]?>">
                            </div>
                            <div class="form-group row">
                                <label for="exampleInputEmail1" class="col-md-6">Cell Number (for text alerts)<span>*</span></label>
                                <input type="text" class="form-control col-md-5" id="provider_cell" name="provider_cell" placeholder="" value="<?=$provider["provider_cell"]?>">
                            </div>
                        </aside>


                        <div class="<?=$edit_provider?>">
                            <h4 class="pt-4 pb-1 mb-4">Professional License</h4>
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
                        </div>

                        <div class="<?=$edit_delegate?>">
                            <h4 class="pt-4 pb-1 mb-4">Delegate</h4>
                            <aside>
                                <div class="form-group">
                                    <label for="exampleInputEmail1">I am a delegate for the following people<span>**</span>:</label>
                                </div>
                                <div class="form-group">
                                    <label for="exampleInputEmail1 d-block ">Email Address</label>
                                    <div class='row'>
                                        <input type="text" class="form-control col-md-8 ml-3" id="exampleInputEmail1" > <button id="add_people" class='btn btn-info btn-sm col-md-2 ml-3'>Add +</button>
                                    </div>
                                </div>

                                <div class="form-group" >
                                    <label for="exampleInputEmail1">Selected Personnel<span>**</span></label>
                                </div>
                                <div class="delegates">
                                    
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
            newDelegate(new_email);
        }
    });

    $(".delegates").on("click",".remove_delegate", function(e){
        e.preventDefault();
        $(this).closest(".form-group").fadeOut("medium");
    });

    
    let prefill_delegates;
    <?php
        if(!empty($provider["delegates"])){
            echo "prefill_delegates = " . json_encode($provider["delegates"]) . ";";
        }
    ?>
    if(prefill_delegates.length){
        for(var i in prefill_delegates){
            newDelegate(prefill_delegates[i]);
        }
    }

    function newDelegate(new_email){
        var new_delegate = $(delegate);
        new_delegate.find("input").val(new_email);
        $(".delegates").append(new_delegate);
    }

    <?php
        if(!empty($error)){
    ?>
        setTimeout(function(){
            $(".alert").slideUp("medium");
        },4000);
    <?php    
        }
    ?>
});
</script>