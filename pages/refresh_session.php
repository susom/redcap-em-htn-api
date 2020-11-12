<?php
namespace Stanford\HTNtree;
/** @var \Stanford\HTNtree\HTNtree $module */


if(!empty($_POST)){
    $module->emDebug("refresh dashboard INTF", $_POST);

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
}
    