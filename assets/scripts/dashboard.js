function dashboard(urls){
    for(var i in urls){
        this[i] = urls[i];
    }

    this.patient_detail = {};
    this.state          = null;
    this.nav            = null;
    this.intf           = null;
    
    this.filter_txn     = {"rx_change" : "Prescription Change Needed", "results_needed" : "Lab Results Needed" , "data_needed" : "Data Needed", "all_patients" : "All Patients"};
    this.filter_nav     = [];
    
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
    },300000);  // get new data every 5 min

    this.displayPatientDetail();
}
dashboard.prototype.refreshData = function(){
    var _this = this;
    console.log("refresh session , pull new data from dashboard INTF");
    $.ajax({
        url : this["ajax_endpoint"],
        method: 'POST',
        data: { "action" : "refresh" },
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
    var rx_change       = intf["rx_change"];
    var results_needed  = intf["results_needed"]; 
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
    $("#filters").append(tpl);

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
    $("#filters").append(tpl);

    //results_needed
    var tpl = $(overview_filter);
    tpl.addClass("results_needed");
    tpl.find(".stat").data("filter", "results_needed").data("idx", results_needed);
    tpl.find(".stat p").text(results_needed.length);
    tpl.find(".stat i").text("Patients");
    tpl.find(".stat-body .stat-title").text("Lab Results Needed");
    tpl.find(".stat").click(function(e){
        e.preventDefault();
        _this.updateFilters($(this));
    });
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

    this.buildNav();
    this.displayPatientDetail();
    return false;
}
dashboard.prototype.buildNav = function(){
    $("#patient_list").empty();
    $("#patients .filter").empty();

    var filters = this.filter_nav.slice();
    if(!filters.length){
        filters.push("all_patients");
    }

    for(var i in filters){
        var filter      = filters[i];
        var filter_tpl  = `<b class="uncontrolled_above filtered d-inline-block align-middle pl-3 mr-3 pt-3 pb-2"><span></span></b>`;
        var new_filter  = $(filter_tpl);
        new_filter.find('span').text(this.filter_txn[filter]);
        $("#patients .filter").append(new_filter);
    }

    var intf            = this.intf;
    var patients        = intf["patients"];
    var _this           = this;

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
            $.ajax({
                url : _this["ajax_endpoint"],
                method: 'POST',
                data: { "action" : "patient_details", "record_id" : record_id },
                dataType: 'json'
            }).done(function (result) {
                _el.addClass("active");
                _this.patient_detail[record_id] = result;
                _this.displayPatientDetail(record_id);
            }).fail(function (e) {
                console.log(e,"something failed");
            });
        });
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

    if(record_id){
        console.log(record_id, this.patient_detail[record_id]);

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
        
        var cuff_type = "Omron Hema 9200";
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
        tpl.find(".bp_cuff_type").html(cuff_type);

        tpl.find(".planning_pregnancy").text(patient["planning_pregnancy"]);
        tpl.find(".pharmacy_info").text(patient["pharmacy_info"]);

        tpl.find(".pulse_goal span").text(patient["patient_bp_target_pulse"]);
        tpl.find(".systolic_goal span").text(patient["patient_bp_target_systolic"]);
        tpl.find(".diastolic_goal span").text(patient["patient_bp_target_diastolic"]);

        tpl.find(".patient_status span").text("high");
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
    }else{
        $("#patient_details").addClass("none_selected").addClass("bg-light").addClass("rounded");
        var tpl = $("<h1>No Patient Selected</h1>");
    }
    $("#patient_details").append(tpl);
    $(".recommendation").hide();
}
