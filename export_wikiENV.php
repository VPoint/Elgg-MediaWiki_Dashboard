<?php
session_start();

$exportVariable = $_GET["export"];

include "GA_backend.php";
	
$wikiENV = new GCPedia_Analytics_Object();

$wikiENV->setTimeSpan($_SESSION["startdate"], $_SESSION["end_date"]);
$wikiENV->setLevel($_SESSION["level"]);
$wikiENV->setFilter($_SESSION["filter"]);

if($_SESSION["value"] != "/"){
	$wikiENV->setPageName($_SESSION["value"]);
} else {
	$wikiENV->setPageName("All of GCPedia");
}


function has_string_keys(array $array) {
  return count(array_filter(array_keys($array), 'is_string')) > 0;
}

function array2csv(array &$array, $header)
{
   if (count($array) == 0) {
     return null;
   }
   ob_start();
   $df = fopen("php://output", 'w');
   
   foreach ($header as $h) {
      fputcsv($df, $h);
   }
   
   global $exportVariable;
   if($exportVariable != 3){
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

switch ($exportVariable) {
	case 1:
		$printArray = $wikiENV->getPageResults();
		break;
	case 2:
		$printArray = $wikiENV->getTimeSpanResults();
		break;
	case 3:
		array_push ($printArray, ["By City"],[]);
		$city = $wikiENV->getLocationResults("city");
		$printArray[] = array_keys(reset($city));
		foreach($city as $temp){
			$printArray[] = $temp;
		}
		array_push ($printArray, [],["By Region"],[]);
		$region = $wikiENV->getLocationResults("region");
		$printArray[] = array_keys(reset($region));
		foreach($region as $temp){
			$printArray[] = $temp;
		}
		break;
	default:
		//code to be executed if n is different from all labels;
		$printArray = $wikiENV->getPageResults();
}

$filenamePages = "wikiENV_export_data_". str_replace(" ", "_" , $wikiENV->getPageName())."_". $_SESSION["startdate"] ."_to_".$_SESSION["end_date"]."_". date("Y-m-d") . ".csv";
download_send_headers($filenamePages);
echo array2csv($printArray, [[], ["------------------------------------------"],["GCPedia Analytics Data"],[$wikiENV->getPageName()], ["From: " . $_SESSION["startdate"] ." to ".$_SESSION["end_date"]], ["------------------------------------------"], []]);

?>

