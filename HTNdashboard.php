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
        $filter     = "[patient_physician_id] = '$provider_id'";
        $params     = array(
            "fields"        => array("record_id", "patient_fname", "patient_lname" , "patient_birthday", "sex", "patient_photo", "filter", "patient_message", "message_ts", "message_read"),
            'return_format' => 'json',
            'filterLogic'   => $filter
        );
        $raw            = \REDCap::getData($params);
        $results        = json_decode($raw,1);
        
        $patients       = array();
        $rx_change      = array();
        $results_needed = array();
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
            if($result["filter"] == "results_needed"){
                $results_needed[] = $result["record_id"];
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
            "results_needed"    => $results_needed,
            "data_needed"       => $data_needed,
            "messages"          => $messages
        );

        $this->module->emDebug("getAllPatients()", $ui_intf);
        return $ui_intf;
    }

    public function getPatientDetails($record_id){
        $fields = array("record_id"
                        ,"patient_fname"
                        ,"patient_mname"
                        ,"patient_lname"
                        ,"patient_photo"
                        ,"patient_birthday"
                        ,"patient_group"
                        ,"patient_bp_target_pulse"
                        ,"patient_bp_target_systolic"
                        ,"patient_bp_target_diastolic"
                        ,"patient_physician_id"
                        ,"current_treatment_plan_id"
                        ,"patient_treatment_status"
                        ,"patient_rec_tree_step"
                        ,"sex"
                        ,"weight"
                        ,"height"
                        ,"bmi"
                        ,"planning_pregnancy"
                        ,"ckd"
                        ,"comorbidity"
                        ,"pharmacy_info"
                        ,"filter"
                        ,"patient_email"
                        ,"omron_client_id"

                       );
        $params = array(
            "records"       => $record_id,
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
            

            $bp_filter  = "[omron_bp_id] != '' AND [bp_reading_ts] > '" . date("Y-m-d H:i:s", strtotime('-1 weeks')) . "'";
            $bp_params  = array(
                "records"       => array($result["record_id"]),
                "fields"        => array("record_id", "omron_bp_id", "bp_reading_ts" , "bp_systolic", "bp_diastolic", "bp_pulse", "bp_device_type", "bp_units", "bp_pulse_units"),
                'return_format' => 'json',
                'filterLogic'   => $bp_filter
            );
            $bp_raw         = \REDCap::getData($bp_params);
            $bp_results     = json_decode($bp_raw,1);
            $result["bp_readings"] = $bp_results;
        }
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

    public function loginProvider($em_input, $pw_input){
        $salt       = $em_input;
        $pepper     = $this->pepper;
        $input      = $salt.$pw_input.$pepper;
        $pw_hash    = $this->pwHash($input);

        $filter     = "[provider_email] = '" . $salt . "'";
        $fields     = array("record_id", "provider_email", "provider_pw", "provider_fname", "provider_mname", "provider_lname");
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
            $this->module->emDebug("verify pw",  $input, $pw_hash, $db_pw_hash);

            if($this->pwVerify($input, $db_pw_hash)){
                $_SESSION["logged_in_user"] = $result;
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    public function registerProvider($post){
        // $this->module->emDebug("post", $post);

        $_POST          = $post;
        $dict           = $this->getProjectDictionary($this->providers_project);
        $dict_keys      = array_keys($dict);

        $provider_email = in_array("provider_email", $dict_keys) ? strtolower(trim(filter_var($_POST["provider_email"], FILTER_SANITIZE_STRING))) : null;
        $provider_pw    = in_array("provider_pw", $dict_keys) ? strtolower(trim(filter_var($_POST["provider_pw"], FILTER_SANITIZE_STRING))) : null;
        $provider_pw2   = in_array("provider_pw", $dict_keys) ? strtolower(trim(filter_var($_POST["provider_pw2"], FILTER_SANITIZE_STRING))) : null;

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
            $record_id          = $this->module->getNextAvailableRecordId($this->providers_project);
            $data["record_id"]  = $record_id;

            $instance_data      = array();
            $next_instance_id   = $this->module->getNextInstanceId($record_id, "provider_delegates", array("provider_delegate"));
            if(!empty($post["delegates"])){
                foreach($post["delegates"] as $delegate){
                    $temp                               = array();
                    $temp["redcap_repeat_instance"]     = $next_instance_id;
                    $temp["redcap_repeat_instrument"]   = "provider_delegates";
                    $temp["provider_delegate"]          = $delegate;
                    $temp["record_id"]                  = $record_id;
                    
                    
                    $next_instance_id++;
                    $instance_data[] = $temp;
                }
            }

            $r  = \REDCap::saveData($this->providers_project, 'json', json_encode(array($data)) );
            $i  = \REDCap::saveData($this->providers_project, 'json', json_encode($instance_data) );
            if(empty($r["errors"])){
                $this->loginProvider($provider_email, $provider_pw);
            }   
            return $r;
        }
    }

    public function addPatient($post){
        $script_fieldnames = \REDCap::getFieldNames("patient_baseline");
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
        $data["patient_physician_id"]       = $_SESSION["logged_in_user"]["record_id"];
        $data["patient_treatment_status"]   = 0; //always start with the first step of whatever tree
        
        //TODO how to pick template?
        $data["current_treatment_plan_id"]  = 1;

        $next_id            = $this->module->getNextAvailableRecordId($this->patients_project);
        $data["record_id"]  = $next_id;

        $r    = \REDCap::saveData('json', json_encode(array($data)) );
        $this->module->emDebug("ahha", $data);
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