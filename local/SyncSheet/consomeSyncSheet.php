<?php
require_once'SyncSheet.php';

$idAttendance = 1;
$sync = new ScoreSync('2');

$spreadsheetId = "11OUkfHXXdPuL-N2vIs1HweDdXUoBmNXwryPGpaIDVj0";

$values = $sync->getSheet($spreadsheetId);
print_r($values);
$sync->insertSheet($values);

?>
 

