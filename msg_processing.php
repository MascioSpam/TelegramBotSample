<?php

require('conversation_handling.php');

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
        		db_perform_action("REPLACE INTO `conversation` VALUES($from_id, 'valutabot', 1)");

       			telegram_send_message($chat_id, VALUTABOT_MSG_0);
			break;
		}
		case 'rating':{
			$avg = db_scalar_query("SELECT AVG(vote) FROM bot_votes");
			$count = db_scalar_query("SELECT COUNT(user_id) as count FROM bot_votes");
			$bot_response = "Ho ricevuto un totale di $count valutazioni con una media di $avg";
			telegram_send_message($chat_id, $bot_response);
			break;
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
