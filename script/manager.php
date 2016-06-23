<?php
//creates a new entry in the databese: chatid|language|person|state|puzzleid|hint
function newEntry($userId) {
	$file = 'data.csv';
	$current = file_get_contents($file);
	$current .= PHP_EOL .$userId . ',2,0,0,0,0';
	file_put_contents($file, $current);
}

//deletes a entry based on the chatid
function deleteEntry($userId) {
	$changingLine = findEntry('chatid', $userId);
	
	if ($changingLine == null)return;
	
	$input = fopen('data.csv', 'r');
	$output = fopen('temporary.csv', 'w');
	
	$line = 0;
	while ($row = fgetcsv($input)) {
		if ($line != $changingLine) {         
			fputcsv($output, $row);  
		}
		$line++;
	}

	//close
	fclose($input);
	fclose($output);

	unlink('data.csv');// Delete
	rename('temporary.csv', 'data.csv'); //Rename temporary
}

//updates a entry
function updateEntry($userId, $name, $value) {	
	$changingLine = findEntry('chatid', $userId);
	if ($changingLine == null)return;
	$searchedRow = 0;
	
	$input = fopen('data.csv', 'r');
	$output = fopen('temporary.csv', 'w');
	
	$data = fgetcsv($input);
	$num = count($data);
	for ($c=0; $c < $num; $c++){
		if($data[$c] == $name){
			$searchedRow = $c;
		}
	}
	fputcsv($output, $data);
	
	$line = 1;
	while ($row = fgetcsv($input)) {
		if ($line == $changingLine) {         
			$row[$searchedRow] = $value;        
		}
		$line++;
		fputcsv($output, $row);  
	}

	//close
	fclose($input);
	fclose($output);

	unlink('data.csv');// Delete
	rename('temporary.csv', 'data.csv'); //Rename temporary
}

//finds line of entry
function findEntry($term, $value){
	$line = 1;
	$searchedRow = 0;
	
	if (($handle = fopen("data.csv", "r")) != false) {
		$data = fgetcsv($handle);
		$num = count($data);
		
		for ($c=0; $c < $num; $c++) {
			if($data[$c] == $term){
				$searchedRow = $c;
			}
		}
		
		while (($data = fgetcsv($handle)) != false) {
			
			if($data[$searchedRow] == $value){
				fclose($handle);
				return $line;
			}
			$line++;
		}
		fclose($handle);
	}
}
function countFile($db){
  return count(file($db))-1; 
}
//return entry
function getEntry($term, $value, $db){
	$searchedRow = 0;
	$csvHeader = array();
	$response = array();
	
	if (($handle = fopen($db, "r")) != false) {
		$data = fgetcsv($handle);
		$num = count($data);
		for ($c=0; $c < $num; $c++) {
			if($data[$c] == $term){
				$searchedRow = $c;
				$csvHeader = $data;
			}
		}
		while (($data = fgetcsv($handle)) != false) {
			if($data[$searchedRow] == $value)
			{
				for ($c=0; $c < $num; $c++) {
					$response[$csvHeader[$c]] = $data[$c];
				}
				fclose($handle);
				return $response;
			}
		}
		fclose($handle);
	}
}

function getAnswerSnipet($person, $correct, $language){

	if($person == 1){//layton
		if($correct){//corect answer
			$selcted = 'layton_r'.sprintf("%02d", rand(1, 29));
			$langResponse = getEntry('Person', $selcted, 'lang/response.csv');
		}else{//wrong answer
			$selcted = 'layton_w'.sprintf("%02d", rand(1, 27));
			$langResponse = getEntry('Person', $selcted, 'lang/response.csv');
		}
	}else if($person == 2){//luke
		if($correct){//corect answer
			$selcted = 'luke_r'.sprintf("%02d", rand(1, 27));
			$langResponse = getEntry('Person', $selcted, 'lang/response.csv');
		}else{//wrong answer
			$selcted = 'luke_w'.sprintf("%02d", rand(1, 27));
			$langResponse = getEntry('Person', $selcted, 'lang/response.csv');
		}
	}
	if ($language == 0){
		return $langResponse['English'];
	}else if($language == 1){
		return $langResponse['German'];
	}
	
}



function getLang($identifier, $language){
	
	if($language == 99){
		if((is_array($identifier) or ($identifier instanceof Traversable))) {
			foreach ($identifier as &$value) {
				foreach ($value as &$subvalue) {
					$subvalue = str_replace("\\n", "\n" ,$subvalue);
				}
			}
			return $identifier;
		}else{
		return str_replace("\\n", "\n" ,$identifier);
		}
	}
	
	//loads language file
	
	if((is_array($identifier) or ($identifier instanceof Traversable))) {
		//checks if array(button)
		foreach ($identifier as &$value) {
			foreach ($value as &$subvalue) {//opens array inside the array
				$langfound = getEntry('identifier', $subvalue, 'lang/interface.csv');
				
				if($langfound != null){
					if ($language == 0) {
						$subvalue = str_replace("\\n", "\n" ,$langfound['English']);//returns english version and replaces \\n with \n
					}
					else if ($language == 1) {
						$subvalue = str_replace("\\n", "\n" ,$langfound['German']);//returns german version and replaces \\n with \n
					}else if ($language == 2){
						$subvalue = str_replace("\\n", "\n" ,$langfound['Emoji']);//returns emoji version and replaces \\n with \n
					}else{
						$subvalue = str_replace("\\n", "\n" ,$subvalue);
					}
				}else{
					$subvalue = str_replace("\\n", "\n" ,$subvalue);
				}
			}
		}
		return $identifier;
	}else{//same thing but only if its not an array(normal text)
		$langfound = getEntry('identifier', $identifier, 'lang/interface.csv');
		if($langfound != null){
			if ($language == 0) {
				return str_replace("\\n", "\n" ,$langfound['English']);
			}
			else if ($language == 1) {
				return str_replace("\\n", "\n" ,$langfound['German']);
			}
			else if ($language == 2){
				return str_replace("\\n", "\n" , $langfound['Emoji']);
			}
			else{
				return str_replace("\\n", "\n" , $identifier);
			}
		}else{
			return str_replace("\\n", "\n" ,$identifier);
		}
	}
}

function sendChatAction($user_id, $action){
	apiRequestJson("sendChatAction", array('chat_id' => $user_id, "action" => $action));
}

function sendVideo($user_id, $video){
	$url = API_URL . "sendVideo?chat_id=" . $user_id ;
	
	$post = array('chat_id'   => $user_id, 'video' => new CURLFile(realpath($video)));

	$header = curl_init();
	
	curl_setopt($header, CURLOPT_HTTPHEADER, array("Content-Type:multipart/form-data"));
	curl_setopt($header, CURLOPT_URL, $url); 
	curl_setopt($header, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt($header, CURLOPT_POSTFIELDS, $post); 
	curl_exec($header);
}

function sendMessage($user_id, $msg_identifier, $language){
	apiRequestJson("sendMessage", array('chat_id' => $user_id, "text" => getLang($msg_identifier, $language), 'reply_markup' => array('hide_keyboard' => true)));
}
	
function sendKeyboardMessage($user_id, $msg_identifier, $language, $keyboard_btns){
	apiRequestJson("sendMessage", array('chat_id' => $user_id, "text" => getLang($msg_identifier, $language), 'reply_markup' => array(
		'keyboard' => getLang($keyboard_btns, $language),
		'one_time_keyboard' => true,
		'resize_keyboard' => true)));
		
}
function sendKeyboardMarkdownMessage($user_id, $msg_identifier, $language, $keyboard_btns){
	apiRequestJson("sendMessage", array('chat_id' => $user_id, "text" => getLang($msg_identifier, $language), 'parse_mode' => 'Markdown' , 'reply_markup' => array(
		'keyboard' => $keyboard_btns,
		'one_time_keyboard' => true,
		'resize_keyboard' => true)));
}


function sendMarkdownMessage($user_id, $msg_identifier, $language){
	apiRequestJson("sendMessage", array('chat_id' => $user_id, "text" => getLang($msg_identifier, $language), 'parse_mode' => 'Markdown' , 'reply_markup' => array('hide_keyboard' => true)));
}

function sendImage($user_id, $image){
	$url = API_URL . "sendPhoto?chat_id=" . $user_id ;
	
	$post = array('chat_id'   => $user_id, 'photo' => new CURLFile(realpath($image)));

	$header = curl_init();
	
	curl_setopt($header, CURLOPT_HTTPHEADER, array("Content-Type:multipart/form-data"));
	curl_setopt($header, CURLOPT_URL, $url); 
	curl_setopt($header, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt($header, CURLOPT_POSTFIELDS, $post); 
	curl_exec($header);
}

?>