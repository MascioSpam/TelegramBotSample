<?php

// Function definitions

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
    $state = $conv[2];
    $text = $message['text'];

    switch($state) {
        case 1:
	    $vote = intval($text);
	    if ($vote >= 1 && $vote <= 5){
		db_perform_action ("REPLACE INTO bot_votes VALUES($from_id, $vote)");
            	telegram_send_message($chat_id, VALUTAZIONE_MSG_1);
	    }
	    else {
		db_perform_action("REPLACE INTO `conversation` VALUES($from_id, 'valutazione', 2)");
		telegram_send_message($chat_id, VALUTAZIONE_MSG_2);
	    }

            db_perform_action("DELETE FROM `conversation` WHERE `user_id` = $from_id");
            return true;
	case 2:
	    $vote = intval($text);
	    if ($vote >= 1 && $vote <= 5){
		db_perform_action ("REPLACE INTO bot_votes VALUES($from_id, $vote)");
            	telegram_send_message($chat_id, VALUTAZIONE_MSG_1);
	    }
	    else {
		return false;
	    }

            db_perform_action("DELETE FROM `conversation` WHERE `user_id` = $from_id");
            return true;
    }

    return false;
}

// End of Function declarations

$message_id = $message['message_id'];
$chat_id = $message['chat']['id'];
$from_id = $message['from']['id'];

if (isset($message['text'])) {
    // Got an incoming text message
    $text = $message['text'];
    $conv = db_row_query("SELECT `user_id`, `topic`, `state` FROM `conversation` WHERE `user_id` = $from_id");
    $handled_conv = true;

    if ($conv != null)
	$handled_conv = handle_conversation($chat_id, $from_id, $message, $conv);

    else if (strpos($text, "/") === 0) {
	// Received a command
	switch (substr($message['text'], 1)){
		case 'start':{
			telegram_send_message($chat_id, 'Ciao ' . $message['from']['first_name'] . '!' . "\n" . START_MSG);
			break;
		}
		case 'valutabot':{
        		db_perform_action("REPLACE INTO `conversation` VALUES($from_id, 'valutazione', 1)");

       			telegram_send_message($chat_id, VALUTAZIONE_MSG_0);
			break;
		}
		case 'rating':{
		}
		default:{
			telegram_send_message($chat_id, ERROR_UNKNOWN_COMMAND);
			break;
		}
	}
    }
    else {
	// Received a text message
        $handle = prepare_curl_api_request(PROGRAMO_API_URI_BASE, 'POST',
    		array(
        		'say' => $message['text'],
        		'bot_id' => PROGRAMO_BOT_ID,
        		'format' => 'json',
        		'convo_id' => 'telegram' . $chat_id
    		),
    		null,
    		array(
        		'Content-Type: application/x-www-form-urlencoded',
        		'Accept: application/json'
    		)
	);

	$response = perform_curl_request($handle);
	if($response === false) {
    		Logger::fatal('Failed to perform request', __FILE__);
	}

	$json_response = json_decode($response, true);
	$bot_response = $json_response['botsay'];
	telegram_send_message($chat_id, $bot_response);
    }

    if ($handled_conv != true)
    	db_perform_action("DELETE FROM `conversation` WHERE `user_id` = $from_id");
}
else {
    telegram_send_message($chat_id, 'Sorry, I understand only text messages at the moment!');
}
?>
