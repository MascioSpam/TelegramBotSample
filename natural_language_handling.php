<?php

    function natural_language($message) {
	$rep_bib = Array(
		'biblioteche' => 'biblioteca',
		'libreria' => 'biblioteca',
		'librerie' => 'biblioteca',
		'archivio' => 'biblioteca',
		'archivi' => 'biblioteca',
		'fondo' => 'biblioteca',
		'fondi' => 'biblioteca',
	);

	$rep_seg = Array(
		'segnalare' => 'segnala',
		'vicinanze' => 'vicina',
		'paraggi' => 'vicina',
		'vicino' => 'vicina',
	);

	$result = str_ireplace(array_keys($rep_bib), $rep_bib, $message);
	$result = str_ireplace(array_keys($rep_seg), $rep_seg, $result);
	$result = str_ireplace(array_keys($rep_l), $rep_l, $result);
	
	return $result;
    }

    function process_response($bot_response) {
	if (strpos($bot_response, ' - ') === false)
		return $bot_response;
	else
		$bot_response = strtolower($bot_response);
	$attr = explode ( " " , $bot_response , 5);
	$sql = "";
	$bot_response = "";
	
	switch ($attr[0]){
		case 'cap':
			$sql = "SELECT denominazione, cap FROM biblioteche WHERE cap = '$attr[2]'";
			$bot_response = CAP_LIST_MSG_1;
			break;
		default:
			return $bot_response;
	}
	$ps = $attr[2];
	$list = db_table_query($sql);
	    if ($list != null){
		$bot_response .= " $attr[2]:\n\n";
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
