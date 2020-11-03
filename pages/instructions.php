<?php
namespace Stanford\HTNapi;
/** @var \Stanford\HTNapi\HTNapi $module */

require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';


echo "<p>No Auth Return URL</p>";
echo $module->getUrl("pages/redirect.php",true, true);
?>