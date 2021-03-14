function treeLogic(rawJson, patient, urls, is_sponsored){
    for(var i in urls){
        this[i] = urls[i];
    }

    //TODO FUCK ME, MUST CHOSE WHICH ?
    this.patient        = patient;
    this.is_sponsored   = is_sponsored;
    console.log("use the patients tree to pick the logic tree shit", rawJson[this.patient["current_treatment_plan_id"]]);

    this.raw            = rawJson[this.patient["current_treatment_plan_id"]]["logicTree"];
    this.tplname        = rawJson[this.patient["current_treatment_plan_id"]]["label"];
 
    this.steps          = {};
    this.step_order     = [0]; //gets set on init? only update when "makeCurrent()"
    // this.display_order  = [0]; //gets set on init. update when "makeCurrent" with up to 3 previews. 
    this.num_previews   = 3;

    this.rec_step       = patient["patient_rec_tree_step"];
    if(patient["tree_log"].length){
        for(var i in patient["tree_log"]){
            // if patient has been in the tree for a while , they will have previous step history
            console.log("patient has a history",patient["tree_log"][i]);
            this.step_order.push(patient["tree_log"][i]["ptree_log_current_step"]);
        }
    }

    this.current_step   = patient["patient_treatment_status"];
    this.total_steps    = 1;

    this.containerid    = "viewer";

    // one time
    this.prepSteps();

    
}
treeLogic.prototype.prepSteps = function(){
    for(var i in this.raw){
        var step_id             = this.raw[i].step_id;
        this.steps[step_id]     = new treeLogicStep(this, this.raw[i]);
    }
    return;
}
treeLogic.prototype.startAttachTree = function(){

    if(!this.raw){
        return;
    }

    if(this.tplname != ""){
        $(".template_name").text(this.patient["patient_fname"]+ " " + this.patient["patient_mname"] + " " + this.patient["patient_lname"]);

        var backlink = $("<a>").addClass("backlink").attr("href",this.patient_backlink).html("&#171; Return to "+this.patient["patient_fname"]+"'s Dashboard");
        $(".template_name").append(backlink);
    }

    //First display all the previous steps up to the current step.
    $("#"+this.containerid).empty();
    var first_step  = true;
    var prev_step   = null;
    for(var i in this.step_order){
        var step    = this.steps[ this.step_order[i] ];
        if(first_step){
            // first step, no previous 
            step.appendToDom(["noconnect"],null,null);
            first_step = false;
        }else{
            var previous_action_text    = "Uncontrolled";
            var uncontrolled            = prev_step.getUncontrolled();
            for(var i in uncontrolled){
                var uc = uncontrolled[i];
                if(step.step_id == uc.action_val){
                    previous_action_text = uc.action_text;
                }
            }
            step.appendToDom([], prev_step , previous_action_text);
        }

        //if not the last in the steporder array then make previous
        if(step.step_id != this.step_order[this.step_order.length - 1]){
            step.makePrevious();
        }else{
            step.makeCurrent();
        }
        prev_step = step;
    }
}
treeLogic.prototype.showPreview = function(calling_step, action_text, action_val, container_to_attach=null){
    if(action_val == 999){
        return;
    }

    if($.isNumeric(action_val)){
        // show next stap
        var step_class = ["preview"];
        if(action_val == this.rec_step){
            // console.log("this is recoommended step , highlight", action_val);
            step_class.push("rec_highlight");
        }

        this.steps[action_val].appendToDom(step_class, calling_step, action_text, container_to_attach);
    }else{
        // is TEXT so need to parse and display
        var end = false;
        if(action_val.indexOf("End") > -1  || action_val.indexOf("Stop") > -1){
            end = true;
        } 
        this.showMessage(calling_step, action_text, action_val, end, container_to_attach);
    }

    return;
}
treeLogic.prototype.removePreviews = function(){
    var _this = this;
    
    // TODO adjust the display order array
    // var arr = [5, 15, 110, 210, 550];
    // var index = arr.indexOf(210);

    // if (index > -1) {
    //    arr.splice(index, 1);
    // }

    $(".tree_step.preview").each(function(){
        if($(this).data("step_id")){
            var hmm = _this.steps[$(this).data("step_id")].jq.clone(true, true); //oh
            _this.steps[$(this).data("step_id")].jq.remove();
            _this.steps[$(this).data("step_id")].jq = hmm;
        }else{
            //end of protocol step, no actual step just display
            $(this).remove();
        }
    });

    //gotta remove branches too
    $(".branch").remove();
}
treeLogic.prototype.showMessage = function(calling_step, condition, msg, end=false, container_to_attach=null){
    var _this = this;
    var tree_step   = $(tree_step_msg);
    
    var change_condition = $("<b>").addClass("change_condition").html($("<span>").text(condition));
    tree_step.append(change_condition);

    tree_step.find(".msg").text(msg);
    tree_step.find(".btn.reject").click(function(){
        //if its a msg it will be continue previous step
        tree_step.fadeOut("fast", function(){
            $(this).remove();
            calling_step.jq.find("option:selected").prop("selected",false);
        });
    });
    tree_step.find(".btn.accept").click(function(){
        if(end){
            //END THE TREE HERE CHANGE IT TO RED?
            _this.steps[_this.current_step].makePrevious();
            tree_step.addClass("end").removeClass("preview");
            _this.removePreviews();
        }else{
            //if its a msg it will be continue previous step
            tree_step.fadeOut("fast", function(){
                $(this).remove();
            }); 
        }
    }); 
    if(!container_to_attach){
        container_to_attach = $("#"+this.containerid);
    }
    container_to_attach.append(tree_step);
}
