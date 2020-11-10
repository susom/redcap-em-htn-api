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
                    <figcaption class="my-4"><input type="text" name='patient_fname' placeholder="First Name"/> <input type="text" class="mx-4" name='patient_mname' placeholder="Middle Name"/> <input type="text" name='patient_lname' placeholder="Last Name"/></figcaption>
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
                    <dt class="d-inline-block">BP Cuff Type</dt>
                    <dd class="d-inline-block"><select><option>Omron</option></select></dd>
                    </dl>
                    <dl class="mb-4">
                    <dt class="d-inline-block align-top">Planning Pregnancy</dt>
                    <dd class="d-inline-block align-top">
                        <label class='font-weight-light mb-0 d-block'><input type="radio" class='form-radio-input patient_group align-baseline' name='planning_pregnancy' value="1"/> Yes</label>
                        <label class='font-weight-light d-block'><input type="radio" class='form-radio-input patient_group align-baseline' name='planning_pregnancy' value="0"/> No</label>
                    </dd>
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
