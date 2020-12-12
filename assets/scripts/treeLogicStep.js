function treeLogicStep(parent, rawJson){
    this.parent         = parent;
    this.raw            = rawJson;
    this.jq             = "";
    this.previous_step  = null;
    this.container      = $("#"+this.parent.containerid);
    this.step_status    = "Current";
    this.step_id        = this.raw.step_id;
    this.eval           = "14 days";
    this.drugs          = this.raw.drugs;
    this.bp_options     = this.raw.bp_status;
    this.se_options     = this.raw.side_effects;

    this.bp_outcome     = null;
    this.se_outcome     = null;

    this.plain_drugs = [];
    this.med_dose    = {};
    for(var i in this.drugs){
        var drug = this.drugs[i];
        if(drug){
            var temp = drug.split(" ");
            this.plain_drugs.push(temp[0]);
            this.med_dose[temp[0]] = temp.pop().split("mg")[0];
        }
    }

    //build the initial jquery object from html in template.js
    //will be modified depending if its a preview or previous step
    this.buildHTML();
}
treeLogicStep.prototype.buildHTML = function(){
    var tree_step   = $(tree_step_template);

    tree_step.prop("id", "step_" + this.step_id);
    tree_step.attr("data-step_id", this.step_id);
    tree_step.find("h4 i").text(this.step_status);
    tree_step.find(".eval").text(this.eval);
    
    // ADD DRUGS
    for(var i in this.drugs){
        var drug        = this.drugs[i];
        var b_drug      = $("<b>").addClass("drug").text(drug);
        var li_drug     = $("<li>");
        if(drug){
            for(var i in this.plain_drugs){
                if(drug.indexOf(this.plain_drugs[i]) > -1){
                    li_drug.addClass(this.plain_drugs[i]);
                    break;
                }
            }
        }
        li_drug.append(b_drug);
        tree_step.find(".step_drugs").append(li_drug);
    }

    // ADD BP STATUS ACTIONS
    for(var i in this.bp_options){
        var action = this.bp_options[i];
        if(action){
            var option = $("<option>").val(action).text(i);
            tree_step.find(".bp_status").append(option);
        }
    }

    // ADD SIDE EFFECTS ACTIONS
    for(var i in this.se_options){
        var action = this.se_options[i];
        if(action){
            var option = $("<option>").val(action).text(i);
            tree_step.find(".side_effects").append(option);
        }
    }

    this.jq = tree_step;

    //bind click events on branches
    this.bindStepEvents();
}
treeLogicStep.prototype.bindStepEvents = function(){
    // bind select options to show next step
    var _this = this;

    this.jq.find(".bp_status").change(function(){
        _this.parent.removePreviews();

        var selected_val = $(this).children("option:selected").val();
        var selected_txt = $(this).children("option:selected").text();
        
        _this.parent.showPreview(_this, selected_txt, selected_val);
        var buffer_previews     = [];
        var previews            = _this.recursivePreviews(_this.parent.steps[selected_val], buffer_previews, 2);
        console.log("extra previews", previews);
    });
    this.jq.find(".side_effects").change(function(){
        _this.parent.removePreviews();

        var selected_val = $(this).children("option:selected").val();
        var selected_txt = $(this).children("option:selected").text();

        _this.parent.showPreview(_this, selected_txt, selected_val);
        var buffer_previews     = [];
        var previews            = _this.recursivePreviews(_this.parent.steps[selected_val], buffer_previews, 2);
        console.log("extra previews", previews);
    });

    this.jq.find(".btn.reject").click(function(){
        var hmm = _this.jq.clone(true, true); //oh
        _this.jq.fadeOut("fast", function(){
            $(this).remove();
            _this.jq = hmm;

            if(_this.previous_step){
                _this.previous_step.jq.find("option:selected").prop("selected",false);
                _this.previous_step = null;
            }
        });
    });

    this.jq.find(".btn.accept").click(function(){
        _this.showRecModal();
        return false;

        
    });

    this.jq.find(".btn.showhide").click(function(){
        if(_this.jq.hasClass("hide")){
            _this.jq.removeClass("hide");
            $(this).find("span").text("-");
        }else{
            _this.jq.addClass("hide");
            $(this).find("span").text("+")
        }
    });
}
treeLogicStep.prototype.appendToDom = function(stepClass=[], calling_step=null, change_condition=null, container=null){
    if(calling_step){
        this.previous_step = calling_step;
    }

    if(change_condition){
        this.jq.find(".change_condition").remove();

        var change = $("<b>").addClass("change_condition").html($("<span>").text(change_condition));
        this.jq.append(change);
    }

    if(stepClass){
        if(stepClass.indexOf("preview") > -1) {
            this.jq.find("h4 i").text("Preview");
            this.checkDelta();
        }
        if(stepClass.indexOf("rec_highlight") > -1) {
            this.jq.find("h4 i").text("Recommended");
        }

        for(var i in stepClass){
            var cls = stepClass[i];
            this.jq.addClass(cls)
        }
    }
    if(container){
        this.container = container;
    }
    this.container.append(this.jq);
}
treeLogicStep.prototype.makePrevious = function(){
    this.jq.addClass("previous").addClass("hide");
    this.jq.find("h4 i").text("Previous");
    var bp_val  = "N/A";
    var se_val  = "N/A";

    if(this.jq.find(".bp_status option:selected").length && this.jq.find(".bp_status option:selected").val() != 999){
        var bp_val = this.jq.find(".bp_status option:selected").text();
    }
    if(this.jq.find(".side_effects option:selected").length && this.jq.find(".side_effects option:selected").val() != 999){
        var se_val = this.jq.find(".side_effects option:selected").text();
    }

    var bp_par = this.jq.find(".bp_status").parent();
    var se_par = this.jq.find(".side_effects").parent();

    bp_par.empty().text(bp_val);
    se_par.empty().text(se_val);
}
treeLogicStep.prototype.makeCurrent = function(){
    // Parent Tracks How many Steps have been accepted previous + current
    if(!this.parent.step_order.includes(this.step_id)){
        this.parent.step_order.push(this.step_id);
    }
    
    //RESET the "previous step" in case skipp over
    this.previous_step         = this.parent.steps[this.parent.current_step];

    //RESET current step tracking in parent class
    this.parent.current_step   = this.step_id;
    this.parent.total_steps    = this.parent.step_order.length;

    this.jq.removeClass("preview").removeClass("rec_highlight").removeClass("hide");
    this.jq.find("h4 i").text("Current");

    //READD current TO THE MAIN BRANCH
    // TODO, MAKE SURE ALL THE PROPER RELATIONSHIPS ARE KEPT HERE
    this.appendToDom([], this.previous_step, "", this.previous_step.container );


    //REMOVE ALL PREVIEWS BEFORE READDING NEW
    this.parent.removePreviews();

    var nowtime         = new Date().toLocaleString();
    this.jq.find(".eval").text(nowtime);

    var buffer_previews = [];
    var previews        = this.recursivePreviews(this, buffer_previews, this.parent.num_previews);

    // now loop and show. 
    for(var i in previews){
        var preview = previews[i];

        // this should be safe cause the recursivePreviews function should always stop on ab
        if(preview.uc.length > 1){
            // fucking hell, this needs to be recursive, but i cant wrap my tiny brain around it.  
            //need special HTML
            var branch_box = $(branch_container);
            $("#"+this.parent.containerid).append(branch_box);
            
            for(var i in preview.uc){
                // each branch will be housed in a column withing branch_box
                var branch_col              = $("<div>").addClass("branch_col").prop("id","col_"+i);
                branch_box.append(branch_col);

                // print the top of the branched column
                var branch_uc               = preview.uc[i];
                this.parent.showPreview(this.parent.steps[preview["prev_step_id"]], branch_uc["action_text"], branch_uc["action_val"], branch_col);
                
                // now recurse and get the rest of the previews
                var branch_buffer_previews  = [];
                var branch_previews         = this.recursivePreviews(this.parent.steps[branch_uc["action_val"]], branch_buffer_previews, this.parent.num_previews - previews.length );
                
                for(var b in branch_previews){
                    var branch_preview = branch_previews[b];
                    this.parent.showPreview(this.parent.steps[branch_preview["prev_step_id"]], branch_preview["uc"]["action_text"], branch_preview["uc"]["action_val"], branch_col);
                }
            };
        }else{
             // find the uncontrolled step in the steps and keep displaying all the way down
            this.parent.showPreview(this.parent.steps[preview["prev_step_id"]], preview["uc"]["action_text"], preview["uc"]["action_val"], this.parent.steps[preview["prev_step_id"]].container);
        }
    }

    // console.log("step_order", this.parent.step_order);
    // console.log("display_order", this.parent.display_order);
}
treeLogicStep.prototype.recursivePreviews = function(current_step, buffer_previews, desired_previews){
    //Will runthis recursive funcrtion this.parent.num_previews times
    desired_previews--;

    var end_of_protocol         = null;
    if(!current_step){
        end_of_protocol         = true;
        var uncontrolled_action = [];
    }else{
        var uncontrolled_action = current_step.getUncontrolled();
    }

    // steps already exist in the parent class this.steps
    // just need the "preview" jq and save them in buffer_previews

    //TODO fix this logic .... fix how? 12/12/20
    if(uncontrolled_action.length == 1){
        var next_action = uncontrolled_action.pop();
        buffer_previews.push( {"prev_step_id" : current_step.step_id, "uc" : next_action, "branching" : false } );

        if(!$.isNumeric(next_action.action_val) && (next_action.action_val.indexOf("End") > -1  || next_action.action_val.indexOf("Stop") > -1) ){
            end_of_protocol = true;
        }
    }else{
        buffer_previews.push({"prev_step_id" : current_step.step_id, "uc" : uncontrolled_action, "branching" : true});
        end_of_protocol = true;
    }

    if(end_of_protocol || buffer_previews.length == this.parent.num_previews || desired_previews < 1){
        // end the recursion and return the stored previews
        // return doesnt work?
        return buffer_previews;
    }else{
        var next_step = this.parent.steps[next_action.action_val];
        return this.recursivePreviews(next_step, buffer_previews, desired_previews);
    }
}
treeLogicStep.prototype.getUncontrolled = function(){
    var uc_arr = [];
    for(var key in this.bp_options){
        var action_text = null;
        var action_val  = null;
        if(key.indexOf("Uncontrolled") > -1){
            action_text = key;
            action_val  = this.bp_options[key];
            var an_uc   = {"action_text" : action_text, "action_val" : action_val};
            uc_arr.push(an_uc);
        }
    }
    return uc_arr;
}
treeLogicStep.prototype.checkDelta = function(){
    if(this.previous_step){
        //find increases in step
        for(var i in this.med_dose){
            var drug_name = i;
            var drug_dose = parseFloat(this.med_dose[i]);
            if(this.previous_step.med_dose.hasOwnProperty(drug_name)){
                var prev_dose = parseFloat(this.previous_step.med_dose[drug_name]);
                if(drug_dose > prev_dose){
                    this.jq.find("li."+drug_name).addClass("increase");
                }else if(drug_dose < prev_dose){
                    this.jq.find("li."+drug_name).addClass("decrease");
                }
            }
        }

        //find new drugs in step
        var just_meds_current   = Object.keys(this.med_dose);
        var just_meds_prev      = Object.keys(this.previous_step.med_dose);
        var diff                = just_meds_current.filter(x=>!just_meds_prev.includes(x));
        for(var i in diff){
            this.jq.find("li."+diff[i]).addClass("add");
        }
    }
}
treeLogicStep.prototype.showRecModal = function(){
    var tpl     = $(rec_modal);
    var _this   = this;

    var patient = this.parent.patient;
    patient["current_drugs"] = this.drugs.join(", ");

    // Add This Steps Drugs
    tpl.find("h4").text(this.drugs.join(", "));

    var patient_baseline_summ = "Patient, "+patient["patient_fname"]+ " " + patient["patient_mname"] + " " + patient["patient_lname"] + ", " + patient["patient_age"] + " " + patient["sex"] + 
        ", BMI-"+ patient["bmi"]+", "+patient["patient_group"]+", \
        lab test (CR 07mg/dl), Calculated eGFR 108.   \
        She is intolerant to Lisinopril/Cough.  \
        Her BP Target is "+patient["patient_bp_target_systolic"]+"/"+patient["patient_bp_target_diastolic"]+" and \
        her measured BP is 140/85, 8 out of 10 readings in 2 weeks.";
    tpl.find(".natural_text").append($("<p>").text(patient_baseline_summ));
    
    
    if(this.parent.rec_step == this.step_id){
        tpl.find("h3").text("Recommended Step Change");
        var patient_change_rec    = "It is recommended to change medications from <b>\""+this.parent.steps[this.parent.current_step].drugs.join(", ")+"\"</b> to <b><em>\""+this.drugs.join(", ")+"\"</em></b>.";
        tpl.find(".natural_text").append($("<p>").html(patient_change_rec));
    }

    //If Reject
    tpl.find(".reject").click(function(){
        tpl.fadeOut("medium", function(){
            $(this).remove();
        });
    });

    // If Accept
    tpl.find(".accept").click(function(){
        tpl.fadeOut("medium", function(){
            $(this).remove();
        });

        patient["provider_comment"] = $("#provider_comment").val() ?? "accepted recommendation";
        $.ajax({
            url : _this.parent["ajax_endpoint"],
            method: 'POST',
            data: { "action" : "accept_rec" , "record_id" : patient["record_id"], "patient" : patient },
            dataType: 'json'
        }).done(function (result) {
            console.log(result);
            //UPDATE UI
            _this.makeCurrent();
            _this.previous_step.makePrevious();
        }).fail(function () {
            console.log("something failed");
        });
    });

    $("body").append(tpl);
}


treeLogicStep.prototype.saveChange = function(){
    //save a record of the change to patient , tree change log
    var log_data = {
        "patient_id" : this.parent.patient_id,
        "status"     : this.branch_status,
        "prev_idx"      : this.prev_idx,
        "step_idx"      : this.step_idx,
        "next_idx"      : this.next_idx,
        "current_meds"  : this.current_meds,
        "next_meds"     : this.next_meds
    }


    $.ajax({
        type:'POST',
        data: {"action" : "log_step", "data" : log_data },
        dataType: 'json',
        success:function(result){
            console.log(log_data);
            console.log(result);
        }
    });
}

