<?php

function newEntry($userId) {
	ptb_add('data.csv', 'L', $userId . '|0|0|0|0|0');
}

function deleteEntry($userId) {
	ptb_delete('data.csv', 'L', "'chatid' == $userId");
}

function updateEntry($userId, $name, $value) {
	ptb_update('data.csv', 'L', "'chatid' == $userId", "'$name' ='$value'");
}

?>