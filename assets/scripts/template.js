
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
            <h3>Recommended Change</h3>
            <h4></h4>
        </div>
        <div class="natural_text">
        </div>
        <div class="continue">
            <button class="btn btn-primary btn-sm accept">Accept Step & Continue</button>
            <button class="btn btn-danger btn-sm reject">Close</button>
        </div>
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
        <td class="patient" data-label="subject"><a href="#" class="text-infolink">RE: Patient <span></span></a></td>
        <td class="subject" data-label="message"><a href="#" class="text-infolink"><span></span></a></td>
        <td class="date" data-label="date"><a href="#" class="text-infolink"><span></span></a></td>
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