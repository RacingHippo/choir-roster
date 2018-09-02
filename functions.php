<?
/** FUNCTIONS **/
function cr_UserData($id) {
	//todo: get meta for vocal part
	global $wpdb, $table_prefix;
	$name = $wpdb->get_var("SELECT display_name FROM ".$wpdb->users." WHERE ID='" . intval($id) . "' LIMIT 1");
	if ($name!="") return $name;
	else return false;
}

function cr_GetList($id, $onlyActive=false) {
	global $wpdb, $table_prefix;
	$list=array();

	$cond="";
	if($onlyActive) $cond=" AND response <> 'No'";

	$data = $wpdb->get_results("SELECT user, response FROM ".$table_prefix."choir_roster WHERE post = " .  intval($id) .$cond. " ORDER BY dateSet ASC");
	if(count($data)>0 && is_array($data)) {
		foreach($data as $r) {
			if(cr_UserData($r->user))
				$list[$r->user] = $r->response;
		}
		return $list;
	}
	return false;
}

function cr_GetRehearsalListResponses($year, $term, $userID) {
    global $wpdb, $table_prefix;
		$year=intval($year);
		$term=intval($term);
    $sql = "SELECT dates.rehearsalID, user, response, rehearsalDate, location FROM " . $table_prefix . "choir_rehearsalResponses res JOIN " . $table_prefix . "choir_rehearsalDates dates on dates.rehearsalID = res.rehearsalID  WHERE year = $year AND  term = $term AND user = $userID ORDER BY rehearsalDate ASC";
    $data = $wpdb->get_results($sql);
    if(count($data)>0 && is_array($data)) {
        return($data);
    } else {
			// can't find any responses for this user for this term, so let's insert the rows for them
			// but first, let's check that rehearsals exist for this year/term...
			$sql = "SELECT rehearsalID FROM " . $table_prefix . "choir_rehearsalDates dates WHERE year = $year AND  term = $term";
	    $data = $wpdb->get_results($sql);
			if(count($data)==0) {
				return false;
			}
			$query = "INSERT INTO " . $table_prefix . "choir_rehearsalResponses (rehearsalID, user, response) SELECT rehearsalID, $userID, 0 FROM " . $table_prefix . "choir_rehearsalDates WHERE year = $year AND  term = $term";
			$res=$wpdb->query($query);
			// now call this function again to display them
			if($res) return cr_GetRehearsalListResponses($year, $term, $userID);
		}
		return false;
}

function cr_GetRehearsalList($year, $term) {
    global $wpdb, $table_prefix;
		$year=intval($year);
		$term=intval($term);
    $sql = "SELECT dates.rehearsalID, user, response, rehearsalDate, location FROM " . $table_prefix . "choir_rehearsalResponses res LEFT JOIN " . $table_prefix . "choir_rehearsalDates dates on dates.rehearsalID = res.rehearsalID  WHERE year = $year AND  term = $term ORDER BY rehearsalDate ASC";
    $data = $wpdb->get_results($sql);
    if(count($data)>0 && is_array($data)) {
        return($data);
    } else {
			// can't find any responses for this user for this term, so let's insert the rows for them
			// but first, let's check that rehearsals exist for this year/term...
			$sql = "SELECT rehearsalID FROM " . $table_prefix . "choir_rehearsalDates dates WHERE year = $year AND  term = $term";
	    $data = $wpdb->get_results($sql);
			if(count($data)==0) {
				return false;
			}
			$query = "INSERT INTO " . $table_prefix . "choir_rehearsalResponses (rehearsalID, user, response) SELECT rehearsalID, $userID, 0 FROM " . $table_prefix . "choir_rehearsalDates WHERE year = $year AND  term = $term";
			$res=$wpdb->query($query);
			// now call this function again to display them
			if($res) return cr_GetRehearsalListResponses($year, $term, $userID);
		}
		return false;
}

// ajax handler to record the user's response to a rehearsal date.
function cr_RecordRehearsalResponse($userID, $rehearsalID, $response) {
	// todo why is this empty?
}

function cr_ResponseName($response) {
	global $cr_lang;
	if ($response=='') $response = 'None';
	return $cr_lang[$response];
}

function cr_AddResponse($post, $response, $userID=0) {
	global $wpdb, $current_user, $table_prefix;
	if ($userID==0) $userID = $current_user->ID;

	if ($userID > 0 && $post > 0 && $response) {
		$list = cr_GetList($post, false);
		//check, if user already on list
		if (empty($list[$userID])) {
			$query = sprintf("INSERT INTO `".$table_prefix."choir_roster` (`post`, `user`, `response`, `setBy`, `dateSet`) VALUES (%d, %d, '%s', %d, %d)",
												intval($post),
												intval($userID),
												$response,
												intval($userID),
												time()
												);
			$res=$wpdb->query($query);
		} else {
			$query = sprintf("UPDATE `" . $table_prefix . "choir_roster` SET `response`='%s', `dateSet`=%d, setBy=%d WHERE post=%d AND user=%d LIMIT 1",
												$response,
												time(),
												intval($userID),
												intval($post),
												intval($userID)
												);
			$res=$wpdb->query($query);
		}
		if($res) return true;
	}
	return false;
}

function cr_AddCss(){
	echo '<link rel="stylesheet" href="'.get_bloginfo('wpurl').'/wp-content/plugins/choir-roster/css/style.css" type="text/css" media="screen"  />';
}

function cr_AjaxResponse($vars) {
	global $wpdb, $current_user, $cr_lang;
	$userID = isset($vars['cr_uid']) ? $vars['cr_uid'] : $current_user->ID;

	$res=cr_AddResponse(intval($vars["cr_postid"]), $vars["cr_response"], $userID);
	return cr_DrawList(intval($vars["cr_postid"]), intval($current_user->ID));
}

function cr_AjaxRehearsalResponse($vars) {
	global $wpdb, $current_user, $cr_lang, $table_prefix;
	$userID = isset($vars['cr_uid']) ? $vars['cr_uid'] : $current_user->ID;
	$response = $vars["cr_response"]=="true" ? 1 : 0;
	$rehearsalID = $vars["cr_reheasalid"];

	if ($userID > 0 && $rehearsalID > 0 ) {
			$query = sprintf("REPLACE INTO `".$table_prefix."choir_rehearsalResponses` (`rehearsalID`, `user`, `response`, `setBy`) VALUES (%d, %d, %d, %d)",
												intval($rehearsalID),
												intval($userID),
												$response,
												intval($current_user->ID)
												);
		$res=$wpdb->query($query);
		if($res) return "Record updated successfully";
	}
	return "Update failed";

}

function cr_DrawList($id=0, $activeUserID=0) {
	global $post, $current_user, $cr_lang;
	if($id==0) $id=$post->ID;
	#echo "cr_drawlist id=$id activeUserID = $activeUserID";
		$responseList = cr_GetList($id);
		$draw = '';

		$arrUserVoices = array();
		$users = get_users() ;
		foreach ($users as $user) {
			$v = get_user_meta( $user->ID, 'Voice' );
			$voice = $v[0];
			$arrUserVoices[$user->ID] = $voice;
		}

		$arrVoices1 = array(
				'Soprano1' => '1st Soprano',
				'Soprano2' => '2nd Soprano',
				'Alto1' => '1st Alto',
				'Alto2' => '2nd Alto'
		);
		$arrVoices2 = array(
				'Tenor1' => '1st Tenor',
				'Tenor2' => '2nd Tenor',
				'Bass1' => '1st Bass',
				'Bass2' => '2nd Bass'
		);

		#$all_meta_for_user = get_user_meta( $uid );
		#$draw .= print_r( $all_meta_for_user, 1 );

	$draw.='<table class="cr_outerTable" id="cr_table_'.$post->ID.'"><tr><td colspan="2">';
	$draw.=$cr_lang['listheader'].'</td></tr>';

	$draw.='<tr><td><table class="cr_innerTable" id="cr_innerTableL_'.$post->ID.'">';
	foreach($arrVoices1 as $voiceHandle => $voiceName) {
		// *********************************************
		$draw.=drawSingers($arrUserVoices, $voiceHandle, $voiceName, $responseList, $activeUserID);
	}

	$draw.='</table></td><td><table class="cr_innerTable" id="cr_innerTableR_'.$post->ID.'">';

	foreach($arrVoices2 as $voiceHandle => $voiceName) {
		$draw.=drawSingers($arrUserVoices, $voiceHandle, $voiceName, $responseList, $activeUserID);
	}

	$draw.='</table></td></tr></table>';

	return $draw;
}

function drawSingers($arrUserVoices, $voiceHandle, $voiceName, $responseList, $activeUserID) {
    global $post;
	$draw='<tr class="voice_' . $voiceHandle. '"><td class="voiceHeader" colspan="2">' .$voiceName.'</td></tr>';
	foreach($arrUserVoices as $userID => $voice) {
		if ($voice == $voiceHandle) {
			$trClass = 'voice_' . $voiceHandle. ' response_' . $responseList[$userID];
			if ($userID == $activeUserID) $trClass .= " featured";
			if (current_user_can('edit_pages')) { // if editor rights
				$responseDisplay = "";

				// NB Should be using WP's selected() function, but weird things happened when I tried it.
				$responseDisplay .= '<select onChange="updateResponse(this)" class="cr_dropdown cr_dropdown_'.$post->ID.'" title="'. $userID . '" id="response_'. $userID . '">';
				$responseDisplay .= '    <option value="???" ';
				if($responseList[$userID] == '') $responseDisplay .= 'selected="selected"';
				$responseDisplay .= '>???</option>';

				$responseDisplay .= '    <option value="Yes" ';
				if($responseList[$userID] == 'Yes') $responseDisplay .= 'selected="selected"';
				$responseDisplay .= '>Yes</option>';

				$responseDisplay .= '    <option value="No" ';
				if($responseList[$userID] == 'No') $responseDisplay .= 'selected="selected"';
				$responseDisplay .= '>No</option>';

				$responseDisplay .= '    <option value="Maybe" ';
				if($responseList[$userID] == 'Maybe') $responseDisplay .= 'selected="selected"';
				$responseDisplay .= '>Maybe</option>';

				$responseDisplay .= '</select>';
			} else {
				$responseDisplay = cr_ResponseName($responseList[$userID]);
			}
			$draw.='<tr class="' .$trClass. '"><td>'.cr_UserData($userID).'</td><td>' . $responseDisplay .'</td></tr>';
		}
	}
	return $draw;
}

function cr_DrawRehearsalList($year, $term) {
    global $post, $current_user, $cr_lang;
    //currentUser = $current_user";
		$draw = "<h2>" . $cr_lang['term'] . " $term of $year</h2>";
		$responseList = cr_GetRehearsalListResponses($year,$term, $current_user->ID);
    //return( print_r($responseList,1));
		if (!$responseList) {
			$draw .= $cr_lang['noRehearsalsForPeriod'];
			return $draw;
		}

    $draw.='<table class="cr_innerTable" id="cr_innerTableL_'.$current_user->ID.'">';
		$draw .='<thead><tr><th>' . $cr_lang['Date'] . '</th><th>' . $cr_lang['Location'] . '</th><th>' . $cr_lang['Attending'] . '</th></tr></thead><tbody>';
    for($i=0; $i<count($responseList); $i++){
        $prettyDate = date("D jS M", strtotime($responseList[$i]->rehearsalDate));
				$draw .= "<tr>";
				$draw .= "<td>" . $prettyDate . "</td>";
				$draw .= "<td>" . $responseList[$i]->location . "</td>";
				$response = $responseList[$i]->response ? 'checked' : '';
				$rehearsalID=$responseList[$i]->rehearsalID;
        $draw .= "<td><input type='checkbox' name='$rehearsalID' onChange='updateRehearsalResponse(this)' id='".$current_user->ID."' title='$rehearsalID' $response></td>"; //"<td> <input type='checkbox' name='thinkOfAName' value='1'" . $response . "></td>";
				$draw .= "</tr>";
    }
    $draw.='</tbody></table>';


    return $draw;
}

function cr_DrawRehearsalGrid($year, $term) {
    global $post, $current_user, $cr_lang;
		//return "cr_DrawRehearsalGrid($year, $term)";
		$draw = "<h2>" . $cr_lang['term'] . " $term of $year</h2>";
		$responseList = cr_GetRehearsalList($year,$term);
    return( print_r($responseList,1));
		if (!$responseList) {
			$draw .= $cr_lang['noRehearsalsForPeriod'];
			return $draw;
		}

    $draw.='<table class="cr_innerTable" id="cr_innerTableL_'.$current_user->ID.'">';
		$draw .='<thead><tr>';
		foreach ($responseList as $key => $value) {
			echo "k=$key v=$value<br>";
			// code... '<th>' . $cr_lang['Date'] . '</th><th>' . $cr_lang['Location'] . '</th><th>' . $cr_lang['Attending'] . '</th>
		}
		$draw .='</tr></thead>';
		$draw .='<tbody>';
		/*
    for($i=0; $i<count($responseList); $i++){
        $prettyDate = date("D jS M", strtotime($responseList[$i]->rehearsalDate));
				$draw .= "<tr>";
				$draw .= "<td>" . $prettyDate . "</td>";
				$draw .= "<td>" . $responseList[$i]->location . "</td>";
				$response = $responseList[$i]->response ? 'checked' : '';
				$rehearsalID=$responseList[$i]->rehearsalID;
        $draw .= "<td><input type='checkbox' name='$rehearsalID' onChange='updateRehearsalResponse(this)' id='".$current_user->ID."' title='$rehearsalID' $response></td>"; //"<td> <input type='checkbox' name='thinkOfAName' value='1'" . $response . "></td>";
				$draw .= "</tr>";
    }
  */  $draw.='</tbody></table>';


    return $draw;
}


/********************************************/
function cr_simpleList() {
	global $post, $current_user, $cr_lang;
		$draw = '';

		$arrUserVoices = array();
		$users = get_users() ;
		foreach ($users as $user) {
			$v = get_user_meta( $user->ID, 'Voice' );
			$voice = $v[0];
			$arrUserVoices[$user->ID] = $voice;
		}

		$arrVoices = array(
				'Soprano1' => '1st Soprano',
				'Soprano2' => '2nd Soprano',
				'Alto1' => '1st Alto',
				'Alto2' => '2nd Alto',
				'Tenor1' => '1st Tenor',
				'Tenor2' => '2nd Tenor',
				'Bass1' => '1st Bass',
				'Bass2' => '2nd Bass',
				'None' => 'Non-singing members'
		);

		#$all_meta_for_user = get_user_meta( $uid );
		#$draw .= print_r( $all_meta_for_user, 1 );

	$draw.='<table class="cr_outerTable" id="cr_table_'.$post->ID.'"><tr><td colspan="1">';
	$draw.=$cr_lang['listheader'].'</td></tr>';

	$draw.='<tr><td><table class="cr_innerTable" id="cr_innerTableL_'.$post->ID.'">';
	foreach($arrVoices as $voiceHandle => $voiceName) {
		// *********************************************
		$draw.=drawSection($arrUserVoices, $voiceHandle, $voiceName);
	}

	$draw.='</table></td></tr></table>';

	return $draw;
}


function drawSection($arrUserVoices, $voiceHandle, $voiceName) {
    global $post;
	$draw='<tr class="voice_' . $voiceHandle. '"><td class="voiceHeader" width="150px">' .$voiceName.'</td><td></td><td></td></tr>';
	foreach($arrUserVoices as $userID => $voice) {
		if ($voice == $voiceHandle) {
			$trClass = 'voice_' . $voiceHandle. ' response_Yes';
			if ($userID == $activeUserID) $trClass .= " featured";
			$userData=get_userdata( $userID );
			$userEmail=$userData->user_email;
			$userPhone=$userData->mobile_phone;
			$userBiog=$userData->description;
			$draw.="<tr class='$trClass'><td rowspan='2'>" . cr_UserData($userID) . "</td><td><a href='mailto:$userEmail'>$userEmail</a></td><td>$userPhone</td></tr><tr class='$trClass' ><td colspan='2'>$userBiog</td> </tr>";
		}
	}
	return $draw;
}



?>
