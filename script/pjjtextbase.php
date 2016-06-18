<?php
/******************************************************************************\
* pjjTextBase                                  Version 1.2                     *
* Author: Przemyslaw Jerzy Jackowski           E-Mail: ptb@pjj.pl              *
* Created  07/24/2006                          Last Modified 06/20/2007        *
* Scripts Archive at:                          http://www.pjj.pl/pjjtextbase/  *
* Script License: GPL 2 or any later version                                   *
*******************************************************************************/

ob_start();
define('PTB_VERSION', '1.2');
if (file_exists(dirname(__FILE__) . '/ptb_ini.php')) {
  require_once dirname(__FILE__) . '/ptb_ini.php';
}
if (!defined('PTB_DEFAULT_DB_LOCATION')) {
  define('PTB_DEFAULT_DB_LOCATION', 'G');
}
if (!defined('PTB_PATH_DB')) {
  define('PTB_PATH_DB', '/db');
}
if (!defined('PTB_FILE_DB_PREFIX')) {
  define('PTB_FILE_DB_PREFIX', '');
}
if (!defined('PTB_NEWLINE')) {
  define('PTB_NEWLINE', "\n");
}
if (!defined('PTB_DEFAULT_SECURITY')) {
  define('PTB_DEFAULT_SECURITY', 1);
}
if (!defined('PTB_SEC_STR')) {
  define('PTB_SEC_STR', "<?php die('Access denied!');?>");
}
if (!defined('PTB_SHOW_ERRORS')) {
  define('PTB_SHOW_ERRORS', true);
}
if (!defined('PTB_BOM')) {
  define('PTB_BOM', false);
}
if (!defined('PIPE')) {
  define('PIPE', '&pipe;');
}
// -----------------------------------------------------[ internal functions ]--
function ptb_internal_error($errType, $errName, $var = '', $var2 = '')
{
  if (strcmp('4.3.0', phpversion()) > 0) {
    return;
  } else if (PTB_SHOW_ERRORS) {
    $debug_array = debug_backtrace();
    $debug_cnt = ($errName == 'doubleEqualSignExpectedSub') ? 3 : 2;
    if ($debug_array[$debug_cnt - 1]['file'] != __FILE__) {
      $file = file($debug_array[$debug_cnt - 1]['file']);
      echo '<pre style="clear:both;margin:0 50px;padding:0 20px 20px;background-color:';
      switch ($errType) {
        case 0:
          echo '#f99;';
          break;
        case 1:
          echo '#9bf;';
          break;
        case 2:
          echo '#ffc;';
          break;
      }
      echo '"><br /><u>';
      switch ($errType) {
        case 0:
          echo 'Syntax error';
          break;
        case 1:
          echo 'File error (read/write or filesystem)';
          break;
        case 2:
          echo 'Database structure error';
          break;
      }
      echo '</u> called from<br />&nbsp;&nbsp;&nbsp;<strong>', $debug_array[$debug_cnt - 1]['function'], '</strong><br />in file<br />&nbsp;&nbsp;&nbsp;<strong>', $debug_array[$debug_cnt - 1]['file'], '</strong><br />on line no.<br />&nbsp;&nbsp;&nbsp;<strong>', $debug_array[$debug_cnt - 1]['line'], '</strong>: ', trim($file[$debug_array[$debug_cnt - 1]['line'] - 1]), '<br />message:<br />&nbsp;&nbsp;&nbsp;';
      switch ($errName) {
      case 'tooLittleArguments':
        echo "this function needs <strong>$var</strong> arguments!";
      break;
      case 'singleQuoteExpected':
        echo "error in \$update: <strong>single quote</strong> expected!";
      break;
      case 'equalSignExpected':
        echo "error in \$condition: <strong>equal sign</strong> expected!";
      break;
      case 'doubleEqualSignExpected':
      case 'doubleEqualSignExpectedSub':
        echo "error in \$condition: <strong>double equal sign</strong> expected!";
      break;
      case 'assignmentOperatorExpected':
        echo "error in \$update: <strong>single equal sign</strong> expected!";
      break;
      case 'fileDoesntExist':
        echo "file <strong>$var</strong> does not exist!";
        break;
      case 'cantOpenFile':
        echo "can't open <strong>$var</strong> for writing!";
        break;
      case 'cantWriteToFile':
        echo "can't write to file <strong>$var</strong>!";
        break;
      case 'fieldnameAlreadyExists':
        echo "this field already exists in <strong>$var</strong>";
        break;
      case 'noSuchFieldname':
        echo "field <strong>$var</strong> does not exist in $var2";
        break;
      case 'notADatabase':
        echo "variable used as the first argument is not a database!";
        break;
      }
      echo '</pre><br />';
    }
  }
  if ($errType == 0) {
    die('PTB fatal error. Exiting...');
  }
}
function ptb_internal_write($filename, $location, $header, $data)
{
  $filename = ptb_internal_set_filename($filename, $location);
  $record = '';
  if (file_exists($filename) AND !is_writable($filename)) {
    ptb_internal_error(1, 'cantOpenFile', $filename);
    return;
  }
  if ((PTB_BOM == true) AND ($header[0] == 1)) {
    $strBuffer = chr(hexdec('ef')) . chr(hexdec('bb')) . chr(hexdec('bf'));
  } else {
    $strBuffer = '';
  }
  if ($header[1] == 1) {
    $strBuffer .= PTB_SEC_STR;
  }
  if ($header[2] == 1) {
    $keys = ptb_fieldnames($data);
    if ($header[1] == 1) {
      $strBuffer .= PTB_NEWLINE;
    }
    $strBuffer .= implode('|', $keys);
  }
  if (is_array($data)) {
    foreach ($data as $key => $value) {
      $strBuffer .= PTB_NEWLINE . implode('|', $data[$key]);
    }
  } else {
    if ($header == '100') {
      $strBuffer .= $data;
    } else {
      $strBuffer .= PTB_NEWLINE . $data;
    }
  }
  ignore_user_abort(true);
  if ($header[0] == 1) {
    if (!($fp = fopen($filename, "wb"))) {
      ptb_internal_error(1, 'cantOpenFile', $filename);
      return;
    }
  } else {
    if (!($fp = fopen($filename, "ab"))) {
      ptb_internal_error(1, 'cantOpenFile', $filename);
      return;
    }
  }
  flock($fp, LOCK_EX);
  if (!fwrite($fp, $strBuffer)) {
    ptb_internal_error(1, 'cantWriteToFile', $filename);
    return;
  }
  flock($fp, LOCK_UN);
  fclose($fp);
  ignore_user_abort(false);
  return true;
}
function ptb_internal_update($record, $record_number, $update)
{
  for ($i = 0, $c = count($update); $i < $c; $i++) {
    $record[$update[$i][0]] = $update[$i][1];
  }
  return $record;
}
function ptb_internal_set_filename($filename, $location)
{
  switch ($location) {
  case 'L':
    $filename = dirname($_SERVER['DOCUMENT_ROOT'] . $_SERVER['PHP_SELF']) . '/' . $filename;
    break;
  case 'G':
    $filename = $_SERVER['DOCUMENT_ROOT'] . PTB_PATH_DB . '/' . $filename;
    break;
  case 'F':
    $filename = $filename;
    break;
  default:
    $filename = $_SERVER['DOCUMENT_ROOT'] . $location . '/' . $filename;
  }
  return $filename;
}
function ptb_internal_set_condition($database, $condition)
{
  if ((substr_count($condition, '=') == '1') AND (substr_count($condition, '!') == '0') AND (substr_count($condition, '>') == '0') AND (substr_count($condition, '<') == '0')) {
    ptb_internal_error(0, 'doubleEqualSignExpectedSub');
  }
  $fieldnames = ptb_fieldnames($database);
  $condition = '(' . $condition . ')';
  for ($j = 0, $c = count($fieldnames); $j < $c; $j++) {
    $condition = str_replace('\'' . $fieldnames[$j] . '\'', '$database[$i][\'' . $fieldnames[$j] . '\']', $condition);
  }
  return $condition;
}
function ptb_internal_columns_equal($database1, $field1, $database2, $field2)
{
  foreach ($database1 as $value) {
    $column1[][$field1] = $value[$field1];
  }
  foreach ($database2 as $value) {
    $column2[][$field2] = $value[$field2];
  }
  $commonValuesInBothColumns = array_intersect($column1, $column2);
  if (ptb_count($column1) == ptb_count($commonValuesInBothColumns)) {
    return true;
  }
}
// -----------------------------------------------------[ external functions ]--
function ptb_connect($filename, $location = PTB_DEFAULT_DB_LOCATION, $recursive = true, $keyField = '')
{
  $numargs = func_num_args();
  if ($numargs == 3) {
    if ((func_get_arg(2) === true) OR (func_get_arg(2) === false)) {
      $recursive = func_get_arg(2);
      $keyField = '';
    } else {
      $recursive = true;
      $keyField = func_get_arg(2);
    }
  }
  $filename = ptb_internal_set_filename($filename, $location);
  if (!file_exists($filename)) {
    ptb_internal_error(1, 'fileDoesntExist', $filename);
    return;
  }
  $f_database = file($filename);
  if ((dechex(ord($f_database[0][0])) == 'ef') AND (dechex(ord($f_database[0][1])) == 'bb') AND (dechex(ord($f_database[0][2])) == 'bf')) {
    $f_database[0] = substr($f_database[0], 3);
  }
  $secure = (rtrim($f_database[0]) == PTB_SEC_STR) ? 1 : 0;
  if ((!$secure AND count($f_database) < 1) OR ($secure AND count($f_database) < 2)) {
    return;
  }
  $fieldnames = explode('|', rtrim($f_database[$secure]));
  $fieldnamesCount = count($fieldnames);
  $recordNo = 0;
  $keyFieldNumber = '';
  for ($i = 0; $i < $fieldnamesCount; $i++) {
    if ($fieldnames[$i] == $keyField) {
      $keyFieldNumber = $i;
      break;
    }
  }
  if (($keyFieldNumber === '') AND ($keyField != '')) {
    ptb_internal_error(2, 'noSuchFieldname', $keyField, $filename);
  }
  $database = array();
  for ($i = 1 + $secure, $c = count($f_database); $i < $c; $i++) {
    if (($f_database[$i] != PTB_NEWLINE) AND ($f_database[$i][0] != '#')) {
      $lineToSplit = explode('|', rtrim($f_database[$i]));
      for ($j = 0; $j < $fieldnamesCount; $j++) {
        if ($keyField != '') {
          $thisRecordKey = $lineToSplit[$keyFieldNumber];
          $database[$thisRecordKey][$fieldnames[$j]] = $lineToSplit[$j];
        } else {
          $database[$recordNo][$fieldnames[$j]] = $lineToSplit[$j];
        }
      }
      $recordNo++;
    }
  }
  if (!$recursive) {
    return $database;
  }
  $linkFields = array(); $j = 0;
  for ($i = 0; $i < $fieldnamesCount; $i++) {
    if (($fieldnames[$i][0] == '@') OR ($fieldnames[$i][0] == '%')) {
      $linkFields[$j]['type'] = substr($fieldnames[$i], 0, 1);
      $linkFields[$j]['name'] = substr($fieldnames[$i], 1, strlen($fieldnames[$i]));
      $j++;
    }
  }
  if (count($linkFields) == 0) {
    return $database;
  }
  $cn_record = count($database);
  for ($i = 0, $c = count($linkFields); $i < $c; $i++) {
    $ext = explode('.', $filename);
    $ext = (count($ext) > 1) ? $ext[count($ext) - 1] : '';
    if (file_exists(ptb_internal_set_filename(PTB_FILE_DB_PREFIX . $linkFields[$i]['name'] . '.' . $ext, $location))) {
      $linkedTable = ptb_connect(PTB_FILE_DB_PREFIX . $linkFields[$i]['name'] . '.' . $ext, $location, $recursive, '');
    } else {
      ptb_internal_error(1, 'fileDoesntExist', PTB_FILE_DB_PREFIX . $linkFields[$i]['name'] . '.' . $ext);
      break;
    }
    $linkedTableFields = ptb_fieldnames($linkedTable);
    $ccc = count($linkedTableFields);
    if ($linkFields[$i]['type'] == '@') {
      foreach ($database as $key => $value) {
        for ($k = 1; $k < $ccc; $k++) {
          foreach ($linkedTable as $key1 => $value1) {
            $linked_file_field_value = '';
            if ($linkedTable[$key1]['id'] == $database[$key]['@' . $linkedTableFields[1]]) {
              $linked_file_field_value = $linkedTable[$key1][$linkedTableFields[$k]];
              break;
            }
          }
          $database[$key][$linkedTableFields[$k]] = $linked_file_field_value;
        }
      }
    } else {
      foreach ($database as $key => $value) {
        if ($database[$key]['%' . $linkFields[$i]['name']] != '') {
          $multiFieldValue = explode(',', $database[$key]['%' . $linkFields[$i]['name']]);
          for ($k = 0, $cc = count($multiFieldValue); $k < $cc; $k++) {
            foreach ($linkedTable as $key1 => $value1) {
              if ($multiFieldValue[$k] == $linkedTable[$key1]['id']) {
                $linked_file_field_value = $linkedTable[$key1][$linkFields[$i]['name']];
                break;
              }
            }
            if (!empty($linked_file_field_value)) {
              $database[$key][$linkFields[$i]['name']][$k] = $linked_file_field_value;
            }
          }
        } else {
          $database[$key][$linkFields[$i]['name']] = '';
        }
      }
    }
  }
  return $database;
}
function ptb_count($database, $condition = '')
{
  if (is_array($database)) {
    if (empty($condition)) {
      return count($database);
    } else {
      $j = 0;
      $condition = ptb_internal_set_condition($database, $condition);
      foreach ($database as $i => $value) {
        eval("if ($condition) {\$j++;}");
      }
      return $j;
    }
  } else {
    return 0;
  }
}
function ptb_create($filename, $location, $fieldnamesLine, $secure = PTB_DEFAULT_SECURITY)
{
  $numargs = func_num_args();
  if ($numargs < 3) {
    ptb_internal_error(0, 'tooLittleArguments', 'at least 3');
  } else {
    if (ptb_internal_write($filename, $location, '1'.$secure.'0', $fieldnamesLine)) {
      return true;
    } 
  }
}
function ptb_write($filename, $location, $table, $secure = PTB_DEFAULT_SECURITY)
{
  $numargs = func_num_args();
  if ($numargs < 3) {
    ptb_internal_error(0, 'tooLittleArguments', 'at least 3');
  } else {
    if (ptb_internal_write($filename, $location, '1'.$secure.'1', $table)) {
      return true;
    } 
  }
}
function ptb_add($filename, $location, $fieldValues)
{
  if (!file_exists(ptb_internal_set_filename($filename, $location))) {
    ptb_internal_error(1, 'fileDoesntExist', ptb_internal_set_filename($filename, $location));
    return;
  } else {
    if (is_array($fieldValues)) {
      $fieldValues = implode('|', $fieldValues);
    }
    if (ptb_internal_write($filename, $location, '000', $fieldValues)) {
      return true;
    }
  }
}
function ptb_sort($database, $sort)
{
  $numargs = func_num_args();
  if ($numargs != 2) {
    ptb_internal_error(0, 'tooLittleArguments', '2');
  } else {
    $sort = preg_replace('/(\s*,\s*)|\s+/', ';', $sort);
    $sort = explode(';', $sort);
    if (count($sort) & 1) {
      $sort[count($sort)] = 'ASC';
    }
    for ($i = 0, $c = count($sort); $i < $c; $i++) {
      eval("foreach (\$database as \$key => \$row) {\$$sort[$i][\$key] = \$row['$sort[$i]'];}");
      $i++;
    }
    $sortby = '';
    for ($i = 0, $c = count($sort)/2; $i < $c; $i++) {
      $sortby .= '$' . $sort[($i*2)] . ', SORT_' . $sort[($i*2 + 1)] . ',';
    }
    eval("array_multisort($sortby \$database);");
    return $database;
  }
}
function ptb_select($database, $condition, $sort = '', $limit = '')
{
  $numargs = func_num_args();
  if ($numargs < 2) {
    ptb_internal_error(0, 'tooLittleArguments', 'at least 2');
  } else {
    if (ptb_count($database) == 0) return;
    $condition = ptb_internal_set_condition($database, $condition);
    $result = '';
    $numArgs = func_get_args();
    if ((count($numArgs) == 3) AND (is_int($numArgs[2]))) {
      $limit = $numArgs[2];
      $sort = '';
    }
    if ((!isset($limit)) OR (!is_int($limit)) OR ($limit < 1) OR ($limit > ptb_count($database))) {
      $limit = ptb_count($database);
    }
    $j = 0;
    foreach ($database as $key => $value) {
      if ($j < $limit) {
        $i = $key;
        eval("if ($condition) {\$result[\$j] = \$database[\$key]; \$j++;}");
      }
    }
    if ($sort != '') {
      if (is_array($result)) {
        $result = ptb_sort($result, $sort);
      }
    }
    return $result;
  }
}
function ptb_delete($filename, $location, $condition)
{
  $numargs = func_num_args();
  if ($numargs != 3) {
    ptb_internal_error(0, 'tooLittleArguments', '3');
  } else {
    $database = ptb_connect($filename, $location, false);
    if (ptb_count($database) == 0) {
      return true;
    }
    $condition = ptb_internal_set_condition($database, $condition);
    $j = 0;
    $dataToRemain = array();
    for ($i = 0, $c = count($database); $i < $c; $i++) {
      eval("if (!$condition) {\$dataToRemain[\$j] = \$database[\$i]; \$j++;}");
    }
    if (count($dataToRemain) != count($database)) {
      if (count($dataToRemain) != 0) {
        if (ptb_internal_write($filename, $location, '1'.PTB_DEFAULT_SECURITY.'1', $dataToRemain)) {
          return true;
        }
      } else {
        if (ptb_internal_write($filename, $location, '1'.PTB_DEFAULT_SECURITY.'0', implode('|', ptb_fieldnames($database)))) {
          return true;
        }
      }
    } else {
      return true;
    }
  }
}
function ptb_update($filename, $location, $condition, $update)
{
  $numargs = func_num_args();
  if ($numargs != 4) {
    ptb_internal_error(0, 'tooLittleArguments', '4');
  } else {
    $database = ptb_connect($filename, $location, false);
    if (ptb_count($database) == 0) {
      return true;
    }
    $condition = ptb_internal_set_condition($database, $condition);
    $i = 0; $j = 0; $result = array();
    while (strlen($update) > 0) {
      $update = ltrim($update);
      if ($update[0] != '\'') {
        ptb_internal_error(0, 'singleQuoteExpected');
      } else {
        $update = substr($update, 1);
      }
      $pos = strpos($update, '\'');
      $result[$i][$j] = substr($update, 0, $pos);
      if (!ptb_fieldnames($database, $result[$i][$j])) {
        ptb_internal_error(2, 'noSuchFieldname', $result[$i][$j], $filename);
      }
      $update = ltrim(substr($update, $pos + 1));
      if ($update[0] != '=') {
        ptb_internal_error(0, 'equalSignExpected');
      } else {
        $update = ltrim(substr($update, 1));
      }
      if ($update[0] == '=') {
        ptb_internal_error(0, 'assignmentOperatorExpected');
      }
      if ($update[0] == '\'') {
        $update = substr($update, 1);
        $pos = strpos($update, '\'');
        $result[$i][$j+1] = substr($update, 0, $pos);
        $update = ltrim(substr($update, $pos + 1));
      } else {
        $pos = strpos($update, ',');
        $result[$i][$j+1] = substr($update, 0, $pos);
        $update = ltrim(substr($update, $pos));
      }
      $update = substr($update, 1);
      $i++;
      $j = 0;
    }
    $changeCounter = 0;
    for ($i = 0, $c = count($database); $i < $c; $i++) {
      eval("if ($condition) {\$database[\$i] = ptb_internal_update(\$database[\$i], \$i, \$result); \$changeCounter++;}");
    }
    if ($changeCounter > 0) {
      $f_database = file(ptb_internal_set_filename($filename, $location));
      $secure = (rtrim($f_database[0]) == PTB_SEC_STR) ? 1 : 0;
      if (!ptb_internal_write($filename, $location, '1'.PTB_DEFAULT_SECURITY.'1', $database)) {
        return;
      }
    }
    return true;
  }
}
function ptb_map($database, $equation, $otherFieldname = '')
{
  $numargs = func_num_args();
  if ($numargs < 2) {
    ptb_internal_error(0, 'tooLittleArguments', '2');
  } else {
    if (!ptb_fieldnames($database, $otherFieldname)) {
      ptb_internal_error(2, 'noSuchFieldname', $otherFieldname, 'this database');
    }
    $equation = explode('==', $equation);
    if (count($equation) != 2) {
      ptb_internal_error(0, 'doubleEqualSignExpected');
    }
    $tmp0 = trim($equation[0], " '");
    $tmp1 = trim($equation[1], " '");
    for ($i = 0, $c = ptb_count($database); $i < $c; $i++) {
      if ($database[$i][$tmp0] == $tmp1) {
        if (!empty($otherFieldname)) {
          return $database[$i][$otherFieldname];
        } else {
          return $database[$i];
        }
      }
    }
  }
}
function ptb_listUnique($database, $fieldname)
{
  $numargs = func_num_args();
  if ($numargs != 2) {
    ptb_internal_error(0, 'tooLittleArguments', '2');
  } else {
    if (ptb_count($database) == 0) {
      return;
    }
    $uniques = array();
    $k = 0;
    for ($i = 0, $c = ptb_count($database); $i < $c; $i++) {
      $isAlready = 0;
      for ($j = 0, $cc = count($uniques); $j < $cc; $j++) {
        if ($database[$i][$fieldname] == $uniques[$j]) {
          $isAlready = 1;
          break;
        }
      }
      if ($isAlready == 0) {
        $uniques[$k] = $database[$i][$fieldname];
        $k++;
      }
    }
    sort($uniques);
    return $uniques;
  }
}
function ptb_fieldnames($database, $fieldname = '')
{
  if (!is_array($database)) {
    ptb_internal_error(2, 'notADatabase');
    return;
  }
  $tmp = array_keys($database);
  $keys = array_keys($database[$tmp[0]]);
  if (empty($fieldname)) {
    return $keys;
  }
  for ($i = 0, $c = ptb_count($keys); $i < $c; $i++) {
    if ($keys[$i] == $fieldname) {
      return true;
    }
  }
  return;
}
function ptb_max($database, $fieldname)
{
  $numargs = func_num_args();
  if ($numargs != 2) {
    ptb_internal_error(0, 'tooLittleArguments', '2');
  } else {
    if (!ptb_fieldnames($database, $fieldname)) {
      ptb_internal_error(2, 'noSuchFieldName', $fieldname, 'this database');
    }
    $max = '';
    foreach ($database as $key => $value) {
      if (($database[$key][$fieldname]) > $max) {
        $max = $value[$fieldname];
      }
    }
    return $max;
  }
}
function ptb_min($database, $fieldname)
{
  $numargs = func_num_args();
  if ($numargs != 2) {
    ptb_internal_error(0, 'tooLittleArguments', '2');
  } else {
    if (!ptb_fieldnames($database, $fieldname)) {
      ptb_internal_error(2, 'noSuchFieldname', $fieldname, 'this database');
    }
    $min = '';
    foreach ($database as $key => $value) {
      if (empty($min)) {
        $min = $value[$fieldname];
      }
      if (($database[$key][$fieldname]) < $min) {
        $min = $value[$fieldname];
      }
    }
    return $min;
  }
}
function ptb_merge($database, $filename, $location = PTB_DEFAULT_DB_LOCATION, $keyField = '')
{
  $numargs = func_num_args();
  if ($numargs < 2) {
    ptb_internal_error(0, 'tooLittleArguments', 'at least 2');
  } else {
    if (!is_array($database)) {
      $database2 = ptb_connect($filename, $location, true, $keyField);
      if (!is_array($database2)) {
        return;
      } else {
        return $database2;
      }
    }
    $database2 = ptb_connect($filename, $location, true, $keyField);
    if (!is_array($database2)) {
      ptb_internal_error(2, 'notADatabase');
      return $database;
    }
    $commonKeys = array_intersect(ptb_fieldnames($database), ptb_fieldnames($database2));
    if (ptb_count($commonKeys) == 0) {
      return $database;
    }
    if (ptb_internal_columns_equal($database, $commonKeys[0], $database2, $commonKeys[0])) {
      $diffKeys = array_values(array_diff(ptb_fieldnames($database2), $commonKeys));
      for ($i = 0, $c = count($diffKeys); $i < $c; $i++) {
        foreach ($database as $key => $value) {
          $database[$key][$diffKeys[$i]] = $database2[$key][$diffKeys[$i]];
        }
      }
    }
    return $database;
  }
}
function ptb_append($database, $filename, $location = PTB_DEFAULT_DB_LOCATION, $recursive = true, $keyField = '')
{
  $numargs = func_num_args();
  if ($numargs < 2) {
    ptb_internal_error(0, 'tooLittleArguments', 'at least 2');
  } else if (!is_array($database)) {
    $database2 = ptb_connect($filename, $location, $recursive, $keyField);
    return $database2;
  } else {
    $keys = ptb_fieldnames($database);
    $database2 = ptb_connect($filename, $location, $recursive, $keyField);
    if (!is_array($database2)) {
      return $database;
    }
    $keys2 = ptb_fieldnames($database2);
    if (($keys[0] != 'id') OR ($keys2[0] != 'id') OR (!empty($keyField))) {
      return $database + $database2;
    } else {
      $maxId = ptb_max($database, 'id');
      for ($i = 0, $c = ptb_count($database2); $i < $c; $i++) {
        $database2[$i]['id'] = $maxId + $i + 1;
      }
      return array_merge($database, $database2);
    }
  }
}
function ptb_changeFieldname($filename, $location, $oldFieldname, $newFieldname)
{
  $numargs = func_num_args();
  if ($numargs != 4) {
    ptb_internal_error(0, 'tooLittleArguments', '4');
  } else {
    $table = ptb_connect($filename, $location, false);
    $fieldnames = ptb_fieldnames($table);
    if ((ptb_fieldnames($table, $oldFieldname)) AND (!ptb_fieldnames($table, $newFieldname))) {
      for ($i = 0, $c = ptb_count($fieldnames); $i < $c; $i++) {
        if ($fieldnames[$i] == $oldFieldname) {
          $fieldnames[$i] = $newFieldname;
          break;
        }
      }
      if (!ptb_internal_write($filename, $location, '1'.PTB_DEFAULT_SECURITY.'0', implode('|', $fieldnames))) {
        return;
      }
      if (ptb_internal_write($filename, $location, '000', $table)) {
        return true;
      }
    } else {
      if (!ptb_fieldnames($table, $oldFieldname)) {
        ptb_internal_error(2, 'noSuchFieldname', $oldFieldname, $filename);
      }
      if (ptb_fieldnames($table, $newFieldname)) {
        ptb_internal_error(2, 'fieldnameAlreadyExists', $filename);
      }
      return;
    }
  }
}
function ptb_addField($filename, $location, $newFieldname, $defaultValue = '')
{
  $numargs = func_num_args();
  if ($numargs < 3) {
    ptb_internal_error(0, 'tooLittleArguments', 'at least 3');
  } else {
    $table = ptb_connect($filename, $location, false);
    if (!ptb_fieldnames($table, $newFieldname)) {
      for ($i = 0, $c = ptb_count($table); $i < $c; $i++) {
        $table[$i][$newFieldname] = $defaultValue;
      }
    } else {
      ptb_internal_error(2, 'fieldnameAlreadyExists', $filename);
      return;
    }
    if (ptb_internal_write($filename, $location, '1'.PTB_DEFAULT_SECURITY.'1', $table)) {
      return true;
    }
  }
}
function ptb_delField($filename, $location, $fieldname)
{
  $numargs = func_num_args();
  if ($numargs != 3) {
    ptb_internal_error(0, 'tooLittleArguments', '3');
  } else {
    $table = ptb_connect($filename, $location, false);
    if (ptb_fieldnames($table, $fieldname)) {
      for ($i = 0, $c = ptb_count($table); $i < $c; $i++) {
        unset($table[$i][$fieldname]);
      }
      if (ptb_internal_write($filename, $location, '1'.PTB_DEFAULT_SECURITY.'1', $table)) {
        return true;
      }
    } else {
      ptb_internal_error(2, 'noSuchFieldname', $fieldname, $filename);
      return;
    }
  }
}
function isThere($needle, $haystack)
{
  if (!is_array($haystack)) {
    $haystack = explode(',', $haystack);
  }
  return in_array($needle, $haystack);
}
function isThereExt($haystack, $condition, $link = 'OR')
{
  $link = strtoupper($link);
  if (!is_array($haystack)) {
    $haystack = explode(',', $haystack);
  }
  $is = ($link == 'OR') ? false : true;
  for ($i = 0, $c = count($haystack); $i < $c; $i++) {
    $piece = $haystack[$i];
    if ($link == 'OR') {
      eval("if ($condition) {\$is = true;}");
      if ($is) {
        return $is;
      }
    } else {
      eval("if (!($condition)) {\$is = false;}");
      if (!$is) {
        return $is;
      }
    }
  }
  return $is;
}
?>