<?php
namespace Stanford\HTNapi;
/** @var \Stanford\HTNapi\HTNapi $module */

$startTS = microtime(true);

$module->dailySurveyCheck();

$module->emLog("dailySurveyCheck() page time : " . microtime(true) - $startTS );