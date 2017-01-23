<?php
$message_id = $message['message_id'];
$chat_id = $message['chat']['id'];
$from_id = $message['from']['id'];

if (isset($message['text'])) {
    // Got an incoming text message
    $text = $message['text'];

    if (strpos($text, "/") === 0) {
	// Received a command
	switch (substr($message['text'], 1)){
		case 'start':{
			telegram_send_message($chat_id, 'Ciao ' . $message['from']['first_name'] . '!' . "\n" . START_MSG);
		}
		case 'valutabot':{
		}
		case 'rating':{
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
}
else {
    telegram_send_message($chat_id, 'Sorry, I understand only text messages at the moment!');
}
?>
