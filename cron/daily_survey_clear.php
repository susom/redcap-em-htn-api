<?php
namespace Stanford\HTNapi;
/** @var \Stanford\HTNapi\HTNapi $module */

function print_rr($dump){
    echo "<pre>";
    print_r($dump);
    echo "</pre>";
}



// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["fileToUpload"])) {
    $file = $_FILES["fileToUpload"];

    // Check if file was uploaded without errors
    if ($file["error"] == 0) {
        $filename = $file["name"];
        $fileType = $file["type"];
        $fileTempPath = $file["tmp_name"];

        // Check if the file is a CSV
        if ($fileType == 'text/csv' || $fil0eType == 'application/vnd.ms-excel') {
            // Open the file for reading
            if (($handle    = fopen($fileTempPath, "r")) !== FALSE) {
                $headers    = fgetcsv($handle);  // Read the first line as headers
                $rows       = [];
                $new_data   = [];
                while (($data = fgetcsv($handle)) !== FALSE) {

                    $temparr = [];
                    foreach($headers as $key=> $header){
                        $temparr[$header] = $data[$key];
                    }
                    $rows[] = $temparr;  // Read each line of data

                }
                fclose($handle);


//                // GENERATEING TREE LOGIC PHP ARRAY FROM XCEL
//                $medications = array(
//                    "ACEI" => array(
//                        "LISINOPRIL 10mg",
//                        "LISINOPRIL 20mg",
//                        "LISINOPRIL 40mg",
//                    ),
//                    "ARB" => array(
//                        "LOSARTAN 50mg",
//                        "LOSARTAN 100mg",
//                    ),
//                    "DIURETIC" => array(
//                        "HCTZ 12.5mg",
//                        "HCTZ 25mg",
//                    ),
//                    "SPIRNO" => array(
//                        "SPIRONOLACTONE 12.5mg",
//                        "SPIRONOLACTONE 25mg",
//                        "EPLERENONE 1xDaily",
//                        "EPLERENONE 2xDaily"
//                    ),
//                    "CCB" => array(
//                        "AMLODIPINE 2.5mg",
//                        "AMLODIPINE 5mg",
//                        "AMLODIPINE 10mg"
//                    ),
//                    "BB" => array(
//                        "BISOPROLOL 2.5mg",
//                        "BISOPROLOL 5mg",
//                        "BISOPROLOL 7.5mg",
//                        "BISOPROLOL 10mg"
//                    )
//                );
//
//// Drug mapping function
//                function mapDrugsToArrayPosition($drugName, $dose) {
//                    global $medications;
//                    foreach ($medications as $category => $drugs) {
//                        foreach ($drugs as $index => $drug) {
//                            if (stripos($drug, $drugName) === 0 && strpos($drug, (string)$dose) !== false) {
//                                return "'$" . $category . "[" . $index . "]'";
//                            }
//                        }
//                    }
//                    return null; // In case no match is found
//                }
//
//                function escapeValue($value) {
//                    if ($value === 0 || $value === '0') {
//                        return '0';
//                    } elseif (empty($value)) {
//                        return null;
//                    } elseif (is_numeric($value) or $value == "null") {
//                        return $value;
//                    } else {
//                        return '"' . addslashes($value) . '"';
//                    }
//                }
//
//                echo "<pre>";
//                $count = 0;
//                foreach($rows as $row){
//                    $drugs = [];
//                    foreach ($medications as $category => $medList) {
//                        foreach ($medList as $index => $drug) {
//                            $drugName = strtolower(substr($drug, 0, strpos($drug, ' ')));
//                            $dose = floatval($row[$drugName]);
//                            if ($dose != 0 && strpos($drug, (string)$dose) !== false) {
//                                $drugs[] = "$" . $category . "[" . $index . "]";
//                            }
//                        }
//                    }
//
//                    $sideEffects = array(
//                        "hypotension" => $row['hypotension'] ?: 'null',
//                        "hyponatremia" => $row['hyponatremia'] ?: 'null',
//                        "hypokalemia" => $row['hypokalemia'] ?: 'null',
//                        "cough" => $row['cough'] ?: 'null',
//                        "elevated_cr" => $row['elevated cr'] ?: 'null',
//                        "hyperkalemia" => $row['hyperkalemia'] ?: 'null',
//                        "angioedema" => $row['angioedema'] ?: 'null',
//                        "breast_discomfort" => $row['breast_discomfort'] ?: 'null',
//                        "slow_hr" => $row['slow HR'] ?: 'null',
//                        "asthma" => $row['asthma'] ?: 'null',
//                        "rash_other" => $row['rash'] ?: 'null'
//                    );
//
//                    // Function to escape strings and add quotes if necessary
//
//
//                    foreach ($sideEffects as $key => $value) {
//                        $sideEffects[$key] = escapeValue($value);
//                    }
//
//
//                    echo "\$logicTree[] = array(\n" .
//                        "    \"step_id\" => " . $row['step'] . ",\n" .
//                        "    \"drugs\" => array(" . implode(', ', $drugs) . "),\n" .
//                        "    \"bp_status\" => array(\n" .
//                        "        \"Controlled\" => \"Continue current step\",\n" .
//                        "        \"Uncontrolled\" => " . escapeValue($row['Uncontrolled']) . "\n" .
//                        "    ),\n" .
//                        "    \"note\" => \"\",\n" .
//                        "    \"side_effects\" => array(\n";
//
//                    foreach ($sideEffects as $effect => $value) {
//                        echo "        \"$effect\" => " . $value . ",\n";
//                    }
//
//                    echo "    )\n" .
//                        ");\n\n";
//
//                    $count++;
//                    if($count > 5){
//                        break;
//                    }
//                }



                // RUN THROGUH SCRRIPT AND TRACK CURRENT STEP AND RECCOMENDATIONS
                $count = 0;
                $state = ['current_step' => 0]; // Start with the initial step
                foreach($rows as $patient_data){
                    echo "<pre>";
                    print_R("<h4>Current Step ".$state["current_step"]." Input : " . implode(" , ",$patient_data) ."</h4>");

                    $state = $module->evaluateOmronBPavg_2($patient_data, $state);
                    echo "</pre>";
                    $count++;
                    if($count > 1){
//                        break;
                    }
                }
            } else {
                echo "Error opening the file.";
            }
        } else {
            echo "Please upload a CSV file.";
        }
    } else {
        echo "Error: " . $file["error"];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload CSV File</title>
</head>
<body>
    <form action="" method="post" enctype="multipart/form-data">
        <h2>Upload CSV File</h2>
        <input type="file" name="fileToUpload" id="fileToUpload">
        <button type="submit">Upload CSV</button>
    </form>
</body>
</html>
