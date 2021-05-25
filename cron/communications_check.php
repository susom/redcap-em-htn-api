<?php
namespace Stanford\HTNapi;
/** @var \Stanford\HTNapi\HTNapi $module */

$startTS = microtime(true);

$module->communicationsCheck();

$module->emLog("communicationsCheck() page time : " . microtime(true) - $startTS );