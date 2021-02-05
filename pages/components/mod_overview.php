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
    #filters{
        display:flex;
        justify-content:space-between;
    }
</style>
<div id="overview" class="container mt-5 mb-0">
    <div class="row header">
        <h1 class="mt-5 mb-3 mr-3 ml-3 d-inline-block align-middle">Overview</h1>
        <aside class="float-right mt-5">
            <a href="<?=$module->getUrl("pages/add_patient.php", true, true)?>" class="add_patient d-inline-block mr-3 mt-2">+ Add Patient</a> 
            <!-- <form class="d-inline-block  mt-2"><input type="text" class="search rounded" placeholder="Search Patients"/></form> -->
        </aside>
    </div>

    <div id="filters">
    </div>
</div>