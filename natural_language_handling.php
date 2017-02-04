<?php

    function natural_language($chat_id, $from_id, $message, $conv) {
	$rep_bib = Array(
		'biblioteche' => 'biblioteca',
		'libreria' => 'biblioteca',
		'librerie' => 'biblioteca',
		'archivio' => 'biblioteca',
		'archivi' => 'biblioteca',
		'fondo' => 'biblioteca',
		'fondi' => 'biblioteca',
	);

	$rep_l = Array(
		'province' => 'provincia',
		'comuni' => 'comune',
	);

	$message['text'] = str_replace(array_keys($rep_bib), $rep_bib, $message['text']);
	$message['text'] = str_replace(array_keys($rep_l), $rep_l, $message['text']);
	
	return $message['text'];
    }

    function process_response($bot_response) {
	if (strpos($bot_response, ' - ') === false)
		return $bot_response;
	else
		$bot_response = strtolower($bot_response);
	$attr = explode ( " " , $bot_response , 5);
	$sql = "";
	
	switch ($attr[0]){
		case 'com': 
			$sql = "SELECT denominazione, comune FROM biblioteche WHERE comune = '$attr[2]'";
			break;
		case 'cap':
			$sql = "SELECT denominazione, cap FROM biblioteche WHERE cap = '$attr[2]'";
			break;
		case 'pro':
			$sql = "SELECT denominazione, provincia FROM biblioteche WHERE provincia = '$attr[2]'";
			break;
		default:
			return $bot_response;
	}
	$ps = $attr[2];
	$list = db_table_query($sql);
	    if ($list != null){
		$bot_response = "$attr[2]:\n";
		foreach ($list as $ps => $p){
			$bot_response .= $p[0] ."\n";
	        }
		return $bot_response;
	    }
	    else{
		return 'Mi dispiace, non conosco il comando da te inserito';
	    }
    }
?>
