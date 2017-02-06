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
            	telegram_send_message($chat_id, VALUTABOT_MSG_1);
		db_perform_action("DELETE FROM `conversation` WHERE `user_id` = $from_id");
            	return true;
	    }
	    else {
		db_perform_action("REPLACE INTO `conversation` VALUES($from_id, 'valutabot', 2)");
		telegram_send_message($chat_id, VALUTABOT_MSG_2);
		return true;
	    }
	case 2:
	    $vote = intval($text);
	    if ($vote >= 1 && $vote <= 5){
		db_perform_action ("REPLACE INTO bot_votes VALUES($from_id, $vote)");
            	telegram_send_message($chat_id, VALUTABOT_MSG_1);
	    }
	    else
		telegram_send_message($chat_id, VALUTABOT_MSG_3);

            db_perform_action("DELETE FROM `conversation` WHERE `user_id` = $from_id");
            return true;
	default:
	   return false;
    }
}

function handle_biblicom($chat_id, $from_id, $text, $state,$message) {
    switch($state) {
        case 1:
	    $com = ucwords(strtolower($text));
	    $list = db_table_query("SELECT denominazione, comune FROM biblioteche WHERE comune = '$com'");
	    if ($list != null){
		$bot_response = COM_LIST_MSG_1 . " $com:\n";
		foreach ($list as $com => $p){
			$bot_response .= $p[0] ." (" . $p[1] . ") \n";
	        }
		telegram_send_message($chat_id, $bot_response);
		db_perform_action("DELETE FROM `conversation` WHERE `user_id` = $from_id");
	    }
	    else{
		telegram_send_message($chat_id, COM_LIST_MSG_2);
	    	db_perform_action("REPLACE INTO `conversation` VALUES($from_id, 'biblicom', 2)");
	    }
	    return true;
        case 2:
	    $com = ucwords(strtolower($text));
	    $list = db_table_query("SELECT denominazione, comune FROM biblioteche WHERE comune = '$com'");
	    if ($list != null){
		$bot_response = COM_LIST_MSG_1 . " $com:\n";
		foreach ($list as $com => $p){
			$bot_response .= $p[0] ." (" . $p[1] . ") \n";
	        }
		telegram_send_message($chat_id, $bot_response);
	    }
	    else{
		telegram_send_message($chat_id, COM_LIST_MSG_3);
	    	return false;
	    }
	    db_perform_action("DELETE FROM `conversation` WHERE `user_id` = $from_id");
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
		   
		   telegram_send_message($chat_id, SEGNALA_MSG_1);
		   return true;
		}
		else{
		   db_perform_action("DELETE FROM `conversation` WHERE `user_id` = $from_id");
		   telegram_send_message($chat_id, SEGNALA_MSG_1_ERROR);
		   return false;
		}
	case 2:
		db_perform_action("DELETE FROM `conversation` WHERE `user_id` = $from_id");
		db_perform_action("UPDATE `aulee` SET `nome` = '$text' WHERE `aulee`.`us_id` = $from_id;");
		telegram_send_message($chat_id, SEGNALA_MSG_2);
		return true;
	default:
	   return false;
    }
}
?>
