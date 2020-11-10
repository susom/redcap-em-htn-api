
//TREE BUILDING CLASSES
function prescriptionTree(tree_json, record_id){
    this.record_id  = record_id == 99 ? null : record_id;
    this.meds       = [];

    $("#prescription_tree").empty();
    this.jq         = this.initHTML();

    // If editing existing tree draw it
    if(tree_json.hasOwnProperty(record_id)){
        this.name       = tree_json[record_id].name;
        this.raw_meds   = tree_json[record_id].meds;

        for(var i in this.raw_meds){
            this.addMed(this.raw_meds[i]);
        }
    }

    //bind some events to the template html
    this.bindEvents();
}
prescriptionTree.prototype.initHTML = function(){
    //from template.js
    var ptree = $(tree_table_template);
    $("#prescription_tree").append( ptree );

    return ptree;
}
prescriptionTree.prototype.addMed = function(med_data){
    this.meds.push( new medRow(this, med_data) );
}
prescriptionTree.prototype.bindEvents = function(){
    var _this = this;

    this.jq.on("click","#save_edit",function(e){
        console.log("save current tree edit");
        _this.saveTree();
        e.preventDefault();
    });

    this.jq.on("click","#save_as_new",function(e){
        console.log("save as new tree");

        var step_name = $("#new_name").val();
        if(step_name == ""){
            alert("Please fill out 'new name'");
            return false;
        }else{
            _this.record_id = null;
        }

        _this.name = step_name;
        _this.saveTree();

        e.preventDefault();
    });

    this.jq.on("click","#add_med",function(e){
        _this.addMed();
        e.preventDefault();
    });

    this.jq.on("click","#new_tree",function(e){
        $("#current_treatment_plan_id").val(99);
        $("#current_treatment_plan_id").change();
        e.preventDefault();
    });
}
prescriptionTree.prototype.saveTree = function(){
    var tree_plan = {};
    var _this = this;

    if(this.record_id){
        console.log("saving is there a record_id", this.record_id);
        tree_plan.record_id = this.record_id;
    }
    tree_plan.name              = this.name;
    tree_plan.treatment_plan    = [];

    $(".drug_step").each(function(){
        var drug        = {};
        var row         = $(this);

        drug.tree_med_name          = row.find(".med_name :selected").val();
        drug.tree_med_class         = row.find(".med_name :selected").parent("optgroup").attr("label");
        drug.tree_med_note          = row.find(".med_notes").val();
        drug.tree_med_alt_name      = row.find(".alt_med_name :selected").val();
        drug.tree_med_alt_dose      = row.find(".alt_med_custom_dose").val();
        drug.unit                   = row.find(".med_name :selected").data("unit");
        drug.steps                  = [];

        //now get doses
        row.find(".drug_dose").each(function(idx){
            var step                = $(this);
            var counter             = idx + 1;
            var evaluation          = $("#dose_head .drug_dose:nth-child("+counter+")").find("input").val();

            var dose_step           = {};
            dose_step.dose          = step.find(".custom_dose").val() != "" ? step.find(".custom_dose").val() : (step.find(".dose_select :selected").val() != "Dosage" && step.find(".dose_select :selected").length ? step.find(".dose_select :selected").val() : false );
            dose_step.freq          = step.find(".freq_select :selected").val() != "Frequency" &&  dose_step.dose ? step.find(".freq_select :selected").val() : false;
            dose_step.evaluation    = !dose_step.dose  ? false : evaluation;
            drug.steps.push(dose_step);
        });
        tree_plan.treatment_plan.push(drug);
    });

    console.log(tree_plan);
    $.ajax({
        type:'POST',
        data: {"data" : tree_plan, "action" : "save_tree"},
        dataType: 'json',
        success:function(result){
            _this.record_id  = result.record_id;
            _this.name       = result.name;

            //update the tree dropdown
            if(!$("#current_treatment_plan_id option[value="+_this.record_id+"]").length){
                $("#current_treatment_plan_id").append($("<option>").val(_this.record_id).text(_this.name).prop("selected",true));
            }
        }
    });
}
prescriptionTree.prototype.highLightCol = function(){
}

function medRow(parent, med_json){
    if(med_json === undefined){
        med_json = {};
    }
    this.tree       = parent;
    this.drug_class = med_json.hasOwnProperty("class")      ? med_json.class : null;
    this.name       = med_json.hasOwnProperty("name")       ? med_json.name : null;
    this.note       = med_json.hasOwnProperty("note")       ? med_json.note : null;
    this.alt        = med_json.hasOwnProperty("alt")        ? med_json.alt : null;
    this.alt_dose   = med_json.hasOwnProperty("alt_dose")   ? med_json.alt_dose : null;

    this.steps      = [];
    this.jq         = this.appendHTML($("#tree_rows_above"));

    //add in the steps
    if(med_json.hasOwnProperty("steps")) {
        for (var i in med_json.steps) {
            var j = parseInt(i);
            this.steps.push(new medStep(med_json.steps[i], j, this));
        }
    }else{
        for (var i = 1; i < 11; i++) {
            this.steps.push(new medStep([], i, this));
        }
    }

    //if editing set data state
    if(this.name){
        this.setupEdit();
    }
    //bind some events to the template html
    this.bindEvents();
}
medRow.prototype.appendHTML = function(parentContainer){
    //from template.js
    var medrow = $(tree_medrow_template);
    parentContainer.before(medrow);

    return medrow;
}
medRow.prototype.bindEvents = function(){
    var _this = this;

    this.jq.on("click",".trashit",function(){
        _this.jq.fadeOut(function(){
            _this.jq.remove();
        });
        return false;
    });

    this.jq.on("change", ".med_name", function(){
        _this.changeMed($(this));
        return false;
    });
}
medRow.prototype.setupEdit = function(){
    if(this.name){
        var el = this.jq.find(".med_name");
        el.find("option[value='"+this.name+"']").prop("selected",true);
        this.changeMed(el);
    }
    if(this.note){
        this.jq.find(".med_notes").val(this.note);
    }
    if(this.alt){
        this.jq.find(".alt_med_name").find("option[value='"+this.alt+"']").prop("selected",true);
    }
    if(this.alt_dose){
        this.jq.find(".alt_med_custom_dose").val(this.alt_dose);
    }
}
medRow.prototype.changeMed = function(el){
    var _this = this
    var opt     = el.find(":selected");
    var optgrp  = opt.parent().attr('label');
    var par     = opt.closest("tr");

    var unit    = opt.data("unit");
    var dosage  = opt.data("dosage");
    var freq    = opt.data("freq") ? opt.data("freq") : 1;
    var note    = opt.data("note");
    var name    = opt.val();


    par.find(".dose_select").each(function(idx){
        $(this).empty();
        $(this).append($("<option>").text("Dosage"));
        for(var i in dosage){
            var dose_opt = $("<option>").val(dosage[i]).text(dosage[i]+unit);
            $(this).append(dose_opt);
        }
        $(this).attr("data-drugname",name);
        $(this).attr("data-drugunit",unit);

        var new_jq = $(this).closest(".drug_dose");

        //oh boy
        _this.steps[idx].new_jq = new_jq;
    });

    par.find(".freq_select").each(function(idx){
        $(this).find('option[value="'+freq+'"]').prop("selected", "selected");
    });
    par.find(".med_notes").val(note);
    $(this).attr("data-unit",unit);
}

function medStep(step_data,i, parent){
    var ev = "evaluate_step_" + i;
    var ds = "step_dose_" + i;
    var fr = "freq_step_" + i;

    this.step_idx           = i;
    this.evaluate           = step_data[ev];
    this.dose               = step_data[ds];
    this.frequency          = step_data[fr];
    this.parent             = parent;
    this.parentContainer    = this.parent.jq;

    this.jq                 = this.appendHTML();

    if(this.dose !== "" && this.dose !== undefined){
        this.prefill();
    }

    //bind some events to the template html
    this.bindEvents();
}
medStep.prototype.appendHTML = function(){
    //from template.js
    var medstep = $(tree_medstep_template);
    this.parentContainer.find(".dose_ladder").append(medstep);

    return medstep;
}
medStep.prototype.bindEvents = function(){
    var _this = this;

    this.jq.on("click", ".dose_actions a",function(e){
        var action = $(this).data("action");
        switch(action){
            case "dupe_prev_dose":
                var prev_dose = $(this).closest(".drug_dose").prev().find(".dose_select option:selected").val();
                $(this).closest(".drug_dose").find('.dose_select option[value="'+prev_dose+'"]').prop("selected","selected");
            case "new_dose":
                $(this).parent().next().show();
                break;
            default:
                var clear_this = $(this).closest(".drug_dose").find(".dose_selects");
                clear_elements(clear_this,["freq_select"]);
                $(this).closest(".drug_dose").find(".dose_select option:first-child").prop("selected","selected");
                $(this).parent().next().hide();
                break;
        }
        e.preventDefault();
    });

    this.jq.on("change",".dose_select",function(e){
        var col_index = $(this).closest(".drug_dose").index() + 1;

        var col_drug_classes = {};
        $("#med_steps .dose_ladder .drug_dose:nth-child("+col_index+") .dose_select option:selected").each(function(){
            if($(this).val() != "Dosage"){
                var drugname = $(this).parent().data("drugname");
                var drugunit = $(this).parent().data("drugunit");
                col_drug_classes[drugname] = {"dose" : $(this).val() + drugunit, "freq" : $(this).closest(".dose_selects").find(".freq_select :selected").val() };
            }
        });

        $("#plan_summary .drug_dose:nth-child("+col_index+")").empty();
        for(var i in col_drug_classes){
            var drugsumm = $("<span>").text(i + " " + col_drug_classes[i]["dose"] + " " + col_drug_classes[i]["freq"] + "x daily");
            $("#plan_summary .drug_dose:nth-child("+col_index+")").append(drugsumm);
        }

        e.preventDefault();
    });

    this.jq.on("click", ".drug_dose", function(e){
        $(".drug_dose.active").removeClass("active");
        var round_index = $(this).index();
        var select_index = round_index+1;
        $("#med_steps .dose_ladder .drug_dose:nth-child("+select_index+")").addClass("active");

        e.preventDefault();
    });
}
medStep.prototype.prefill = function(){
    this.jq.find(".dose_selects").show();

    this.updateSummary();

    this.jq.find(".dose_select").find("options").each(function(){

    });

    //crap , it has to be added before it can be modified, but this.jq is saved copy of old one how to refresh?
    this.jq.find(".dose_select").find("option[value='"+this.dose+"']").prop("selected",true);

    if(!this.jq.find(".dose_select").find("option[value='"+this.dose+"']").length){
        this.jq.find(".dose_select").find("option[value='Dosage']").prop("selected",true);
        this.jq.find(".custom_dose").val(this.dose);
    }

    this.jq.find(".freq_select").find("option[value="+this.frequency+"]").prop("selected",true);

}
medStep.prototype.updateSummary = function(){
    this.parent.tree.jq.find(".plan_summ:nth-child("+this.step_idx+") em").append( $("<span>").text(this.parent.name + " " + this.dose + "mg") );
}




