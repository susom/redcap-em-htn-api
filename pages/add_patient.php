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
                    <ul class="nav nav-tabs border-0">
                        <li class="nav-item  rounded-top">
                            <a class="nav-link active" href="#" data-tab="profile">Profile</a>
                        </li>
                    </ul>
                    <div class="bg-light rounded-right rounded-bottom rounded-left p-3 profile panels">
                        <div class="patient_detail mb-5">
                            <form method="POST">
                            <div class="patient_name mb-5 pt-5">
                                <fig class="patient_profile d-block text-center mx-auto">
                                    <figure><a href="#" class="rounded-circle add_photo d-inline-block">Add Photo</a></figure>
                                    <figcaption class="my-4">
                                        <input type="text" name='patient_fname' placeholder="First Name"/> 
                                        <input type="text" class="mx-4" name='patient_mname' placeholder="Middle Name"/> 
                                        <input type="text" name='patient_lname' placeholder="Last Name"/>

                                        <div class="my-4">
                                            <label>
                                                <span>Email: </span>
                                                <input type="text" name='patient_email' placeholder="jane@doe.com"/> 
                                            </label>
                                            <label>
                                                <span>Phone: </span>
                                                <input type="text" name='patient_phone' placeholder="555-555-1234"/> 
                                            </label>
                                        </div>

                                        <div class="my-4 bg-primary text-light p-5">
                                            <label>
                                                <span>Systolic Goal: </span>
                                                <input type="text" name='patient_bp_target_systolic' placeholder="120"/> 
                                            </label>
                                            <label>
                                                <span>Diastolic Goal: </span>
                                                <input type="text" name='patient_bp_target_diastolic' placeholder="80"/> 
                                            </label>
                                            <label>
                                                <span>Pulse Goal: </span>
                                                <input type="text" name='patient_bp_target_pulse' placeholder="65"/> 
                                            </label>
                                        </div>
                                    </figcaption>
                                </fig>
                            </div>

                            <div class="patient_details row pt-3 mt-5">
                                <div class="col-md-4 offset-md-1">
                                    <dl class="mb-4">
                                    <dt class="d-inline-block">DOB</dt>
                                    <dd class="d-inline-block"><input type="text" name='patient_birthday' placeholder="mm/dd/yyyy"/></dd>
                                    </dl>

                                    <dl class="mb-4 ">
                                    <dt class="d-inline-block align-top">SEX</dt>
                                    <dd class="d-inline-block align-top">
                                        <label class='font-weight-light mb-0 d-block'><input type="radio" class='form-radio-input patient_group align-baseline' name='sex' value="Male"/> Male</label>
                                        <label class='font-weight-light d-block'><input type="radio" class='form-radio-input patient_group align-baseline' name='sex' value="Female"/> Female</label></dd>
                                    </dl>

                                    <dl class="mb-4">
                                    <dt class="d-inline-block">WEIGHT</dt>
                                    <dd class="d-inline-block"><input type="text" name='weight' placeholder="weight"/></dd>
                                    </dl>

                                    <dl class="mb-4">
                                    <dt class="d-inline-block">HEIGHT</dt>
                                    <dd class="d-inline-block"><input type="text" name='height' placeholder="height"/></dd>
                                    </dl>

                                    <dl class="mb-4">
                                    <dt class="d-inline-block">BMI</dt>
                                    <dd class="d-inline-block"><input type="text" name='bmi' placeholder="BMI"/></dd>
                                    </dl>

                                    <dl class="mb-4">
                                    <dt class="d-inline-block align-top">CKD</dt>
                                    <dd class="d-inline-block align-top">
                                        <label class='font-weight-light mb-0 d-block'><input type="radio" class='form-radio-input patient_group align-baseline' name='ckd' value="1"/> Yes</label>
                                        <label class='font-weight-light d-block'><input type="radio" class='form-radio-input patient_group align-baseline' name='ckd' value="0"/> No</label></dd>
                                    </dl>
                                </div>
                                <div class="col-md-6 offset-md-1">
                                    <dl class="mb-4">
                                    <dt class="d-inline-block align-top">Demographic</dt>
                                    <dd class="d-inline-block align-top">
                                    <?php
                                    $patient_groups = array("General Population - Non-Black (no CKD present)","General Population - Black (no CKD present)","CKD Present","Resistant Hypertension","All Drug Classes");
                                    foreach($patient_groups as $pg){
                                        echo "<label class='font-weight-light mb-0  d-block'><input type='radio' class='form-radio-input patient_group align-baseline' name='patient_group' value='$pg' > $pg</label>\r\n";
                                    }
                                    ?>
                                    </dd>
                                    </dl>
                                    
                                    <dl class="mb-4">
                                    <dt class="d-inline-block">Comorbidity</dt>
                                    <dd class="d-inline-block"><input type="text" name='comorbidity' placeholder="comma seperate"/></dd>
                                    </dl>

                                    <dl class="mb-4">
                                    <dt class="d-inline-block align-top">Planning Pregnancy</dt>
                                    <dd class="d-inline-block align-top">
                                        <label class='font-weight-light mb-0 d-block'><input type="radio" class='form-radio-input patient_group align-baseline' name='planning_pregnancy' value="1"/> Yes</label>
                                        <label class='font-weight-light d-block'><input type="radio" class='form-radio-input patient_group align-baseline' name='planning_pregnancy' value="0"/> No</label>
                                    </dd>
                                    </dl>
                                    <dl class="mb-4">
                                    <dt class="d-inline-block align-top">Pharmacy Info</dt>
                                    <dd class="d-inline-block"><input type="text" name='pharmacy_info' placeholder="Pharmacy Name"/></dd>
                                    </dl>
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
