<?php
namespace Stanford\HTNapi;
/** @var \Stanford\HTNapi\HTNapi $module */

include("components/gl_checklogin.php");

$provider_id    = !empty($_SESSION["logged_in_user"]["sponsor_id"]) ? $_SESSION["logged_in_user"]["sponsor_id"] : $_SESSION["logged_in_user"]["record_id"];

//SAVE ACTIONS
if(isset($_POST["action"])){
    $action     = $_POST["action"];

    switch($action){
        case "save_template":
            if($_POST["confirm_template_name"] != $_POST["template_name"]){
                $_POST["record_id"] = null;
                // $module->emDebug("no match, new record");
            }

            $error = $module->saveTemplate($provider_id, $_POST);
            unset($_SESSION["logged_in_user"]["provider_trees"]);
        break;
    }
}

//EDIT PATIENTS
$is_edit = false;
if(!empty($_GET["patient"])){
    $patient_id = $_GET["patient"];
    $patient    = $module->getPatientDetails($patient_id);

    $patient_name       = $patient["patient_fname"] . " "  . $patient["patient_mname"] . " " . $patient["patient_lname"];
    $tree_id            = $patient["current_treatment_plan_id"];
    $current_step_idx   = $patient["patient_treatment_status"];
    $rec_step_idx       = $patient["patient_rec_tree_step"];

    $is_edit            = true;
}

//First Get the Pre made DEFAULT Trees
$default_trees  = $module->getDefaultTrees();

//Then get the Provider Custom Trees
if(empty($_SESSION["logged_in_user"]["provider_trees"])){
    $provider_trees = $module->getDefaultTrees($provider_id);
    $_SESSION["logged_in_user"]["provider_trees"] = $provider_trees;
}
$provider_trees = $_SESSION["logged_in_user"]["provider_trees"];
$default_trees  = array_merge($default_trees, $provider_trees);

//get All Drug List
$druglist       = $module->getDrugList();

$tree_logic     = $module->treeLogic($provider_id);
$name_na        = "None Selected";

$page_hdr       = $is_edit ? "Interactive Tree View" : "Customize Prescription Tree Medications"; 
// $tree_active    = "active";
?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
<?php include("components/gl_meta.php") ?>
<link href="<?php echo $module->getUrl('assets/styles/treeview.css', true, true) ?>" rel="stylesheet">
<style>
    b.drug {
        padding-left:23px;
        background:url(<?= $module->getUrl('assets/images/icon_drug.png', true, true) ?>) 0 0 no-repeat;
        background-size:18px 18px;
    }
    .step_drugs li.increase{
        background:url(<?= $module->getUrl('assets/images/icon_arrow_up.png', true, true) ?>) 0 4px no-repeat;
        background-size:18px 18px;
    }
    .step_drugs li.decrease{
        background:url(<?= $module->getUrl('assets/images/icon_arrow_down.png', true, true) ?>) 0 4px no-repeat;
        background-size:18px 18px;
    }

    .step_drugs li.add{
        background:url(<?= $module->getUrl('assets/images/icon_add_plus.png', true, true) ?>) 0 4px no-repeat;
        background-size:18px 18px;
    }
</style>
<!-- Custom styles for this template -->
</head>
<body class="d-flex flex-column h-100">
    <?php include("components/gl_topnav.php") ?>

    <!-- Begin page content -->
    <main role="main" class="flex-shrink-0">
        <div class="container mt-5">
            <div class="row">
                <h1 class="col-sm-12 mt-5 mb-4 mx-3 align-middle tree_header"><?=$page_hdr?> <span class="template_name"></span></h1>
            </div>

            <div id="prescription_tree" class="content bg-light mh-10 mx-1">
                <div id="viewer">
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
                    
                        #template_select {
                        }

                        #templates label{
                            width:80px;
                            height:80px; 
                            border-radius:50px; 
                            display:inline-block; 
                            margin:20px 10px; 
                            background:
                        }
                        #templates input{
                            position: absolute;
                            left: -100vw;
                        }

                        #templates label {
                            text-align:center;
                            font-size:40px;
                            line-height:78px;
                            cursor:pointer;
                        }

                        .tpl + label {
                            border: 2px solid blue;
                            color:blue;
                        }
                        .tpl:checked + label {
                            background:blue;
                            color:#fff;
                        }

                        .tpl:disabled + label {
                            border:2px solid #666;
                            color:#666;
                            background:#ccc;
                            cursor:default !important;
                        }
                        .tpl:disabled + label:after{
                            content:"*";
                        }

                        #template_form{
                            font-size:130%;
                        }

                        .class_drug.alias {
                            margin-top:20px; 
                        }

                        .class_drug label{
                            font-weight:bold; 
                        }
                        .class_drug label em {
                            font-weight:normal; 
                            font-style:normal;
                            font-size:77%;
                            display:block;
                        }
                        .class_drug label em::before{
                            content:"";
                        }
                        .class_drug label em::after{
                            content:""
                        }
                        #template_form .class_drug select.modified{
                            color:red;
                            border:1px solid red; 
                        }

                        #template_form select{
                                color:#2f5f73;
                                padding-left:5px; 
                                padding-right:5px;
                        }

                        #template_form.hide select{
                                border:1px transparent;
                                background:none;

                                -webkit-appearance: none;
                                -moz-appearance: none;      
                                appearance: none;
                        }


                        .btns {
                            margin:20px 0; 
                            font-size:initial; 
                        }
                        .btns label { width:200px; }

                        input:focus,
                        select:focus,
                        textarea:focus,
                        button:focus {
                            outline: none;
                        }

                        #alias_select option{
                            border:1px solid red; 
                        }

                        .drugs {
                            display:none;
                        }
                    </style>
                    <div id="template_select" class="container">
                        <form id="template_form" method="POST" class="col-sm-12">
                            <input type="hidden" id="hidden_action" name="action" value="save_template"/>
                            <input type="hidden" id="hidden_record_id" name="record_id" value=""/>
                            <input type="hidden" id="hidden_template_name" name="confirm_template_name" value=""/>
                            <?php
                                if(!empty($error)){
                                    $greenred   = !empty($error["errors"]) ? "danger" : "success";
                                    $msg        = !empty($error["errors"]) ? $error["errors"] : "Action Successful!";
                                    echo "<p class='alert alert-".$greenred."'>".$msg."</p>";
                                }
                            ?>
                            <div id="templates" class="row p-4 mb-3 border-bottom">
                                <div class="col-sm-12">
                                    <h4 class="text-dark">Select a tree to view or edit medications/dosages</h4>
                                    <p class="text-muted lead small">In the dropdown below, there may be several "default" Prescription Trees with preselected medications and dosages.</p>
                                    
                                    <select id="alias_select" class="form-control">
                                        <option value="99">View/Edit Saved Templates</option>
                                        <?php
                                            foreach($default_trees as $idx => $ptree){
                                                $tree_id = $ptree["tree_meta"]["record_id"];
                                                echo "<option value='".$tree_id."' data-rc='".$tree_id."' data-doses='".json_encode($ptree["doses"])."' data-raw='".json_encode($ptree["tree_meta"])."'>".$ptree["label"]."</option>";
                                            }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="drugs row p-4">
                                <p class="text-muted lead small col-sm-12">You may modify the medications or doses and "save as" a custom labeled Prescription Tree for your patients.</p> 
                                
                                <?php
                                    foreach($druglist as $med_class => $drugs){
                                        $option_str = "";
                                        $dose_str   = "";

                                        $label              = !empty($drugs["label"]) ? " <em>".$drugs["label"]."</em>" : "";
                                        $temp_med_class     = $med_class;
                                        if($med_class == "ARA"){
                                            $temp_med_class = "SPIRNO";
                                        }
                                        if($med_class == "MRA"){
                                            $temp_med_class = "EPLER";
                                        }
                                        $med_class_class    = strtolower($temp_med_class)."_class";
                                        $med_dosage         = strtolower($temp_med_class)."_doses";
                                        foreach($drugs["drugs"] as $drug){
                                            $drug_name      = strtoupper($drug["name"]);
                                            $option_str     .= "<option value='$drug_name' data-dosename='".$med_dosage ."[]' data-dosages='".json_encode($drug["common_dosage"])."' data-unit='".$drug["unit"]."'>$drug_name</option>";
                                            $unit           = $drug["med_unit"];
                                        }

                                        echo '<div class="class_drug col-sm-12 row input-group pb-4">
                                                <label class="col-sm-4">'.$med_class.$label.'</label> 
                                                <div class="col-sm-8 row">
                                                    <select data-med_class="'.$med_class.'" name="'.$med_class_class.'" class="form-control ml-3 col-sm-11 mb-1">
                                                        '.$option_str.'
                                                    </select>
                                                    <div class="doses row col-sm-12"></div>
                                                </div>
                                        </div>';
                                    }
                                ?>
                                <div class="alias class_drug col-sm-12 row input-group  py-3 mb-4 border-top border-bottom">
                                    <label class="col-sm-4">Template Alias</label> 
                                    <div class="col-sm-8">
                                        <input class="form-control" type="text" id="template_name" name="template_name"/>
                                    </div>
                                </div>
                                <div class='btns col-sm-12 text-right pr-5'>
                                    <label class="mr-3"><input type="checkbox" id="modify_defaults"> Modify Defaults?</label>  <button type='submit' class="btn btn-primary btn-lg" id="save_template">Save As Is</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include("components/gl_foot.php"); ?>  
    <script src="<?php echo $module->getUrl('assets/scripts/template.js', true, true) ?>" crossorigin="anonymous"></script>
    <script src="<?php echo $module->getUrl('assets/scripts/treeLogic.js', true, true) ?>" crossorigin="anonymous"></script>
    <script src="<?php echo $module->getUrl('assets/scripts/treeLogicStep.js', true, true) ?>" crossorigin="anonymous"></script>
</body>
</html>
<script>
$(document).ready(function(){
    $("#modify_defaults").click(function(){
        if($(this).prop("checked")){
            // $("#template_form").removeClass("hide");
            $(".class_drug select").each(function(){
                $(this).prop("disabled",false);
            });
            $("#save_template").val("Save As");
        }else{
            // $("#template_form").addClass("hide");
            $(".class_drug select").each(function(){
                $(this).prop("selected",false);
                $(this).find("option[value='" + $(this).data("initial_value") + "']").prop("selected",true);
                $(this).removeClass("modified");
                $(this).prop("disabled",true);
                $("#save_template").removeClass("btn-danger").text("Save As Is");
            });
            $("#save_template").val("Save As Is");
        }
    });

    $(".class_drug select").each(function(){
        $(this).data("initial_value", this.value);
        $(this).prop("disabled",true);

        $(this).change(function(){
            if(this.value !== $(this).data("initial_value") ){
                $(this).addClass("modified");
                displayDosageSlots($(this).find(":checked"));
            }else{
                $(this).removeClass("modified");
            }
            
            if($("select.modified").length){
                $("#save_template").addClass("btn-danger").text("Save Modified Template");
            }else{
                $("#save_template").removeClass("btn-danger").text("Save As Is");
            }
        });
    });

    $("#save_template").click(function(){
        if(!$("#modify_defaults").is(":checked")){
            $("#modify_defaults").trigger("click");
        }
    });
    
    $("#alias_select").change(function(){
        resetDrugs();

        if($(this).find(":selected").length && $(this).find(":selected").val() != 99){            
            var _opt    = $(this).find(":selected");
            var raw     = _opt.data("raw");
            var doses   = _opt.data("doses");

            $("#hidden_record_id").val(_opt.data("rc")); //actual redcap record id
            $("#template_name").val(_opt.text()); // provider alias for this configuration
            $("#hidden_template_name").val(_opt.text());

            for(var i in raw){
                if(i.indexOf("_class") > -1){
                    let med_class = $("select[name='"+i + "']").data("med_class");
                    if(med_class == "ARA"){
                        med_class = "SPIRNO";
                    }
                    if(med_class == "MRA"){
                        med_class = "EPLER"
                    }
                    console.log("selected doses", med_class, doses[med_class]["raw"]);
                    $("select[name='"+i + "'] option[value='"+raw[i]+"']").attr("selected",true);
                    $("select[name='"+i + "'] option[value='"+raw[i]+"']").data("dosages", doses[med_class]["raw"]);
                    displayDosageSlots($("select[name='"+i + "'] option[value='"+raw[i]+"']"));
                    console.log("what the fuck is up with spirono", i, raw[i], raw);
                }
            }

            $(".drugs").slideDown("medium");
            if(!$("#modify_defaults").prop("checked")){
                $("#modify_defaults").trigger("click");
            }
        }else{
            $("#template_name").val("");
            $("#hidden_record_id").val("");
            $(".tpl").attr("checked", false);
        }
    });

    $(".tpl").click(function(){
        resetDrugs();
        $(this).prop("checked", true);
        $(".drugs").slideDown("medium");
    });

    function displayDosageSlots(el){
        let dosages     = el.data("dosages");
        let unit        = el.data("unit");
        let med_class   = el.data("dosename");

        el.parent().next().empty();
        let unitinp = $("<input>").prop("type","hidden").prop("name",med_class).val(unit);
        el.parent().next().append(unitinp);


        console.log("what the fuck up with spirono", med_class, unit, dosages);
        for(let j in dosages){
            let dose    = dosages[j];
            let inp     = $(tree_dosage);
            inp.find("input").attr("name", med_class).val(dose);
            inp.find(".add_on").text(unit);
            el.parent().next().append(inp);
        }
    }

    function resetDrugs(){
        //uncheck template picker
        $(".tpl:checked").prop("checked", false);

        // slide up drugs and reset all selectd
        $(".drugs").slideUp("fast");
        $(".drugs select").prop("selectedIndex", 0);

        // if checked uncheck
        if($("#modify_defaults").prop("checked")){
            $("#modify_defaults").trigger("click");
        }
    }

    <?php
        if(!empty($error)){
    ?>
        setTimeout(function(){
            $(".alert").slideUp("medium");
        },5000);
    <?php    
        }
    ?>

    // BUILD TREE SHOULD HAVE EVERY PERMUTATION + NEXT STEP + SIDE-EFFECT BRANCHES

    //make initial call to dashboard to grab data and draw out pertinent UI
    var urls = {
         "ajax_endpoint" : '<?=$module->getURL("endpoints/ajax_handler.php", true, true);?>'
        ,"anon_profile_src" : '<?=$module->getUrl('assets/images/icon_anon.gif', true, true)?>'
        ,"ptree_url" : '<?=$module->getUrl('pages/tree_view.php', true, true)?>'
        ,"patient_backlink" : '<?=$module->getUrl('pages/dashboard.php', true, true)?>'
    };

    var patient     = <?= json_encode($patient) ;?>;
    var raw_json    = <?= json_encode($tree_logic) ;?>;
    var tree        = new treeLogic(raw_json, patient, urls);
    tree.startAttachTree();
});
</script>