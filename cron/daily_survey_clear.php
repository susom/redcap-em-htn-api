<?php
namespace Stanford\HTNapi;
/** @var \Stanford\HTNapi\HTNapi $module */

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["fileToUpload"])) {
    $file = $_FILES["fileToUpload"];

    // Check if file was uploaded without errors
    if ($file["error"] == 0) {
        $filename = $file["name"];
        $fileType = $file["type"];
        $fileTempPath = $file["tmp_name"];

        // Check if the file is a CSV
        if ($fileType == 'text/csv' || $fileType == 'application/vnd.ms-excel') {
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


                $count = 0;
                $state = ['current_step' => 0]; // Start with the initial step
                foreach($rows as $patient_data){
                    echo "<pre>";
                    print_R("<h4>Current Step ".$state["current_step"]." Input : " . implode(" , ",$patient_data) ."</h4>");

                    $state = $module->evaluateOmronBPavg_2($patient_data, $state);
                    echo "</pre>";
                    $count++;
                    if($count > 50){
                        break;
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
