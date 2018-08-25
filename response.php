<?php
require_once("../../../wp-config.php");
if(file_exists(ABSPATH . "wp-content/plugins/choir-roster/lang.php")) {
	include (ABSPATH . "wp-content/plugins/choir-roster/lang.php");
} else {
	echo "Choir Roster error: language file not found.";
}
require_once(ABSPATH . "wp-content/plugins/choir-roster/functions.php");
header('Content-Type: text/html; charset='.get_option('blog_charset').'');


echo cr_AjaxResponse($_POST);
?>