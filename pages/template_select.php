<?php
namespace Stanford\HTNtree;
/** @var \Stanford\HTNtree\HTNtree $module */
?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <?php include("pages/gl_meta.php") ?>
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
    <!-- Custom styles for this template -->
</head>
<body class="d-flex flex-column h-100">
    <?php include("pages/gl_topnav.php") ?>

    <!-- Begin page content -->
    <main role="main" class="flex-shrink-0">
        <div id="template_select" class="container mt-5">
            <div class="row">
                <h1 class="mt-5 mb-4 mr-3 ml-3 d-inline-block align-middle">Template Select</h1>
            </div>

            <div id="templates" class="row">
                <div><input id="tpl1" name="use_template" type="radio" checked/> <label for="tpl1">1</label></div>
                <div><input id="tpl2" name="use_template" type="radio"/> <label for="tpl2">2</label></div>
            </div>

            <form id="template_form" class="hide">
                <div class="class_drug">
                    <label>ACE-I HCTZ</label> 
                    <select>
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
                    <select>
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
                    <select>
                        <option value="LOSARTAN - HCTZ">LOSARTAN - HCTZ</option>
                    </select>
                </div>
                <div class="class_drug">
                    <label>ARB</label> 
                    <select>
                        <option value="LOSARTAN">LOSARTAN</option>
                    </select>
                </div>
                <div class="class_drug">
                    <label>CCB</label> 
                    <select>
                        <option value="AMLODIPINE">AMLODIPINE</option>
                        <option value="DILTIAZEM">DILTIAZEM</option>
                        <option value="VERAPAMIL">VERAPAMIL</option>
                    </select>
                </div>
                <div class="class_drug">
                    <label>BB</label> 
                    <select>
                        <option value="BISOPROLOL">BISOPROLOL</option>
                    </select>
                </div>
            
                <div class='btns'>
                    <label><input type="checkbox"> Modify Defaults?</label>  <button type='button' class="btn btn-primary" id="save_template">Save As Is</button>
                </div>
            </form>
        </div>
    </main>

    <?php include("pages/gl_foot.php"); ?>
</body>
</html>
<script>
$(document).ready(function(){
    $(".btns input").click(function(){
        if($(this).prop("checked")){
            $("#template_form").removeClass("hide");
            $(".class_drug select").each(function(){
                $(this).prop("disabled",false);
            });
        }else{
            $("#template_form").addClass("hide");
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
});

//google jquery templates, might be useful
</script>