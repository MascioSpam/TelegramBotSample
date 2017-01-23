<?php
/*
 * Telegram Bot Sample
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Basic message processing functionality,
 * used by both pull and push scripts.
 *
 * Put your custom bot intelligence here!
 */

// This file assumes to be included by pull.php or
// hook.php right after receiving a new message.
// It also assumes that the message data is stored
// inside a $message variable.

// Message object structure: {
//     "message_id": 123,
//     "from": {
//       "id": 123456789,
//       "first_name": "First",
//       "last_name": "Last",
//       "username": "FirstLast"
//     },
//     "chat": {
//       "id": 123456789,
//       "first_name": "First",
//       "last_name": "Last",
//       "username": "FirstLast",
//       "type": "private"
//     },
//     "date": 1460036220,
//     "text": "Text"
//   }
$message_id = $message['message_id'];
$chat_id = $message['chat']['id'];
$from_id = $message['from']['id'];

if (isset($message['text'])) {
    // We got an incoming text message
    $text = $message['text'];

    if (strpos($text, "/start") === 0) {
        echo 'Received /start command!' . PHP_EOL;

        telegram_send_message($chat_id, 'This is your first Telegram bot, welcome!');
    }
    else {

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
