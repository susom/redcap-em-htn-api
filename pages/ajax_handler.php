<?php
namespace Stanford\HTNtree;
/** @var \Stanford\HTNtree\HTNtree $module */


if(!empty($_POST)){
    $module->emDebug("refresh dashboard INTF", $_POST);
    $action = $_POST["action"];
    switch($action){
        case "addEditPatient":
            
        break; 

        case "sendAuth":
            //email patient a authorization request and link
            $patient  = $_POST["patient"] ?? null;
            $result = $module->emailOmronAuthRequest($patient);
        break;

        case "markAlertRead":
            $msgs = $_POST["msgs"] ?? null;
            $data = array();
            foreach($msgs as $msg){
                $temp = array(
                    "record_id" => $msg["record_id"],
                    "redcap_repeat_instance" => $msg["instance"],
                    "redcap_repeat_instrument" => "communications",
                    "message_read"  => 1
                );
                $data[] = $temp;
            }
            $result = \REDCap::saveData('json', json_encode($data) );
        break;

        case "addPatientNote":
            
        break;

        case "patient_details":
            //refresh dashboard INTF
            $record_id  = $_POST["record_id"] ?? null;
            $result     = $module->getPatientDetails($record_id);
        break;

        case "example_in_data_var":
            //refresh dashboard INTF
            $upcscan        = $_POST["upcscan"] ?? null;
            $qrscan         = $_POST["qrscan"] ?? null;
            $records        = $_POST["records"] ?? array();
            $mainid         = $_POST["mainid"] ?? null;

            $record_ids     = explode(",",$records);
            foreach($record_ids as $record_id){
                // SAVE TO REDCAP
                $data   = array(
                    "record_id"         => $record_id,
                    "kit_upc_code"      => $upcscan,
                    "kit_qr_input"      => $qrscan,
                    "household_record_id" => $mainid
                );
                $result = \REDCap::saveData('json', json_encode(array($data)) );

                $module->emDebug("i need to add the main record", $data);
            }
        break;

        default:
            //refresh dashboard INTF
            $result = $module->dashBoardInterface();
        break;
    }

    echo json_encode($result);
    exit;
        
}
    