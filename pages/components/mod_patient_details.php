<div id="patient_details" class="col-md-8">
    <ul class="nav nav-tabs border-0">
        <li class="nav-item rounded-top">
            <a class="nav-link active" href="#" data-tab="profile">Profile</a>
        </li>
        <li class="nav-item rounded-right">
            <a class="nav-link" href="#" data-tab="recommendation">Recommendations</a>
        </li>
    </ul>
    <div class="bg-light rounded-right rounded-bottom rounded-left p-3 profile panels row">
        <div class="patient_detail col-md-12">
            <div class="patient_name mb-5 pt-5">
                <div class="patient_status float-left">Urgency: <b>High</b></div>
                <fig class="patient_profile d-block text-center mx-auto">
                    <figure><img src='<?=$module->getUrl('assets/images/icon_anon.gif')?>' class="rounded-circle"/></figure>
                    <figcaption class='h1'><?=$patient["patient_fname"]?> <?=$patient["patient_mname"]?>  <?=$patient["patient_lname"]?></figcaption>
                </fig>
                <a href="#" class="add_notes float-right">Add Notes</a>
            </div>

            <div class="patient_details row mb-5">
                <div class="col-md-5 offset-md-1">
                    <dl class="mb-2">
                    <dt class="d-inline-block">DOB</dt>
                    <dd class="d-inline-block dob"></dd>
                    </dl>
                    <dl class="mb-2">
                    <dt class="d-inline-block">AGE</dt>
                    <dd class="d-inline-block age"></dd>
                    </dl>
                    <dl class="mb-2">
                    <dt class="d-inline-block">SEX</dt>
                    <dd class="d-inline-block sex"></dd>
                    </dl>
                    <dl class="mb-2">
                    <dt class="d-inline-block">WEIGHT</dt>
                    <dd class="d-inline-block weight"></dd>
                    </dl>
                    <dl class="mb-2">
                    <dt class="d-inline-block">HEIGHT</dt>
                    <dd class="d-inline-block height"></dd>
                    </dl>
                    <dl class="mb-2">
                    <dt class="d-inline-block">BMI</dt>
                    <dd class="d-inline-block bmi"></dd>
                    </dl>
                </div>
                <div class="col-md-6">
                    <dl class="mb-2">
                    <dt class="d-inline-block">Demographic</dt>
                    <dd class="d-inline-block demographic"><?=$patient["patient_group"]?></dd>
                    </dl>
                    <dl class="mb-2">
                    <dt class="d-inline-block">Comorbidity</dt>
                    <dd class="d-inline-block comorbidity"><?=$patient["comorbidity"]?></dd>
                    </dl>
                    <dl class="mb-2">
                    <dt class="d-inline-block">BP Cuff Type</dt>
                    <dd class="d-inline-block bp_cuff_type">Omron</dd>
                    </dl>
                    <dl class="mb-2">
                    <dt class="d-inline-block">Planning Pregnancy</dt>
                    <dd class="d-inline-block planning_pregnancy"><?=$patient["planning_pregnancy"]?></dd>
                    </dl>
                    <dl class="mb-2">
                    <dt class="d-inline-block">Pharmacy Info</dt>
                    <dd class="d-inline-block pharmacy_info">CVS</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="patient_data col-md-12 py-2">
            <section class="information">
                <h1 class="text-center p-3">Information</h2>
                <div class="content bg-light mh-10 mx-1">
                    <img src="<?php echo $module->getUrl('assets/images/fpo_info_toggles.png') ?>" style="width:100%"/>
                </div>
                <div class="content bg-light mh-10 mx-1"></div>
            </section>

            <section class="presription_tree">
                <h3 class="text-center p-3">Prescription Tree</h3>
                <div class="content bg-light mh-10 mx-1">
                   TBD 
                </div>
            </section>

            <section class="bp_threshold">
                <h3 class="text-center p-3">BP Threshold</h3>
                <div class="content bg-light mh-10 mx-1b py-3 row">
                    <dl class="col-md-5 offset-md-1">
                        
                        <dt>BP Goal</dt>
                        <dd class="font-weight-light mb-0 systolic_goal">Systolic : <span>130</span></dd>
                        <dd class="font-weight-light mb-0 diastolic_goal">Diastolic : <span>80</span></dd>

                        <dt class="mt-3">Measurement Frequency</dt>
                        <dd class="font-weight-light mb-0 frequency">Frequency : <span>2/week</span></dd>
                        <dd class="font-weight-light mb-0 total_duration">Total Duration : <span>2 weeks</span></dd>
                    </dl>
                    
                    <dl class="col-md-5 offset-md-1">
                        <dt>Pulse Goal</dt>
                        <dd class="font-weight-light mb-0 pulse">Pulse : <span>< 50</span></dd>

                        <dt class="mt-3"><br></dt>
                        <dd class="font-weight-light mb-0">Average Data : <select class="avg_data_range"><option selected>Daily</option><option >Weekly</option><option >AM</option><option >PM</option></select></dd>
                        <dd class="font-weight-light mb-0 define_controlled">Define Uncontrolled : <span>60%</span></dd>
                    </dl>
                </div>
            </section>

            <section class="data">
                <h3 class="text-center p-3">Data</h3>
                <div class="content rounded-bottom bg-light mh-10 mx-1">
                    <img src="<?php echo $module->getUrl('assets/images/fpo_patient_data.png') ?>" style="width:100%"/>
                </div>
            </section>
        </div>
    </div>
    <div class="bg-light rounded-right rounded-bottom rounded-left p-3 recommendation panels row">
        <div class="patient_detail mb-5 col-md-3">
            <div class="patient_name mb-4 pt-5">
                <div class="patient_status float-left">Urgency: <b>High</b></div>
                <fig class="patient_profile d-block text-center mx-auto">
                    <figure><img src='<?=$module->getUrl('assets/images/icon_anon.gif')?>' class="rounded-circle"/></figure>
                    <figcaption class='h1'><?=$patient["patient_fname"]?> <?=$patient["patient_mname"]?>  <?=$patient["patient_lname"]?></figcaption>
                </fig>
            </div>
        </div>
        <div class="patient_summary col-md-9 mt-5 border-left">
            <h4 class="pl-3">Patient Summary</h4>
            <div class="pl-3 mb-5">
                <dl class="mb-0 row">
                <dt class="d-inline-block col-md-6">DOB</dt>
                <dd class="d-inline-block col-md-6 dob"><?=$patient["patient_birthday"]?></dd>
                </dl>
                <dl class="mb-0 row">
                <dt class="d-inline-block col-md-6">AGE</dt>
                <dd class="d-inline-block col-md-6 age"><?=$patient["patient_age"]?></dd>
                </dl>
                <dl class="mb-0 row">
                <dt class="d-inline-block col-md-6">SEX</dt>
                <dd class="d-inline-block col-md-6 sex"><?=$patient["sex"]?></dd>
                </dl>
                <dl class="mb-0 row">
                <dt class="d-inline-block col-md-6">WEIGHT</dt>
                <dd class="d-inline-block col-md-6 weight"><?=$patient["weight"]?></dd>
                </dl>
                <dl class="mb-0 row">
                <dt class="d-inline-block col-md-6">HEIGHT</dt>
                <dd class="d-inline-block col-md-6 height"><?=$patient["height"]?></dd>
                </dl>
                <dl class="mb-0 row">
                <dt class="d-inline-block col-md-6">BMI</dt>
                <dd class="d-inline-block col-md-6 bmi"><?=$patient["bmi"]?></dd>
                </dl>

                <dl class="mb-0 row">
                <dt class="d-inline-block col-md-6">Demographic</dt>
                <dd class="d-inline-block col-md-6 demographic"><?=$patient["patient_group"]?></dd>
                </dl>
                <dl class="mb-0 row">
                <dt class="d-inline-block col-md-6">BP Cuff Type</dt>
                <dd class="d-inline-block col-md-6 bp_cuff_type">Omron</dd>
                </dl>
                <dl class="mb-0 row">
                <dt class="d-inline-block col-md-6">Planning Pregnancy</dt>
                <dd class="d-inline-block col-md-6 planning_pregnancy"><?=$patient["planning_pregnancy"]?></dd>
                </dl>
                <dl class="mb-0">
                <dt class="d-inline-block">Pharmacy Info</dt>
                <dd class="d-inline-block pharmacy_info">CVS</dd>
                </dl>
            </div>

            <h4 class="pl-3">Recommendations</h4>
            <div class="p-3 patient_data">
                <section class="bp_threshold">
                    <div class="row">
                        <div class="change col-md-6">
                            <h5>Recommended Change</h5>
                            <h6>Lisinopril 20mg - HCTZ 25mg</h6>
                        </div>
                        <div class="accept col-md-6">
                            <form>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="" checked id="defaultCheck1">
                                    <label class="form-check-label" for="defaultCheck1">Accept</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="" id="defaultCheck1">
                                    <label class="form-check-label" for="defaultCheck1">No Change</label>
                                </div>
                                <div class="btns pt-1 pb-3">
                                    <a href='<?=$module->getURL("pages/view_tree.php")?>' class="btn btn-info btn-sm">View/Edit Prescription Tree</a>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="content bg-light p-3 rounded">
                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. </p><p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
                    </div>
                    <div class="btns pt-4 pb-2">
                        <b class="mb-2">* NOTE: check lab test before proceeding</b>
                        <button class="btn btn-info">Send to Pharmacist</button>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function(){
    $(".patient_details .nav-link").click(function(e){
        e.preventDefault();
        var tab = $(this).data("tab");
        $(".patient_details .nav-link").removeClass("active");
        $(this).addClass("active");
        
        $(".panels").hide();
        $("."+tab).show();

        return false;
    });

    $(".recommendation").hide();

});
</script>