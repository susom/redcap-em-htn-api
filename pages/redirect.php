<?php
namespace Stanford\HTNapi;
/** @var \Stanford\HTNapi\HTNapi $module */

if(!empty($_REQUEST)){
    $module->emDebug("POSTBACK FROM OMRON AUTH REQUEST", $_REQUEST);

    $error = isset($_REQUEST["error"]) ? $_REQUEST["error"] : null;
    $acode = isset($_REQUEST["code"]) ? $_REQUEST["code"] : null;

    if($acode){
        $postback = $module->getOrRefreshOmronAccessToken($acode);
        $module->emDebug("Authorization Granted", $acode, $postback);

        $id_token           = $postback["id_token"];
        $record_id          = $postback["state"];
        $id_details         = $module->decodeJWT($id_token);
        $omron_client_id    = $id_details["sub"]; //patient uniuqe omron id

        $access_token       = $postback["access_token"];
        $refresh_token      = $postback["refresh_token"];
        $token_type         = $postback["token_type"]; //always gonna be "Bearer"
        $token_expire       = date("Y-m-d H:i:s", time() + $postback["expires_in"]); 
        
        $data = array(
            "record_id"             => $record_id,
            "omron_client_id"       => $omron_client_id,
            "omron_access_token"    => $access_token,
            "omron_refresh_token"   => $refresh_token,
            "omron_token_expire"    => $token_expire,
            "omron_token_type"      => $token_type
        );
        $r  = \REDCap::saveData('json', json_encode(array($data)) );
        if(empty($r["errors"])){
            $module->emDebug("record $record_id saved", $data);

            // NOW DO FIRST PULL OF HIstorical PAtient BP DATA if ANY
            if($omron_client_id){
                //dont pass token details (even though we have them) because need to run first pass with no "since" in the recurseive funciton 
                $success = $module->recurseSaveOmronApiData($omron_client_id);
        
                if($success){
                    $module->emDebug("First API Pull of Historical BP Data for patient RC $record_id");
                }else{
                    $module->emDebug("ERROR FAILED : First API Pull of Historical BP Data for patient RC $record_id");
                }
            }
        }
    }

    if($error){
        $module->emDebug("Authorization Failed", $error);
    }
}


/*
When our users upload device data to the Omron Wellness solution, your application will be sent an upload notification, at which point your application may make a pull request using our API to receive the new data. In addition, your application may make an initial pull request to load historic data upon the initial user connection.
We currently offer two types of data through our API service: Blood Pressure Readings
Activity Metrics

Once registered, Omron will provide you with a unique Application ID and Secret Key that is unique to your application. This information will insure your application has the correct level of access to the APIs and help secure communication between the 2 platforms. Your application will be assigned an Application ID and Secret Key for use in the Omron Wellness development environment for initial integration and testing. When approved for a production Go-Live, a separate Application ID and Secret Key for our production environment.

From within your partner application, there will need to be a mechanism to redirect your user to a web page with a specific URL (called the Authorization Request) to initiate the authorization process. This web page will allow the Omron Wellness user to login to their account, view information about the connection with your application, and either approve or deny the connection.
In order to create this special URL, you will first need to know the host name of our OAuth server, which can be found below according to the environment you are targeting:

Environment

Hostname

staging (used for all testing during development and even post-production)

https://ohi-oauth.numerasocial.com

production

https://oauth.omronwellness.com


*/
?>

<p>When our users upload device data to the Omron Wellness solution, your application will be sent an upload notification, at which point your application may make a pull request using our API to receive the new data. In addition, your application may make an initial pull request to load historic data upon the initial user connection.
We currently offer two types of data through our API service: Blood Pressure Readings & Activity Metrics</p>

<p>Once registered, Omron will provide you with a unique Application ID and Secret Key that is unique to your application. This information will insure your application has the correct level of access to the APIs and help secure communication between the 2 platforms. Your application will be assigned an Application ID and Secret Key for use in the Omron Wellness development environment for initial integration and testing. When approved for a production Go-Live, a separate Application ID and Secret Key for our production environment.</p>
