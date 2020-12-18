function dashboard(record_id,urls){
    for(var i in urls){
        this[i] = urls[i];
    }

    this.provider       = record_id;
    this.patient_detail = {}; //buffer patient details to avoid a roundtrip if already called once in the sessions

    //maintain state across refreshData()
    this.cur_patient    = null;
    this.filter_nav     = [];
    //the two above will maintain state across refreshData()

    if(typeof(Storage) !== "undefined"){
        if(this.getSession("cur_patient")){
            console.log("this was in session storage cur_patient", this.getSession("cur_patient") );
            this.cur_patient = this.getSession("cur_patient");
        }

        if(this.getSession("filter_nav")){
            console.log("this was in session storage filter_nav", this.getSession("filter_nav") );
            this.filter_nav = this.getSession("filter_nav");
        }

        if(this.getSession("patient_detail")){
            console.log("this was in session storage patient_detail", this.getSession("patient_detail") );
            this.patient_detail = this.getSession("patient_detail");
        }
    }else{
        console.log("no local storage support");
    }

    this.intf           = null;
    this.filter_txn     = {"rx_change" : "Prescription Change Needed", "labs_needed" : "Lab Results Needed" , "data_needed" : "Data Needed", "all_patients" : "All Patients", "clear_filters" : "Clear Filters"};
    
    //some UI/UX 
    $("#overview").on("click","h1", function(e){
        e.preventDefault();
        if($(this).parent().next().is(":visible")){
            $(this).parent().next().slideUp("fast");
        }else{
            $(this).parent().next().slideDown("medium");
        }
    });

    this.refreshData();
    var _this = this;
    setInterval(function(){
        _this.refreshData();
    },180000);  // get new data every 3 min
}
dashboard.prototype.refreshData = function(){
    var _this       = this;
    var record_id   = this.provider;
    // console.log("refresh session , pull new data from dashboard INTF, need to maintain state!! and patient detail", this.filter_nav);
    $.ajax({
        url : this["ajax_endpoint"],
        method: 'POST',
        data: { "action" : "refresh" , "record_id" : record_id},
        dataType: 'json'
    }).done(function (result) {
        _this.intf = result;
        _this.updateOverview();
        _this.updateAlerts();
        _this.displayPatientDetail();
    }).fail(function () {
        console.log("something failed");
    });
}
dashboard.prototype.updateOverview = function(){
    var intf            = this.intf;
    var patients        = intf["patients"];
    var rx_change       = intf["rx_change"];
    var labs_needed  = intf["labs_needed"]; 
    var data_needed     = intf["data_needed"];
    var alerts          = intf["messages"];
    var _this           = this;

    var msg_count       = 0;
    for(var ts in alerts){
        var ts_alerts = alerts[ts];
        for(var n in ts_alerts){
            msg_count++;
        }
    }
    $("#filters").empty();
    //alerts
    //TODO THIS NEEDS TO BE FIRGURED OUT
    var tpl = $(overview_filter);
    tpl.addClass("alerts");
    tpl.find(".stat p").text("Alerts");
    tpl.find(".stat i").text(msg_count);
    tpl.find(".stat-body").remove();
    tpl.find(".stat p").click(function(e){
        e.preventDefault();
        if($("#alerts").is(":visible")){
            $("#alerts").slideUp("fast");
        }else{
            $("#alerts").slideDown("medium");
        }
    });
    // $("#filters").append(tpl);

    //rx_change
    var tpl = $(overview_filter);
    tpl.addClass("rx_change");
    tpl.find(".stat").data("filter", "rx_change").data("idx", rx_change);
    tpl.find(".stat p").text(rx_change.length);
    tpl.find(".stat i").text("Patients");
    tpl.find(".stat-body .stat-title").text("Prescription Change Needed");
    tpl.find(".stat").click(function(e){
        e.preventDefault();
        _this.updateFilters($(this));
    });
    if(this.filter_nav.includes("rx_change")){
        //this will maintain state when the page data is refreshed
        tpl.find(".stat").parent().addClass("picked");
    }
    $("#filters").append(tpl);
    

    //labs_needed
    var tpl = $(overview_filter);
    tpl.addClass("labs_needed");
    tpl.find(".stat").data("filter", "labs_needed").data("idx", labs_needed);
    tpl.find(".stat p").text(labs_needed.length);
    tpl.find(".stat i").text("Patients");
    tpl.find(".stat-body .stat-title").text("Lab Results Needed");
    tpl.find(".stat").click(function(e){
        e.preventDefault();
        _this.updateFilters($(this));
    });
    if(this.filter_nav.includes("labs_needed")){
        tpl.find(".stat").parent().addClass("picked");
    }
    $("#filters").append(tpl);
    

    //data_needed
    var tpl = $(overview_filter);
    tpl.addClass("data_needed");
    tpl.find(".stat").data("filter", "data_needed").data("idx", data_needed);
    tpl.find(".stat p").text(data_needed.length);
    tpl.find(".stat i").text("Patients");
    tpl.find(".stat-body .stat-title").text("Data Needed");
    tpl.find(".stat").click(function(e){
        e.preventDefault();
        _this.updateFilters($(this));
    });
    if(this.filter_nav.includes("data_needed")){
        tpl.find(".stat").parent().addClass("picked");
    }
    $("#filters").append(tpl);
    

    //all
    var tpl = $(overview_filter);
    tpl.addClass("all_patients");
    tpl.find(".stat").data("filter", "all_patients").data("idx", Object.keys(patients));
    tpl.find(".stat p").text(Object.keys(patients).length);
    tpl.find(".stat i").text("Patients");
    tpl.find(".stat-body .stat-title").text("All Patients");
    $("#filters").append(tpl);

    //Build Nav Defailt is ALL Patients
    this.buildNav();
}
dashboard.prototype.updateAlerts = function(){
    //TODO NOT FLESHED OUT YET
    var intf    = this.intf;
    var alerts  = intf["messages"];
    
    $("#alerts_tbody").empty();
    for(var ts in alerts){
        var ts_alerts = alerts[ts];
        for(var n in ts_alerts){
            var alert   = ts_alerts[n];
            var tpl     = $(alerts_row);
            tpl.find(".date span").text(alert["date"]);
            tpl.find(".subject span").text(alert["subject"]);
            tpl.find(".patient span").text(alert["patient_name"]);
            tpl.find(".check input").data("record_id", alert["record_id"]).data("redcap_repeat_instance", alert["redcap_repeat_instance"]) ;
            $("#alerts_tbody").append(tpl);
        }
    }
    return;
}
dashboard.prototype.updateFilters = function(el){
    var filter  = el.data("filter");
    if(el.parent().hasClass("picked") ){
        el.parent().removeClass("picked");
        removeA(this.filter_nav, filter);
    }else{
        el.parent().addClass("picked");
        this.filter_nav.push(filter);
    }
    this.setSession("filter_nav", this.filter_nav);
    this.buildNav();
    this.displayPatientDetail();
    return false;
}
dashboard.prototype.buildNav = function(){
    $("#patient_list").empty();
    $("#patients .filter").empty();

    var _this = this;
    var intf            = this.intf;
    var patients        = intf["patients"];

    var filters = this.filter_nav.slice();
    if(!filters.length){
        filters.push("all_patients");
    }else{
        filters.push("clear_filters");
    }

    for(var i in filters){
        var filter      = filters[i];
        var filter_tpl  = `<b class="uncontrolled_above filtered d-inline-block align-middle pl-3 mr-3 pt-3 pb-2"><span></span></b>`;
        var new_filter  = $(filter_tpl);
        new_filter.find('span').text(this.filter_txn[filter]);
        if(filter == "clear_filters"){
            new_filter.addClass("clear_filters");
            new_filter.find('span').click(function(e){
                e.preventDefault();
                _this.filter_nav = [];
                this.deleteSession("filter_nav");
                _this.updateOverview();
            });
        }
        $("#patients .filter").append(new_filter);
    }

    var displayed       = 0;
    for(var i in patients){
        var patient = patients[i];
        var filter  = patient["filter"];
        if(filters.length && !filters.includes(filter) && !filters.includes("all_patients")){
            continue;
        }

        displayed++;

        var newnav = $(patient_nav);
        newnav.data("record_id",patient["record_id"]);
        newnav.addClass(patient["filter"]);
        newnav.find("b").text(patient["patient_name"]);
        newnav.find("i").text(patient["age"] + ", " + patient["sex"]);
        newnav.find("img").attr("src", patient["patient_photo"]);
        newnav.click(function(e){
            e.preventDefault();

            var record_id = $(this).data("record_id");
            $("#patient_list .patient_tab").removeClass("active");
            var _el = $(this);

            // console.log("getting patient detail everytime? whatev if already in patient_detail");
            $.ajax({
                url : _this["ajax_endpoint"],
                method: 'POST',
                data: { "action" : "patient_details", "record_id" : record_id },
                dataType: 'json'
            }).done(function (result) {
                _el.addClass("active");
                _this.cur_patient = _el.data("record_id");
                _this.setSession("cur_patient",_this.cur_patient);
                _this.patient_detail[record_id] = result;
                _this.setSession("patient_detail",_this.patient_detail);

                //artificial delay to draw the eye when theres a change 
                $("#patient_details").removeClass().addClass("col-md-8 none_selected bg-light rounded").empty().addClass("loading_patient");
                setTimeout(function(){
                    _this.displayPatientDetail(record_id);
                },1250);
            }).fail(function (e) {
                console.log(e,"something failed");
            });

        });

        if(_this.cur_patient == patient["record_id"]){
            //preserving page state while page data refreshes
            newnav.addClass("active");
        }

        $("#patient_list").append(newnav);
    }

    if(!displayed){
        var null_text = !Object.keys(patients).length ? "You do not currently have any patients" : "No patients filtered";
        var newnav = $("<p>").text(null_text);
        $("#patient_list").append(newnav);
    }
    
}
dashboard.prototype.displayPatientDetail = function(record_id){
    $("#patient_details").removeClass().addClass("col-md-8");
    $("#patient_details").empty();

    record_id = this.cur_patient ? this.cur_patient : record_id;
    if(record_id || this.cur_patient){
        var patient = this.patient_detail[record_id];
        var tpl     = $(patient_details);
        var _this   = this;
        
        tpl.find(".dob").text(patient["patient_birthday"]);
        tpl.find(".age").text(patient["patient_age"]);
        tpl.find(".sex").text(patient["sex"]);
        tpl.find(".weight").text(patient["weight"]);
        tpl.find(".height").text(patient["height"]);
        tpl.find(".bmi").text(patient["bmi"]);
        tpl.find(".demographic").text(patient["patient_group"]);
        tpl.find(".comorbidity").text(patient["comorbidity"]);
        
        var need_CRK    = patient["crk_readings"].length ? false : true;
        if(!need_CRK){
            var last_crk = patient["crk_readings"].pop();
            tpl.find(".cr > span").text(last_crk["creatinine"]);
            tpl.find(".k > span").text(last_crk["potassium"]);

            tpl.find(".cr i span").text(last_crk["cr_ts"]);
            tpl.find(".k i span").text(last_crk["k_ts"]);
        }

        var cuff_type   = patient["bp_readings"].length ? "<b>" + patient["bp_readings"][0]["bp_device_type"] + "</b>" : "N/A";
        if(patient["omron_client_id"] == ""){
            var emaillink = $("<i>").text("Request Data Authorization");
            emaillink.addClass("email");
            emaillink.click(function(e){
                e.preventDefault();
                var _el = $(this);
                $.ajax({
                    url : _this["ajax_endpoint"],
                    method: 'POST',
                    data: { "action" : "sendAuth", "patient" : patient},
                    dataType: 'json'
                }).done(function (result) {
                    console.log("whats wrong now?", result);
                    if(result){
                        _el.addClass("sent");
                        _el.text("Authorization Request Sent");
                    }else{
                        _el.text("Error - Try Clicking Again");
                    }
                }).fail(function () {
                    console.log("something failed");
                });
            });          
            cuff_type = emaillink;
        }
        tpl.find(".patient_status").html(cuff_type);

        tpl.find(".planning_pregnancy").text(patient["planning_pregnancy"]);
        tpl.find(".pharmacy_info").text(patient["pharmacy_info"]);

        tpl.find(".pulse_goal span").text(patient["patient_bp_target_pulse"]);
        tpl.find(".systolic_goal span").text(patient["patient_bp_target_systolic"]);
        tpl.find(".diastolic_goal span").text(patient["patient_bp_target_diastolic"]);

        tpl.find(".patient_profile img").attr("src",this.anon_profile_src);
        tpl.find(".patient_profile figcaption").text(patient["patient_fname"] + " " + patient["patient_mname"] + " " + patient["patient_lname"]);
        
        tpl.find(".nav-link").click(function(e){
            e.preventDefault();
            var tab = $(this).data("tab");
            $("#patient_details .nav-link").removeClass("active");
            $(this).addClass("active");
            
            $(".panels").hide();
            $("."+tab).show();
            return false;
        });

        tpl.find(".clear_patient a").click(function(e){
            e.preventDefault();
            _this.cur_patient = null;
            _this.deleteSession("cur_patient");
            _this.buildNav();
            _this.displayPatientDetail();
        });

        tpl.find(".edit_patient").click(function(e){
            e.preventDefault();
            console.log("change out flip all of displayPaientDetail to ... editPatientDetail... same as patient detail without the recommendation tab");
        });


        if(patient["bp_readings"].length){
            var json_bp_readings = JSON.stringify(patient["bp_readings"]);
            tpl.find("section.data span").text(json_bp_readings);
        }

        if(patient["filter"] == "rx_change"){
            var rec = $(recommendation);
            console.log("patient info", patient);
            var patient_id          = record_id;  
            var cur_tree_step_idx   = parseInt(patient["patient_treatment_status"]);
            var cur_drugs           = _this.intf.ptree["logicTree"][cur_tree_step_idx]["drugs"].join(", ");

            var rec_tree_step_idx   = parseInt(patient["patient_rec_tree_step"]);
            var rec_drugs           = _this.intf.ptree["logicTree"][rec_tree_step_idx]["drugs"].join(", ");
            rec.find("h6").text(rec_drugs);
            
            var sum_bps = 0;
            for(var i in patient["bp_readings"]){
                var bp_units = patient["bp_readings"][i]["bp_units"];
                sum_bps += parseInt(patient["bp_readings"][i]["bp_systolic"]);
            }
            var mean_systolic   = Math.round(sum_bps/patient["bp_readings"].length);
            var target_systolic = patient["patient_bp_target_systolic"];
            var diff_systolic   = Math.abs(target_systolic - mean_systolic);
            
            var rec_p = $("<p>").addClass("summary").html(patient["patient_fname"]+"'s mean systolic reading over the last 2 weeks was <b>"+mean_systolic+bp_units+"</b>. " + diff_systolic + bp_units + " over their goal of <b>" + target_systolic + bp_units + "</b>.");
            rec.find(".summaries").append(rec_p);
            var rec_p = $("<p>").addClass("summary").html(patient["patient_fname"] + " is currently taking <b>" +cur_drugs + "</b>.  It is recommended that they move on to next step and use the new course of medications : <b>"+rec_drugs+"</b>");
            rec.find(".summaries").append(rec_p);

            var pharmacy = patient["pharmacy_info"] ?? "Pharmacy";
            rec.find(".send_to_pharmacy span").text(pharmacy);

            if(need_CRK){
                rec.find(".send_to_pharmacy").prepend($("<b class='mb-2'>* NOTE: check lab test before proceeding</b>"));
            }

            rec.on("click", ".view_edit_tree", function(e){
                e.preventDefault();
                //THIS SHOULD BE A POST OR AT LEAST SOMETHING THAT CHECKS PROVIDER AND patient ID
                location.href = _this["ptree_url"]+"&patient="+record_id;
            });

            rec.on("click", ".send_and_accept", function(e){
                e.preventDefault();
                
                // ADD SOME DATA TO THE patient OBJ
                patient["provider_comment"] = $("#provider_comment").val().trim() != "" ? $("#provider_comment").val() : "accepted recommendation";
                patient["current_drugs"]    = $("#recommendations .change h6").text();
                $.ajax({
                    url : _this["ajax_endpoint"],
                    method: 'POST',
                    data: { "action" : "send_and_accept" , "record_id" : patient["record_id"], "patient" : patient },
                    dataType: 'json'
                }).done(function (result) {
                    rec.slideUp("medium", function(){
                        tpl.find("#recommendations").empty();
                        tpl.find("#recommendations").append($('<p class="p-3"><i>No current recommendations</i></p>'));

                        //NEED TO IMMEDIELTY REFRESH DASHBOARD NOW!
                        _this.refreshData();
                    });
                }).fail(function () {
                    console.log("something failed");
                });
            });

            rec.on("click", ".decline_rec", function(e){
                e.preventDefault();
                
                $.ajax({
                    url : _this["ajax_endpoint"],
                    method: 'POST',
                    data: { "action" : "decline_rec" , "record_id" : patient["record_id"], "patient" : patient},
                    dataType: 'json'
                }).done(function (result) {
                    //remove recommendation from patient details
                    _this.patient_detail["patient_rec_tree_step"] = null;
                    _this.patient_detail["filter"] = null;                    
                    _this.setSession("patient_detail",_this.patient_detail);

                    rec.slideUp("medium", function(){
                        tpl.find("#recommendations").empty();
                        tpl.find("#recommendations").append($('<p class="p-3"><i>No current recommendations</i></p>'));

                        //NEED TO IMMEDIELTY REFRESH DASHBOARD NOW!
                        _this.refreshData();
                    });
                }).fail(function () {
                    console.log("something failed");
                });
            });

            tpl.find("#recommendations").empty();
            tpl.find("#recommendations").append(rec);
        }
    }else{
        $("#patient_details").addClass("none_selected").addClass("bg-light").addClass("rounded");
        var tpl = $("<h1>No Patient Selected</h1>");
    }
    $("#patient_details").append(tpl);
    
    //open to recoommendation (must be after append) if it is an rxchangeoh
    if(patient["filter"] == "rx_change"){
        $(".recommendation_tab").trigger("click");
    }else{
        $(".profile_tab").trigger("click");
    }
}
dashboard.prototype.setSession = function(key,val){
    console.log("setting session var " + key, val);
    val = JSON.stringify(val);
    sessionStorage.setItem(key,val);
}
dashboard.prototype.getSession = function(key){
    if(sessionStorage.getItem(key)){
        console.log("getting session var "+ key);
        var val = sessionStorage.getItem(key);
        return JSON.parse(val);
    }else{
        return false;
    }
}
dashboard.prototype.deleteSession = function(key){
    if(key){
        delete sessionStorage[key];
    }else{
        sessionStorage.clear();
    }
}
