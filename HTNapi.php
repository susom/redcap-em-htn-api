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
	public function dashBoardInterface($provider_id, $super_delegate=null, $dag_admin=null){
		$this->loadEM();

		// $this->emDebug("super delegate", $_SESSION["logged_in_user"]['super_delegate']);
		$intf = $this->dashboard->getAllPatients($provider_id, $super_delegate, $dag_admin);
		$intf["ptree"] 			= $this->treeLogic($provider_id);
		$intf["super_delegate"] = !empty($_SESSION["logged_in_user"]['super_delegate']) ? $this->dashboard->getAllProviders() : array();
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

		return $this->dashboard->registerProvider($post);
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

	public function sendResetPassword($login_email){
		$this->loadEM();

		return $this->dashboard->sendResetPassword($login_email);
	}

	public function updateProviderPassword($record_id, $login_email, $rawpw){
		$this->loadEM();

		return $this->dashboard->updateProviderPassword($record_id, $login_email, $rawpw);
	}

	public function flagPatientForDeletion($patient_record_id){
		$this->loadEM();

		return $this->dashboard->flagPatientForDeletion($patient_record_id);
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

		$this->emDebug("update lab reading", $r , $data_log);
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

	public function sendPatientConsent($patient_id, $consent_url , $consent_email){
		$this->loadEM();
		return $this->dashboard->sendPatientConsent($patient_id, $consent_url , $consent_email);
	}

    public function getProviderbyEmail($provider_email){
        $this->loadEM();
        return $this->dashboard->getProviderbyEmail($provider_email);
    }

	public function newPatientConsent($provider_id=null){
		$this->loadEM();
		return $this->dashboard->newPatientConsent($provider_id);
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
		$this->loadEM();

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
		// $this->emDebug("Saving the treatment step log?", $data_log);

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
		// $this->emDebug("Accepting rx change", $data);

		$this->updateRecLog($patient_details["record_id"], $patient_details["patient_rec_tree_step"]);
		return array("rec saved");
	}

	public function declineRecommendation($patient_details){
		$this->loadEM();

		// update the shortcut data in patient baseline
		$data = array(
			"record_id"             	=> $patient_details["record_id"],
			"patient_rec_tree_step" 	=> '',
			"filter" 					=> ''
		);
		$r = \REDCap::saveData($this->enabledProjects["patients"]["pid"], 'json', json_encode(array($data)), "overwrite" );

		$this->updateRecLog($patient_details["record_id"], $step_id, true);
		return array("rec declined");
	}

	public function updateRecLog($record_id, $step_id , $reject=false){
		$this->emDebug("updateing a recLog", $record_id, $step_id, $reject);
		if(!empty($step_id)){
			$fields = array("record_id", "rec_step");
			$filter = "[rec_step] = $step_id and [record_id] = $record_id";
			$params	= array(
				'return_format' => 'json',
				'fields'        => $fields,
				'filterLogic'   => $filter
			);
			$q 		= \REDCap::getData($params);
			$rows 	= json_decode($q,1);

			foreach($rows as $row){
				if($row["redcap_repeat_instrument"] == "recommendations_log"){
					$instance_id = $row["redcap_repeat_instance"];
					$temp = array(
						"redcap_repeat_instance" 	=> $instance_id,
						"redcap_repeat_instrument" 	=> "recommendations_log",
						"record_id"             	=> $record_id,
						"rec_accepted"           	=> ($reject ? "0" : "1"),
					);
					$data[] = $temp;
					$r = \REDCap::saveData($this->enabledProjects["patients"]["pid"], 'json', json_encode($data) );
					// $this->emDebug("accept/reject recommendtation", $data, $r);
				}
			}
		}
	}

	public function getPatientBaselineFields(){
		$this->loadEM();
		$script_fieldnames = \REDCap::getFieldNames("patient_baseline");
		return $script_fieldnames;
	}

	public function sendToPharmacy($patient){
		//TODO, FIGURE OUT PHARMACY API
		$this->emDebug("SEND TO PHARMACY FOR patient, NONE FOR NOW, IRB ISSUES");
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
			if($result){
				$data = array(
					"record_id" => $patient["record_id"],
					"omron_auth_request_ts" => date("Y-m-d H:i:s")
				);
				$r = \REDCap::saveData("json", json_encode(array($data)) );
			}
			// $this->emDebug("emailing patient", $result,$this->enabledProjects["patients"]["pid"], $r);
		}
		return $result;
	}

	public function dailySurveyCheck(){
		$this->loadEM();

		//FIRST GET ALL THE  PEOPLE
		//THEN DETERMINE WHAT DAY THEYVE JOINED
		//IF MULTIPLE OF 7 THEN SEND SURVEY INSTANCE

		$filter	= "";
		$fields	= array("record_id","patient_add_ts");
		$params	= array(
			'project_id'	=> $this->enabledProjects["patients"]["pid"],
			'return_format' => 'json',
			'fields'        => $fields,
			'filterLogic'   => $filter
		);
		$q 			= \REDCap::getData($params);
		$records 	= json_decode($q, true);

		foreach($records as $patient){
			$record_id 		= $patient["record_id"];
			$patient_added 	= $patient["patient_add_ts"];

			$dateOne 		= new \DateTime();
			$dateTwo 		= new \DateTime($patient_added);

			$days_since 	= $dateOne->diff($dateTwo)->format("%a");
			$weekly_anni	= $days_since % 7;

			if($weekly_anni === 0){
				$this->emDebug("Patient # $record_id 'weekly' anniversery" );

				//if patient has weekly anniversary lets mark it.
				$filter	= "";
				$fields	= array("record_id","message_ts");
				$params	= array(
					'project_id'	=> $this->enabledProjects["patients"]["pid"],
					'return_format' => 'json',
					'fields'        => $fields,
					'records'		=> $record_id,
					'filterLogic'   => $filter
				);
				$q 			= \REDCap::getData($params);
				$records 	= json_decode($q, true);

				$most_recent_message = array_pop($records);
				$current_instance_id = 0;
				if(!empty($most_recent_message["message_ts"])){
					$current_instance_id= $most_recent_message["redcap_repeat_instance"];
					$test_date 			= new \DateTime($most_recent_message["message_ts"]);
					$most_recent_date 	= $dateOne->diff($test_date)->format("%a");

					if($most_recent_date < 7){
						$this->emDebug("This patient already got this weeks survey $most_recent_date days ago");
						//they had a survey within the last week, no need
						continue;
					}
				}

				$next_instance_id = $current_instance_id +1;
				//create survey link if it gets past here
				$survey_link = \REDCap::getSurveyLink($record_id, "communications", "", $next_instance_id);

				if($survey_link){
					$data = array(
						"record_id"             	=> $record_id,
						"weekly_patient_survey" 	=> $survey_link
					);
					$r = \REDCap::saveData($this->enabledProjects["patients"]["pid"], "json",  json_encode(array($data)));
					if(empty($r["errors"])){
						echo "weekly survey queued for patient # $record_id <br>";
						$this->emDebug("only  patient #$record_id needs a weekly survey", $survey_link, $r);
					}
				}
			}
		}


		// $msg_arr        = array();
		// $msg_arr[]      = "<p>Dear " . $patient["patient_fname"] . "</p>";
		// $msg_arr[]	    = "<p>Please take a moment to answer your <a href='$survey_link' target='_blank'>weekly survey</a> for the Digital Hypertension Management System.</p>";
		// $msg_arr[]      = "<p>Thank You! <br> Stanford HypertensionStudy Team</p>";

		// $message 	= implode("\r\n", $msg_arr);
		// $to 		= $patient["patient_email"];
		// $from 		= "no-reply@stanford.edu";
		// $subject 	= "Weekly Stanford Digital Hypertension Management System Survey";
		// $fromName	= "Stanford Hypertension Study Team";


		// $this->emDebug("emailing patient", $result,$this->enabledProjects["patients"]["pid"], $r);

		return ;
	}

	public function dailySurveyClear(){
		$this->loadEM();

		$filter	= "[weekly_patient_survey] <> ''";
		$fields	= array("record_id","patient_add_ts");
		$params	= array(
			'project_id'	=> $this->enabledProjects["patients"]["pid"],
			'return_format' => 'json',
			'fields'        => $fields,
			'filterLogic'   => $filter
		);
		$q 			= \REDCap::getData($params);
		$records 	= json_decode($q, true);

		foreach($records as $patient){
			$record_id 		= $patient["record_id"];

			$data = array(
				"record_id"             	=> $record_id,
				"weekly_patient_survey" 	=> ""
			);
			$r = \REDCap::saveData($this->enabledProjects["patients"]["pid"], "json",  json_encode(array($data)), "overwrite");
			if(empty($r["errors"])){
				echo "removing weekly survey for patient #$record_id";
			}
		}
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
    public function getOrRefreshOmronAccessToken($token, $refresh = false) {
        $client_id      = $this->getProjectSetting("omron-client-id");
        $client_secret  = $this->getProjectSetting("omron-client-secret");
        $redirect_uri   = $this->getProjectSetting("omron-postback"); // Ensure this is correct and URL-encoded
        $omron_url      = $this->getProjectSetting("omron-api-url");
        $oauth_scope    = $this->getProjectSetting("omron-auth-scope");

        // Token endpoint
        $token_url  = $omron_url . "/connect/token";

        // Data to be sent in the POST request
        $data = array(
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri'  => $redirect_uri
        );

        if ($refresh) {
            $data['grant_type'] = 'refresh_token';
            $data['refresh_token'] = $token;
        } else {
            $data['grant_type'] = 'authorization_code';
            $data['code'] = $token;
        }

        // cURL initiation and setting options
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $token_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Execute the cURL session and close it
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            $this->emDebug("Curl error: " . curl_error($ch));
        }
        curl_close($ch);

        // Decode JSON response
        $response = json_decode($result, true);

        // Log the response for debugging purposes
//        $this->emDebug("getOrRefreshOmronAccessToken", $api_url, $token_url, $data, array_keys($response));

        return $response;
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
		$api_url 		= $omron_url . "/api/measurement";
		$ch 			= curl_init($api_url);
        $this->emDebug("$api_url");

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
        $this->emDebug("curl call", $result);
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
			$this->emDebug("Omron getTokenDetail() : Could not find Patient with omron_client_id = $omron_client_id", $params);
			return false;
		}
		$this->emDebug("found token details");
		return current($records);
	}

	// get data via API and recurse if pagination
    public function recurseSaveOmronApiData($omron_client_id, $since_ts = null, $token_details = array()) {
        $this->loadEM();

        $first_pass = false;
        if (empty($token_details)) {
            $first_pass = true;
            $token_details = $this->getTokenDetails($omron_client_id);
            if (!$token_details) {
                return false;
            }
        }

        $record_id = $token_details["record_id"];
        $omron_access_token = $token_details["omron_access_token"];
        $omron_token_type = $token_details["omron_token_type"];

        if (!empty($since_ts) || $first_pass) {
            $adjusted_since_ts = date("Y-m-d", strtotime($since_ts .' -1 day'));
            $result = $this->getOmronAPIData($omron_access_token, $omron_token_type, $adjusted_since_ts);
            $api_data = json_decode($result, true);

            // Check status
            if ($api_data["status"] !== 0) {
                $this->emDebug("Error fetching data for Omron ID $omron_client_id: " . $api_data["reason"]);
                return [
                    'status' => false,
                    'reason' => $api_data["reason"] ?? 'Unknown error',
                    'errorCode' => $api_data["errorCode"] ?? null,
                ];
            }

            // Process API data if status is successful
            $truncated = $api_data["result"]["truncated"];
            $bp_readings = $api_data["result"]["bloodPressure"];
            $bp_instance_data = $this->getBPInstanceData($record_id);
            $next_instance_id = $bp_instance_data["next_instance_id"];
            $used_bp_id = $bp_instance_data["used_bp_id"];
            $data = [];

            foreach ($bp_readings as $reading) {
                $omron_bp_id = $reading["id"];
                if (in_array($omron_bp_id, $used_bp_id)) continue;

                $data[] = [
                    "redcap_repeat_instance" => $next_instance_id++,
                    "redcap_repeat_instrument" => "bp_readings_log",
                    "record_id" => $record_id,
                    "omron_bp_id" => $omron_bp_id,
                    "bp_reading_ts" => date("Y-m-d H:i:s", strtotime($reading["dateTime"])),
                    "bp_reading_local_ts" => date("Y-m-d H:i:s", strtotime($reading["dateTimeLocal"])),
                    "bp_systolic" => $reading["systolic"],
                    "bp_diastolic" => $reading["diastolic"],
                    "bp_units" => $reading["bloodPressureUnits"],
                    "bp_pulse" => $reading["pulse"],
                    "bp_pulse_units" => $reading["pulseUnits"],
                    "bp_device_type" => $reading["deviceType"],
                ];
            }

            $save_result = \REDCap::saveData($this->enabledProjects["patients"]["pid"], 'json', json_encode($data));
            if (empty($save_result["errors"])) {
                $this->emDebug("Saved Omron BP readings for record_id $record_id");

                // Get the most recent reading if data was saved successfully
                $most_recent_reading = end($bp_readings);

                // Handle pagination if necessary
                if ($truncated) {
                    return $this->recurseSaveOmronApiData($omron_client_id, $most_recent_reading["dateTime"], $token_details);
                } else {
                    $this->evaluateOmronBPavg($record_id);
                    return [
                        'status' => $most_recent_reading,
                    ];
                }
            } else {
                $this->emDebug("Error saving data for record_id $record_id", $save_result["errors"]);
                return ['status' => false, 'reason' => 'Error saving data', 'details' => $save_result["errors"]];
            }
        }
    }

    public function checkBPvsThreshold($bp_data, $target_threshold, $control_condition = 0.6, $external_avg = null) {
        // Filter BP data to the last 2 weeks
        $time_window = strtotime('-2 weeks');
        $recent_bp_data = array_filter($bp_data, fn($bp) => strtotime($bp["bp_reading_ts"]) >= $time_window);

        // Use recent data if there are at least 4 records, otherwise use all data
        $bp_data = count($recent_bp_data) >= 4 ? $recent_bp_data : $bp_data;

        // Use external average if provided and determine if it's above the threshold
        if ($external_avg !== null) {
            return $external_avg > $target_threshold ? $external_avg : false;
        }

        // Organize data into AM/PM readings for each day
        $ampm_data = [];
        foreach ($bp_data as $bp) {
            $date_key = date("ymd", strtotime($bp["bp_reading_ts"]));
            $am_pm_key = date("a", strtotime($bp["bp_reading_ts"]));
            $ampm_data[$date_key][$am_pm_key][] = $bp["bp_systolic"];
        }

        // Calculate daily averages and whether each average is above the threshold
        $total_datapoints = [];
        $above_threshold_counts = 0;
        foreach ($ampm_data as $day_data) {
            foreach (['am', 'pm'] as $period) {
                if (!empty($day_data[$period])) {
                    $average = array_sum($day_data[$period]) / count($day_data[$period]);
                    $total_datapoints[] = $average;
                    $above_threshold_counts += ($average > $target_threshold) ? 1 : 0;
                }
            }
        }

        // Calculate the control threshold and mean
        $required_above_count = floor(count($total_datapoints) * $control_condition);
        $mean_of_data = !empty($total_datapoints) ? round(array_sum($total_datapoints) / count($total_datapoints)) : 0;

        $calcs = array(
            "total data points" => $total_datapoints, 
            "above threshold counts" => $above_threshold_counts,
            "required above count" => $required_above_count,
            "mean of data?" => $mean_of_data
        );
        $this->emDebug('calculated convoluted bp threshold', 
            $calcs,
            $above_threshold_counts >= $required_above_count,
            count($total_datapoints) >= 4
        );
        // Return mean if threshold met; otherwise, return false
        return $above_threshold_counts >= $required_above_count && count($total_datapoints) >= 4 ? $mean_of_data : false;
    }

    public function evaluateOmronBPavg($record_id, $external_avg = null) {
        $this->loadEM();
        $this->emDebug("Starting evaluation for record", $record_id);

        // Prepare parameters to retrieve patient data
        $params = array(
            'project_id'    => $this->enabledProjects["patients"]["pid"],
            'records'       => array($record_id),
            'return_format' => 'json',
            'fields'        => array(
                "record_id", "filter", "patient_bp_target_systolic", "patient_bp_target_diastolic",
                "patient_bp_target_pulse", "custom_target_sys_lower", "custom_target_k_upper",
                "custom_target_k_lower", "custom_target_slowhr", "custom_target_cr", "custom_target_na",
                "current_treatment_plan_id", "patient_treatment_status", "patient_physician_id", "last_update_ts"
            )
        );

        // Retrieve and decode patient data
        $q = \REDCap::getData($params);
        $records = json_decode($q, true);
        $patient = current($records);

        // Extract key patient data
        $provider_id = $patient["patient_physician_id"];
        $current_tree = $patient["current_treatment_plan_id"];
        $current_step = !empty($patient["patient_treatment_status"]) ? $patient["patient_treatment_status"]: 1;
        $target_systolic = $patient["patient_bp_target_systolic"];

        // Set custom target values if available
        $custom_targets = array_filter([
            "sys_avg_lower" => $patient["custom_target_sys_lower"],
            "k_upper"       => $patient["custom_target_k_upper"],
            "k_lower"       => $patient["custom_target_k_lower"],
            "cr_upper"      => $patient["custom_target_cr"],
            "na_lower"      => $patient["custom_target_na"],
            "hr_lower"      => $patient["custom_target_slowhr"],
        ]);
        
       $this->emDebug("Initial data for Patient", array($provider_id,$current_tree,$current_step,$target_systolic));

        if (!empty($target_systolic)) {
            // Retrieve recent BP readings (last 2 weeks)
            //TODO HOW THE HELL DO WE DO THIS IF THERES NO DATA WITHIN 2 weeks?
            $time_window = "-80 weeks";
            $filter = "[bp_reading_ts] > '" . date("Y-m-d H:i:s", strtotime($time_window)) . "'";
            $params = array(
                'project_id'    => $this->enabledProjects["patients"]["pid"],
                'records'       => array($record_id),
                'return_format' => 'json',
                'fields'        => array("record_id", "omron_bp_id", "bp_reading_ts", "bp_systolic", "bp_diastolic", "bp_pulse"),
                'filterLogic'   => $filter
            );

            $q = \REDCap::getData($params);
            $records = json_decode($q, true);
            
            // Calculate mean pulse over the last 2 weeks
            $pulse_sum = array_sum(array_column($records, 'bp_pulse'));
            $pulse_count = count(array_filter(array_column($records, 'bp_pulse')));
            $mean_bp_pulse = $pulse_count > 0 ? $pulse_sum / $pulse_count : null;


            // Retrieve recent lab values (last 2 weeks)
            $filter = "[lab_ts] > '" . date("Y-m-d", strtotime($time_window)) . "'";
            $params = array(
                'project_id'    => $this->enabledProjects["patients"]["pid"],
                'records'       => array($record_id),
                'return_format' => 'json',
                'fields'        => array("record_id", "lab_name", "lab_value", "lab_ts"),
                'filterLogic'   => $filter
            );

            $q = \REDCap::getData($params);
            $labs = json_decode($q, true);
            $lab_values = array_column($labs, 'lab_value', 'lab_name');

            // Extract relevant lab values
            $lab_k  = $lab_values["k"] ?? null;
            $lab_na = $lab_values["na"] ?? null;
            $lab_cr = $lab_values["cr"] ?? null;

            // Access current treatment tree and step logic
            $provider_trees = $this->tree->treeLogic($provider_id);
            $treelogic = $provider_trees[$current_tree];



            $current_tree_step = $treelogic["logicTree"][$current_step];

            // Evaluate side effects based on labs and BP averages
            $side_effects_next_step = $this->evaluateSideEffects($current_tree_step, $lab_k, $lab_na, $lab_cr, $mean_bp_pulse, $external_avg, $custom_targets);

            // $this->emDebug("side effect or check bp avg with bp records", $records);
            
            if ($side_effects_next_step !== null) {
                $next_step = $side_effects_next_step;
            } else {
                // Evaluate BP against threshold if no side effects
                $is_above = $this->checkBPvsThreshold($records, $target_systolic, 0.6, $external_avg);
                if ($is_above) {
                    $next_step = $current_tree_step["bp_status"]["Uncontrolled"] ?? null;
                    $this->emDebug("bp is above threshold!!!", $current_step, $next_step, $current_tree_step["step_id"]);
                }
            }

            // Determine lab requirements and update record with recommendations or lab needs
            if (isset($next_step)) {
                $current_update_ts = date("Y-m-d H:i:s");
                $need_lab = empty($lab_values) || !isset($lab_values["k"], $lab_values["cr"], $lab_values["na"]);

                $filter_tag = $need_lab ? "labs_needed" : "rx_change";
                $data = array(
                    "record_id"              => $record_id,
                    "patient_rec_tree_step"  => !$need_lab ? $next_step : null,
                    "last_update_ts"         => $current_update_ts,
                    "filter"                 => $filter_tag,
                );

                \REDCap::saveData($this->enabledProjects["patients"]["pid"], 'json', json_encode(array($data)), "overwrite");
                $this->emDebug('Save recommendation or lab needed status', $data);

                // Log recommendations if no labs needed
                if ($filter_tag == "rx_change") {
                    $this->emDebug("ok rx_change, logRecommendations", $record_id, $next_step, $current_tree_step, $treelogic, $systolic_mean);
                    $this->logRecommendations($record_id, $next_step, $current_tree_step, $treelogic, $systolic_mean ?? false);
                }
            }
        }
        return;
    }

    private function evaluateSideEffects($treeStep, $k, $na, $cr, $hr, $systolic_average, $custom_targets = array()) {
        // Convert incoming parameters to floats for numeric comparisons
        $values = [
            'k'           => floatval($k),
            'na'          => floatval($na),
            'cr'          => floatval($cr),
            'hr'          => floatval($hr),
            'systolic'    => floatval($systolic_average)
        ];

        // Retrieve and set target limits, preferring custom targets if provided
        $targets = [
            'k_upper'     => floatval($custom_targets['k_upper'] ?? $this->getProjectSetting("lab-target-k-upper")),
            'k_lower'     => floatval($custom_targets['k_lower'] ?? $this->getProjectSetting("lab-target-k-lower")),
            'cr_upper'    => floatval($custom_targets['cr_upper'] ?? $this->getProjectSetting("lab-target-cr")),
            'na_lower'    => floatval($custom_targets['na_lower'] ?? $this->getProjectSetting("lab-target-na")),
            'hr_lower'    => floatval($custom_targets['hr_lower'] ?? $this->getProjectSetting("lab-target-slowhr")),
            'sys_lower'   => floatval($custom_targets['sys_avg_lower'] ?? $this->getProjectSetting("target-sys-lower"))
        ];

        // Define side effect checks with corresponding conditions
        $side_effects_checks = [
            'hypotension'      => ['value' => $values['systolic'], 'condition' => $values['systolic'] < $targets['sys_lower']],
            'hyponatremia'     => ['value' => $values['na'],       'condition' => $values['na'] < $targets['na_lower']],
            'hypokalemia'      => ['value' => $values['k'],        'condition' => $values['k'] < $targets['k_lower']],
            'elevated_cr'      => ['value' => $values['cr'],       'condition' => $values['cr'] > $targets['cr_upper']],
            'hyperkalemia'     => ['value' => $values['k'],        'condition' => $values['k'] > $targets['k_upper']],
            'slow_hr'          => ['value' => $values['hr'],       'condition' => $values['hr'] < $targets['hr_lower']]
        ];

        // Evaluate side effects and return the appropriate step if any condition is met
        foreach ($side_effects_checks as $effect => $check) {
            if (!empty($check['value']) && $check['condition']) {
                $this->emDebug("Side effect detected: $effect with value {$check['value']}", $treeStep);
                return $treeStep["side_effects"][$effect];
            }
        }

        // Return null if no side effects are detected
        return null;
    }

    private function logRecommendations($record_id, $next_step, $current_tree_step, $treelogic, $systolic_mean) {
        $current_meds = implode(", ", $current_tree_step["drugs"]);

        $this->emDebug("Handling recommendation for next step", [
            "current_meds" => $current_meds,
            "next_step" => $next_step
        ]);

        // Check if $next_step is a valid key in the logic tree
        if (!isset($treelogic["logicTree"][$next_step])) {
            $this->emDebug("Next step '{$next_step}' is not a valid tree key (Stop or Refer). Proceeding with default logging.");
            $rec_meds = $next_step; // Save the literal "Stop" or "Refer" as the recommendation
        } else {
            $next_tree_step = $treelogic["logicTree"][$next_step];
            $rec_meds = implode(", ", $next_tree_step["drugs"]);
        }

        // Fetch existing recommendations for this record
        $existing_recommendations = \REDCap::getData($this->enabledProjects["patients"]["pid"], 'array', [
            "record_id" => $record_id
        ]);

        // Check if the current recommendation exists
        foreach ($existing_recommendations as $instance) {
            if (
                $instance["rec_step"] == $next_step &&
                $instance["rec_meds"] == $rec_meds
            ) {
                $this->emDebug("Duplicate recommendation found for record {$record_id}, step {$next_step}");
                return; // Exit to avoid duplication
            }
        }

        // If no duplicates, log the new recommendation
        $next_instance_id = $this->getNextInstanceId($record_id, "recommendations_log", "rec_ts");
        $data = array(
            "record_id"                 => $record_id,
            "redcap_repeat_instance"    => $next_instance_id,
            "redcap_repeat_instrument"  => "recommendations_log",
            "rec_current_meds"          => $current_meds,
            "rec_step"                  => $next_step,
            "rec_meds"                  => $rec_meds,
            "rec_accepted"              => 0,
            "rec_mean_systolic"         => $systolic_mean,
            "rec_ts"                    => date("Y-m-d H:i:s")
        );

        $this->emDebug("Logging new recommendation", $data);
        \REDCap::saveData($this->enabledProjects["patients"]["pid"], 'json', json_encode(array($data)), "overwrite");
    }


    //FOR TESTING SCRIPT
    public function evaluateOmronBPavg_2($patientData, &$state) {
        $this->loadEM();

        $target_systolic    = $patientData["target_systolic"];
        $systolic_average   = $patientData["systolic_average"];
        $k_value            = $patientData["k"];
        $cr_value           = $patientData["cr"];
        $na_value           = $patientData["na"];
        $hr_value           = $patientData["hr"];

        $current_tree       = 1;
        $provider_id        = 2;

        // Assume $state['current_step'] carries the accepted recommendation step.
        $current_step       = $state['current_step'] ?? 0;
        $provider_trees     = $this->tree->treeLogic($provider_id);
        $treelogic          = $provider_trees[$current_tree];

        // OK EVAULAUTE SIDE EFFECTS FIRST!
        // BUT IM NOT SURE THATS A GOOD IDEA FOR THE MAIN evaluateOmronBPavg casue what if lab values are year old and doesnt change ?
        // CHECK FOR K, CR, NA SIDE EFFECTS FIRST
        // THEN FALL BACK TO REGULAR "UNcontrolled"

        if($current_step != "Refer" && $current_step != "Stop"){
            $provider_trees     = $this->tree->treeLogic($provider_id);
            $treelogic          = $provider_trees[$current_tree];
            $current_tree_step  = $treelogic['logicTree'][$current_step];
            print_r($current_tree_step);

            //GET PATIENTS CUSTOM TARGETS IF ANY
            //            $custom_targets = array(
            //                "k_upper"   => 5.5,
            //                "k_lower"   => 3.5,
            //                "cr"        => 2.0,
            //                "na"        => 135,
            //                "hr"        => 55,
            //                "sys_avg"   => 105
            //            );
            $custom_targets= array();

            $side_effects_next_step = $this->evaluateSideEffects($current_tree_step, $k_value, $na_value, $cr_value, $hr_value, $systolic_average, $custom_targets);
            $is_above_threshold     = $this->checkBPvsThreshold(array(), $target_systolic, .6, $systolic_average );

            if($side_effects_next_step !== null) {
                $state['current_step'] = $side_effects_next_step;
            }elseif ($is_above_threshold) {
                $uncontrolled_next_step = array_key_exists("Uncontrolled", $current_tree_step["bp_status"]) ?  $current_tree_step["bp_status"]["Uncontrolled"] : $current_step;
                $state['current_step'] = $uncontrolled_next_step;
            }
        }

        print_r("<h5>Next Step : " . $state['current_step'] . "</h5>");

        return $state;
    }




	public function communicationsCheck(){
		$this->loadEM();

		//every 30 minutes, gather all "unread" communications
		//get the patient id
		//if side effect
		//eval current step based on the side effectg
		//make recoomendation or flag patient

		$filter	= "[message_read] <> 1";
		$fields	= array("record_id","patient_side_fx","extras_patient_input","patient_physician_id","current_treatment_plan_id","patient_treatment_status","patient_rec_tree_step","filter");
		$params	= array(
			'project_id'	=> $this->enabledProjects["patients"]["pid"],
			'return_format' => 'json',
			'fields'        => $fields,
			'filterLogic'   => $filter
		);
		$q 			= \REDCap::getData($params);
		$records 	= json_decode($q, true);


		$side_fx_key_map = array();
		$side_fx_key_map[1] = "cough";
		$side_fx_key_map[2] = "rash_other";
		$side_fx_key_map[3] = "breast_discomfort";
		$side_fx_key_map[4] = "rash_other";
		$side_fx_key_map[5] = "asthma";
		$side_fx_key_map[6] = "slow_hr";

		//When getData with mixed data from stand alone instrument and repeating, the repeating wont have the data from the standalone, so need to buffer it into array
		foreach($records as $comm){
			$instr 		= $comm["redcap_repeat_instrument"];
			$record_id  = $comm["record_id"];

			if($instr !== "communications"){
				$patient_baseline_data[$record_id] = $comm;
				continue;
			}

			$instance_id 	= $comm["redcap_repeat_instance"];
			$free_text 		= $comm["extras_patient_input"]; //nothing can be ddone wiht this

			//GET THE PATIENT TREE, AND CURRENT STEP, WONT BE IN THS REPEAT RECORD, WILL NEED TO PULL FROM STANDALONE RECORDS BUFFERED IN $patient_baseline_data
			$provider_id 	= $patient_baseline_data[$record_id]["patient_physician_id"];
			$current_tree 	= $patient_baseline_data[$record_id]["current_treatment_plan_id"];
			$current_step 	= $patient_baseline_data[$record_id]["patient_treatment_status"];
			$current_rec 	= $patient_baseline_data[$record_id]["patient_rec_tree_step"]; //if they alreayd have a rec? do what?
			$current_filter = $patient_baseline_data[$record_id]["filter"];

			//CYCLE THROUGH THESEE IF ANY SIDE FX, MEASURE AGAINST CURRENT STEPS SIDDEFX RULESS and THROW RECCOMENDATION
			$survey_side_fx 	= array();
			foreach(range(1, 6) as $number){
				$side_fx_check 	= "patient_side_fx___" .$number ;
				$side_fx 		= $comm[$side_fx_check];

				if($side_fx){
					//meassure against tree
					array_push($survey_side_fx,$number);
				}
			}

			if(empty($survey_side_fx)){
				$but = !empty($free_text) ? " but they did put '$free_text' in the 'Other SideFX' field" : null;
				$this->emDebug("patient $record_id has no sidefx" . $but);
				continue;
			}else{
				$provider_trees 		= $this->tree->treeLogic($provider_id);
				$treelogic 				= $provider_trees[$current_tree];
				$current_tree_step 		= $treelogic["logicTree"][$current_step];

				$current_step_sidefx 	= $current_tree_step["side_effects"];
				$this->emDebug("$record_id has side fx", $survey_side_fx, $current_step_sidefx);

				$possible_recs = array();
				foreach($survey_side_fx as $sfx){
					$patient_side_fx = $side_fx_key_map[$sfx];

					//check it against the side effects inthe current tree step
					$rec_step = $current_step_sidefx[$patient_side_fx];
					array_push($possible_recs,$rec_step);

					$this->emDebug("this patient has side fx : $patient_side_fx and is recommended to go to step : $rec_step");
					if(strpos(strtoupper($rec_step),"STOP") > -1){
						$possible_recs = array($rec_step);
						break;
					}
				}

				if(!empty($possible_recs)){
					$this->emDebug("this patient has a side fx RX CHANGE!!!", $possible_recs);
					foreach($possible_recs as $rec){
						//if side fx has this:
						//"rash_other" => array("Uncontrolled, K < 4.5" => 69, "Uncontrolled, K > 4.5" => 65),
						// then need to get their lab values

						//if patient only h as one side fx rec, easy , we make their recommendation

						//if patient rec is text and not digit, then what happens?
						$uncontrolled_next_step 		= $rec;
						$uncontrolled_Kplus_next_step 	= array_key_exists("Uncontrolled, K > 4.5", $rec) ?  $rec["Uncontrolled, K > 4.5"] : null;
						$uncontrolled_Kminus_next_step 	= array_key_exists("Uncontrolled, K < 4.5", $rec) ?  $rec["Uncontrolled, K < 4.5"] : null;

						$current_update_ts		= date("Y-m-d H:i:s");
						$lab_values 			= array();
						$need_lab 				= false;
						if(!empty($uncontrolled_Kplus_next_step) || !empty($uncontrolled_Kminus_next_step)){
							//UNCONTROLLED STEP HAS A LAB CHECK (K) OR a POSSIBLE ELEVATED CR Side EFFECT
							//NEED RECENT LABS ( 2 weeks )
							$this->emDebug("possible labs needed, K or CR" , $uncontrolled_Kplus_next_step, $uncontrolled_Kminus_next_step);
							$filter = "[lab_ts] > '" . date("n/j/y", strtotime('-2 weeks')) . "'";
							$params	= array(
								'project_id'	=> $this->enabledProjects["patients"]["pid"],
								'records' 		=> array($record_id),
								'return_format' => 'json',
								'fields'        => array("record_id", "lab_name", "lab_value", "lab_ts"),
								'filterLogic' 	=> $filter
							);
							$q 		= \REDCap::getData($params);
							$labs	= json_decode($q, true);
							if(!empty($labs)){
								foreach($labs as $lab){
									$lab_values[$lab["lab_name"]] = $lab["lab_value"];
								}
							}

							if(empty($lab_values)){
								$need_lab = true;
							}elseif(!empty($uncontrolled_Kplus_next_step) || !empty($uncontrolled_Kminus_next_step)){
								//then set the uncontrolled_next_step
								$this->emdebug("this is K check step, if have K, then use that as recommended step");
								if(isset($lab_values["k"])){
									$uncontrolled_next_step = $lab_values["k"] > 4.5 ? $uncontrolled_Kplus_next_step : $uncontrolled_Kminus_next_step;
								}else{
									$need_lab = true;
								}
							}
						}

						$filter_tag = $need_lab ? "labs_needed" : "rx_change";
						$data 		= array(
							"record_id"             	=> $record_id,
							"patient_rec_tree_step" 	=> (!$need_lab ? $uncontrolled_next_step : null),
							"last_update_ts"			=> $current_update_ts,
							"filter"      				=> $filter_tag,
						);
						// $r = \REDCap::saveData($this->enabledProjects["patients"]["pid"], 'json', json_encode(array($data)), "overwrite" );
						$this->emDebug("update patient baseline data", $data);

						//SAVE THE RECOMMENDATION STEP WHETHER IT WILL BE ACCEPTED OR NOT
						if($filter_tag == "rx_change"){
							$current_meds 	= implode(", ",$current_tree_step["drugs"]);
							$next_tree_step = $treelogic["logicTree"][$uncontrolled_next_step];
							$rec_meds 		= implode(", ",$next_tree_step["drugs"]);

							$next_instance_id 				= $this->getNextInstanceId($record_id, "recommendations_log", "rec_ts");
							$data = array(
								"record_id"             	=> $record_id,
								"redcap_repeat_instance" 	=> $next_instance_id,
								"redcap_repeat_instrument" 	=> "recommendations_log",
								"rec_current_meds" 			=> $current_meds,
								"rec_step" 					=> $next_tree_step["step_id"],
								"rec_meds" 					=> $rec_meds,
								"rec_accepted"				=> 0,
								"rec_ts"					=> $current_update_ts
							);
							// $r = \REDCap::saveData($this->enabledProjects["patients"]["pid"], 'json', json_encode(array($data)), "overwrite" );
							$this->emDebug("store a recommendation log!",  $data);
						}
					}
				}
			}

			//UPDATE THE INSTANCE AS "read"
			$data 		= array(
				"record_id"             	=> $record_id,
				"redcap_repeat_instrument"	=> "communications",
				"redcap_repeat_instance" 	=> $instance_id,
				"message_read"				=> 1,
			);
			// $r = \REDCap::saveData($this->enabledProjects["patients"]["pid"], 'json', json_encode(array($data)));
		}
	}

	// contact patient by email / text if available
	public function contactPatient($record_id, $subject, $msg){
		$this->loadEM();

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
		$this->loadEM();

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
        $next_id = \REDCap::reserveNewRecordId($pid);
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
		$fields	= array("record_id","patient_fname","patient_lname", "omron_client_id","omron_access_token","omron_refresh_token", "omron_token_expire", "omron_token_type");
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
		$this->loadEM();

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
                $new_token_details      = $this->getOrRefreshOmronAccessToken($omron_refresh_token, true);
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
    public function dailyOmronDataPull($since_today = null, $testMode = false) {
        // Get patients with tokens
        $patients_with_tokens = $this->getPatientsWithTokens();
        $since_today = $since_today ?? date("Y-m-d", strtotime("-3 days"));
        if ($testMode) {
            $since_today = date("Y-m-d", strtotime("-100 days"));
        }
        $data = [];

        ini_set('max_execution_time', 300);
        foreach ($patients_with_tokens as $patient) {
            //            $this->emDebug("patient", $patient);
            // Ensure provider_id and omron_client_id are valid
            if (empty($patient["omron_client_id"])) {
                $this->emDebug("Skipping record due to missing omron_client_id for record_id: " . ($patient["record_id"] ?? 'unknown'));
                continue;  // Skip this patient if essential data is missing
            }

            // Extract required fields
            $omron_client_id = $patient["omron_client_id"];
            $record_id = $patient["record_id"];
            $expiration_date = $patient["omron_token_expire"];

            // Attempt to save Omron data and get status
            try {
                $result = $this->recurseSaveOmronApiData($omron_client_id, $since_today);
                $this->emDebug("omron api status?", $result);
                $status = is_array($result) && isset($result['status']) ? $result['status'] : false;
            } catch (Exception $e) {
                $this->emDebug("Error processing record_id $record_id: " . $e->getMessage());
                $status = false;  // Set status to false if an error occurs
            }

            // Collect data for display if in testMode
            if ($testMode) {
                $data[] = [
                    'record_id' => $record_id,
                    'patient_name' => $patient["patient_fname"] . " " . $patient["patient_lname"],
                    'omron_id' => $omron_client_id,
                    'expiration_date' => $expiration_date,
                    'status' => $status
                ];
            }

            // Log success or failure for each patient
            if ($status) {
                $this->emDebug("BP data for $since_today was successfully downloaded for record_id $record_id");
            } else {
                $this->emDebug("Failed to download BP data for record_id $record_id");
            }
            usleep(100000);
        }

        // Return collected data if in testMode
        if ($testMode) {
            return $data;
        }
    }

    // cron to refresh omron access tokens expiring within 48 hours
    public function htnAPICron() {
        $projects = $this->framework->getProjectsWithModuleEnabled();
        $urls = array(
            $this->getUrl('cron/refresh_omron_tokens.php', true, true),
            $this->getUrl('cron/daily_omron_data_pull.php', true, true)
        );

        foreach ($projects as $index => $project_id) {
            $mode = $this->getProjectSetting("em-mode", $project_id);
            if($mode != "patients"){
                continue;
            }

            foreach ($urls as $url) {
                $thisUrl = $this->correctUrlParams($url, $project_id);
                $this->emDebug("Requesting URL: $thisUrl");
                try {
                    $client = new \GuzzleHttp\Client();
                    $response = $client->request('GET', $thisUrl, [
                        \GuzzleHttp\RequestOptions::SYNCHRONOUS => true,
                    ]);

                    $this->emDebug("Response received: " . $response->getBody()->getContents());
                } catch (\GuzzleHttp\Exception\ClientException $e) {
                    $this->emDebug("HTTP request failed: " . $e->getMessage());
                    $this->emDebug("Response: " . $e->getResponse()->getBody()->getContents());
                }
            }
        }
    }

    /**
     * Ensure the URL is correctly formatted with 'NOAUTH=1'.
     */
    private function correctUrlParams($url, $project_id) {
        $urlComponents = parse_url($url);
        parse_str($urlComponents['query'], $params);
        $params['NOAUTH'] = '1';
        $params['pid'] = $project_id;

        $urlComponents['query'] = http_build_query($params);
        return $this->buildUrl($urlComponents);
    }

    private function buildUrl($urlComponents) {
        return $urlComponents['scheme'] . '://' . $urlComponents['host'] . $urlComponents['path'] . '?' . $urlComponents['query'];
    }


    public function daily_survey_check(){
		$projects 	= $this->framework->getProjectsWithModuleEnabled();
		$urls 		= array(
						$this->getUrl('cron/daily_survey_check.php',true,true)
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

	public function daily_survey_clear(){
		$projects 	= $this->framework->getProjectsWithModuleEnabled();
		$urls 		= array(
						$this->getUrl('cron/daily_survey_clear.php',true,true)
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

	public function communications_check(){
		$projects 	= $this->framework->getProjectsWithModuleEnabled();
		$urls 		= array(
						$this->getUrl('cron/communications_check.php',true,true)
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


