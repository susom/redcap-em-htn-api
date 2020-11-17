<?php
namespace Stanford\HTNapi;
/** @var \Stanford\HTNapi\HTNapi $module */

$startTS = microtime(true);

$module->refreshOmronAccessTokens();

$module->emLog("refreshOmronAccessTokens() page time : " . microtime(true) - $startTS );