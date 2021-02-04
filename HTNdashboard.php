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

    public function getAllPatients($provider_id){
        // $this->getProviderbyId($provider_id)

        $filter	= "[patient_physician_id] = '$provider_id'";
        $fields	= array("record_id", "patient_fname", "patient_lname" , "patient_birthday", "sex", "patient_photo", "filter");
		$params	= array(
            'project_id'    => $this->patients_project,
			'return_format' => 'json',
			'fields'        => $fields,
			'filterLogic'   => $filter 
		);
		$q 			= \REDCap::getData($params);
        $results 	= json_decode($q, true);
        
        $patients       = array();
        $rx_change      = array();
        $labs_needed    = array();
        $data_needed    = array();
        $messages       = array();

        foreach($results as $i => $result){
            $date2          = Date("Y-m-d");
            $diff           = abs(strtotime($date2)-strtotime($result["patient_birthday"]));
            $years          = floor($diff / (365*60*60*24));
            $result["age"]  = "$years yrs old";

            $result["patient_photo"]    = $this->module->getUrl('assets/images/icon_anon.gif');
            $result["patient_name"]     = $result["patient_lname"] . ", " . $result["patient_fname"] . " " . substr($result["patient_mname"],0,1);

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
            "rx_change"         => $rx_change,
            "labs_needed"       => $labs_needed,
            "data_needed"       => $data_needed,
            "messages"          => $messages
        );

        // $this->module->emDebug("getAllPatients() uiintf", $ui_intf);
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
            $date2  = Date("Y-m-d");

            $diff   = abs(strtotime($date2)-strtotime($result["patient_birthday"]));
            $years  = floor($diff / (365*60*60*24));
            $result["patient_age"]          = "$years yrs old";
            $result["planning_pregnancy"]   = $result["planning_pregnancy"] == "1" ? "Yes" : "No";
            
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
            $crk_filter  = "([lab_name] = 'creatinin' OR [lab_name] != 'potassium') AND [lab_ts] > '" . date("Y-m-d H:i:s", strtotime('-6 months')) . "'";
            $crk_params  = array(
                'project_id'    => $this->patients_project,
                "records"       => array($result["record_id"]),
                "fields"        => array("record_id", "creatinine", "potassium" , "cr_ts", "k_ts"),
                'return_format' => 'json',
                'filterLogic'   => $crk_filter
            );
            $crk_raw         = \REDCap::getData($crk_params);
            $crk_results     = json_decode($crk_raw,1);
            // $this->module->emDebug($crk_results);
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

        $filter     = "[provider_email] = '" . $salt . "'"; //TODO CHHECKK AGAINST PW TOO HAHAHA
        $fields     = array("record_id", "provider_email", "provider_pw", "provider_fname", "provider_mname", "provider_lname", "sponsor_id");
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
        $this->module->emDebug("register post", $post);

        $_POST          = $post;
        $dict           = $this->getProjectDictionary($this->providers_project);
        $dict_keys      = array_keys($dict);

        $provider_email = in_array("provider_email", $dict_keys) ? strtolower(trim(filter_var($_POST["provider_email"], FILTER_SANITIZE_STRING))) : null;
        $provider_pw    = in_array("provider_pw", $dict_keys) ? strtolower(trim(filter_var($_POST["provider_pw"], FILTER_SANITIZE_STRING))) : null;
        $provider_pw2   = in_array("provider_pw", $dict_keys) ? strtolower(trim(filter_var($_POST["provider_pw2"], FILTER_SANITIZE_STRING))) : null;

        $edit_id        = empty($_POST["record_id"]) ? null : $_POST["record_id"];

        $errors         = array(); 
        if(!$provider_email || !$provider_pw || $provider_pw != $provider_pw2){
            if( !$provider_email ){
                $errors[] = "Missing Email";
            }
            if( !$provider_pw || !$provider_pw2 ){
                $errors[] = "Missing Password Input";
            }
            if( $provider_pw != $provider_pw2 ){
                $errors[] = "Mismatched Password Inputs";
            }
            return array("errors" => $errors);
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
            $errors[] = "Username/Email already in system";
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
            $this->module->emDebug("what the fuck what the fuck", $r);

            if(empty($edit_id)){
                $this->module->emDebug("save deligates", $instance_data);
                $i  = \REDCap::saveData($this->providers_project, 'json', json_encode($instance_data) );
            }
            if(empty($r["errors"]) && empty($edit_id)){
                $this->module->emDebug("send new verification emails", $new_account);
                $this->newAccountEmail($new_account);
            } else {
                $this->loginProvider($data["provider_email"], $data["provider_pw"], true);
            }
            return $r;
        }
    }

    public function newAccountEmail($providers){
        $this->module->emDebug("providers?", $providers );

        $main_provider = $providers[0];
        foreach($providers as $new_account){
            $is_delegate    = array_key_exists("sponsor_id",$new_account);

            $verify_link        = $this->module->getURL("pages/registration.php", true, true)."&email=".$new_account["provider_email"]."&verify=".$new_account["verification_token"];
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
            // delete the verification token , set the time stamp
            $data = array(
                "record_id"         => $provider["record_id"],
                "verification_token"=> "",
                "verification_ts"   => Date("Y-m-d H:i:s"),
            );
            $r    = \REDCap::saveData($this->providers_project, 'json', json_encode(array($data)) , $overwriteBehavior = "overwrite");
            return array("errors" => $r["errors"], "provider" => $provider);
        }
		return false;
	}
	
	public function findProviderByToken($verification_email, $verification_token){
		$filter	= "[verification_token] = '$verification_token' and [provider_email] = '$verification_email'";
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

    public function addPatient($post){
        $script_fieldnames = $this->module->getPatientBaselineFields(); //\REDCap::getFieldNames("patient_baseline");
        $this->module->emDebug("post", $post);
        
        if(empty($script_fieldnames)){
            //WTF NOW? THIS NO LONGER WORKS?
            $script_fieldnames = array("record_id","patient_fname","patient_mname","patient_lname","alias_select", "patient_bp_target_pulse", "patient_mrn", "patient_email", "patient_phone", "patient_bp_target_systolic", "patient_bp_target_diastolic");
        }

        $data = array();
        foreach($post as $rc_var => $rc_val){
            if( !in_array($rc_var, $script_fieldnames) ){
                continue;
            }
            if($rc_var == "patient_birthday"){
                $rc_val = Date("Y-m-d", strtotime($rc_val));
            }
            $data[$rc_var] = $rc_val;
        }
        if( (isset($post["action"]) && $post["action"] == "add") || empty($post["action"]) ){
            $data["patient_physician_id"]       = !empty($_SESSION["logged_in_user"]["sponsor_id"]) ? $_SESSION["logged_in_user"]["sponsor_id"] : $_SESSION["logged_in_user"]["record_id"];
            $data["patient_treatment_status"]   = 0; //always start with the first step of whatever tree
            $data["patient_add_ts"]             = Date("Y-m-d H:i:s"); //always start with the first step of whatever tree
        }
        $data["current_treatment_plan_id"]  = $post["current_treatment_plan_id"] ?? 1;
        $next_id                            = !empty($post["record_id"]) ? $post["record_id"] : $this->module->getNextAvailableRecordId($this->patients_project);
        $data["record_id"]                  = $next_id;

        $r    = \REDCap::saveData($this->patients_project, 'json', json_encode(array($data)) );
        $this->module->emDebug("patient added or edited or what?", $r, $data);
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