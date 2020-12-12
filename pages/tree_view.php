<?php
namespace Stanford\HTNapi;
/** @var \Stanford\HTNapi\HTNapi $module */

include("components/gl_checklogin.php");

$provider_id    = $_SESSION["logged_in_user"]["record_id"];

if(isset($_POST["action"])){
    $action     = $_POST["action"];

    switch($action){
        case "save_template":
            $module->saveTemplate($provider_id, $_POST);
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

$tpl_record_id  = $tree_id ?? 1;
$tree_logic     = $module->treeLogic($provider_id);
$name_na        = "None Selected";

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
                <h1 class="mt-5 mb-4 mr-3 ml-3 d-inline-block align-middle">Interactive Tree View for : <span class="template_name"></span></h1>
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
                            padding-left:30px;
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

                        #tpl1 + label {
                            border: 2px solid blue;
                            color:blue;
                        }
                        #tpl2 + label {
                            border:2px solid orange;
                            color:orange;
                        }

                        #tpl1:checked + label {
                            background:blue;
                            color:#fff;
                        }
                        #tpl2:checked + label {
                            background:orange;
                            color:#fff;
                        }


                        #template_form{
                            margin:30px; 
                            font-size:130%;
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
                    </style>
                    <div id="template_select" class="container mx-5 ">
                        <div class="row">
                            <h3 class="mb-4 mr-3 ml-3 d-inline-block align-middle">Template Select</h3>
                        </div>

                        <form id="template_form" method="POST">
                            <input type="hidden" name="action" value="save_template"/>
                            <input type="hidden" name="record_id" value="<?=$tpl_record_id?>"/>
                            <div id="templates" class="row">
                                <div><input id="tpl1" name="template_name" value="template_1" type="radio" checked/> <label for="tpl1">1</label></div>
                                <div><input id="tpl2" name="template_name" value="template_2" type="radio"/> <label for="tpl2">2</label></div>
                            </div>
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
                        
                            <div class='btns'>
                                <label><input type="checkbox" id="modify_defaults"> Modify Defaults?</label>  <button type='submit' class="btn btn-primary" id="save_template">Save As Is</button>
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
    $(".btns input").click(function(){
        if($(this).prop("checked")){
            // $("#template_form").removeClass("hide");
            $(".class_drug select").each(function(){
                $(this).prop("disabled",false);
            });
        }else{
            // $("#template_form").addClass("hide");
            $(".class_drug select").each(function(){
                $(this).prop("selected",false);
                $(this).find("option[value='" + $(this).data("initial_value") + "']").prop("selected",true);
                $(this).removeClass("modified");
                $(this).prop("disabled",true);
                $("#save_template").removeClass("btn-danger").text("Save As Is");
            });
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
    // BUILD TREE SHOULD HAVE EVERY PERMUTATION + NEXT STEP + SIDE-EFFECT BRANCHES

    //make initial call to dashboard to grab data and draw out pertinent UI
    var urls = {
         "ajax_endpoint" : '<?=$module->getURL("endpoints/ajax_handler.php", true, true);?>'
        ,"anon_profile_src" : '<?=$module->getUrl('assets/images/icon_anon.gif')?>'
        ,"ptree_url" : '<?=$module->getUrl('pages/tree_view.php', true, true)?>'
        
    };

    var patient     = <?= json_encode($patient) ;?>;
    var raw_json    = <?= json_encode($tree_logic) ;?>;
    var tree        = new treeLogic(raw_json, patient, urls);
    tree.startAttachTree();
});
</script>