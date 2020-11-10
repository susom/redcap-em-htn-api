<footer class="footer mt-5 py-3 text-center">
    <div class="container">
        <span class="text-muted">© Stanford University 2020</span>
    </div>
</footer>

<script>
$(document).ready(function(){
    //ON PAGE LOAD BUILD ALL NAV
    var all_patients = <?=json_encode($all_patients)?>;
    window.filter_txn   = {"rx_change" : "Prescription Change Needed", "results_needed" : "Lab Results Needed" , "data_needed" : "Data Needed"};

    window.filter_nav   = [];
    buildNav(all_patients, window.filter_nav);

    $("#overview .alerts .stat").click(function(){
        if($("#alerts").is(":visible")){
            $("#alerts").slideUp();
        }else{
            $("#alerts").slideDown();
        }
        return false;
    });
    $("#overview h1").click(function(){
        if($(this).parent().next().hasClass("hide") ){
            $(this).parent().next().removeClass("hide");
        }else{
            $(this).parent().next().addClass("hide");
        }
        return false;
    });

    $("#overview .stat-group:not(.alerts) .stat").click(function(){
        var filter  = $(this).data("filter");
        var idx     = $(this).data("idx");
        if($(this).parent().hasClass("picked") ){
            $(this).parent().removeClass("picked");
            removeA(window.filter_nav, filter);
        }else{
            $(this).parent().addClass("picked");
            window.filter_nav.push(filter);
        }
        
                    
        
        buildNav(all_patients, window.filter_nav);
        return false;
    });

    setInterval(function(){
        console.log("refresh session");
        $.post('<?=$module->getURL("pages/refresh_session.php");?>');
    },600000); //refreshes the session every 10 minutes
})

function buildNav(patients, filternav){
    var html_tpl = `<dl class="patient_tab rounded bg-light">
                <dt class='d-inline-block pt-2 pb-2 pl-2 text-center'><img class="rounded-circle"/></dt>
                <dd class='d-inline-block align-middle'>
                    <b></b>
                    <i class="d-block"></i>
                </dd>
                </dl>`;

    $(".patient_body .patient_list").empty();
    $("#patients .filter").empty();
    for(var i in filternav){
        var filter = filternav[i];
        var filter_tpl = `<b class="uncontrolled_above filtered d-inline-block align-middle pl-3 mr-3"><span></span> <i class='d-block'><br></i></b>`;
        var new_filter = $(filter_tpl);
        new_filter.find('span').text(window.filter_txn[filter]);
        $("#patients .filter").append(new_filter);
    }

    for(var i in patients){
        var patient = patients[i];
        var filter  = patient["filter"];

        if(filternav.length && !filternav.includes(filter)){
            continue;
        }

        var newnav = $(html_tpl);
        newnav.data("record_id",patient["record_id"]);
        newnav.addClass(patient["filter"]);
        newnav.find("b").text(patient["patient_name"]);
        newnav.find("i").text(patient["age"] + ", " + patient["sex"]);
        newnav.find("img").attr("src", patient["patient_photo"]);
        
        
        newnav.data("patient_record_id", patient["record_id"]);

        newnav.click(function(e){
            e.preventDefault();
            var patient_detail="<?=$module->getUrl("pages/patient_detail.php")?>" + "&record_id=" + $(this).data("patient_record_id");
            console.log("why", patient_detail);
            
            location.href = patient_detail;
        });

        $(".patient_body .patient_list").append(newnav);
    }

    if(!patients.length){
        var newnav = $("<p>").text("No Patients in Database");
        $(".patient_body .patient_list").append(newnav);
    }
}
function removeA(arr) {
    var what, a = arguments, L = a.length, ax;
    while (L > 1 && arr.length) {
        what = a[--L];
        while ((ax= arr.indexOf(what)) !== -1) {
            arr.splice(ax, 1);
        }
    }
    return arr;
}
function clear_elements(element, exception_classes) {
  $(".hide").removeClass("hide");
  $(".delete_this").remove();

  element.find(':input').each(function() {
    for(var i in exception_classes){
        if($(this).attr("class") == exception_classes[i]){
            return;
        }
    }
    
    switch(this.type) {
        case 'password':
        case 'text':
        case 'textarea':
        case 'file':
        case 'select-one':
        case 'select-multiple':
        case 'date':
        case 'number':
        case 'tel':
        case 'email':
            $(this).val('');
            break;
        case 'checkbox':
        case 'radio':
            this.checked = false;
            break;
    }
  });
}
</script>