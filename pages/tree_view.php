<?php
namespace Stanford\HTNtree;
/** @var \Stanford\HTNtree\HTNtree $module */
?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
<?php include("components/gl_meta.php") ?>
<link href="<?php echo $module->getUrl('assets/styles/treeview.css') ?>" rel="stylesheet">
<style>
    b.drug {
        padding-left:23px;
        background:url(<?= $module->getUrl('assets/images/icon_drug.png') ?>) 0 0 no-repeat;
        background-size:18px 18px;
    }
    .step_drugs li.increase{
        background:url(<?= $module->getUrl('assets/images/icon_arrow_up.png') ?>) 0 4px no-repeat;
        background-size:18px 18px;
    }
    .step_drugs li.decrease{
        background:url(<?= $module->getUrl('assets/images/icon_arrow_down.png') ?>) 0 4px no-repeat;
        background-size:18px 18px;
    }

    .step_drugs li.add{
        background:url(<?= $module->getUrl('assets/images/icon_add_plus.png') ?>) 0 4px no-repeat;
        background-size:18px 18px;
    }

</style>
<!-- Custom styles for this template -->
</head>
<body class="d-flex flex-column h-100">
    <?php include("components/gl_topnav.php") ?>

    <!-- Begin page content -->
    <main role="main" class="flex-shrink-0">
        <div class="container mt-5">
            <div class="row">
                <h1 class="mt-5 mb-4 mr-3 ml-3 d-inline-block align-middle">Tree View - Template 1</h1>
            </div>

            <div id="prescription_tree" class="content bg-light mh-10 mx-1">
                <div id="viewer">
                </div>
            </div>
        </div>
    </main>

    <?php include("components/gl_foot.php"); ?>
    <script src="<?php echo $module->getUrl('assets/scripts/template.js') ?>" crossorigin="anonymous"></script>
    <script src="<?php echo $module->getUrl('assets/scripts/treeLogic.js') ?>" crossorigin="anonymous"></script>
    <script src="<?php echo $module->getUrl('assets/scripts/treeLogicStep.js') ?>" crossorigin="anonymous"></script>
</body>
</html>
<script>
$(document).ready(function(){
    // BUILD TREE SHOULD HAVE EVERY PERMUTATION + NEXT STEP + SIDE-EFFECT BRANCHES
    var raw_json   = <?= json_encode($module->treeLogic()) ;?>;
    var tree        = new treeLogic(raw_json);
    tree.startAttachTree();
});
</script>