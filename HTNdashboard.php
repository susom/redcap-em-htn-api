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
        $this->pepper = $module->getProjectSetting(self::PEPPER);
        $this->getEnabledProjects();
        foreach($this->enabledProjects as $project){
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

    public function getEnabledProjects(){
        $enabledProjects    = array();
        $projects           = \ExternalModules\ExternalModules::getEnabledProjects($this->PREFIX);
        while($project = db_fetch_assoc($projects)){
            $pid  = $project['project_id'];
            $name = $project['name'];
            $url  = APP_PATH_WEBROOT . 'ProjectSetup/index.php?pid=' . $project['project_id'];
            $mode = $this->module->getProjectSetting(self::EM_MODE, $pid);
            
            $enabledProjects[$mode] = array(
                'pid'   => $pid,
                'name'  => $name,
                'url'   => $url,
                'mode'  => $mode
            );
            
        }
        $this->enabledProjects = $enabledProjects;
    }

    public function getAllPatients(){
        $params     = array(
            "fields"        => array("record_id", "patient_fname", "patient_lname" , "patient_birthday", "sex", "patient_photo", "filter", "patient_message", "message_ts", "message_read"),
            'return_format' => 'json'
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

            $patients[$result["record_id"]] = $result;

            if($result["filter"] == "rx_change"){
                $rx_change[] = $i;
            }
            if($result["filter"] == "results_needed"){
                $results_needed[] = $i;
            }
            if($result["filter"] == "data_needed"){
                $data_needed[] = $i;
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

    public function getPatientDetail($id){
        $params     = array(
            "records"       => $id,
            'return_format' => 'json'
        );
        $raw    = \REDCap::getData($params);
        $result = json_decode($raw,1);
        $result = current($result);

        $date2  = Date("Y-m-d");
        $diff   = abs(strtotime($date2)-strtotime($result["patient_birthday"]));
        $years  = floor($diff / (365*60*60*24));
    
        $result["patient_age"]          = "$years yrs old";
        $result["planning_pregnancy"]   = $result["planning_pregnancy"] == "1" ? "Yes" : "No";

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

        $this->module->emDebug("checking email", $results);
        $errors     = array();
        if(!empty($results)){
            $result         = current($results);
            $db_pw_hash     = $result["provider_pw"];
            $this->module->emDebug("verify pw",$pepper,  $input, $pw_hash, $db_pw_hash);

            if($this->pwVerify($input, $db_pw_hash)){
                $_SESSION["logged_in_user"] = $result;
                $this->module->emDebug("logged in", $result);
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    public function registerProvider(){
        $this->module->emDebug("post", $_POST);

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
            $data["record_id"]  = $this->getNextAvailableRecordId($this->providers_project);
            $this->module->emDebug("register", $data);

            $r  = \REDCap::saveData($this->providers_project, 'json', json_encode(array($data)) );
            return $r;
        }
    }

    /**
     * GET Next available RecordId in a project
     * @return bool
     */
    public function getNextAvailableRecordId($pid=PROJECT_ID){
        $pro                = new \Project($pid);
        $primary_record_var = $pro->table_pk;

        $q          = \REDCap::getData($pid, 'json', null, $primary_record_var );
        $results    = json_decode($q,true);
        if(empty($results)){
            $next_id = 1;
        }else{
            $last_entry = array_pop($results);
            $next_id    = $last_entry[$primary_record_var] + 1;
        }

        return $next_id;
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
