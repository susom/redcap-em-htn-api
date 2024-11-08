<?php
namespace Stanford\HTNapi;
/** @var \Stanford\HTNapi\HTNapi $module */

$startTS = microtime(true);
$data = $module->dailyOmronDataPull(null, true);

foreach ($data as &$record) {
    if (is_array($record['status']) && isset($record['status']['id'])) {
        // If 'status' contains data, create a bullet list in HTML
        $status = $record['status'];
        $record['status_text'] = "<ul>" .
            "<li><b>Date/Time: " . date("F j, Y, g:i a", strtotime($status['dateTime'])) . "</b></li>" .
            "<li>Systolic: " . $status['systolic'] . " " . $status['bloodPressureUnits'] . "</li>" .
            "<li>Diastolic: " . $status['diastolic'] . " " . $status['bloodPressureUnits'] . "</li>" .
            "<li>Pulse: " . $status['pulse'] . " " . $status['pulseUnits'] . "</li>" .
            "<li>Device Type: " . $status['deviceType'] . "</li>" .
            "</ul>";
    } else {
        // If 'status' is false, set 'status_text' to "No data"
        $record['status_text'] = "<b class='problem'>No data in last 100 days</b>";
    }
}
?>
    <style>
        #omronStatusTable_wrapper{
            padding-right:20px;
        }
        #omronStatusTable{
            width:100% !important;
            ul{
                margin:0;
                padding:0;
                list-style:none;
            }
        }
        b.problem{
            color:red
        }
    </style>
    <table id="omronStatusTable"></table>
    <script>
        $(document).ready(function() {
            const tableData = <?= json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
            $('#omronStatusTable').DataTable({
                data: tableData,
                columns: [
                    { title: "Record ID", data: "record_id" },
                    { title: "Patient Name", data: "patient_name" },
                    { title: "Omron ID", data: "omron_id" },
                    { title: "Expiration Date", data: "expiration_date" },
                    { title: "Last Data", data: "status_text" }  // Use status_text directly without rendering
                ]
            });
        });
    </script>



    <?php
$module->emLog("dailyOmronDataPull() page time : " . microtime(true) - $startTS );




