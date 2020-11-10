<?php
namespace Stanford\HTNtree;
/** @var \Stanford\HTNtree\HTNtree $module */

if (isset($_SESSION['logged_in_user'])) { 
    //if more session-vars that are needed for login
    $_SESSION['logged_in_user'] = $_SESSION['logged_in_user'];
    $module->emDebug("haahah", $_SESSION['logged_in_user'], $_POST);
}else{
    $module->emDebug("booohoo", $_POST);
}