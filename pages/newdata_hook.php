<?php
namespace Stanford\HTNapi;
/** @var \Stanford\HTNapi\HTNapi $module */

//This might need to be whitelisted with OMRON dev.  Might need to shift this to "redirect.php" which is already the postback for AUTH/token getting
//you will need to implement a web service endpoint in your system to accept an HTTP POST request containing the user id and timestamp of the latest update.
if(!empty($_POST)){
    //id, ts
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
