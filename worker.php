<?php
//uses PJJtextbase for now(http://www.pjj.pl/pjjtextbase/)
require 'script/pjjtextbase.php';
require 'script/manager.php';
require 'script/puzzles.php';

//Warning! Super messy undocumented code ahead...
function processMessage($message) {
  $message_id = $message['message_id'];
  $chat_id = $message['chat']['id'];
  
  $myDatabase = ptb_connect('data.csv', 'L');
  
  $found = ptb_select($myDatabase, "isThere('chatid', '$chat_id')");
  
if (isset($message['text'])) {
	
	$text = $message['text'];
	
	if ($text == "/start" and $found == 0){
		newEntry($chat_id);
		sendKeyboardMessage($chat_id, "info_language", 2, array(array('btn_german', 'btn_english')));
	}else if ($found == null){
		sendMessage($chat_id, "error_noEntry", 0);
	}else if ($text == "/start"){
		sendMessage($chat_id, "error_entryExists", $found[0]['language']);
	}else if ($found[0]['state'] == 0 and ($text == "🇩🇪" or $text == "🇬🇧")){
		updateEntry($chat_id, "state", "4");
		if ($text == "🇩🇪"){
			updateEntry($chat_id, "language", "1");
			sendMessage($chat_id, "info_welcome", 1);
		}
		else if ($text == "🇬🇧"){
			updateEntry($chat_id, "language", "0");
			sendMessage($chat_id, "info_welcome", 0);
		}
		puzzleHandler($chat_id, $text);
	}else if ($found[0]['state'] == 0){	
		sendKeyboardMessage($chat_id, "info_language", $found[0]['language'], array(array('btn_german', 'btn_english')));
	}else if ($text == "/stop"){
		updateEntry($chat_id, "state", "5");
		sendKeyboardMessage($chat_id, "info_stopBot", $found[0]['language'], array(array('btn_yes', 'btn_no')));
	}else if($found[0]['state'] == 5){
		if($text == "Yes" or $text == "Ja"){
			deleteEntry($chat_id);
			sendMessage($chat_id, "info_botStoped", $found[0]['language']);
		}else if($text == "No" or $text == "Nein"){
			updateEntry($chat_id, "state", "4");
			sendMessage($chat_id, "info_botNotStoped", $found[0]['language']);
			puzzleHandler($chat_id, $text);
		}else{
			updateEntry($chat_id, "state", "5");
			sendKeyboardMessage($chat_id, "info_stopBot", $found[0]['language'], array(array('btn_yes', 'btn_no')));
		}
	}else if($text == "/settings"){
		updateEntry($chat_id, "state", "1");
		sendKeyboardMessage($chat_id, "info_settings", $found[0]['language'], array(array('btn_language', 'btn_person'), array('btn_stopBot', 'btn_close')));
	}else if($found[0]['state'] == 1){
		if($text == "Language" or $text == "Sprache"){
			updateEntry($chat_id, "state", "2");
			sendKeyboardMessage($chat_id, "info_language", $found[0]['language'], array(array('btn_german', 'btn_english')));
		}else if($text == "Person"){
			updateEntry($chat_id, "state", "3");
			sendKeyboardMessage($chat_id, "info_person", $found[0]['language'], array(array('btn_layton', 'btn_luke', 'btn_random')));
		}else if($text == "Stop Bot" or $text == "Abbestellen"){
			updateEntry($chat_id, "state", "5");
			sendKeyboardMessage($chat_id, "info_stopBot", $found[0]['language'], array(array('btn_yes', 'btn_no')));
		}else if($text == "Exit" or $text == "Schließen"){
			updateEntry($chat_id, "state", "4");
			puzzleHandler($chat_id, $text);
		}else {
			sendKeyboardMessage($chat_id, "info_settings", $found[0]['language'], array(array('btn_language', 'btn_person'), array('btn_stopBot', 'btn_close')));
		}
	}else if($found[0]['state'] == 3){
		if($text == "Layton - 🎩"){
			updateEntry($chat_id, "state", "1");
			updateEntry($chat_id, "person", "1");
			sendKeyboardMessage($chat_id, "info_settingsChanged", $found[0]['language'], array(array('btn_language', 'btn_person'), array('btn_stopBot', 'btn_close')));
		}else if($text == "Luke"){
			updateEntry($chat_id, "state", "1");
			updateEntry($chat_id, "person", "2");
			sendKeyboardMessage($chat_id, "info_settingsChanged", $found[0]['language'], array(array('btn_language', 'btn_person'), array('btn_stopBot', 'btn_close')));
		}else if($text == "Zufällig" or $text == "Random"){
			updateEntry($chat_id, "state", "1");
			updateEntry($chat_id, "person", "0");
			sendKeyboardMessage($chat_id, "info_settingsChanged", $found[0]['language'], array(array('btn_language', 'btn_person'), array('btn_stopBot', 'btn_close')));
		}else{
			sendKeyboardMessage($chat_id, "info_person", $found[0]['language'], array(array('btn_layton', 'btn_luke', 'btn_random')));
		}
	}else if($found[0]['state'] == 2){
		if ($text == "Deutsch - 🇩🇪" or $text == "German - 🇩🇪"){
			updateEntry($chat_id, "language", "1");
			updateEntry($chat_id, "state", "1");
			sendKeyboardMessage($chat_id, "info_settingsChanged", 1, array(array('btn_language', 'btn_person'), array('btn_stopBot', 'btn_close')));
		}
		else if ($text == "Englisch - 🇬🇧" or $text == "English - 🇬🇧"){
			updateEntry($chat_id, "language", "0");
			updateEntry($chat_id, "state", "1");
			sendKeyboardMessage($chat_id, "info_settingsChanged", 0, array(array('btn_language', 'btn_person'), array('btn_stopBot', 'btn_close')));
		}else{
			sendKeyboardMessage($chat_id, "info_language", $found[0]['language'], array(array('btn_german', 'btn_english')));
		}
	}else if ($found[0]['state'] == 4 or $found[0]['state'] == 6){
		puzzleHandler($chat_id, $text);
	}else {
		sendMessage($chat_id, "error_noContext", $found[0]['language']);
	}
}else
	sendMessage($chat_id, "error_noTextMsg", $found[0]['language']);
  }
?>