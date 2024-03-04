function dashboard(record_id,urls, is_sponsored=false){
    for(var i in urls){
        this[i] = urls[i];
    }
    this.is_sponsored   = is_sponsored;
    this.provider       = record_id;
    this.patient_detail = {}; //buffer patient details to avoid a roundtrip if already called once in the sessions

    //maintain state across refreshData()
    this.cur_patient    = null;
    this.filter_nav     = [];
    //the two above will maintain state across refreshData()

    if(typeof(Storage) !== "undefined"){
        if(this.getSession("cur_patient")){
            // console.log("this was in session storage cur_patient", this.getSession("cur_patient") );
            this.cur_patient = this.getSession("cur_patient");
        }

        if(this.getSession("filter_nav")){
            // console.log("this was in session storage filter_nav", this.getSession("filter_nav") );
            this.filter_nav = this.getSession("filter_nav");
        }

        if(this.getSession("patient_detail")){
            // console.log("this was in session storage patient_detail", this.getSession("patient_detail") );
            // this.patient_detail = this.getSession("patient_detail");
        }
    }else{
        console.log("no local storage support");
    }

    this.intf           = null;
    this.filter_txn     = {"rx_change" : "Prescription Change Needed", "labs_needed" : "Lab Results Needed" , "data_needed" : "Data Needed", "all_patients" : "All Patients", "clear_filters" : "Clear Filters"};
    var _this           = this;
    //some UI/UX
    $("#overview").on("click","h1", function(e){
        e.preventDefault();
        if($(this).parent().next().is(":visible")){
            $(this).parent().next().slideUp("fast");
        }else{
            $(this).parent().next().slideDown("medium");
        }
    });

    //hijack add patient for consent flow
    $(".add_patient").click(function(e){
        e.preventDefault();

        //UI FOR NEW PENDING PATIENT

        //NEED TO prevent DOUBLE CLICKS
        if(!$(this).prop("disabled")){
            //disable it until ajax returned
            $(this).prop("disabled",true);
        }

        //TODO , IF PROVIDER = super_delegate, then need a dropdown of ALL provider IDS + names?
        if(_this.intf["super_delegate"].length){
            var tpl = $(providers_modal);
            $("body").append(tpl);

            for(var i in _this.intf["super_delegate"]){
                let provider        = _this.intf["super_delegate"][i];
                let provider_id     = provider["record_id"];
                let provider_name   = provider["provider_fname"] + " " + " " + provider["provider_lname"] + " (pid : "+provider_id+")";
                let opt             = $("<option>").val(provider_id).text(provider_name);
                $("#provideroptions").append(opt);
            }

            //bind events to the email consent buttons
            tpl.find(".continue .cancel").click(function(e){
                e.preventDefault();
                tpl.remove();
            });

            tpl.find(".continue .send").click(function(e){
                e.preventDefault();
                //TOO IF NOTHING SELECTED ALERT
                let provider_id = $( "#provideroptions option:selected" ).val();
                _this.addPatient(provider_id);
                tpl.remove();
            });
        }else{
            //KICK OFF AJAX TO ADD CREATE EMPTY PATIENT RECORD + ID
            _this.addPatient();
        }
    });

    this.refreshData();
    var _this = this;
    setInterval(function(){
        _this.refreshData();
    },180000);  // get new data every 3 min

    if(!this.cur_patient){
        _this.displayPatientDetail();
    }
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
    }).fail(function () {
        console.log("something failed");
    });
}
dashboard.prototype.updateOverview = function(){
    var intf            = this.intf;
    var patients        = intf["patients"];
    var pending_patients= intf["pending_patients"];
    var rx_change       = intf["rx_change"];
    var labs_needed     = intf["labs_needed"];
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
    // var tpl = $(overview_filter);
    // tpl.addClass("alerts");
    // tpl.find(".stat p").text("Alerts");
    // tpl.find(".stat i").text(msg_count);
    // tpl.find(".stat-body").remove();
    // tpl.find(".stat p").click(function(e){
    //     e.preventDefault();
    //     if($("#alerts").is(":visible")){
    //         $("#alerts").slideUp("fast");
    //     }else{
    //         $("#alerts").slideDown("medium");
    //     }
    // });
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
    var total_patients = Object.keys(patients).length + Object.keys(pending_patients).length;
    var tpl = $(overview_filter);
    tpl.addClass("all_patients");
    tpl.find(".stat").data("filter", "all_patients").data("idx", Object.keys(patients));
    tpl.find(".stat p").text(total_patients);
    if(Object.keys(pending_patients).length){
        tpl.find(".stat p").addClass("pending");
    }
    tpl.find(".stat i").text("Patients");
    tpl.find(".stat-body .stat-title").text("All Patients");
    tpl.find(".stat p").click(function(e){
        e.preventDefault();
        if($("#alerts").is(":visible")){
            $("#alerts").slideUp("fast");
        }else{
            $("#alerts").slideDown("medium");
        }
    });
    $("#filters").append(tpl);

    //Build Nav Defailt is ALL Patients
    this.buildNav();
}
dashboard.prototype.updateAlerts = function(){
    var intf    = this.intf;
    var alerts  = intf["pending_patients"];

    $("#alerts_tbody").empty();
    for(var i in alerts){
        var consent_alert   = alerts[i];
        var patient_id      = consent_alert["patient_id"];
        let patient_email   = consent_alert["consent_email"] ? consent_alert["consent_email"] : "n/a";
        let patient_name    = consent_alert["patient_consent_name"] ? consent_alert["patient_consent_name"] : patient_email;

        let consent_url     = consent_alert["consent_url"];
        let consent_sent    = consent_alert["consent_sent"];
        let consent_ts      = consent_alert["consent_date"];

        var row             = $(alerts_row);
        row.attr("id", "pending_patient_"+patient_id);

        row.find(".consent_url").html(consent_url);
        if(consent_ts){
            //PATIENT HAS CONSENTED
            //MAKE EDIT PATIENT LINK
            console.log(patient_id + " has consented so make edit patient link");

            var edit_link = $("<a>").attr("href",this["edit_patient"]).data("patient",patient_id).text(patient_id);
            edit_link.click(function(e){
                e.preventDefault();
                location.href=$(this).attr("href")+"&patient="+$(this).data("patient")+"&consented=1";
            });
            patient_id = edit_link;
        }else{
            //PATIENT HAS NOT CONSENTED
            if(!consent_sent){
                //IF NOT USING REDCAP TO SEND (OR IF NOT SENT THEN ADD THIS FUNCTIONALITY;
                this.consentBindings(row, patient_id, consent_url);
            }
        }

        row.find(".patient_id").html(patient_id);
        row.find(".patient_name").html(patient_name);
        row.find(".consent_sent").html((consent_sent ? consent_sent : "n/a"));
        row.find(".consent_ts").html((consent_ts ? consent_ts : "n/a"));

        $("#alerts_tbody").prepend(row);
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
                _this.deleteSession("filter_nav");
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
        newnav.click(function(e, isAutoClick){
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

                //TODO
                console.log("TODO, why doesnt the patientdetail have bp_resyltsalready?");
                result["bp_readings"] = _this.intf["patients"][result.record_id]["bp_readings"];

                _this.patient_detail[record_id] = result;
                _this.setSession("patient_detail",_this.patient_detail);

                //artificial delay to draw the eye when theres a change
                $("#patient_details").removeClass().addClass("col-md-8 none_selected bg-light rounded").empty().addClass("loading_patient");

                if(isAutoClick){
                    //if its autoclick from a refreshData call dont bother with the fake settimeout
                    _this.displayPatientDetail(record_id);
                }else{
                    setTimeout(function(){
                        _this.displayPatientDetail(record_id);
                    },1250);
                }
            }).fail(function (e) {
                console.log(e,"something failed");
            });

        });

        $("#patient_list").append(newnav);

        if(_this.cur_patient == patient["record_id"]){
            //preserving page state while page data refreshes
            newnav.trigger("click", true);
        }
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
        // console.log("patient info", patient);

        tpl.find(".dob").text(patient["patient_birthday"]);
        tpl.find(".age").text(patient["patient_age"]);
        tpl.find(".sex").text(patient["sex"]);
        tpl.find(".weight").text(patient["weight"]);
        tpl.find(".height").text(patient["height"]);
        tpl.find(".bmi").text(patient["bmi"]);
        tpl.find(".demographic").text(patient["patient_group"]);

        var temp_comorbid = patient["comorbidity"].split(";");
        var comorbid_list = [];
        for(var i in temp_comorbid){
            var cm = temp_comorbid[i];
            comorbid_list.push("<li>"+cm.trim()+"</li>");
        }

        tpl.find(".comorbidity").html(comorbid_list.join("\r\n"));

        var need_CRK    = patient["crk_readings"].length ? false : true;
        if(!need_CRK){
            for(var i in patient["crk_readings"]){
                var lab = patient["crk_readings"][i];
                var lab_name = lab["lab_name"];
                var lab_val  = lab["lab_value"];
                var lab_ts   = lab["lab_ts"];

                var inputname = "." + lab_name ;
                tpl.find(inputname + " input").val(lab_val);
                tpl.find(inputname + " i span").text(lab_ts);
            }
        }

        var cuff_type   = patient["bp_readings"].length ? "<b>" + patient["bp_readings"][0]["bp_device_type"] + "</b>" : "N/A";
        if(patient["omron_client_id"] == ""){
            var request_auth_text = patient["omron_auth_request_ts"] != "" ? "Request Sent, Send Again?" : "Request Data Authorization";
            var emaillink = $("<i>").text(request_auth_text);
            emaillink.addClass("email");
            emaillink.click(function(e){
                $(this).text("Sending Authorization Request ...");

                e.preventDefault();
                var _el = $(this);
                $.ajax({
                    url : _this["ajax_endpoint"],
                    method: 'POST',
                    data: { "action" : "sendAuth", "patient" : patient},
                    dataType: 'json'
                }).done(function (result) {
                    if(result){
                        setTimeout(function(){
                            _el.addClass("sent");
                            _el.text("Request Sent, Send Again?");
                        }, 1000);
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
        tpl.find(".patient_profile figcaption.h1").text(patient["patient_fname"] + " " + patient["patient_mname"] + " " + patient["patient_lname"]);
        tpl.find(".patient_profile figcaption.contact").html("<b>MRN:</b> " + patient["patient_mrn"] + " <span class='mx-2'>|</span> <b>Email:</b> " + patient["patient_email"] + " <span class='mx-2'>|</span> <b>Cell:</b> " + patient["patient_phone"])
        tpl.find(".edit_patient").attr("href", _this["edit_patient"]).data("patient",patient["record_id"]);
        tpl.find(".delete_patient").data("patient",patient["record_id"]).data("patient_name",patient["patient_fname"] + " " + patient["patient_mname"] + " " + patient["patient_lname"]);

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
            location.href=$(this).attr("href")+"&patient="+$(this).data("patient");
        });

        tpl.find(".delete_patient").click(function(e){
            e.preventDefault();
            if (window.confirm("Please confirm deletion of patient [" + $(this).data("patient_name") +"]" )) {
                $.ajax({
                    url : _this["ajax_endpoint"],
                    method: 'POST',
                    data: { "action" : "delete_patient" , "record_id" : $(this).data("patient")},
                    dataType: 'json'
                }).done(function (result) {
                    if(!result["errors"].length){
                        //NEED TO IMMEDIELTY REFRESH DASHBOARD NOW!
                        _this.refreshData();

                        $("#patient_details").empty().addClass("none_selected").addClass("bg-light").addClass("rounded");
                        var tpl = $("<h1>No Patient Selected</h1>");
                        $("#patient_details").append(tpl);
                    }
                }).fail(function () {
                    console.log("something failed");
                });
            }
        });

        tpl.find(".cr_reading").change(function(e){
            e.preventDefault();
            $.ajax({
                url : _this["ajax_endpoint"],
                method: 'POST',
                data: { "action" : "update_cr_reading" , "reading" : $(this).val() , "record_id" : patient["record_id"], "patient" : patient },
                dataType: 'json'
            }).done(function (result) {
                var lab      = result["data"];
                var lab_name = lab["lab_name"];
                var lab_val  = lab["lab_value"];
                var lab_ts   = lab["lab_ts"];

                var inputname = "." + lab_name ;
                tpl.find(inputname + " input").val(lab_val);
                tpl.find(inputname + " i span").text(lab_ts);
            }).fail(function () {
                console.log("something failed");
            });
        });

        tpl.find(".k_reading").change(function(e){
            e.preventDefault();
            console.log("update k reading", $(this).val());
            $.ajax({
                url : _this["ajax_endpoint"],
                method: 'POST',
                data: { "action" : "update_k_reading" , "reading" : $(this).val() ,"record_id" : patient["record_id"], "patient" : patient },
                dataType: 'json'
            }).done(function (result) {
                var lab      = result["data"];
                var lab_name = lab["lab_name"];
                var lab_val  = lab["lab_value"];
                var lab_ts   = lab["lab_ts"];

                var inputname = "." + lab_name ;
                tpl.find(inputname + " input").val(lab_val);
                tpl.find(inputname + " i span").text(lab_ts);
            }).fail(function () {
                console.log("something failed");
            });

        });

        // PTREE LOG
        var patient_tree_id = patient["current_treatment_plan_id"];
        patient_tree_id = this.intf["ptree"].hasOwnProperty(patient_tree_id) ? patient_tree_id : 1;

        var json_tree_logs = patient["tree_log"];
        //Always add the first free step
        json_tree_logs.unshift({
            "ptree_current_meds": this.intf["ptree"][patient_tree_id]["logicTree"][0]["drugs"].join(", "),
            "ptree_log_ts": patient["patient_add_ts"]
        });
        tpl.find(".presription_tree .content").empty();
        for(var i in json_tree_logs){
            var log = json_tree_logs[i];
            var log_step = $(tree_log_step);

            var ts_temp = log["ptree_log_ts"].split(" ");
            log_step.find(".ts h5").html(ts_temp[0]);
            log_step.find(".ts em").html(ts_temp[1]);
            log_step.find(".meds h6").html(log["ptree_current_meds"]);

            if(log.hasOwnProperty("ptree_step_intolerances") && log["ptree_step_intolerances"].length){
                log_step.find(".intolerances span").html(log["ptree_step_intolerances "]);
            }else{
                log_step.find(".intolerances").remove();
            }

            if(log.hasOwnProperty("ptree_log_comment") && log["ptree_log_comment"].length){
                log_step.find(".comment span").html(log["ptree_log_comment"]);
                log_step.find(".comment").hide();
                log_step.find(".note").click(function(){
                    if($(this).next(".comment").is(":visible")){
                        $(this).next(".comment").slideUp("fast");
                    }else{
                        $(this).next(".comment").slideDown("medium");
                    }
                });
            }else{
                log_step.find(".note").remove();
                log_step.find(".comment").remove();
            }

            tpl.find(".presription_tree .content").append(log_step);
        }
        var view_tree   = $("<button>").text("View Patient Interactive Tree View").addClass("btn btn-info btn-large");
        var log_step    = $(tree_log_step);
        log_step.find("ul").remove();
        log_step.find(".instep").css("text-align","center").css("padding","0px 20px 20px").append( view_tree );
        view_tree.click(function(e){
            e.preventDefault();
            location.href = _this["ptree_url"]+"&patient="+record_id;
        });
        tpl.find(".presription_tree h3 em b").text(this.intf["ptree"][patient_tree_id]["label"]);
        tpl.find(".presription_tree .content").append(log_step);

        //REC LOG
        tpl.find(".recs_log .content").empty();
        var rec_logs = patient["rec_logs"];
        if(rec_logs.length){
            for(var i in rec_logs){
                var rec_log     = rec_logs[i];
                var log_step    = $(rec_log_step);

                var cur_drugs       = rec_log["cur_drugs"];
                var rec_drugs       = rec_log["rec_drugs"];
                var bp_units        = rec_log["bp_units"];
                var mean_systolic   = rec_log["mean_systolic"];
                var target_systolic = rec_log["target_systolic"];
                var diff_systolic   = mean_systolic - target_systolic;
                var rec_status      = rec_log["rec_status"]; //Accepted / No change
                var rec_action      = $("<btn>").addClass("btn").addClass("btn-secondary").addClass("btn-xs").text("Copy to Clipboard");//;rec_log["rec_action"]; //Sent to Pharmacy / None
                var rec_ts          = rec_log["rec_ts"];

                var rec_p = $("<p>").addClass("summary").html("MRN : " + patient["patient_mrn"] + "\r\n<br>" + "Date: " + rec_ts +"\r\n");
                log_step.find(".rec_summaries").append(rec_p);
                var rec_p = $("<p>").addClass("summary").html(patient["patient_fname"]+"'s mean systolic reading over the last 2 weeks was <b>"+mean_systolic+bp_units+"</b>. " + diff_systolic + bp_units + " over their goal of <b>" + target_systolic + bp_units + "</b>."+"\r\n");
                log_step.find(".rec_summaries").append(rec_p);
                var rec_p = $("<p>").addClass("summary").html(patient["patient_fname"] + " is currently taking <b>" +cur_drugs + "</b>.  It is recommended that they move on to next step and use the new course of medications : <b>"+rec_drugs+"</b>");
                log_step.find(".rec_summaries").append(rec_p);

                log_step.find(".rec_status b").append(rec_status);
                log_step.find(".rec_action b").append(rec_action);
                log_step.find(".rec_ts b").append(rec_ts);

                rec_action.data("cptext",log_step.find(".rec_summaries").text());
                rec_action.click(function(){
                    let cptext = $(this).data("cptext");
                    copyToClipboard(cptext);
                });

                tpl.find(".recs_log .content").append(log_step);
            }
        }else{
            var none_msg = $("<em>").addClass("none_msg").text("No recommendations have been generated yet.");
            tpl.find(".recs_log .content").append(none_msg);
        }



        //BP READINGS GRAPH
        if(patient["bp_readings"].length){
            // generateBpGraph(patient["bp_readings"]);
            this.graphBpData();
        }else{
            var nodata = $("<em>").addClass("nodata").text("No BP data within the past 2 weeks");
            tpl.find("#bpchart").html(nodata);
        }

        tpl.find("#run_bp_eval").click(function(e){
            e.preventDefault();

            $.ajax({
                url : _this["ajax_endpoint"],
                method: 'POST',
                data: { "action" : "manual_eval_bp" , "record_id" : patient["record_id"], "patient" : patient },
                dataType: 'json'
            }).done(function (result) {
                console.log("yay it did it");
                //NEED TO IMMEDIELTY REFRESH DASHBOARD NOW!
                _this.refreshData();
            }).fail(function () {
                console.log("something failed");
            });


        });

        // TODO FIX PTREE
        if(patient["filter"] == "rx_change"){
            var rec = $(recommendation);
            var patient_id          = record_id;
            var cur_tree_step_idx   = patient["patient_treatment_status"] ? parseInt(patient["patient_treatment_status"]) : 0;
            var cur_tree_id         = patient["current_treatment_plan_id"] ? patient["current_treatment_plan_id"] : 1;
            var cur_drugs           = _this.intf["ptree"][cur_tree_id]["logicTree"][cur_tree_step_idx]["drugs"].join(", ");


            var rec_tree_step_idx   = parseInt(patient["patient_rec_tree_step"]);
            var rec_drugs           = _this.intf["ptree"][cur_tree_id]["logicTree"][rec_tree_step_idx]["drugs"].join(", ");
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

            if(_this.is_sponsored){
                //remove controls;
                rec.find("#provider_comment").hide();
                rec.find(".send_to_pharmacy").hide();
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

                        _this.patient_detail["filter"] = null;

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
        }else if((patient["filter"] == "rx_change")){
            console.log("LAB NEEEDED FOO")
        }

    }else{
        $("#patient_details").addClass("none_selected").addClass("bg-light").addClass("rounded");
        var tpl = $("<h1>No Patient Selected</h1>");
    }
    $("#patient_details").append(tpl);

    //open to recoommendation (must be after append) if it is an rxchangeoh
    if(this.cur_patient && patient["filter"] == "rx_change"){
        $(".recommendation_tab").trigger("click");
    }else{
        $(".profile_tab").trigger("click");
    }
}
dashboard.prototype.setSession = function(key,val){
    // console.log("setting session var " + key, val);
    val = JSON.stringify(val);
    sessionStorage.setItem(key,val);
}
dashboard.prototype.getSession = function(key){
    if(sessionStorage.getItem(key)){
        // console.log("getting session var "+ key);
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
dashboard.prototype.graphBpData = function(){
    var record_id   = this.cur_patient;
    var patient     = this.patient_detail[record_id];

    var parseTime   = d3.time.format("%Y-%m-%dT%H:%M:%S").parse;
    var xaxis       = [];
    var datadia     = [];
    var datasys     = [];

    var htn_bps     = patient["bp_readings"];
    for(var i in htn_bps){
        var bp      = htn_bps[i];
        var ts      = bp["bp_reading_ts"];
        var sys     = bp["bp_systolic"];
        var dia     = bp["bp_diastolic"];
        var puls    = bp["bp_pulse"];
        var pu_un   = bp["bp_pulse_units"];
        var bp_un   = bp["bp_units"];

        ts          = ts.replace(" ","T");
        xaxis.push(parseTime(ts));
        datasys.push(sys);
        datadia.push(dia);
    }

    setTimeout(function(){
        var chart = c3.generate({
            "bindto"    : "#bpchart",
            "title"     : {
                "text": function(d){
                    return patient["patient_fname"]+"'s Blood Pressure Data";
                }
            },
            "data"      : {
                "x"         : 'x',
                "json"      : {
                                "Systolic"  : datasys,
                                "Diastolic" : datadia,
                                "x"         : xaxis
                            },
                "type" : "spline"
            },


            "subchart"  : {
                "show" : false
            },
            "axis"      : {
                "x" : {
                    "type"  : 'timeseries'
                    ,"tick"  : {
                                "count" : 6
                                //'%Y-%m-%dT%H:%M:%S.%LZ'
                                //("%Y-%m-%dT%H:%M:%S%Z");
                                //1942-08-22T06:31:59-04:00'
                                ,"format" : '%Y-%m-%d'
                            }
                    ,"label" : { // ADD
                                "text"      : 'Date'
                                ,"position"  : 'outer-center'
                            }
                },
                "y" : {
                    "tick"   : {
                                "format": d3.format("s")
                            }
                    ,"label" : { // ADD
                                "text": 'Blood Pressure ' + bp_un
                                ,"position": 'outer-middle'
                            }
                },
            },
            "tooltip"   : {
                format: {
                    "title" : function(d) {
                        var dr = d;
                        return 'BP: ' + dr;
                    },
                    "value" : function(value, ratio, id) {
                        var format = id === 'data1' ? d3.format(',') : d3.format('');
                        return format(value);
                    }
                }
            }
        });
    },500);
}
dashboard.prototype.consentBindings = function(row, patient_id, consent_url){
    var _this = this;

    let btndiv      = $("<div>");
    // add copy to clip functionality for consent url
    let copyclip    = $("<btn>").text("copy to clipboard").addClass("btn-info").addClass("btn-xs").addClass("mr-2").data("consent_url",consent_url);
    btndiv.append(copyclip);

    //add email to patient functionality for consent url
    let emailconsent = $("<btn>").text("email to patient").addClass("btn-info").addClass("btn-xs").addClass("mr-2").data("consent_url",consent_url).data("patient_id",patient_id);
    btndiv.append(emailconsent);

    row.find(".consent_url").append(btndiv);

    //bind some events;
    copyclip.click(function(){
        let consent_url = $(this).data("consent_url");
        copyToClipboard(consent_url);
    });

    emailconsent.click(function(){
        let consent_url = $(this).data("consent_url");
        let patient_id  = $(this).data("patient_id");

        var tpl         = $(email_modal);
        tpl.find(".url b").text(consent_url);
        tpl.find(".id b").text(patient_id);
        tpl.find("#consent_patient_id").val(patient_id);
        tpl.find("#consent_url").val(consent_url);
        $("body").append(tpl);

        //bind events to the email consent buttons
        tpl.find(".continue .cancel").click(function(e){
            e.preventDefault();
            tpl.remove();
        });

        tpl.find(".continue .send").click(function(e){
            e.preventDefault();

            let patient_id      = $("#consent_patient_id").val();
            let consent_url     = $("#consent_url").val();
            let consent_email   = $("#consent_email").val();

            $.ajax({
                url : _this["ajax_endpoint"],
                method: 'POST',
                data: { "action" : "send_patient_consent" , "patient_id" : patient_id, "consent_url" : consent_url, "consent_email": consent_email },
                dataType: 'json'
            }).done(function (result) {
                let patient_id      = result["patient_id"];
                let consent_sent    = result["consent_sent"];
                let pending_row     = $("#pending_patient_"+patient_id);
                pending_row.find(".consent_sent").text(consent_sent);
                tpl.remove();
            }).fail(function () {
                console.log("something failed");
            });
        });
    });
}
dashboard.prototype.addPatient = function(provider_id){
    var _this = this;
    $.ajax({
        url : this["ajax_endpoint"],
        method: 'POST',
        data: { "action" : "new_patient_consent", "provider_id" : provider_id },
        dataType: 'json'
    }).done(function (result) {
        console.log("add Blank Patient", result);
        var consent_url = result["consent_link"];
        var patient_id  = result["patient_id"];

        if(consent_url && patient_id){
            //ADD TO COUNT OF TOTAL PATIENTS , TEMPORARY UNTIL THE NEXT "REAL" DATA REFRESH
            var curcount = $("#filters .all_patients p").text();
            curcount++;
            $("#filters .all_patients p").addClass("pending").text(curcount);

            //ADD ROW TO alerts* TABLE AND OPEN
            var row             = $(alerts_row);
            let patient_name    = "n/a";
            let consent_sent    = null;
            let consent_ts      = null;

            row.attr("id","pending_patient_"+patient_id);
            row.find(".patient_id").html(patient_id);
            row.find(".patient_name").html(patient_name);
            row.find(".consent_url").html(consent_url);
            if(!consent_sent){
                _this.consentBindings(row, patient_id, consent_url);
            }

            row.find(".consent_sent").html((consent_sent ? consent_sent : "n/a"));
            row.find(".consent_ts").html((consent_ts ? consent_ts : "n/a"));

            row.addClass("highlight");
            $("#alerts_tbody").prepend(row);

            $("#alerts").slideDown("medium");
            $(".add_patient").prop("disabled",false);
        }
    }).fail(function () {
        $(".add_patient").prop("disabled",false);
    });

    return;
}
function copyToClipboard(text) {
    var dummy = document.createElement("textarea");
    document.body.appendChild(dummy);
    dummy.value = text;
    dummy.style.position = 'absolute';
    dummy.style.left = '-9999px';
    dummy.select();
    document.execCommand("copy");
    document.body.removeChild(dummy);
}
