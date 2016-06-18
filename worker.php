<?php
//uses PJJtextbase for now(http://www.pjj.pl/pjjtextbase/)
require 'pjjtextbase.php';
require 'dataManager.php';



//Warning! Super messy undocumented code ahead...
function processMessage($message) {
  $message_id = $message['message_id'];
  $chat_id = $message['chat']['id'];
  
  $myDatabase = ptb_connect('data.csv', 'L');
  
  $found = ptb_select($myDatabase, "isThere('chatid', '$chat_id,')");
  
if (isset($message['text'])) {
	
	$text = $message['text'];
	
	if ($text == "/start" and $found == 0){
		newEntry($chat_id);
		apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => '🌍?', 'reply_markup' => array(
			'keyboard' => array(array('🇩🇪', '🇬🇧')),
			'one_time_keyboard' => true,
			'resize_keyboard' => true)));
	}else if ($found == null){
		apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'You should first use /start to start this bot.'));
		
	}else if ($text == "/start"){
		if ($found[0]['language'] == 1){ 
			apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'Ich glaube wir sind uns schonmal begegnet.'));
		} else if ($found[0]['language'] == 0 or $found[0]['language'] == 2){ 
			apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'I think we alredy met.'));
		}
	}else if ($found[0]['state'] == 0 and ($text == "🇩🇪" or $text == "🇬🇧")){
		
		if ($text == "🇩🇪"){
			updateEntry($chat_id, "language", "1");
			updateEntry($chat_id, "state", "4");
			apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Sprache wurde auf Deutsch gestellt.'.PHP_EOL.'Du kannst die Sprache jederzeit in den Einstellungen unter /settings ändern.', 'reply_markup' => array('hide_keyboard' => true)));
		}
		else if ($text == "🇬🇧"){
			updateEntry($chat_id, "language", "2");
			updateEntry($chat_id, "state", "4");
			apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Language was set to English.'.PHP_EOL.'You can always change the Language in the Settings under /settings.', 'reply_markup' => array('hide_keyboard' => true)));
		}
	}else if ($found[0]['state'] == 0){
		apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => '🌍?', 'reply_markup' => array(
			'keyboard' => array(array('🇩🇪', '🇬🇧')),
			'one_time_keyboard' => true,
			'resize_keyboard' => true)));
	}else if ($text == "/stop"){
		if ($found[0]['language'] == 0 or $found[0]['language'] == 2){
			updateEntry($chat_id, "state", "5");
			apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'If you stop this Bot all data will be lost!'.PHP_EOL.'Do you want to continue?', 'reply_markup' => array(
				'keyboard' => array(array('Yes', 'No')),
				'one_time_keyboard' => true,
				'resize_keyboard' => true)));
		}else if ($found[0]['language'] == 1){
			updateEntry($chat_id, "state", "5");
			apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Wenn du diesen Bot abbestellst werden alle Daten gelöscht!'.PHP_EOL.'Möchtest du fortfahren?', 'reply_markup' => array(
				'keyboard' => array(array('Ja', 'Nein')),
				'one_time_keyboard' => true,
				'resize_keyboard' => true)));
		}
	}else if($found[0]['state'] == 5){
		if($text == "Yes" or $text == "Ja"){
			deleteEntry($chat_id);
			if ($found[0]['language'] == 0 or $found[0]['language'] == 2){ 
				apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'All youre data has been delted!'.PHP_EOL.'Use /start to restart the bot.', 'reply_markup' => array('hide_keyboard' => true)));
			}else if ($found[0]['language'] == 1){
				apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Alle deine Daten wurden gelöscht!'.PHP_EOL.'Nutze /start um den Bot neu zu starten.', 'reply_markup' => array('hide_keyboard' => true)));
			}
		}else if($text == "No" or $text == "Nein"){
			updateEntry($chat_id, "state", "4");
			if ($found[0]['language'] == 0 or $found[0]['language'] == 2){
				apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Glad to see you stay!', 'reply_markup' => array('hide_keyboard' => true)));//PUZZLE
			}else if ($found[0]['language'] == 1){
				apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Schön, dass du uns nicht verlässt!', 'reply_markup' => array('hide_keyboard' => true)));
			}
		}else{
			updateEntry($chat_id, "state", "5");
			if ($found[0]['language'] == 0 or $found[0]['language'] == 2){
			apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'If you stop this Bot all data will be lost!'.PHP_EOL.'Do you want to continue?', 'reply_markup' => array(
				'keyboard' => array(array('Yes', 'No')),
				'one_time_keyboard' => true,
				'resize_keyboard' => true)));
			
		}else if ($found[0]['language'] == 1){
			apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Wenn du diesen Bot abbestellst werden alle Daten gelöscht!'.PHP_EOL.'Möchtest du fortfahren?', 'reply_markup' => array(
				'keyboard' => array(array('Ja', 'Nein')),
				'one_time_keyboard' => true,
				'resize_keyboard' => true)));
			}
		}
	}else if($text == "/settings"){
		updateEntry($chat_id, "state", "1");
		if ($found[0]['language'] == 0 or $found[0]['language'] == 2){
			apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'What do you want to change?', 'reply_markup' => array(
				'keyboard' => array(array('Language', 'Person'), array('Stop Bot', 'Exit')),
				'one_time_keyboard' => true,
				'resize_keyboard' => true)));
			
		}else if ($found[0]['language'] == 1){
			apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Was möchtest du verändern?', 'reply_markup' => array(
				'keyboard' => array(array('Sprache', 'Person'),array('Abbestellen', 'Schließen')),
				'one_time_keyboard' => true,
				'resize_keyboard' => true)));
			}
	}else if($found[0]['state'] == 1){
		if($text == "Language" or $text == "Sprache"){
			updateEntry($chat_id, "state", "2");
				if ($found[0]['language'] == 0 or $found[0]['language'] == 2){
					apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Language?', 'reply_markup' => array(
					'keyboard' => array(array('German - 🇩🇪', 'English - 🇬🇧')),
					'one_time_keyboard' => true,
					'resize_keyboard' => true)));
			
				}else if ($found[0]['language'] == 1){
					apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Sprache?', 'reply_markup' => array(
					'keyboard' => array(array('Deutsch - 🇩🇪', 'Englisch - 🇬🇧')),
					'one_time_keyboard' => true,
					'resize_keyboard' => true)));
				}
		}else if($text == "Person"){
			updateEntry($chat_id, "state", "3");
			if ($found[0]['language'] == 0 or $found[0]['language'] == 2){
			apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Person?', 'reply_markup' => array(
				'keyboard' => array(array('Layton - 🎩', 'Luke', 'Random')),
				'one_time_keyboard' => true,
				'resize_keyboard' => true)));
			}else if ($found[0]['language'] == 1){
			apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Person?', 'reply_markup' => array(
				'keyboard' => array(array('Layton - 🎩', 'Luke', 'Zufällig')),
				'one_time_keyboard' => true,
				'resize_keyboard' => true)));
			}	
		}else if($text == "Stop Bot" or $text == "Abbestellen"){
			updateEntry($chat_id, "state", "1");
			if ($found[0]['language'] == 0 or $found[0]['language'] == 2){
			apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'If you stop this Bot all data will be lost!'.PHP_EOL.'Do you want to continue?', 'reply_markup' => array(
				'keyboard' => array(array('Yes', 'No')),
				'one_time_keyboard' => true,
				'resize_keyboard' => true)));
			}else if ($found[0]['language'] == 1){
			apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Wenn du diesen Bot abbestellst werden alle Daten gelöscht!'.PHP_EOL.'Möchtest du fortfahren?', 'reply_markup' => array(
				'keyboard' => array(array('Ja', 'Nein')),
				'one_time_keyboard' => true,
				'resize_keyboard' => true)));
			}
		}else if($text == "Exit" or $text == "Schließen"){
			apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'PUZZLE', 'reply_markup' => array('hide_keyboard' => true)));
			updateEntry($chat_id, "state", "4");
		}else {
			if ($found[0]['language'] == 0 or $found[0]['language'] == 2){
				updateEntry($chat_id, "state", "1");
				apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'What do you want to change?', 'reply_markup' => array(
					'keyboard' => array(array('Language', 'Person'), array('Stop Bot', 'Exit')),
					'one_time_keyboard' => true,
					'resize_keyboard' => true)));
			
			}else if ($found[0]['language'] == 1){
				updateEntry($chat_id, "state", "1");
				apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Was möchtest du verändern?', 'reply_markup' => array(
					'keyboard' => array(array('Sprache', 'Person'), array('Abbestellen', 'Schließen')),
					'one_time_keyboard' => true,
					'resize_keyboard' => true)));
			}
		}
	}else if($found[0]['state'] == 3){
		if($text == "Layton - 🎩"){
			updateEntry($chat_id, "state", "1");
			updateEntry($chat_id, "person", "1");
			if ($found[0]['language'] == 0 or $found[0]['language'] == 2){
				apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Settings Changed.'.PHP_EOL.'What do you want to change?', 'reply_markup' => array(
					'keyboard' => array(array('Language', 'Person'), array('Stop Bot', 'Exit')),
					'one_time_keyboard' => true,
					'resize_keyboard' => true)));	
			}else if ($found[0]['language'] == 1){
				apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Einstellungen Gespeichert.'.PHP_EOL.'Was möchtest du verändern?', 'reply_markup' => array(
					'keyboard' => array(array('Sprache', 'Person'), array('Abbestellen', 'Schließen')),
					'one_time_keyboard' => true,
					'resize_keyboard' => true)));
			}
		}else if($text == "Luke"){
			updateEntry($chat_id, "state", "1");
			updateEntry($chat_id, "person", "2");
			if ($found[0]['language'] == 0 or $found[0]['language'] == 2){
				apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Settings Changed.'.PHP_EOL.'What do you want to change?', 'reply_markup' => array(
					'keyboard' => array(array('Language', 'Person'), array('Stop Bot', 'Exit')),
					'one_time_keyboard' => true,
					'resize_keyboard' => true)));	
			}else if ($found[0]['language'] == 1){
				apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Einstellungen Gespeichert.'.PHP_EOL.'Was möchtest du verändern?', 'reply_markup' => array(
					'keyboard' => array(array('Sprache', 'Person'), array('Abbestellen', 'Schließen')),
					'one_time_keyboard' => true,
					'resize_keyboard' => true)));
			}
		}else if($text == "Zufällig" or $text == "Random"){
			updateEntry($chat_id, "state", "1");
			updateEntry($chat_id, "person", "0");
			if ($found[0]['language'] == 0 or $found[0]['language'] == 2){
				apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Settings Changed.'.PHP_EOL.'What do you want to change?', 'reply_markup' => array(
					'keyboard' => array(array('Language', 'Person'), array('Stop Bot', 'Exit')),
					'one_time_keyboard' => true,
					'resize_keyboard' => true)));	
			}else if ($found[0]['language'] == 1){
				apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Einstellungen Gespeichert.'.PHP_EOL.'Was möchtest du verändern?', 'reply_markup' => array(
					'keyboard' => array(array('Sprache', 'Person'), array('Abbestellen', 'Schließen')),
					'one_time_keyboard' => true,
					'resize_keyboard' => true)));
			}
		}else{
			if ($found[0]['language'] == 0 or $found[0]['language'] == 2){
				apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Person?', 'reply_markup' => array(
					'keyboard' => array(array('Layton - 🎩', 'Luke', 'Random')),
					'one_time_keyboard' => true,
					'resize_keyboard' => true)));
			}else if ($found[0]['language'] == 1){
				apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Person?', 'reply_markup' => array(
					'keyboard' => array(array('Layton - 🎩', 'Luke', 'Zufällig')),
					'one_time_keyboard' => true,
					'resize_keyboard' => true)));
			}
		}
	}else if($found[0]['state'] == 2){
		if ($text == "Deutsch - 🇩🇪" or $text == "German - 🇩🇪"){
			updateEntry($chat_id, "language", "1");
			updateEntry($chat_id, "state", "1");
			apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Sprache wurde auf Deutsch gestellt.'.PHP_EOL.'Was möchtest du verändern?', 'reply_markup' => array(
				'keyboard' => array(array('Sprache', 'Person'), array('Abbestellen', 'Schließen')),
				'one_time_keyboard' => true,
				'resize_keyboard' => true)));
		}
		else if ($text == "Englisch - 🇬🇧" or $text == "English - 🇬🇧"){
			updateEntry($chat_id, "language", "2");
			updateEntry($chat_id, "state", "1");
			apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Language was set to English.'.PHP_EOL.'What do you want to change?', 'reply_markup' => array(
				'keyboard' => array(array('Language', 'Person'), array('Stop Bot', 'Exit')),
				'one_time_keyboard' => true,
				'resize_keyboard' => true)));
		}else{
				if ($found[0]['language'] == 0 or $found[0]['language'] == 2){
					apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Language?', 'reply_markup' => array(
					'keyboard' => array(array('German - 🇩🇪', 'English - 🇬🇧')),
					'one_time_keyboard' => true,
					'resize_keyboard' => true)));
			
				}else if ($found[0]['language'] == 1){
					apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Sprache?', 'reply_markup' => array(
					'keyboard' => array(array('Deutsch - 🇩🇪', 'Englisch - 🇬🇧')),
					'one_time_keyboard' => true,
					'resize_keyboard' => true)));
				}
		}
	}else {
		if ($found[0]['language'] == 0 or $found[0]['language'] == 2){ 
			apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => "I don't quite understand"));
		}else if ($found[0]['language'] == 1){ 
			apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'Ich verstehe nicht ganz...'));
		}
	}
}else
	if ($found[0]['language'] == 0 or $found[0]['language'] == 2){ 
		apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'I only understand text messages'));
	}else if ($found[0]['language'] == 1){ 
		apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'Ich verstehe nur Textnachrichten'));
	}
  }
?>