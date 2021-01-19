<?php
namespace Stanford\HTNapi;
/** @var \Stanford\HTNapi\HTNapi $module */

include("components/gl_checklogin.php");

$provider_id    = $_SESSION["logged_in_user"]["record_id"];

if(isset($_POST["action"])){
    $action     = $_POST["action"];

    switch($action){
        case "save_template":
            if($_POST["confirm_template_name"] != $_POST["template_name"]){
                $_POST["record_id"] = null;
                $module->emDebug("no match, new record");
            }
            $module->saveTemplate($provider_id, $_POST);
            unset($_SESSION["logged_in_user"]["provider_trees"]);
        break;
    }
}

if(!empty($_GET["patient"])){
    $patient_id = $_GET["patient"];
    $patient    = $module->getPatientDetails($patient_id);

    $patient_name       = $patient["patient_fname"] . " "  . $patient["patient_mname"] . " " . $patient["patient_lname"];
    $tree_id            = $patient["current_treatment_plan_id"];
    $current_step_idx   = $patient["patient_treatment_status"];
    $rec_step_idx       = $patient["patient_rec_tree_step"];
}

if(empty($_SESSION["logged_in_user"]["provider_trees"])){
    $provider_trees = $module->getProviderTrees($provider_id);
    $_SESSION["logged_in_user"]["provider_trees"] = $provider_trees;
}
$provider_trees = $_SESSION["logged_in_user"]["provider_trees"];


$tree_logic     = $module->treeLogic($provider_id);
$name_na        = "None Selected";

$available_trees= array(1, 2, 3, 4);

// $tree_active    = "active";
?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
<?php include("components/gl_meta.php") ?>
<link href="<?php echo $module->getUrl('assets/styles/treeview.css') ?>" rel="stylesheet">
<style>
    b.drug {
        padding-left:23px;
        background:url(<?= $module->getUrl('assets/images/icon_drug.png') ?>) 0 0 no-repeat;
        background-size:18px 18px;
    }
    .step_drugs li.increase{
        background:url(<?= $module->getUrl('assets/images/icon_arrow_up.png') ?>) 0 4px no-repeat;
        background-size:18px 18px;
    }
    .step_drugs li.decrease{
        background:url(<?= $module->getUrl('assets/images/icon_arrow_down.png') ?>) 0 4px no-repeat;
        background-size:18px 18px;
    }

    .step_drugs li.add{
        background:url(<?= $module->getUrl('assets/images/icon_add_plus.png') ?>) 0 4px no-repeat;
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
                <h1 class="col-sm-12 mt-5 mb-4 mx-3 align-middle tree_header">Interactive Tree View <span class="template_name"></span></h1>
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
                            background:url(<?php echo $module->getUrl('assets/images/icon_anon.gif') ?>) 0 0 no-repeat;
                            background-size:contain;
                            width:40px; height:40px; 
                        }

                        .navbar-brand {
                            display:inline-block;
                            background:url(<?php echo $module->getUrl('assets/images/logo_heartex.gif') ?>) 0 0 no-repeat;
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

                        #templates {
                            margin:0 0 30px; 
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

                        #template_form{
                            font-size:130%;
                        }

                        .class_drug.alias {
                            margin-top:20px; 
                        }

                        .class_drug label{
                            display:inline-block;
                            width:200px ; 
                            font-weight:bold; 
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
                    <div id="template_select" class="container mx-5 ">
                        <div class="row">
                            <h3 class="mb-4 mr-3 ml-3 d-inline-block align-middle">Customize Drugs for Templates</h3>
                        </div>

                        <form id="template_form" method="POST">
                            <input type="hidden" id="hidden_action" name="action" value="save_template"/>
                            <input type="hidden" id="hidden_record_id" name="record_id" value=""/>
                            <input type="hidden" id="hidden_template_name" name="confirm_template_name" value=""/>
                            <div id="templates" class="row">
                                <div class="col-sm-12 mb-6">
                                    <h5>Choose a saved custom tree to view or edit</h5>
                                    <select id="alias_select">
                                        <option value="99">View/Edit Saved Templates</option>
                                        <?php
                                            foreach($provider_trees as $idx => $ptree){
                                                echo "<option value='".$ptree["template_id"]."' data-rc='".$ptree["record_id"]."' data-raw='".json_encode($ptree)."'>".$ptree["template_name"]."</option>";
                                            }
                                        ?>
                                    </select>
                                </div>

                                <div class="col-sm-7 mt-5 row">
                                    <h5 class="col-sm-12"><b>OR</b> Choose a default tree template to customize</h5>
                                    <?php
                                        foreach($available_trees as $i=> $tree){
                                            $disabled = ($i > 0) ? "disabled" : "";
                                            echo '<div class="col-sm-3"><input class="tpl" '.$disabled.' id="tpl'.$tree.'" name="template_id" value="'.$tree.'" type="radio"/> <label for="tpl'.$tree.'">'.$tree.'</label></div>';
                                        }
                                    ?>
                                    <em>*Not all trees are available yet.</em>
                                </div>
                            </div>
                            
                            <div class="drugs">
                                <div class="class_drug">
                                    <label>ACE-I HCTZ</label> 
                                    <select name="acei_diuretic class">
                                        <option value="LISINOPRIL - HCTZ">LISINOPRIL - HCTZ</option>
                                        <option value="CAPTOPRIL - HCTZ">CAPTOPRIL - HCTZ</option>
                                        <option value="BENAZEPRIL - HCTZ">BENAZEPRIL - HCTZ</option>
                                        <option value="FOSINOPRIL - HCTZ">FOSINOPRIL - HCTZ</option>
                                        <option value="QUINAPRIL - HCTZ">QUINAPRIL - HCTZ</option>
                                        <option value="ENALAPRIL - HCTZ">ENALAPRIL - HCTZ</option>
                                    </select>
                                </div>
                                <div class="class_drug">
                                    <label>ACE-I</label> 
                                    <select name="acei_class">
                                        <option value="LISINOPRIL">LISINOPRIL</option>
                                        <option value="CAPTOPRIL">CAPTOPRIL</option>
                                        <option value="BENAZEPRIL">BENAZEPRIL</option>
                                        <option value="FOSINOPRIL">FOSINOPRIL</option>
                                        <option value="QUINAPRIL">QUINAPRIL</option>
                                        <option value="ENALAPRIL">ENALAPRIL</option>
                                    </select>
                                </div>
                                <div class="class_drug">
                                    <label>ARB HCTZ</label> 
                                    <select name="arb_diuretic_class">
                                        <option value="LOSARTAN - HCTZ">LOSARTAN - HCTZ</option>
                                    </select>
                                </div>
                                <div class="class_drug">
                                    <label>ARB</label> 
                                    <select name="arb_class">
                                        <option value="LOSARTAN">LOSARTAN</option>
                                    </select>
                                </div>
                                <div class="class_drug">
                                    <label>Diuretic</label> 
                                    <select name="diuretic_class">
                                        <option value="HCTZ">HCTZ</option>
                                    </select>
                                </div>
                                <div class="class_drug">
                                    <label>Spirnolactone</label> 
                                    <select name="spirno_class">
                                        <option value="Spirnolactone">Spirnolactone</option>
                                    </select>
                                </div>
                                <div class="class_drug">
                                    <label>Eplerenone</label> 
                                    <select name="epler_class">
                                        <option value="Eplerenone">Eplerenone</option>
                                    </select>
                                </div>
                                <div class="class_drug">
                                    <label>CCB</label> 
                                    <select name="ccb_class">
                                        <option value="AMLODIPINE">AMLODIPINE</option>
                                        <option value="DILTIAZEM">DILTIAZEM</option>
                                        <option value="VERAPAMIL">VERAPAMIL</option>
                                    </select>
                                </div>
                                <div class="class_drug">
                                    <label>BB</label> 
                                    <select name="bb_class">
                                        <option value="BISOPROLOL">BISOPROLOL</option>
                                    </select>
                                </div>
                                <div class="alias class_drug">
                                    <label>Template Alias</label> 
                                    <input type="text" id="template_name" name="template_name"/>
                                </div>
                                <div class='btns'>
                                    <label><input type="checkbox" id="modify_defaults"> Modify Defaults?</label>  <button type='submit' class="btn btn-primary" id="save_template">Save As Is</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include("components/gl_foot.php"); ?>
    <script src="<?php echo $module->getUrl('assets/scripts/template.js') ?>" crossorigin="anonymous"></script>
    <script src="<?php echo $module->getUrl('assets/scripts/treeLogic.js') ?>" crossorigin="anonymous"></script>
    <script src="<?php echo $module->getUrl('assets/scripts/treeLogicStep.js') ?>" crossorigin="anonymous"></script>
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
            var _opt = $(this).find(":selected");
            var raw  = _opt.data("raw");

            $("#hidden_record_id").val(_opt.data("rc")); //actual redcap record id
            $("#tpl"+raw["template_id"]).prop("checked", true); //template id (the base templates 1 - 5)
            $("#template_name").val(_opt.text()); // provider alias for this configuration
            $("#hidden_template_name").val(_opt.text());

            for(var i in raw){
                if(i.indexOf("_class") > -1){
                    console.log("wtf", i, raw[i]); 
                    $("select[name='"+i + "'] option[value='"+raw[i]+"']").attr("selected",true);
                }
            }

            // raw["acei_class"]
            // raw["arb_class"]
            // raw["bb_class"]
            // raw["ccb_class"]
            // raw["diuretic_class"]
            // raw["epler_class"]
            // raw["provider_id"]
            // raw["spirno_class"]

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
    // $("#templates input:first-child").attr("checked",true);


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



    // BUILD TREE SHOULD HAVE EVERY PERMUTATION + NEXT STEP + SIDE-EFFECT BRANCHES

    //make initial call to dashboard to grab data and draw out pertinent UI
    var urls = {
         "ajax_endpoint" : '<?=$module->getURL("endpoints/ajax_handler.php", true, true);?>'
        ,"anon_profile_src" : '<?=$module->getUrl('assets/images/icon_anon.gif')?>'
        ,"ptree_url" : '<?=$module->getUrl('pages/tree_view.php', true, true)?>'
        ,"patient_backlink" : '<?=$module->getUrl('pages/dashboard.php', true, true)?>'
    };

    var patient     = <?= json_encode($patient) ;?>;
    var raw_json    = <?= json_encode($tree_logic) ;?>;
    var tree        = new treeLogic(raw_json, patient, urls);
    tree.startAttachTree();
});
</script>