<?php
namespace Stanford\HTNapi;
/** @var \Stanford\HTNapi\HTNapi $module */

//Build the OMRON generic OAUTH URL
$client_id      = $module->getProjectSetting("omron-client-id");
$client_secret  = $module->getProjectSetting("omron-client-secret");
$oauth_url      = $module->getProjectSetting("omron-auth-url");
$oauth_postback = $module->getProjectSetting("omron-postback");
$oauth_scope    = $module->getProjectSetting("omron-auth-scope");
$record_id      = 1; //this needs to be dynamic per Patient

$oauth_params   = array(
    "client_id"     => $client_id,
    "response_type" => "code",
    "scope"         => $oauth_scope,
    "redirect_uri"  => $oauth_postback
);
$oauth_params["state"] = $record_id;
$oauth_url .= "/connect/authorize?". http_build_query($oauth_params);

//IT IS A _GET request
//Once they LOGIN on the OMRON side, it WILL POST BACK to $oauth_postback URL /redirect.php

//SINCE IT IS GENERIC, Lets Just make a single URL that Doctors can Copy and Paste To their Patients
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
            background:url(<?= $module->getURL("pages/logo_heartex.png")?>) no-repeat;
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
            padding:20px; 
            margin:0 0 20px; 
        }

        .well .btn{
            display:block; 
            width:50%;
            margin:0 auto;
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
                <p>In order for Stanford and your Provider to have access to your Blood Pressure readings.  You will be asked to Login to your Omron account on the following page.  It will inform you that the <b>[Stanford Hypertension Study]</b> is requesting authorization to your data.  Please allow it.</p>
                
                <a class="btn" href="<?= $oauth_url ?>">Go To the Omron Authorization Page</a>
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

