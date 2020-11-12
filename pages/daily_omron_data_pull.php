<?php
namespace Stanford\HTNapi;
/** @var \Stanford\HTNapi\HTNapi $module */

$startTS = microtime(true);

$module->dailyOmronDataPull();

$module->emLog("dailyOmronDataPull() page time : " . microtime(true) - $startTS );