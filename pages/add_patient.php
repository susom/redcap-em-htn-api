<?php
namespace Stanford\HTNapi;
/** @var \Stanford\HTNapi\HTNapi $module */

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

include("components/gl_checklogin.php");

if(!empty($_POST)){
    $action = $_POST["action"];
    $error  = $module->addPatient($_POST);
    if(empty($error["errors"])){
        header("location:".$module->getUrl("pages/dashboard.php", true, true));
        exit;
    }else{
        $patient = $_POST;
    }

    //THERE ARE ERRORS
    switch($action){
        case "edit":

        break;

        case "add":
            
        break;

        default :
            
            
        break;
    }

}

if(!empty($_GET["patient"])){
    $patient_id = $_GET["patient"];
    $patient    = $module->getPatientDetails($patient_id);

    $patient_name       = $patient["patient_fname"] . " "  . $patient["patient_mname"] . " " . $patient["patient_lname"];
    $tree_id            = $patient["current_treatment_plan_id"];
    $current_step_idx   = $patient["patient_treatment_status"];
    $rec_step_idx       = $patient["patient_rec_tree_step"];
}

$provider_id    = !empty($_SESSION["logged_in_user"]["sponsor_id"]) ? $_SESSION["logged_in_user"]["sponsor_id"] : $_SESSION["logged_in_user"]["record_id"];

$patient_id     = !empty($patient["record_id"]) ? $patient["record_id"] : null;
$patient_fname  = !empty($patient["patient_fname"]) ? $patient["patient_fname"] : null;
$patient_mname  = !empty($patient["patient_mname"]) ? $patient["patient_mname"] : null;
$patient_lname  = !empty($patient["patient_lname"]) ? $patient["patient_lname"] : null;
$patient_email  = !empty($patient["patient_email"]) ? $patient["patient_email"] : null;
$patient_phone  = !empty($patient["patient_phone"]) ? $patient["patient_phone"] : null;
$patient_mrn    = !empty($patient["patient_mrn"])   ? $patient["patient_mrn"] : null;
$patient_bp_target_systolic     = !empty($patient["patient_bp_target_systolic"])    ? $patient["patient_bp_target_systolic"] : null;
$patient_bp_target_diastolic    = !empty($patient["patient_bp_target_diastolic"])   ? $patient["patient_bp_target_diastolic"] : null;
$patient_bp_target_pulse        = !empty($patient["patient_bp_target_pulse"])       ? $patient["patient_bp_target_pulse"] : null;
$current_treatment_plan_id      = !empty($patient["current_treatment_plan_id"])     ? $patient["current_treatment_plan_id"] : null;

$patient_group      = $patient["patient_group"];
$patient_birthday   = $patient["patient_birthday"] == "dob n/a" ? "" : $patient["patient_birthday"];
$sex                = $patient["sex"] == "sex n/a" ? "" : $patient["sex"];
$weight             = $patient["weight"] == "weight n/a" ? "" : $patient["weight"];
$height             = $patient["height"] == "height n/a" ? "" : $patient["height"];
$bmi                = $patient["bmi"] == "BMI n/a" ? "" : $patient["bmi"];
$planning_pregnancy = $patient["planning_pregnancy"];
$ckd                = $patient["ckd"] == "CKD n/a" ? "" : $patient["ckd"];
$comorbidity        = $patient["comorbidity"] == "comorbidity n/a" ? "" : $patient["comorbidity"];
$pharmacy_info      = $patient["pharmacy_info"] == "pharmacy n/a" ? "" : $patient["pharmacy_info"];



$add_edit_btn_text  = !empty($patient) ? "Edit Patient $patient_fname's Data" : "Add New Patient";
$action             = !empty($patient) && empty($action) ? "edit" : "add";

//First Get the Pre made DEFAULT Trees
$default_trees  = $module->getDefaultTrees();

//Then get the Provider Custom Trees
if(empty($_SESSION["logged_in_user"]["provider_trees"])){
    $provider_trees = $module->getDefaultTrees($provider_id);
    $_SESSION["logged_in_user"]["provider_trees"] = $provider_trees;
}
$provider_trees = $_SESSION["logged_in_user"]["provider_trees"];
$default_trees  = array_merge($default_trees, $provider_trees);


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
                <h1 class="mt-0 mb-3 mr-3 ml-3 d-inline-block align-middle"><?=$add_edit_btn_text?></h1>
            </div>

            <div class="row patient_body">
                <div class="patient_details col-md-12">
                    <div class="bg-light rounded-right rounded-bottom rounded-left p-3 profile panels">
                        <div class="patient_detail mb-5">
                            <form method="POST">
                                <input type="hidden" name="action" value="<?=$action?>"/>
                                <input type="hidden" name="record_id" value="<?=$patient_id?>"/>

                                <?php
                                    if(!empty($default_trees)){
                                ?>
                                <div class="patient_name  col-sm-10 offset-sm-1 row pt-5 mb-3">
                                    <?php
                                        if(!empty($error)){
                                            $greenred   = !empty($error["errors"]) ? "danger" : "success";
                                            $msg        = !empty($error["errors"]) ? $error["errors"] : "Action Successful!";
                                            echo "<div class='col-sm-12 alert alert-".$greenred."'>".$msg."</div>";
                                        }
                                    ?>
                                    <div class="form-group col-sm-12 mb-5">
                                        <h3 >Patient Prescription Tree</h3>
                                        <p class="text-muted lead small">When adding a new patient, please start them off with a preselected Prescription Tree</p>

                                        <select class="form-control" id="current_treatment_plan_id" name="current_treatment_plan_id">
                                            <?php
                                                foreach($default_trees as $idx => $ptree){
                                                    $tree_id    = $ptree["tree_meta"]["record_id"];
                                                    $selected   = $current_treatment_plan_id == $tree_id ? "selected" : "";
                                                    echo "<option value='".$tree_id."' $selected>".$ptree["label"]."</option>";
                                                }
                                            ?>
                                        </select>
                                    </div>
                                    <fig class="patient_profile d-block mx-auto mb-3">
                                        <!-- <figure class="text-center "><a href="#" class="rounded-circle add_photo d-inline-block">Add Photo</a></figure> -->
                                        <figcaption class="col-sm-12 row">
                                            <h3 class="col-sm-12">Patient Baseline</h3>
                                            <p class="col-sm-12 text-muted lead small">The rest of the Patient's baseline data will be pulled from STARR/Epic databses based on the patient's MRN. <br> All required fields are marked with a *</p>

                                            <div class="form-group col-sm-12">
                                                <label for="patient_mrn"><b>Patient MRN*</b></label>
                                                <input type="text" class="form-control" name="patient_mrn" value="<?=$patient["patient_mrn"]?>" id="patient_mrn" aria-describedby="patient_mrn" placeholder="eg; 123456789">
                                                <small id="patient_mrn_help" class="form-text text-muted">This will be used to link with STARR/EPIC databses.</small>
                                            </div>
                                            
                                            <div class="form-group col-sm-6">
                                                <label for="patient_email"><b>Patient Email*</b></label>
                                                <input type="text" class="form-control" name="patient_email" value="<?=$patient["patient_email"]?>" id="patient_email" aria-describedby="patient_email" placeholder="eg; jane@doe.com">
                                                <small id="patient_email_help" class="form-text text-muted">This will be used to authorize BP data.</small>
                                            </div>

                                            <div class="col-sm-12 my-4 bg-info text-light py-3 row">
                                                <h3 class="col-sm-12"><b>Blood Pressure Goals</b></h3>
                                                <div class="form-group col-sm-4">
                                                    <label for="patient_bp_target_systolic"><b>Systolic Goal*</b></label>
                                                    <input type="text" class="form-control" name="patient_bp_target_systolic" value="<?=$patient["patient_bp_target_systolic"] ?? 120?>" id="patient_bp_target_systolic" aria-describedby="patient_bp_target_systolic">
                                                </div>
                                                <div class="form-group col-sm-4">
                                                    <label for="patient_bp_target_diastolic"><b>Diastolic Goal*</b></label>
                                                    <input type="text" class="form-control" name="patient_bp_target_diastolic" value="<?=$patient["patient_bp_target_diastolic"] ?? 80?>" id="patient_bp_target_diastolic" aria-describedby="patient_bp_target_diastolic">
                                                </div>
                                                <div class="form-group col-sm-4">
                                                    <label for="patient_bp_target_pulse"><b>Pulse Goal*</b></label>
                                                    <input type="text" class="form-control" name="patient_bp_target_pulse" value="<?=$patient["patient_bp_target_pulse"] ?? 65?>" id="patient_bp_target_pulse" aria-describedby="patient_bp_target_pulse">
                                                </div>
                                            </div>
                                        </figcaption>
                                    </fig>
                                </div>
                                
                                <div class="patient_details col-sm-10 offset-sm-1 mb-3 row <?= $action == "edit" ? "show" : "hide"?>">            
                                    <h3 class="col-sm-12">Patient Details</h3>
                                    <em class="col-sm-12 mb-3">Much of this will be pulled and automatically refreshed from STARR/EPIC</em>
                                    <div class="form-group col-sm-4">
                                        <label for="patient_fname"><b>Patient First Name*</b></label>
                                        <input type="text" class="form-control" name="patient_fname" value="<?=$patient["patient_fname"]?>" id="patient_fname" aria-describedby="patient_fname" placeholder="First Name">
                                    </div>
                                    <div class="form-group col-sm-4">
                                        <label for="patient_mname"><b>Middle Name</b></label>
                                        <input type="text" class="form-control" name="patient_mname" value="<?=$patient["patient_mname"]?>" id="patient_mname" aria-describedby="patient_mname" placeholder="Middle Name">
                                    </div>
                                    <div class="form-group col-sm-4">
                                        <label for="patient_lname"><b>Last Name*</b></label>
                                        <input type="text" class="form-control" name="patient_lname" value="<?=$patient["patient_lname"]?>" id="patient_lname" aria-describedby="patient_lname" placeholder="Last Name">
                                    </div>
                                    
                                    <div class="form-group col-sm-7">
                                        <label for="patient_phone"><b>Patient Cell*</b></label>
                                        <input type="text" class="form-control" name="patient_phone" value="<?=$patient["patient_phone"]?>" id="patient_phone" aria-describedby="patient_phone" placeholder="eg; 555-555-1234">
                                        <small id="patient_phone_help" class="form-text text-muted">This will be used to text surveys to patient.</small>
                                    </div>
                                    
                                    
                                    <div class="form-group col-sm-6">
                                        <label><b>Patient Sex*</b></label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" <?= $patient["sex"] == "Male" ? "checked" : ""?> name="sex" id="sex1" value="Male">
                                            <label class="form-check-label" for="sex1">
                                                Male
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" <?= $patient["sex"] == "Female" ? "checked" : ""?> name="sex" id="sex2" value="Female">
                                            <label class="form-check-label" for="sex2">
                                                Female
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-group col-sm-6">
                                        <label for="patient_mrn"><b>Patient DOB*</b></label>
                                        <input type="text" class="form-control" name="patient_birthday" value="<?=$dob?>" id="patient_birthday" aria-describedby="patient_birthday" placeholder="eg; mm/dd/yyyy">
                                        <small id="patient_birthday_help" class="form-text text-muted">*This will also be pulled from STARR/EPIC databses.</small>
                                    </div>

                                    <div class="col-sm-12 row">
                                        <div class="form-group col-sm-4">
                                            <label for="weight"><b>Weight*</b></label>
                                            <input type="text" class="form-control" name="weight" value="<?=$weight?>" id="weight" aria-describedby="weight" placeholder="eg; 170lb">
                                        </div>

                                        <div class="form-group col-sm-4">
                                            <label for="height"><b>Height*</b></label>
                                            <input type="text" class="form-control" name="height" value="<?=$height?>" id="height" aria-describedby="height" placeholder="eg; 5'10">
                                        </div>

                                        <div class="form-group col-sm-4">
                                            <label for="bmi"><b>BMI*</b></label>
                                            <input type="text" class="form-control" name="bmi" value="<?=$bmi?>" id="bmi" aria-describedby="bmi" placeholder="eg; 23">
                                        </div>
                                    </div>
                                    <div class="col-sm-12 row pt-5">
                                        <h3 class="col-sm-12">Patient Medical</h3>

                                        <div class="form-group col-sm-6">
                                            <label><b>Patient CKD</b></label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="ckd" <?= $patient["ckd"] == 1 ? "checked" : ""?> id="ckd1" value="1">
                                                <label class="form-check-label" for="ckd1">
                                                    Yes
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="ckd" <?= $patient["ckd"] == 0 ? "checked" : ""?> id="ckd2" value="0">
                                                <label class="form-check-label" for="ckd2">
                                                    No
                                                </label>
                                            </div>
                                        </div>

                                        <div class="form-group col-sm-6">
                                            <label><b>Planning Pregnancy</b></label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="planning_pregnancy" id="planning_pregnancy1" <?= $patient["planning_pregnancy"] == "Yes" ? "checked" : ""?> value="1">
                                                <label class="form-check-label" for="planning_pregnancy1">
                                                    Yes
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="planning_pregnancy" id="planning_pregnancy2" <?= $patient["planning_pregnancy"] == "No" ? "checked" : ""?> value="0">
                                                <label class="form-check-label" for="planning_pregnancy2">
                                                    No
                                                </label>
                                            </div>
                                        </div>

                                        <div class="form-group col-sm-6">
                                            <label><b>Patient Demographic</b></label>
                                            <?php
                                                $patient_groups = array("General Population - Non-Black (no CKD present)","General Population - Black (no CKD present)","CKD Present","Resistant Hypertension","All Drug Classes");
                                                foreach($patient_groups as $i=> $pg){
                                                    $checked = $patient["patient_group"] == $pg ? "checked" : "";
                                                    ?>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" <?=$checked ?> name="patient_group" id="patient_group<?=$i?>" value="<?=$pg?>">
                                                            <label class="form-check-label" for="ckd2">
                                                                <?=$pg?>
                                                            </label>
                                                        </div>
                                                    <?php
                                                }
                                            ?>
                                        </div>
                                    
                                    
                                        <div class="form-group col-sm-12">
                                            <label for="comorbidity"><b>Comorbidities</b></label>
                                            <input type="text" class="form-control" name="comorbidity" value="<?=$comorbidity?>" id="comorbidity" aria-describedby="comorbidity" placeholder="eg; diabetes, whooping cough">
                                            <small id="comorbidity" class="form-text text-muted">*This will also be pulled from STARR/EPIC databses.</small>
                                        </div>
                                    
                                        <div class="form-group col-sm-12">
                                            <label for="pharmacy_info"><b>Pharmacy Info</b></label>
                                            <input type="text" class="form-control" name="pharmacy_info" value="<?=$pharmacy_info?>" id="pharmacy_info" aria-describedby="pharmacy_info" placeholder="eg; CVS">
                                        </div>
                                                

                                        
                                    </div>
                                </div>
                                
                                <div class="btns text-center mt-2 mb-5">
                                    <button type="submit" id="save_patient" class='btn btn-primary btn-lg'><?=$add_edit_btn_text?></button>
                                </div>
                                
                                <?php
                                    }
                                ?>
                            </form>
                        </div>
                        
                    </div>
                    
                </div>
            </div>
        </div>
    </main>

    <?php include("components/gl_foot.php"); ?>
</body>
</html>