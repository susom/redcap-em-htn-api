<?php
namespace Stanford\HTNtree;
/** @var \Stanford\HTNtree\HTNtree $module */

if (!isset($module)) {
    $module = $this;
}

$API_TOKEN  = "7FDC083BFED646678E9613FFB48F203D";
$API_URL    = "http://localhost/api/";
$trees      = $module->getPrescriptionTrees($API_TOKEN,$API_URL);
$druglist   = $module->getDrugList();

//Handle the Ajax Submit New/Edit
$ajax       = $_POST["data"] ?? false;
if($ajax){
    $action = $_POST["action"] ?? false;
    if($action == "get_tree"){
        $record_id  = $ajax;
        $tree       = $module->getPrescriptionTree($record_id, $API_TOKEN, $API_URL);

        if(empty($record_id)){
            $tree = array();
        }

        exit(json_encode($tree));
    }else{
        //save_tree
        $response   = $module->savePrescriptionTree($ajax,$API_TOKEN, $API_URL);

        exit(json_encode($response));
    }
}

$edit_tree      = $_GET["record_id"] ?? null;
$edit_show      = is_null($edit_tree) ? "" : "class='show'";
?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <?php include("gl_meta.php") ?>
    <style>
        body { color:#2f5f73; }
        body, .footer { background-color:#e8f0f2; }
        h1,h2,h3,h4,h5,h6{ font-family:georgia; letter-spacing:2px; }
        nav{ background-color:#2f5f73; }
        nav .nav a{
            color:#fff;
            font-size:130%;
            font-weight:500;
        }
        nav .dropdown button{ 
            background:none; 
        }
        nav .dropdown-toggle::after{
            display: inline-block;
            margin-left: .5em;
            vertical-align:middle;
            border:none;
            background:url(<?php echo $module->getUrl('assets/images/icon_anon.gif', true, true) ?>) 0 0 no-repeat;
            background-size:contain;
            width:40px; height:40px; 
        }

        .navbar-brand {
            display:inline-block;
            background:url(<?php echo $module->getUrl('assets/images/logo_heartex.gif', true, true) ?>) 0 0 no-repeat;
            /*background: url(https://identity.stanford.edu/img/stanford-university-white.png) 0 50% no-repeat;*/
            background-size: contain;
            text-indent: -5000px;
            width: 170px;
            height: 39px;
        }
        .project_title{
            color:#fff; 
            margin:0; 
        }
        .tab-content{
            border-color: #DEE1E5;
        }
        #myTabContent {
            min-height:500px;
        }
        #patient_baseline label.d-block{ font-size:92%; }
        #manual_diastolic, #manual_systolic {
            border-style: solid;
            border-width: 1px;
            border-color: #DEE1E5;
            font-size:92%; 
        }
        .emtext{
            font-size:77%;
        }
        .dose_actions a{
            width: 15px;
            height: 15px;
            text-decoration: none;
            line-height: 85%;
        }
        a.dupe_prev_dose { 
            line-height:100%; 
        }
        .drug_dose{ 
            width:82px;
            font-size:85%;
        }
        .drug_dose:last-child {
            width:86px;
        }
        #plan_summary .drug_dose,
        #dose_head .drug_dose {
            height:auto;
            min-height:25px;
        }
        #dose_head .drug_dose input{
            width: 30px;
            font-size: 85%; 
        }
        #med_steps > .drug_step ~ .drug_step .trashit { 
            display:inline-block; 
            opacity:1;  
        }
        .drug_name .trashit { 
            display:none;
            opacity:0;
            width: 15px;
            height: 15px;            
            text-decoration: none;
            line-height: 65%;
        }
        .dose_ladder .drug_dose .dose_selects{
            display:none;
            font-size:100%;
        }
        .dose_ladder .drug_dose .dose_selects input{
            font-size: 85%;
            width: 100%;
        }
        .dose_ladder .drug_dose:first-child .dupe_prev_dose{
            display:none;
        }
        .med_notes {
            font-size:77%;
            line-height:1;
        }
        .alt_med_custom_dose{
            font-size:77%;
        }
        #plan_summary .drug_name, 
        #dose_head .drug_name{
            font-size:85%;
        }
        #plan_summary .drug_dose{
            font-size:65%;
        }
        #plan_summary .drug_dose span{
            display:block;
            background: #ffc;
            color: #333;
        }

        .drug_dose.active {  
            background:#ffc !important;
        }


        .drug_dose select{
            font-size:92%;
            display:block; 
        }
        .drug_dose i{
            display:block;
            font-size:77%;
            font-style:normal;  
        }
        
        .current_tree{ display:none; }
        label + em{
            display:block;
            font-weight:normal;
            margin-left: 10px;
            font-size: 85%;
        }

        .filtered,
        .patient_count{ 
            font-size:120%;
            border-left:2px solid #2f5f73;
            height:50px;
            line-height:300%;
        }

        .filtered {
            line-height:initial;
            height:auto;
        }
        .filtered i {
            font-style:normal;
            font-size:85%;
            font-weight:normal;
        }

        #overview .stat{
            background:none;
            
            border-width:5px;
            border-style: solid;
            border-radius:180px;

            min-width:180px;
            min-height:180px;
        }
        .stat::after{
            content:"";
            background:url(<?php echo $module->getUrl('assets/images/icon_info.gif', true, true) ?>) 0 0 no-repeat;
            background-size:contain;
            width: 20px;
            height: 20px;
            display: block;
            margin: 0 auto;
            cursor:pointer;
        }
        .stat i{ font-style:normal;  }
        .stat .h1{
            font-size:500%;
            line-height:100%; 
        }
        .stat-title{
            line-height:100%;
        }
        .stat-text{
            font-style:normal; 
            font-size:85%;
        }

        .uncontrolled_above .stat{ border-color:#b30004;  }
        .uncontrolled_above .h1,
        .uncontrolled_above.filtered {color:#b30004; border-left-color:#b30004;}

        .uncontrolled_within .stat{ border-color:#e66c19;  }
        .uncontrolled_within .h1,
        .uncontrolled_within.filtered {color:#e66c19; border-left-color:#e66c19;}

        .controlled .stat{ border-color:#89ce11; }
        .controlled .h1 {color:#89ce11; border-left-color:#89ce11;}

        .data_needed .stat{ border-color:#e7b403; }
        .data_needed .h1 {color:#e7b403; border-left-color:#e7b403;}

        .provider_flagged .stat{ border-color:#0f72e9; }
        .provider_flagged .h1 {color:#0f72e9; border-left-color:#0f72e9;}

        .unpicked .stat { border-color: transparent;  }

        .patient_list,
        .patient_details{
        }

        .patient_tab{
            position:relative;
            width:calc(100% - 20px);
            border-left:20px solid #b30004;
            cursor:pointer;
        }
        .patient_tab.active{
            background:#b30004;
            color:#FFF;
        }
        .patient_tab.active::after{
            content: "";
            position: absolute;
            right: -30px;
            top: 50%;
            margin-top: -20px;
            width: 20px;
            height: 20px;
            border: 15px solid #b30004;
            border-top: 15px solid transparent !important;
            border-bottom: 15px solid transparent !important;
            border-right: 15px solid transparent !important;
        }
        .patient_tab dt img{ width:80%; max-width:76px; }


        .patient_tab.uncontrolled_above,
        .patient_tab.uncontrolled_above.active::after { border-color:#b30004; }
        .patient_tab.uncontrolled_above.active{ background-color:#b30004; }
        .patient_tab.uncontrolled_within,
        .patient_tab.uncontrolled_within.active::after { border-color:#e66c19; }
        .patient_tab.uncontrolled_within.active{ background-color:#e66c19; }
        .patient_tab.controlled,
        .patient_tab.controlled.active::after { border-color:#89ce11; }
        .patient_tab.controlled.active{ background-color:#89ce11; }
        .patient_tab.data_needed,
        .patient_tab.data_needed.active::after { border-color:#e7b403; }
        .patient_tab.data_needed.active{ background-color:#e7b403; }
        .patient_tab.provider_flagged,
        .patient_tab.provider_flagged.active::after { border-color:#0f72e9; }
        .patient_tab.provider_flagged.active{ background-color:#0f72e9; }


        .patient_profile figcaption{
            font-family:georgia;
        }
        .patient_profile img{
            max-width: 185px;
            max-height: 185px;
            min-height:185px;
        }
        .patient_status{
            position:absolute;
            left:15px;
            top:15px;
        }
        .add_notes{
            position:absolute;
            top:15px;
            right:15px;
        }

        .patient_data{
            border-radius:10px 10px 10px 10px;
            background:#e8f0f2;
        }
        .patient_data .content{
            min-height:15px;
        }
        section {
            /*border-left:3px solid #e8f0f2;*/
            /*border-right:3px solid #e8f0f2;*/
        }

        section.information{

        }

        section h3{
            font-family:inherit;
        }
        .patient_details dl dt { min-width:140px; }
        .patient_details dl dd { color:#a65c43; font-weight:bold; white-space: nowrap; }

        #dose_head + #tree_rows_above {
            display:table-row;
        }
        #tree_rows_above {
            display:none;
        }
    </style>
    <!-- Custom styles for this template -->
</head>
<body class="d-flex flex-column h-100">
    <?php include("gl_topnav.php") ?>

    <!-- Begin page content -->
    <main role="main" class="flex-shrink-0">
        <div id="patients" class="container mt-5">
            <div class="row">
                <h1 class="mt-5 mb-5 mr-3 ml-3 d-inline-block align-middle">Prescription Trees</h1>
                <div class="filter d-inline-block mt-5 pl-3">
                    <b class="uncontrolled_above filtered d-inline-block align-middle pl-3 mr-3"><span>Uncontrolled BP</span> <i class='d-block'>Above Threshold</i></b>
                </div>
            </div>

            <div class="row patient_body">
                <div class="patient_details bg-light rounded col-sm-12 p-3">
                    <div class="patient_data py-2">
                        <section class="prescription_tree">
                            <h3 class="text-center p-3">
                                New Tree
                                <select id="current_treatment_plan_id" name="current_treatment_plan_id" class="d-inline-block float-right h6 mt-2">
                                    <option value="99">-View/Edit Prescription Trees-</option>
                                    <?php
                                    foreach($trees as $record_id => $tree){
                                        $selected = $record_id == $edit_tree ? "selected" :"";
                                        echo "<option value='$record_id' $selected>$tree</option>";
                                    }
                                    ?>
                                </select>
                            </h3>

                            <div id="prescription_tree" class="content bg-light mh-10 mx-1">
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include("gl_foot.php"); ?>
</body>
</html>
<script src="<?php echo $module->getUrl('assets/scripts/template.js', true, true) ?>" crossorigin="anonymous"></script>
<script src="<?php echo $module->getUrl('assets/scripts/makeTree.js', true, true) ?>" crossorigin="anonymous"></script>
<script src="<?php echo $module->getUrl('assets/scripts/treeViewer.js', true, true) ?>" crossorigin="anonymous"></script>
<script>
$(document).ready(function(){
    // tree nav
    $("#current_treatment_plan_id").change(function(e){
        // get record_id of tree , fetch it and display it
        var record_id = $(this).val();
        $.ajax({
            type:'POST',
            data: {"action" : "get_tree", "data" : record_id},
            dataType: 'json',
            success:function(result){
                console.log(result);
                var tree = new prescriptionTree(result, record_id);
            }
        });
        e.preventDefault();
    });

    // kick off a prescription tree new or edit.
    $("#current_treatment_plan_id").change();
});
function clear_elements(element, exception_classes) {
    $(".hide").removeClass("hide");
    $(".delete_this").remove();

    element.find(':input').each(function() {
        for(var i in exception_classes){
            if($(this).attr("class") == exception_classes[i]){
                return;
            }
        }

        switch(this.type) {
            case 'password':
            case 'text':
            case 'textarea':
            case 'file':
            case 'select-one':
            case 'select-multiple':
            case 'date':
            case 'number':
            case 'tel':
            case 'email':
                $(this).val('');
                break;
            case 'checkbox':
            case 'radio':
                this.checked = false;
                break;
        }
    });
}

//google jquery templates, might be useful
</script>