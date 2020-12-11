<?php
namespace Stanford\HTNapi;
/** @var \Stanford\HTNapi\HTNapi $module */

// This needs to be whitelisted with OMRON dev, currently manual process. 

// Takes raw data from the request
$json = file_get_contents('php://input') ?? array();

// Converts it into a PHP object
$data = json_decode($json, true);

if(!empty($data)){
    // $module->emDebug("ah ha it came in raw");
    $_POST = $data;
}

if( !empty($_POST) ){
    $module->emDebug("Ping to New Data Hook", $_POST);

    //id, timestamp
    $omron_client_id = $_POST["id"] ?? null;
    $new_data_ts     = $_POST["timestamp"] ?? null;  //not as granular so should be ok

    if($omron_client_id){
        $success = $module->recurseSaveOmronApiData($omron_client_id, $new_data_ts);
        if($success){
            // If Omron gets this They won't fire the webhook again
            header("HTTP/1.1 200 OK");
        }
    }
}
// If no 200 recieved, they will try again , 1 x based on time limit set during sign up process(?did i do this already?)
?>
