<?php

function handle_conversation($chat_id, $from_id, $message, $conv) {
    if($conv === false) {
        // Query failed
        return false;
    }
    if($conv == null) {
        // No existing conversation with the user
        return false;
    }

    $topic = $conv[1];

    switch($topic) {
	case valutabot:
		return handle_vote ($chat_id, $from_id, $message['text'], $conv[2],$message);
	case biblicom:
		return handle_biblicom ($chat_id, $from_id, $message['text'], $conv[2],$message);
	case biblicap:
		return handle_biblicap ($chat_id, $from_id, $message['text'], $conv[2],$message);
	case segnala:
		return handle_segnala ($chat_id, $from_id, $message['text'], $conv[2],$message);
	case vicino:
		return handle_vicino ($chat_id, $from_id, $message['text'], $conv[2],$message);
	default:
		return false;
    }
}

function handle_vote($chat_id, $from_id, $text, $state,$message) {
    switch($state) {
        case 1:
	    $vote = intval($text);
	    if ($vote >= 1 && $vote <= 5){
		db_perform_action ("REPLACE INTO bot_votes VALUES($from_id, $vote)");
		db_perform_action("DELETE FROM `conversation` WHERE `user_id` = $from_id");
            	return VALUTABOT_MSG_1;
	    }
	    else {
                db_perform_action("DELETE FROM `conversation` WHERE `user_id` = $from_id");
		if ($text != "Annulla")
			return VALUTABOT_MSG_2;
		else	return VALUTABOT_MSG_3;
	    }
	default:
	   return false;
    }
}

function handle_biblicom($chat_id, $from_id, $text, $state,$message) {
	// In DB:
	//  val_1 used as offset
	//  val_2 used as rows
    switch($state) {
        case 2:
        case 1:
	    return lista_bib ($chat_id, $from_id, $text, $state,$message);
        case 3:
	   if ($text == "Continua"){
		   $vals = db_row_query("SELECT * FROM `conversation` WHERE `user_id` = $from_id");
		   $com = $vals[3];
		   $offset = intval($vals[4]);

		   $list = db_table_query("SELECT denominazione, comune FROM biblioteche WHERE comune = ".'"'.$com.'"' . "LIMIT $vals[4],30");
		   if ($list != null){
			$bot_response = "";
			foreach ($list as $val[3] => $p){
				$bot_response .= $p[0] ." (" . $p[1] . ") \n";
			}
			telegram_send_message($chat_id, $bot_response);
		   }else telegram_send_message($chat_id, "Lista vuota!");

	           $showed = $offset + 30;
		   if ($vals[5] - $showed > 0){
			db_perform_action("REPLACE INTO `conversation` (`attr`,`val_2`, `val_1`, `user_id`, `topic`, `state`) VALUES('$com', $vals[5], $showed, $from_id, 'biblicom', 3)");
		   	$keyboard = prepare_button_array (array(array('Continua', 'Annulla')));
	       	   	telegram_send_message($chat_id, str_replace("*listnum*", $vals[5] - $showed, COM_LIST_MSG_4), $keyboard);
		   }
		   else{
			db_perform_action("DELETE FROM `conversation` WHERE `user_id` = $from_id");
			return COM_LIST_MSG_6;
		   }
	   }
	   else {
		db_perform_action("DELETE FROM `conversation` WHERE `user_id` = $from_id");
		if ($text == "Annulla")
		   return COM_LIST_MSG_5;
		else 
		   return false;
	   }
	   return true;
	default:
	   return false;
    }
}


function handle_biblicap($chat_id, $from_id, $text, $state,$message) {
	$cap = ucwords(strtolower($text));
	db_perform_action("DELETE FROM `conversation` WHERE `user_id` = $from_id");
	$list = db_table_query("SELECT denominazione FROM biblioteche WHERE cap = ".'"'.$cap.'"');
	if ($list != null){
		$bot_response = CAP_LIST_MSG_1 . " $cap:\n\n";
		foreach ($list as $cap => $p){
			$bot_response .= $p[0] . $p[1] . "\n";
	        }
		return $bot_response;
	}
	else if ($state == 1){
	    	db_perform_action("REPLACE INTO `conversation` (`user_id`, `topic`, `state`) VALUES($from_id, 'biblicap', 2)");
		return CAP_LIST_MSG_2;
	} else {
		db_perform_action("DELETE FROM `conversation` WHERE `user_id` = $from_id");
		return CAP_LIST_MSG_3;
	}
}



function handle_segnala($chat_id, $from_id, $text, $state,$message) {
    switch($state) {
        case 1:
		if (isset($message['location'])){
		   db_perform_action("REPLACE INTO `conversation` (`user_id`, `topic`, `state`) VALUES($from_id, 'segnala', 2)");
		   $lat = $message['location']['latitude'];
		   $lng = $message['location']['longitude'];

		   db_perform_action("INSERT INTO `aule` (`nome`, `lat`, `lng`, `us_id`) VALUES ('*name*', '$lat', '$lng', '$from_id')");
		   
		   telegram_send_message($chat_id, SEGNALA_MSG_1, prepare_button_array(array(array('Annulla segnalazione'))));
		   return true;
		}
		else{
		   db_perform_action("DELETE FROM `conversation` WHERE `user_id` = $from_id");
		   if ($text == "No")
			return SEGNALA_MSG_3;
		   else	return SEGNALA_MSG_1_ERROR;
		}
	case 2:
		db_perform_action("DELETE FROM `conversation` WHERE `user_id` = $from_id");
		if ($text == "Annulla segnalazione"){
			db_perform_action("DELETE FROM `aule` WHERE `aule`.`us_id` = $from_id AND `nome` = '*name*'");
			return SEGNALA_MSG_3;
		}
		else{
			db_perform_action("UPDATE `aule` SET `nome` = '$text' WHERE `aule`.`us_id` = $from_id AND `nome` = '*name*'");
			return SEGNALA_MSG_2;
		}
	default:
	   return false;
    }
}

function handle_vicino($chat_id, $from_id, $text, $state,$message) {
	// In DB:
	//  val_1 used as latitude
	//  val_2 used as longitude
	switch($state) {
	case 1:
		// if the message contain geographic coordinates
		if (isset($message['location'])){
			$lat = $message['location']['latitude'];
			$lng = $message['location']['longitude'];

			//  Remember to pass coordinates with '.' NOT ','
			db_perform_action("REPLACE INTO `conversation` (`user_id`, `topic`, `state`, `val_1`, `val_2`) VALUES($from_id, 'vicino', 2, $lat, $lng)");
			$keyboard = prepare_button_array (array(array('Biblioteca vicina a te', 'Aula studio vicina a te')));
			telegram_send_message($chat_id, VICINO_MSG_1, $keyboard);
			return true;
		}
		else {
			db_perform_action("DELETE FROM `conversation` WHERE `user_id` = $from_id");
			if ($text == "Annulla")
				return true;
			else return VICINO_MSG_ERROR;
		}
        case 2:
		$row = db_row_query("SELECT * FROM conversation WHERE `user_id` = $from_id");
		if ($text == "Biblioteca vicina a te"){
			$res = db_row_query("SELECT *, SQRT(POW($row[4] - lat, 2) + POW($row[5] - lng, 2)) AS distance
  						FROM `biblioteche`
						WHERE lat IS NOT NULL AND lat != 0 AND lng IS NOT NULL AND lng != 0
  						ORDER BY distance ASC
  						LIMIT 1");
			telegram_send_message($chat_id, "La biblioteca più vicina a te e' :\n".$res[1]);
			return find_address($row[4],$row[5]) . "\nClicca sul link per aprire il navigatore:\n https://www.google.com/maps/dir/Current+Location/".$row[4].",".$row[5];
		}
		else if ($text == "Aula studio vicina a te"){
			$res = db_row_query("SELECT *, SQRT(POW($row[4] - lat, 2) + POW($row[5] - lng, 2)) AS distance
  						FROM `aule`
  						ORDER BY distance ASC
  						LIMIT 1");
			telegram_send_message($chat_id, "L'aula studio più vicina a te e' :\n".$res[1]);
			return find_address($row[4],$row[5]) . "\nClicca sul link per aprire il navigatore:\n https://www.google.com/maps/dir/Current+Location/".$row[4].",".$row[5];
		}
		else return false;
	default:
	   return false;
    }
}

function find_address($lat,$lng) {
	$handle = prepare_curl_api_request("http://dev.virtualearth.net/REST/v1/Locations/$lat,$lng?key=" . BING_API, 'GET');

	$response = perform_curl_request($handle);
	if($response === false) {
	    return "Failed to perform request.";
	}

	$json = json_decode($response, true);
	if(!$json['resourceSets']) {
	    return "Response contains no resource sets";
	}

	$sets = $json['resourceSets'];
	$s = 1;

	$response = "";

	foreach($sets as $set) {

	    $resources = $sets[0]['resources'];
	    $r = 1;

	    foreach($resources as $resource) {
		$address = $resource['address'];
		$confidence = $resource['confidence'];

		$response .= "Indirizzo: ";
		$response .= $address['formattedAddress'];

		$response .= "\nConfidenza: $confidence";

		$r++;
	    }

	    $s++;
	}
	return $response;
}

function lista_bib ($chat_id, $from_id, $text, $state,$message) {
	$com = ucwords(strtolower($text));
	$n_row = db_scalar_query("SELECT COUNT(*) FROM biblioteche WHERE comune = ".'"'.$com.'"');
	if ($n_row > 30){
		//result too big to show in one shot
		db_perform_action("REPLACE INTO `conversation` (`val_2`, `val_1`, `attr`, `user_id`, `topic`, `state`) VALUES($n_row, 30, '$com', $from_id, 'biblicom', 3)");
	}
	else 
		db_perform_action("DELETE FROM `conversation` WHERE `user_id` = $from_id");
	$list = db_table_query("SELECT denominazione, comune FROM biblioteche WHERE comune = ".'"'.$com.'" LIMIT 30');
	if ($list != null){
		$bot_response = COM_LIST_MSG_1 . " $com:\n\n";
		foreach ($list as $com => $p){
			$bot_response .= $p[0] ." (" . $p[1] . ") \n";
	        }
		$n_row -= 30;
		if ($n_row > 0){
		  $keyboard = prepare_button_array (array(array('Continua', 'Annulla')));
	       	  telegram_send_message($chat_id, $bot_response . str_replace("*listnum*", $n_row, COM_LIST_MSG_4), $keyboard);
		}
	       	else
		  telegram_send_message($chat_id, $bot_response);
		return true;
	}
	else if ($state == 1){
	    	db_perform_action("REPLACE INTO `conversation` (`user_id`, `topic`, `state`) VALUES($from_id, 'biblicom', 2)");
		return COM_LIST_MSG_2;
	} else {
		db_perform_action("DELETE FROM `conversation` WHERE `user_id` = $from_id");
		return COM_LIST_MSG_3;
	}
}
?>
