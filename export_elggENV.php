<?php
session_start();
$param = $_SESSION["param"];
$exportVariable = $_GET["export"];

include "GA_backend.php";
include "DB_backend.php";
	
$elggENV = new elggENV_Analytics_Object();

$elggENV->setTimeSpan($_SESSION["startdate"], $_SESSION["end_date"]);
$elggENV->setFilter($_SESSION["filter"]);

if($_SESSION["value"] != ""){
	$elggENV->setPageName($_SESSION["name"]);
} else {
	$gcpedia->setPageName("All of " . getGroup());
}
$elggENV->setPrecision($_SESSION["precision"]);
$elggENV->setGUID($_SESSION["guid"]);

function connectDatabase(){
	// Connect to static SQL database
	global $db_Object;
	$db_Object = new Database_Object(new mysqli('IP ADDRESS','USERNAME','PASSWORD','DATABASE'));
}

function membersByDepartment(){
	global $db_Object;
	global $elggENV;
	
	if($elggENV->getPrecision() == "/groups/profile/"){
		$data = $db_Object->getPieGraphData($elggENV->getGUID());
		return $data;
	} else {
		return [];
	}
}

function membersByDate(){
	global $db_Object;
	global $elggENV;
	
	if($elggENV->getPrecision() == "/groups/profile/"){
		$data = $db_Object->getLineGraphData($elggENV->getGUID());
		return $data;
	} else {
		return [];
	}
}

function has_string_keys(array $array) {
  return count(array_filter(array_keys($array), 'is_string')) > 0;
}

function array2csv(array &$array, $header){
	if (count($array) == 0) {
		return null;
	}
	ob_start();
	$df = fopen("php://output", 'w');

	foreach ($header as $h) {
		fputcsv($df, $h);
	}

	global $exportVariable;
	if($exportVariable != 2){
		fputcsv($df, array_keys(reset($array)));
	}   
	foreach ($array as $row) {
		fputcsv($df, $row);
	}
	fclose($df);
	return ob_get_clean();
}

function download_send_headers($filename) {
    // disable caching
    $now = gmdate("D, d M Y H:i:s");
    //header("Expires: Tue, 03 Jul 2017 06:00:00 GMT");
    header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
    header("Last-Modified: {$now} GMT");

    // force download  
    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream");
    header("Content-Type: application/download");
	header('Content-type: application/csv');

    // disposition / encoding on response body
    header("Content-Disposition: attachment;filename={$filename}");
    header("Content-Transfer-Encoding: binary");
}

$printArray = array();

if($param == "groups"){
	switch ($exportVariable) {
		case -1:
			//code to be executed if n=label1;
			connectDatabase();
			$printArray = membersByDepartment();
			break;
		case 0:
			//code to be executed if n=label2;
			connectDatabase();
			$printArray = membersByDate();
			break;
		case 1:
			//code to be executed if n=label3;
			$printArray = $elggENV->getTimeSpanResults();
			break;
		case 2:
			//code to be executed if n=label3;
			array_push ($printArray, ["By City"],[]);
			$city = $elggENV->getLocationResults("city");
			$printArray[] = array_keys(reset($city));
			foreach($city as $temp){
				$printArray[] = $temp;
			}
			array_push ($printArray, [],["By Region"],[]);
			$region = $elggENV->getLocationResults("region");
			$printArray[] = array_keys(reset($region));
			foreach($region as $temp){
				$printArray[] = $temp;
			}
			break;
		default:
			//code to be executed if n is different from all labels;
			$printArray = $elggENV->getPageResults();
	}
}
else{
	switch ($exportVariable) {
		case 0:
			//code to be executed if n=label1;
			$printArray = $elggENV->getPageResults();
			break;
		case 1:
			//code to be executed if n=label2;
			$printArray = $elggENV->getTimeSpanResults();
			break;
		case 2:
			//code to be executed if n=label3;
			array_push ($printArray, ["By City"],[]);
			$city = $elggENV->getLocationResults("city");
			$printArray[] = array_keys(reset($city));
			foreach($city as $temp){
				$printArray[] = $temp;
			}
			array_push ($printArray, [],["By Region"],[]);
			$region = $elggENV->getLocationResults("region");
			$printArray[] = array_keys(reset($region));
			foreach($region as $temp){
				$printArray[] = $temp;
			}
			break;
		default:
			//code to be executed if n is different from all labels;
			$printArray = $elggENV->getPageResults();
	}
}

$filenamePages = "elggENV_export_data_". str_replace(" ", "_" , $elggENV->getPageName())."_". $_SESSION["startdate"] ."_to_".$_SESSION["end_date"]."_". date("Y-m-d") . ".csv";
download_send_headers($filenamePages);
echo array2csv($printArray, [[], ["------------------------------------------"],["elggENV Analytics Data"],[str_replace("_", " " , $elggENV->getPageName())], ["From: " . $_SESSION["startdate"] ." to ".$_SESSION["end_date"]], ["------------------------------------------"], []]);
?>

