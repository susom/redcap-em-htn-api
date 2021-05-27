<?php
namespace Stanford\HTNtree;
/** @var \Stanford\HTNtree\HTNtree $module */
session_start();


$show_pw_fields = false;
$action         = isset($_POST["action"]) ? filter_var($_POST["action"], FILTER_SANITIZE_STRING) : null;
if(!isset($action) && isset($_GET["verify"]) ){
    $action = "verify";
}
switch($action){
    case "reset_password":
        $login_email    = strtolower(trim(filter_var($_POST["login_email"], FILTER_SANITIZE_STRING)));
        $result         = $module->sendResetPassword($login_email);
        if(!empty($result)){
            $_SESSION["buffer_alert"] = array("errors" => null , "success" => "Check your email for a reset password link.");  
            header("Location: " . $module->getUrl("pages/dashboard.php", true, true));
            exit;
        }else{
            $_SESSION["buffer_alert"] = array("errors" => "Email not found." , "success" => "");  ;
        }
    break;

    case "update_password":
        $record_id      = strtolower(trim(filter_var($_POST["record_id"], FILTER_SANITIZE_NUMBER_INT)));
        $provider_email = trim(filter_var($_POST["provider_email"], FILTER_VALIDATE_EMAIL)) ;
        $pw_1           = strtolower(trim(filter_var($_POST["provider_pw"], FILTER_SANITIZE_STRING)));
        $pw_2           = strtolower(trim(filter_var($_POST["provider_pw2"], FILTER_SANITIZE_STRING)));
        
        if(!empty($pw_1) && $pw_1 == $pw_2){
            $result     = $module->updateProviderPassword($record_id, $provider_email, $pw_1);
            if(!empty($result)){
                header("Location: " . $module->getUrl("pages/dashboard.php", true, true));
                exit;
            }
        }else{
            $_SESSION["buffer_alert"] = array("errors" => "Passwords do not match" , "success" => "");
            $show_pw_fields = true;
        }
    break;

    case "verify":
        $verification_token = !empty($_GET["verify"]) ? filter_var($_GET["verify"], FILTER_SANITIZE_STRING) : null;
        $verification_email = !empty($_GET["email"]) ? filter_var($_GET["email"], FILTER_VALIDATE_EMAIL) : null;

        $verify_account     = $module->verifyAccount($verification_email,$verification_token);
        if(array_key_exists("errors",$verify_account) && empty($verify_account["errors"])){
            $_SESSION["buffer_alert"] = array("errors" => null , "success" => "Account found, reset password below.");
            $show_pw_fields = true;
            $record_id      = $verify_account["provider"]["record_id"];
            $provider_email = $verify_account["provider"]["provider_email"];
        }
    break;
}

//FOR PASSING ALERTS AROUND
if(isset($_SESSION["buffer_alert"])){
    $errors = $_SESSION["buffer_alert"];
    unset($_SESSION["buffer_alert"]);
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
                    $greenred   = !empty($errors["errors"]) ? "danger" : "success";
                    $msg        = !empty($errors["errors"]) ? $errors["errors"] : $errors["success"];
                    echo "<div class='offset-sm-3 col-sm-6 alert alert-".$greenred."'>".$msg."</div>";
                }
            ?>

            <div class="row">
                <div class="col-md-6 offset-md-3 bg-light border rounded mt-5">
                    <h1 class="mt-3 mb-3 mr-3 ml-3 d-inline-block align-middle">Reset Password</h1>
                    
                    <form method="POST" class="mr-3 ml-3">
                        <?php if($show_pw_fields) { ?>
                            <input type="hidden" name="action" value="update_password"/>
                            <input type="hidden" name="record_id" value="<?=$record_id?>"/>
                            <input type="hidden" name="provider_email" value="<?=$provider_email?>"/>
                            <div class="form-group">
                                <label for="login_pw">New Password</label>
                                <input type="password" class="form-control" id="provider_pw" name="provider_pw" placeholder="new password">
                            </div>
                            <div class="form-group">
                                <label for="login_pw">Confirm Password</label>
                                <input type="password" class="form-control" id="provider_pw2" name="provider_pw2" placeholder="new password again">
                            </div>
                        <?php } else { ?>
                            <input type="hidden" name="action" value="reset_password"/>
                            <div class="form-group">
                                <label for="login_email">Email address</label>
                                <input type="email" class="form-control" id="login_email" name="login_email" aria-describedby="emailHelp" placeholder="johndoe@stanford.edu">
                            </div>
                        <?php } ?>
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