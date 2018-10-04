<?
/*  Copyright 2010-2014  John Kennard

		This program is free software; you can redistribute it and/or modify
		it under the terms of the GNU General Public License, version 2, as
		published by the Free Software Foundation.

		This program is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
		GNU General Public License for more details.

		You should have received a copy of the GNU General Public License
		along with this program; if not, write to the Free Software
		Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*
Plugin Name: Choir Roster
Plugin URI:
Description: Choir roster and event attendance list. You can add it to any post or page.
Author: RacingHippo
Version: 1.2
Author URI:
License: GPLv2
*/

if(file_exists(ABSPATH . "wp-content/plugins/choir-roster/lang.php")) {
		include (ABSPATH . "wp-content/plugins/choir-roster/lang.php");
} else {
		echo "Choir Roster error: language file not found.";
}

if (!function_exists('add_action')) {
		if (file_exists(ABSPATH.'/wp-load.php')) {
				require_once(ABSPATH.'/wp-load.php');
		} else {
				require_once(ABSPATH.'/wp-config.php');
		}
}

$create_table = "CREATE TABLE IF NOT EXISTS `".$table_prefix."choir_roster` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`post` int(10) unsigned NOT NULL,
	`user` int(10) unsigned NOT NULL,
	`response` enum('Yes','No','Maybe') NOT NULL,
	`setBy` int(10) unsigned NOT NULL,
	`dateSet` int(10) unsigned NOT NULL,
	`attendance` enum('Present','Absent','Late') NULL,
	`reportedBy` int(10) unsigned NULL,
	`dateReported` int(10) unsigned NULL,
	PRIMARY KEY (`id`),
	KEY `post` (`post`,`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";

// todo: create rehearsal tables

If (file_exists(ABSPATH . 'wp-admin/includes/upgrade.php')) {
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
}
If (!dbDelta($create_table)) {
	echo "Choir Roster error: db query failed";
}
/****************************************
**********    shortcodes   **************
*****************************************/
add_shortcode('choirRoster', 'cr_EventAttendanceResponder');
add_shortcode('choirMemberList', 'cr_memberList');
add_shortcode('choirRehearsalResponder', 'cr_rehearsalResponder');
add_shortcode('choirRehearsalList', 'cr_rehearsalList');
add_shortcode('choirRehearsalSummary', 'cr_rehearsalSummary');


require_once(ABSPATH . "wp-content/plugins/choir-roster/functions.php");

wp_enqueue_script("jquery");
add_action("wp_head", "cr_AddCss");

/*****************************
Event attendance functions
******************************/
function cr_EventAttendanceResponder() {
	global $wpdb, $current_user, $cr_lang, $post;

	$return = '<div id="cr_table_cont_'.$post->ID.'" class="cr_table_cont">';

	if($current_user->ID > 0) {
		$return.=	'<table id="cr_head_'.$post->ID.'"><tr><td class="cr_head"><strong>'.$cr_lang['question'].'</strong> </td><td class="cr_head">' .
				'<a href="#" id="cr_responseY_'.$post->ID.'" title="Yes" class="cr_btn cr_btn_'.$post->ID.'">'.$cr_lang['Yes'].'</a>&nbsp;' .
				'<a href="#" id="cr_responseN_'.$post->ID.'" title="No" class="cr_btn cr_btn_'.$post->ID.'">'.$cr_lang['No'].'</a>&nbsp;' .
				'<a href="#" id="cr_responseM_'.$post->ID.'" title="Maybe" class="cr_btn cr_btn_'.$post->ID.'">'.$cr_lang['Maybe'].'</a>&nbsp;&nbsp;&nbsp;' .
				'<span id="cr_state_'.$post->ID.'" class="cr_state"></span></td></tr></table>';
	} else {
		$return.='<table><tr><td>'.$cr_lang['login'].'</td></tr></table>';
	}

	$return.='<div id="cr_cont_'.$post->ID.'">'.cr_DrawList().'</div></div>';

	$return .= "<!-- Choir Roster Script start --><script language='javascript'>

	jQuery(document).ready(function(){
		jQuery('.cr_btn_".$post->ID."').click(function() {
			jQuery('#cr_state_".$post->ID."').html('<img src=\"".get_bloginfo('wpurl') ."/wp-content/plugins/choir-roster/img/ajax-loader.gif\" />');
			param=jQuery(this).attr('title');
			jQuery.post('".get_bloginfo('wpurl') ."/wp-content/plugins/choir-roster/response.php', { cr_response: param, cr_postid:".$post->ID." },
										function(data){
											if(data) {
												jQuery('#cr_cont_".$post->ID."').html(data);
												jQuery('#cr_state_".$post->ID."').html('');
											}
										},
										'html');
			return false;
		});
	});

	function updateResponse(element) {
		uid = jQuery(element).attr('title');
		jQuery('#cr_state_".$post->ID."').html('<img src=\"".get_bloginfo('wpurl') ."/wp-content/plugins/choir-roster/img/ajax-loader.gif\" />');
		response = jQuery(element).val();
		jQuery.post('".get_bloginfo('wpurl') ."/wp-content/plugins/choir-roster/response.php', { cr_response: response, cr_uid: uid, cr_postid:".$post->ID." },
			function(data){
				if(data) {
					jQuery('#cr_cont_".$post->ID."').html(data);
					jQuery('#cr_state_".$post->ID."').html('');
				}
			},
			'html');
		return false;
	}
	</script><!-- Choir Roster Script end -->";

	return $return;
}

/************************************************/
function cr_eventList() {
	global $wpdb, $current_user, $cr_lang;

	$return = '<div id="cr_table_cont_'.$post->ID.'" class="cr_table_cont">';
  $eventID=1;
	if($current_user->ID > 0) {
		$return.=	'<table id="cr_head_'.$eventID.'"><tr><td class="cr_head"><strong>'.$cr_lang['question'].'</strong> </td><td class="cr_head">' .
				'<a href="#" eventID="'. $eventID .'" id="cr_responseY_'.$eventID.'" title="Yes" class="cr_btn cr_btn_'.$eventID.'">'.$cr_lang['Yes'].'</a>&nbsp;' .
				'<a href="#" eventID="'. $eventID .'" id="cr_responseN_'.$eventID.'" title="No" class="cr_btn cr_btn_'.$eventID.'">'.$cr_lang['No'].'</a>&nbsp;' .
				'<a href="#" eventID="'. $eventID .'" id="cr_responseM_'.$eventID.'" title="Maybe" class="cr_btn cr_btn_'.$eventID.'">'.$cr_lang['Maybe'].'</a>&nbsp;&nbsp;&nbsp;' .
				'<span id="cr_state_'.$eventID.'" class="cr_state"></span></td></tr></table>';
	} else {
		$return.='<table><tr><td>'.$cr_lang['login'].'</td></tr></table>';
	}

	$return .= "<script language='javascript'>
	jQuery(document).ready(function(){
		jQuery('.cr_btn').click(function() {
		  eventID=jQuery(this).attr('eventID');
		  alert(eventID);
			//jQuery('#cr_state_".$post->ID."').html('<img src=\"".get_bloginfo('wpurl') ."/wp-content/plugins/choir-roster/img/ajax-loader.gif\" />');
			//param=jQuery(this).attr('title');
			//jQuery.post('".get_bloginfo('wpurl') ."/wp-content/plugins/choir-roster/response.php', { cr_response: param, cr_postid:".$post->ID." },
			//							function(data){
			//								if(data) {
			//									jQuery('#cr_cont_"  . $post->ID."').html(data);
			//									jQuery('#cr_state_" . $post->ID."').html('');
			//								}
			//							},
			//							'html');
			return false;
		});
	})
	</script>";

	return $return;
}


/**********************
rehearsal functions
************************/
function cr_rehearsalResponder($atts) {
    global $wpdb, $current_user, $cr_lang, $post;
    $params = shortcode_atts( array(
        'year' => '2010',
        'term' => '0',
    ), $atts );

    $return = '<div id="cr_table_cont_'.$post->ID.'" class="cr_table_cont">';

    $return.='<div id="cr_cont_'.$post->ID.'">'.cr_DrawRehearsalList($params['year'],$params['term']).'</div></div>';

    $return .= "<script language='javascript'>
			function updateRehearsalResponse(element) {
				rid = jQuery(element).attr('title');
				uid = jQuery(element).attr('id');
				response = jQuery(element).prop('checked');
				jQuery.post('".get_bloginfo('wpurl') ."/wp-content/plugins/choir-roster/rehearsalResponse.php', { cr_response: response, cr_uid: uid, cr_reheasalid: rid });
				return false;
			}
			</script>";

    return $return;
}


function cr_rehearsalList($atts) {
    global $wpdb, $current_user, $cr_lang, $post;
		$params = shortcode_atts( array(
        'year' => '2010',
        'term' => '0',
    ), $atts );


    $return = '<div id="cr_table_cont_'.$post->ID.'" class="cr_table_cont">';

    $return.='<div id="cr_cont_'.$post->ID.'">'.cr_DrawRehearsalGrid($params['year'],$params['term']).'</div></div>';

    $return .= "<script language='javascript'>
			function updateRehearsalResponse(element) {
				rid = jQuery(element).attr('title');
				uid = jQuery(element).attr('id');
				response = jQuery(element).prop('checked');
				jQuery.post('".get_bloginfo('wpurl') ."/wp-content/plugins/choir-roster/rehearsalResponse.php', { cr_response: response, cr_uid: uid, cr_reheasalid: rid });
				return false;
			}
			</script>";

    return $return;
}

function cr_rehearsalSummary($atts) {
    global $wpdb, $current_user, $cr_lang, $post;
		$params = shortcode_atts( array(
        'year' => '2010',
        'term' => '0',
    ), $atts );

    $return = '<div id="cr_table_cont_'.$post->ID.'" class="cr_table_cont">';

    $return.='<div id="cr_cont_'.$post->ID.'">'.cr_DrawRehearsalSummary($params['year'],$params['term']).'</div></div>';

    $return .= "<script language='javascript'>
			function updateRehearsalResponse(element) {
				rid = jQuery(element).attr('title');
				uid = jQuery(element).attr('id');
				response = jQuery(element).prop('checked');
				jQuery.post('".get_bloginfo('wpurl') ."/wp-content/plugins/choir-roster/rehearsalResponse.php', { cr_response: response, cr_uid: uid, cr_reheasalid: rid });
				return false;
			}
			</script>";

    return $return;
}

/**************************************************/
function cr_memberList() {
	global $wpdb, $current_user, $cr_lang, $post;

	$return = '<div id="cr_table_cont_'.$post->ID.'" class="cr_table_cont">';

	$return.='<div id="cr_cont_'.$post->ID.'">'.cr_simpleList().'</div></div>';

	return $return;
}



function addHeadStuff() {
?>
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css" integrity="sha384-mzrmE5qonljUremFsqc01SB46JvROS7bZs3IO2EmfFsd15uHvIt+Y8vEf7N7fWAU" crossorigin="anonymous">
<?php
}
add_action('wp_head', 'addHeadStuff');

?>
