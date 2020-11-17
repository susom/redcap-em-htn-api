<?php
session_start();

if(empty($_SESSION["logged_in_user"])){
    header("location:". $module->geturl("pages/login.php", true, true));
    exit;
}
