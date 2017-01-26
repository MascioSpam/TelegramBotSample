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
		return handle_vote ($chat_id, $from_id, $message, $conv);
	default:
		return false;
    }
}

function handle_vote($chat_id, $from_id, $message, $conv) {
    $text = $message['text'];
    $state = $conv[2];
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
?>
