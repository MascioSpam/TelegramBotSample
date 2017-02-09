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

		   $list = db_table_query("SELECT denominazione, comune FROM biblioteche WHERE comune = '$com' LIMIT $vals[4],30");
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



function handle_segnala($chat_id, $from_id, $text, $state,$message) {
    switch($state) {
        case 1:
		if (isset($message['location'])){
		   db_perform_action("REPLACE INTO `conversation` (`user_id`, `topic`, `state`) VALUES($from_id, 'segnala', 2)");
		   $lat = $message['location']['latitude'];
		   $lng = $message['location']['longitude'];

		   db_perform_action("INSERT INTO `aulee` (`nome`, `lat`, `lng`, `us_id`) VALUES ('*name*', '$lat', '$lng', '$from_id')");
		   
		   return SEGNALA_MSG_1;
		}
		else{
		   db_perform_action("DELETE FROM `conversation` WHERE `user_id` = $from_id");
		   return SEGNALA_MSG_1_ERROR;
		}
	case 2:
		db_perform_action("DELETE FROM `conversation` WHERE `user_id` = $from_id");
		db_perform_action("UPDATE `aulee` SET `nome` = '$text' WHERE `aulee`.`us_id` = $from_id");
		return SEGNALA_MSG_2;
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
			return VICINO_MSG_ERROR;
		}
        case 2:
		if ($text == "Biblioteca vicina a te"){
			$row = db_row_query("SELECT * FROM conversation WHERE `user_id` = $from_id");
			$res = db_row_query("SELECT *, SQRT(POW($row[4] - lat, 2) + POW($row[5] - lng, 2)) AS distance
  						FROM `biblioteche`
						WHERE lat IS NOT NULL AND lat != 0 AND lng IS NOT NULL AND lng != 0
  						ORDER BY distance ASC
  						LIMIT 1");
			telegram_send_message($chat_id, "La biblioteca più vicina a te e' '".$res[1]."'");
			return true;
		}
		else if ($text == "Aula studio vicina a te"){
			$row = db_row_query("SELECT * FROM conversation WHERE `user_id` = $from_id");
			$res = db_row_query("SELECT *, SQRT(POW($row[4] - lat, 2) + POW($row[5] - lng, 2)) AS distance
  						FROM `aulee`
  						ORDER BY distance ASC
  						LIMIT 1");
			telegram_send_message($chat_id, "L'aula studio più vicina a te e' '".$res[1]."'");
			return true;
		}
		else return false;
	default:
	   return false;
    }
}

function lista_bib ($chat_id, $from_id, $text, $state,$message) {
	$com = ucwords(strtolower($text));
	$n_row = db_scalar_query("SELECT COUNT(*) FROM biblioteche WHERE comune = '$com'");
	if ($n_row > 30){
		//result too big to show in one shot
		db_perform_action("REPLACE INTO `conversation` (`val_2`, `val_1`, `attr`, `user_id`, `topic`, `state`) VALUES($n_row, 30, '$com', $from_id, 'biblicom', 3)");
	}
	else 
		db_perform_action("DELETE FROM `conversation` WHERE `user_id` = $from_id");
	$list = db_table_query("SELECT denominazione, comune FROM biblioteche WHERE comune = '$com' LIMIT 30");
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
