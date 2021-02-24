<?php
namespace Stanford\HTNapi;

require_once( "emLoggerTrait.php" );
require_once( 'HTNdashboard.php' );
require_once( 'HTNtree.php' );

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class HTNapi extends \ExternalModules\AbstractExternalModule {

    use emLoggerTrait;

	private $dashboard
			,$tree;

	public  $enabledProjects;

    public function __construct() {
		parent::__construct();
		// Other code to run when object is instantiated
	}
	
	//Load the pertinent EM stuff, as well as the broken off Classes for Dashboard and Tree
	public function loadEM(){
		$this->getEnabledProjects();

		$this->dashboard 	= new \Stanford\HTNapi\HTNdashboard($this);
		$this->tree			= new \Stanford\HTNapi\HTNtree($this);

		// what else?  API stuff?
		session_start();
	}

	//DEFAULT TREES
	public function getDefaultTrees($provider_id=0){
		$this->loadEM();

		return $this->tree->getDefaultTrees($provider_id);
	}

	public function getDefaultMeds(){
		$this->loadEM();

		return $this->tree->getDefaultMeds();
	}

	 public function getDrugList(){
		$this->loadEM();

		return $this->tree->getDrugList();
	 }

	//Get All Patients
	public function dashBoardInterface($provider_id){
		$this->loadEM();

		$intf = $this->dashboard->getAllPatients($provider_id);

		$intf["ptree"] = array();
		$this->emDebug("its this fucking thing right?", $intf);


		return $intf;
	}

	//Get All Patients
	public function getPatientDetails($record_id){
		$this->loadEM();
		
		return $this->dashboard->getPatientDetails($record_id);
	}

	//Login Provider
	public function loginProvider($login_email, $login_pw, $already_hashed=false){
		$this->loadEM();

		return $this->dashboard->loginProvider($login_email, $login_pw,  $already_hashed);
	}

	//REgister PRovider
	public function registerProvider($post){
		$this->loadEM();

		$this->dashboard->registerProvider($post);
	}

	//EDIT PRovider
	public function editProvider($post){
		$this->loadEM();

		$this->dashboard->editProvider($post);
	}

	public function getProvider($provider_id){
		$this->loadEM();

		return $this->dashboard->getProvider($provider_id);
	}

	//EDIT LAB READING
	public function updateLabReading($record_id, $lab, $reading){
		$this->loadEM();

		// update the shortcut data in patient baseline
		$next_instance_id = $this->getNextInstanceId($record_id, "labs_log", "lab_ts");

		$data_log = array(
			"record_id"             	=> $record_id,
			"redcap_repeat_instance" 	=> $next_instance_id,
			"redcap_repeat_instrument" 	=> "labs_log",
			"lab_name" 					=> $lab,
			"lab_value" 				=> $reading,
			"lab_token" 				=> "manual",
			"lab_ts"				=> Date("Y-m-d")
		);

		$r = \REDCap::saveData($this->enabledProjects["patients"]["pid"], 'json', json_encode(array($data_log)) );

		$this->emDebug("fuck you", $r , $data_log);
		return array("errors" => $r["errors"], "data"=> $data_log);
	}

	/**
     * Load all enabled projects with this EM
     */
    public function getEnabledProjects() {
        $enabledProjects    = array();
		$projects           = \ExternalModules\ExternalModules::getEnabledProjects($this->PREFIX);
		
        while($project = db_fetch_assoc($projects)){
            $pid  = $project['project_id'];
            $name = $project['name'];
            $url  = APP_PATH_WEBROOT . 'ProjectSetup/index.php?pid=' . $project['project_id'];
            $mode = $this->getProjectSetting("em-mode", $pid);
            
            $enabledProjects[$mode] = array(
                'pid'   => $pid,
                'name'  => $name,
                'url'   => $url,
                'mode'  => $mode
            );
            
        }

        $this->enabledProjects = $enabledProjects;
        // $this->emDebug($this->enabledProjects, "Enabled Projects");
    }

	//show enabled projects
	public function showEnabledProjects(){
		return $this->enabledProjects;
	}

	public function addPatient($post){
		$this->loadEM();

		return $this->dashboard->addPatient($post);
	}

	public function treeLogic($provider_id){
		$this->loadEM();

		return $this->tree->treeLogic($provider_id);
	}

	public function saveTemplate($provider_id, $post){
		$this->loadEM();

		return $this->tree->saveTemplate($provider_id, $post);
	}

	public function acceptRecommendation($patient_details){
		$next_instance_id = $this->getNextInstanceId($patient_details["record_id"], "treatment_status_log", "ptree_log_ts");

		// ONLY UPDATE THIS WHEN IT RXCHANGE ACTUALLY ACCEPTED
		// add log to treattment_status_log
		$data_log = array(
			"record_id"             	=> $patient_details["record_id"],
			"redcap_repeat_instance" 	=> $next_instance_id,
			"redcap_repeat_instrument" 	=> "treatment_status_log",
			"ptree_log_prev_step" 		=> $patient_details["patient_treatment_status"],
			"ptree_log_current_step" 	=> $patient_details["patient_rec_tree_step"],
			"ptree_current_meds" 		=> $patient_details["current_drugs"],
			"ptree_log_comment" 		=> $patient_details["provider_comment"],
			"ptree_log_ts"				=> Date("Y-m-d H:i:s")
		);
		$r = \REDCap::saveData($this->enabledProjects["patients"]["pid"], 'json', json_encode(array($data_log)) );

		// update the shortcut data in patient baseline
		$data = array(
			"record_id"             	=> $patient_details["record_id"],
			"patient_rec_tree_step" 	=> '',
			"patient_treatment_status" 	=> $patient_details["patient_rec_tree_step"],
			"filter" 					=> '',
			"last_update_ts"			=> Date("Y-m-d H:i:s")
		);
		// TODO THIS timestamp IS WHEN THE LAST RECOMMENDATION WAS MADE or Accepted, DONT MAKE ANOTHER ONE FOR 2 WEEKS at LEAST 

		$r = \REDCap::saveData($this->enabledProjects["patients"]["pid"], 'json', json_encode(array($data)), "overwrite" );
		$this->emDebug("accepting rec rx change", $data_log);
		return array("rec saved");
	}

	public function declineRecommendation($patient_details){
		// update the shortcut data in patient baseline
		$data = array(
			"record_id"             	=> $patient_details["record_id"],
			"patient_rec_tree_step" 	=> '',
			"filter" 					=> ''
		);
		$r = \REDCap::saveData($this->enabledProjects["patients"]["pid"], 'json', json_encode(array($data)), "overwrite" );
		return array("rec declined");
	}

	public function getPatientBaselineFields(){
		$this->loadEM();
		$script_fieldnames = \REDCap::getFieldNames("patient_baseline");
		return $script_fieldnames;
	}

	public function sendToPharmacy($patient){
		//TODO, FIGURE OUT PHARMACY API
		$this->emDebug("SEND TO PHARMACY FOR patient", $patient);
		return;
	}
	public function verifyAccount($verification_email,$verification_token){
		$this->loadEM();
		return $this->dashboard->verifyAccount($verification_email,$verification_token);
	}

	public function makeEmailVerifyToken(){
		return $this->generateRandomString(10,false,true);
	}

	// Creates random alphanumeric string
	public function generateRandomString($length=25, $addNonAlphaChars=false, $onlyHandEnterableChars=false, $alphaCharsOnly=false) {
		// Use character list that is human enterable by hand or for regular hashes (i.e. for URLs)
		if ($onlyHandEnterableChars) {
			$characters = '34789ACDEFHJKLMNPRTWXY'; // Potential characters to use (omitting 150QOIS2Z6GVU)
		} else {
			$characters = 'abcdefghijkmnopqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ23456789'; // Potential characters to use 
			if ($addNonAlphaChars) $characters .= '~.$#@!%^&*-';
		}
		// If returning only letter, then remove all non-alphas from $characters
		if ($alphaCharsOnly) {
			$characters = preg_replace("/[^a-zA-Z]/", "", $characters);
		}
		// Build string
		$strlen_characters = strlen($characters);
		$string = '';
		for ($p = 0; $p < $length; $p++) {
			$string .= $characters[mt_rand(0, $strlen_characters-1)];
		}
		// If hash matches a number in Scientific Notation, then fetch another one 
		// (because this could cause issues if opened in certain software - e.g. Excel)
		if (preg_match('/^\d+E\d/', $string)) {
			return generateRandomString($length, $addNonAlphaChars, $onlyHandEnterableChars);
		} 
			
		return $string;
	}

	/*
		BELOW HERE IS ALL THE OMRON AUTHORIZATION WORK FLOW STUFF
	*/
	//get oauthURL to presetn to Patient
	public function emailOmronAuthRequest($patient){
		$result = false;
		if( !empty($patient) && isset($patient["record_id"]) && isset($patient["patient_email"]) ){
			$auth_link 		= $this->getOAUTHurl($patient["record_id"]);

			$msg_arr        = array();
			$msg_arr[]      = "<p>Dear " . $patient["patient_fname"] . "</p>";
			$msg_arr[]	    = "<p>In order to participate in this study, we need access to your Blood Pressure Cuff data.</p>";
			$msg_arr[]	    = "<p>If you have not downloaded and set up your <b>Omron Heart Advisor</b> App yet, Please download them first.</p>";
			$msg_arr[]	    = "<p><a href='https://apps.apple.com/us/app/omron-heartadvisor/id1444973178' target='_blank'>Apple App Store</a> or <a href='https://play.google.com/store/apps/details?id=com.omronhealthcare.heartadvisor&hl=en_US&gl=US' target='_blank'>Google Play Store</a></p>";
            $msg_arr[]      = "<p>Please click this <a href='".$this->getURL("pages/oauth.php", true, true)."&state=".$patient["record_id"]."'>link</a> to start the process of authorizing us to retrieve your data from Omron.<p>";
			$msg_arr[]      = "<p>Thank You! <br> Stanford HypertensionStudy Team</p>";
			
			$message 	= implode("\r\n", $msg_arr);
			$to 		= $patient["patient_email"];
			$from 		= "no-reply@stanford.edu";
			$subject 	= "HTN Study Needs Your Authorization";
			$fromName	= "Stanford Hypertension Study Team";

			$result = \REDCap::email($to, $from, $subject, $message);
		}
		return $result;
	}

	//get oauthURL to presetn to Patient
	public function getOAUTHurl($record_id = null){
		$oauth_url = null;
		
		if($record_id){
			$client_id      = $this->getProjectSetting("omron-client-id");
			$oauth_url      = $this->getProjectSetting("omron-auth-url");
			$oauth_postback = $this->getProjectSetting("omron-postback");
			$oauth_scope    = $this->getProjectSetting("omron-auth-scope");

			$oauth_params   = array(
				"client_id"     => $client_id,
				"response_type" => "code",
				"scope"         => $oauth_scope,
				"redirect_uri"  => $oauth_postback,
				"state"         => $record_id
			);
			$oauth_params["state"] = $record_id;
			$oauth_url .= "/connect/authorize?". http_build_query($oauth_params);
		}

		return $oauth_url;
	}

	// gets or refresh omron access token
	public function getOrRefreshOmronAccessToken($access_refresh_token, $refresh=false){
		$client_id      = $this->getProjectSetting("omron-client-id");
		$client_secret  = $this->getProjectSetting("omron-client-secret");
		$omron_url      = $this->getProjectSetting("omron-auth-url");
		$oauth_postback = $this->getProjectSetting("omron-postback");
		$oauth_scope    = $this->getProjectSetting("omron-auth-scope");

		$data 			= array(
			"client_id" 	=> $client_id,
			"client_secret" => $client_secret,
			"redirect_uri"	=> $oauth_postback
		);

		if($refresh){
			$data["refresh_token"] 	= $access_refresh_token;
			$data["grant_type"] 	= "refresh_token";
		}else{
			$data["code"] 			= $access_refresh_token;
			$data["grant_type"] 	= "authorization_code";
			$data["scope"] 			= $oauth_scope;
		}

		$api_url 		= $omron_url . "/connect/token";
		$ch 			= curl_init($api_url);

        $header_data = array();
		array_push($header_data, 'Content-Type: application/x-www-form-urlencoded');
		array_push($header_data, 'Content-Length: ' . strlen(http_build_query($data)));
		array_push($header_data, 'Cache-Control: no-cache' );
		
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($ch, CURLOPT_POST, true);
	
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header_data);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_TIMEOUT, 105200);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        
		$info 	= curl_getinfo($ch);
		$result = curl_exec($ch);
        curl_close($ch);

        return $result;
	}

	//returns array, expects JWT token
	public function decodeJWT($token){
		return json_decode(base64_decode(str_replace('_', '/', str_replace('-','+',explode('.', $token)[1]))), true);
	}

	// gets or refresh omron access token
	public function getOmronAPIData($omron_access_token, $omron_token_type, $hook_timestamp=null, $limit=null, $type="bloodpressure", $includeHourlyActivity=false, $sortOrder="asc"){
		//There are two typical use cases when you will use the Omron API to retrieve data for a user: 
		// To retrieve historical data at the time the user authorizes your application
		// To retrieve data whenever your application receives notification that an upload has occurred
		$omron_url = $this->getProjectSetting("omron-api-url");

		$data = array(
			"type" 					=> $type,
			"sortOrder"				=> $sortOrder
		);

		// OPTIONAL
		if($limit){
			$data["limit"] = $limit;
		}
		if($includeHourlyActivity){
			$data["includeHourlyActivity"] = $includeHourlyActivity;
		}
		if(!$hook_timestamp){
			//supposed to default to "from the beginning" with this field empty, but it appears to be required?
			$hook_timestamp = date("Y")."-".date("m")."-01";
		}
		$data["since"] 	= $hook_timestamp;
		$api_url 		= $omron_url;
		$ch 			= curl_init($api_url);

		$header_data = array();
		array_push($header_data, "Authorization: $omron_token_type $omron_access_token");
		array_push($header_data, 'Content-Type: application/x-www-form-urlencoded');
		array_push($header_data, 'Content-Length: ' . strlen(http_build_query($data)));
		array_push($header_data, 'Cache-Control: no-cache' );
		
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($ch, CURLOPT_POST, true);
	
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header_data);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_TIMEOUT, 105200);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        
        $info 	= curl_getinfo($ch);
		$result = curl_exec($ch);
        curl_close($ch);

        return $result;
	}

	// get Omron Token Details by OmronClientId
	public function getTokenDetails($omron_client_id){
		$filter	= "[omron_client_id] = '$omron_client_id'";
		$fields	= array("record_id","omron_access_token","omron_token_type");
		$params	= array(
			'project_id'	=> $this->enabledProjects["patients"]["pid"],
			'return_format' => 'json',
			'fields'        => $fields,
			'filterLogic'   => $filter 
		);
		$q 			= \REDCap::getData($params);
		$records 	= json_decode($q, true);
		if(empty($records)){
			$this->emDebug("Omron getTokenDetail() : Could not find Patient with omron_client_id = $omron_client_id");
			return false;
		}
		$this->emDebug("found token details");
		return current($records);
	}
	
	// get data via API and recurse if pagination
	public function recurseSaveOmronApiData($omron_client_id, $since_ts=null, $token_details=array()){
		$first_pass = false;
		if(empty($token_details)){
			$first_pass = true;
			//should only need on first pass
			$token_details 	= $this->getTokenDetails($omron_client_id);
			if(!$token_details){
				return false;
			}
		}

		$record_id 			= $token_details["record_id"];
		$omron_access_token = $token_details["omron_access_token"];
		$omron_token_type 	= $token_details["omron_token_type"];

		if(!empty($since_ts) || $first_pass){
			//USING THE TOKEN , HIT OMRON API TO GET LATEST READINGS SINCE (hook timestamp?)
			$adjusted_since_ts 	= date("Y-m-d", strtotime($since_ts .' -1 day'));
			// $this->emDebug("Since the newdata hook gets posted with THEIR server ts, but for some stupid reason, searches the data by LOCAL ts, to be safe I will adjust the ts - 1 day", $adjusted_since_ts);

			$result             = $this->getOmronAPIData($omron_access_token, $omron_token_type, $adjusted_since_ts);
			$api_data           = json_decode($result, true); 

			$status             = $api_data["status"];
			$truncated          = $api_data["result"]["truncated"];
			$bp_readings        = $api_data["result"]["bloodPressure"];
			$measurementCount   = $api_data["result"]["measurementCount"];

			// $this->emDebug("in recurseSaveOmronApiData() here is API data since $since_ts", $api_data);
			if($status == 0){
				$bp_instance_data = $this->getBPInstanceData($record_id);
				$next_instance_id = $bp_instance_data["next_instance_id"];
				$used_bp_id 	  = $bp_instance_data["used_bp_id"];

				$data = array();
				foreach($bp_readings as $reading){
					$omron_bp_id            = $reading["id"];

					if(in_array($omron_bp_id, $used_bp_id) ){
						//reading already in RC, skip
						continue;
					}

					$bp_reading_ts          = date("Y-m-d H:i:s", strtotime($reading["dateTime"]));
					$bp_reading_local_ts    = date("Y-m-d H:i:s", strtotime($reading["dateTimeLocal"]));
					$bp_systolic            = $reading["systolic"];
					$bp_diastolic           = $reading["diastolic"];
					$bp_units               = $reading["bloodPressureUnits"];
					$bp_pulse               = $reading["pulse"];
					$bp_pulse_units         = $reading["pulseUnits"];
					$bp_device_type         = $reading["deviceType"];

					$temp = array(
						"redcap_repeat_instance" 	=> $next_instance_id,
						"redcap_repeat_instrument" 	=> "bp_readings_log",
						"record_id"             	=> $record_id,
						"omron_bp_id"           	=> $omron_bp_id,
						"bp_reading_ts"         	=> $bp_reading_ts,
						"bp_reading_local_ts"   	=> $bp_reading_local_ts,
						"bp_systolic"           	=> $bp_systolic,
						"bp_diastolic"          	=> $bp_diastolic,
						"bp_units"              	=> $bp_units,
						"bp_pulse"              	=> $bp_pulse,
						"bp_pulse_units"        	=> $bp_pulse_units,
						"bp_device_type"        	=> $bp_device_type
					);
					$next_instance_id++;
					$data[] = $temp;

					//Im trying to save lives DAMNIT!
					//If the reading timestamp is TODAY, then urgency is HIGH, email and text patient to Call 911
					$bp_reading_today  	= date("Y-m-d", strtotime($reading["dateTime"]));
					$todate 			= date("Y-m-d");
					if($bp_reading_today == $todate && $bp_systolic > 180){
						$this->contactPatient($record_id, "Warning : Abnormal Blood Pressure Reading Detected", "<p>We received your blood pressure cuff data from Omron.</p><p>The reading is at a dangerously high level : $bp_systolic/$bp_diastolic.</p><p>Please call 911 or Go to Urgent Care.</p>");
					}
				}
			
				$r = \REDCap::saveData($this->enabledProjects["patients"]["pid"], 'json', json_encode($data) );
				if(empty($r["errors"])){
					$readings_saved = count($r["ids"]);
					$this->emDebug("Saved $readings_saved Omron BP readings for RC $record_id");
					
					if($truncated){
						//last bp_reading_ts from the foreach will be the paginating ts
						$this->recurseSaveOmronApiData($omron_client_id, $bp_reading_ts, $token_details);
					}else{
						$this->emDebug("recurseSaveOmronApiData done, now evaluate the last 2 weeks data to see if need rx change");
						$this->evaluateOmronBPavg($record_id);
						return true;
					}
				}else{
					$this->emDebug("ERROR trying to save $readings_saved Omron BP readings for RC $record_id", $r["errrors"], $data);
					return false;
				}
        	}
		}
	}

	// When new data comes in. How should we evaluated if the patient needs a tree change recommendation?
	// TODO 12/1/2020 only evaluate minimum 2 weeks after a prescription change or start of treatment
	// NEED TO PROMPT FOR CR & K readings FROM PROVIDER  BEFORE MAKING RX CHANGE ASSESTMENT if not within last 2 weeks.
	public function evaluateOmronBPavg($record_id){
		$this->loadEM();

		//TODO how to do this?
		$control_condition = 1.6; //60%
		
		// GET patient BP data, current filter status, and 
		$params	= array(
			'project_id'	=> $this->enabledProjects["patients"]["pid"],
			'records' 		=> array($record_id),
			'return_format' => 'json',
			'fields'        => array("record_id", "filter", "patient_bp_target_systolic", "patient_bp_target_diastolic", "patient_bp_target_pulse","current_treatment_plan_id", "patient_treatment_status", "last_update_ts"),
			'filterLogic' 	=> $filter
		);
		$q 					= \REDCap::getData($params);
		$records			= json_decode($q, true);
		$patient 			= current($records);

		$this->emDebug("the fucking patient", $patient);

		//GET PATIENT TREE CHANGE STEPS TAKEN (not including the first step 0)
		// $tree_filter  		= "[ptree_log_ts] != ''";
		// $tree_params  		= array(
		// 	"records"       => array($record_id),
		// 	"fields"        => array("record_id", "ptree_log_ts", "ptree_log_prev_step" , "ptree_log_current_step", "ptree_current_meds", "ptree_log_comment"),
		// 	'return_format' => 'json',
		// 	'filterLogic'   => $tree_filter
		// );
		// $tree_raw         	= \REDCap::getData($tree_params);
		// $tree_results     	= json_decode($tree_raw,1);

		$target_pulse 		= $patient["patient_bp_target_pulse"];
		$target_systolic 	= $patient["patient_bp_target_systolic"];
		$target_diastolic 	= $patient["patient_bp_target_diastolic"];
		
		$current_tree 		= $patient["current_treatment_plan_id"];
		$current_step 		= $patient["patient_treatment_status"]; 
		$rec_step 			= $patient["patient_rec_tree_step"];

		$dash_filter 		= json_decode($patient["filter"],1);

		//TODO
		//if time since last_update_ts is >= 2 weeks
		//more complex algo than mean for triggering rx change
		if(!empty($target_systolic)){
			//TODO FIX THE FILTER  one week? two weeks?!?
			$filter = "";//"[bp_reading_ts] > '" . date("n/j/y H:i", strtotime('-20 weeks')) . "'";
			$params	= array(
				'project_id'	=> $this->enabledProjects["patients"]["pid"],
				'records' 		=> array($record_id),
				'return_format' => 'json',
				'fields'        => array("record_id", "patient_bp_target_systolic", "patient_bp_target_diastolic", "patient_bp_target_pulse", "bp_reading_ts",  "omron_bp_id", "bp_systolic", "bp_diastolic", "bp_pulse"),
				'filterLogic' 	=> $filter
			);
			$q 			= \REDCap::getData($params);
			$records	= json_decode($q, true);

			$this->emDebug("why wont repeating work with meds then? ", $records);

			$systolic 	= array();
			$diastolic 	= array();
			$pulse 		= array();

			// $this->emDebug("TODO will need to make sure not to eval if less than 2 weeks data?");
			//TODO 12/1/2020 : rEDO eval logic against target COMPARE individual readings in the last 2 weeks vs goals as long at 60% of them... wtf

			foreach($records as $record){
				if($record["redcap_repeat_instrument"] == "bp_readings_log"){
					array_push($systolic, $record["bp_systolic"]);
					array_push($diastolic, $record["bp_diastolic"]);
					array_push($pulse, $record["bp_pulse"]);
				}
			}

			$systolic_mean 	= round(array_sum($systolic)/count($systolic));
			$diastolic_mean = round(array_sum($diastolic)/count($diastolic));
			$pulse_mean 	= round(array_sum($pulse)/count($pulse));

			//TODO 
			$systolic_mean 	= array_pop($systolic);

			// $this->emDebug("TODO, need a more granular evaluation for _uncontrolled");
			$sys_uncontrolled = $systolic_mean > $target_systolic ? true : false;
			$dia_uncontrolled = $diastolic_mean > $target_diastolic ? true : false;
			$pls_uncontrolled = $pulse_mean > $target_pulse ? true : false;	
			
			$this->emDebug("systolic_mean / target_systolic / sys_uncontrolled / current step" ,$systolic, $systolic_mean,  $target_systolic, $sys_uncontrolled, $current_step);
			$this->emDebug("current dash filter" , $dash_filter);
			if(!empty($systolic) && $sys_uncontrolled){
				$treelogic 				= $this->tree->treeLogic(1);
				$current_tree_step 		= $treelogic["logicTree"][$current_step];
				$uncontrolled_next_step = $current_tree_step["bp_status"]["Uncontrolled"];
				$this->emDebug("rx change recommendation, Use current tree, find the uncontrolled next step", $current_tree_step, $treelogic["logicTree"][$uncontrolled_next_step]);
				
				//TODO PULL IN TREE INFO AND SEE WHAT NEXT STEP IS FOR "uncontrolled"
				$current_update_ts		= date("Y-m-d H:i:s");
				
				// Recommended RX change
				$data = array(
					"record_id"             	=> $record_id,
					"patient_rec_tree_step" 	=> $uncontrolled_next_step,
					"last_update_ts"			=> $current_update_ts,
					"filter"      				=> "rx_change",
				);
				$r = \REDCap::saveData($this->enabledProjects["patients"]["pid"], 'json', json_encode(array($data)) );
				$this->emDebug("for saves too?" , $r);
			}
		}

		return;
	}

	// contact patient by email / text if available
	public function contactPatient($record_id, $subject, $msg){
		$result = false;

		$params	= array(
			'project_id'	=> $this->enabledProjects["patients"]["pid"],
			'records' 		=> array($record_id),
			'return_format' => 'json',
			'fields'        => array("patient_email", "patient_phone", "patient_fname")
		);
		$q 			= \REDCap::getData($params);
		$records	= json_decode($q, true);
		$patient    = current($records);

		if(!empty($patient["patient_phone"])){
			//TODO  SEND TWILIO SMS TO PATIENT PHONEWITH $msg only

		}

		if( !empty($patient["patient_email"]) ){
			$msg_arr        = array();
            $msg_arr[]      = "<p>Dear " . $patient["patient_fname"] . "</p>";
            $msg_arr[]	    = $msg;
			$msg_arr[]      = "<p>Stanford Hypertension Team</p>";
			
			$message 	= implode("\r\n", $msg_arr);
			$to 		= $patient["patient_email"];
			$from 		= "no-reply@stanford.edu";
			$fromName	= "Stanford Hypertension Team";

			$result = \REDCap::email($to, $from, $subject, $message);
		}

		return $result;
	}

	// get the next instance id (repeating) in bp_readings_log
	public function getBPInstanceData($record_id){
		$filter	= "[omron_bp_id] != '' ";
		$fields	= array("record_id","omron_bp_id");

		$params	= array(
			'project_id'	=> $this->enabledProjects["patients"]["pid"],
            'return_format' => 'json',
			'fields'        => $fields,
            'filterLogic'   => $filter 
		);
		if($record_id){
			$params['records'] = array($record_id);
		}

        $q 			= \REDCap::getData($params);
		$records 	= json_decode($q, true);
		
		$used_bp_id 		= array();
		$last_instance_id 	= 0;
		foreach($records as $record){
			array_push($used_bp_id, $record["omron_bp_id"]);
			$last_instance_id = $record["redcap_repeat_instance"];
		}
		$last_instance_id++;

		return array("used_bp_id" => $used_bp_id, "next_instance_id" => $last_instance_id);
	}

	// get the next instance id (repeating) in bp_readings_log
	public function getNextInstanceId($record_id, $instrument, $field){
		$params	= array(
			'project_id'	=> $this->enabledProjects["patients"]["pid"],
			'return_format' => 'json',
			'records'		=> $record_id,
			'fields'        => $field,
            'filterLogic'   => "[$field] != ''"
		);

        $q 					= \REDCap::getData($params);
		$records 			= json_decode($q, true);
		$last_instance_id 	= count($records);
		$last_instance_id++;

		return $last_instance_id;
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
	
	// revoke omron access or refresh (why?) token, no return only 200 no matter what
	public function revokeToken($token_to_revoke, $refresh=false){
		$client_id      	= $this->getProjectSetting("omron-client-id");
		$client_secret  	= $this->getProjectSetting("omron-client-secret");
		$omron_url      	= $this->getProjectSetting("omron-auth-url");
		$token_type_hint    = $refresh ? "refresh_token" : "access_token";

		$data 			= array(
			"client_id" 	=> $client_id,
			"client_secret" => $client_secret,
			"token" 		=> $token_to_revoke
		);

		$api_url 		= $omron_url . "/connect/revocation";
		$ch 			= curl_init($api_url);

        $header_data = array();
		array_push($header_data, 'Content-Type: application/x-www-form-urlencoded');
		array_push($header_data, 'Content-Length: ' . strlen(http_build_query($data)));
		array_push($header_data, 'Cache-Control: no-cache' );
		
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($ch, CURLOPT_POST, true);
	
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header_data);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_TIMEOUT, 105200);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        
        $info 	= curl_getinfo($ch);
		$result = curl_exec($ch);
        curl_close($ch);

		//NEEDE TO REMOVE FROM RC, PUT HERE FOR NOW BUT MAYBE MOVE IT OUT AT SOME POINT



		// there is no response, only 200
        return $result;
	}

	// get patients with tokens
	public function getPatientsWithTokens($record_id = null){
		global $project_id;

		$filter	= "[omron_token_expire] != '' ";
		$fields	= array("record_id","omron_client_id","omron_acess_token","omron_refresh_token", "omron_token_expire", "omron_token_type");
		$params	= array(
			'project_id'	=> $project_id,
            'return_format' => 'json',
            'fields'        => $fields,
            'filterLogic'   => $filter 
		);
		if($record_id){
			$params['records'] = array($record_id);
		}

        $q = \REDCap::getData($params);
        $records = json_decode($q, true);

		return $records;
	}

	// cron to refresh omron access tokens expiring within 48 hours
	public function refreshOmronAccessTokens(){
		//GET ALL PATIENTS (REGARDLESS OF PROVIDER) WHO HAVE ACCESS TOKENS AND CHECK ALL EXPIRES
		//ANYTHING LANDING WITHIN 48 HOURS WILL NEED TO BE REFRESHED WITH THE refresh_token (does this get updated as well?)

		$data = array();
		$patients_with_tokens   = $this->getPatientsWithTokens();

		foreach($patients_with_tokens as $patient){
			$omron_token_expire = $patient["omron_token_expire"];
			$diff_in_seconds    = strtotime($omron_token_expire) - time();

			// 48 hours = 172800 seconds
			if($diff_in_seconds < 172800){
				$omron_refresh_token    = $patient["omron_refresh_token"];
				$record_id              = $patient["record_id"];
				
				//if token will expire within 48 hours, then go ahead and refresh it with the refresh token
				$result                 = $this->getOrRefreshOmronAccessToken($omron_refresh_token, true);
				$new_token_details      = json_decode($result, true);
				if(!empty($new_token_details)){
					$access_token           = $new_token_details["access_token"];
					$refresh_token          = $new_token_details["refresh_token"];
					$token_type             = $new_token_details["token_type"];
					$token_expire           = date("Y-m-d H:i:s", time() + $new_token_details["expires_in"]); 
					
					$temp = array(
						"record_id"             => $record_id,
						"omron_access_token"    => $access_token,
						"omron_refresh_token"   => $refresh_token,
						"omron_token_expire"    => $token_expire,
						"omron_token_type"      => $token_type
					);
					$data[] = $temp;
				}
			}
		}
		if(!empty($data)){
			$r = \REDCap::saveData($this->enabledProjects["patients"]["pid"], 'json', json_encode($data) );
			if(empty($r["errors"])){
				$this->emDebug("REFRESH TOKENS CRON : " . count($data) . " tokens updated");
			}else{
				$this->emDebug("ERRORS during REFRESH TOKENS CRON : " ,  $r["errors"] ,$data );
			}
		}else{
			$this->emDebug("refreshOmronAccessTokens : no tokens needed refreshing in the next 48 hours");
		}
		

		return;
	}

	// cron to pull data daily in case the notification ping fails?
	public function dailyOmronDataPull(){
		//GET ALL PATIENTS (REGARDLESS OF PROVIDER) WHO HAVE ACCESS TOKENS 
		//PULL ALL DATA FOR TODAY

		$data = array();
		$patients_with_tokens   = $this->getPatientsWithTokens();

		foreach($patients_with_tokens as $patient){
			$omron_client_id 	= $patient["omron_client_id"];
			$record_id 			= $patient["record_id"];
			$since_today 		= date("Y-m-d");
			$success 			= $this->recurseSaveOmronApiData($omron_client_id, $since_today);
			if($success){
				$this->emDebug("BP data for $since_today was succesfully downloaded for record_id $record_id");
			}
		}
		return;
	}

	// cron to refresh omron access tokens expiring within 48 hours
	public function htnAPICron(){
		$projects 	= $this->framework->getProjectsWithModuleEnabled();
		$urls 		= array(
						$this->getUrl('cron/refresh_omron_tokens.php', true, true)
						,$this->getUrl('cron/daily_omron_data_pull.php', true, true)
					); //has to be page
		foreach($projects as $index => $project_id){
			foreach($urls as $url){
				$thisUrl 	= $url . "&pid=$project_id"; //project specific
				$client 	= new \GuzzleHttp\Client();
				$response 	= $client->request('GET', $thisUrl, array(\GuzzleHttp\RequestOptions::SYNCHRONOUS => true));
				$this->emDebug("running cron for $url on project $project_id");
			}
			
		}
	}
}
