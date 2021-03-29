<?php
namespace Stanford\HTNtree;
/** @var \Stanford\HTNtree\HTNtree $module */

header("Access-Control-Allow-Origin: *");

if(!empty($_POST)){
    // $module->emDebug("refresh dashboard INTF", $_POST);
    $action = $_POST["action"];
    switch($action){
        case "update_cr_reading":
            $lab = "cr";

            $record_id  = $_POST["record_id"] ?? null;
            $reading    = $_POST["reading"] ?? null;

            $result     = $module->updateLabReading($record_id, $lab, $reading);
        break;

        case "update_k_reading":
            $lab = "k";
            
            $record_id  = $_POST["record_id"] ?? null;
            $reading    = $_POST["reading"] ?? null;

            $result     =$module->updateLabReading($record_id, $lab, $reading);
        break;

        case "manual_eval_bp":
            $record_id  = $_POST["record_id"] ?? null;
            $module->evaluateOmronBPavg($record_id);

            $result = array("yay we did it");
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

        case "patient_details":
            //refresh dashboard INTF
            $record_id  = $_POST["record_id"] ?? null;
            $result     = $module->getPatientDetails($record_id);
        break;

        case "delete_patient":
            //refresh dashboard INTF
            $record_id  = $_POST["record_id"] ?? null;
            $result     = $module->flagPatientForDeletion($record_id);
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

        case "send_and_accept":
            $patient    = $_POST["patient"] ?? null;
            $result     = $module->sendToPharmacy($patient);
        case "accept_rec":
            $patient    = $_POST["patient"] ?? null;
            $result     = $module->acceptRecommendation($patient);
        break;

        case "decline_rec":
            $patient    = $_POST["patient"] ?? null;
            $result     = $module->declineRecommendation($patient);
        break;

        case "new_patient_consent":
            $provider_id    = !empty($_POST["provider_id"]) ? filter_var($_POST["provider_id"], FILTER_SANITIZE_NUMBER_INT) : null;
            $result         = $module->newPatientConsent($provider_id);
        break;

        case "send_patient_consent":
            $patient_id     = !empty($_POST["patient_id"]) ?  filter_var($_POST["patient_id"], FILTER_SANITIZE_NUMBER_INT): null; 
            $consent_url    = !empty($_POST["consent_url"]) ?  filter_var($_POST["consent_url"], FILTER_SANITIZE_URL): null;
            $consent_email  = !empty($_POST["consent_email"]) ?  filter_var($_POST["consent_email"], FILTER_SANITIZE_EMAIL): null;
            $result         = $module->sendPatientConsent($patient_id, $consent_url , $consent_email);
        break;

        default:
            session_start();
            $_SESSION['logged_in_user'] = $_SESSION['logged_in_user'];
            $provider_id                = $_POST["record_id"];
            
            //refresh dashboard INTF
            $result         = $module->dashBoardInterface($provider_id,$_SESSION["logged_in_user"]["super_delegate"]);
        break;
    }

    echo json_encode($result);
    exit;
        
}
    