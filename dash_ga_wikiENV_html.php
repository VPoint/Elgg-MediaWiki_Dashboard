<!DOCTYPE html>
<?php
	session_start();
	
	include "GA_backend.php";
	
	$wikiENV = new wikiENV_Analytics_Object();

	if(isset($_SESSION["level"])){
		$wikiENV->setLevel($_SESSION["level"]);
	}
	
	if(empty($_POST['filter']) && empty($_GET['filter']) && empty($_POST['URL-search'])){
		$wikiENV->setFilter("wiki;");
		$wikiENV->setPageName("");
		$wikiENV->setLevel(2);
		unset($_SESSION["memory"]);
 
	} elseif (isset($_GET['filter'])){
		
		if($wikiENV->getLevel() == 2){
			$toppage =  "/" . $_GET['filter'];
		} else{
			$wikiENV->setPageName(str_replace("_", " ", $_GET['filter']));
			$toppage = implode($_SESSION["memory"], "/") . "/" . $_GET['filter'];
		}
		
		if (substr($_GET['filter'], -2) == "**"){
			$wikiENV->setPageName(htmlspecialchars(str_replace(["**", "_"], ["", " "], $_GET['filter'])));
			// level should equal 1.....
			//echo "</br> Check if Level " . $wikiENV->getLevel() . " contains " . $wikiENV->getPageName();
			$top_page = trim(str_replace("**", "",$toppage));
			$wikiENV->getStatsSpecific($top_page);
			//echo "<br/> TOP PAGE: " . $top_page;
		} else {
			$wikiENV->setPageName(htmlspecialchars(str_replace(["_"], [" "], $_GET['filter'])));
			$wikiENV->setFilter('wiki;ga:pagePathLevel'.$wikiENV->getLevel().'=@/'. $_GET['filter'] . ';');
		}
		//echo "</br> Check if Level " . $wikiENV->getLevel() . " contains " . $wikiENV->getPageName();
		$wikiENV->pageDrillDown();
		//echo "</br> Next Level " . $wikiENV->getLevel();
		
	} elseif(!empty($_POST['URL-search'])){
		$url = trim(str_replace(["https://", "http://", "www", ".", "wikiENV", "gc", "ca"], "", $_POST['URL-search']));
		$wikiENV->setFilter('wiki;ga:pagePath=='. $url . ';');
		$wikiENV->setPageName(htmlspecialchars(str_replace(["_", "/", "wiki", " |  | "], [" "," | ", ""], $url)));
		unset($_SESSION["memory"]);
	} else{
		$wikiENV->setFilter('wiki;ga:pagePath=@'. str_replace(" ", "_", $_POST['filter']). ';');
		$wikiENV->setPageName(htmlspecialchars($_POST['filter']));
		unset($_SESSION["memory"]);
	}
	
	if(isset($_POST['back_btn'])){
		//echo "Back Button";
		$temp = array_pop($_SESSION["memory"]);
		$prev = array_pop($_SESSION["memory"]);
		
		if($prev == null){
			unset($_SESSION["memory"]);
			
			$wikiENV->setLevel(1);
			$_SESSION["level"] = $wikiENV->getLevel();
			//echo "<br/> PREVIOUS ==== NULL";
			//echo "<br/> Lvl: " . $wikiENV->getLevel() . " For: " . "GeNERaL Wiki";
			echo "<script>window.location = '/themes-dist-4.0.20-gcweb/dash_ga_wikiENV_html.php' ;</script>";
		} else if(substr($_GET['filter'], -2) == "**"){
			$wikiENV->setLevel($wikiENV->getLevel() - 1);
			//$wikiENV->pageDrillDown();
			//echo "<br/> PREVIOUS IS SET, From Stats Specific";
			$_SESSION["level"] = $wikiENV->getLevel();
			//echo "<br/> Lvl: " . $wikiENV->getLevel() . " For: " . $prev;
			echo "<script>window.location = '/themes-dist-4.0.20-gcweb/dash_ga_wikiENV_html.php?filter=". $prev . "' ;</script>";
		} else {
			$wikiENV->setLevel($wikiENV->getLevel() - 3);
			//echo "<br/> PREVIOUS IS SET";
			$_SESSION["level"] = $wikiENV->getLevel();
			//echo "<br/> Lvl: " . $wikiENV->getLevel() . " For: " . $prev;
			echo "<script>window.location = '/themes-dist-4.0.20-gcweb/dash_ga_wikiENV_html.php?filter=". $prev . "' ;</script>";
		}
	}
	
	if(isset($_POST['date1']) && isset($_POST['date2']) && 
		!empty($_POST['date1']) && !empty($_POST['date2']) && 
			($_POST['date1'] != "0000-00-00") && ($_POST['date2'] != "0000-00-00") && 
				($_POST['date1'] <= $_POST['date2']) == 1 ){
		$wikiENV->setTimeSpan($_POST['date1'], $_POST['date2']);
	} elseif(isset($_GET['filter'])){
		$wikiENV->setTimeSpan($_SESSION["startdate"], $_SESSION["end_date"]);
	} else {
		$wikiENV->setTimeSpan("7daysAgo", "today");
	}
	
	$value = str_replace("_", " ", implode($_SESSION["memory"], " > ")) . " > " . $wikiENV->getPageName();
	
	$timespan = $wikiENV->getTimeSpan();
	
	$date_start = $timespan["start"];
	$date_end = $timespan["end"];
	
	//Set session variables...
	$_SESSION["filter"] = $wikiENV->getFilter();
	$_SESSION["startdate"] = $timespan["start"];
	$_SESSION["end_date"] = $timespan["end"];
	$_SESSION["level"] = $wikiENV->getLevel();
	$_SESSION["value"] = str_replace([" > ", " "] , ["/", "_"], $value);
	
	if(!isset($_POST['back_btn'])){
		$_SESSION["memory"][] = $_GET['filter'];
	}
	
	//echo "<br/>". $_SESSION["filter"]. "<br/>";
	//echo "Session Variables";
	//echo $_SESSION["filter"]. "<br/>" . $_SESSION["startdate"]. "<br/>" . $_SESSION["end_date"]. "<br/>" . $_SESSION["level"]. "<br/>" . $_SESSION["value"]. "<br/>";
?>
<html>
<head>
	<form action="" method="get">
		<select name="lang" onchange="javascript: submit()">
  			<option value=''>------</option>
  			<option value="en">English</option>
  			<option value="fr">French</option>
		</select>
	</form>

	<?php 
		include 'select_lang.php';
		include $lang_file;
	?>

	<title>GCPedia Dashboard</title>
	<meta charset="utf-8">
	<script src="https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.12/d3.js" type="text/javascript"></script>
	<script src="d3-jetpack.js" type="text/javascript"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/c3/0.4.10/c3.min.js" type="text/javascript"></script>
	
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js" type="text/javascript"></script>
	<script src="tableToD3Chart.js" type="text/javascript"></script>
	<script src="http://labratrevenge.com/d3-tip/javascripts/d3.tip.v0.6.3.js"></script>
	
	<link href="./GCWeb/assets/favicon.ico" rel="icon" type="image/x-icon">
	<link rel="stylesheet" href="./GCWeb/css/theme.min.css">
	<noscript><link rel="stylesheet" href="./wet-boew/css/noscript.min.css" /></noscript>
	
	<link href="https://cdnjs.cloudflare.com/ajax/libs/c3/0.4.10/c3.min.css" rel="stylesheet" />
	<link rel="stylesheet" type="text/css" href="default_style.css">
	<link rel="stylesheet" type="text/css" href="graph.css">
	
	<script src="js_backend.js" type="text/javascript"></script>
</head>
<body>
	<main role="main" property="mainContentOfPage" class="container">
		<h1 property="name" id="wb-cont">GCPedia Dashboard <?php echo $value ?></h1>
		<form action="" method="post" style="width:50%; margin-right:0;">
			<input id="button" class="form-control" type="hidden" name="back_btn" value="back"/>
			<input type="submit" class="btn btn-primary" style="float:left, display:inline" value ="Back"/>
		</form>
		
	<div id="time-span">
			<h2><?php echo $lang['From'], $date_start," ", $lang['To']," " , $date_end ?> </h2>
		</div>
	
	<form action="dash_ga_wikiENV_html.php" method="post">
		<h4><?php echo $lang['SearchByURL']; ?></h4>  <input style="width:50%;" id="URL" class="form-control" type="text" name="URL-search" value="<?php global $url; echo $url; ?>" />
    	<input class="btn btn-primary" type="submit" name="change_filter" value="Find Info" />
		<h4><?php echo $lang['SearchByKeyword']; ?></h4><input id="filter" class="form-control" type="text" value="<?php //echo $value ?>" name="filter" />
    	<input class="btn btn-primary" type="submit" name="change_filter" value="Find Info" />
		<br />
		<h4>Time Span:</h4>
		<form action="" method="post">
			<div class="wb-frmvld">
				<div class="form-group" style="display:inline-block;width:100%;">
					<label for="date1"><span class="field-name">From:</span><span class="datepicker-format"></span></label>
					<input class="form-control" id="date1" name="date1" type="date" data-rule-dateISO="true" value="<?php echo $date_start ?>"/>
					<label for="date2"><span class="field-name">To:</span><span class="datepicker-format"></span></label>
					<input class="form-control" id="date2" name="date2" type="date" data-rule-dateISO="true" value="<?php echo $date_end ?>"/>
					<input class="btn btn-primary" type="submit" name="change_time" value="Change Span" />
				</div>
			</div>
		</form>
	</form>
	
	<div class='basic_info'>
		<h3>Total Stats:</h3>
	</div>
	
	<div id='group_stats'></div>
	<hr>
	<div id='legend'>
		<button class="btn btn-default" onclick='changeMetricValue("pageviews")'>Pageviews</button>
		<button class="btn btn-default" onclick='changeMetricValue("uniquePageviews")'>Unique Pageviews</button>
		<button class="btn btn-default" onclick='changeMetricValue("avgTimeOnPage")'>Average Time On Page</button>
	</div>
	<div class='more_info'>Pageviews: Pageviews are the total number of pages viewed. Repeated views of a single page are counted.</div>
	
	<section id='main_barChart'>
		<h2 id="title_bar" class ="basic_info btn-export">Pageviews per Page</h2>
		<div id='chart_pages'></div>
		<div class="accordion">
		<!-- Accordion section 1 -->
		<details class="acc-group">
			<summary class="wb-toggle tgl-tab" data-toggle='{"parent": ".accordion", "group": ".acc-group"}'>Table Data</summary>
			<div class="tgl-panel">
				<!-- Section 1's content -->
				<div id='table_pages'></div>
			</div>
		</details>
		</div>
		
	</section>
	<section id='main_lineChart'>
		<h2 id="title_line" class ="basic_info btn-export">Total Pageviews Over Time</h2>
		<div id='chart_time'></div>
		<div class="accordion">
		<!-- Accordion section 1 -->
		<details class="acc-group">
			<summary class="wb-toggle tgl-tab" data-toggle='{"parent": ".accordion", "group": ".acc-group"}'>Table Data</summary>
			<div class="tgl-panel">
				<!-- Section 1's content -->
				<div id='table_time'></div>
			</div>
		</details>
		</div>
	</section>
	<section id='main_circleChart'>
		<h2 id="title_circle" class ="basic_info btn-export">Total Pageviews per Region and City</h2>
		<div class='pie-chart' style="float:left;">
			<div id='chart_city'> </div>
			<div class="accordion">
			<!-- Accordion section 1 -->
			<details class="acc-group">
				<summary class="wb-toggle tgl-tab" data-toggle='{"parent": ".accordion", "group": ".acc-group"}'>Table Data</summary>
				<div class="tgl-panel">
					<!-- Section 1's content -->
					<div id='table_city'></div>
				</div>
			</details>
			</div>
		</div>
		
		<div class='pie-chart' style="float:right;">
			<div id='chart_region'> </div>
			<div class="accordion">
			<!-- Accordion section 1 -->
			<details class="acc-group">
				<summary class="wb-toggle tgl-tab" data-toggle='{"parent": ".accordion", "group": ".acc-group"}'>Table Data</summary>
				<div class="tgl-panel">
					<!-- Section 1's content -->
					<div id='table_region'></div>
				</div>
			</details>
			</div>
		</div>
	</section>
</body>

<script>

writeTotals(<?php echo json_encode($wikiENV->getTotalMetrics()); ?>);

var pages_table = new Table(groupByPageTitle(<?php echo json_encode($wikiENV->getPageResults()); ?>), "pageviews", "table_pages");
drawDrillDownBarGraph(<?php echo json_encode($wikiENV->getPageResults()); ?>, "pageviews", <?php echo json_encode($wikiENV->getLevel()); ?>);
var time_table = new Table(<?php echo json_encode($wikiENV->getTimeSpanResults()); ?>,"pageviews", "table_time");
var city_table = new Table(<?php echo json_encode($wikiENV->getLocationResults("city")); ?>,"pageviews", "table_city");
var region_table =new Table(<?php echo json_encode($wikiENV->getLocationResults("region")); ?>,"pageviews", "table_region");
exporter("wikiENV");

$( "#chart_time" ).tableToD3Chart({
  chartDataTable: "#table_time #d3-table",
  chartType: "area"
})

$( "#chart_city" ).tableToD3Chart({
  chartDataTable: "#table_city #d3-table",
  rows: false,
  chartType: "pie"
})

$( "#chart_region" ).tableToD3Chart({
  chartDataTable: "#table_region #d3-table",
  rows: false,
  chartType: "pie"
})

function changeMetricValue(value){
	d3.select(".more_info").text(capitalizeFirstLetter(value)+ ": " + description[value]);
	
	// change the titles as well as the values for the graphs
	if(!d3.select("#main_barChart").empty()){
		d3.select("#title_bar").attr("class", "basic_info btn-export").text(capitalizeFirstLetter(value) + " per Page");
		d3.select("#main_barChart").selectAll("svg").remove();
		pages_table.changeVal(value);
		drawDrillDownBarGraph(<?php echo json_encode($wikiENV->getPageResults()); ?>, value, <?php echo json_encode($wikiENV->getLevel()); ?>);
	}
	
	d3.select("#title_line").attr("class", "basic_info btn-export").text("Total " + capitalizeFirstLetter(value) + " Over Time");
	d3.select("#title_circle").attr("class", "basic_info btn-export").text("Total " + capitalizeFirstLetter(value) + " per Region and City");
	
	time_table.changeVal(value);
	city_table.changeVal(value);
	region_table.changeVal(value);
	
	d3.select("#main_lineChart").selectAll("svg").remove();
	d3.select("#main_circleChart").selectAll("svg").remove();
	
	$( "#chart_time" ).tableToD3Chart({
	  chartDataTable: "#table_time #d3-table",
	  chartType: "area"
	})

	$( "#chart_city" ).tableToD3Chart({
	  chartDataTable: "#table_city #d3-table",
	  rows: false,
	  chartType: "pie"
	})

	$( "#chart_region" ).tableToD3Chart({
	  chartDataTable: "#table_region #d3-table",
	  rows: false,
	  chartType: "pie"
	})
	
	exporter("wikiENV");
}
</script>
</html>