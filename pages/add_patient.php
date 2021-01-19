<?php
namespace Stanford\HTNapi;
/** @var \Stanford\HTNapi\HTNapi $module */

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
                            <form method="POST">
                            <div class="patient_name mb-5 pt-5">
                                <fig class="patient_profile d-block mx-auto row">
                                    <figure class="text-center "><a href="#" class="rounded-circle add_photo d-inline-block">Add Photo</a></figure>
                                    
                                    <figcaption class="my-4 col-sm-10 offset-sm-1 row">
                                        <h3 class="col-sm-12">Patient Contact Details</h3>
                                        <div class="form-group col-sm-4">
                                            <label for="patient_fname"><b>Patient First Name</b></label>
                                            <input type="text" class="form-control" name="patient_fname" id="patient_fname" aria-describedby="patient_fname" placeholder="First Name">
                                        </div>
                                        <div class="form-group col-sm-4">
                                            <label for="patient_mname"><b>Middle Name</b></label>
                                            <input type="text" class="form-control" name="patient_mname" id="patient_mname" aria-describedby="patient_mname" placeholder="Middle Name">
                                        </div>
                                        <div class="form-group col-sm-4">
                                            <label for="patient_lname"><b>Last Name</b></label>
                                            <input type="text" class="form-control" name="patient_lname" id="patient_lname" aria-describedby="patient_lname" placeholder="Last Name">
                                        </div>
                                  
                                        <div class="form-group col-sm-12">
                                            <label for="patient_mrn"><b>Patient MRN</b></label>
                                            <input type="text" class="form-control" name="patient_mrn" id="patient_mrn" aria-describedby="patient_mrn" placeholder="eg; 123456789">
                                            <small id="patient_mrn_help" class="form-text text-muted">This will be used to link with STARR/EPIC databses.</small>
                                        </div>
                                        
                                        <div class="form-group col-sm-6">
                                            <label for="patient_email"><b>Patient Email</b></label>
                                            <input type="text" class="form-control" name="patient_email" id="patient_email" aria-describedby="patient_email" placeholder="eg; jane@doe.com">
                                            <small id="patient_email_help" class="form-text text-muted">This will be used to authorize BP data.</small>
                                        </div>

                                        <div class="form-group col-sm-4">
                                            <label for="patient_phone"><b>Patient Cell</b></label>
                                            <input type="text" class="form-control" name="patient_phone" id="patient_phone" aria-describedby="patient_phone" placeholder="eg; 555-555-1234">
                                            <small id="patient_phone_help" class="form-text text-muted">This will be used to text surveys to patient.</small>
                                        </div>

                                        <div class="my-4 bg-info text-light py-3 row col-sm-12">
                                            <h3 class="col-sm-12"><b>Blood Pressure Goals</b></h3>
                                            <div class="form-group col-sm-4">
                                                <label for="patient_bp_target_systolic"><b>Systolic Goal</b></label>
                                                <input type="text" class="form-control" name="patient_bp_target_systolic" id="patient_bp_target_systolic" aria-describedby="patient_bp_target_systolic" placeholder="eg; 120">
                                            </div>
                                            <div class="form-group col-sm-4">
                                                <label for="patient_bp_target_diastolic"><b>Diastolic Goal</b></label>
                                                <input type="text" class="form-control" name="patient_bp_target_diastolic" id="patient_bp_target_diastolic" aria-describedby="patient_bp_target_diastolic" placeholder="eg; 80">
                                            </div>
                                            <div class="form-group col-sm-4">
                                                <label for="patient_bp_target_pulse"><b>Pulse Goal</b></label>
                                                <input type="text" class="form-control" name="patient_bp_target_pulse" id="patient_bp_target_pulse" aria-describedby="patient_bp_target_pulse" placeholder="eg; 65">
                                            </div>
                                        </div>
                                    </figcaption>
                                </fig>
                            </div>

                            <div class="patient_details col-sm-10 offset-sm-1 row pt-3">
                                <h3 class="col-sm-12">Patient Baseline</h3>
                                <em class="col-sm-12 mb-3">Much of this will be pulled and automatically refreshed from STARR/EPIC</em>
                                
                                <div class="form-group col-sm-6">
                                    <label><b>Patient Sex*</b></label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="sex" id="sex1" value="Male">
                                        <label class="form-check-label" for="sex1">
                                            Male
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="sex" id="sex2" value="Female">
                                        <label class="form-check-label" for="sex2">
                                            Female
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group col-sm-6">
                                    <label for="patient_mrn"><b>Patient DOB*</b></label>
                                    <input type="text" class="form-control" name="patient_birthday" id="patient_birthday" aria-describedby="patient_birthday" placeholder="eg; mm/dd/yyyy">
                                    <small id="patient_birthday_help" class="form-text text-muted">*This will also be pulled from STARR/EPIC databses.</small>
                                </div>

                                <div class="col-sm-12 row">
                                    <div class="form-group col-sm-4">
                                        <label for="weight"><b>Weight*</b></label>
                                        <input type="text" class="form-control" name="weight" id="weight" aria-describedby="weight" placeholder="eg; 170lb">
                                    </div>

                                    <div class="form-group col-sm-4">
                                        <label for="height"><b>Height*</b></label>
                                        <input type="text" class="form-control" name="height" id="height" aria-describedby="height" placeholder="eg; 5'10">
                                    </div>

                                    <div class="form-group col-sm-4">
                                        <label for="bmi"><b>BMI*</b></label>
                                        <input type="text" class="form-control" name="bmi" id="bmi" aria-describedby="bmi" placeholder="eg; 23">
                                    </div>
                                </div>



                                <div class="col-sm-12 row pt-5">
                                    <h3 class="col-sm-12">Patient Medical</h3>

                                    <div class="form-group col-sm-6">
                                        <label><b>Patient CKD</b></label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="ckd" id="ckd1" value="1">
                                            <label class="form-check-label" for="ckd1">
                                                Yes
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="ckd" id="ckd2" value="0">
                                            <label class="form-check-label" for="ckd2">
                                                No
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-group col-sm-6">
                                        <label><b>Planning Pregnancy</b></label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="planning_pregnancy" id="planning_pregnancy1" value="1">
                                            <label class="form-check-label" for="planning_pregnancy1">
                                                Yes
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="planning_pregnancy" id="planning_pregnancy2" value="0">
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
                                                ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="patient_group" id="patient_group<?=$i?>" value="<?=$pg?>">
                                                        <label class="form-check-label" for="ckd2">
                                                            <?=$pg?>
                                                        </label>
                                                    </div>
                                                <?
                                            }
                                        ?>
                                    </div>
                                
                                
                                    <div class="form-group col-sm-12">
                                        <label for="comorbidity"><b>Comorbidities</b></label>
                                        <input type="text" class="form-control" name="comorbidity" id="comorbidity" aria-describedby="comorbidity" placeholder="eg; diabetes, whooping cough">
                                        <small id="comorbidity" class="form-text text-muted">*This will also be pulled from STARR/EPIC databses.</small>
                                    </div>
                                
                                    <div class="form-group col-sm-12">
                                        <label for="pharmacy_info"><b>Pharmacy Info</b></label>
                                        <input type="text" class="form-control" name="pharmacy_info" id="pharmacy_info" aria-describedby="pharmacy_info" placeholder="eg; CVS">
                                    </div>
                                            

                                    <div class="form-group col-sm-12">
                                        <label for="exampleFormControlSelect1"><b>Prescription Tree</b></label>
                                        <select class="form-control" id="alias_select" name="alias_select">
                                            <option value="99">View or Edit Saved Templates</option>
                                            <?php
                                                foreach($provider_trees as $ptree){
                                                    echo "<option value='".$ptree["record_id"]."' data-templateid='".$ptree["templpate_id"]."' data-raw='".json_encode($ptree)."'>".$ptree["template_name"]."</option>";
                                                }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="btns text-center">
                                <button type="submit" id="save_patient" class='btn btn-primary btn-lg'>Add Patient</button>
                            </div>
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
