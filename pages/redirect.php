<?php
namespace Stanford\HTNapi;
/** @var \Stanford\HTNapi\HTNapi $module */

if(!empty($_REQUEST)){
    $error      = isset($_REQUEST["error"]) ? $_REQUEST["error"] : null;
    $acode      = isset($_REQUEST["code"]) ? $_REQUEST["code"] : null;
    $record_id  = $_REQUEST["state"] ??  null;
    $oauth_url  = $module->getOAUTHurl($record_id);

    if($error){
        $module->emDebug("Authorization Failed", $error);
    }else{
        //if no 
        if($acode && $record_id){
            $raw        = $module->getOrRefreshOmronAccessToken($acode);
            $postback   = json_decode($raw, true);
            $module->emDebug("Authorization Granted??? here is the post back", $postback);

            if( !array_key_exists("error", $postback) ){
                $id_token           = isset($postback["id_token"]) ? $postback["id_token"] : null;
                $omron_client_id    = null;
                if($id_token){
                    $id_details         = $module->decodeJWT($id_token);
                    $omron_client_id    = $id_details["sub"]; //patient uniuqe omron id
                }

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
                    if($access_token){
                        //dont pass token details (even though we have them) because need to run first pass with no "since" in the recurseive funciton 
                        //TODO change this to $omron_client_id
                        $success = $module->recurseSaveOmronApiData($access_token);
                
                        if($success){
                            $module->emDebug("First API Pull of Historical BP Data for patient RC $record_id");
                        }else{
                            $module->emDebug("ERROR FAILED : First API Pull of Historical BP Data for patient RC $record_id");
                        }
                    }
                }else{
                    $error = $r["errors"];
                }
            }else{
                $error = $postback["error"];
            }
        }else{
            $error = "Record not found";
        }
    }
}

/*
When our users upload device data to the Omron Wellness solution, your application will be sent an upload notification. 
at which point your application may make a pull request using our API to receive the new data. 
In addition, your application may make an initial pull request to load historic data upon the initial user connection.
We currently offer two types of data through our API service: Blood Pressure Readings & Activity Metrics

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
<html lang="en" >
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="Irvin Szeto">
    <style>
        body {
            font-family: Helvetica, Arial, Tahoma;
            font-size:100%;
            padding:0; margin:0;
            color:#333;
        }
        header{
            background:#0072bc;
            width:100%;
        }
        footer{
            background:#0072bc;
            position:fixed;
            bottom:0; left:0;
            width:100%;
            color:#fff;
        }
        .container{
            width:1140px; 
            margin:0 auto;
            padding:20px; 
            overflow:hidden;
        }
        #main { margin-bottom:55px; }
        .omron_logo{
            float:left;
            background:url(https://ohi-oauth.numerasocial.com/static/omron-logo.png) no-repeat;
            background-size:contain;
            width:107px;
            height:23px;  

            border-right:1px solid #fff;
            padding-right:20px;
            margin-right:20px; 
        }
        .heartex_logo{
            float:left;
            background:url(<?= $module->getURL("assets/images/logo_heartex_trans.png")?>) no-repeat;
            background-size:contain;
            width:100px;
            height:23px; 
        }

        .well{
            width:50%;
            margin:0 auto;
            border-radius:3px;
            border:1px solid #ddd;
            margin-top:50px; 
            padding-bottom:40px; 
        }

        .well h3{
            background:#f5f5f5;
            margin:0; 
            padding:10px 20px; 
            border-radius:3px 3px 0 0;
            border-bottom:1px solid #ddd;
            font-size:
        }

        .well p {
            padding:20px 20px 0; 
            margin:0 0; 
        }

        .well .btn{
            display:block; 
            width:50%;
            margin:20px auto 0;
            padding: 10px 20px; 
            background:#0072bc; 
            border-radius:3px;
            color:#fff; 
            text-decoration:none; 
        }
    </style>
</head>
<body>

    <header>
        <div class="container">
            <div class="omron_logo"></div>
            <div class="heartex_logo"></div>
        </div>
    </header>
    <div id="main">
        <div class="container">
            <div class="well">
                <h3>Omron + Heartex Study : Data Use Authorization</h3>
                <?php
                    if($error && $error == "access_denied"){
                        echo "<p>You have denied access to your Omron Data.</p>";
                        echo "<p>In order to participate in the <b>[Stanford Hypertension Study]</b>, it is essential to allow access to this data.  Please allow it.</p>";
                        echo "<a class='btn' href='$oauth_url'>Go To the Omron Authorization Page</a>";
                    }else if(!$error){
                        echo "<p>Thank you for granting your provider access to your Omron Data!<br/>
                        You may now close this browser window at this time.</p>";
                    }else{
                        echo "<p>We are sorry, there was an error : <b>'$error'</b><br/>
                        Please try the link again at a later time.</p>";
                        echo "<a class='btn' href='$oauth_url'>Go To the Omron Authorization Page</a>";
                    }
                ?>  
            </div>
        </div>
    </div>
    <footer>
        <div class="container">
            <copyright>© Stanford University 2020</copyright>
        </div>
    </footer>
</body>
</html>