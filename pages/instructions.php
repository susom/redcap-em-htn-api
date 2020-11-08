<?php
namespace Stanford\HTNapi;
/** @var \Stanford\HTNapi\HTNapi $module */

require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
?>

<h3>POSTBACK URL for OMRON OAUTH 2.0</h3>
<p><i>*TODO: This should be dynamic, but Omron requires urls to be whitelisted so currently tied to Prod Project : <b>21382</b></i></p>
<pre style="width:80%;">
<?= $module->getUrl("pages/redirect.php",true, true) ?>
</pre>