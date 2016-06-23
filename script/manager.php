<?php
//creates a new entry in the ptbDatabese: chatid|language|person|state|puzzleid|hint
function newEntry($userId) {
	ptb_add('data.csv', 'L', $userId . '|2|0|0|0|0');
}

//deletes a entry based on the chatid
function deleteEntry($userId) {
	ptb_delete('data.csv', 'L', "'chatid' == $userId");
}

//updates a entry
function updateEntry($userId, $name, $value) {
	ptb_update('data.csv', 'L', "'chatid' == $userId", "'$name' ='$value'");
}

function getAnswerSnipet($person, $correct, $language){
	$responseLang = ptb_connect('lang/response.csv', 'L');
	
	if($person == 1){//layton
		if($correct){//corect answer
			$selcted = 'layton_r'.sprintf("%02d", rand(1, 29));
			$langResponse = ptb_select($responseLang, "isThere('Person', '$selcted')");
		}else{//wrong answer
			$selcted = 'layton_w'.sprintf("%02d", rand(1, 27));
			$langResponse = ptb_select($responseLang, "isThere('Person', '$selcted')");
		}
	}else if($person == 2){//luke
		if($correct){//corect answer
			$selcted = 'luke_r'.sprintf("%02d", rand(1, 27));
			$langResponse = ptb_select($responseLang, "isThere('Person', '$selcted')");
		}else{//wrong answer
			$selcted = 'luke_w'.sprintf("%02d", rand(1, 27));
			$langResponse = ptb_select($responseLang, "isThere('Person', '$selcted')");
		}
	}
	if ($language == 0){
		return $langResponse[0]['English'];
	}else if($language == 1){
		return $langResponse[0]['German'];
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
	$interfaceLang = ptb_connect('lang/interface.csv', 'L');
	
	if((is_array($identifier) or ($identifier instanceof Traversable))) {
		//checks if array(button)
		foreach ($identifier as &$value) {
			foreach ($value as &$subvalue) {//opens array inside the array
				$langfound = ptb_select($interfaceLang, "isThere('identifier', '$subvalue')");//get the entry for the identifier
				
				if($langfound != null){
					if ($language == 0) {
						$subvalue = str_replace("\\n", "\n" ,$langfound[0]['English']);//returns english version and replaces \\n with \n
					}
					else if ($language == 1) {
						$subvalue = str_replace("\\n", "\n" ,$langfound[0]['German']);//returns german version and replaces \\n with \n
					}else if ($language == 2){
						$subvalue = str_replace("\\n", "\n" ,$langfound[0]['Emoji']);//returns emoji version and replaces \\n with \n
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
		$langfound = ptb_select($interfaceLang, "isThere('identifier', '$identifier')");
		if($langfound != null){
			if ($language == 0) {
				return str_replace("\\n", "\n" ,$langfound[0]['English']);
			}
			else if ($language == 1) {
				return str_replace("\\n", "\n" ,$langfound[0]['German']);
			}
			else if ($language == 2){
				return str_replace("\\n", "\n" , $langfound[0]['Emoji']);
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