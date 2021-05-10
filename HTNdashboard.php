<?php
namespace Stanford\HTNapi;

// NOTE That for this to work in shibboleth, you must add a define("NOAUTH",true) to the main index.php before redcap connect.
class HTNdashboard {
    const PEPPER    = "project-pepper";
    const EM_MODE   = "em-mode";
    private   $salt
            , $pepper
            , $enabledProjects
            , $patients_project
            , $tree_templates_project
            , $meds_project
            , $providers_project
            , $module;

    public function __construct($module) {
        // This is how to access the parent EM class in these other Classes
        $this->module = $module;

        // Fill some class vars
        $this->pepper       = $this->module->getProjectSetting(self::PEPPER);
        $enabledProjects    = $this->module->showEnabledProjects();
        foreach($enabledProjects as $project){
            $pid            = $project["pid"];
            $project_mode   = $project["mode"];
            switch($project_mode){
                case "patients":
                    $this->patients_project = $pid;
                break;

                case "tree_templates":
                    $this->tree_templates_project = $pid;
                break;

                case "meds":
                    $this->meds_project = $pid;
                break;

                case "providers":
                    $this->providers_project = $pid;
                break;
            }
        }
    }

    public function getAllProviders(){
        $filter	= "[sponsor_id] = ''";
        $fields	= array("record_id", "provider_fname", "provider_mname", "provider_lname");
		$params	= array(
            'project_id'    => $this->providers_project,
			'return_format' => 'json',
			'fields'        => $fields,
			'filterLogic'   => $filter 
		);
		$q 			        = \REDCap::getData($params);
        $providers          = json_decode($q, true);
        $this->module->emDebug("haha", $providers);   
        return $providers;
    }
    public function getAllPatients($provider_id, $super_delegate=null){
        // $this->getProviderbyId($provider_id)

        //NEED TO KEEP THE PEOPLE THAT HAVE BEEN SENT CONSENT, CONSENTED BUT NOT ADDED MRN SEPERATE FROM COMPLETED
        $patients           = array();
        $pending_patients   = array();
        $rx_change          = array();
        $labs_needed        = array();
        $data_needed        = array();
        $messages           = array();

        //FOR CONSENTED PATIENTS WITH MRN ADDED
        $filter	= "[patient_physician_id] = '$provider_id' && ([patient_remove_flag] = '' || [patient_remove_flag] = 0) && ([patient_mrn] != '')";
        if($super_delegate){
            $filter	= "([patient_remove_flag] = '' || [patient_remove_flag] = 0) && ([patient_mrn] != '')";
        }
        $fields	= array("record_id", "patient_physician_id", "patient_fname", "patient_lname" , "patient_email", "patient_birthday", "sex", "patient_photo", "filter");
		$params	= array(
            'project_id'    => $this->patients_project,
			'return_format' => 'json',
			'fields'        => $fields,
			'filterLogic'   => $filter 
		);
		$q 			        = \REDCap::getData($params);
        $patient_results    = json_decode($q, true);
        
        foreach($patient_results as $i => $result){
            //FIRST DEFAULT VALUES THEN FILL IN
            $result["patient_photo"]    = $this->module->getUrl('assets/images/icon_anon.gif', true, true);
            $result["patient_name"]     = "name n/a";
            $result["age"]              = "age n/a";
            $result["sex"]              = empty($result["sex"]) ? "sex n/a" : $result["sex"];

            if(!empty($result["patient_birthday"])){
                $date2          = Date("Y-m-d");
                $diff           = abs(strtotime($date2)-strtotime($result["patient_birthday"]));
                $years          = floor($diff / (365*60*60*24));
                $result["age"]  = "$years yrs old";
            }
            if(!empty($result["patient_email"]) ){
                $result["patient_name"]     = $result["patient_email"] ;
            }
            if(!empty($result["patient_lname"]) && !empty($result["patient_fname"])){
                $result["patient_name"]     = $result["patient_lname"] . ", " . $result["patient_fname"] . " " . substr($result["patient_mname"],0,1);
            }

            if(!empty($result["redcap_repeat_instrument"])){
                if($result["message_read"]){
                    // skip read messages
                    continue;
                }

                $message_ts = strtotime($result["message_ts"]);
                if(!array_key_exists($message_ts,$messages)){
                    $messages[$message_ts] = array();
                }
                $messages[$message_ts][] = array("record_id" => $result["record_id"], "redcap_repeat_instance" => $result["redcap_repeat_instance"], "subject" => $result["patient_message"], "date" => date("M j", $message_ts) );
                continue;
            }


            $bp_filter  = "[omron_bp_id] != '' AND [bp_reading_ts] > '" . date("Y-m-d H:i:s", strtotime('-1 weeks')) . "'";
            $bp_params  = array(
                'project_id'    => $this->patients_project,
                "records"       => array($result["record_id"]),
                "fields"        => array("record_id", "omron_bp_id", "bp_reading_ts" , "bp_systolic", "bp_diastolic", "bp_pulse", "bp_device_type", "bp_units", "bp_pulse_units"),
                'return_format' => 'json',
                'filterLogic'   => $bp_filter
            );
            $bp_raw         = \REDCap::getData($bp_params);
            $bp_results     = json_decode($bp_raw,1);
            $result["bp_readings"] = $bp_results;

            $patients[$result["record_id"]] = $result;

            if($result["filter"] == "rx_change"){
                $rx_change[] = $result["record_id"];
            }
            if($result["filter"] == "labs_needed"){
                $labs_needed[] = $result["record_id"];
            }
            if($result["filter"] == "data_needed"){
                $data_needed[] = $result["record_id"];
            }
        }

        //FOR PENDING PATIENTS THAT BEEN ADDED OR CONSENTED
        $filter	= "[patient_physician_id] = '$provider_id' && ([patient_remove_flag] = '' || [patient_remove_flag] = 0) && [patient_mrn] = ''";
        if($super_delegate){
            $filter	= "([patient_remove_flag] = '' || [patient_remove_flag] = 0) && [patient_mrn] = ''";
        }
        $fields	= array("record_id", "patient_physician_id" ,"paper_icf_v2", "paper_name_v2", "paper_poc_v2" , "participant_print_name_esp_v2", "cooper_icf_eng_subj_v2", "cooper_icf_eng_subj_v3", "patient_email", "consent_sent", "paper_date_v2", "participant_signature_date_esp_v2", "cooper_icf_datetime_v2");
		$params	= array(
            'project_id'    => $this->patients_project,
			'return_format' => 'json',
			'fields'        => $fields,
			'filterLogic'   => $filter 
		);
		$q 			                = \REDCap::getData($params);
        $pending_patients_results 	= json_decode($q, true);

        foreach($pending_patients_results as $pending){
            $temp = array(
                "patient_id"    => $pending["record_id"],
                "consent_email" => $pending["patient_email"],
                "consent_sent"  => ($pending["consent_sent"] ? date("m/d/y" , strtotime($pending["consent_sent"])) : null)
            );

            $paper_consent = $pending["paper_icf_v2"];
            if($paper_consent){
                $patient_name = $pending["paper_name_v2"];
                $consent_date = $pending["paper_date_v2"];
            }else{
                $patient_name = $pending["participant_print_name_esp_v2"];
                $consent_date =$pending["participant_signature_date_esp_v2"];
            }
            
            //5 possible fields for names
            //3 possible fields for consent date.  what the fuck

            $temp["patient_consent_name"]   = $patient_name;
            $temp["consent_date"]           = $consent_date ? date("m/d/y" , strtotime($consent_date)) : null; 
            $temp["consent_url"]            = \REDCap::getSurveyLink($pending["record_id"], 'patient_consent_for_mobile_hypertension_system');
                
            array_push($pending_patients, $temp);
        }

        //SORT MESSAGES DESC
        krsort($messages);
        foreach($messages as $ts => $msgs){
            foreach($msgs as $idx => $msg){
                $fname = $patients[$msg["record_id"]]["patient_fname"];
                $lname = $patients[$msg["record_id"]]["patient_lname"];
                $messages[$ts][$idx]["patient_name"] = $fname . " " . $lname;
            }
        }

        $ui_intf = array(
            "patients"          => $patients,
            "pending_patients"  => $pending_patients,
            "rx_change"         => $rx_change,
            "labs_needed"       => $labs_needed,
            "data_needed"       => $data_needed,
            "messages"          => $messages
        );

        $this->module->emDebug("getAllPatients() ui_intf", $ui_intf["patients"]);
        return $ui_intf;
    }

    public function getPatientDetails($record_id){
        //GET PATIENT BASELINE DATA
        $fields = array("record_id"
                        ,"patient_fname"
                        ,"patient_mname"
                        ,"patient_lname"
                        ,"patient_mrn"
                        ,"patient_phone"
                        ,"patient_photo"
                        ,"patient_birthday"
                        ,"patient_group"
                        ,"patient_bp_target_pulse"
                        ,"patient_bp_target_systolic"
                        ,"patient_bp_target_diastolic"
                        ,"patient_add_ts"
                        ,"patient_physician_id"
                        ,"current_treatment_plan_id"
                        ,"patient_treatment_status"
                        ,"patient_rec_tree_step"
                        ,"filter"
                        ,"last_updatte_ts"

                        ,"sex"
                        ,"weight"
                        ,"height"
                        ,"bmi"
                        ,"planning_pregnancy"
                        ,"ckd"
                        ,"comorbidity"
                        ,"pharmacy_info"
                        ,"patient_email"
                        ,"omron_client_id"
                        ,"omron_auth_request_ts"
                       );
        $params = array(
            'project_id'    => $this->patients_project,
            "records"       => array($record_id),
            "fields"        => $fields,
            "return_format" => 'json'
        );
        $raw    = \REDCap::getData($params);
        $result = json_decode($raw,1);

        if(!empty($result)){
            $result = current($result);

            $result["patient_age"] = "age n/a";
            if(!empty($result["patient_birthday"])){
                $date2          = Date("Y-m-d");
                $diff           = abs(strtotime($date2)-strtotime($result["patient_birthday"]));
                $years          = floor($diff / (365*60*60*24));
                $result["patient_age"]  = "$years yrs old";
            }
            $result["planning_pregnancy"]   = $result["planning_pregnancy"] == "1" ? "Yes" : "No";
            

            
            $result["patient_mrn"]  = empty($result["patient_mrn"]) ? "n/a" : $result["patient_mrn"];
            $result["patient_fname"] = empty($result["patient_fname"]) ? "n/a" : $result["patient_fname"];
            $result["patient_lname"] = empty($result["patient_lname"]) ? "n/a" : $result["patient_lname"];
            $result["patient_email"] = empty($result["patient_email"]) ? "email n/a" : $result["patient_email"];
            $result["patient_phone"] = empty($result["patient_phone"]) ? "phone n/a" : $result["patient_phone"];
            $result["patient_bp_target_pulse"] = empty($result["patient_bp_target_pulse"]) ? "n/a" : $result["patient_bp_target_pulse"];
            $result["patient_bp_target_systolic"]  = empty($result["patient_bp_target_systolic"]) ? "n/a" : $result["patient_bp_target_systolic"];
            $result["patient_bp_target_diastolic"] = empty($result["patient_bp_target_diastolic"]) ? "n/a" : $result["patient_bp_target_diastolic"];
            $result["patient_group"] = empty($result["patient_group"]) ? "n/a" : $result["patient_group"];
            $result["patient_birthday"] = empty($result["patient_birthday"]) ? "dob n/a" : $result["patient_birthday"];
            $result["sex"] = empty($result["sex"]) ? "sex n/a" : $result["sex"];
            $result["weight"] = empty($result["weight"]) ? "weight n/a" : $result["weight"];
            $result["height"] = empty($result["height"]) ? "height n/a" : $result["height"];
            $result["bmi"] = empty($result["bmi"]) ? "BMI n/a" : $result["bmi"];
            $result["ckd"] = empty($result["ckd"]) ? "CKD n/a" : $result["ckd"];
            $result["comorbidity"] = empty($result["comorbidity"]) ? "comorbidity n/a" : $result["comorbidity"];
            $result["pharmacy_info"] = empty($result["pharmacy_info"]) ? "pharmacy n/a" : $result["pharmacy_info"];
            


            //GET PATIENT BP READINGS DATA OVER LAST 2 WEEKS
            $bp_filter  = "[omron_bp_id] != '' AND [bp_reading_ts] > '" . date("Y-m-d H:i:s", strtotime('-20 weeks')) . "'";
            $bp_params  = array(
                'project_id'    => $this->patients_project,
                "records"       => array($result["record_id"]),
                "fields"        => array("record_id", "omron_bp_id", "bp_reading_ts" , "bp_systolic", "bp_diastolic", "bp_pulse", "bp_device_type", "bp_units", "bp_pulse_units"),
                'return_format' => 'json',
                'filterLogic'   => $bp_filter
            );
            $bp_raw         = \REDCap::getData($bp_params);
            $bp_results     = json_decode($bp_raw,1);
            $result["bp_readings"] = $bp_results;

            //GET PATIENT CRK MEASUREMENTS AND LAST UPDATED OVER LAST 6 MONTHS
            //TODO LIMIT TO ONLY LATEST ONES
            $crk_filter  = "([lab_name] = 'cr' OR [lab_name] = 'k') AND [lab_ts] > '" . date("Y-m-d H:i:s", strtotime('-6 months')) . "'";
            $crk_params  = array(
                'project_id'    => $this->patients_project,
                "records"       => array($result["record_id"]),
                "fields"        => array("record_id", "lab_name", "lab_value" , "lab_ts"),
                'return_format' => 'json',
                'filterLogic'   => $crk_filter
            );
            $crk_raw         = \REDCap::getData($crk_params);
            $crk_results     = json_decode($crk_raw,1);
            $result["crk_readings"] = $crk_results;

            //GET PATIENT TREE CHANGE STEPS TAKEN (not including the first step 0)
            $tree_filter  = "[ptree_log_ts] != ''";
            $tree_params  = array(
                'project_id'    => $this->patients_project,
                "records"       => array($result["record_id"]),
                "fields"        => array("record_id", "ptree_log_ts", "ptree_log_prev_step" , "ptree_log_current_step", "ptree_current_meds", "ptree_log_comment"),
                'return_format' => 'json',
                'filterLogic'   => $tree_filter
            );
            $tree_raw         = \REDCap::getData($tree_params);
            $tree_results     = json_decode($tree_raw,1);
            // $this->module->emDebug("tree log", $tree_results);
            $result["tree_log"] = $tree_results;

            //GET PATIENT RECOMMENDATION LOGS WHETHER STEP TAKEN OR NOT
            $tree_filter  = "[rec_ts] != ''";
            $tree_params  = array(
                'project_id'    => $this->patients_project,
                "records"       => array($result["record_id"]),
                "fields"        => array("record_id", "rec_ts", "rec_accepted" , "rec_current_meds", "rec_meds", "rec_status", "rec_mean_systolic"),
                'return_format' => 'json',
                'filterLogic'   => $tree_filter
            );
            $rec_raw            = \REDCap::getData($tree_params);
            $rec_results        = json_decode($rec_raw,1);
            
            $pretty_rec_logs    = array();
            $bp_units           = isset($result["bp_readings"][0]) ? $result["bp_readings"][0]["bp_units"] : "mmHg";
            $target_systolic    = $result["patient_bp_target_systolic"];
            foreach($rec_results as $rec_result){
                array_push($pretty_rec_logs, array(
                    "cur_drugs"    => $rec_result["rec_current_meds"]
                   ,"rec_drugs"    => $rec_result["rec_meds"]
                   ,"bp_units"     => $bp_units 
                   ,"rec_status"   => empty($rec_result["rec_accepted"]) ? "No Change" : "Accepted"
                   ,"rec_action"   => empty($rec_result["rec_accepted"]) ? "None" : "Sent to Pharmacy??"
                   ,"rec_ts"       => $rec_result["rec_ts"]
                   ,"mean_systolic"    => $rec_result["rec_mean_systolic"]
                   ,"target_systolic"  => $target_systolic
                ));
            }
            $result["rec_logs"] = $pretty_rec_logs;
        }
        // $this->module->emDebug("patient_detail", $result);
        return $result;
    }

    public function getSystemSubSettings($key) {
		$keys   = [];
		$config = $this->getSettingConfig($key);
		foreach($config['sub_settings'] as $subSetting){
			$keys[] = $this->prefixSettingKey($subSetting['key']);
		}
		$rawSettings = \ExternalModules\ExternalModules::getSystemSettingsAsArray($this->PREFIX);
		$subSettings = [];
		foreach($keys as $key){
			$values = $rawSettings[$key]['value'];
			for($i=0; $i<count($values); $i++){
				$value = $values[$i];
				$subSettings[$i][$key] = $value;
			}
		}
		return $subSettings;
    }
    
    public function getProjectDictionary($proj_id){
        $dict = \REDCap::getDataDictionary($proj_id, "array");
        return $dict;
    }

    public function loginProvider($em_input, $pw_input, $already_hashed=false){
        $salt       = $em_input;
        $pepper     = $this->pepper;
        $input      = $salt.$pw_input.$pepper;
        $pw_hash    = $this->pwHash($input);
        if($already_hashed){
            $pw_hash = $pw_input;
        }

        $filter     = "[provider_email] = '" . $salt . "' && [verification_ts] <> ''"; //TODO CHHECKK AGAINST PW TOO HAHAHA
        $fields     = array("record_id", "provider_email", "provider_pw", "provider_fname", "provider_mname", "provider_lname", "sponsor_id", "super_delegate");
        $params     = array(
            'project_id'    => $this->providers_project,
            'fields'        => $fields,
            'filterLogic'   => $filter,
            'return_format' => 'json'
        );
        $raw        = \REDCap::getData($params);
        $results    = json_decode($raw,1);

        $errors     = array();
        if(!empty($results)){
            $result         = current($results);
            $db_pw_hash     = $result["provider_pw"];

            if($this->pwVerify($input, $db_pw_hash) || $already_hashed){
                session_start();
                $_SESSION["logged_in_user"] = $result;
                $this->module->emDebug($_SESSION["logged_in_user"]);
                return true;
            }else{
                $this->module->emDebug("not pwverified?");
                return false;
            }
        }else{
            return false;
        }
    }

    public function registerProvider($post){
        $required = array(
             "provider_email"
            ,"provider_pw"
            ,"provider_pw2"
            ,"provider_fname"
            ,"provider_lname"
            ,"provider_profession"
            ,"provider_cell"
        );

        $_POST          = $post;
        $dict           = $this->getProjectDictionary($this->providers_project);
        $dict_keys      = array_keys($dict);

        $provider_email = in_array("provider_email", $dict_keys) ? strtolower(trim(filter_var($_POST["provider_email"], FILTER_SANITIZE_STRING))) : null;
        $provider_pw    = in_array("provider_pw", $dict_keys) ? strtolower(trim(filter_var($_POST["provider_pw"], FILTER_SANITIZE_STRING))) : null;
        $provider_pw2   = in_array("provider_pw", $dict_keys) ? strtolower(trim(filter_var($_POST["provider_pw2"], FILTER_SANITIZE_STRING))) : null;
        $edit_id        = empty($_POST["record_id"]) ? null : $_POST["record_id"];

        $error_str  = "";
        foreach($required as $req_var){
            if(empty($_POST[$req_var])){
                $error_str .= "<li>$req_var is required.</li>";
            }
        }
        if( $provider_pw != $provider_pw2 ){
            $error_str .= "<li>passwords dont match.</li>";
        }
        if($error_str !== ""){
            return array("errors" => "<ul>$error_str</ul>");
        }

        if($edit_id){
            $results = null;
        }else{
            $filter     = "[provider_email] = '" . $provider_email . "'";
            $fields     = array("record_id", "provider_email", "provider_pw");
            $params     = array(
                'project_id'    => $this->providers_project,
                'fields'        => $fields,
                'filterLogic'   => $filter,
                'return_format' => 'json'
            );
            $raw        = \REDCap::getData($params);
            $results    = json_decode($raw,1);
        }
        
        if(!empty($results)){
            $errors = "<ul><li>Username/Email already in system</li></ul>";
            return array("errors" => $errors);
        }else{
            $data = array();
            foreach($dict_keys as $key){
                if(array_key_exists($key, $_POST)){
                    $post_val = trim(filter_var($_POST[$key], FILTER_SANITIZE_STRING));
                    if($key == "provider_pw"){
                        $salt       = $provider_email;
                        $pepper     = $this->pepper;
                        $input      = $salt.$provider_pw.$pepper;
                        $post_val   = $this->pwHash($input);
                    }
                    // MASSAGE FOR CHECKBOX
                    if ($dict[$key]["field_type"] == 'checkbox') {
                        if(strpos($dict[$key]["select_choices_or_calculations"],$post_val.",") > -1){
                            $key        = $key."___".$post_val;
                            $post_val   = 1;
                        }else{
                            //IF CHOICE NOT FOUND, JUST SKIP THIS FIELD , FIX LATER
                            continue;
                        }
                    } 
                    //MASSAGE FOR DATE FIELDS
                    if ($dict[$key]["text_validation_type_or_show_slider_number"] == "date_ymd"){
                        $post_val = Date("Y-m-d", strtotime($post_val));
                    }
                    $data[$key] = $post_val;
                }
            }
            $record_id                  = !empty($edit_id) ? $edit_id :  $this->module->getNextAvailableRecordId($this->providers_project);
            $data["record_id"]          = $record_id;
            $data["verification_token"] = $edit_id ? null : $this->module->makeEmailVerifyToken();

            $new_account = array();
            $new_account[] = $data;

            $instance_data      = array();
            $next_instance_id   = $this->module->getNextInstanceId($record_id, "provider_delegates", "provider_delegate");
            if(!empty($post["delegates"])){
                foreach($post["delegates"] as $idx => $delegate){
                    $next_id                            = $this->module->getNextAvailableRecordId($this->providers_project) + $idx + 1;
                    $temp                               = array();
                    $temp["redcap_repeat_instance"]     = $next_instance_id;
                    $temp["redcap_repeat_instrument"]   = "provider_delegates";
                    $temp["provider_delegate"]          = $delegate;
                    $temp["delegate_id"]                = $next_id;
                    $temp["record_id"]                  = $record_id;
                    $next_instance_id++;
                    $instance_data[] = $temp;

                    $delegate_info = array();
                    $delegate_info["verification_token"] = $this->module->makeEmailVerifyToken();
                    $delegate_info["provider_email"]     = $delegate;
                    $delegate_info["sponsor_id"]         = $record_id;
                    $delegate_info["record_id"]          = $next_id;
                    $new_account[] = $delegate_info;
                }
            }

            $r  = \REDCap::saveData($this->providers_project, 'json', json_encode($new_account) );
            if(empty($edit_id)){
                $this->module->emDebug("save deligates", $instance_data);
                $i  = \REDCap::saveData($this->providers_project, 'json', json_encode($instance_data) );
            }
            if(empty($r["errors"]) && empty($edit_id)){
                $this->module->emDebug("send new verification emails", $new_account);
                $this->newAccountEmail($new_account);
            } else {
                $_SESSION["buffer_alert"] = array("errors" => null , "success" => "Account verified.  Welcome to HeartEx! ");
                $this->loginProvider($data["provider_email"], $data["provider_pw"], true);
            }
            return $r;
        }
    }

    public function editProvider($post){
        $this->module->emDebug("edit post", $post);

        $_POST          = $post;
        $dict           = $this->getProjectDictionary($this->providers_project);
        $dict_keys      = array_keys($dict);

        // $provider_email = in_array("provider_email", $dict_keys) ? strtolower(trim(filter_var($_POST["provider_email"], FILTER_SANITIZE_STRING))) : null;
        $provider_pw    = in_array("provider_pw", $dict_keys) ? strtolower(trim(filter_var($_POST["provider_pw"], FILTER_SANITIZE_STRING))) : null;
        $provider_pw2   = in_array("provider_pw", $dict_keys) ? strtolower(trim(filter_var($_POST["provider_pw2"], FILTER_SANITIZE_STRING))) : null;
        $edit_id        = empty($_POST["record_id"]) ? null : $_POST["record_id"];

        $errors         = array(); 
        if($provider_pw != $provider_pw2){
            if( $provider_pw != $provider_pw2 ){
                $errors[] = "Mismatched Password Inputs";
            }
            return array("errors" => $errors);
        }
        
        if(empty($edit_id)){
            $errors[] = "Missing record id";
        }

   
        $data = array();
        foreach($dict_keys as $key){
            if(array_key_exists($key, $_POST)){
                $post_val = trim(filter_var($_POST[$key], FILTER_SANITIZE_STRING));
                if($key == "provider_pw"){
                    if(empty($provider_pw)){
                        continue;
                    }
                    $salt       = $provider_email;
                    $pepper     = $this->pepper;
                    $input      = $salt.$provider_pw.$pepper;
                    $post_val   = $this->pwHash($input);
                }
                // MASSAGE FOR CHECKBOX
                if ($dict[$key]["field_type"] == 'checkbox') {
                    if(strpos($dict[$key]["select_choices_or_calculations"],$post_val.",") > -1){
                        $key        = $key."___".$post_val;
                        $post_val   = 1;
                    }else{
                        //IF CHOICE NOT FOUND, JUST SKIP THIS FIELD , FIX LATER
                        continue;
                    }
                } 
                //MASSAGE FOR DATE FIELDS
                if ($dict[$key]["text_validation_type_or_show_slider_number"] == "date_ymd"){
                    $post_val = Date("Y-m-d", strtotime($post_val));
                }
                $data[$key] = $post_val;
            }
        }
        $record_id                  = !empty($edit_id) ? $edit_id :  $this->module->getNextAvailableRecordId($this->providers_project);
        $data["record_id"]          = $record_id;

        $new_account = array();
        $new_account[] = $data;

        $instance_data      = array();
        $next_instance_id   = $this->module->getNextInstanceId($record_id, "provider_delegates", "provider_delegate");
        if(!empty($post["delegates"])){
            foreach($post["delegates"] as $idx => $delegate){
                $next_id                            = $this->module->getNextAvailableRecordId($this->providers_project) + $idx + 1;
                $temp                               = array();
                $temp["redcap_repeat_instance"]     = $next_instance_id;
                $temp["redcap_repeat_instrument"]   = "provider_delegates";
                $temp["provider_delegate"]          = $delegate;
                $temp["delegate_id"]                = $next_id;
                $temp["record_id"]                  = $record_id;
                $next_instance_id++;
                $instance_data[] = $temp;

                $delegate_info = array();
                $delegate_info["verification_token"] = $this->module->makeEmailVerifyToken();
                $delegate_info["provider_email"]     = $delegate;
                $delegate_info["sponsor_id"]         = $record_id;
                $delegate_info["record_id"]          = $next_id;
                $new_account[] = $delegate_info;
            }
        }

        $this->module->emDebug("edit accoutn", $new_account);
        $r  = \REDCap::saveData($this->providers_project, 'json', json_encode($new_account) );
        if(empty($r["errors"])){
            //this is eddit so remove the actual provider
            array_shift($new_account);
            $this->newAccountEmail($new_account);
        }
        if(!empty($instance_data)){
            //TODO something stomping on existing delgates
            //NEED TO BE ABLE TO DELETE DELEGATES
            $this->module->emDebug("save deligates", $instance_data);
            $i  = \REDCap::saveData($this->providers_project, 'json', json_encode($instance_data) );
        }
        return $r;

    }

    public function getProvider($provider_id){
        $fields     = array();
        $params     = array(
            'project_id'    => $this->providers_project,
            'records'       => array($provider_id),
            'fields'        => $fields,
            'return_format' => 'json'
        );
        $raw        = \REDCap::getData($params);
        $results    = json_decode($raw,1);
        return $results;
    }

    public function newAccountEmail($providers){
        $this->module->emDebug("providers?", $providers );

        $main_provider = $providers[0];
        foreach($providers as $new_account){
            $is_delegate    = array_key_exists("sponsor_id",$new_account);

            $verify_link        = $this->module->getUrl("pages/registration.php", true, true)."&email=".$new_account["provider_email"]."&verify=".$new_account["verification_token"];
            $msg_arr            = array();
            $welcome        = $is_delegate ? "To whom it may concern," : "Dear " . $new_account["provider_fname"] .",";
            $msg_arr[]      = "<p>" . $welcome . "</p>";
            if($is_delegate){
                $main_pro_name  = $main_provider["provider_fname"] . " " . $main_provider["provider_lname"];
                $msg_arr[]	    = "<p>You have been designated a delegate for ".$main_pro_name."'s Hypertension Patients.</p>";
            }else{
                $msg_arr[]	    = "<p>Thanks for registering as a Provider.</p>";
            }
            $msg_arr[]      = "<p>Please click this <a href='".$verify_link."'>link</a> to complete your account registration.<p>";
            $msg_arr[]      = "<p>Thank You! <br> Stanford HypertensionStudy Team</p>";
            
            $message 	= implode("\r\n", $msg_arr);
            $to 		= $new_account["provider_email"];
            $from 		= "no-reply@stanford.edu";
            $subject 	= "HTN needs to verify your email";
            $fromName	= "Stanford Hypertension Team";

            $result = \REDCap::email($to, $from, $subject, $message);
            $this->module->emDebug("verification email sent?", $result,$verify_link );
        }
    }

	public function verifyAccount($verification_email, $verification_token){
		$provider = $this->findProviderByToken($verification_email, $verification_token);
        if(!empty($provider)){
            $record_id      = $provider["record_id"];
            $consent_date   = $provider["provider_consent_date"];

            if(empty($consent_date)){
                $provider_consent_link = \REDCap::getSurveyLink($record_id, 'provider_consent_mobile_hypertension_project_hrtex', '','', $this->providers_project );
                return array("errors" => array("need_consent"), "provider" => $provider, "consent_link" => $provider_consent_link);
            }else{
                // delete the verification token , set the time stamp
                $data = array(
                    "record_id"         => $provider["record_id"],
                    "verification_token"=> "",
                    "verification_ts"   => Date("Y-m-d H:i:s"),
                );
                $r    = \REDCap::saveData($this->providers_project, 'json', json_encode(array($data)) , $overwriteBehavior = "overwrite");
                return array("errors" => $r["errors"], "provider" => $provider);
            }
        }
		return false;
	}
	
	public function findProviderByToken($verification_email, $verification_token){
		$filter	= "[verification_token] = '$verification_token' and [provider_email] = '$verification_email'";
        $fields	= array("record_id","provider_email", "provider_pw", "sponsor_id", "provider_name_consent", "provider_consent_date", "provider_fname");
		$params	= array(
            'project_id'    => $this->providers_project,
			'return_format' => 'json',
			'fields'        => $fields,
			'filterLogic'   => $filter 
		);
		$q 			= \REDCap::getData($params);
        $records 	= json_decode($q, true);
        return current($records);
	}

    public function getProviderbyId($provider_id){
		$filter	= "[record_id] = '$provider_id'";
        $fields	= array("record_id","provider_email", "provider_pw", "sponsor_id");
		$params	= array(
            'project_id'    => $this->providers_project,
			'return_format' => 'json',
			'fields'        => $fields,
			'filterLogic'   => $filter 
		);
		$q 			= \REDCap::getData($params);
        $records 	= json_decode($q, true);
        return current($records);
	}

    public function sendPatientConsent($patient_id, $consent_url , $consent_email){
        $result = false;
		if( !empty($patient_id) && !empty($consent_url) && !empty($consent_email) ){
			$msg_arr        = array();
			$msg_arr[]      = "<p>Dear Heart Ex Participant,</p>";
			$msg_arr[]	    = "<p>In order to participate in this study, we need your consent to access your medical information.</p>";
            $msg_arr[]      = "<p>Please click this <a href='$consent_url'>link</a> to consent to be part of the study<p>";
			$msg_arr[]      = "<p>Thank You! <br> Stanford Heart Ex Team</p>";
			
			$message 	= implode("\r\n", $msg_arr);
			$to 		= $consent_email;
			$from 		= "no-reply@stanford.edu";
			$subject 	= "Stanford Heart Ex requests your consent";
			$fromName	= "Stanford Heart Ex Team";

			$result = \REDCap::email($to, $from, $subject, $message);
			if($result){
				$data = array(
					"record_id"     => $patient_id,
					"consent_sent"  => date("Y-m-d"),
                    "patient_email" => $consent_email
				);
				$r = \REDCap::saveData("json", json_encode(array($data)) );
			}
            $result = array("consent_sent" => date("m-d-y"), "patient_id" => $patient_id);
		}
		return $result;
    }

    public function newPatientConsent($provider_id=null){
        //create new record in patients
        $next_id = $this->module->getNextAvailableRecordId($this->patients_project);
        if(empty($provider_id)){
            $provider_id = !empty($_SESSION["logged_in_user"]["sponsor_id"]) ? $_SESSION["logged_in_user"]["sponsor_id"] : $_SESSION["logged_in_user"]["record_id"];
        }
        $main_provider = current($this->getProvider($provider_id));

        $data["patient_physician_id"]   = $provider_id;
        $data["provider_cell"]          = is_array($main_provider) && !empty($main_provider["provider_cell"]) ? $main_provider["provider_cell"] :null;
        $data["record_id"]              = $next_id;
        $r                              = \REDCap::saveData($this->patients_project, 'json', json_encode(array($data)) );

        $this->module->emDebug("wtf",$main_provider,  $data, $r);
        //get survey link for consent
        $survey_link                    = \REDCap::getSurveyLink($next_id, 'patient_consent_for_mobile_hypertension_system');

        return array("consent_link" => $survey_link, "patient_id" => $next_id);
    }

    public function addPatient($post){
        $script_fieldnames = $this->module->getPatientBaselineFields(); //\REDCap::getFieldNames("patient_baseline");
        $required = array(
             "patient_mrn"
            ,"patient_email"
            ,"patient_bp_target_systolic"
            ,"patient_bp_target_diastolic"
            ,"patient_bp_target_pulse"
            ,"current_treatment_plan_id"
        );


        if(empty($script_fieldnames)){
            //WTF NOW? THIS NO LONGER WORKS?
            $script_fieldnames = array("record_id","patient_fname","patient_mname","patient_lname","alias_select", "patient_bp_target_pulse", "patient_mrn", "patient_email", "patient_phone", "patient_bp_target_systolic", "patient_bp_target_diastolic");
        }

        $error_str  = "";
        $data       = array();
        foreach($required as $req_var){
            if($req_var == "current_treatment_plan_id" && $post[$req_var] == 99){
                $post[$req_var] = 1;
            }
            if(empty($post[$req_var])){
                $error_str .= "<li>[$req_var] is required</li>";
            }
        }
        if($error_str != ""){
            $error_str = "<p>The following fields are required:</p><ul>$error_str</ul>";
            return array("errors" => $error_str);
        }

        foreach($post as $rc_var => $rc_val){
            if( !in_array($rc_var, $script_fieldnames) ){
                continue;
            }
            $data[$rc_var] = $rc_val;
        }
        if( (isset($post["action"]) && $post["action"] == "add") || empty($post["action"]) ){
            $this->module->emDebug("superdelegate?", $_SESSION["logged_in_user"]["super_delegate"], $_SESSION["logged_in_user"]);
            if(!$_SESSION["logged_in_user"]["super_delegate"]){
                $data["patient_physician_id"]       = !empty($_SESSION["logged_in_user"]["sponsor_id"]) ? $_SESSION["logged_in_user"]["sponsor_id"] : $_SESSION["logged_in_user"]["record_id"];
            }
            $data["patient_treatment_status"]   = 0; //always start with the first step of whatever tree
            $data["patient_add_ts"]             = Date("Y-m-d H:i:s"); //always start with the first step of whatever tree
        }
        $data["current_treatment_plan_id"]      = 1; //default to 1
        $next_id                                = !empty($post["record_id"]) ? $post["record_id"] : $this->module->getNextAvailableRecordId($this->patients_project);
        $data["record_id"]                      = $next_id;

        $r    = \REDCap::saveData($this->patients_project, 'json', json_encode(array($data)) );
        $this->module->emDebug("patient added or edited or what?", $r, $data);
        return $r;
    }

    public function flagPatientForDeletion($record_id){
        $data   = array(
            "record_id"             => $record_id,
            "patient_remove_flag"   => 1
        );
        $r      = \REDCap::saveData($this->patients_project, 'json', json_encode(array($data)) );
        return $r;
    }

    private function pwHash($pw_input, $cost=12){
        $pw_hash = password_hash($pw_input, PASSWORD_BCRYPT, array('cost'=>$cost));
        return $pw_hash;
    }

    private function pwVerify($input, $pw_hash){
        if(password_verify($input,$pw_hash)){
            return true;
        }else{
            return false;
        }
    }
}
?> 