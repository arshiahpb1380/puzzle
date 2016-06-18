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

function getLang($identifier, $language){
	
	//loads language file
	$interfaceLang = ptb_connect('lang/interface.csv', 'L');
	
	if((is_array($identifier) or ($identifier instanceof Traversable))) {
		//checks if array(button)
		foreach ($identifier as &$value) {
			foreach ($value as &$subvalue) {//opens array inside the array
				$langfound = ptb_select($interfaceLang, "isThere('identifier', '$subvalue')");//get the entry for the identifier
				if ($language == 0) {
					$subvalue = str_replace("\\n", "\n" ,$langfound[0]['English']);//returns english version and replaces \\n with \n
				}
				else if ($language == 1) {
					$subvalue = str_replace("\\n", "\n" ,$langfound[0]['German']);//returns german version and replaces \\n with \n
				}else if ($language == 2){
					$subvalue = str_replace("\\n", "\n" ,$langfound[0]['Emoji']);//returns emoji version and replaces \\n with \n
				}else{
					$subvalue = "ERROR";//returns error if the identifier doesn't exist
				}
			}
		}
		return $identifier;
	}else{//same thing if its not an array(normal text)
		$langfound = ptb_select($interfaceLang, "isThere('identifier', '$identifier')");
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
			return "ERROR";
		}
	}
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
?>