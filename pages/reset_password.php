<?php
namespace Stanford\HTNtree;
/** @var \Stanford\HTNtree\HTNtree $module */

$verification_token = !empty($_GET["verify"]) ? filter_var($_GET["verify"], FILTER_SANITIZE_STRING) : null;
if($verification_token){
    $action = $_POST["action"];

    switch($action){
        case "login_provider":
            $login_email    = strtolower(trim(filter_var($_POST["login_email"], FILTER_SANITIZE_STRING)));
            $login_pw       = strtolower(trim(filter_var($_POST["login_pw"], FILTER_SANITIZE_STRING)));  
            $verify         = $module->loginProvider($login_email, $login_pw);
            if($verify){
                header("Location: " . $module->getUrl("pages/dashboard.php", true, true));
                exit;
            }else{
                $module->emDebug("not verified!");
                $errors[]   = "Email / Password combination not found";
            }   
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <?php include("components/gl_meta.php") ?>
    <!-- Custom styles for this template -->
</head>
<?php
$page = "login_reg";
?>
<body class="d-flex flex-column h-100">
    <?php include("components/gl_topnav.php") ?>

    
    <!-- Begin page content -->
    <main role="main" class="flex-shrink-0">
        <div id="login" class="container mt-5">
            <?php
                if(!empty($errors)){
                    echo '<div class="mt-4 row">
                            <div class="col-md-6 offset-md-3 alert alert-danger">'.$errors[0].'</div>
                        </div>';
                }
            ?>

            <div class="row">
                <div class="col-md-6 offset-md-3 bg-light border rounded mt-5">
                    <h1 class="mt-3 mb-3 mr-3 ml-3 d-inline-block align-middle">Reset Password</h1>
                    
                    <form method="POST" class="mr-3 ml-3">
                        <input type="hidden" name="action" value="login_provider"/>
                        <div class="form-group">
                            <label for="login_email">Email address</label>
                            <input type="email" class="form-control" id="login_email" name="login_email" aria-describedby="emailHelp" placeholder="johndoe@stanford.edu">
                        </div>
                        <div class="form-group">
                            <label for="login_pw">New Password</label>
                            <input type="password" class="form-control" id="login_pw" name="login_pw" placeholder="secret password">
                        </div>
                        <div class="form-group">
                            <label for="login_pw">Confirm Password</label>
                            <input type="password" class="form-control" id="login_pw" name="login_pw" placeholder="secret password">
                        </div>
                        <div class="btns pt-4 pb-4">
                            <button type="submit" id="login_go" class="btn btn-primary btn-block">Reset Password</button>
                        </div>
                        <div class="more_links pb-3">
                            <a class="help_link" href="<?=$module->getUrl("pages/help.php", true, true)?>">Need Help?</a>
                        </div>
                    </form>

                </div>

            </div>
        </div>
    </main>

    <?php include("components/gl_foot.php"); ?>
</body>
</html>
<script>
$(document).ready(function(){
    var new_tree        = {};
    var patient_list    = {};
    var treatment_trees = {};
    
    if(typeof(Storage) !== "undefined"){
        if(localStorage.getItem("patient_list")){
            patient_list    = JSON.parse(localStorage.getItem("patient_list"));

            $("#patient_list").empty();
            for(var patient_name in patient_list){
                var newa    = $("<a>").attr("href","#").text(patient_name);
                var newli   = $("<li>").append(newa);
                $("#patient_list").append(newli);
            }
        }

        if(localStorage.getItem("treatment_trees")){
            treatment_trees = JSON.parse(localStorage.getItem("treatment_trees"));

            $("#view_tree option:not(:first-child),#patient_tree option:not(:first-child)").remove();
            for(var treatment_name in treatment_trees){
                var newopt  = $("<option>").val(treatment_name).text(treatment_name);
                $("#view_tree").append(newopt);
                $("#patient_tree").append(newopt.clone());
            }
        }
        
        // localStorage.setItem("key",JSON.stringify(OB));
        // localStorage.removeItem("key");
        // localStorage.clear();
    }else{
        alert("no local storage support");
    }

    $("#clear_storage").click(function(){
        $("#clear_patient").click();
        $("#patient_list").empty().append($("<li>No saved patients.</li>"));
        newTree();
        patient_list = {};
        treatment_trees = {};
        localStorage.clear();
        return;
    });
});
</script>