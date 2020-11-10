<style>
.stat::after{
    content:"";
    background:url(<?php echo $module->getUrl('assets/images/icon_info.gif') ?>) 0 0 no-repeat;
    background-size:contain;
    width: 20px;
    height: 20px;
    display: block;
    margin: 0 auto;
    cursor:pointer;
}
</style>
<div id="overview" class="container mt-5 mb-0">
    <div class="row header">
        <h1 class="mt-5 mb-3 mr-3 ml-3 d-inline-block align-middle">Overview</h1>
        <aside class="float-right mt-5">
            <a href="<?=$module->getUrl("pages/add_patient.php")?>" class="add_patient d-inline-block mr-3 mt-2">+ Add Patient</a> <form class="d-inline-block  mt-2"><input type="text" class="search rounded" placeholder="Search Patients"/></form>
        </aside>
    </div>

    <div class="row">
        
        <div class="stat-group text-center alerts d-inline-block">
            <a href="#" class="stat d-inline-block">
                <p class='h3 mt-4 mb-0 p-0'>Alerts</p>
                <i class="d-block"><?=count($alerts)?></i>
            </a>
        </div>

        <div class="stat-group text-center mr-3 rx_change d-inline-block">
            <a href="#" data-filter="rx_change" data-idx="<?=json_encode($rx_change)?>" class="stat d-inline-block">
                <p class='h1 mt-4 mb-0 p-0'><?=count($rx_change)?></p>
                <i class="d-block">Patients</i>
            </a>

            <div class="stat-body mt-3 ">
            <b class="stat-title d-block">Prescription Change Needed</b>
            <i class="stat-text d-block"></i>
            </div>
        </div>

        <div class="stat-group text-center ml-3 results_needed d-inline-block">
            <a href="#" data-filter="results_needed" data-idx="<?=json_encode($results_needed)?>" class="stat d-inline-block">
                <p class='h1 mt-4 mb-0 p-0'><?=count($results_needed)?></p>
                <i class="d-block">Patients</i>
            </a>

            <div class="stat-body mt-3">
            <b class="stat-title d-block">Lab Results Needed</b>
            <i class="stat-text d-block"></i>
            </div>
        </div>

        <div class="stat-group text-center mr-3 data_needed d-inline-block">
            <a href="#" data-filter="data_needed" data-idx="<?=json_encode($data_needed)?>" class="stat d-inline-block">
                <p class='h1 mt-4 mb-0 p-0'><?=count($data_needed)?></p>
                <i class="d-block">Patients</i>
            </a>

            <div class="stat-body mt-3">
            <b class="stat-title d-block">Data Needed</b>
            <i class="stat-text d-block"></i>
            </div>
        </div>

        <div class="stat-group text-center ml-3 all_patients d-inline-block">
            <a href="#" data-filter="all_patients"  class="stat d-inline-block">
                <p class='h1 mt-4 mb-0 p-0'><?=count($all_patients)?></p>
                <i class="d-block">Patients</i>
            </a>

            <div class="stat-body mt-3">
            <b class="stat-title d-block">All Patients</b>
            <i class="stat-text d-block"></i>
            </div>
        </div>

    </div>
</div>