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

    public function getPrescriptionTree($id,$API_TOKEN, $API_URL){
        $extra_params   = array(
            "records"   => $id,
            "events"     => "tree_arm_2"
        );
        $results    = RC::callApi($extra_params, true, $API_URL, $API_TOKEN);
        $trees      = array();
        $prev_id    = null;
        foreach($results as $result){
            $record_id  = $result["record_id"];

            if($prev_id !== $record_id){
                $trees[$record_id]          = array();
                $trees[$record_id]["name"]  = $result["htn_tree_name"];
            }else{
                $repeat     = $result["redcap_repeat_instrument"];
                $instance   = $result["redcap_repeat_instance"];

                if(!empty($repeat) && !empty($instance)){
                    if(!array_key_exists("meds", $trees[$record_id])){
                        $trees[$record_id]["meds"]  = array();
                    }

                    $steps = array();
                    for($i=1; $i < 11; $i++){
                        $evaluate   = "evaluate_step_" . $i;
                        $dose       = "step_dose_" .$i;
                        $freq       = "freq_step_" .$i;

                        $steps[$i]  = array(
                            $evaluate   => $result[$evaluate],
                            $dose       => $result[$dose],
                            $freq       => $result[$freq]
                        );
                    }

                    $trees[$record_id]["meds"][]    = array(
                        "class"     => $result["tree_med_class"],
                        "name"      => $result["tree_med_name"],
                        "note"      => $result["tree_med_note"],
                        "alt"       => $result["tree_med_alt_name"],
                        "alt_dose"  => $result["tree_med_alt_dose"],
                        "steps"     => $steps
                    );
                }
            }

            $prev_id = $record_id;
        }

        return $trees;
    }

    public function getPrescriptionTrees($API_TOKEN, $API_URL){
        $extra_params   = array(
            "fields"     => array("record_id","htn_tree_name"),
            "events"     => "tree_arm_2"
        );
        $results = RC::callApi($extra_params, true, $API_URL, $API_TOKEN);

        $trees = array();
        foreach($results as $result){
            $trees[$result["record_id"]] = $result["htn_tree_name"];
        }

        return $trees;
    }

    public function logPatientTreeChange($logdata,$API_TOKEN, $API_URL){
        $edit_id                    = $logdata["patient_id"] ?? null;

        if(is_null($edit_id)){
            return array("error" => "invalid record id");
        }
        $results    = $this->getPatient($edit_id,$API_TOKEN,$API_URL);
        $log_count  = count($results);

        $data["redcap_repeat_instance"]     = $log_count;
        $data["redcap_event_name"]          = "patients_arm_1";
        $data["redcap_repeat_instrument"]   = "tree_log";
        $data["record_id"]                  = $edit_id;

        $data["ptree_log_ts"]               = date("Y-m-d H:i:s");;
        $data["ptree_log_current_step"]     = $logdata["step_idx"];

        $cur_meds = array();
        $eval     = 7;
        foreach($logdata["current_meds"] as $med){
            array_push($cur_meds, $med["name"]. " " . $med["dose"] );
        }
        $data["ptree_current_meds"]         = implode(", ", $cur_meds);


        if($logdata["status"] == "Controlled"){
            $data["ptree_next_meds"]        = $data["ptree_current_meds"];
            $data["ptree_log_next_step"]    = $data["ptree_log_current_step"];
        }else if($logdata["status"] == "side effect"){
            // is this a repeat of previous step with side effect med? i think so
            $data["ptree_next_meds"]        = $data["ptree_current_meds"];
            $data["ptree_log_next_step"]    = $data["ptree_log_current_step"];
        }else{
            // not controlled
            $data["ptree_log_next_step"]  = $logdata["next_idx"];
            $next_meds = array();
            foreach($logdata["next_meds"] as $med){
                array_push($next_meds, $med["name"]. " " . $med["dose"] );
                $eval = $med["eval"] ?? $eval;
            }
            $data["ptree_next_meds"]      = implode(", ", $next_meds);
        }

        $status = "BP : " . $logdata["status"] ."\r\n\r\n";
        $status .= "Current meds : ". $data["ptree_current_meds"] ."\r\n" ;
        $status .= "Recommended next step meds : ". $data["ptree_next_meds"] ."\r\n\r\n" ;
        $status .= "Re-evaluate in $eval days.";

        $data["ptree_log_comment"]          = $status;
        $result                             = RC::writeToApi($data, array("overwriteBehavior" => "overwite"), $API_URL, $API_TOKEN);

        return array("result" => $result , "data" => $data);
    }

    public function savePrescriptionTree($ajax,$API_TOKEN, $API_URL){
        $response                   = array();
        $data                       = array();
        $treearr                    = $ajax;
        $edit_id                    = $treearr["record_id"] ?? null;


        $tree_name                  = $treearr["name"];
        $next_id                    = $edit_id ?? RC_Util::getNextId("record_id",$API_TOKEN, $API_URL);

        $data["record_id"]          = $next_id;
        $data["redcap_event_name"]  = "tree_arm_2";
        $data["htn_tree_name"]      = $tree_name;
        $result                     = RC::writeToApi($data, array("overwriteBehavior" => "overwite"), $API_URL, $API_TOKEN);

        if(array_key_exists("count", $result)){
            $response["record_id"]  = $next_id;
            $response["name"]       = $tree_name;
        }else{
            array_push($response, $result);
        }

        // Save Sub Trees
        $ladders                    = $treearr["treatment_plan"];

        foreach($ladders as $ladder){
            $instance_id                        = $instance_id ?? 1;

            $data                               = array();
            $data["redcap_event_name"]          = "tree_arm_2";
            $data["record_id"]                  = $next_id;
            $data["redcap_repeat_instance"]     = $instance_id;
            $data["redcap_repeat_instrument"]   = "meds";

            $data["tree_med_class"]             = $ladder["tree_med_class"];
            $data["tree_med_name"]              = $ladder["tree_med_name"];
            $data["tree_med_note"]              = $ladder["tree_med_note"];
            $data["tree_med_alt_name"]          = $ladder["tree_med_alt_name"];
            $data["tree_med_alt_dose"]          = $ladder["tree_med_alt_dose"];

            $steps                      = $ladder["steps"];
            foreach($steps as $idx => $step){
                $idx++;

                $evaluation = $step["evaluation"];
                $dose       = $step["dose"];
                $freq       = $step["freq"];

                if($dose !== "false" && $freq !== "false" && $evaluation !== "false"){
                    $data["evaluate_step_" . $idx]  = $evaluation;
                    $data["step_dose_" . $idx]      = $dose;
                    $data["freq_step_" . $idx]      = $freq;
                }
            }

            // NEED TO SAVE MULTIPLE SUB things
            $result  = RC::writeToApi($data, array("overwriteBehavior" => "overwite"), $API_URL, $API_TOKEN);
            if(!array_key_exists("count", $result)){
                array_push($response, $result);
            }

            $instance_id++;
        }

        return $response;
    }

    public function saveTemplate($provider_id, $post){
        $next_id    = !empty($post["record_id"]) ? $post["record_id"] : $this->module->getNextAvailableRecordId($this->tree_templates_project);
        $data       = array(
            "record_id"             => $next_id,
            "provider_id"           => $provider_id,
            "template_name"         => $post["template_name"],
            "acei_class"            => $post["acei_class"],
            "arb_class"             => $post["arb_class"],
            "diuretic_class"        => $post["diuretic_class"],
            "ccb_class"             => $post["ccb_class"],
            "bb_class"              => $post["bb_class"]
        );
        $r = \REDCap::saveData($this->tree_templates_project, 'json', json_encode(array($data)) );
        $this->module->emDebug("save_template", $data);
    }

    public function getDrugList(){
        return array(
            "Diuretics"  => array(
                 array( "name" => "HCTZ" , "unit" => "mg" , "common_dosage" => array(12.5,25,50) , "note" => "most effective when combined with ACEI" )
                ,array( "name" => "Chlorthalidone" , "unit" => "mg" , "common_dosage" => array(12.5,25) , "note" => "most effective when combined with ACEI" )
                ,array( "name" => "Indapamide" , "unit" => "mg" , "common_dosage" => array(1.25,2.5) , "note" => "most effective when combined with ACEI" )
                ,array( "name" => "Triamterene" , "unit" => "mg" , "common_dosage" => array(25,50) , "note" => "most effective when combined with ACEI" )
                ,array( "name" => "K+ sparing-spironolactone" , "unit" => "mg" , "common_dosage" => array(25,50) , "note" => "most effective when combined with ACEI" )
                ,array( "name" => "Amiloride" , "unit" => "mg" , "common_dosage" => array(5,10) , "note" => "most effective when combined with ACEI" )
                ,array( "name" => "Furosemide" , "unit" => "mg" , "common_dosage" => array(20,40,80) , "note" => "most effective when combined with ACEI", "frequency" => 2 )
                ,array( "name" => "Torsemide" , "unit" => "mg" , "common_dosage" => array(10,20,40) , "note" => "most effective when combined with ACEI" )
            )
        ,"ACEI - ACE Inhibtor" => array(
                 array( "name" => "Lisinopril" , "unit" => "mg" , "common_dosage" => array(10,20,30,40) , "note" => "" )
                ,array( "name" => "Benazaril" , "unit" => "mg" , "common_dosage" => array(5,10,20,40) , "note" => "" )
                ,array( "name" => "Fosinopril" , "unit" => "mg" , "common_dosage" => array(10,20,40) , "note" => "" )
                ,array( "name" => "Quinapril" , "unit" => "mg" , "common_dosage" => array(5,10,20,40) , "note" => "" )
                ,array( "name" => "Ramipril" , "unit" => "mg" , "common_dosage" => array(1.25,2.5,5,10) , "note" => "" )
                ,array( "name" => "Trandolapril" , "unit" => "mg" , "common_dosage" => array(1,2,4) , "note" => "" )
            )
        ,"ARB (Angiotensin receptor blocker)" => array(
                 array( "name" => "Candesartan" , "unit" => "mg" , "common_dosage" => array(8,16,32) , "note" => "may prevent migraine headaches" )
                ,array( "name" => "Valsartan" , "unit" => "mg" , "common_dosage" => array(40,80,160,320) , "note" => "" )
                ,array( "name" => "Iosartan" , "unit" => "mg" , "common_dosage" => array(50,100) , "note" => "" )
                ,array( "name" => "Olmesartan" , "unit" => "mg" , "common_dosage" => array(20,40) , "note" => "" )
                ,array( "name" => "Telmisartan" , "unit" => "mg" , "common_dosage" => array(20,40,80) , "note" => "" )
            )
        ,"Calcium Channel Blockers (CCB)" => array(
                 array( "name" => "Nifedipine ER" , "unit" => "mg" , "common_dosage" => array(30,60,90) , "note" => "" )
                ,array( "name" => "Diltiazem ER" , "unit" => "mg" , "common_dosage" => array(180,240,300,360) , "note" => "" )
                ,array( "name" => "Amlodipine" , "unit" => "mg" , "common_dosage" => array(2.5,5,10) , "note" => "" )
                ,array( "name" => "Verapamil" , "unit" => "mg" , "common_dosage" => array(80,120) , "note" => "" , "frequency" => 3)
                ,array( "name" => "Verapamil ER" , "unit" => "mg" , "common_dosage" => array(240,480) , "note" => "" )
            )
        ,"Beta-Blockers" => array(
                 array( "name" => "Metroprolol succinate" , "unit" => "mg" , "common_dosage" => array(1,2,3) , "note" => "" )
                ,array( "name" => "Tartrate" , "unit" => "mg" , "common_dosage" => array(50,100) , "note" => "" , "frequency" => 2)
                ,array( "name" => "Propranolol" , "unit" => "mg" , "common_dosage" => array(40,80,120) , "note" => "" , "frequency" => 2)
                ,array( "name" => "Carvedilol" , "unit" => "mg" , "common_dosage" => array(6.25,12.5,25) , "note" => "" , "frequency" => 2)
                ,array( "name" => "Bisoprolol" , "unit" => "mg" , "common_dosage" => array(5,10) , "note" => "" )
                ,array( "name" => "Labetalol" , "unit" => "mg" , "common_dosage" => array(100,200,300) , "note" => "" , "frequency" => 2)
                ,array( "name" => "Nebivolol" , "unit" => "mg" , "common_dosage" => array(5,10) , "note" => "" )
            )
        ,"Vasodilators" => array(
                 array( "name" => "Hydralazine" , "unit" => "mg" , "common_dosage" => array(25,50,100) , "note" => "" , "frequency" => 2)
                ,array( "name" => "Minoxidil" , "unit" => "mg" , "common_dosage" => array(5,10) , "note" => "" )
                ,array( "name" => "Terazosin" , "unit" => "mg" , "common_dosage" => array(1,2,5) , "note" => "" )
                ,array( "name" => "Doxazosin" , "unit" => "mg" , "common_dosage" => array(1,2,4) , "note" => "at bedtime" )
            )
        ,"Centrally-acting" => array(
                 array( "name" => "Clonidine" , "unit" => "mg" , "common_dosage" => array(0.1,0.2) , "note" => "" , "frequency" => 2)
                ,array( "name" => "Methyldopa" , "unit" => "mg" , "common_dosage" => array(250,500) , "note" => "" , "frequency" => 2)
                ,array( "name" => "Guanfacine" , "unit" => "mg" , "common_dosage" => array(1,2,3) , "note" => "" )
            )
        );

    }
    
    public function getTemplateDrugs($provider_id, $record_id=null){
        $filter = "[provider_id] = '$provider_id'";
        $fields = array("record_id", "provider_id","template_name", "acei_class", "arb_class","diuretic_class", "ccb_class", "bb_class");
		$params	= array(
			'project_id'	=> $this->tree_templates_project,
            'return_format' => 'json',
            'fields'        => $fields,
            'filterLogic'   => $filter 
		);
		if($record_id){
			$params['records'] = array($record_id);
		}

        $q          = \REDCap::getData($params);
        $records    = json_decode($q, true);

        if(!empty($records)){
            $meds 	    = current($records);
            $this->module->emDebug("drugs", $meds);
            
            //TODO GET THIS FROM RC PROJECT
            // Eplerenone
            // Spirnolactone
            $tplname        = $meds["template_name"];
            $template_drugs = array(
                 "ACEI"     => array($meds["acei_class"]." 10mg", $meds["acei_class"]." 20mg", $meds["acei_class"]." 40mg")
                ,"Diuretic" => array($meds["diuretic_class"]." 12.5mg", $meds["diuretic_class"]." 25mg")
                ,"Spirno"   => array($meds["spirno_class"]." 12.5mg", $meds["spirno_class"]." 25mg", $meds["epler_class"]." 1 Daily", $meds["epler_class"]." 1 Bidaily")
                ,"ARB"      => array($meds["arb_class"]." 50mg",$meds["arb_class"]." 100mg")
                ,"CCB"      => array($meds["ccb_class"]." 2.5mg", $meds["ccb_class"]." 5mg", $meds["ccb_class"]." 10mg")
                ,"BB"       => array($meds["bb_class"]." 2.5mg",  $meds["bb_class"]." 5mg", $meds["bb_class"]." 7.5mg", $meds["bb_class"]." 10mg")
            );
        }else {
            $tplname        = null;
            $template_drugs = array();
        }
        

        return array("template_name" => $tplname, "template_drugs" => $template_drugs);
    }

    public function treeLogic($provider_id){
        $provider_tree  = $this->getTemplateDrugs($provider_id);
        $logicTree      = array();

        $template_drugs = $provider_tree["template_drugs"];
        if(!empty($template_drugs)){
            $logicTree[]    = array(
                "step_id"  => 0
                ,"drugs"    => array($template_drugs["ACEI"][0], $template_drugs["Diuretic"][0])
                ,"bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 1)
                ,"note"     => ""
                ,"side_effects"     => array(
                    "cough"             => 13,
                    "elevated_cr"       => 28,
                    "hyperkalemia"      => 28, 
                    "slow_hr"           => null, 
                    "angioedema"        => 41,
                    "breast_discomfort" => null,
                    "rash_other"        => "Stop, call doctor",
                    "asthma"            => null
                )
            );
            $logicTree[] = array(
                "step_id" => 1,
                "drugs" => array($template_drugs["ACEI"][1],$template_drugs["Diuretic"][0] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 2),
                "note" => "",
                "side_effects" => array(
                    "cough" => 14,
                    "elevated_cr" => 25,
                    "hyperkalemia" => 25, 
                    "slow_hr" => null, 
                    "angioedema" => 41,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            ); 
            $logicTree[] = array(
                "step_id" => 2,
                "drugs" => array($template_drugs["ACEI"][1],$template_drugs["Diuretic"][1] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 3),
                "note" => "",
                "side_effects" => array(
                    "cough" => 15,
                    "elevated_cr" => 25,
                    "hyperkalemia" => 25, 
                    "slow_hr" => null, 
                    "angioedema" => 41,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 3,
                "drugs" => array($template_drugs["ACEI"][2],$template_drugs["Diuretic"][1] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 4),
                "note" => "",
                "side_effects" => array(
                    "cough" => 15,
                    "elevated_cr" => 64,
                    "hyperkalemia" => 25, 
                    "slow_hr" => null, 
                    "angioedema" => 41,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 4,
                "drugs" => array($template_drugs["ACEI"][2],$template_drugs["Diuretic"][1], $template_drugs["CCB"][0] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 5),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => 16,
                    "elevated_cr" => null,
                    "hyperkalemia" => null, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => array("Uncontrolled, K < 4.5" => 69, "Uncontrolled, K > 4.5" => 65),
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 5,
                "drugs" => array($template_drugs["ACEI"][2],$template_drugs["Diuretic"][1], $template_drugs["CCB"][1] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 6),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => 17,
                    "elevated_cr" => null,
                    "hyperkalemia" => null, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => array("Uncontrolled, K < 4.5" => 69, "Uncontrolled, K > 4.5" => 65),
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 6,
                "drugs" => array($template_drugs["ACEI"][2],$template_drugs["Diuretic"][1], $template_drugs["CCB"][2] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled, K < 4.5" => 7, "Uncontrolled, K > 4.5" => 9),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => 18,
                    "elevated_cr" => null,
                    "hyperkalemia" => null, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => array("Uncontrolled, K < 4.5" => 69, "Uncontrolled, K > 4.5" => 65),
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 7,
                "drugs" => array($template_drugs["ACEI"][2],$template_drugs["Diuretic"][1], $template_drugs["CCB"][2], $template_drugs["Spirno"][0] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 8),
                "note" => "",
                "side_effects" => array(
                    "cough" => 19,
                    "elevated_cr" => "Stop, Cr elevatedor hyperkalemia present",
                    "hyperkalemia" => "Stop, Cr elevated or hyperkalemia present", 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => 56,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 8,
                "drugs" => array($template_drugs["ACEI"][2],$template_drugs["Diuretic"][1], $template_drugs["CCB"][2], $template_drugs["Spirno"][1] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => "End of protocol"),
                "note" => "",
                "side_effects" => array(
                    "cough" => 20,
                    "elevated_cr" => 7,
                    "hyperkalemia" => 7, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => 56,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 9,
                "drugs" => array($template_drugs["ACEI"][2],$template_drugs["Diuretic"][1], $template_drugs["CCB"][2], $template_drugs["BB"][0] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 10),
                "note" => "Before adding/increasing " . $template_drugs["BB"][0] . ", confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => 21,
                    "elevated_cr" => null,
                    "hyperkalemia" => "Stop, hyperkalemia present", 
                    "slow_hr" => "Stop, call doctor", 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => "Stop, call doctor"
                )
            );
            $logicTree[] = array(
                "step_id" => 10,
                "drugs" => array($template_drugs["ACEI"][2],$template_drugs["Diuretic"][1], $template_drugs["CCB"][2], $template_drugs["BB"][1] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 11),
                "note" => "Before adding/increasing " . $template_drugs["BB"][1] . ", confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => 22,
                    "elevated_cr" => null,
                    "hyperkalemia" => 9, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => "Stop, call doctor"
                )
            );
            $logicTree[] = array(
                "step_id" => 11,
                "drugs" => array($template_drugs["ACEI"][2],$template_drugs["Diuretic"][1], $template_drugs["CCB"][2], $template_drugs["BB"][2] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 12),
                "note" => "Before adding/increasing " . $template_drugs["BB"][2] . ", confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => 23,
                    "elevated_cr" => null,
                    "hyperkalemia" => 10, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => "Stop, call doctor"
                )
            );
            $logicTree[] = array(
                "step_id" => 12,
                "drugs" => array($template_drugs["ACEI"][2], $template_drugs["Diuretic"][1], $template_drugs["CCB"][2], $template_drugs["BB"][3] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => "End of protocol"),
                "note" => "Before adding/increasing " . $template_drugs["BB"][3] . ", confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => 24,
                    "elevated_cr" => null,
                    "hyperkalemia" => 11, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => "Stop, call doctor"
                )
            );
            $logicTree[] =array(
                "step_id" => 13,
                "drugs" => array($template_drugs["ARB"][0],$template_drugs["Diuretic"][0] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 14),
                "note" => "",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => 28,
                    "hyperkalemia" => 28, 
                    "slow_hr" => null, 
                    "angioedema" => 41,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 14,
                "drugs" => array($template_drugs["ARB"][1],$template_drugs["Diuretic"][0] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 15),
                "note" => "",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => 31,
                    "hyperkalemia" => 31, 
                    "slow_hr" => null, 
                    "angioedema" => 41,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 15,
                "drugs" => array($template_drugs["ARB"][1],$template_drugs["Diuretic"][1] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 16),
                "note" => "",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => 31,
                    "hyperkalemia" => 31, 
                    "slow_hr" => null, 
                    "angioedema" => 41,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 16,
                "drugs" => array($template_drugs["ARB"][1],$template_drugs["Diuretic"][1], $template_drugs["CCB"][0]  ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 17),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => null,
                    "hyperkalemia" => null, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => array("Uncontrolled, K < 4.5" => 71, "Uncontrolled, K > 4.5" => 73),
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 17,
                "drugs" => array($template_drugs["ARB"][1],$template_drugs["Diuretic"][1], $template_drugs["CCB"][1]  ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 18),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => null,
                    "hyperkalemia" => null, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => array("Uncontrolled, K < 4.5" => 71, "Uncontrolled, K > 4.5" => 73),
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 18,
                "drugs" => array($template_drugs["ARB"][1],$template_drugs["Diuretic"][1], $template_drugs["CCB"][2] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled, K < 4.5" => 19, "Uncontrolled, K > 4.5" => 21),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => null,
                    "hyperkalemia" => null, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => array("Uncontrolled, K < 4.5" => 71, "Uncontrolled, K > 4.5" => 73),
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 19,
                "drugs" => array($template_drugs["ARB"][1],$template_drugs["Diuretic"][1], $template_drugs["CCB"][2], $template_drugs["Spirno"][0] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 20),
                "note" => "",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => "Stop, Cr elevatedor hyperkalemia present",
                    "hyperkalemia" => "Stop, Cr elevated or hyperkalemia present", 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => 58,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 20,
                "drugs" => array($template_drugs["ARB"][1],$template_drugs["Diuretic"][1], $template_drugs["CCB"][2], $template_drugs["Spirno"][1] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => "End of protocol"),
                "note" => "",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => 19,
                    "hyperkalemia" => 19, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => 58,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 21,
                "drugs" => array($template_drugs["ARB"][1],$template_drugs["Diuretic"][1], $template_drugs["CCB"][2], $template_drugs["BB"][0] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 22),
                "note" => "Before adding/increasing " . $template_drugs["BB"][0] . ", confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => null,
                    "hyperkalemia" => "Stop, hyperkalemia present", 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => "Stop, call doctor"
                )
            );
            $logicTree[] = array(
                "step_id" => 22,
                "drugs" => array($template_drugs["ARB"][1],$template_drugs["Diuretic"][1], $template_drugs["CCB"][2], $template_drugs["BB"][1] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 23),
                "note" => "Before adding/increasing " . $template_drugs["BB"][1] . ", confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => null,
                    "hyperkalemia" => 21, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => "Stop, call doctor"
                )
            );
            $logicTree[] = array(
                "step_id" => 23,
                "drugs" => array($template_drugs["ARB"][1],$template_drugs["Diuretic"][1], $template_drugs["CCB"][2], $template_drugs["BB"][2] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 24),
                "note" => "Before adding/increasing " . $template_drugs["BB"][2] . ", confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => null,
                    "hyperkalemia" => 22, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => "Stop, call doctor"
                )
            );
            $logicTree[] = array(
                "step_id" => 24,
                "drugs" => array($template_drugs["ARB"][1],$template_drugs["Diuretic"][1], $template_drugs["CCB"][2], $template_drugs["BB"][3] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => "End of protocol"),
                "note" => "Before adding/increasing " . $template_drugs["BB"][3] . ", confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => null,
                    "hyperkalemia" => 23, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => "Stop, call doctor"
                )
            );
            $logicTree[] = array(
                "step_id" => 25,
                "drugs" => array($template_drugs["ACEI"][0], $template_drugs["Diuretic"][0], $template_drugs["CCB"][0] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 26),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => 28,
                    "hyperkalemia" => 28, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 26,
                "drugs" => array($template_drugs["ACEI"][0], $template_drugs["Diuretic"][0], $template_drugs["CCB"][1] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 27),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => 29,
                    "hyperkalemia" => null, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 27,
                "drugs" => array($template_drugs["ACEI"][0], $template_drugs["Diuretic"][0], $template_drugs["CCB"][2] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 28),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => null,
                    "hyperkalemia" => null, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 28,
                "drugs" => array($template_drugs["Diuretic"][0], $template_drugs["CCB"][0] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 29),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => 38,
                    "hyperkalemia" => "hyperkalemia still present",
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 29,
                "drugs" => array($template_drugs["Diuretic"][0], $template_drugs["CCB"][1] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 30),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => 28,
                    "hyperkalemia" => 28, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 30,
                "drugs" => array($template_drugs["Diuretic"][0], $template_drugs["CCB"][2] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => "End of protocol"),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => 28,
                    "hyperkalemia" => 28, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 31,
                "drugs" => array($template_drugs["ARB"][0],$template_drugs["Diuretic"][0], $template_drugs["CCB"][0]  ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 32),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => 28,
                    "hyperkalemia" => 28, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 32,
                "drugs" => array($template_drugs["ARB"][0],$template_drugs["Diuretic"][0], $template_drugs["CCB"][1]  ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 33),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => 29,
                    "hyperkalemia" => 29, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 33,
                "drugs" => array($template_drugs["ARB"][0],$template_drugs["Diuretic"][0], $template_drugs["CCB"][2]  ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => "End of protocol"),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => 30,
                    "hyperkalemia" => 30, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 34,
                "drugs" => array($template_drugs["Diuretic"][1], $template_drugs["CCB"][0] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 35),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => 38,
                    "hyperkalemia" => 38, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 35,
                "drugs" => array($template_drugs["Diuretic"][2], $template_drugs["CCB"][0] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 36),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => 38,
                    "hyperkalemia" => 38, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 36,
                "drugs" => array($template_drugs["Diuretic"][2], $template_drugs["CCB"][1] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 37),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => 39,
                    "hyperkalemia" => "hyperkalemia still present", 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 37,
                "drugs" => array($template_drugs["Diuretic"][2], $template_drugs["CCB"][2] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled, K < 4.5" => 50, "Uncontrolled, K > 4.5" => 52),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => 40,
                    "hyperkalemia" => "hyperkalemia still present", 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 38,
                "drugs" => array($template_drugs["CCB"][0] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 39),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => "Cr still elevated",
                    "hyperkalemia" => "hyperkalemia still present", 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 39,
                "drugs" => array($template_drugs["CCB"][1] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 40),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => "Cr still elevated",
                    "hyperkalemia" => "hyperkalemia still present", 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 40,
                "drugs" => array($template_drugs["CCB"][2] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled, K < 4.5" => 44, "Uncontrolled, K > 4.5" => 46),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => "Cr still elevated",
                    "hyperkalemia" => "hyperkalemia still present", 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 41,
                "drugs" => array($template_drugs["Diuretic"][0] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 42),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => 38,
                    "hyperkalemia" => 34, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 42,
                "drugs" => array($template_drugs["Diuretic"][1] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 43),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => 38,
                    "hyperkalemia" => 34, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 43,
                "drugs" => array($template_drugs["Diuretic"][2] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 35),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => 38,
                    "hyperkalemia" => 34, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 44,
                "drugs" => array($template_drugs["CCB"][2],  $template_drugs["Spirno"][0] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 45),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => "Stop, Cr elevatedor hyperkalemia present",
                    "hyperkalemia" => "Stop, Cr elevated or hyperkalemia present", 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => "change to Eplerenone",
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 45,
                "drugs" => array($template_drugs["CCB"][2], $template_drugs["Spirno"][1] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => "End of protocol"),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => 44,
                    "hyperkalemia" => 44, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => "change to Eplerenone",
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 46,
                "drugs" => array($template_drugs["CCB"][2],  $template_drugs["BB"][0] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 47),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => "Cr still elevated",
                    "hyperkalemia" => "Stop, hyperkalemia present", 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => "Stop, call doctor"
                )
            );
            $logicTree[] = array(
                "step_id" => 47,
                "drugs" => array($template_drugs["CCB"][2],  $template_drugs["BB"][1]  ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 48),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => "Cr still elevated",
                    "hyperkalemia" => 46, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => "Stop, call doctor"
                )
            );
            $logicTree[] = array(
                "step_id" => 48,
                "drugs" => array($template_drugs["CCB"][2],  $template_drugs["BB"][2] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 49),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => "Cr still elevated",
                    "hyperkalemia" => 47, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => "Stop, call doctor"
                )
            );
            $logicTree[] = array(
                "step_id" => 49,
                "drugs" => array($template_drugs["CCB"][2],  $template_drugs["BB"][3] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => "End of protocol"),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => "Cr still elevated",
                    "hyperkalemia" => 48, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => "Stop, call doctor"
                )
            );
            $logicTree[] = array(
                "step_id" => 50,
                "drugs" => array($template_drugs["Diuretic"][2], $template_drugs["CCB"][2] , $template_drugs["Spirno"][0]),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 51),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => "Stop, Cr elevatedor hyperkalemia present",
                    "hyperkalemia" => "Stop, Cr elevated or hyperkalemia present", 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => 62,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 51,
                "drugs" => array($template_drugs["Diuretic"][2], $template_drugs["CCB"][2], $template_drugs["Spirno"][1] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => "End of protocol"),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => 50,
                    "hyperkalemia" => 50, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => 62,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 52,
                "drugs" => array($template_drugs["Diuretic"][2], $template_drugs["CCB"][2], $template_drugs["BB"][0] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 53),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => 40,
                    "hyperkalemia" => "Stop, hyperkalemia present", 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => "Stop, call doctor"
                )
            );
            $logicTree[] = array(
                "step_id" => 53,
                "drugs" => array($template_drugs["Diuretic"][2], $template_drugs["CCB"][2], $template_drugs["BB"][1] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 54),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => 40,
                    "hyperkalemia" => 52, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => "Stop, call doctor"
                )
            );
            $logicTree[] = array(
                "step_id" => 54,
                "drugs" => array($template_drugs["Diuretic"][2], $template_drugs["CCB"][2], $template_drugs["BB"][2] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 55),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => 40,
                    "hyperkalemia" => 53, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => "Stop, call doctor"
                )
            );
            $logicTree[] = array(
                "step_id" => 55,
                "drugs" => array($template_drugs["Diuretic"][2], $template_drugs["CCB"][2], $template_drugs["BB"][3] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => "End of protocol"),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => 40,
                    "hyperkalemia" => 54, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => "Stop, call doctor"
                )
            );
            $logicTree[] = array(
                "step_id" => 56,
                "drugs" => array($template_drugs["ACEI"][2],$template_drugs["Diuretic"][1], $template_drugs["CCB"][2], $template_drugs["Spirno"][2] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 57),
                "note" => "",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => null,
                    "hyperkalemia" => null,
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => "Stop, Call Doctor",
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 57,
                "drugs" => array($template_drugs["ACEI"][2],$template_drugs["Diuretic"][1], $template_drugs["CCB"][2], $template_drugs["Spirno"][3] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => "End of protocol"),
                "note" => "",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => null,
                    "hyperkalemia" => null,
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => "Stop, Call Doctor",
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 58,
                "drugs" => array($template_drugs["ARB"][1],$template_drugs["Diuretic"][1], $template_drugs["CCB"][2], $template_drugs["Spirno"][2] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 58),
                "note" => "",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => null,
                    "hyperkalemia" => null,
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => "Stop, Call Doctor",
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 59,
                "drugs" => array($template_drugs["ARB"][1],$template_drugs["Diuretic"][1], $template_drugs["CCB"][2], $template_drugs["Spirno"][3] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => "End of protocol"),
                "note" => "",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => null,
                    "hyperkalemia" => null,
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => "Stop, Call Doctor",
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 60,
                "drugs" => array($template_drugs["CCB"][2],  $template_drugs["Spirno"][2] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 61),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => null,
                    "hyperkalemia" => null,
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => "Stop, Call Doctor",
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 61,
                "drugs" => array($template_drugs["CCB"][2],  $template_drugs["Spirno"][3] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => "End of protocol"),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => null,
                    "hyperkalemia" => null,
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => "Stop, Call Doctor",
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 62,
                "drugs" => array($template_drugs["Diuretic"][2], $template_drugs["CCB"][2] , $template_drugs["Spirno"][2]),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 63),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => null,
                    "hyperkalemia" => null, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => "Stop, Call Doctor",
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 63,
                "drugs" => array($template_drugs["Diuretic"][2], $template_drugs["CCB"][2] , $template_drugs["Spirno"][3]),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => "End of protocol"),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => null,
                    "hyperkalemia" => null, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => "Stop, Call Doctor",
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 64,
                "drugs" => array($template_drugs["ACEI"][1], $template_drugs["Diuretic"][1], $template_drugs["CCB"][0] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 26),
                "note" => "Before adding/increasing Diltiazem or Verapamil, confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => 25,
                    "hyperkalemia" => 25, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 65,
                "drugs" => array($template_drugs["ACEI"][2],$template_drugs["Diuretic"][1], $template_drugs["BB"][0] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 66),
                "note" => "Before adding/increasing " . $template_drugs["BB"][0] . ", confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => null,
                    "hyperkalemia" => null, 
                    "slow_hr" => "Stop, Call Doctor", 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => "Stop, call doctor"
                )
            );
            $logicTree[] = array(
                "step_id" => 66,
                "drugs" => array($template_drugs["ACEI"][2],$template_drugs["Diuretic"][1], $template_drugs["BB"][1] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 67),
                "note" => "Before adding/increasing " . $template_drugs["BB"][1] . ", confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => null,
                    "hyperkalemia" => null, 
                    "slow_hr" => 65, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => "Stop, call doctor"
                )
            );
            $logicTree[] = array(
                "step_id" => 67,
                "drugs" => array($template_drugs["ACEI"][2],$template_drugs["Diuretic"][1], $template_drugs["BB"][2] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 68),
                "note" => "Before adding/increasing " . $template_drugs["BB"][2] . ", confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => null,
                    "hyperkalemia" => null, 
                    "slow_hr" => 66, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => "Stop, call doctor"
                )
            );
            $logicTree[] = array(
                "step_id" => 68,
                "drugs" => array($template_drugs["ACEI"][2], $template_drugs["Diuretic"][1], $template_drugs["BB"][3] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => "End of protocol"),
                "note" => "Before adding/increasing " . $template_drugs["BB"][3] . ", confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => null,
                    "hyperkalemia" => null, 
                    "slow_hr" => 67, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => "Stop, call doctor"
                )
            );
            $logicTree[] = array(
                "step_id" => 69,
                "drugs" => array($template_drugs["ACEI"][2],$template_drugs["Diuretic"][1], $template_drugs["Spirno"][0] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 70),
                "note" => "",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => null,
                    "hyperkalemia" => null, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => 56,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 70,
                "drugs" => array($template_drugs["ACEI"][2],$template_drugs["Diuretic"][1], $template_drugs["Spirno"][1] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => "End of protocol"),
                "note" => "",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => null,
                    "hyperkalemia" => null, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => 56,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 71,
                "drugs" => array($template_drugs["ARB"][1],$template_drugs["Diuretic"][1], $template_drugs["Spirno"][0] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 72),
                "note" => "",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => null,
                    "hyperkalemia" => null, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => 58,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 72,
                "drugs" => array($template_drugs["ARB"][1],$template_drugs["Diuretic"][1], $template_drugs["Spirno"][1] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => "End of protocol"),
                "note" => "",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => null,
                    "hyperkalemia" => null, 
                    "slow_hr" => null, 
                    "angioedema" => null,
                    "breast_discomfort" => 58,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => null
                )
            );
            $logicTree[] = array(
                "step_id" => 73,
                "drugs" => array($template_drugs["ARB"][1],$template_drugs["Diuretic"][1],  $template_drugs["BB"][0] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 74),
                "note" => "Before adding/increasing " . $template_drugs["BB"][0] . ", confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => null,
                    "hyperkalemia" => null, 
                    "slow_hr" => "Stop, Call Doctor", 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => "Stop, call doctor"
                )
            );
            $logicTree[] = array(
                "step_id" => 74,
                "drugs" => array($template_drugs["ARB"][1],$template_drugs["Diuretic"][1], $template_drugs["BB"][1] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 75),
                "note" => "Before adding/increasing " . $template_drugs["BB"][1] . ", confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => null,
                    "hyperkalemia" => null, 
                    "slow_hr" => 73, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => "Stop, call doctor"
                )
            );
            $logicTree[] = array(
                "step_id" => 75,
                "drugs" => array($template_drugs["ARB"][1],$template_drugs["Diuretic"][1], $template_drugs["BB"][2] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => 76),
                "note" => "Before adding/increasing " . $template_drugs["BB"][2] . ", confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => null,
                    "hyperkalemia" => null, 
                    "slow_hr" => 74, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => "Stop, call doctor"
                )
            );
            $logicTree[] = array(
                "step_id" => 76,
                "drugs" => array($template_drugs["ARB"][1],$template_drugs["Diuretic"][1], $template_drugs["BB"][3] ),
                "bp_status" => array("Controlled"=> "Continue current step", "Uncontrolled" => "End of protocol"),
                "note" => "Before adding/increasing " . $template_drugs["BB"][3] . ", confirm HR > 55bpm",
                "side_effects" => array(
                    "cough" => null,
                    "elevated_cr" => null,
                    "hyperkalemia" => null, 
                    "slow_hr" => 75, 
                    "angioedema" => null,
                    "breast_discomfort" => null,
                    "rash_other" => "Stop, call doctor",
                    "asthma" => "Stop, call doctor"
                )
            );
        }

        return array( "template_name" => $provider_tree["template_name"] , "logicTree" => $logicTree);
    }
}


?>