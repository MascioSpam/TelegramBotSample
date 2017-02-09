<?php

require('conversation_handling.php');
require('natural_language_handling.php');

$message_id = $message['message_id'];
$chat_id = $message['chat']['id'];
$from_id = $message['from']['id'];
$handled_conv = false;
$conv = db_row_query("SELECT `user_id`, `topic`, `state` FROM `conversation` WHERE `user_id` = $from_id");

if ($conv != null){
   $handled_conv = handle_conversation($chat_id, $from_id, $message, $conv);
   if ($handled_conv == false)
	db_perform_action("DELETE FROM `conversation` WHERE `user_id` = $from_id");
   else if (is_string ($handled_conv))telegram_send_message($chat_id, $handled_conv);
}

if (isset($message['text']) && $handled_conv == false) {
    // Got an incoming text message
    

    if (strpos($message['text'], "/") === 0) {
	// Received a command
	command_handle ($chat_id, $from_id, $message, $message['text']);
    }
    else {
	//
	$message['text'] = strtoupper($message['text']);
	$message['text'] = natural_language($message['text']);

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

	if (strpos($bot_response, "/") === 0) {
		// Received a command
		command_handle ($chat_id, $from_id, $message, $bot_response);
        }
	else{
		$bot_response = replace_placeholder ($bot_response, $message);

		$bot_response = process_response($bot_response);
	
		telegram_send_message($chat_id, $bot_response);
	}
    }
}
else {
    //telegram_send_message($chat_id, 'Sorry, I understand only text messages at the moment!');
}



function command_handle ($chat_id, $from_id, $message, $text){
    switch (substr($text, 1)){
		case 'start':{
			$keyboard = prepare_button_array (array(array('Lista Biblioteche', 'Valuta bot'), array('Rating bot', 'Segnala aula studio'), array('Trova biblioteca/aula studio più vicina alla tua posizione')));
			telegram_send_message($chat_id, replace_placeholder (START_MSG, $message), $keyboard);
			break;
		}
		case 'biblicom':{
        		db_perform_action("REPLACE INTO `conversation` (`user_id`, `topic`, `state`) VALUES($from_id, 'biblicom', 1)");

       			telegram_send_message($chat_id, COM_LIST_MSG_0);
			break;
		}
		case 'valutabot':{
        		db_perform_action("REPLACE INTO `conversation` (`user_id`, `topic`, `state`) VALUES($from_id, 'valutabot', 1)");

			$keyboard = prepare_button_array (array(array('1', '2', '3'), array('4', '5', 'Annulla')));
       			telegram_send_message($chat_id, VALUTABOT_MSG_0, $keyboard);
			break;
		}
		case 'rating':{
			$avg = db_scalar_query("SELECT AVG(vote) FROM bot_votes");
			$count = db_scalar_query("SELECT COUNT(user_id) as count FROM bot_votes");
			$bot_response = "Ho ricevuto un totale di $count valutazioni con una media di $avg";
			telegram_send_message($chat_id, $bot_response);
			break;
		}
		case 'segnala':{
			db_perform_action("REPLACE INTO `conversation` (`user_id`, `topic`, `state`) VALUES($from_id, 'segnala', 1)");

       			telegram_send_message($chat_id, SEGNALA_MSG_0);
			break;
		}
		case 'vicino':{
			db_perform_action("REPLACE INTO `conversation` (`user_id`, `topic`, `state`) VALUES($from_id, 'vicino', 1)");
			telegram_send_message($chat_id, VICINO_MSG_0);
			break;
		}
		default:{
			telegram_send_message($chat_id, ERROR_UNKNOWN_COMMAND);
			break;
		}
	}
}
?>
