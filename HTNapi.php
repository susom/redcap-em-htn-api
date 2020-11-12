<?php
namespace Stanford\HTNapi;

include_once "emLoggerTrait.php";
include_once 'HTNdashboard.php';
include_once 'HTNTree.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class HTNapi extends \ExternalModules\AbstractExternalModule {

    use emLoggerTrait;

	private $dashboard
			,$tree;

    public function __construct() {
		parent::__construct();
		// Other code to run when object is instantiated
	}
	
	// public function redcap_module_system_enable( $version ) {}
	// public function redcap_module_project_enable( $version, $project_id ) {}
	// public function redcap_module_save_configuration( $project_id ) {}

	// public function redcap_module_link_check_display($link){
	// 	//only show the links in the main project?

	// 	// $pid  = $this->getProjectId();
    //     // $this->emDebug("pid", $pid, $this->patients_project);
	// 	// if($pid == $this->patients_project){
	// 	// 	return $link;
	// 	// }
	// 	return $link;
    // }
	
	//Load the pertinent EM stuff, as well as the broken off Classes for Dashboard and Tree
	public function loadEM(){
		$this->dashboard 	= new \Stanford\HTNapi\HTNdashboard($this);
		$this->tree			= new \Stanford\HTNapi\HTNtree($this);

		// what else?  API stuff?
	}

	//Get All Patients
	public function dashBoardInterface(){
		$this->loadEM();
		
		return $this->dashboard->getAllPatients();
	}





















	
	/*
		BELOW HERE IS ALL THE OMRON AUTHORIZATION WORK FLOW STUFF
	*/
	
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
			$result             = $this->getOmronAPIData($omron_access_token, $omron_token_type, $since_ts);
			$api_data           = json_decode($result, true); 

			$status             = $api_data["status"];
			$truncated          = $api_data["result"]["truncated"];
			$bp_readings        = $api_data["result"]["bloodPressure"];
			$measuremnetCount   = $api_data["result"]["measurementCount"];

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
				}
			
				$r = \REDCap::saveData('json', json_encode($data) );
				if(empty($r["errors"])){
					$this->emDebug("Saved $measurementCount Omron BP readings for RC $record_id");
					
					if($truncated){
						//last bp_reading_ts from the foreach will be the paginating ts
						$this->recurseSaveOmronApiData($omron_client_id, $bp_reading_ts, $token_details);
					}else{
						return true;
					}
				}else{
					$this->emDebug("ERROR trying to save $measurementCount Omron BP readings for RC $record_id", $r["errrors"], $data);
					return false;
				}
        	}
		}
	}

	// get the next instance id (repeating) in bp_readings_log
	public function getBPInstanceData($record_id){
		$filter	= "[omron_bp_id] != '' ";
		$fields	= array("record_id","omron_bp_id");

		$params	= array(
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
			$r = \REDCap::saveData('json', json_encode($data) );
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

	// cron to refresh omron access tokens expiring within 48 hours
	public function htnAPICron(){
		$projects 	= $this->framework->getProjectsWithModuleEnabled();
		$url 		= $this->getUrl('pages/refresh_omron_tokens.php', true); //has to be page
		foreach($projects as $index => $project_id){
			$thisUrl 	= $url . "&pid=$project_id"; //project specific
			$client 	= new \GuzzleHttp\Client();
			$response 	= $client->request('GET', $thisUrl, array(\GuzzleHttp\RequestOptions::SYNCHRONOUS => true));
			$this->emDebug("running cron for refreshOmronAccessTokens() on project $project_id");
		}
	}
}
