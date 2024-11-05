<?php
namespace Stanford\HTNapi;

// NOTE That for this to work in shibboleth, you must add a define("NOAUTH",true) to the main index.php before redcap connect.
class HTNtree  {
    private   $enabledProjects
            , $patients_project
            , $tree_templates_project
            , $meds_project
            , $providers_project
            , $module;

    public function __construct($module) {
        // This is how to access the parent EM class in these other Classes
        $this->module = $module;

        $enabledProjects    = $this->module->showEnabledProjects();
        foreach($enabledProjects as $project){
            $pid            = $project["pid"];
            $project_mode   = $project["mode"];
            switch($project_mode){
                case "patients":
                    $this->patients_project = $pid;
                break;

                case "tree_templates":
                    $this->tree_templates_project = $pid;
                break;

                case "meds":
                    $this->meds_project = $pid;
                break;

                case "providers":
                    $this->providers_project = $pid;
                break;
            }
        }
    }

    public function getDefaultTrees($provider_id=0){
        $filter = "[provider_id] = $provider_id";
        $fields = array();
		$params	= array(
			'project_id'	=> $this->tree_templates_project,
            'return_format' => 'json',
            'fields'        => $fields,
            'filterLogic'   => $filter
		);
        $q      = \REDCap::getData($params);
        $trees  = json_decode($q, true);

        $labeled_trees = array();
        foreach($trees as $tree){
//            $this->module->emDebug("what is happening?", $tree);
            $def_tree = $this->getTemplateDrugs($tree);
            if(!empty($def_tree)){
                $tree_id = $tree["record_id"];
                $labeled_trees[$tree_id] = array("label" => $tree["template_name"], "tree_meta" => $tree, "doses" => $def_tree);
            }
        }

        return $labeled_trees;
    }

    public function saveTemplate($provider_id, $post){
        $is_edit    = !empty($post["record_id"]) ? true : false;
        if($is_edit && $post["record_id"] < 5){
            //Dont Edit Default Templates
            return array("errors" => "Default Prescription Tree Templates aren't editable." );
        }

        $next_id    = !empty($post["record_id"]) ? $post["record_id"] : $this->module->getNextAvailableRecordId($this->module->enabledProjects['tree_templates']['pid']);
        $data       = array(
            "record_id"             => $next_id,
            "provider_id"           => $provider_id,
            "template_name"         => $post["template_name"],
            "acei_class"            => $post["acei_class"],
            "arb_class"             => $post["arb_class"],
            "diuretic_class"        => $post["diuretic_class"],
            "ccb_class"             => $post["ccb_class"],
            "bb_class"              => $post["bb_class"],
            "spirno_class"          => $post["spirno_class"],
            "epler_class"           => $post["epler_class"]
        );
        $r = \REDCap::saveData($this->tree_templates_project, 'json', json_encode(array($data)) );

        //NOW SAVE THE MEDS IN MED PROJECT
        $map_med_class      = array(
            1 => "ACEI",
            2 => "ARB",
            3 => "DIURETIC",
            4 => "SPIRNO",
            5 => "EPLER",
            6 => "CCB",
            7 => "BB",
        );
        $map_med_class_r    = array_flip($map_med_class);

        $med_instance       = array();
        if($is_edit){
            //GET MEDS BY tree_id (record_id) from above
            $params	= array(
                'project_id'	=> $this->meds_project,
                'return_format' => 'json',
                'fields'        => array("record_id","med_name", "med_unit", "med_class"),
                'filterLogic'   => "[tree_id] = $next_id"
            );
            $q 					= \REDCap::getData($params);
            $meds 			    = json_decode($q, true);
            $this->module->emDebug("EDIT meds for tree_id $next_id");

            foreach($meds as $med){
                $med_name   = $med["med_name"];
                $med_unit   = $med["med_unit"];
                $med_id     = $med["record_id"];
                $med_class  = $med["med_class"];
                $post_med   = strtolower($map_med_class[$med_class])."_class";
                $post_doses = strtolower($map_med_class[$med_class])."_doses";
                $new_doses  = $post[$post_doses];
                $post_unit  = array_shift($new_doses);
                if($post[$post_med] != $med_name){
                    //DRUG WAS CHANGED SO UPDATE IT NOW
                    $temp   = array(
                         "record_id"    => $med_id
                        ,"med_name"     => $post[$post_med]
                        ,"med_unit"     => $post_unit
                    );
                    array_push($med_instance, $temp);
                }

                $params	= array(
                    'project_id'	=> $this->meds_project,
                    'return_format' => 'json',
                    'records'		=> $med_id,
                    'fields'        => array("dose_value", "record_id")
                );
                $q 					= \REDCap::getData($params);
                $doses 			    = json_decode($q, true);
                array_shift($doses);

                foreach($new_doses as $i => $new_dose){
                    $edit_instance_id   = isset($doses[$i]) ? $doses[$i]["redcap_repeat_instance"] : $edit_instance_id;
                    $old_dose           = isset($doses[$i]) ? $doses[$i]["dose_value"] : null;
                    if($old_dose != $new_dose){
                        $temp               = array(
                            "record_id"                 => $med_id,
                            "redcap_repeat_instance" 	=> $edit_instance_id,
                            "redcap_repeat_instrument" 	=> "doses",
                            "dose_value"    => $new_dose
                        );
                        array_push($med_instance,$temp);
                    }
                    $edit_instance_id++;
                }
            }
            $this->module->emDebug("Final Updates for tree_id $next_id", $med_instance);
        }else{
            $next_med_id  = $this->module->getNextAvailableRecordId($this->meds_project);
            foreach($post as $key => $doses){
                if(strpos($key,"_doses") > -1){
                    $temp           = explode("_",$key);
                    $med_class      = $map_med_class_r[strtoupper($temp[0])];
                    $unit           = array_shift($doses);
                    $meddata        = array(
                        "record_id" => $next_med_id
                        ,"tree_id"   => $next_id
                        ,"med_class" => $med_class
                        ,"med_name"  => $post[$temp[0]."_class"]
                        ,"med_unit"  => $unit
                    );
                    $r = \REDCap::saveData($this->meds_project, 'json', json_encode(array($meddata)) );

                    $field  = "dose_value";
                    $params	= array(
                        'project_id'	=> $this->meds_project,
                        'return_format' => 'json',
                        'records'		=> $next_med_id,
                        'fields'        => $field,
                        'filterLogic'   => "[$field] != ''"
                    );
                    $q 					= \REDCap::getData($params);
                    $records 			= json_decode($q, true);
                    $last_instance_id 	= count($records);
                    $last_instance_id++;
                    foreach($doses as $dose){
                        $temp               = array(
                            "record_id"                 => $next_med_id,
                            "redcap_repeat_instance" 	=> $last_instance_id,
                            "redcap_repeat_instrument" 	=> "doses",
                            "dose_value"    => $dose
                    );
                    array_push($med_instance,$temp);
                    $last_instance_id++;
                    }
                    $next_med_id++;
                }
            }
        }
        $r = \REDCap::saveData($this->meds_project, 'json', json_encode($med_instance) );
        return $r;
        // $this->module->emDebug("save_template tree, med, doses", $data, $meddata, $med_instance);
    }

    public function getDrugList(){
        return array(

            "ACEI" => array("label" => "ACE Inhibitor" , "drugs" => array(
                     array( "name" => "Lisinopril" , "unit" => "mg" , "common_dosage" => array(10,20,30,40) , "note" => "" )
                    ,array( "name" => "Benazaril" , "unit" => "mg" , "common_dosage" => array(5,10,20,40) , "note" => "" )
                    ,array( "name" => "Fosinopril" , "unit" => "mg" , "common_dosage" => array(10,20,40) , "note" => "" )
                    ,array( "name" => "Quinapril" , "unit" => "mg" , "common_dosage" => array(5,10,20,40) , "note" => "" )
                    ,array( "name" => "Ramipril" , "unit" => "mg" , "common_dosage" => array(1.25,2.5,5,10) , "note" => "" )
                    ,array( "name" => "Trandolapril" , "unit" => "mg" , "common_dosage" => array(1,2,4) , "note" => "" )
                )
            )
            ,"ARB" => array("label" => "Angiotensin receptor blocker", "drugs" => array(
                     array( "name" => "Losartan" , "unit" => "mg" , "common_dosage" => array(50,100) , "note" => "" )
                    ,array( "name" => "Candesartan" , "unit" => "mg" , "common_dosage" => array(8,16,32) , "note" => "may prevent migraine headaches" )
                    ,array( "name" => "Valsartan" , "unit" => "mg" , "common_dosage" => array(40,80,160,320) , "note" => "" )
                    ,array( "name" => "Olmesartan" , "unit" => "mg" , "common_dosage" => array(20,40) , "note" => "" )
                    ,array( "name" => "Telmisartan" , "unit" => "mg" , "common_dosage" => array(20,40,80) , "note" => "" )
                )
            )
            ,"DIURETIC"  => array("label" => "", "drugs" => array(
                    array( "name" => "HCTZ" , "unit" => "mg" , "common_dosage" => array(12.5,25,50) , "note" => "most effective when combined with ACEI" )
                   ,array( "name" => "Chlorthalidone" , "unit" => "mg" , "common_dosage" => array(12.5,25) , "note" => "most effective when combined with ACEI" )
                   ,array( "name" => "Indapamide" , "unit" => "mg" , "common_dosage" => array(1.25,2.5) , "note" => "most effective when combined with ACEI" )
                   ,array( "name" => "Triamterene" , "unit" => "mg" , "common_dosage" => array(25,50) , "note" => "most effective when combined with ACEI" )
                   ,array( "name" => "K+ sparing-spironolactone" , "unit" => "mg" , "common_dosage" => array(25,50) , "note" => "most effective when combined with ACEI" )
                   ,array( "name" => "Amiloride" , "unit" => "mg" , "common_dosage" => array(5,10) , "note" => "most effective when combined with ACEI" )
                   ,array( "name" => "Furosemide" , "unit" => "mg" , "common_dosage" => array(20,40,80) , "note" => "most effective when combined with ACEI", "frequency" => 2 )
                   ,array( "name" => "Torsemide" , "unit" => "mg" , "common_dosage" => array(10,20,40) , "note" => "most effective when combined with ACEI" )
               )
            )
            ,"ARA" => array("label" => "Aldosterone receptor antagonists" , "drugs" => array(
                    array( "name" => "SPIRONOLACTONE" , "unit" => "mg" , "common_dosage" => array(12.5,25) , "note" => "" )
                )
            )

            ,"MRA" => array("label"=> "Mineralocorticoid receptor antagonists" , "drugs" => array(
                    array( "name" => "EPLERENONE" , "unit" => "Daily" , "common_dosage" => array("1x","2x") , "note" => "" )
                )
            )

            ,"CCB" => array("label"=> "Calcium Channel Blockers" , "drugs" => array(
                    array( "name" => "Amlodipine" , "unit" => "mg" , "common_dosage" => array(2.5,5,10) , "note" => "" )
                    ,array( "name" => "Nifedipine ER" , "unit" => "mg" , "common_dosage" => array(30,60,90) , "note" => "" )
                    ,array( "name" => "Diltiazem ER" , "unit" => "mg" , "common_dosage" => array(180,240,300,360) , "note" => "" )
                    ,array( "name" => "Verapamil" , "unit" => "mg" , "common_dosage" => array(80,120) , "note" => "" , "frequency" => 3)
                    ,array( "name" => "Verapamil ER" , "unit" => "mg" , "common_dosage" => array(240,480) , "note" => "" )
                )
            )
            ,"BB" => array("label"=> "Beta-Blockers" , "drugs" => array(
                     array( "name" => "Bisoprolol" , "unit" => "mg" , "common_dosage" => array(5,10) , "note" => "" )

                    ,array( "name" => "Metroprolol succinate" , "unit" => "mg" , "common_dosage" => array(1,2,3) , "note" => "" )
                    ,array( "name" => "Tartrate" , "unit" => "mg" , "common_dosage" => array(50,100) , "note" => "" , "frequency" => 2)
                    ,array( "name" => "Propranolol" , "unit" => "mg" , "common_dosage" => array(40,80,120) , "note" => "" , "frequency" => 2)
                    ,array( "name" => "Carvedilol" , "unit" => "mg" , "common_dosage" => array(6.25,12.5,25) , "note" => "" , "frequency" => 2)
                    ,array( "name" => "Labetalol" , "unit" => "mg" , "common_dosage" => array(100,200,300) , "note" => "" , "frequency" => 2)
                    ,array( "name" => "Nebivolol" , "unit" => "mg" , "common_dosage" => array(5,10) , "note" => "" )
                )
            )
        );
    }

    public function getTemplateDrugs($tree){
        //matches radio values from redcap
        $map_med_class = array(
            1 => "ACEI",
            2 => "ARB",
            3 => "DIURETIC",
            4 => "SPIRNO",
            5 => "EPLER",
            6 => "CCB",
            7 => "BB",
        );
        $tree_id = $tree["record_id"];

        $filter = "[tree_id] = $tree_id";
        $fields = array("record_id");
		$params	= array(
			'project_id'	=> $this->meds_project,
            'return_format' => 'json',
            'records'       => array(),
            'fields'        => $fields,
            'filterLogic'   => $filter
		);
        $q      = \REDCap::getData($params);
        $meds   = json_decode($q, true);

        if(!empty($meds)){
            $med_ids = array();
            foreach($meds as $i => $med){
                $med_ids[] = $med["record_id"];
            }

            $fields = array("record_id", "med_class", "med_unit", "dose_value");
            $params	= array(
                'project_id'	=> $this->meds_project,
                'return_format' => 'json',
                'records'       => $med_ids,
                'fields'        => $fields
            );
            $q      = \REDCap::getData($params);
            $doses  = json_decode($q, true);

            $template_drugs = array();
            $med_unit       = "";
            $drug_class     = "";
            $drug_name      = "";
            foreach($doses as $el){
                if(empty($el["redcap_repeat_instrument"])){
                    //top level meds instrument
                    $drug_class     = $map_med_class[$el["med_class"]];
                    $med_unit       = $el["med_unit"]; //this will be what it is until the next
                    $drug_name      = $tree[strtolower($drug_class)."_class"];
                    $template_drugs[$drug_class] = array("raw" => array(), "pretty" => array());
                    continue;
                }
                //else repeating dose instrument
                $current_drug_dose = $drug_name. " ".$el["dose_value"].$med_unit;
                array_push($template_drugs[$drug_class]["raw"], $el["dose_value"]);
                array_push($template_drugs[$drug_class]["pretty"], $current_drug_dose);
            }
        }else {
            $template_drugs = array();
        }
        return $template_drugs;
    }

    public function treeLogic($provider_id){
        //TODO FIX THIS FOR NEW WORKFLOW
        $default_trees      = $this->getDefaultTrees();
        $provider_trees     = $this->getDefaultTrees($provider_id);
        $default_trees      = array_merge($default_trees,$provider_trees);

        $provider_logic_trees = array();
        foreach($default_trees as $tree){
            $tree_id            = $tree["tree_meta"]["record_id"];
            $template_name      = $tree["tree_meta"]["template_name"];
            $template_drugs     = $tree["doses"];

            $logicTree      = array();
            if(!empty($template_drugs)){

                // Assume 'logic_tree_version' is a field in your template that specifies which logic tree to use
                if($template_name == "CCB Start (Default)"){
                    // For defaultLogicTree2, we need to know the tree type
                    $tree_type = $tree["tree_meta"]["tree_type"];
                    $logicTree = $this->defaultLogicTree2($template_drugs, $tree_type);
                } elseif($template_name == "Thiazides + CCB (Default)"){
                    // For defaultLogicTree2, we need to know the tree type
                    $tree_type = $tree["tree_meta"]["tree_type"];
                    $logicTree = $this->defaultLogicTree3($template_drugs, $tree_type);
                } elseif($template_name == "Thiazides + ARB (Default)"){
                    // For defaultLogicTree2, we need to know the tree type
                    $tree_type = $tree["tree_meta"]["tree_type"];
                    $logicTree = $this->defaultLogicTree4($template_drugs, $tree_type);
                } else {
                    $logicTree = $this->defaultLogicTree1($template_drugs);
                }
            }


            $tree["logicTree"]  = $logicTree;
            $provider_logic_trees[$tree_id] = $tree;
        }
//        $this->module->emDebug("This Providers custom Logic trees, including the default ones");

        return $provider_logic_trees;
    }

    public function defaultLogicTree1($template_drugs){
        //TODO - DO NOT REMEMBER WHY WE MERGE THESE TWO?
        $template_drugs["SPIRNO"]["pretty"] = array_merge($template_drugs["SPIRNO"]["pretty"], $template_drugs["EPLER"]["pretty"]);

        $ACEI           = array();
        $ARB            = array();
        $DIURETIC       = array();
        $SPIRNO         = array();
        $CCB            = array();
        $BB             = array();

        foreach ($template_drugs as $drug_class => $drugs) {
            foreach ($drugs["pretty"] as $drug) {
                ${$drug_class}[] = $drug;
            }
        }

        $logicTree[] = array(
            "step_id" => 0,
            "drugs" => array($ACEI[0], $DIURETIC[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 1
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 205,
                "hyponatremia" => 16,
                "hypokalemia" => 16,
                "cough" => 31,
                "elevated_cr" => 46,
                "hyperkalemia" => 46,
                "angioedema" => 46,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 1,
            "drugs" => array($ACEI[1], $DIURETIC[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 2
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 0,
                "hyponatremia" => 17,
                "hypokalemia" => 17,
                "cough" => 32,
                "elevated_cr" => 46,
                "hyperkalemia" => 46,
                "angioedema" => 46,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 2,
            "drugs" => array($ACEI[1], $DIURETIC[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 3
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 1,
                "hyponatremia" => 17,
                "hypokalemia" => 17,
                "cough" => 33,
                "elevated_cr" => 47,
                "hyperkalemia" => 47,
                "angioedema" => 47,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 3,
            "drugs" => array($ACEI[2], $DIURETIC[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 4
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 2,
                "hyponatremia" => 18,
                "hypokalemia" => 18,
                "cough" => 33,
                "elevated_cr" => 47,
                "hyperkalemia" => 47,
                "angioedema" => 47,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 4,
            "drugs" => array($ACEI[2], $DIURETIC[1], $CCB[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 5
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 3,
                "hyponatremia" => 19,
                "hypokalemia" => 19,
                "cough" => 34,
                "elevated_cr" => 48,
                "hyperkalemia" => 48,
                "angioedema" => 48,
                "breast_discomfort" => null,
                "slow_hr" => 102,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 5,
            "drugs" => array($ACEI[2], $DIURETIC[1], $CCB[0], $CCB[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 6
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 4,
                "hyponatremia" => 20,
                "hypokalemia" => 20,
                "cough" => 35,
                "elevated_cr" => 49,
                "hyperkalemia" => 49,
                "angioedema" => 49,
                "breast_discomfort" => null,
                "slow_hr" => 102,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 6,
            "drugs" => array($ACEI[2], $DIURETIC[1], $CCB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 7
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 5,
                "hyponatremia" => 21,
                "hypokalemia" => 21,
                "cough" => 36,
                "elevated_cr" => 50,
                "hyperkalemia" => 50,
                "angioedema" => 50,
                "breast_discomfort" => null,
                "slow_hr" => 102,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 7,
            "drugs" => array($ACEI[2], $DIURETIC[1], $SPIRNO[0], $CCB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 8
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 6,
                "hyponatremia" => 26,
                "hypokalemia" => null,
                "cough" => 37,
                "elevated_cr" => 55,
                "hyperkalemia" => 55,
                "angioedema" => 55,
                "breast_discomfort" => 9,
                "slow_hr" => 103,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 8,
            "drugs" => array($ACEI[2], $DIURETIC[1], $SPIRNO[1], $CCB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 7,
                "hyponatremia" => 26,
                "hypokalemia" => null,
                "cough" => 38,
                "elevated_cr" => 55,
                "hyperkalemia" => 55,
                "angioedema" => 55,
                "breast_discomfort" => 10,
                "slow_hr" => 104,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 9,
            "drugs" => array($ACEI[2], $DIURETIC[1], $SPIRNO[2], $CCB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 10
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 6,
                "hyponatremia" => 26,
                "hypokalemia" => null,
                "cough" => 39,
                "elevated_cr" => 55,
                "hyperkalemia" => 55,
                "angioedema" => 55,
                "breast_discomfort" => null,
                "slow_hr" => 105,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 10,
            "drugs" => array($ACEI[2], $DIURETIC[1], $SPIRNO[3], $CCB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 9,
                "hyponatremia" => 26,
                "hypokalemia" => null,
                "cough" => 40,
                "elevated_cr" => 55,
                "hyperkalemia" => 55,
                "angioedema" => 55,
                "breast_discomfort" => null,
                "slow_hr" => 106,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 11,
            "drugs" => array($ACEI[2], $DIURETIC[1], $CCB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 12
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 5,
                "hyponatremia" => 26,
                "hypokalemia" => 26,
                "cough" => 41,
                "elevated_cr" => 55,
                "hyperkalemia" => 55,
                "angioedema" => 55,
                "breast_discomfort" => null,
                "slow_hr" => 107,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 12,
            "drugs" => array($ACEI[2], $DIURETIC[1], $CCB[2], $BB[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 13
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 11,
                "hyponatremia" => 27,
                "hypokalemia" => 27,
                "cough" => 42,
                "elevated_cr" => 56,
                "hyperkalemia" => 56,
                "angioedema" => 56,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 13,
            "drugs" => array($ACEI[2], $DIURETIC[1], $CCB[2], $BB[0], $BB[1], $BB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 14
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 12,
                "hyponatremia" => 28,
                "hypokalemia" => 28,
                "cough" => 43,
                "elevated_cr" => 57,
                "hyperkalemia" => 57,
                "angioedema" => 57,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 14,
            "drugs" => array($ACEI[2], $DIURETIC[1], $CCB[2], $BB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 15
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 13,
                "hyponatremia" => 29,
                "hypokalemia" => 29,
                "cough" => 44,
                "elevated_cr" => 58,
                "hyperkalemia" => 58,
                "angioedema" => 58,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 15,
            "drugs" => array($ACEI[2], $DIURETIC[1], $CCB[2], $BB[3]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 14,
                "hyponatremia" => 30,
                "hypokalemia" => 30,
                "cough" => 45,
                "elevated_cr" => 59,
                "hyperkalemia" => 59,
                "angioedema" => 59,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 16,
            "drugs" => array($ACEI[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 17
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 205,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => 73,
                "elevated_cr" => 116,
                "hyperkalemia" => 116,
                "angioedema" => 116,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 17,
            "drugs" => array($ACEI[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 18
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 16,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => 74,
                "elevated_cr" => 116,
                "hyperkalemia" => 116,
                "angioedema" => 116,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 18,
            "drugs" => array($ACEI[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 19
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 17,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => 74,
                "elevated_cr" => 116,
                "hyperkalemia" => 116,
                "angioedema" => 116,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 19,
            "drugs" => array($ACEI[2], $CCB[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 20
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 18,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => 75,
                "elevated_cr" => 117,
                "hyperkalemia" => 117,
                "angioedema" => 117,
                "breast_discomfort" => null,
                "slow_hr" => 131,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 20,
            "drugs" => array($ACEI[2], $CCB[0], $CCB[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 21
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 19,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => 76,
                "elevated_cr" => 118,
                "hyperkalemia" => 118,
                "angioedema" => 118,
                "breast_discomfort" => null,
                "slow_hr" => 131,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 21,
            "drugs" => array($ACEI[2], $CCB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 22
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 20,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => 77,
                "elevated_cr" => 119,
                "hyperkalemia" => 119,
                "angioedema" => 119,
                "breast_discomfort" => null,
                "slow_hr" => 131,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 22,
            "drugs" => array($ACEI[2], $SPIRNO[0], $CCB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 23
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 21,
                "hyponatremia" => 26,
                "hypokalemia" => null,
                "cough" => 78,
                "elevated_cr" => 124,
                "hyperkalemia" => 124,
                "angioedema" => 120,
                "breast_discomfort" => 24,
                "slow_hr" => 132,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 23,
            "drugs" => array($ACEI[2], $SPIRNO[1], $CCB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 22,
                "hyponatremia" => 26,
                "hypokalemia" => null,
                "cough" => 79,
                "elevated_cr" => 124,
                "hyperkalemia" => 124,
                "angioedema" => 121,
                "breast_discomfort" => 25,
                "slow_hr" => 133,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 24,
            "drugs" => array($ACEI[2], $SPIRNO[2], $CCB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 25
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 21,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => 80,
                "elevated_cr" => 124,
                "hyperkalemia" => 124,
                "angioedema" => 122,
                "breast_discomfort" => null,
                "slow_hr" => 134,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 25,
            "drugs" => array($ACEI[2], $SPIRNO[3], $CCB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 24,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => 81,
                "elevated_cr" => 124,
                "hyperkalemia" => 124,
                "angioedema" => 123,
                "breast_discomfort" => null,
                "slow_hr" => 135,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 26,
            "drugs" => array($ACEI[2], $CCB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 27
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 20,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => 82,
                "elevated_cr" => 124,
                "hyperkalemia" => 124,
                "angioedema" => 124,
                "breast_discomfort" => null,
                "slow_hr" => 136,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 27,
            "drugs" => array($ACEI[2], $CCB[2], $BB[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 28
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 26,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => 83,
                "elevated_cr" => 125,
                "hyperkalemia" => 125,
                "angioedema" => 125,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 28,
            "drugs" => array($ACEI[2], $CCB[2], $BB[0], $BB[1], $BB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 29
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 27,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => 84,
                "elevated_cr" => 126,
                "hyperkalemia" => 126,
                "angioedema" => 126,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 29,
            "drugs" => array($ACEI[2], $CCB[2], $BB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 30
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 28,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => 85,
                "elevated_cr" => 127,
                "hyperkalemia" => 127,
                "angioedema" => 127,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 30,
            "drugs" => array($ACEI[2], $CCB[2], $BB[3]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 29,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => 86,
                "elevated_cr" => 128,
                "hyperkalemia" => 128,
                "angioedema" => 128,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 31,
            "drugs" => array($ARB[0], $DIURETIC[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 32
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 185,
                "hyponatremia" => 73,
                "hypokalemia" => 73,
                "cough" => null,
                "elevated_cr" => 46,
                "hyperkalemia" => 46,
                "angioedema" => 46,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 32,
            "drugs" => array($ARB[1], $DIURETIC[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 33
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 31,
                "hyponatremia" => 74,
                "hypokalemia" => 74,
                "cough" => null,
                "elevated_cr" => 46,
                "hyperkalemia" => 46,
                "angioedema" => 46,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 33,
            "drugs" => array($ARB[1], $DIURETIC[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 34
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 32,
                "hyponatremia" => 74,
                "hypokalemia" => 74,
                "cough" => null,
                "elevated_cr" => 47,
                "hyperkalemia" => 47,
                "angioedema" => 47,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 34,
            "drugs" => array($ARB[1], $DIURETIC[1], $CCB[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 35
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 33,
                "hyponatremia" => 75,
                "hypokalemia" => 75,
                "cough" => null,
                "elevated_cr" => 48,
                "hyperkalemia" => 48,
                "angioedema" => 48,
                "breast_discomfort" => null,
                "slow_hr" => 89,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 35,
            "drugs" => array($ARB[1], $DIURETIC[1], $CCB[0], $CCB[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 36
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 34,
                "hyponatremia" => 76,
                "hypokalemia" => 76,
                "cough" => null,
                "elevated_cr" => 49,
                "hyperkalemia" => 49,
                "angioedema" => 49,
                "breast_discomfort" => null,
                "slow_hr" => 89,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 36,
            "drugs" => array($ARB[1], $DIURETIC[1], $CCB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 37
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 35,
                "hyponatremia" => 77,
                "hypokalemia" => 77,
                "cough" => null,
                "elevated_cr" => 50,
                "hyperkalemia" => 50,
                "angioedema" => 50,
                "breast_discomfort" => null,
                "slow_hr" => 89,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 37,
            "drugs" => array($ARB[1], $DIURETIC[1], $SPIRNO[0], $CCB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 38
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 36,
                "hyponatremia" => 82,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 55,
                "hyperkalemia" => 55,
                "angioedema" => 55,
                "breast_discomfort" => 39,
                "slow_hr" => 90,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 38,
            "drugs" => array($ARB[1], $DIURETIC[1], $SPIRNO[1], $CCB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 37,
                "hyponatremia" => 82,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 55,
                "hyperkalemia" => 55,
                "angioedema" => 55,
                "breast_discomfort" => 40,
                "slow_hr" => 91,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 39,
            "drugs" => array($ARB[1], $DIURETIC[1], $SPIRNO[2], $CCB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 40
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 36,
                "hyponatremia" => 82,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 55,
                "hyperkalemia" => 55,
                "angioedema" => 55,
                "breast_discomfort" => null,
                "slow_hr" => 92,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 40,
            "drugs" => array($ARB[1], $DIURETIC[1], $SPIRNO[3], $CCB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 39,
                "hyponatremia" => 82,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 55,
                "hyperkalemia" => 55,
                "angioedema" => 55,
                "breast_discomfort" => null,
                "slow_hr" => 93,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 41,
            "drugs" => array($ARB[1], $DIURETIC[1], $CCB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 42
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 35,
                "hyponatremia" => 82,
                "hypokalemia" => 82,
                "cough" => null,
                "elevated_cr" => 55,
                "hyperkalemia" => 55,
                "angioedema" => 55,
                "breast_discomfort" => null,
                "slow_hr" => 94,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 42,
            "drugs" => array($ARB[1], $DIURETIC[1], $CCB[2], $BB[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 43
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 41,
                "hyponatremia" => 83,
                "hypokalemia" => 83,
                "cough" => null,
                "elevated_cr" => 56,
                "hyperkalemia" => 56,
                "angioedema" => 56,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 43,
            "drugs" => array($ARB[1], $DIURETIC[1], $CCB[2], $BB[0], $BB[1], $BB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 44
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 42,
                "hyponatremia" => 84,
                "hypokalemia" => 84,
                "cough" => null,
                "elevated_cr" => 57,
                "hyperkalemia" => 57,
                "angioedema" => 57,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 44,
            "drugs" => array($ARB[1], $DIURETIC[1], $CCB[2], $BB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 45
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 43,
                "hyponatremia" => 85,
                "hypokalemia" => 85,
                "cough" => null,
                "elevated_cr" => 58,
                "hyperkalemia" => 58,
                "angioedema" => 58,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 45,
            "drugs" => array($ARB[1], $DIURETIC[1], $CCB[2], $BB[3]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 44,
                "hyponatremia" => 86,
                "hypokalemia" => 86,
                "cough" => null,
                "elevated_cr" => 59,
                "hyperkalemia" => 59,
                "angioedema" => 59,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 46,
            "drugs" => array($DIURETIC[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 47
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 205,
                "hyponatremia" => 205,
                "hypokalemia" => 205,
                "cough" => null,
                "elevated_cr" => 205,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 47,
            "drugs" => array($DIURETIC[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 48
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 46,
                "hyponatremia" => 205,
                "hypokalemia" => 205,
                "cough" => null,
                "elevated_cr" => 205,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 48,
            "drugs" => array($DIURETIC[1], $CCB[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 49
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 47,
                "hyponatremia" => 117,
                "hypokalemia" => 117,
                "cough" => null,
                "elevated_cr" => 117,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => 154,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 49,
            "drugs" => array($DIURETIC[1], $CCB[0], $CCB[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 50
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 48,
                "hyponatremia" => 118,
                "hypokalemia" => 118,
                "cough" => null,
                "elevated_cr" => 118,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => 154,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 50,
            "drugs" => array($DIURETIC[1], $CCB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 51
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 49,
                "hyponatremia" => 119,
                "hypokalemia" => 119,
                "cough" => null,
                "elevated_cr" => 119,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => 154,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 51,
            "drugs" => array($DIURETIC[1], $SPIRNO[0], $CCB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 52
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 50,
                "hyponatremia" => 124,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 124,
                "hyperkalemia" => 55,
                "angioedema" => null,
                "breast_discomfort" => 53,
                "slow_hr" => 155,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 52,
            "drugs" => array($DIURETIC[1], $SPIRNO[1], $CCB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 51,
                "hyponatremia" => 124,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 124,
                "hyperkalemia" => 55,
                "angioedema" => null,
                "breast_discomfort" => 54,
                "slow_hr" => 156,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 53,
            "drugs" => array($DIURETIC[1], $SPIRNO[2], $CCB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 54
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 50,
                "hyponatremia" => 124,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 124,
                "hyperkalemia" => 55,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => 157,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 54,
            "drugs" => array($DIURETIC[1], $SPIRNO[3], $CCB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 53,
                "hyponatremia" => 124,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 124,
                "hyperkalemia" => 55,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => 158,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 55,
            "drugs" => array($DIURETIC[1], $CCB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 56
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 49,
                "hyponatremia" => 124,
                "hypokalemia" => 124,
                "cough" => null,
                "elevated_cr" => 124,
                "hyperkalemia" => 55,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => 159,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 56,
            "drugs" => array($DIURETIC[1], $CCB[2], $BB[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 57
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 55,
                "hyponatremia" => 125,
                "hypokalemia" => 125,
                "cough" => null,
                "elevated_cr" => 125,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 57,
            "drugs" => array($DIURETIC[1], $CCB[2], $BB[0], $BB[1], $BB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 58
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 56,
                "hyponatremia" => 126,
                "hypokalemia" => 126,
                "cough" => null,
                "elevated_cr" => 126,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 58,
            "drugs" => array($DIURETIC[1], $CCB[2], $BB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 59
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 57,
                "hyponatremia" => 127,
                "hypokalemia" => 127,
                "cough" => null,
                "elevated_cr" => 127,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 59,
            "drugs" => array($DIURETIC[1], $CCB[2], $BB[3]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 58,
                "hyponatremia" => 128,
                "hypokalemia" => 128,
                "cough" => null,
                "elevated_cr" => 128,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 60,
            "drugs" => array($ACEI[0], $DIURETIC[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 61
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 205,
                "hyponatremia" => 129,
                "hypokalemia" => 129,
                "cough" => 87,
                "elevated_cr" => 152,
                "hyperkalemia" => 152,
                "angioedema" => 152,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 61,
            "drugs" => array($ACEI[1], $DIURETIC[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 62
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 60,
                "hyponatremia" => 130,
                "hypokalemia" => 130,
                "cough" => 88,
                "elevated_cr" => 152,
                "hyperkalemia" => 152,
                "angioedema" => 152,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 62,
            "drugs" => array($ACEI[1], $DIURETIC[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 63
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 61,
                "hyponatremia" => 130,
                "hypokalemia" => 130,
                "cough" => 89,
                "elevated_cr" => 153,
                "hyperkalemia" => 153,
                "angioedema" => 153,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 63,
            "drugs" => array($ACEI[2], $DIURETIC[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 64
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 62,
                "hyponatremia" => 131,
                "hypokalemia" => 131,
                "cough" => 89,
                "elevated_cr" => 153,
                "hyperkalemia" => 153,
                "angioedema" => 153,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 64,
            "drugs" => array($ACEI[2], $DIURETIC[1], $SPIRNO[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 65
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 63,
                "hyponatremia" => 136,
                "hypokalemia" => null,
                "cough" => 90,
                "elevated_cr" => 158,
                "hyperkalemia" => 158,
                "angioedema" => 154,
                "breast_discomfort" => 66,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 65,
            "drugs" => array($ACEI[2], $DIURETIC[1], $SPIRNO[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 64,
                "hyponatremia" => 136,
                "hypokalemia" => null,
                "cough" => 91,
                "elevated_cr" => 158,
                "hyperkalemia" => 158,
                "angioedema" => 155,
                "breast_discomfort" => 67,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 66,
            "drugs" => array($ACEI[2], $DIURETIC[1], $SPIRNO[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 67
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 63,
                "hyponatremia" => 136,
                "hypokalemia" => null,
                "cough" => 92,
                "elevated_cr" => 158,
                "hyperkalemia" => 158,
                "angioedema" => 156,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 67,
            "drugs" => array($ACEI[2], $DIURETIC[1], $SPIRNO[3]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 66,
                "hyponatremia" => 136,
                "hypokalemia" => null,
                "cough" => 93,
                "elevated_cr" => 158,
                "hyperkalemia" => 158,
                "angioedema" => 157,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 68,
            "drugs" => array($ACEI[2], $DIURETIC[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 69
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 62,
                "hyponatremia" => 136,
                "hypokalemia" => 136,
                "cough" => 94,
                "elevated_cr" => 158,
                "hyperkalemia" => 158,
                "angioedema" => 158,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 69,
            "drugs" => array($ACEI[2], $DIURETIC[1], $BB[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 70
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 68,
                "hyponatremia" => 137,
                "hypokalemia" => 137,
                "cough" => 95,
                "elevated_cr" => 159,
                "hyperkalemia" => 159,
                "angioedema" => 159,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 70,
            "drugs" => array($ACEI[2], $DIURETIC[1], $BB[0], $BB[1], $BB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 71
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 69,
                "hyponatremia" => 138,
                "hypokalemia" => 138,
                "cough" => 96,
                "elevated_cr" => 160,
                "hyperkalemia" => 160,
                "angioedema" => 160,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 71,
            "drugs" => array($ACEI[2], $DIURETIC[1], $BB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 72
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 70,
                "hyponatremia" => 139,
                "hypokalemia" => 139,
                "cough" => 97,
                "elevated_cr" => 161,
                "hyperkalemia" => 161,
                "angioedema" => 161,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 72,
            "drugs" => array($ACEI[2], $DIURETIC[1], $BB[3]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 71,
                "hyponatremia" => 140,
                "hypokalemia" => 140,
                "cough" => 98,
                "elevated_cr" => 162,
                "hyperkalemia" => 162,
                "angioedema" => 162,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 73,
            "drugs" => array($ARB[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 74
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 205,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 205,
                "hyperkalemia" => 205,
                "angioedema" => 205,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 74,
            "drugs" => array($ARB[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 75
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 73,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 205,
                "hyperkalemia" => 205,
                "angioedema" => 205,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 75,
            "drugs" => array($ARB[1], $CCB[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 76
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 74,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 117,
                "hyperkalemia" => 117,
                "angioedema" => 117,
                "breast_discomfort" => null,
                "slow_hr" => 142,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 76,
            "drugs" => array($ARB[1], $CCB[0], $CCB[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 77
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 75,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 118,
                "hyperkalemia" => 118,
                "angioedema" => 118,
                "breast_discomfort" => null,
                "slow_hr" => 142,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 77,
            "drugs" => array($ARB[1], $CCB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 78
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 76,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 119,
                "hyperkalemia" => 119,
                "angioedema" => 119,
                "breast_discomfort" => null,
                "slow_hr" => 142,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 78,
            "drugs" => array($ARB[1], $SPIRNO[0], $CCB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 79
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 77,
                "hyponatremia" => 82,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 124,
                "hyperkalemia" => 124,
                "angioedema" => 120,
                "breast_discomfort" => 80,
                "slow_hr" => 143,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 79,
            "drugs" => array($ARB[1], $SPIRNO[1], $CCB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 78,
                "hyponatremia" => 82,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 124,
                "hyperkalemia" => 124,
                "angioedema" => 121,
                "breast_discomfort" => 81,
                "slow_hr" => 144,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 80,
            "drugs" => array($ARB[1], $SPIRNO[2], $CCB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 81
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 77,
                "hyponatremia" => 82,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 124,
                "hyperkalemia" => 124,
                "angioedema" => 122,
                "breast_discomfort" => null,
                "slow_hr" => 145,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 81,
            "drugs" => array($ARB[1], $SPIRNO[3], $CCB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 80,
                "hyponatremia" => 82,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 124,
                "hyperkalemia" => 124,
                "angioedema" => 123,
                "breast_discomfort" => null,
                "slow_hr" => 146,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 82,
            "drugs" => array($ARB[1], $CCB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 83
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 76,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 124,
                "hyperkalemia" => 124,
                "angioedema" => 124,
                "breast_discomfort" => null,
                "slow_hr" => 147,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 83,
            "drugs" => array($ARB[1], $CCB[2], $BB[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 84
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 82,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 125,
                "hyperkalemia" => 125,
                "angioedema" => 125,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 84,
            "drugs" => array($ARB[1], $CCB[2], $BB[0], $BB[1], $BB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 85
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 83,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 126,
                "hyperkalemia" => 126,
                "angioedema" => 126,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 85,
            "drugs" => array($ARB[1], $CCB[2], $BB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 86
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 84,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 127,
                "hyperkalemia" => 127,
                "angioedema" => 127,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 86,
            "drugs" => array($ARB[1], $CCB[2], $BB[3]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 85,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 128,
                "hyperkalemia" => 128,
                "angioedema" => 128,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 87,
            "drugs" => array($ARB[0], $DIURETIC[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 88
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 152,
                "hyponatremia" => 141,
                "hypokalemia" => 141,
                "cough" => null,
                "elevated_cr" => 152,
                "hyperkalemia" => 152,
                "angioedema" => 152,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 88,
            "drugs" => array($ARB[1], $DIURETIC[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 89
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 87,
                "hyponatremia" => 142,
                "hypokalemia" => 142,
                "cough" => null,
                "elevated_cr" => 152,
                "hyperkalemia" => 152,
                "angioedema" => 152,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 89,
            "drugs" => array($ARB[1], $DIURETIC[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 90
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 88,
                "hyponatremia" => 142,
                "hypokalemia" => 142,
                "cough" => null,
                "elevated_cr" => 153,
                "hyperkalemia" => 153,
                "angioedema" => 153,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 90,
            "drugs" => array($ARB[1], $DIURETIC[1], $SPIRNO[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 91
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 89,
                "hyponatremia" => 147,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 158,
                "hyperkalemia" => 158,
                "angioedema" => null,
                "breast_discomfort" => 92,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 91,
            "drugs" => array($ARB[1], $DIURETIC[1], $SPIRNO[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 90,
                "hyponatremia" => 147,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 158,
                "hyperkalemia" => 158,
                "angioedema" => null,
                "breast_discomfort" => 93,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 92,
            "drugs" => array($ARB[1], $DIURETIC[1], $SPIRNO[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 93
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 89,
                "hyponatremia" => 147,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 158,
                "hyperkalemia" => 158,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 93,
            "drugs" => array($ARB[1], $DIURETIC[1], $SPIRNO[3]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 92,
                "hyponatremia" => 147,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 158,
                "hyperkalemia" => 158,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 94,
            "drugs" => array($ARB[1], $DIURETIC[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 95
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 88,
                "hyponatremia" => 147,
                "hypokalemia" => 147,
                "cough" => null,
                "elevated_cr" => 158,
                "hyperkalemia" => 158,
                "angioedema" => 158,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 95,
            "drugs" => array($ARB[1], $DIURETIC[1], $BB[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 96
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 94,
                "hyponatremia" => 148,
                "hypokalemia" => 148,
                "cough" => null,
                "elevated_cr" => 159,
                "hyperkalemia" => 159,
                "angioedema" => 159,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 96,
            "drugs" => array($ARB[1], $DIURETIC[1], $BB[0], $BB[1], $BB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 97
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 95,
                "hyponatremia" => 149,
                "hypokalemia" => 149,
                "cough" => null,
                "elevated_cr" => 160,
                "hyperkalemia" => 160,
                "angioedema" => 160,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 97,
            "drugs" => array($ARB[1], $DIURETIC[1], $BB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 98
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 96,
                "hyponatremia" => 150,
                "hypokalemia" => 150,
                "cough" => null,
                "elevated_cr" => 161,
                "hyperkalemia" => 161,
                "angioedema" => 161,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 98,
            "drugs" => array($ARB[1], $DIURETIC[1], $BB[3]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 97,
                "hyponatremia" => 151,
                "hypokalemia" => 151,
                "cough" => null,
                "elevated_cr" => 162,
                "hyperkalemia" => 162,
                "angioedema" => 162,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 99,
            "drugs" => array($ACEI[0], $DIURETIC[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 100
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 205,
                "hyponatremia" => 163,
                "hypokalemia" => 163,
                "cough" => 108,
                "elevated_cr" => 178,
                "hyperkalemia" => 178,
                "angioedema" => 178,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 100,
            "drugs" => array($ACEI[1], $DIURETIC[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 101
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 99,
                "hyponatremia" => 164,
                "hypokalemia" => 164,
                "cough" => 109,
                "elevated_cr" => 178,
                "hyperkalemia" => 178,
                "angioedema" => 178,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 101,
            "drugs" => array($ACEI[1], $DIURETIC[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 102
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 100,
                "hyponatremia" => 164,
                "hypokalemia" => 164,
                "cough" => 110,
                "elevated_cr" => 179,
                "hyperkalemia" => 179,
                "angioedema" => 179,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 102,
            "drugs" => array($ACEI[2], $DIURETIC[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 103
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 101,
                "hyponatremia" => 165,
                "hypokalemia" => 165,
                "cough" => 110,
                "elevated_cr" => 179,
                "hyperkalemia" => 179,
                "angioedema" => 179,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 103,
            "drugs" => array($ACEI[2], $DIURETIC[1], $SPIRNO[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 104
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 102,
                "hyponatremia" => "Refer",
                "hypokalemia" => null,
                "cough" => 111,
                "elevated_cr" => 184,
                "hyperkalemia" => 184,
                "angioedema" => 180,
                "breast_discomfort" => 105,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 104,
            "drugs" => array($ACEI[2], $DIURETIC[1], $SPIRNO[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 103,
                "hyponatremia" => "Refer",
                "hypokalemia" => null,
                "cough" => 112,
                "elevated_cr" => 184,
                "hyperkalemia" => 184,
                "angioedema" => 181,
                "breast_discomfort" => 106,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 105,
            "drugs" => array($ACEI[2], $DIURETIC[1], $SPIRNO[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 106
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 102,
                "hyponatremia" => "Refer",
                "hypokalemia" => null,
                "cough" => 113,
                "elevated_cr" => 184,
                "hyperkalemia" => 184,
                "angioedema" => 182,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 106,
            "drugs" => array($ACEI[2], $DIURETIC[1], $SPIRNO[3]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 105,
                "hyponatremia" => "Refer",
                "hypokalemia" => null,
                "cough" => 114,
                "elevated_cr" => 184,
                "hyperkalemia" => 184,
                "angioedema" => 183,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 107,
            "drugs" => array($ACEI[2], $DIURETIC[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 101,
                "hyponatremia" => "Refer",
                "hypokalemia" => 169,
                "cough" => 115,
                "elevated_cr" => 184,
                "hyperkalemia" => 184,
                "angioedema" => 184,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 108,
            "drugs" => array($ARB[0], $DIURETIC[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 109
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 152,
                "hyponatremia" => 171,
                "hypokalemia" => 171,
                "cough" => null,
                "elevated_cr" => 178,
                "hyperkalemia" => 178,
                "angioedema" => 178,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 109,
            "drugs" => array($ARB[1], $DIURETIC[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 110
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 108,
                "hyponatremia" => 172,
                "hypokalemia" => 172,
                "cough" => null,
                "elevated_cr" => 178,
                "hyperkalemia" => 178,
                "angioedema" => 178,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 110,
            "drugs" => array($ARB[1], $DIURETIC[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 111
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 109,
                "hyponatremia" => 172,
                "hypokalemia" => 172,
                "cough" => null,
                "elevated_cr" => 179,
                "hyperkalemia" => 179,
                "angioedema" => 179,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 111,
            "drugs" => array($ARB[1], $DIURETIC[1], $SPIRNO[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 112
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 110,
                "hyponatremia" => 177,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 184,
                "hyperkalemia" => 184,
                "angioedema" => 180,
                "breast_discomfort" => 113,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 112,
            "drugs" => array($ARB[1], $DIURETIC[1], $SPIRNO[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 111,
                "hyponatremia" => 177,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 184,
                "hyperkalemia" => 184,
                "angioedema" => 181,
                "breast_discomfort" => 114,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 113,
            "drugs" => array($ARB[1], $DIURETIC[1], $SPIRNO[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 114
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 110,
                "hyponatremia" => 177,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 184,
                "hyperkalemia" => 184,
                "angioedema" => 182,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 114,
            "drugs" => array($ARB[1], $DIURETIC[1], $SPIRNO[3]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 113,
                "hyponatremia" => 177,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 184,
                "hyperkalemia" => 184,
                "angioedema" => 183,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 115,
            "drugs" => array($ARB[1], $DIURETIC[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 109,
                "hyponatremia" => 177,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 184,
                "hyperkalemia" => 184,
                "angioedema" => 184,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 116,
            "drugs" => array(),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 117
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 205,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 117,
            "drugs" => array($CCB[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 118
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 205,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 118,
            "drugs" => array($CCB[0], $CCB[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 119
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 117,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 119,
            "drugs" => array($CCB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 120
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 118,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 120,
            "drugs" => array($SPIRNO[0], $CCB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 121
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 119,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 124,
                "hyperkalemia" => 124,
                "angioedema" => null,
                "breast_discomfort" => 122,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 121,
            "drugs" => array($SPIRNO[1], $CCB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 120,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 124,
                "hyperkalemia" => 124,
                "angioedema" => null,
                "breast_discomfort" => 123,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 122,
            "drugs" => array($SPIRNO[2], $CCB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 123
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 119,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 124,
                "hyperkalemia" => 124,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 123,
            "drugs" => array($SPIRNO[3], $CCB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 122,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 124,
                "hyperkalemia" => 124,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 124,
            "drugs" => array($CCB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 125
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 118,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 125,
            "drugs" => array($CCB[2], $BB[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 126
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 124,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 126,
            "drugs" => array($CCB[2], $BB[0], $BB[1], $BB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 127
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 125,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 127,
            "drugs" => array($CCB[2], $BB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 128
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 126,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 128,
            "drugs" => array($CCB[2], $BB[3]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 127,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 129,
            "drugs" => array($ACEI[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 130
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 205,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => 141,
                "elevated_cr" => 190,
                "hyperkalemia" => 190,
                "angioedema" => 195,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 130,
            "drugs" => array($ACEI[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 131
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 129,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => 142,
                "elevated_cr" => 190,
                "hyperkalemia" => 190,
                "angioedema" => 195,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 131,
            "drugs" => array($ACEI[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 132
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 130,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => 142,
                "elevated_cr" => 190,
                "hyperkalemia" => 190,
                "angioedema" => 195,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 132,
            "drugs" => array($ACEI[2], $SPIRNO[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 133
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 131,
                "hyponatremia" => 136,
                "hypokalemia" => null,
                "cough" => 143,
                "elevated_cr" => 190,
                "hyperkalemia" => 190,
                "angioedema" => 196,
                "breast_discomfort" => 134,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 133,
            "drugs" => array($ACEI[2], $SPIRNO[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 132,
                "hyponatremia" => 136,
                "hypokalemia" => null,
                "cough" => 144,
                "elevated_cr" => 190,
                "hyperkalemia" => 190,
                "angioedema" => 197,
                "breast_discomfort" => 135,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 134,
            "drugs" => array($ACEI[2], $SPIRNO[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 135
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 131,
                "hyponatremia" => 136,
                "hypokalemia" => null,
                "cough" => 145,
                "elevated_cr" => 190,
                "hyperkalemia" => 190,
                "angioedema" => 198,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 135,
            "drugs" => array($ACEI[2], $SPIRNO[3]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 134,
                "hyponatremia" => 136,
                "hypokalemia" => null,
                "cough" => 146,
                "elevated_cr" => 190,
                "hyperkalemia" => 190,
                "angioedema" => 199,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 136,
            "drugs" => array($ACEI[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 137
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 130,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => 147,
                "elevated_cr" => 190,
                "hyperkalemia" => 190,
                "angioedema" => 200,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 137,
            "drugs" => array($ACEI[2], $BB[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 138
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 136,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => 148,
                "elevated_cr" => 191,
                "hyperkalemia" => 191,
                "angioedema" => 201,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 138,
            "drugs" => array($ACEI[2], $BB[0], $BB[1], $BB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 139
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 137,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => 149,
                "elevated_cr" => 192,
                "hyperkalemia" => 192,
                "angioedema" => 202,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 139,
            "drugs" => array($ACEI[2], $BB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 140
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 138,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => 150,
                "elevated_cr" => 193,
                "hyperkalemia" => 193,
                "angioedema" => 203,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 140,
            "drugs" => array($ACEI[2], $BB[3]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 139,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => 151,
                "elevated_cr" => 194,
                "hyperkalemia" => 194,
                "angioedema" => 204,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 141,
            "drugs" => array($ARB[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 142
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 205,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 190,
                "hyperkalemia" => 190,
                "angioedema" => 195,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 142,
            "drugs" => array($ARB[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 143
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 141,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 190,
                "hyperkalemia" => 190,
                "angioedema" => 195,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 143,
            "drugs" => array($ARB[1], $SPIRNO[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 144
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 142,
                "hyponatremia" => 147,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 190,
                "hyperkalemia" => 190,
                "angioedema" => 196,
                "breast_discomfort" => 145,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 144,
            "drugs" => array($ARB[1], $SPIRNO[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 143,
                "hyponatremia" => 147,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 190,
                "hyperkalemia" => 190,
                "angioedema" => 197,
                "breast_discomfort" => 146,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 145,
            "drugs" => array($ARB[1], $SPIRNO[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 146
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 142,
                "hyponatremia" => 147,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 190,
                "hyperkalemia" => 190,
                "angioedema" => 198,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 146,
            "drugs" => array($ARB[1], $SPIRNO[3]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 145,
                "hyponatremia" => 147,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 190,
                "hyperkalemia" => 190,
                "angioedema" => 199,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 147,
            "drugs" => array($ARB[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 148
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 140,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 190,
                "hyperkalemia" => 190,
                "angioedema" => 200,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 148,
            "drugs" => array($ARB[1], $BB[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 149
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 147,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 191,
                "hyperkalemia" => 191,
                "angioedema" => 201,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 149,
            "drugs" => array($ARB[1], $BB[0], $BB[1], $BB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 150
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 148,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 192,
                "hyperkalemia" => 192,
                "angioedema" => 202,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 150,
            "drugs" => array($ARB[1], $BB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 151
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 149,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 193,
                "hyperkalemia" => 193,
                "angioedema" => 203,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 151,
            "drugs" => array($ARB[1], $BB[3]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 150,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 194,
                "hyperkalemia" => 194,
                "angioedema" => 204,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 152,
            "drugs" => array($DIURETIC[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 153
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 205,
                "hyponatremia" => 205,
                "hypokalemia" => 205,
                "cough" => null,
                "elevated_cr" => 190,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 153,
            "drugs" => array($DIURETIC[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 154
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 152,
                "hyponatremia" => 190,
                "hypokalemia" => 205,
                "cough" => null,
                "elevated_cr" => 190,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 154,
            "drugs" => array($DIURETIC[1], $SPIRNO[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 155
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 153,
                "hyponatremia" => 190,
                "hypokalemia" => 143,
                "cough" => null,
                "elevated_cr" => 190,
                "hyperkalemia" => 158,
                "angioedema" => null,
                "breast_discomfort" => 156,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 155,
            "drugs" => array($DIURETIC[1], $SPIRNO[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 154,
                "hyponatremia" => 190,
                "hypokalemia" => 144,
                "cough" => null,
                "elevated_cr" => 190,
                "hyperkalemia" => 158,
                "angioedema" => null,
                "breast_discomfort" => 157,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 156,
            "drugs" => array($DIURETIC[1], $SPIRNO[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 157
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 153,
                "hyponatremia" => 190,
                "hypokalemia" => 145,
                "cough" => null,
                "elevated_cr" => 190,
                "hyperkalemia" => 158,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 157,
            "drugs" => array($DIURETIC[1], $SPIRNO[3]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 156,
                "hyponatremia" => 190,
                "hypokalemia" => 146,
                "cough" => null,
                "elevated_cr" => 190,
                "hyperkalemia" => 158,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 158,
            "drugs" => array($DIURETIC[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 159
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 152,
                "hyponatremia" => 190,
                "hypokalemia" => 190,
                "cough" => null,
                "elevated_cr" => 190,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 159,
            "drugs" => array($DIURETIC[1], $BB[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 160
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 158,
                "hyponatremia" => 191,
                "hypokalemia" => 191,
                "cough" => null,
                "elevated_cr" => 191,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 160,
            "drugs" => array($DIURETIC[1], $BB[0], $BB[1], $BB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 161
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 159,
                "hyponatremia" => 192,
                "hypokalemia" => 192,
                "cough" => null,
                "elevated_cr" => 192,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 161,
            "drugs" => array($DIURETIC[1], $BB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 162
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 160,
                "hyponatremia" => 193,
                "hypokalemia" => 193,
                "cough" => null,
                "elevated_cr" => 193,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 162,
            "drugs" => array($DIURETIC[1], $BB[3]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 161,
                "hyponatremia" => 194,
                "hypokalemia" => 194,
                "cough" => null,
                "elevated_cr" => 194,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 163,
            "drugs" => array($ACEI[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 164
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 205,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => 171,
                "elevated_cr" => 205,
                "hyperkalemia" => 205,
                "angioedema" => 205,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 164,
            "drugs" => array($ACEI[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 165
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 163,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => 172,
                "elevated_cr" => 205,
                "hyperkalemia" => 205,
                "angioedema" => 205,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 165,
            "drugs" => array($ACEI[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 166
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 164,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => 172,
                "elevated_cr" => 205,
                "hyperkalemia" => 205,
                "angioedema" => 205,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 166,
            "drugs" => array($ACEI[2], $SPIRNO[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 167
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 165,
                "hyponatremia" => 170,
                "hypokalemia" => null,
                "cough" => 173,
                "elevated_cr" => 205,
                "hyperkalemia" => 205,
                "angioedema" => 185,
                "breast_discomfort" => 168,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 167,
            "drugs" => array($ACEI[2], $SPIRNO[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 166,
                "hyponatremia" => 170,
                "hypokalemia" => null,
                "cough" => 174,
                "elevated_cr" => 205,
                "hyperkalemia" => 205,
                "angioedema" => 186,
                "breast_discomfort" => 169,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 168,
            "drugs" => array($ACEI[2], $SPIRNO[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 169
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 165,
                "hyponatremia" => 170,
                "hypokalemia" => null,
                "cough" => 175,
                "elevated_cr" => 205,
                "hyperkalemia" => 205,
                "angioedema" => 187,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 169,
            "drugs" => array($ACEI[2], $SPIRNO[3]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 168,
                "hyponatremia" => 170,
                "hypokalemia" => null,
                "cough" => 176,
                "elevated_cr" => 205,
                "hyperkalemia" => 205,
                "angioedema" => 188,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 170,
            "drugs" => array($ACEI[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 164,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => 177,
                "elevated_cr" => 205,
                "hyperkalemia" => 205,
                "angioedema" => 205,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 171,
            "drugs" => array($ARB[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 172
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 205,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 205,
                "hyperkalemia" => 205,
                "angioedema" => 205,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 172,
            "drugs" => array($ARB[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 173
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 171,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 205,
                "hyperkalemia" => 205,
                "angioedema" => 205,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 173,
            "drugs" => array($ARB[1], $SPIRNO[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 174
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 172,
                "hyponatremia" => 177,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 205,
                "hyperkalemia" => 205,
                "angioedema" => 185,
                "breast_discomfort" => 175,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 174,
            "drugs" => array($ARB[1], $SPIRNO[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 173,
                "hyponatremia" => 177,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 205,
                "hyperkalemia" => 205,
                "angioedema" => 186,
                "breast_discomfort" => 176,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 175,
            "drugs" => array($ARB[1], $SPIRNO[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 176
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 172,
                "hyponatremia" => 177,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 205,
                "hyperkalemia" => 205,
                "angioedema" => 187,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 176,
            "drugs" => array($ARB[1], $SPIRNO[3]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 175,
                "hyponatremia" => 177,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 205,
                "hyperkalemia" => 205,
                "angioedema" => 188,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 177,
            "drugs" => array($ARB[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 171,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 205,
                "hyperkalemia" => 205,
                "angioedema" => 189,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 178,
            "drugs" => array($DIURETIC[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 179
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 205,
                "hyponatremia" => 205,
                "hypokalemia" => 205,
                "cough" => null,
                "elevated_cr" => 205,
                "hyperkalemia" => 205,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 179,
            "drugs" => array($DIURETIC[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 180
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 178,
                "hyponatremia" => 205,
                "hypokalemia" => 205,
                "cough" => null,
                "elevated_cr" => 205,
                "hyperkalemia" => 205,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 180,
            "drugs" => array($DIURETIC[1], $SPIRNO[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 181
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 179,
                "hyponatremia" => 205,
                "hypokalemia" => 185,
                "cough" => null,
                "elevated_cr" => 205,
                "hyperkalemia" => 205,
                "angioedema" => null,
                "breast_discomfort" => 182,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 181,
            "drugs" => array($DIURETIC[1], $SPIRNO[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 180,
                "hyponatremia" => 205,
                "hypokalemia" => 186,
                "cough" => null,
                "elevated_cr" => 205,
                "hyperkalemia" => 205,
                "angioedema" => null,
                "breast_discomfort" => 183,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 182,
            "drugs" => array($DIURETIC[1], $SPIRNO[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 183
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 179,
                "hyponatremia" => 205,
                "hypokalemia" => 187,
                "cough" => null,
                "elevated_cr" => 205,
                "hyperkalemia" => 205,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 183,
            "drugs" => array($SPIRNO[3]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 182,
                "hyponatremia" => 205,
                "hypokalemia" => 188,
                "cough" => null,
                "elevated_cr" => 205,
                "hyperkalemia" => 205,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 184,
            "drugs" => array($DIURETIC[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 178,
                "hyponatremia" => 205,
                "hypokalemia" => 189,
                "cough" => null,
                "elevated_cr" => 205,
                "hyperkalemia" => 205,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 185,
            "drugs" => array($SPIRNO[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 186
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 205,
                "hyponatremia" => 205,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 205,
                "hyperkalemia" => 205,
                "angioedema" => null,
                "breast_discomfort" => 187,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 186,
            "drugs" => array($SPIRNO[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 185,
                "hyponatremia" => 205,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 205,
                "hyperkalemia" => 205,
                "angioedema" => null,
                "breast_discomfort" => 188,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 187,
            "drugs" => array($SPIRNO[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 188
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 186,
                "hyponatremia" => 205,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 205,
                "hyperkalemia" => 205,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 188,
            "drugs" => array($SPIRNO[3]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 205,
                "hyponatremia" => 205,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 205,
                "hyperkalemia" => 205,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 189,
            "drugs" => array(),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => null,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 190,
            "drugs" => array(),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 191
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 205,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 191,
            "drugs" => array($BB[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 192
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 190,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 192,
            "drugs" => array($BB[0], $BB[1], $BB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 193
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 191,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 193,
            "drugs" => array($BB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 194
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 192,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 194,
            "drugs" => array($BB[3]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 193,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 195,
            "drugs" => array(),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 196
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 205,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 196,
            "drugs" => array($SPIRNO[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 197
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 195,
                "hyponatremia" => 200,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 200,
                "hyperkalemia" => 200,
                "angioedema" => null,
                "breast_discomfort" => 198,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 197,
            "drugs" => array($SPIRNO[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 196,
                "hyponatremia" => 200,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 200,
                "hyperkalemia" => 200,
                "angioedema" => null,
                "breast_discomfort" => 199,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 198,
            "drugs" => array($SPIRNO[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 199
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 195,
                "hyponatremia" => 200,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 200,
                "hyperkalemia" => 200,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 199,
            "drugs" => array($SPIRNO[3]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 198,
                "hyponatremia" => 200,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => 200,
                "hyperkalemia" => 200,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 200,
            "drugs" => array(),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 201
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => null,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 201,
            "drugs" => array($BB[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 202
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 200,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 202,
            "drugs" => array($BB[0], $BB[1], $BB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 203
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 201,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 203,
            "drugs" => array($BB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 204
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 202,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 204,
            "drugs" => array($BB[3]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 203,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 205,
            "drugs" => array(),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => null,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => null,
            )
        );

        return $logicTree;
    }

    public function defaultLogicTree2($template_drugs){
        //CCB DEFAULT 1
        $template_drugs["SPIRNO"]["pretty"] = array_merge($template_drugs["SPIRNO"]["pretty"], $template_drugs["EPLER"]["pretty"]);

        $ACEI           = array();
        $ARB            = array();
        $DIURETIC       = array();
        $SPIRNO         = array();
        $CCB            = array();
        $BB             = array();

        foreach ($template_drugs as $drug_class => $drugs) {
            foreach ($drugs["pretty"] as $drug) {
                ${$drug_class}[] = $drug;
            }
        }

        $logicTree[] = array(
            "step_id" => 0,
            "drugs" => array($CCB[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 1
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => "Refer",
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => 18,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 1,
            "drugs" => array($CCB[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 2
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 0,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => 19,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 2,
            "drugs" => array($CCB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 3
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 1,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => 19,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 3,
            "drugs" => array($CCB[2], $DIURETIC[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 4
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 2,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => 19,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 4,
            "drugs" => array($CCB[2], $DIURETIC[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 5
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 3,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => 20,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 5,
            "drugs" => array($CCB[2], $DIURETIC[1], $ARB[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 6
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 4,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => 20,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 6,
            "drugs" => array($CCB[2], $DIURETIC[1], $ARB[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 7
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 5,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => 20,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 7,
            "drugs" => array($CCB[2], $DIURETIC[1], $ARB[1], $SPIRNO[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 8
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 6,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => 9,
                "slow_hr" => 20,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 8,
            "drugs" => array($CCB[2], $DIURETIC[1], $ARB[1], $SPIRNO[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 7,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => 10,
                "slow_hr" => 21,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 9,
            "drugs" => array($CCB[2], $DIURETIC[1], $ARB[1], $SPIRNO[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 10
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 6,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => 22,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 10,
            "drugs" => array($CCB[2], $DIURETIC[1], $ARB[1], $SPIRNO[3]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 9,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => 23,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 11,
            "drugs" => array($CCB[2], $DIURETIC[1], $ARB[1], $BB[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 12
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 6,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 12,
            "drugs" => array($CCB[2], $DIURETIC[1], $ARB[1], $BB[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 13
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 11,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 13,
            "drugs" => array($CCB[2], $DIURETIC[1], $ARB[1], $BB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 14
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 12,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 14,
            "drugs" => array($CCB[2], $DIURETIC[1], $ARB[1], $BB[3]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 13,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 18,
            "drugs" => array($DIURETIC[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => null
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => null,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => null,
            )
        );

        $logicTree[] = array(
            "step_id" => 19,
            "drugs" => array($DIURETIC[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => null
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => null,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => null,
            )
        );

        $logicTree[] = array(
            "step_id" => 20,
            "drugs" => array($DIURETIC[1], $SPIRNO[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => null
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => null,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => 22,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => null,
            )
        );

        $logicTree[] = array(
            "step_id" => 21,
            "drugs" => array($DIURETIC[1], $SPIRNO[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => null
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => null,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => 23,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => null,
            )
        );

        $logicTree[] = array(
            "step_id" => 22,
            "drugs" => array($DIURETIC[1], $SPIRNO[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => null
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => null,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => null,
            )
        );

        $logicTree[] = array(
            "step_id" => 23,
            "drugs" => array($DIURETIC[1], $SPIRNO[3]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => null
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => null,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => null,
            )
        );

        $logicTree[] = array(
            "step_id" => 24,
            "drugs" => array($DIURETIC[1], $BB[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => null
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => null,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => null,
            )
        );

        $logicTree[] = array(
            "step_id" => 25,
            "drugs" => array($DIURETIC[1], $BB[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => null
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => null,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => null,
            )
        );

        $logicTree[] = array(
            "step_id" => 26,
            "drugs" => array($DIURETIC[1], $BB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => null
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => null,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => null,
            )
        );

        $logicTree[] = array(
            "step_id" => 27,
            "drugs" => array($DIURETIC[1], $BB[3]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => null
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => null,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => null,
            )
        );


        return $logicTree;
    }

    public function defaultLogicTree3($template_drugs){
        //Thiazides CCB DEFAULT 2
        $template_drugs["SPIRNO"]["pretty"] = array_merge($template_drugs["SPIRNO"]["pretty"], $template_drugs["EPLER"]["pretty"]);

        $ACEI           = array();
        $ARB            = array();
        $DIURETIC       = array();
        $SPIRNO         = array();
        $CCB            = array();
        $BB             = array();

        foreach ($template_drugs as $drug_class => $drugs) {
            foreach ($drugs["pretty"] as $drug) {
                ${$drug_class}[] = $drug;
            }
        }

        $logicTree[] = array(
            "step_id" => 0,
            "drugs" => array($CCB[0], $DIURETIC[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 1
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => "Refer",
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => 18,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 1,
            "drugs" => array($CCB[1], $DIURETIC[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 2
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 0,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => 19,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 2,
            "drugs" => array($CCB[1], $DIURETIC[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 3
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 1,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => 19,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 3,
            "drugs" => array($CCB[2], $DIURETIC[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 4
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 2,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => 19,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 4,
            "drugs" => array($CCB[2], $DIURETIC[1], $ARB[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 5
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 3,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => 20,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 5,
            "drugs" => array($CCB[2], $DIURETIC[1], $ARB[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 6
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 4,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => 20,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 6,
            "drugs" => array($CCB[2], $DIURETIC[1], $ARB[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 7
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 5,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => 20,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 7,
            "drugs" => array($CCB[2], $DIURETIC[1], $ARB[1], $SPIRNO[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 8
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 6,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => 9,
                "slow_hr" => 20,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 8,
            "drugs" => array($CCB[2], $DIURETIC[1], $ARB[1], $SPIRNO[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 7,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => 10,
                "slow_hr" => 21,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 9,
            "drugs" => array($CCB[2], $DIURETIC[1], $ARB[1], $SPIRNO[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 10
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 6,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => 22,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 10,
            "drugs" => array($CCB[2], $DIURETIC[1], $ARB[1], $SPIRNO[3]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 9,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => 23,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 11,
            "drugs" => array($CCB[2], $DIURETIC[1], $ARB[1], $BB[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 12
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 6,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 12,
            "drugs" => array($CCB[2], $DIURETIC[1], $ARB[1], $BB[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 13
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 11,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 13,
            "drugs" => array($CCB[2], $DIURETIC[1], $ARB[1], $BB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 14
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 12,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 14,
            "drugs" => array($CCB[2], $DIURETIC[1], $ARB[1], $BB[3]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 13,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 18,
            "drugs" => array($DIURETIC[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => null
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => null,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => null,
            )
        );

        $logicTree[] = array(
            "step_id" => 19,
            "drugs" => array($DIURETIC[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => null
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => null,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => null,
            )
        );

        $logicTree[] = array(
            "step_id" => 20,
            "drugs" => array($DIURETIC[1], $SPIRNO[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => null
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => null,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => 22,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => null,
            )
        );

        $logicTree[] = array(
            "step_id" => 21,
            "drugs" => array($DIURETIC[1], $SPIRNO[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => null
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => null,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => 23,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => null,
            )
        );

        $logicTree[] = array(
            "step_id" => 22,
            "drugs" => array($DIURETIC[1], $SPIRNO[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => null
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => null,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => null,
            )
        );

        $logicTree[] = array(
            "step_id" => 23,
            "drugs" => array($DIURETIC[1], $SPIRNO[3]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => null
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => null,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => null,
            )
        );

        $logicTree[] = array(
            "step_id" => 24,
            "drugs" => array($DIURETIC[1], $BB[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => null
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => null,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => null,
            )
        );

        $logicTree[] = array(
            "step_id" => 25,
            "drugs" => array($DIURETIC[1], $BB[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => null
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => null,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => null,
            )
        );

        $logicTree[] = array(
            "step_id" => 26,
            "drugs" => array($DIURETIC[1], $BB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => null
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => null,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => null,
            )
        );

        $logicTree[] = array(
            "step_id" => 27,
            "drugs" => array($DIURETIC[1], $BB[3]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => null
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => null,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => null,
            )
        );

        return $logicTree;
    }

    public function defaultLogicTree4($template_drugs){
        //Thiazides ARB Default 3
        $template_drugs["SPIRNO"]["pretty"] = array_merge($template_drugs["SPIRNO"]["pretty"], $template_drugs["EPLER"]["pretty"]);

        $ACEI           = array();
        $ARB            = array();
        $DIURETIC       = array();
        $SPIRNO         = array();
        $CCB            = array();
        $BB             = array();

        foreach ($template_drugs as $drug_class => $drugs) {
            foreach ($drugs["pretty"] as $drug) {
                ${$drug_class}[] = $drug;
            }
        }

        $logicTree[] = array(
            "step_id" => 0,
            "drugs" => array($DIURETIC[0], $ARB[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 1
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => "Refer",
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => 18,
                "breast_discomfort" => null,
                "slow_hr" => 18,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 1,
            "drugs" => array($DIURETIC[0], $ARB[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 2
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 0,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => 18,
                "breast_discomfort" => null,
                "slow_hr" => 19,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 2,
            "drugs" => array($DIURETIC[1], $ARB[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 3
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 1,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => 19,
                "breast_discomfort" => null,
                "slow_hr" => 19,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 3,
            "drugs" => array($CCB[0],$DIURETIC[1], $ARB[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 4
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 2,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => 19,
                "breast_discomfort" => null,
                "slow_hr" => 20,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 4,
            "drugs" => array($CCB[1],$DIURETIC[1], $ARB[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 5
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 3,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => 19,
                "breast_discomfort" => null,
                "slow_hr" => 20,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 5,
            "drugs" => array($CCB[2], $DIURETIC[1], $ARB[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 6
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 4,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => 20,
                "breast_discomfort" => null,
                "slow_hr" => 20,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 6,
            "drugs" => array($CCB[2], $DIURETIC[1], $ARB[1], $SPIRNO[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 7
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 5,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => 20,
                "breast_discomfort" => 8,
                "slow_hr" => 21,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 7,
            "drugs" => array($CCB[2], $DIURETIC[1], $ARB[1], $SPIRNO[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 6,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => 21,
                "breast_discomfort" => 9,
                "slow_hr" => 21,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 8,
            "drugs" => array($CCB[2], $DIURETIC[1], $ARB[1], $SPIRNO[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 9
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 5,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => 22,
                "breast_discomfort" => null,
                "slow_hr" => 22,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 9,
            "drugs" => array($CCB[2], $DIURETIC[1], $ARB[1], $SPIRNO[3]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 8,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => 23,
                "breast_discomfort" => null,
                "slow_hr" => 23,
                "asthma" => null,
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 10,
            "drugs" => array($CCB[2], $DIURETIC[1], $ARB[1], $BB[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 11
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 4,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => 24,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 11,
            "drugs" => array($CCB[2], $DIURETIC[1], $ARB[1], $BB[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 12
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 10,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => 25,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 12,
            "drugs" => array($CCB[2], $DIURETIC[1], $ARB[1], $BB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => 13
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 11,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => 26,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 13,
            "drugs" => array($CCB[2], $DIURETIC[1], $ARB[1], $BB[3]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => "Refer"
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => 12,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => 27,
                "breast_discomfort" => null,
                "slow_hr" => "Stop",
                "asthma" => "Stop",
                "rash_other" => "Stop",
            )
        );

        $logicTree[] = array(
            "step_id" => 18,
            "drugs" => array($DIURETIC[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => null
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => null,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => null,
            )
        );

        $logicTree[] = array(
            "step_id" => 19,
            "drugs" => array($DIURETIC[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => null
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => null,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => null,
            )
        );

        $logicTree[] = array(
            "step_id" => 20,
            "drugs" => array($DIURETIC[1], $SPIRNO[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => null
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => null,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => 22,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => null,
            )
        );

        $logicTree[] = array(
            "step_id" => 21,
            "drugs" => array($DIURETIC[1], $SPIRNO[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => null
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => null,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => 23,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => null,
            )
        );

        $logicTree[] = array(
            "step_id" => 22,
            "drugs" => array($DIURETIC[1], $SPIRNO[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => null
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => null,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => null,
            )
        );

        $logicTree[] = array(
            "step_id" => 23,
            "drugs" => array($DIURETIC[1], $SPIRNO[3]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => null
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => null,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => null,
            )
        );

        $logicTree[] = array(
            "step_id" => 24,
            "drugs" => array($DIURETIC[1], $BB[0]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => null
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => null,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => null,
            )
        );

        $logicTree[] = array(
            "step_id" => 25,
            "drugs" => array($DIURETIC[1], $BB[1]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => null
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => null,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => null,
            )
        );

        $logicTree[] = array(
            "step_id" => 26,
            "drugs" => array($DIURETIC[1], $BB[2]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => null
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => null,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => null,
            )
        );

        $logicTree[] = array(
            "step_id" => 27,
            "drugs" => array($DIURETIC[1], $BB[3]),
            "bp_status" => array(
                "Controlled" => "Continue current step",
                "Uncontrolled" => null
            ),
            "note" => "",
            "side_effects" => array(
                "hypotension" => null,
                "hyponatremia" => null,
                "hypokalemia" => null,
                "cough" => null,
                "elevated_cr" => null,
                "hyperkalemia" => null,
                "angioedema" => null,
                "breast_discomfort" => null,
                "slow_hr" => null,
                "asthma" => null,
                "rash_other" => null,
            )
        );

        return $logicTree;
    }


















    public function defaultLogicTreeAlt($template_drugs, $tree_type){
        // Define drug classes and expected counts
        $drug_classes = array(
            "CCB"          => 4,  // Amlodipine doses: 0, 2.5, 5, 10
            "DIURETIC"     => 3,  // HCTZ doses: 0, 12.5, 25
            "BB"           => 5,  // Bisoprolol doses: 0, 2.5, 5, 7.5, 10
            "ARB"          => 3,  // Losartan doses: 0, 50, 100
            "SPIRNO"       => 3,  // Spironolactone doses: 0, 12.5, 25
            "EPLER"        => 3   // Eplerenone doses: 0, 1, 2
        );

        // Initialize drug arrays
        $initialized_drugs = $this->initializeDrugs($template_drugs, $drug_classes);
        $CCB      = $initialized_drugs['CCB'];
        $DIURETIC = $initialized_drugs['DIURETIC'];
        $BB       = $initialized_drugs['BB'];
        $ARB      = $initialized_drugs['ARB'];
        $SPIRNO   = $initialized_drugs['SPIRNO'];
        $EPLER    = $initialized_drugs['EPLER'];

        $logicTree = array();

        // Build logic tree steps based on $tree_type
        if($tree_type == 'CCB'){
            // Logic Tree for CCB
            $logicTree = $this->buildCCBLogicTree($CCB, $DIURETIC, $BB, $ARB, $SPIRNO, $EPLER);
        } elseif($tree_type == 'Thiazides_CCB'){
            // Logic Tree for Thiazides + CCB
            $logicTree = $this->buildThiazidesCCBLogicTree($CCB, $DIURETIC, $BB, $ARB, $SPIRNO, $EPLER);
        } elseif($tree_type == 'Thiazides_ARB'){
            // Logic Tree for Thiazides + ARB
            $logicTree = $this->buildThiazidesARBLogicTree($CCB, $DIURETIC, $BB, $ARB, $SPIRNO, $EPLER);
        } else {
            // Handle unknown tree type
            throw new Exception("Unknown tree type: " . $tree_type);
        }

        return $logicTree;
    }

    /**
     * Initializes drug arrays based on the provided template drugs.
     *
     * @param array $template_drugs The drugs and dosages from the template.
     * @param array $drug_classes   The expected drug classes and counts.
     * @return array                The initialized drug arrays.
     */
    private function initializeDrugs($template_drugs, $drug_classes) {
        $initialized_drugs = array();

        foreach ($drug_classes as $drug_class => $expected_count) {
            $initialized_drugs[$drug_class] = array();
            for ($i = 0; $i < $expected_count; $i++) {
                if (isset($template_drugs[$drug_class]["pretty"][$i])) {
                    $current_drug = $template_drugs[$drug_class]["pretty"][$i];
                } else {
                    $current_drug = null;
                }
                $initialized_drugs[$drug_class][] = $current_drug;
            }
        }
        return $initialized_drugs;
    }

    /**
     * Builds the logic tree for the CCB pathway.
     *
     * @return array The logic tree array.
     */
    private function buildCCBLogicTree($CCB, $DIURETIC, $BB, $ARB, $SPIRNO, $EPLER) {
        $logicTree = array();

        // Define steps based on your data
        // Example for steps 0 to 14
        $steps = array(
            array(
                "step_id" => 0,
                "drugs" => array($CCB[1], $DIURETIC[0], $BB[0], $ARB[0], $SPIRNO[0], $EPLER[0]),
                "bp_status" => array("Controlled" => "Continue current step", "Uncontrolled" => 1),
                "side_effects" => array("hypotension" => "Refer", "rash_other" => "Stop", "breast_discomfort" => 18),
            ),
            array(
                "step_id" => 1,
                "drugs" => array($CCB[2], $DIURETIC[0], $BB[0], $ARB[0], $SPIRNO[0], $EPLER[0]),
                "bp_status" => array("Controlled" => "Continue current step", "Uncontrolled" => 2),
                "side_effects" => array("hypotension" => 0, "rash_other" => "Stop", "breast_discomfort" => 19),
            ),
            // Continue adding steps 2 to 14
            // ...
        );

        // Build logic tree array
        foreach ($steps as $step) {
            $logicTree[] = $step;
        }

        // Return the logic tree
        return $logicTree;
    }

    /**
     * Builds the logic tree for the Thiazides + CCB pathway.
     *
     * @return array The logic tree array.
     */
    private function buildThiazidesCCBLogicTree($CCB, $DIURETIC, $BB, $ARB, $SPIRNO, $EPLER) {
        $logicTree = array();

        // Define steps based on your data
        // Example for steps 0 to 14
        $steps = array(
            array(
                "step_id" => 0,
                "drugs" => array($CCB[1], $DIURETIC[1], $BB[0], $ARB[0], $SPIRNO[0], $EPLER[0]),
                "bp_status" => array("Controlled" => "Continue current step", "Uncontrolled" => 1),
                "side_effects" => array("hypotension" => "Refer", "rash_other" => "Stop", "breast_discomfort" => 18),
            ),
            array(
                "step_id" => 1,
                "drugs" => array($CCB[2], $DIURETIC[1], $BB[0], $ARB[0], $SPIRNO[0], $EPLER[0]),
                "bp_status" => array("Controlled" => "Continue current step", "Uncontrolled" => 2),
                "side_effects" => array("hypotension" => 0, "rash_other" => "Stop", "breast_discomfort" => 19),
            ),
            // Continue adding steps 2 to 14
            // ...
        );

        // Build logic tree array
        foreach ($steps as $step) {
            $logicTree[] = $step;
        }

        // Return the logic tree
        return $logicTree;
    }

    /**
     * Builds the logic tree for the Thiazides + ARB pathway.
     *
     * @return array The logic tree array.
     */
    private function buildThiazidesARBLogicTree($CCB, $DIURETIC, $BB, $ARB, $SPIRNO, $EPLER) {
        $logicTree = array();

        // Define steps based on your data
        // Example for steps 0 to 13
        $steps = array(
            array(
                "step_id" => 0,
                "drugs" => array($CCB[0], $DIURETIC[1], $BB[0], $ARB[1], $SPIRNO[0], $EPLER[0]),
                "bp_status" => array("Controlled" => "Continue current step", "Uncontrolled" => 1),
                "side_effects" => array("hypotension" => "Refer", "rash_other" => "Stop", "breast_discomfort" => 18, "angioedema" => 18),
            ),
            array(
                "step_id" => 1,
                "drugs" => array($CCB[0], $DIURETIC[1], $BB[0], $ARB[2], $SPIRNO[0], $EPLER[0]),
                "bp_status" => array("Controlled" => "Continue current step", "Uncontrolled" => 2),
                "side_effects" => array("hypotension" => 0, "rash_other" => "Stop", "breast_discomfort" => 19, "angioedema" => 19),
            ),
            // Continue adding steps 2 to 13
            // ...
        );

        // Build logic tree array
        foreach ($steps as $step) {
            $logicTree[] = $step;
        }

        // Return the logic tree
        return $logicTree;
    }



}


?>
