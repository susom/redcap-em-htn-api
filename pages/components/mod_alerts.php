<div id="alerts" class="container mt-0">
    <div class="row">
        <h1 class="mt-0 mb-2 mr-3 ml-3 d-inline-block align-middle">Alerts <span>(<?=count($alerts)?>)</span></h1>
    </div>

    <div class="row">
        <table class="table rounded bg-light mb-0">
        <caption><a href="#" class="text-infolink mr-4 delete_alerts">Delete</a> <a href="#" class="text-infolink select_all_alerts">Select All</a></caption>
        <tbody class="alerts_tbody">
        <?php


            foreach($alerts as $ts => $time_alerts){
                foreach($time_alerts as $alert){
                    //$tr_class = "read";
                    echo '<tr class="$tr_class">
                            <td data-label="select"><input name="delete_alert" data-recordid="'.$alert["record_id"].'" type="checkbox"></td>
                            <td data-label="subject"><a href="#" class="text-infolink">RE: Patient '.$alert["patient_name"].'</a></td>
                            <td data-label="message"><a href="#" class="text-infolink">'.$alert["subject"].'</a></td>
                            <td data-label="date"><a href="#" class="text-infolink">'.$alert["date"].'</a></td>
                        </tr>';
                }
            }
        ?>
        </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function(){
    $(".delete_alerts").click(function(){

    });

    $(".select_all_alerts").click(function(){
        $(".alerts_tbody input").attr("checked",true);
        return false;
    });
});
</script>