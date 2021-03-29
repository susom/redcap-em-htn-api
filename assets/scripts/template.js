
var tree_table_template = `
        <table id="step_table" class="table">
            <thead>
                <tr class="d-flex">
                    <th class="col-sm-3 drug_name border-left border-right p-2">Drug Name</th>
                    <th class="col-sm-9 dose_ladder border-right p-2">Treatment Steps</th>
                </tr>
            </thead>

            <tbody id="med_steps">
            <tr id="plan_summary" class="d-flex">
                <td  class="col-sm-3 drug_name  border-left border-right p-0 pr-1 text-right">Step Summaries:</td>
                <td  class="col-sm-9 dose_ladder border-right p-0">
                    <span class="drug_dose plan_summ float-left text-center align-top p-1 border-right bg-light"><em></em></span>
                    <span class="drug_dose plan_summ float-left text-center align-top p-1 border-right bg-light"><em></em></span>
                    <span class="drug_dose plan_summ float-left text-center align-top p-1 border-right bg-light"><em></em></span>
                    <span class="drug_dose plan_summ float-left text-center align-top p-1 border-right bg-light"><em></em></span>
                    <span class="drug_dose plan_summ float-left text-center align-top p-1 border-right bg-light"><em></em></span>
                    <span class="drug_dose plan_summ float-left text-center align-top p-1 border-right bg-light"><em></em></span>
                    <span class="drug_dose plan_summ float-left text-center align-top p-1 border-right bg-light"><em></em></span>
                    <span class="drug_dose plan_summ float-left text-center align-top p-1 border-right bg-light"><em></em></span>
                    <span class="drug_dose plan_summ float-left text-center align-top p-1 border-right bg-light"><em></em></span>
                    <span class="drug_dose plan_summ float-left text-center align-top p-1 border-right bg-light"><em></em></span>
                </td>
            </tr>

            <tr id="dose_head" class="d-flex">
                <td class="col-sm-3 drug_name border-left border-right p-0 pr-1 text-right">Evaluate in:</td>
                <td class="col-sm-9 dose_ladder border-right p-0 ">
                    <span class="drug_dose float-left text-center align-top p-1 border-right bg-light"><input type="number" class="d-inline border text-right rounded p-0" value="7"> <i class="d-inline">days</i></span>
                    <span class="drug_dose float-left text-center align-top p-1 border-right bg-light"><input type="number" class="d-inline border text-right rounded p-0" value="7"> <i class="d-inline">days</i></span>
                    <span class="drug_dose float-left text-center align-top p-1 border-right bg-light"><input type="number" class="d-inline border text-right rounded p-0" value="7"> <i class="d-inline">days</i></span>
                    <span class="drug_dose float-left text-center align-top p-1 border-right bg-light"><input type="number" class="d-inline border text-right rounded p-0" value="7"> <i class="d-inline">days</i></span>
                    <span class="drug_dose float-left text-center align-top p-1 border-right bg-light"><input type="number" class="d-inline border text-right rounded p-0" value="7"> <i class="d-inline">days</i></span>
                    <span class="drug_dose float-left text-center align-top p-1 border-right bg-light"><input type="number" class="d-inline border text-right rounded p-0" value="7"> <i class="d-inline">days</i></span>
                    <span class="drug_dose float-left text-center align-top p-1 border-right bg-light"><input type="number" class="d-inline border text-right rounded p-0" value="7"> <i class="d-inline">days</i></span>
                    <span class="drug_dose float-left text-center align-top p-1 border-right bg-light"><input type="number" class="d-inline border text-right rounded p-0" value="7"> <i class="d-inline">days</i></span>
                    <span class="drug_dose float-left text-center align-top p-1 border-right bg-light"><input type="number" class="d-inline border text-right rounded p-0" value="7"> <i class="d-inline">days</i></span>
                    <span class="drug_dose float-left text-center align-top p-1 border-right bg-light"><input type="number" class="d-inline border text-right rounded p-0" value="7"> <i class="d-inline">days</i></span>
                </td>
            </tr>

            <tr id="tree_rows_above" >
                <td class="text-center text-muted py-5" colspan="2"><em>No Medications in this Tree yet click '+ Add Medication' to begin building a new tree</em></td>
            </tr>

            <tr id="tfoot" class="d-flex">
                <td  class="col-sm-3 drug_name border p-2"><a href="#" class='font-weight-normal d-inline-block ml-5' id="add_med">+ Add Medication</a></td>
                <td  class="col-sm-9 dose_ladder border-right border-bottom p-2 text-right">
                    <button id="new_tree" class="btn btn-danger">New Tree</button>
                    <button id="save_edit" class="btn btn-primary">Save Edit</button>
                    <span class="mx-3">Or</span>
                    <input type="text" class="form-control-sm d-inline-block" id="new_name" />
                    <button id="save_as_new" class="btn btn-info">Save As a New Tree</button>
                </td>
            </tr>
            </tbody>
        </table>
      `;

var tree_medrow_template = `
      <tr class="drug_step d-flex template">
          <td class="col-sm-3 drug_name border-left border-right p-2 text-left">
              <div class="form-group main_drug mb-2">
                  <a href="#" class="trashit border rounded align-middle text-danger text-center">&times;</a>
                  <select class="med_name bg-info text-white form-control-sm mb-1" name='med_name[]'>
                      <option value="0">Choose a Drug</option>
                      <optgroup label="Diuretics">
                          <option value="HCTZ" data-unit="mg" data-dosage="[12.5,25,50]" data-note="most effective when combined with ACEI">HCTZ</option>
                          <option value="Chlorthalidone" data-unit="mg" data-dosage="[12.5,25]" data-note="most effective when combined with ACEI">Chlorthalidone</option>
                          <option value="Indapamide" data-unit="mg" data-dosage="[1.25,2.5]" data-note="most effective when combined with ACEI">Indapamide</option>
                          <option value="Triamterene" data-unit="mg" data-dosage="[25,50]" data-note="most effective when combined with ACEI">Triamterene</option>
                          <option value="K+ sparing-spironolactone" data-unit="mg" data-dosage="[25,50]" data-note="most effective when combined with ACEI">K+ sparing-spironolactone</option>
                          <option value="Amiloride" data-unit="mg" data-dosage="[5,10]" data-note="most effective when combined with ACEI">Amiloride</option>
                          <option value="Furosemide" data-unit="mg" data-dosage="[20,40,80]" data-freq="2" data-note="most effective when combined with ACEI">Furosemide</option>
                          <option value="Torsemide" data-unit="mg" data-dosage="[10,20,40]" data-note="most effective when combined with ACEI">Torsemide</option>
                      </optgroup>
      
                      <optgroup label="ACEI - ACE Inhibtor">
                          <option value="Lisinopril" data-unit="mg" data-dosage="[10,20,30,40]">Lisinopril</option>
                          <option value="Benazaril" data-unit="mg" data-dosage="[5,10,20,40]">Benazaril</option>
                          <option value="Fosinopril" data-unit="mg" data-dosage="[10,20,40]">Fosinopril</option>
                          <option value="Quinapril" data-unit="mg" data-dosage="[5,10,20,40]">Quinapril</option>
                          <option value="Ramipril" data-unit="mg" data-dosage="[1.25,2.5,5,10]">Ramipril</option>
                          <option value="Trandolapril" data-unit="mg" data-dosage="[1,2,4]">Trandolapril</option>
                      </optgroup>
      
                      <optgroup label="ARB (Angiotensin receptor blocker)">
                          <option value="Candesartan" data-unit="mg" data-dosage="[8,16,32]" data-note="may prevent migraine headaches">Candesartan</option>
                          <option value="Valsartan" data-unit="mg" data-dosage="[40,80,160,320]">Valsartan</option>
                          <option value="Iosartan" data-unit="mg" data-dosage="[50,100]">Iosartan</option>
                          <option value="Olmesartan" data-unit="mg" data-dosage="[20,40]">Olmesartan</option>
                          <option value="Telmisartan" data-unit="mg" data-dosage="[20,40,80]">Telmisartan</option>
                      </optgroup>
      
                      <optgroup label="Calcium Channel Blockers (CCB)">
                          <option value="Nifedipine ER" data-unit="mg" data-dosage="[30,60,90]">Nifedipine ER</option>
                          <option value="Diltiazem ER" data-unit="mg" data-dosage="[180,240,300,360]">Diltiazem ER</option>
                          <option value="Amlodipine" data-unit="mg" data-dosage="[2.5,5,10]">Amlodipine</option>
                          <option value="Verapamil" data-unit="mg" data-dosage="[80,120]" data-freq="3">Verapamil</option>
                          <option value="Verapamil ER" data-unit="mg" data-dosage="[240,480]" >Verapamil ER</option>
                      </optgroup>
      
                      <optgroup label="Beta-Blockers">
                          <option value="Metroprolol succinate" data-unit="mg" data-dosage="[1,2,3]">Metroprolol succinate</option>
                          <option value="Tartrate" data-unit="mg" data-dosage="[50,100]" data-freq="2">Tartrate</option>
                          <option value="Propranolol" data-unit="mg" data-dosage="[40,80,120]" data-freq="2">Propranolol</option>
                          <option value="Carvedilol" data-unit="mg" data-dosage="[6.25,12.5,25]" data-freq="2">Carvedilol</option>
                          <option value="Bisoprolol" data-unit="mg" data-dosage="[5,10]">Bisoprolol</option>
                          <option value="Labetalol" data-unit="mg" data-dosage="[100,200,300]" data-freq="2">Labetalol</option>
                          <option value="Nebivolol" data-unit="mg" data-dosage="[5,10]">Nebivolol</option>
                      </optgroup>
      
                      <optgroup label="Vasodilators">
                          <option value="Hydralazine" data-unit="mg" data-dosage="[25,50,100]" data-freq="2">Hydralazine</option>
                          <option value="Minoxidil" data-unit="mg" data-dosage="[5,10]">Minoxidil</option>
                          <option value="Terazosin" data-unit="mg" data-dosage="[1,2,5]">Terazosin</option>
                          <option value="Doxazosin" data-unit="mg" data-dosage="[1,2,4]" data-note="at bedtime">Doxazosin</option>
                      </optgroup>
      
                      <optgroup label="Centrally-acting">
                          <option value="Clonidine"  data-unit="mg" data-dosage="[0.1,0.2]" data-freq="2">Clonidine</option>
                          <option value="Methyldopa" data-unit="mg" data-dosage="[250,500]" data-freq="2">Methyldopa</option>
                          <option value="Guanfacine" data-unit="mg" data-dosage="[1,2,3]">Guanfacine</option>
                      </optgroup>
                  </select>
                  <textarea class="med_notes form-control-sm p-1" ame="med_notes[]" placeholder="Notes:"></textarea>
              </div>
      
              <div class="form-group side_effect mb-0">
                  <select class="alt_med_name bg-secondary text-white form-control-sm mb-1" name='alt_med_name[]'>
                      <option value="0">Alternate Drug (side effects)</option>
                      <optgroup label="Diuretics">
                          <option value="HCTZ" data-unit="mg" data-dosage="[12.5,25,50]" data-note="most effective when combined with ACEI">HCTZ</option>
                          <option value="Chlorthalidone" data-unit="mg" data-dosage="[12.5,25]" data-note="most effective when combined with ACEI">Chlorthalidone</option>
                          <option value="Indapamide" data-unit="mg" data-dosage="[1.25,2.5]" data-note="most effective when combined with ACEI">Indapamide</option>
                          <option value="Triamterene" data-unit="mg" data-dosage="[25,50]" data-note="most effective when combined with ACEI">Triamterene</option>
                          <option value="K+ sparing-spironolactone" data-unit="mg" data-dosage="[25,50]" data-note="most effective when combined with ACEI">K+ sparing-spironolactone</option>
                          <option value="Amiloride" data-unit="mg" data-dosage="[5,10]" data-note="most effective when combined with ACEI">Amiloride</option>
                          <option value="Furosemide" data-unit="mg" data-dosage="[20,40,80]" data-freq="2" data-note="most effective when combined with ACEI">Furosemide</option>
                          <option value="Torsemide" data-unit="mg" data-dosage="[10,20,40]" data-note="most effective when combined with ACEI">Torsemide</option>
                      </optgroup>
      
                      <optgroup label="ACEI - ACE Inhibtor">
                          <option value="Lisinopril" data-unit="mg" data-dosage="[10,20,30,40]">Lisinopril</option>
                          <option value="Benazaril" data-unit="mg" data-dosage="[5,10,20,40]">Benazaril</option>
                          <option value="Fosinopril" data-unit="mg" data-dosage="[10,20,40]">Fosinopril</option>
                          <option value="Quinapril" data-unit="mg" data-dosage="[5,10,20,40]">Quinapril</option>
                          <option value="Ramipril" data-unit="mg" data-dosage="[1.25,2.5,5,10]">Ramipril</option>
                          <option value="Trandolapril" data-unit="mg" data-dosage="[1,2,4]">Trandolapril</option>
                      </optgroup>
      
                      <optgroup label="ARB (Angiotensin receptor blocker)">
                          <option value="Candesartan" data-unit="mg" data-dosage="[8,16,32]" data-note="may prevent migraine headaches">Candesartan</option>
                          <option value="Valsartan" data-unit="mg" data-dosage="[40,80,160,320]">Valsartan</option>
                          <option value="Iosartan" data-unit="mg" data-dosage="[50,100]">Iosartan</option>
                          <option value="Olmesartan" data-unit="mg" data-dosage="[20,40]">Olmesartan</option>
                          <option value="Telmisartan" data-unit="mg" data-dosage="[20,40,80]">Telmisartan</option>
                      </optgroup>
      
                      <optgroup label="Calcium Channel Blockers (CCB)">
                          <option value="Nifedipine ER" data-unit="mg" data-dosage="[30,60,90]">Nifedipine ER</option>
                          <option value="Diltiazem ER" data-unit="mg" data-dosage="[180,240,300,360]">Diltiazem ER</option>
                          <option value="Amlodipine" data-unit="mg" data-dosage="[2.5,5,10]">Amlodipine</option>
                          <option value="Verapamil" data-unit="mg" data-dosage="[80,120]" data-freq="3">Verapamil</option>
                          <option value="Verapamil ER" data-unit="mg" data-dosage="[240,480]" >Verapamil ER</option>
                      </optgroup>
      
                      <optgroup label="Beta-Blockers">
                          <option value="Metroprolol succinate" data-unit="mg" data-dosage="[1,2,3]">Metroprolol succinate</option>
                          <option value="Tartrate" data-unit="mg" data-dosage="[50,100]" data-freq="2">Tartrate</option>
                          <option value="Propranolol" data-unit="mg" data-dosage="[40,80,120]" data-freq="2">Propranolol</option>
                          <option value="Carvedilol" data-unit="mg" data-dosage="[6.25,12.5,25]" data-freq="2">Carvedilol</option>
                          <option value="Bisoprolol" data-unit="mg" data-dosage="[5,10]">Bisoprolol</option>
                          <option value="Labetalol" data-unit="mg" data-dosage="[100,200,300]" data-freq="2">Labetalol</option>
                          <option value="Nebivolol" data-unit="mg" data-dosage="[5,10]">Nebivolol</option>
                      </optgroup>
      
                      <optgroup label="Vasodilators">
                          <option value="Hydralazine" data-unit="mg" data-dosage="[25,50,100]" data-freq="2">Hydralazine</option>
                          <option value="Minoxidil" data-unit="mg" data-dosage="[5,10]">Minoxidil</option>
                          <option value="Terazosin" data-unit="mg" data-dosage="[1,2,5]">Terazosin</option>
                          <option value="Doxazosin" data-unit="mg" data-dosage="[1,2,4]" data-note="at bedtime">Doxazosin</option>
                      </optgroup>
      
                      <optgroup label="Centrally-acting">
                          <option value="Clonidine"  data-unit="mg" data-dosage="[.1,.2]" data-freq="2">Clonidine</option>
                          <option value="Methyldopa" data-unit="mg" data-dosage="[250,500]" data-freq="2">Methyldopa</option>
                          <option value="Guanfacine" data-unit="mg" data-dosage="[1,2,3]">Guanfacine</option>
                      </optgroup>
                  </select>
                  <input type="text" class="alt_med_custom_dose form-control-sm p-1" name="alt_med_custom_dose[]" placeholder="Alternate Dosage"/>
              </div>
          </td>
          <td class="col-sm-9 dose_ladder border-right p-0">
            <!--  dose_ladder , med step goes here  -->
          </td>
      </tr>
      `;

var tree_medstep_template = `
      <span class="drug_dose float-left text-center align-top p-1 border-right bg-light h-100">
            <div class="dose_actions mx-auto my-1 d-block">
                <a href="#" title-="New Dosage" data-action="new_dose" class="new_dose text-sucess d-inline-block align-middle border rounded">&#43;</a>
                <a href="#" title="Duplicate Previous Dosage" data-action="dupe_prev_dose" class="dupe_prev_dose text-primary d-inline-block align-middle border rounded">&#8635;</a>
                <a href="#" title="Remove Dosage" data-action="remove_dose" class="remove_dose text-danger d-inline-block align-middle border rounded">&times;</a>
            </div>
            
            <div class="dose_selects text-center">
                <select class="dose_select mx-auto my-0 form-control-sm">
                    <option class="custom" value="Dosage">Custom Dosage</option>
                </select>
                
                <span class='d-block'>or</span>
                <input type="name" class="custom_dose form-control-sm" name="custom_dose" placeholder="Custom Dosage"/>
                <select class="freq_select mx-auto my-0 form-control-sm">
                    <option value="1">1x daily</option>
                    <option value="2">2x daily</option>
                    <option value="3">3x daily</option>
                </select>
            </div>
      </span>
      `;

var treeview_step_template = `
      <div class="tree_step">
            <ul class="step_drugs">
                
            </ul>
            <div class="branches">
                <b class="eval">14 days</b>
                <span class="nc">BP Not Controlled</span>
                <span class="controlled">Controlled</span>
                <span class="se">Side Effect</span>
            </div>
            <div class="next_steps">
                <b class="continue">Continue</b>
                <ul class="step_drugs se">
                    
                </ul>
            </div>
      </div>
`;

var tree_step_template = `
    <div class="tree_step">
        <h4><button class="btn btn-light btn-sm showhide"><span>+</span></button><span><i>Current</i> Step</span><div class="continue"><button class="btn btn-info btn-sm accept">View Rec</button><button class="btn btn-info btn-sm reject">Close</button></div></h4>

        <div class="box">
            <div class="drugs">
                <div class="label"><span>Meds</span><em class="eval">14 Days</em></div>
                <ul class="step_drugs">
                </ul>
            </div>

            <div class="actions">
                <div class="label"><span>Actions</span></div>
                <div class="inputs"><label>BP Status:</label><strong><select class="bp_status"><option value=999>Choose to Preview</option></select></strong></div>
                <div class="inputs"><label>Side Effects:</label><strong><select class="side_effects"><option value=999>Choose to Preview</option></select></strong></div>
            </div>
        </div>
    </div>
`;

var tree_step_msg = `
    <div class="tree_step preview">
        <h4><span>Notice</span> <div class="continue"><button class="btn btn-info btn-sm accept">Accept & Continue</button><button class="btn btn-info btn-sm reject">Close</button></div></h4>

        <div class="box">
            <div class="drugs">
                <div class="label"><span>Message</span></div>
                <p class='msg'>Continue current step</p>
            </div>
        </div>
    </div>  
`;

var branch_container = `
    <div class="branch">
        
    </div>
`;

var rec_modal = `
    <div id="recommendation">
        <div class="rec_change">
            <h3>Step Change</h3>
            <h4></h4>
        </div>
        <div class="natural_text">
        </div>
        <textarea id="provider_comment" placeholder="Provider Comments"></textarea>
        <div class="continue">
            <button class="btn btn-primary btn-sm accept">Accept Step & Continue</button>
            <button class="btn btn-danger btn-sm reject">Close</button>
        </div>
    </div>
    <div class="underlay"></div>
`;

var email_modal = `
    <div id="emailconsent">
        <form> 
            <input type="hidden" id="consent_patient_id" name="patient_id"/>
            <input type="hidden" id="consent_url" name="consent_url"/>

            <div class="consent_modal">
                <h3>Email Consent</h3>
                <div class="url">Consent URL: <b></b></div>
                <div class="id">For Patient ID: <b></b></div>
                <input type="text" id="consent_email" class="col-sm-12 my-3" placeholder="Consent Email"/>
            </div>
            <div class="continue">
                <button class="btn btn-danger btn-sm cancel">Cancel</button>
                <button class="btn btn-primary btn-sm send">Send Email</button>
            </div>
        </form>
    </div>
    <div class="underlay"></div>
`;

var providers_modal = `
    <div id="selectprovider">
        <form> 
            <div class="consent_modal">
                <h3>Select Provider for this Patient</h3>
                <em>*as a Super Delegate, you may add patients for any provider</em>
                <select id="provideroptions" class="col-sm-12 p-1 my-3">

                </select>
            </div>
            <div class="continue">
                <button class="btn btn-danger btn-sm cancel">Cancel</button>
                <button class="btn btn-primary btn-sm send">Add Patient</button>
            </div>
        </form>
    </div>
    <div class="underlay"></div>
`;

var overview_filter = `
    <div class="d-inline-block text-center stat-group">
        <a href="#" class="stat d-inline-block">
            <p class='h1 mt-4 mb-0 p-0'></p>
            <i class="d-block"></i>
        </a>

        <div class="stat-body mt-3">
            <b class="stat-title d-block"></b>
            <i class="stat-text d-block"></i>
        </div>
    </div>
`;

var alerts_row = `
    <tr>
        <td class="check" data-label="select"><input name="delete_alert" type="checkbox"></td>
        <td class="patient_id" data-label="patient_id"><a href="#" class="text-infolink">RE: Patient <span></span></a></td>
        <td class="patient_name" data-label="patient_name"><a href="#" class="text-infolink">RE: Patient <span></span></a></td>
        <td class="consent_url" data-label="sent"><a href="#" class="text-infolink"><span></span></a></td>
        <td class="consent_sent" data-label="date"><a href="#" class="text-infolink"><span></span></a></td>
        <td class="consent_ts" data-label="date"><a href="#" class="text-infolink"><span></span></a></td>
    </tr>
`;  

var patient_nav = `
    <dl class="patient_tab rounded bg-light">
        <dt class='d-inline-block pt-2 pb-2 pl-2 text-center'><img class="rounded-circle"/></dt>
        <dd class='d-inline-block align-middle'>
            <b></b>
            <i class="d-block"></i>
        </dd>
    </dl>
`;

var patient_details = `
    <ul class="nav nav-tabs border-0">
        <li class="nav-item rounded-top">
            <a class="nav-link profile_tab" href="#" data-tab="profile">Profile</a>
        </li>
        <li class="nav-item rounded-right">
            <a class="nav-link recommendation_tab" href="#" data-tab="recommendation">Recommendations</a>
        </li>
        <li class="nav-item clear_patient">
            <a class="" href="#">Clear Patient Data</a>
        </li>
    </ul>
    <div class="bg-light rounded-right rounded-bottom rounded-left p-3 profile panels row">
        <div class="patient_detail col-md-12">
            <div class="patient_name mb-5 pt-5">
                <div class="patient_status float-left"></div>
                <fig class="patient_profile d-block text-center mx-auto">
                    <figure><img src='' class="rounded-circle"/></figure>
                    <figcaption class='h1'></figcaption>
                    <figcaption class="contact"></figcaption>
                </fig>
                <div class="float-right">
                    <a href="#" class="edit_patient ">Edit</a>
                    <a href="#" class="delete_patient "></a>
                </div>
                
            </div>

            <div class="patient_details row mb-5">
                <div class="col-md-5 ">
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
                    <dl class="mb-2">
                    <dt class="d-inline-block">CKD</dt>
                    <dd class="d-inline-block ckd"></dd>
                    </dl>
                </div>
                <div class="col-md-7">
                    <dl class="mb-2">
                    <dt class="d-inline-block">Demographic</dt>
                    <dd class="d-inline-block demographic"><?=$patient["patient_group"]?></dd>
                    </dl>
                    <dl class="mb-2">
                    <dt class="d-inline-block">Comorbidity</dt>
                    <dd class="d-inline-block comorbidity"><?=$patient["comorbidity"]?></dd>
                    </dl>
                    <dl class="mb-2">
                    <dt class="d-inline-block">CR <i>mg/dl</i></dt>
                    <dd class="d-inline-block cr"><span><input class="form-control input-sm cr_reading" type="text"></span> <i>last updated: <span>n/a</span></i></dd>
                    </dl>
                    <dl class="mb-2">
                    <dt class="d-inline-block">K <i>mmo/l</i></dt>
                    <dd class="d-inline-block k"><span><input class="form-control input-sm k_reading" type="text"></span> <i>last updated: <span>n/a</span></i></dd>
                    </dl>
                    <dl class="mb-2">
                    <dt class="d-inline-block">Calculated EGFR</dt>
                    <dd class="d-inline-block egfr">108</dd>
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
            <section class="bp_threshold">
                <h3 class="text-center p-3">BP Threshold</h3>
                <div class="content bg-light mh-10 mx-1 py-3 row">
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
                        <dd class="font-weight-light mb-0 pulse_goal">Pulse : <span>< 50</span></dd>

                        <dt class="mt-3"><br></dt>
                        <dd class="font-weight-light mb-0">Average Data : <select class="avg_data_range"><option selected>Daily</option><option >Weekly</option><option >AM</option><option >PM</option></select></dd>
                        <dd class="font-weight-light mb-0 define_controlled">Define Uncontrolled : <span>60%</span></dd>
                    </dl>
                </div>
            </section>

            <section class="presription_tree">
                <h3 class="text-center p-3">Medication Log <em>Current Tree : <b></b></em></h3>
                <div class="content bg-light mh-10 mx-1">
                    
                </div>
            </section>

            <section class="data">
                <h3 class="text-center p-3">Data</h3>
                <div class="content rounded-bottom bg-light mh-10 mx-1">
                    <div id="bpchart"></div>
                    <div class="instep" style="text-align: center; padding: 0px 20px 20px;">
                        <button id="run_bp_eval" class="btn btn-info btn-large">Manually Execute BP Data Eval</button>
                    </div>
                </div>
            </section>

            <section class="recs_log">
                <h3 class="text-center p-3">Recommendations Log</h3>
                <div class="content bg-light p-3 pb-5 rounded">
                    
                </div>
            </section>
        </div>
    </div>
    <div class="bg-light rounded-right rounded-bottom rounded-left p-3 recommendation panels row">
        <div class="patient_detail mb-5 col-md-3">
            <div class="patient_name mb-4 pt-5">
                <fig class="patient_profile d-block text-center mx-auto">
                    <figure><img src='' class="rounded-circle"/></figure>
                    <figcaption class='h1'></figcaption>
                </fig>
            </div>
        </div>
        <div class="patient_summary col-md-9 mt-5 border-left">
            <h4 class="pl-3">Patient Summary</h4>
            <div class="pl-3 mb-5">
                <dl class="mb-0 row">
                <dt class="d-inline-block col-md-6">DOB</dt>
                <dd class="d-inline-block col-md-6 dob"></dd>
                </dl>
                <dl class="mb-0 row">
                <dt class="d-inline-block col-md-6">AGE</dt>
                <dd class="d-inline-block col-md-6 age"></dd>
                </dl>
                <dl class="mb-0 row">
                <dt class="d-inline-block col-md-6">SEX</dt>
                <dd class="d-inline-block col-md-6 sex"></dd>
                </dl>
                <dl class="mb-0 row">
                <dt class="d-inline-block col-md-6">WEIGHT</dt>
                <dd class="d-inline-block col-md-6 weight"></dd>
                </dl>
                <dl class="mb-0 row">
                <dt class="d-inline-block col-md-6">HEIGHT</dt>
                <dd class="d-inline-block col-md-6 height"></dd>
                </dl>
                <dl class="mb-0 row">
                <dt class="d-inline-block col-md-6">BMI</dt>
                <dd class="d-inline-block col-md-6 bmi"></dd>
                </dl>

                <dl class="mb-0 row">
                <dt class="d-inline-block col-md-6">Demographic</dt>
                <dd class="d-inline-block col-md-6 demographic"></dd>
                </dl>

                <dl class="mb-0 row">
                <dt class="d-inline-block col-md-6">Planning Pregnancy</dt>
                <dd class="d-inline-block col-md-6 planning_pregnancy"></dd>
                </dl>
                <dl class="mb-0 row">
                <dt class="d-inline-block col-md-6">Pharmacy Info</dt>
                <dd class="d-inline-block col-md-6 pharmacy_info"></dd>
                </dl>
            </div>

            <h4 class="pl-3">Recommendations</h4>
            <div id="recommendations">
                <p class="p-3"><i>No current recommendations</i></p>
            </div>
        </div>
    </div>
`;

var rec_log_step = `
    <div class="rec_log row col-sm-10 offset-sm-1 px-0 mt-3 border rounded">
        <div class="col-sm-8 border-right">
            <h4 class="pt-2">Notes</h4>
            <div class="rec_summaries">
            
            </div>
        </div>
        <div class="col-sm-4 pr-0 row">
            <div class="col-sm-12 border-bottom rec_status">
                <h5>Status</h5>
                <b></b>
            </div>
            <div class="col-sm-12 border-bottom rec_action">
                <h5>Action</h5>
                <b></b>
            </div>
            <div class="col-sm-12 rec_ts">
                <h5>Date Generated</h5>
                <b></b>
            </div>
        </div>
    </div>
`;

var tree_log_step = `
    <div class="step">
        <div class="instep">
            <ul>
                <li class="ts"><h5></h5><em></em></li>
                <li class="meds">
                    <div><em>Meds</em><h6></h6></div>
                    <div class="intolerances"><em>Intolerances : </em><span></span></div>
                </li>
                <li class="note">Note</li>
                <p class="comment"><b>Notes:</b> <span></span></p>
            </ul>
        </div>
    </div>
`;

var recommendation = `
    <div class="p-3 patient_data">
    <section class="bp_threshold">
        <div class="row">
            <div class="change col-md-6">
                <h5>Recommended Change</h5>
                <h6>Lisinopril 20mg - HCTZ 25mg</h6>
            </div>
            <div class="accept col-md-6">
                <form>
                    <div class="btns pt-1 pb-3">
                        <a href='' class="btn btn-info btn-sm view_edit_tree">View/Edit Prescription Tree</a>
                    </div>
                </form>
            </div>
        </div>
        <div class="summaries content bg-light p-3 rounded">
        </div>
        <textarea id="provider_comment" placeholder="Provider Comments"></textarea>
        <div class="send_to_pharmacy btns pt-4 pb-2">
            <button class="btn btn-danger decline_rec">Decline</button>
            <button class="btn btn-info send_and_accept">Accept Recommendation</button>
        </div>
    </section>
    </div>
`;


var delegate = `
    <div class="form-group ">
        <div class="row">
            <input type="text" class="form-control col-md-8 ml-3" name='delegates[]'> <button class='btn btn-info btn-sm col-md-2 ml-3 remove_delegate'>delete</button>
        </div>
    </div>
`;


var tree_dosage = `
    <div class="input-group col-sm-3">
        <input type="text" class="form-control" name="" value=""/>
        <div class="input-group-append">
            <span class="input-group-text add_on"></span>
        </div>
    </div>`;