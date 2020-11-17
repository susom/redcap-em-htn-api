<style>
    #alerts_tbody .subject { width:700px; }
</style>
<div id="alerts" class="container mt-0">
    <div class="row">
        <h1 class="mt-0 mb-2 mr-3 ml-3 d-inline-block align-middle">Alerts</h1>
    </div>

    <div class="row">
        <table class="table rounded bg-light mb-0">
        <thead>
            <tr>
                <th></th>
                <th>Patient</th>
                <th>Alert</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody id="alerts_tbody">
            
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4"><a href="#" class="text-infolink mr-4 remove_alerts">Remove</a> <a href="#" class="text-infolink select_all_alerts">Select All</a></td>
            </tr>
        </tfoot>
        </table>
    </div>
</div>
<script>
$(document).ready(function(){
    $("#alerts").on("click", ".remove_alerts" ,function(e){
        e.preventDefault();

        var deletes = [];
        $("#alerts_tbody input:checked").each(function(){
            deletes.push({"record_id":$(this).data("record_id"), "instance" :$(this).data("redcap_repeat_instance")});
        });

        $.ajax({
            url: '<?=$module->getURL("pages/ajax_handler.php");?>',
            method: 'POST',
            data: {
                    "action"    : "markAlertRead",
                    "msgs"      : deletes
            },
            dataType: 'json'
        }).done(function (result) {
            if(!result.errors.length){
                var current_alerts_count = $("#filters .alerts .stat i").text();
                $("#filters .alerts .stat i").text(current_alerts_count - $("#alerts_tbody input:checked").length) ; 

                $("#alerts_tbody input:checked").each(function(){
                    $(this).parents("tr").slideUp("slow", function(){
                        $(this).remove();
                    });
                });
            }else{
                alert("error, could not mark as read");
            }
        }).fail(function () {
            console.log("failed to mark as red");
        });
    });

    $("#alerts").on("click", ".select_all_alerts", function(){
        if($("#alerts_tbody input:checked").length){
            $("#alerts_tbody input").attr("checked",false);
        }else{
            $("#alerts_tbody input").attr("checked",true);
        }
        return false;
    });
});
</script>