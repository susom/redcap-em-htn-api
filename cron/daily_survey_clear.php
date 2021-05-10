<?php
namespace Stanford\HTNapi;
/** @var \Stanford\HTNapi\HTNapi $module */

$startTS = microtime(true);

$module->dailySurveyClear();

$module->emLog("dailySurveyClear() page time : " . microtime(true) - $startTS );