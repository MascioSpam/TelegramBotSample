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
    switch($state) {
        case 2:
        case 1:
	    $com = ucwords(strtolower($text));
	    $n_row = db_scalar_query("SELECT COUNT(*) FROM biblioteche WHERE comune = '$com'");
	    if ($n_row > 40){
		//result too big to show in one shot
		db_perform_action("REPLACE INTO `conversation` (`q_rows`, `q_offset`, `attr`, `user_id`, `topic`, `state`) VALUES($n_row, 40, '$com', $from_id, 'biblicom', 3)");
	    }
	    else 
		db_perform_action("DELETE FROM `conversation` WHERE `user_id` = $from_id");
	    $list = db_table_query("SELECT denominazione, comune FROM biblioteche WHERE comune = '$com' LIMIT 40");
	    if ($list != null){
		$bot_response = COM_LIST_MSG_1 . " $com:\n\n";
		foreach ($list as $com => $p){
			$bot_response .= $p[0] ." (" . $p[1] . ") \n";
	        }
		$n_row -= 40;
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
	
        case 3:
	   if ($text == "Continua"){
		   $vals = db_row_query("SELECT * FROM `conversation` WHERE `user_id` = $from_id");
		   $com = $vals[3];
		   $offset = intval($vals[4]);

		   $list = db_table_query("SELECT denominazione, comune FROM biblioteche WHERE comune = '$com' LIMIT $vals[4],40");
		   if ($list != null){
			$bot_response = "";
			foreach ($list as $val[3] => $p){
				$bot_response .= $p[0] ." (" . $p[1] . ") \n";
			}
			telegram_send_message($chat_id, $bot_response);
		   }

	           $showed = $offset + 40;
		   if ($vals[5] - $showed > 0){
			db_perform_action("REPLACE INTO `conversation` (`attr`,`q_rows`, `q_offset`, `user_id`, `topic`, `state`) VALUES('$com', $vals[5], $showed, $from_id, 'biblicom', 3)");
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
		   db_perform_action("REPLACE INTO `conversation` VALUES($from_id, 'segnala', 2)");
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
?>
