function treeViewer(tree, current_step){
    this.record_id  = Object.keys(tree)[0];
    this.patient_id = tree.patient_id;
    this.raw        = tree[this.record_id];
    this.meds       = this.raw.meds;
    this.name       = this.raw.name;
    this.steps_data = {};
    this.steps      = {};
    this.prepSteps();
}
treeViewer.prototype.prepSteps = function(){
    // Possible 10 Steps
    // Possible Overlap of multiple rows per step
    // Possible Max of 10 steps
    this.number_of_meds = this.meds.length;

    //figure out where the meds are out of 10 possible steps
    //put themn into this.steps_data to line them up
    for(var i in this.meds){
        var temp        = Object.assign({}, this.meds[i]);
        delete temp.steps;

        for(var j in this.meds[i].steps){
            j = parseInt(j);
            var sd = "step_dose_" + j;
            var es = "evaluate_step_" + j;
            var fs = "freq_step_" + j;

            var step = this.meds[i].steps[j];
            if(step[sd]){
                if(!Array.isArray(this.steps_data[j])){
                    this.steps_data[j] = [];
                }
                var step_drug = Object.assign({"dose" : step[sd] + "mg", "eval": step[es], "freq" : step[fs]}, temp);
                this.steps_data[j].push(step_drug);
            }
        }
    }

    this.step_count = Object.keys(this.steps_data).length;
    // console.log("there are " +  Object.keys(this.steps_data).length + " steps in this tree", this.steps_data);

    this.buildSteps();
}
treeViewer.prototype.buildSteps = function(){
    // Each step is an independent Unit with 3 branchpoints , BP-Uncontrolled, Controlled, Side Effect
    // Besides the first step, each Unit is closed by default
    for(var j = 1; j <= this.step_count; j++){
        this.steps[j] = new treeStep(j, this.steps_data[j], this);
    }
}

function treeStep(step_idx, data, parent){
    this.data       = data;
    this.jq         = "";
    this.parent     = parent;
    this.step_idx   = step_idx;
    this.prev_idx   = this.parent.steps_data.hasOwnProperty(step_idx-1) ? step_idx-1 : null;
    this.next_idx   = this.parent.steps_data.hasOwnProperty(step_idx+1) ? step_idx+1 : null;

    this.prev_meds      = [];
    this.current_meds   = [];
    this.next_meds      = [];


    for(var i in this.data){
        var med = this.data[i];
        this.current_meds.push({"name" : med.name, "dose" : med.dose, "eval" : med.eval});
    }

    if(this.prev_idx) {
        for (var n  in this.parent.steps_data[this.prev_idx]){
            var temp_med = this.parent.steps_data[this.prev_idx][n];
            this.prev_meds.push(temp_med.name);
            this.prev_meds.push(temp_med.alt);
        }
    }

    if(this.next_idx){
        for(var n in this.parent.steps_data[this.next_idx]){
            var med = this.parent.steps_data[this.next_idx][n];
            this.next_meds.push({"name" : med.name, "dose" : med.dose, "eval" : med.eval});
        }
    }

    this.eval       = 7;
    this.branch_status  = null;

    this.buildHTML();
}
treeStep.prototype.buildHTML = function(){
    //build and inject html into dom
    var tree_step   = $(treeview_step_template);
    var step_i      = "step_" + this.step_idx;
    tree_step.addClass(step_i);

    if(this.step_idx > 1){
        tree_step.addClass("closed");
    }

    var cur_step_meds = this.data;
    for(var k = 0; k < cur_step_meds.length; k++){
        var step_med    = cur_step_meds[k];

        //display evaluation period (days?)
        var eval_period = step_med.eval;
        this.eval       = step_med.eval;
        tree_step.find(".eval").text(eval_period + " days");

        //add main drugs
        var drug_change = "";
        if(this.prev_idx){
            drug_change =  this.prev_meds.indexOf(step_med.name) > -1 ? "increase" : "add";
        }
        var b_drug      = $("<b>").addClass("drug").text(step_med.name + " " + step_med.dose);
        var li_drug     = $("<li>").addClass(drug_change).append(b_drug);
        tree_step.find("ul.step_drugs:first-child").append(li_drug);

        //add alt drugs
        var alt_change  = "";
        if(this.prev_idx){
            alt_change = this.prev_meds.indexOf(step_med.name) > -1 ? "" : "add";
        }
        var b_drug      = $("<b>").addClass("drug").text(step_med.alt + " " + step_med.alt_dose);
        var li_drug     = $("<li>").addClass(alt_change).append(b_drug);
        tree_step.find(".next_steps ul.step_drugs").append(li_drug);
    }
    $("#viewer").append(tree_step);
    this.jq = tree_step;

    //bind click events on branches
    this.bindStepEvents();
}
treeStep.prototype.bindStepEvents = function(){
    //bind the decision branchs to reveal action
    // Bind decision branches
    var _this = this;

    this.jq.find(".branches .nc").click(function(){
        $(this).addClass("on");
        _this.branch_status = "not controlled";
        _this.saveChange();
        if(_this.next_idx){
            // console.log("not controlled, show next step", this.next_idx);
            _this.showNextStep();
        }else{
            // console.log("not controlled, no next step, panic!");
            _this.endTree();
        }
    });
    this.jq.find(".branches .controlled").click(function(){
        $(this).addClass("on");
        _this.branch_status = "controlled";
        _this.saveChange();
        _this.jq.find(".next_steps .continue").show();

        console.log("controlled!  show continue/repeat step " , _this.step_idx);
    });
    this.jq.find(".branches .se").click(function(){
        $(this).addClass("on");
        _this.branch_status = "side effect";
        _this.saveChange();
        _this.jq.find(".next_steps .se").show();

        console.log("side effect! show alternate drug + dose , meaning repeat step " , _this.step_idx);
    });
}
treeStep.prototype.showNextStep = function(){
    //show the next tree for bp-uncontrolled
    this.parent.steps[this.next_idx].calcTop();
    this.parent.steps[this.next_idx].jq.removeClass("closed")
}
treeStep.prototype.endTree = function(){
    var endTree = $("<b>").addClass("alert").addClass("alert-danger").text("Schedule Office Visit!");
    this.jq.find(".next_steps").append(endTree);
}
treeStep.prototype.calcTop = function(){
    if(this.prev_idx){
        var prev_step_height = this.parent.steps[this.prev_idx].jq.height();
        var prev_step_top    = this.parent.steps[this.prev_idx].jq.position().top;
        var current_step_top = prev_step_top + prev_step_height - 20;
        this.jq.css({ top: current_step_top +"px" });
    }
}
treeStep.prototype.saveChange = function(){
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











