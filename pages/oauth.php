<?php
namespace Stanford\HTNapi;
/** @var \Stanford\HTNapi\HTNapi $module */

//Build the OMRON generic OAUTH URL
$record_id      = $_REQUEST["state"] ?? null;
$oauth_url      = $module->getOAUTHurl($record_id);
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
            width:100%; 
            margin:0 auto;
            padding:20px 0; 
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
            margin-left:20px;
        }
        copyright{
            margin-left:20px;
        }
        .heartex_logo{
            float:left;
            background:url(<?= $module->getURL("assets/images/logo_heartex_trans.png", true, true)?>) no-repeat;
            background-size:contain;
            width:100px;
            height:23px; 
        }

        .well{
            width:50%;
            min-width:320px;
            max-width:640px; 
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
            text-align:center;
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
            <div class="well col-sm-6 offset-sm-3">
                <h3>Omron + Heartex Study : Data Use Authorization</h3>
                <?php
                    if($record_id){
                        echo "<p>In order for Stanford and your Provider to have access to your Blood Pressure readings.  You will be asked to Login to your Omron account on the following page.  It will inform you that the <b>[Stanford Hypertension Study]</b> is requesting authorization to your data.  Please allow it.</p>
                        <a class='btn' href='$oauth_url'>Go To the Omron Authorization Page</a>";
                    }else{
                        echo "<p>Sorry there was an error, please contact your provider to request a new link.</p>";
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

