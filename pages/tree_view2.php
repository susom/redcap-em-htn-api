<?php
namespace Stanford\HTNtree;
/** @var \Stanford\HTNtree\HTNtree $module */
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
    </style>
    <!-- Custom styles for this template -->
</head>
<body class="d-flex flex-column h-100">
    <?php include("gl_topnav.php") ?>

    <!-- Begin page content -->
    <main role="main" class="flex-shrink-0">
        <div class="container mt-5">
            <div class="row">
                <h1 class="mt-5 mb-4 mr-3 ml-3 d-inline-block align-middle">Tree View</h1>
            </div>

            <style>
                #prescription_tree{
                    overflow:scroll;
                    height:5000px;
                }
                #viewer {
                    height:5000px;
                    position:relative;
                    background:#fff;
                }

                .tree_step {
                    position:absolute;
                    text-align:center;
                }
                .step_drugs {
                    display:inline-block;
                    list-style:none;
                    margin:0 auto;
                    padding:0;
                    position:relative;
                }
                .tree_step > .step_drugs:after{
                    content:"";
                    position:absolute;
                    bottom:-85px;
                    left:50%;
                    height:80px;
                    border:1px solid #2e5e72;
                }
                .tree_step.closed {
                    display:none;
                }

                .step_drugs li{
                    padding-left:23px;
                    text-align:left;
                }
                b.drug {
                    padding-left:23px;
                    background:url(http://localhost/modules-local/htn_tree_v9.9.9/assets/images/icon_drug.png?1583261227) 0 0 no-repeat;
                    background-size:18px 18px;
                }
                .step_drugs li.increase{
                    background:url(http://localhost/modules-local/htn_tree_v9.9.9/assets/images/icon_arrow_up.png?1583261242) 0 4px no-repeat;
                    background-size:18px 18px;
                }

                .step_drugs li.add{
                    background:url(http://localhost/modules-local/htn_tree_v9.9.9/assets/images/icon_add_plus.png?1583261255) 0 4px no-repeat;
                    background-size:18px 18px;
                }
                .branches {
                    width:500px;
                    margin:40px auto 0;
                    margin-top:40px;
                    height: 50px;
                    border:2px solid #2e5e72;
                    border-bottom:none;
                    position:relative;
                }
                .branches span{
                    position:absolute;
                    bottom:5px;
                    color:#a45c43;
                    font-size:92%;
                    cursor:pointer;
                    padding:1px 3px;
                }
                .branches .nc{
                    left:5px;
                }
                .branches .controlled{
                    left:50%;  margin-left:10px;
                }
                .branches .se{
                    right:5px;
                }
                .branches .eval {
                    position: absolute;
                    left: 50%; bottom: 55px;
                    margin-left: 10px;
                    font-size:92%;
                    font-weight: normal;
                }
                .branches .on {
                    border-radius:3px;
                    background:khaki;
                }

                .next_steps {
                    position:relative;
                    height:20px;
                }
                .next_steps .continue{
                    position:absolute;
                    left:50%; margin-left:-30px;
                    display:none;
                }
                .next_steps .se{
                    position:absolute;
                    right:10px;
                    display:none;
                }
                .next_steps .alert{
                    position: absolute;
                    left: 0; top: 10px;
                }

                .step_1{
                    top:20px;
                }
                .step_2{
                    top:162px;
                }
                .step_3{
                    top:320px;
                }
                .step_4{
                    top:480px;
                }
                .step_5{
                    top:800px;
                }
                .step_6{
                    top:960px;
                }
                .step_7{
                    top:1120px;
                }
                .step_8{
                    top:1280px;
                }
                .step_9{
                    top:1440px;
                }
                .step_10{
                    top:1600px;
                }
            </style>

            <div id="prescription_tree" class="content bg-light mh-10 mx-1">
                <div id="viewer">
                    <div class="tree_step step_1">
                            <ul class="step_drugs">
                                
                            <li><b class="drug">HCTZ 12.5mg</b></li></ul>
                            <div class="branches">
                                <b class="eval">7 days</b>
                                <span class="nc on">BP Not Controlled</span>
                                <span class="controlled">Controlled</span>
                                <span class="se">Side Effect</span>
                            </div>
                            <div class="next_steps">
                                <b class="continue">Continue</b>
                                <ul class="step_drugs se">
                                    
                                <li><b class="drug">Chlorthalidone 20mg</b></li></ul>
                            </div>
                    </div>
                    <div class="tree_step step_2" style="top: 134px;">
                            <ul class="step_drugs">
                                
                            <li class="increase"><b class="drug">HCTZ 25mg</b></li></ul>
                            <div class="branches">
                                <b class="eval">7 days</b>
                                <span class="nc on">BP Not Controlled</span>
                                <span class="controlled">Controlled</span>
                                <span class="se">Side Effect</span>
                            </div>
                            <div class="next_steps">
                                <b class="continue">Continue</b>
                                <ul class="step_drugs se">
                                    
                                <li><b class="drug">Chlorthalidone 20mg</b></li></ul>
                            </div>
                    </div>
                    <div class="tree_step step_3" style="top: 248px;">
                            <ul class="step_drugs">
                                
                            <li class="increase"><b class="drug">HCTZ 50mg</b></li><li class="add"><b class="drug">Lisinopril 10mg</b></li></ul>
                            <div class="branches">
                                <b class="eval">7 days</b>
                                <span class="nc">BP Not Controlled</span>
                                <span class="controlled">Controlled</span>
                                <span class="se on">Side Effect</span>
                            </div>
                            <div class="next_steps">
                                <b class="continue">Continue</b>
                                <ul class="step_drugs se" style="display: block;">
                                    
                                <li><b class="drug">Chlorthalidone 20mg</b></li><li class="add"><b class="drug">Benazaril 2 0mg</b></li></ul>
                            </div>
                    </div>
                    <div class="tree_step step_4">
                            <ul class="step_drugs">
                                
                            <li class="increase"><b class="drug">Lisinopril 20mg</b></li></ul>
                            <div class="branches">
                                <b class="eval">7 days</b>
                                <span class="nc">BP Not Controlled</span>
                                <span class="controlled">Controlled</span>
                                <span class="se">Side Effect</span>
                            </div>
                            <div class="next_steps">
                                <b class="continue">Continue</b>
                                <ul class="step_drugs se">
                                    
                                <li><b class="drug">Benazaril 2 0mg</b></li></ul>
                            </div>
                    </div>
                    <div class="tree_step step_5">
                            <ul class="step_drugs">
                                
                            <li class="increase"><b class="drug">Lisinopril 30mg</b></li></ul>
                            <div class="branches">
                                <b class="eval">7 days</b>
                                <span class="nc">BP Not Controlled</span>
                                <span class="controlled">Controlled</span>
                                <span class="se">Side Effect</span>
                            </div>
                            <div class="next_steps">
                                <b class="continue">Continue</b>
                                <ul class="step_drugs se">
                                    
                                <li><b class="drug">Benazaril 2 0mg</b></li></ul>
                            </div>
                    </div>
                    <div class="tree_step step_6">
                            <ul class="step_drugs">
                                
                            <li class="increase"><b class="drug">Lisinopril 40mg</b></li></ul>
                            <div class="branches">
                                <b class="eval">7 days</b>
                                <span class="nc">BP Not Controlled</span>
                                <span class="controlled">Controlled</span>
                                <span class="se">Side Effect</span>
                            </div>
                            <div class="next_steps">
                                <b class="continue">Continue</b>
                                <ul class="step_drugs se">
                                    
                                <li><b class="drug">Benazaril 2 0mg</b></li></ul>
                            </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include("gl_foot.php"); ?>
</body>
</html>
<script>
$(document).ready(function(){
});

// THIS SHOULD ONLY CONTAIN DRUGS THAT WERE PART OF THE CHOSEN TEMPLATE
var template_drugs {
     "ACEI" : ["Lisinopril 10", "Lisinopril 20", "Lisinopril 40"}
    ,"Diuretic" : ["HCTZ 12.5", "HCTZ 25"]
    ,"Spirno" : ["Spirnolactone 12.5", "Spirnolactone 25"]
    ,"ARB" : ["Losartan 50","Losartan 100"]
    ,"CCB" : ["Amlodipine 2.5", "Amlodipine 5", "Amlodipine 10"]
    ,"BB" : ["Bisoprolol 2.5",  "Bisoprolol 5", "Bisoprolol 7.5", "Bisoprolol 10"]
}

// BUILD TREE SHOULD HAVE EVERY PERMUTATION + NEXT STEP + SIDE-EFFECT BRANCHES
var tree = {
    "steps" : [
        {
            "step_id" : 0,
            "drugs" : [ template_drugs["ACEI"][0], template_drugs["Diuretic"][0] ],
            "condition" : {"uncontrolled" : 1},
            "note" : "",
            "side_effects" : {
                "cough" : 13,
                "elevated_cr" : 28,
                "hyperkalemia" : 28, 
                "slow_hr" : null, 
                "angioedema" : 41,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        {
            "step_id" : 1,
            "drugs" : [ template_drugs["ACEI"][1],template_drugs["Diuretic"][0] ],
            "condition" : {"uncontrolled" : 2},
            "note" : "",
            "side_effects" : {
                "cough" : 14,
                "elevated_cr" : 25,
                "hyperkalemia" : 25, 
                "slow_hr" : null, 
                "angioedema" : 41,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        {
            "step_id" : 2,
            "drugs" : [ template_drugs["ACEI"][1],template_drugs["Diuretic"][1] ],
            "condition" : {"uncontrolled" : 3},
            "note" : "",
            "side_effects" : {
                "cough" : 15,
                "elevated_cr" : 25,
                "hyperkalemia" : 25, 
                "slow_hr" : null, 
                "angioedema" : 41,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        {
            "step_id" : 3,
            "drugs" : [ template_drugs["ACEI"][2],template_drugs["Diuretic"][1] ],
            "condition" : {"uncontrolled" : 4},
            "note" : "",
            "side_effects" : {
                "cough" : 15,
                "elevated_cr" : 64,
                "hyperkalemia" : 25, 
                "slow_hr" : null, 
                "angioedema" : 41,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        {
            "step_id" : 4,
            "drugs" : [ template_drugs["ACEI"][2],template_drugs["Diuretic"][1], template_drugs["CCB"][0] ],
            "condition" : {"uncontrolled" : 5},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : 16,
                "elevated_cr" : null,
                "hyperkalemia" : null, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : {"K < 4.5" : 69, "K > 4.5" : 65},
                "asthma" : null
            }
        },
        {
            "step_id" : 5,
            "drugs" : [ template_drugs["ACEI"][2],template_drugs["Diuretic"][1], template_drugs["CCB"][1] ],
            "condition" : {"uncontrolled" : 6},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : 17,
                "elevated_cr" : null,
                "hyperkalemia" : null, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : {"K < 4.5" : 69, "K > 4.5" : 65},
                "asthma" : null
            }
        },
        {
            "step_id" : 6,
            "drugs" : [ template_drugs["ACEI"][2],template_drugs["Diuretic"][1], template_drugs["CCB"][2] ],
            "condition" : {"K < 4.5" : 7, "K > 4.5" : 9},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : 18,
                "elevated_cr" : null,
                "hyperkalemia" : null, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : {"K < 4.5" : 69, "K > 4.5" : 65},
                "asthma" : null
            }
        },

        //NOW IT GETS WEIRD
        // K < 4.5
        {
            "step_id" : 7,
            "drugs" : [ template_drugs["ACEI"][2],template_drugs["Diuretic"][1], template_drugs["CCB"][2], template_drugs["Spirno"][0] ],
            "condition" : {"uncontrolled" : 8},
            "note" : "",
            "side_effects" : {
                "cough" : 19,
                "elevated_cr" : "Stop, Cr elevatedor hyperkalemia present",
                "hyperkalemia" : "Stop, Cr elevated or hyperkalemia present", 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : 56,
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        {
            "step_id" : 8,
            "drugs" : [ template_drugs["ACEI"][2],template_drugs["Diuretic"][1], template_drugs["CCB"][2], template_drugs["Spirno"][1] ],
            "condition" : {"uncontrolled" : "end of protocol"},
            "note" : "",
            "side_effects" : {
                "cough" : 20,
                "elevated_cr" : 7,
                "hyperkalemia" : 7, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : 56,
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        // K > 4.5
        {
            "step_id" : 9,
            "drugs" : [ template_drugs["ACEI"][2],template_drugs["Diuretic"][1], template_drugs["CCB"][2], template_drugs["BB"][0] ],
            "condition" : {"uncontrolled" : 10},
            "note" : "Before adding/increasing " + template_drugs["BB"][0] + ", confirm HR > 55bpm",
            "side_effects" : {
                "cough" : 21,
                "elevated_cr" : null,
                "hyperkalemia" : "Stop, hyperkalemia present", 
                "slow_hr" : "Stop", 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : "Stop"
            }
        },
        {
            "step_id" : 10,
            "drugs" : [ template_drugs["ACEI"][2],template_drugs["Diuretic"][1], template_drugs["CCB"][2], template_drugs["BB"][1] ],
            "condition" : {"uncontrolled" : 11},
            "note" : "Before adding/increasing " + template_drugs["BB"][1] + ", confirm HR > 55bpm",
            "side_effects" : {
                "cough" : 22,
                "elevated_cr" : null,
                "hyperkalemia" : 9, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : "Stop"
            }
        },
        {
            "step_id" : 11,
            "drugs" : [ template_drugs["ACEI"][2],template_drugs["Diuretic"][1], template_drugs["CCB"][2], template_drugs["BB"][2] ],
            "condition" : {"uncontrolled" : 12},
            "note" : "Before adding/increasing " + template_drugs["BB"][2] + ", confirm HR > 55bpm",
            "side_effects" : {
                "cough" : 23,
                "elevated_cr" : null,
                "hyperkalemia" : 10, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : "Stop"
            }
        },
        {
            "step_id" : 12,
            "drugs" : [ template_drugs["ACEI"][2], template_drugs["Diuretic"][1], template_drugs["CCB"][2], template_drugs["BB"][3] ],
            "condition" : {"uncontrolled" : "end of protocol"},
            "note" : "Before adding/increasing " + template_drugs["BB"][3] + ", confirm HR > 55bpm",
            "side_effects" : {
                "cough" : 24,
                "elevated_cr" : null,
                "hyperkalemia" : 11, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : "Stop"
            }
        },

        // OFF TO THE SIDE EFFECT RACES
        // ACEI COUGH
        {
            "step_id" : 13,
            "drugs" : [ template_drugs["ARB"][0],template_drugs["Diuretic"][0] ],
            "condition" : {"uncontrolled" : 14},
            "note" : "",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : 28,
                "hyperkalemia" : 28, 
                "slow_hr" : null, 
                "angioedema" : 41,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        {
            "step_id" : 14,
            "drugs" : [ template_drugs["ARB"][1],template_drugs["Diuretic"][0] ],
            "condition" : {"uncontrolled" : 15},
            "note" : "",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : 31,
                "hyperkalemia" : 31, 
                "slow_hr" : null, 
                "angioedema" : 41,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        {
            "step_id" : 15,
            "drugs" : [ template_drugs["ARB"][1],template_drugs["Diuretic"][1] ],
            "condition" : {"uncontrolled" : 16},
            "note" : "",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : 31,
                "hyperkalemia" : 31, 
                "slow_hr" : null, 
                "angioedema" : 41,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : null
            }
        },

        {
            "step_id" : 16,
            "drugs" : [ template_drugs["ARB"][1],template_drugs["Diuretic"][1], template_drugs["CCB"][0]  ],
            "condition" : {"uncontrolled" : 17},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : null,
                "hyperkalemia" : null, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : {"K < 4.5" : 71, "K > 4.5" : 73},
                "asthma" : null
            }
        },
        {
            "step_id" : 17,
            "drugs" : [ template_drugs["ARB"][1],template_drugs["Diuretic"][1], template_drugs["CCB"][1]  ],
            "condition" : {"uncontrolled" : 18},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : null,
                "hyperkalemia" : null, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : {"K < 4.5" : 71, "K > 4.5" : 73},
                "asthma" : null
            }
        },
        {
            "step_id" : 18,
            "drugs" : [ template_drugs["ARB"][1],template_drugs["Diuretic"][1], template_drugs["CCB"][2] ],
            "condition" : {"K < 4.5" : 19, "K > 4.5" : 21},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : null,
                "hyperkalemia" : null, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : {"K < 4.5" : 71, "K > 4.5" : 73},
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        // K < 4.5
        {
            "step_id" : 19,
            "drugs" : [ template_drugs["ARB"][1],template_drugs["Diuretic"][1], template_drugs["CCB"][2], template_drugs["Spirno"][0] ],
            "condition" : {"uncontrolled" : 20},
            "note" : "",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : "Stop, Cr elevatedor hyperkalemia present",
                "hyperkalemia" : "Stop, Cr elevated or hyperkalemia present", 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : 58,
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        {
            "step_id" : 20,
            "drugs" : [ template_drugs["ARB"][1],template_drugs["Diuretic"][1], template_drugs["CCB"][2], template_drugs["Spirno"][1] ],
            "condition" : {"uncontrolled" : "end of protocol"},
            "note" : "",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : 19,
                "hyperkalemia" : 19, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : 58,
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        // K > 4.5
        {
            "step_id" : 21,
            "drugs" : [ template_drugs["ARB"][1],template_drugs["Diuretic"][1], template_drugs["CCB"][2], template_drugs["BB"][0] ],
            "condition" : {"uncontrolled" : 22},
            "note" : "Before adding/increasing " + template_drugs["BB"][0] + ", confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : null,
                "hyperkalemia" : "Stop, hyperkalemia present", 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : "Stop"
            }
        },
        {
            "step_id" : 22,
            "drugs" : [ template_drugs["ARB"][1],template_drugs["Diuretic"][1], template_drugs["CCB"][2], template_drugs["BB"][1] ],
            "condition" : {"uncontrolled" : 23},
            "note" : "Before adding/increasing " + template_drugs["BB"][1] + ", confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : null,
                "hyperkalemia" : 21, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : "Stop"
            }
        },
        {
            "step_id" : 23,
            "drugs" : [ template_drugs["ARB"][1],template_drugs["Diuretic"][1], template_drugs["CCB"][2], template_drugs["BB"][2] ],
            "condition" : {"uncontrolled" : 24},
            "note" : "Before adding/increasing " + template_drugs["BB"][2] + ", confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : null,
                "hyperkalemia" : 22, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : "Stop"
            }
        },
        {
            "step_id" : 24,
            "drugs" : [ template_drugs["ARB"][1],template_drugs["Diuretic"][1], template_drugs["CCB"][2], template_drugs["BB"][3] ],
            "condition" : {"uncontrolled" : "end of protocol"},
            "note" : "Before adding/increasing " + template_drugs["BB"][3] + ", confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : null,
                "hyperkalemia" : 23, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : "Stop"
            }
        },


        //ACEI ELEVATED CR+ or HYPERKALEMIA
        {
            "step_id" : 25,
            "drugs" : [ template_drugs["ACEI"][0], template_drugs["Diuretic"][0], template_drugs["CCB"][0] ],
            "condition" : {"uncontrolled" : 26},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : 28,
                "hyperkalemia" : 28, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        {
            "step_id" : 26,
            "drugs" : [ template_drugs["ACEI"][0], template_drugs["Diuretic"][0], template_drugs["CCB"][1] ],
            "condition" : {"uncontrolled" : 27},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : 29,
                "hyperkalemia" : null, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        {
            "step_id" : 27,
            "drugs" : [ template_drugs["ACEI"][0], template_drugs["Diuretic"][0], template_drugs["CCB"][2] ],
            "condition" : {"uncontrolled" : 28},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : null,
                "hyperkalemia" : null, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        {
            "step_id" : 28,
            "drugs" : [ template_drugs["Diuretic"][0], template_drugs["CCB"][0] ],
            "condition" : {"uncontrolled" : 29},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : 38,
                "hyperkalemia" : "hyperkalemia still present",
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        {
            "step_id" : 29,
            "drugs" : [ template_drugs["Diuretic"][0], template_drugs["CCB"][1] ],
            "condition" : {"uncontrolled" : 30},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : 28,
                "hyperkalemia" : 28, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        {
            "step_id" : 30,
            "drugs" : [ template_drugs["Diuretic"][0], template_drugs["CCB"][2] ],
            "condition" : {"uncontrolled" : "end of protocol"},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : 28,
                "hyperkalemia" : 28, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : null
            }
        },

        //ARB elevated cR+ & hyperkalmeia
        {
            "step_id" : 31,
            "drugs" : [ template_drugs["ARB"][0],template_drugs["Diuretic"][0], template_drugs["CCB"][0]  ],
            "condition" : {"uncontrolled" : 32},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : 28,
                "hyperkalemia" : 28, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        {
            "step_id" : 32,
            "drugs" : [ template_drugs["ARB"][0],template_drugs["Diuretic"][0], template_drugs["CCB"][1]  ],
            "condition" : {"uncontrolled" : 33},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : 29,
                "hyperkalemia" : 29, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        {
            "step_id" : 33,
            "drugs" : [ template_drugs["ARB"][0],template_drugs["Diuretic"][0], template_drugs["CCB"][2]  ],
            "condition" : {"uncontrolled" : "end of protocol"},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : 30,
                "hyperkalemia" : 30, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        

        //ACEI-HCTZ hyperkalemia, ARB-HCTZ hyperkalemia
        {
            "step_id" : 34,
            "drugs" : [ template_drugs["Diuretic"][1], template_drugs["CCB"][0] ],
            "condition" : {"uncontrolled" : 35},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : 38,
                "hyperkalemia" : 38, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        {
            "step_id" : 35,
            "drugs" : [ template_drugs["Diuretic"][2], template_drugs["CCB"][0] ],
            "condition" : {"uncontrolled" : 36},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : 38,
                "hyperkalemia" : 38, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        {
            "step_id" : 36,
            "drugs" : [ template_drugs["Diuretic"][2], template_drugs["CCB"][1] ],
            "condition" : {"uncontrolled" : 37},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : 39,
                "hyperkalemia" : "hyperkalemia still present", 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        {
            "step_id" : 37,
            "drugs" : [ template_drugs["Diuretic"][2], template_drugs["CCB"][2] ],
            "condition" : {"K < 4.5" : 50, "K > 4.5" : 52},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : 40,
                "hyperkalemia" : "hyperkalemia still present", 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        

        // HCTZ elevatedd CR
        {
            "step_id" : 38,
            "drugs" : [ template_drugs["CCB"][0] ],
            "condition" : {"uncontrolled" : 39},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : "Cr still elevated",
                "hyperkalemia" : "hyperkalemia still present", 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        {
            "step_id" : 39,
            "drugs" : [ template_drugs["CCB"][1] ],
            "condition" : {"uncontrolled" : 40},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : "Cr still elevated",
                "hyperkalemia" : "hyperkalemia still present", 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        {
            "step_id" : 40,
            "drugs" : [ template_drugs["CCB"][2] ],
            "condition" : {"K < 4.5" : 44, "K > 4.5" : 46},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : "Cr still elevated",
                "hyperkalemia" : "hyperkalemia still present", 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : null
            }
        },

        // ACEI Angiodema
        {
            "step_id" : 41,
            "drugs" : [ template_drugs["Diuretic"][0] ],
            "condition" : {"uncontrolled" : 42},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : 38,
                "hyperkalemia" : 34, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        {
            "step_id" : 42,
            "drugs" : [ template_drugs["Diuretic"][1] ],
            "condition" : {"uncontrolled" : 43},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : 38,
                "hyperkalemia" : 34, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        {
            "step_id" : 43,
            "drugs" : [ template_drugs["Diuretic"][2] ],
            "condition" : {"uncontrolled" : 35},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : 38,
                "hyperkalemia" : 34, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : null
            }
        },

        //CCD + Spirno / BB
        {
            "step_id" : 44,
            "drugs" : [ template_drugs["CCB"][2],  template_drugs["Spirno"][0] ],
            "condition" : {"uncontrolled" : 45},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : "Stop, Cr elevatedor hyperkalemia present",
                "hyperkalemia" : "Stop, Cr elevated or hyperkalemia present", 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : "change to Eplerenone",
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        {
            "step_id" : 45,
            "drugs" : [ template_drugs["CCB"][2], template_drugs["Spirno"][1] ],
            "condition" : {"uncontrolled" : "end of protocol"},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : 44,
                "hyperkalemia" : 44, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : "change to Eplerenone",
                "rash_other" : "Stop",
                "asthma" : null
            }
        },

        {
            "step_id" : 46,
            "drugs" : [ template_drugs["CCB"][2],  template_drugs["BB"][0] ],
            "condition" : {"uncontrolled" : 47},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : "Cr still elevated",
                "hyperkalemia" : "Stop, hyperkalemia present", 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : "Stop"
            }
        },
        {
            "step_id" : 47,
            "drugs" : [ template_drugs["CCB"][2],  template_drugs["BB"][1]  ],
            "condition" : {"uncontrolled" : 48},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : "Cr still elevated",
                "hyperkalemia" : 46, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : "Stop"
            }
        },
        {
            "step_id" : 48,
            "drugs" : [ template_drugs["CCB"][2],  template_drugs["BB"][2] ],
            "condition" : {"uncontrolled" : 49},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : "Cr still elevated",
                "hyperkalemia" : 47, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : "Stop"
            }
        },
        {
            "step_id" : 49,
            "drugs" : [ template_drugs["CCB"][2],  template_drugs["BB"][3] ],
            "condition" : {"uncontrolled" : "end of protocol"},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : "Cr still elevated",
                "hyperkalemia" : 48, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : "Stop"
            }
        },

        {
            "step_id" : 50,
            "drugs" : [ template_drugs["Diuretic"][2], template_drugs["CCB"][2] , template_drugs["Spirno"][0]],
            "condition" : {"uncontrolled" : 51},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : "Stop, Cr elevatedor hyperkalemia present",
                "hyperkalemia" : "Stop, Cr elevated or hyperkalemia present", 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : 62,
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        {
            "step_id" : 51,
            "drugs" : [ template_drugs["Diuretic"][2], template_drugs["CCB"][2], template_drugs["Spirno"][1] ],
            "condition" : {"uncontrolled" : "end of protocol"},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : 50,
                "hyperkalemia" : 50, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : 62,
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        {
            "step_id" : 52,
            "drugs" : [ template_drugs["Diuretic"][2], template_drugs["CCB"][2], template_drugs["BB"][0] ],
            "condition" : {"uncontrolled" : 53},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : 40,
                "hyperkalemia" : "Stop, hyperkalemia present", 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : "Stop"
            }
        },
        {
            "step_id" : 53,
            "drugs" : [ template_drugs["Diuretic"][2], template_drugs["CCB"][2], template_drugs["BB"][1] ],
            "condition" : {"uncontrolled" : 54},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : 40,
                "hyperkalemia" : 52, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : "Stop"
            }
        },
        {
            "step_id" : 54,
            "drugs" : [ template_drugs["Diuretic"][2], template_drugs["CCB"][2], template_drugs["BB"][2] ],
            "condition" : {"uncontrolled" : 55},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : 40,
                "hyperkalemia" : 53, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : "Stop"
            }
        },
        {
            "step_id" : 55,
            "drugs" : [ template_drugs["Diuretic"][2], template_drugs["CCB"][2], template_drugs["BB"][3] ],
            "condition" : {"uncontrolled" : "end of protocol"},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : 40,
                "hyperkalemia" : 54, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : "Stop"
            }
        },

        //SPirnolactone Breast Discomfort
        {
            "step_id" : 56,
            "drugs" : [ template_drugs["ACEI"][2],template_drugs["Diuretic"][1], template_drugs["CCB"][2], template_drugs["Eplerenone"][0] ],
            "condition" : {"uncontrolled" : 57},
            "note" : "",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : null,
                "hyperkalemia" : null,
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : "Stop, Call Doctor",
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        {
            "step_id" : 57,
            "drugs" : [ template_drugs["ACEI"][2],template_drugs["Diuretic"][1], template_drugs["CCB"][2], template_drugs["Eplerenone"][1] ],
            "condition" : {"uncontrolled" : "end of protocol"},
            "note" : "",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : null,
                "hyperkalemia" : null,
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : "Stop, Call Doctor",
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        {
            "step_id" : 58,
            "drugs" : [ template_drugs["ARB"][1],template_drugs["Diuretic"][1], template_drugs["CCB"][2], template_drugs["Eplerenone"][0] ],
            "condition" : {"uncontrolled" : 58},
            "note" : "",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : null,
                "hyperkalemia" : null,
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : "Stop, Call Doctor",
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        {
            "step_id" : 59,
            "drugs" : [ template_drugs["ARB"][1],template_drugs["Diuretic"][1], template_drugs["CCB"][2], template_drugs["Eplerenone"][1] ],
            "condition" : {"uncontrolled" : "end of protocol"},
            "note" : "",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : null,
                "hyperkalemia" : null,
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : "Stop, Call Doctor",
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        {
            "step_id" : 60,
            "drugs" : [ template_drugs["CCB"][2],  template_drugs["Eplerenone"][0] ],
            "condition" : {"uncontrolled" : 61},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : null,
                "hyperkalemia" : null,
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : "Stop, Call Doctor",
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        {
            "step_id" : 61,
            "drugs" : [ template_drugs["CCB"][2],  template_drugs["Eplerenone"][1] ],
            "condition" : {"uncontrolled" : "end of protocol"},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : null,
                "hyperkalemia" : null,
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : "Stop, Call Doctor",
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        {
            "step_id" : 62,
            "drugs" : [ template_drugs["Diuretic"][2], template_drugs["CCB"][2] , template_drugs["Eplerenone"][0]],
            "condition" : {"uncontrolled" : 63},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : null,
                "hyperkalemia" : null, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : "Stop, Call Doctor",
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        {
            "step_id" : 63,
            "drugs" : [ template_drugs["Diuretic"][2], template_drugs["CCB"][2] , template_drugs["Eplerenone"][1]],
            "condition" : {"uncontrolled" : "end of protocol"},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : null,
                "hyperkalemia" : null, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : "Stop, Call Doctor",
                "rash_other" : "Stop",
                "asthma" : null
            }
        },

        //special ACEI 
        {
            "step_id" : 64,
            "drugs" : [ template_drugs["ACEI"][1], template_drugs["Diuretic"][1], template_drugs["CCB"][0] ],
            "condition" : {"uncontrolled" : 26},
            "note" : "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : 25,
                "hyperkalemia" : 25, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : null
            }
        },

        //CCB rash
        {
            "step_id" : 65,
            "drugs" : [ template_drugs["ACEI"][2],template_drugs["Diuretic"][1], template_drugs["BB"][0] ],
            "condition" : {"uncontrolled" : 66},
            "note" : "Before adding/increasing " + template_drugs["BB"][0] + ", confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : null,
                "hyperkalemia" : null, 
                "slow_hr" : "Stop, Call Doctor", 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : "Stop"
            }
        },
        {
            "step_id" : 66,
            "drugs" : [ template_drugs["ACEI"][2],template_drugs["Diuretic"][1], template_drugs["BB"][1] ],
            "condition" : {"uncontrolled" : 67},
            "note" : "Before adding/increasing " + template_drugs["BB"][1] + ", confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : null,
                "hyperkalemia" : null, 
                "slow_hr" : 65, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : "Stop"
            }
        },
        {
            "step_id" : 67,
            "drugs" : [ template_drugs["ACEI"][2],template_drugs["Diuretic"][1], template_drugs["BB"][2] ],
            "condition" : {"uncontrolled" : 68},
            "note" : "Before adding/increasing " + template_drugs["BB"][2] + ", confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : null,
                "hyperkalemia" : null, 
                "slow_hr" : 66, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : "Stop"
            }
        },
        {
            "step_id" : 68,
            "drugs" : [ template_drugs["ACEI"][2], template_drugs["Diuretic"][1], template_drugs["BB"][3] ],
            "condition" : {"uncontrolled" : "end of protocol"},
            "note" : "Before adding/increasing " + template_drugs["BB"][3] + ", confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : null,
                "hyperkalemia" : null, 
                "slow_hr" : 67, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : "Stop"
            }
        },
        {
            "step_id" : 69,
            "drugs" : [ template_drugs["ACEI"][2],template_drugs["Diuretic"][1], template_drugs["Spirno"][0] ],
            "condition" : {"uncontrolled" : 70},
            "note" : "",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : null,
                "hyperkalemia" : null, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : 56,
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        {
            "step_id" : 70,
            "drugs" : [ template_drugs["ACEI"][2],template_drugs["Diuretic"][1], template_drugs["Spirno"][1] ],
            "condition" : {"uncontrolled" : "end of protocol"},
            "note" : "",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : null,
                "hyperkalemia" : null, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : 56,
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        {
            "step_id" : 71,
            "drugs" : [ template_drugs["ARB"][1],template_drugs["Diuretic"][1], template_drugs["Spirno"][0] ],
            "condition" : {"uncontrolled" : 72},
            "note" : "",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : null,
                "hyperkalemia" : null, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : 58,
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        {
            "step_id" : 72,
            "drugs" : [ template_drugs["ARB"][1],template_drugs["Diuretic"][1], template_drugs["Spirno"][1] ],
            "condition" : {"uncontrolled" : "end of protocol"},
            "note" : "",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : null,
                "hyperkalemia" : null, 
                "slow_hr" : null, 
                "angioedema" : null,
                "breast_discomfort" : 58,
                "rash_other" : "Stop",
                "asthma" : null
            }
        },
        // K > 4.5
        {
            "step_id" : 73,
            "drugs" : [ template_drugs["ARB"][1],template_drugs["Diuretic"][1],  template_drugs["BB"][0] ],
            "condition" : {"uncontrolled" : 74},
            "note" : "Before adding/increasing " + template_drugs["BB"][0] + ", confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : null,
                "hyperkalemia" : null, 
                "slow_hr" : "Stop, Call Doctor", , 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : "Stop"
            }
        },
        {
            "step_id" : 74,
            "drugs" : [ template_drugs["ARB"][1],template_drugs["Diuretic"][1], template_drugs["BB"][1] ],
            "condition" : {"uncontrolled" : 75},
            "note" : "Before adding/increasing " + template_drugs["BB"][1] + ", confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : null,
                "hyperkalemia" : null, 
                "slow_hr" : 73, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : "Stop"
            }
        },
        {
            "step_id" : 75,
            "drugs" : [ template_drugs["ARB"][1],template_drugs["Diuretic"][1], template_drugs["BB"][2] ],
            "condition" : {"uncontrolled" : 76},
            "note" : "Before adding/increasing " + template_drugs["BB"][2] + ", confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : null,
                "hyperkalemia" : null, 
                "slow_hr" : 74, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : "Stop"
            }
        },
        {
            "step_id" : 76,
            "drugs" : [ template_drugs["ARB"][1],template_drugs["Diuretic"][1], template_drugs["BB"][3] ],
            "condition" : {"uncontrolled" : "end of protocol"},
            "note" : "Before adding/increasing " + template_drugs["BB"][3] + ", confirm HR > 55bpm",
            "side_effects" : {
                "cough" : null,
                "elevated_cr" : null,
                "hyperkalemia" : null, 
                "slow_hr" : 75, 
                "angioedema" : null,
                "breast_discomfort" : null,
                "rash_other" : "Stop",
                "asthma" : "Stop"
            }
        },
    ]
}
//google jquery templates, might be useful
</script>