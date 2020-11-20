<?php
namespace Stanford\HTNapi;
/** @var \Stanford\HTNapi\HTNapi $module */

$_POST["id"] ="af2fc6f3-3c34-4bb0-ae1d-be3270e1793c";
$_POST["timestamp"] ="2020-11-13T10:29:46";

// This needs to be whitelisted with OMRON dev, currently manual process. 
if(!empty($_POST)){
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
