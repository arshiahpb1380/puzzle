<?php
//selects randomly a new puzzle
function newPuzzle(){

$puzzleAmount = countFile('puzzles.csv'); 

$newPuzzleID = mt_rand(1, $puzzleAmount);

return $newPuzzleID;
}
//gets the puzzle Type
function getPuzzleType($id){

	
	$found = getEntry('ID', $id, 'puzzles.csv');
	
	return $found['Type'];
}
//gets the Puzzle Solution
function getPuzzleSolution($id){
	
	$found = getEntry('ID', $id, 'puzzles.csv');
	
	return $found['Solution'];
}
//get a string between 2 other strings. Taken from: http://www.justin-cook.com/
function get_string_between($string, $start, $end){
    $string = " " . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}
//get data from mediaWiki Api
function getApiResponse($id){
	
	$found = getEntry('ID', $id, 'puzzles.csv');
	
	$url = 'http://layton.wikia.com/api.php?format=json&action=parse&prop=wikitext&pageid='.$found['English'];
	$response = file_get_contents($url);
	
	$content = json_decode($response, true)['parse'];
	
	return $content;
}

//parse response
function getPuzzleImage($response){
	$nResponse = str_replace("[[","",$response);
	$nResponse = str_replace("]]","",$nResponse);
	$nResponse = str_replace("\n","",$nResponse);
	$nResponse = str_replace("File:","",$nResponse);
	
	$a = get_string_between($nResponse, '|image', '|');
	
	if (strpos($a, 'width') !== false) {
		return str_replace("S.",".",getSolutionImage($response));
	}
	$a = str_replace("=","",$a);
	$a = str_replace(" ","",$a);
	if ($a == "") return str_replace("S.",".",getSolutionImage($response));
	return $a;
}
function getPuzzleText($response){
	//filter
	$response = str_replace("<[[","_",$response);
	$response = str_replace("<]]","_",$response);
	
	$a = get_string_between($response, '|puzzle', '|');
	$b = get_string_between($response, '==Puzzle==', '==');
	if($b=="")return substr($a, strpos($a, '=')+1);
	return $b;
}
function getPuzzleHint($response, $hint){
	$a = get_string_between($response, '|hint'. $hint, '|');
	$b = get_string_between($response, '|'. $hint, '|');
	$c = get_string_between($response, '|'. $hint, '}');
	if($a != "")return substr($a, strpos($a, '=')+1);
	if (strlen($b)<strlen($c)) return substr($b, strpos($b, '=')+1);
	return substr($c, strpos($c, '=')+1);
}
function getPuzzleCorrect($response){
	$a = get_string_between($response, '|correct', '<');
	$b = get_string_between($response, '|correct', '[');
	$c = get_string_between($response, '===Correct===', PHP_EOL);
	if($c != "")return $c;
	if($a != "")return substr($a, strpos($a, '=')+1);
	if($b != "")return substr($b, strpos($b, '=')+1);
}
function getPuzzleIncorrect($response){
	$a = get_string_between($response, '|incorrect', '|');
	$b = get_string_between($response, '===Incorrect===', PHP_EOL);
	if($b=="")return substr($a, strpos($a, '=')+1);
	return $b;
}
function getSolutionImage($response){
	$a = get_string_between($response, '>[[Image:', ']]');
	$b = get_string_between($response, '[[Image:', '|center]]');
	$c = get_string_between($response, '[[File:', '|center]]');
	$d = get_string_between($response, '[[File:', '|256px]]');
	$e = get_string_between($response, '>[[File:', '|]]');
	
	$imageName = "";
	if((substr_count($response, '[[Image:')+ substr_count($response, '[[File:')) >= 2){
		if ($e != "")$imageName = $e;
		if ($d != "")$imageName = $d;
		if ($c != "")$imageName = $c;
		if ($a != "")$imageName = $a;
		if ($b != "")$imageName = $b;
	}else{
		if ($e != "")$imageName = $e;
		if ($a != "")$imageName = $a;
		if ($d != "")$imageName = $d;
		if ($c != "")$imageName = $c;
		if ($b != "")$imageName = $b;
	}
	
	return $imageName;
}

function encodeImageURL($filename){
	$apiURL = 'http://layton.wikia.com/api.php?action=query&format=txt&titles=File:' . $filename . '&prop=imageinfo&iiprop=url';
	$apiResponse = file_get_contents($apiURL);
	$imageURL = get_string_between($apiResponse, '[url] => ', PHP_EOL);
	
	return $imageURL;
}

function puzzleHandler($chat_id, $userMsg){
	$found = getEntry('chatid', $chat_id, 'data.csv');
	
	$language = $found['language'];

	
	if ($found['state'] == 4){
		if($userMsg != "Yes" and $userMsg != "Ja"){
			sendKeyboardMessage($chat_id, "info_ready", $language, array(array('btn_yes'),array('btn_settings')));
		}else{
			updateEntry($chat_id, "state", "6");
			
			sendChatAction($chat_id, 'upload_image');
			
			if($found['puzzleid'] == 0){
				$puzzleId = newPuzzle();
				updateEntry($chat_id, "puzzleid", $puzzleId);
				sendPuzzle($chat_id, $puzzleId, $language);
				
			}else{
				$puzzleId = $found['puzzleid'];
				sendPuzzle($chat_id, $puzzleId, $language);
			}
		}
	}else if($found['state'] == 6){
		$puzzleId = $found['puzzleid'];
		$response = getApiResponse($puzzleId);
		
		//filter
		$wikitext = str_replace("<br />", "\n", $response['wikitext']['*']);
		$wikitext = str_replace("<br/>", "\n", $wikitext);
		$wikitext = str_replace("{{P}}", "\n", $wikitext);
		$wikitext = str_replace("{{p}}", "\n", $wikitext);
		$wikitext = str_replace("<u>></u>", "≥", $wikitext);
		$wikitext = str_replace("<u><</u>", "≤", $wikitext);
		
		if($userMsg == getPuzzleSolution($puzzleId)){//right answer
			sendChatAction($chat_id, 'upload_video');
			
			$person = $found['person'];
			if ($person == 0){
				$person = rand(1, 2);
			}
			
			
			$puzzleResponse = getPuzzleCorrect($wikitext);
			if($person == 1){
				sendVideo($chat_id, 'videos/' . 'LaytonR.mp4');
			}else if($person == 2){
				sendVideo($chat_id, 'videos/' . 'LukeR.mp4');
			}
			sendChatAction($chat_id, 'typing');
			sleep(2.5);
			if($puzzleResponse == ""){
				sendMarkdownMessage($chat_id, '_'.getAnswerSnipet($person, true, $language).'_', 99);
				sleep(1);
			}else {
				sendMarkdownMessage($chat_id, '_'.getAnswerSnipet($person, true, $language).'_', 99);
				sleep(1);
				sendMessage($chat_id, $puzzleResponse, 99);
			}
			
			sendChatAction($chat_id, 'upload_image');
			
			$picDir = 'images';
		
			
			
			$imageName = getSolutionImage($wikitext);
			if (!file_exists($picDir . "/" . $imageName)){
				$imageURL = encodeImageURL($imageName);
			}
			
			if (!file_exists($picDir . "/" . $imageName)){
				$currentImageName = substr(strrchr($imageURL, '/'), 1);
				if(!file_exists($picDir)){
					exec("mkdir $picDir");
				}
				exec("cd $picDir && wget --quiet $imageURL && mv $currentImageName $imageName");
			}
			sendImage($chat_id, $picDir . '/' . $imageName);
			
			updateEntry($chat_id, "puzzleid", 0);
			updateEntry($chat_id, "state", 4);
			sendKeyboardMessage($chat_id, "info_ready", $language, array(array('btn_yes'),array('btn_settings')));
		}else if($userMsg == "I may need some help" or $userMsg == "Ich brauche etwas hilfe"){//HELP
			$puzzleId = $found['puzzleid'];
			
			$btnHelp = getLang("btn_help", $language);
			
			$puzzleType = getPuzzleType($puzzleId);
			
			$keyboard = array(array($btnHelp));
			
			if($puzzleType == "A-B"){
				$keyboard = array(array('A'), array('B'), array($btnHelp));
			}
			else if($puzzleType == "A-C"){
				$keyboard = array(array('A'), array('B'), array('C'), array($btnHelp));
			}
			else if($puzzleType == "A-D"){
				$keyboard = array(array('A', 'B'), array('C', 'D'), array($btnHelp));
			}
			else if($puzzleType == "A-E"){
				$keyboard = array(array('A', 'B'), array('C', 'D', 'E'), array($btnHelp));
			}
			else if($puzzleType == "A-F"){
				$keyboard = array(array('A', 'B', 'C'), array('D', 'E', 'F'), array($btnHelp));
			}
			sendKeyboardMessage($chat_id, "Not implemented :(", 99, $keyboard);
			
		}else{
			//wrong answer
			sendChatAction($chat_id, 'upload_video');
			
			$puzzleId = $found['puzzleid'];
			
			$btnHelp = getLang("btn_help", $language);
			
			$puzzleType = getPuzzleType($puzzleId);
			
			$keyboard = array(array($btnHelp));
			
			if($puzzleType == "A-B"){
				$keyboard = array(array('A'), array('B'), array($btnHelp));
			}
			else if($puzzleType == "A-C"){
				$keyboard = array(array('A'), array('B'), array('C'), array($btnHelp));
			}
			else if($puzzleType == "A-D"){
				$keyboard = array(array('A', 'B'), array('C', 'D'), array($btnHelp));
			}
			else if($puzzleType == "A-E"){
				$keyboard = array(array('A', 'B'), array('C', 'D', 'E'), array($btnHelp));
			}
			else if($puzzleType == "A-F"){
				$keyboard = array(array('A', 'B', 'C'), array('D', 'E', 'F'), array($btnHelp));
			}
			
			$person = $found['person'];
			if ($person == 0){
				$person = rand(1, 2);
			}
			
			$puzzleResponse = getPuzzleIncorrect($wikitext);
			if($person == 1){
				sendVideo($chat_id, 'videos/' . 'LaytonW.mp4');
			}else if($person == 2){
				sendVideo($chat_id, 'videos/' . 'LukeW.mp4');
			}
			sendChatAction($chat_id, 'typing');
			sleep(2.5);
			if($puzzleResponse == " "){
				sendKeyboardMarkdownMessage($chat_id, '_'.getAnswerSnipet($person, false, $language).'_', 99, $keyboard);
			}else {
				sendKeyboardMarkdownMessage($chat_id, '_'.getAnswerSnipet($person, false, $language).'_', 99, $keyboard);
				sendKeyboardMessage($chat_id, $puzzleResponse, 99, $keyboard);
			}
		}
	}
}

function sendPuzzle($chat_id, $puzzleId, $language){
			$response = getApiResponse($puzzleId);
			
			$title = substr($response['title'], 7);
			
			$picDir = 'images';
			
			
			//filter
			$wikitext = str_replace("<br />", "\n", $response['wikitext']['*']);
			$wikitext = str_replace("<br/>", "\n", $wikitext);
			$wikitext = str_replace("{{P}}", "\n", $wikitext);
			$wikitext = str_replace("{{p}}", "\n", $wikitext);
			$wikitext = str_replace("<u>></u>", "≥", $wikitext);
			$wikitext = str_replace("<u><</u>", "≤", $wikitext);
			
			
			$content = getPuzzleText($wikitext);
			$imageName = getPuzzleImage($wikitext);
			if (!file_exists($picDir . "/" . $imageName)){
				$imageURL = encodeImageURL($imageName);
			}
			$puzzleType = getPuzzleType($puzzleId);
			
			
			
			
			$btnHelp = getLang("btn_help", $language);
			
			$keyboard = array(array($btnHelp));
			
			if($puzzleType == "A-B"){
				$keyboard = array(array('A'), array('B'), array($btnHelp));
			}
			else if($puzzleType == "A-C"){
				$keyboard = array(array('A'), array('B'), array('C'), array($btnHelp));
			}
			else if($puzzleType == "A-D"){
				$keyboard = array(array('A', 'B'), array('C', 'D'), array($btnHelp));
			}
			else if($puzzleType == "A-E"){
				$keyboard = array(array('A', 'B'), array('C', 'D', 'E'), array($btnHelp));
			}
			else if($puzzleType == "A-F"){
				$keyboard = array(array('A', 'B', 'C'), array('D', 'E', 'F'), array($btnHelp));
			}
			
		if (!file_exists($picDir . "/" . $imageName)){
			$currentImageName = substr(strrchr($imageURL, '/'), 1);
			if (!file_exists($picDir)){
				exec("mkdir $picDir");
			}
			exec("cd $picDir && wget --quiet $imageURL && mv $currentImageName $imageName");
				
			
		}
		
		sendMarkdownMessage($chat_id, "*" . $title . ":*", 99);
		sendImage($chat_id, $picDir . '/' . $imageName);
		sendKeyboardMessage($chat_id, $content, 99, $keyboard);	
}
?>