<!DOCTYPE html>
<?php
	session_start();
	
	include "GA_backend.php";
	include "DB_backend.php";
	
	$elggENV = new elggENV_Analytics_Object();
	
	$_SESSION["param"] = "general";

	if(empty($_POST['precision']) && !isset($_SESSION["precision"])){
		$elggENV->setPrecision('/groups/profile/');
	} elseif(!empty($_POST['precision'])){
		$elggENV->setPrecision($_POST['precision']);
	} else {
		$elggENV->setPrecision($_SESSION["precision"]);
	}

	$title = $elggENV->getGroup();

	if(empty($_POST['filter']) && (empty($_GET['filter']) || !isset($_GET['filter']))){
		$elggENV->setFilter($elggENV->getPrecision() . ';');
		$elggENV->setPageName('');
	} elseif (isset($_GET['filter'])){
		global $guid;
		$guid = $_GET['filter'];
		
		if($elggENV->getPrecision() == "/groups/profile/"){
			connectDatabase("elgggroups_entity");
			$_SESSION["param"] = "groups";
		} else {
			connectDatabase("elggobjects_entity");
		}
		$elggENV->setFilter($guid .';ga:pagePath=~^'. $elggENV->getPrecision() . $guid .'*;');
		//unset($_GET['filter']);
	} else{
		$elggENV->setFilter($elggENV->getPrecision()  .';ga:pageTitle=@'. $_POST['filter'] . ';');
		$elggENV->setPageName(htmlspecialchars($_POST['filter']));
	}
	
	if(isset($_POST['date1']) && isset($_POST['date2']) && 
		!empty($_POST['date1']) && !empty($_POST['date2']) && 
			($_POST['date1'] != "0000-00-00") && ($_POST['date2'] != "0000-00-00") && 
				($_POST['date1'] <= $_POST['date2']) == 1 ){
		$elggENV->setTimeSpan($_POST['date1'], $_POST['date2']);
	} elseif(isset($_GET['filter'])){
		$elggENV->setTimeSpan($_SESSION["startdate"], $_SESSION["end_date"]);
	} else {
		$elggENV->setTimeSpan("7daysAgo", "today");
	}
	
	$value = $elggENV->getPageName();
	$timespan = $elggENV->getTimeSpan();
	$use_precision = $elggENV->getPrecision();
	
	//Set session variables...
	$_SESSION["precision"] = $elggENV->getPrecision();
	$_SESSION["guid"] = $guid;
	$_SESSION["startdate"] = $timespan["start"];
	$_SESSION["end_date"] = $timespan["end"];
	$_SESSION["name"] = str_replace([" ", "|"] , ["_",""], $elggENV->getPageName());
	$_SESSION["filter"] = $elggENV->getFilter();
	
	function connectDatabase($target){
		// Connect to static SQL database
		global $db_Object;
		global $elggENV;
		global $guid;
		
		$db_Object = new Database_Object(new mysqli('IP ADDRESS','USERNAME','PASSWORD','DATABASE'));
		
		$variable = "title";
			if($target == "elgggroups_entity"){
				$variable = "name";
				

		}
		
		$rows = $db_Object->query("SELECT $variable AS text FROM $target WHERE guid = '$guid'");
		$elggENV->setPageName($rows[0]["text"]);
	}
	
	function membersByDepartment(){
		global $db_Object;
		global $elggENV;
		global $guid;
		
		if($elggENV->getPrecision() == "/groups/profile/" && isset($_GET['filter'])){
			$data = $db_Object->getPieGraphData($guid);
			return json_encode($data);
		} else {
			return json_encode("");
		}
	}
	
	function groupItems(){
		global $db_Object;
		global $elggENV;
		global $guid;
		
		if($elggENV->getPrecision() == "/groups/profile/" && isset($_GET['filter'])){
			$data = $db_Object->getGroupEntityStats($guid);
			return json_encode($data);
		} else {
			return json_encode("");
		}
	}
	
	
	function membersByDate(){
		global $db_Object;
		global $elggENV;
		global $guid;
		
		if($elggENV->getPrecision() == "/groups/profile/" && isset($_GET['filter'])){
			$data = $db_Object->getLineGraphData($guid);
			return json_encode($data);
		} else {
			return json_encode("");
		}
	}
?>

<html>
<head>
<form action="" method="get">
		<select name="lang" onchange="javascript: submit()">
  			<option value=''>------</option>
  			<option value="en">English</option>
  			<option value="fr">French</option>
	</select></form>

	<?php 
	include 'select_lang.php';
	include $lang_file;
	?>

	<title>elggENV <?php echo $lang['Dashboard']; ?></title>
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
		<h1 property="name" id="wb-cont">elggENV <?php echo $lang ['Dashboard'], $value .' - '. $title ?></h1>
		<form action="" method="post">
			<input id="toFile" type="hidden" name="export" value="" />
		</form>
		<div id="time-span">
			<h2>From: <?php echo $timespan["start"] ?>  to  <?php echo $timespan["end"] ?> </h2>
		</div>
	
	<form action="dash_ga_elggENV_html.php" method="post">
		<h4><?php echo $lang['Keyword']; ?></h4>  <input id="data2" class="form-control" type="text" name="filter" value="<?php echo $value ?>" />
		<select name="precision" id="precision" class="form-control" >
			<option value=<?php echo $use_precision ?> id = "persist"><?php echo $title;?></option>
			<option value="/groups/profile/"><?php echo $lang['Groups']; ?></option>
			<option value="/blog/view/"><?php echo $lang['Blogs']; ?></option>
			<option value="/pages/view/"><?php echo $lang['Pages']; ?></option>
			<option value="/polls/view/"><?php echo $lang['Polls']; ?></option>
			<option value="/event_calendar/view/"><?php echo $lang['Events']; ?></option>
			<option value="/missions/view/"><?php echo $lang['MicroMissions']; ?></option>
			<option value="/discussion/view/"><?php echo $lang['Discussions']; ?></option>
		</select>
    	<input class="btn btn-primary" type="submit" name="change_filter" value="<?php echo $lang['FindInfo']; ?>" />
		<br/>
		<h4><?php echo $lang['TimeSpan']; ?></h4>
		<form action="" method="post">
			<div class="wb-frmvld">
				<div class="form-group" style="display:inline-block;width:100%;">
					<label for="date1"><span class="field-name"><?php echo $lang['From']; ?></span><span class="datepicker-format"></span></label>
					<input class="form-control" id="date1" name="date1" type="date" data-rule-dateISO="true" value="<?php echo $timespan["start"] ?>"/>
					<label for="date2"><span class="field-name"><?php echo $lang['To']; ?></span><span class="datepicker-format"></span></label>
					<input class="form-control" id="date2" name="date2" type="date" data-rule-dateISO="true" value="<?php echo $timespan["end"] ?>"/>
					<input class="btn btn-primary" type="submit" name="change_time" value="<?php echo $lang['ChangeSpan']; ?>" />
				</div>
			</div>
		</form>
	</form>
	
	<div class='basic_info'>
		<h3><?php echo $lang['TotalStats']; ?></h3>
	</div>
	
	<section class="group" >
		<h2 class="group_title basic_info btn-export" style="display: none;"><?php echo $lang['MemByDept']; ?></h2>
		<div id='group_stats'></div>
		<div id='group_chart_dept'></div>
		<div class="accordion" style="display: none;">
			<!-- Accordion section 1 -->
			<details class="acc-group">
				<summary class="wb-toggle tgl-tab" data-toggle='{"parent": ".accordion", "group": ".acc-group"}'><?php echo $lang['TableData']; ?></summary>
				<div class="tgl-panel">
					<!-- Section 1's content -->
					<div id='group_table_dept'></div>
				</div>
			</details>
		</div>
		
		<h2 class="group_title basic_info btn-export" style="display: none;"><?php echo $lang['ChangeInMemOT']; ?></h2> 
		<div id='group_chart_line'></div>
		<div class="accordion" style="display: none;">
			<!-- Accordion section 1 -->
			<details class="acc-group">
				<summary class="wb-toggle tgl-tab" data-toggle='{"parent": ".accordion", "group": ".acc-group"}'><?php echo $lang['TableData']; ?></summary>
				<div class="tgl-panel">
					<!-- Section 1's content -->
					<div id='group_table_line'></div>
				</div>
			</details>
		</div>
	</section>
	<hr>
	<div id='legend'>
		<button class="btn btn-default" onclick='changeMetricValue("pageviews")'><?php echo $lang['PageViews']; ?></button>
		<button class="btn btn-default" onclick='changeMetricValue("uniquePageviews")'><?php echo $lang['UniquePageViews']; ?></button>
		<button class="btn btn-default" onclick='changeMetricValue("avgTimeOnPage")'><?php echo $lang['TimeOnPage']; ?></button>
	</div>
	<div class='more_info' tabindex=0><?php echo $lang['PageViewsDes']; ?></div>

	<section id='main_barChart'>
		<h2 id="title_bar" class ="basic_info btn-export"><?php echo $lang['PageViewsPerPage']; ?></h2>
		<div id='chart_pages'></div>
		<div class="accordion">
		<!-- Accordion section 1 -->
		<details class="acc-group">
			<summary class="wb-toggle tgl-tab" data-toggle='{"parent": ".accordion", "group": ".acc-group"}'><?php echo $lang['TableData']; ?></summary>
			<div class="tgl-panel">
				<!-- Section 1's content -->
				<div id='table_pages'></div>
			</div>
		</details>
		</div>
		
	</section>
	<section id='main_lineChart'>
		<h2 id="title_line" class ="basic_info btn-export"><?php echo $lang['Total'], $lang['PageViews'], $lang['OverTime']; ?></h2>
		<div id='chart_time'></div>
		<div class="accordion">
		<!-- Accordion section 1 -->
		<details class="acc-group">
			<summary class="wb-toggle tgl-tab" data-toggle='{"parent": ".accordion", "group": ".acc-group"}'><?php echo $lang['TableData']; ?></summary>
			<div class="tgl-panel">
				<!-- Section 1's content -->
				<div id='table_time'></div>
			</div>
		</details>
		</div>
	</section>
	<section id='main_circleChart'>
		<h2 id="title_circle" class ="basic_info btn-export"><?php echo $lang['Total'], $lang['PageViews'], $lang['RegionCity']; ?></h2>
		<div class='pie-chart' style="float:left;">
			<div id='chart_city'> </div>
			<div class="accordion">
			<!-- Accordion section 1 -->
			<details class="acc-group">
				<summary class="wb-toggle tgl-tab" data-toggle='{"parent": ".accordion", "group": ".acc-group"}'><?php echo $lang['TableData']; ?></summary>
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
				<summary class="wb-toggle tgl-tab" data-toggle='{"parent": ".accordion", "group": ".acc-group"}'><?php echo $lang['TableData']; ?></summary>
				<div class="tgl-panel">
					<!-- Section 1's content -->
					<div id='table_region'></div>
				</div>
			</details>
			</div>
		</div>
	</section>
	</main>
</body>

<script>
writeTotals(<?php echo json_encode($elggENV->getTotalMetrics()); ?>);

var pages_table = new Table(groupByPageTitle(<?php echo json_encode($elggENV->getPageResults()); ?>), "pageviews", "table_pages");
drawBarGraph(<?php echo json_encode($elggENV->getPageResults()); ?>, "pageviews");
var time_table = new Table(<?php echo json_encode($elggENV->getTimeSpanResults()); ?>,"pageviews", "table_time");
var city_table = new Table(<?php echo json_encode($elggENV->getLocationResults("city")); ?>,"pageviews", "table_city");
var region_table =new Table(<?php echo json_encode($elggENV->getLocationResults("region")); ?>,"pageviews", "table_region");
exporter("elggENV");

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

function getCookie(cname) {
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for(var i = 0; i <ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length,c.length);
        }
    }
    return "";
}

var lang = getCookie('lang');

function changeMetricValue(value){
	
	console.log(lang);
	//translation of the 'value'
	if (lang == 'fr'){
		if (value =='pageviews'){
			value == 'pagevues';
		}
		else if (value == 'uniquePageviews'){
			value == 'pagevuesUniques';
		}
		else {
			value == 'delai moyen a la page';
		}
	}

	d3.select(".more_info").text(capitalizeFirstLetter(titles[value])+ ": " + description[value]);


	// change the titles as well as the values for the graphs

	if(!d3.select("#main_barChart").empty()){
		d3.select("#title_bar").attr("class", "basic_info btn-export").text(capitalizeFirstLetter(titles[value]) + " per Page");
		d3.select("#main_barChart").selectAll("svg").remove();
		pages_table.changeVal(titles[value]);
		drawBarGraph(<?php echo json_encode($elggENV->getPageResults()); ?>, value);
	}
	
	d3.select("#title_line").attr("class", "basic_info btn-export").text("Total " + capitalizeFirstLetter(titles[value]) + " Over Time");
	d3.select("#title_circle").attr("class", "basic_info btn-export").text("Total " + capitalizeFirstLetter(titles[value]) + " per Region and City");
	
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
	
	exporter("elggENV");
}

if(d3.select("#main_barChart svg").empty() && $("#precision")[0].value == "/groups/profile/"){
	displayGroupStats();
}

function displayGroupStats(){
	
	writeGroupTotals(<?php echo groupItems(); ?>);
	$(".group_title").show();
	
	$(".accordion").show();
	window.group_department_table = new Table(<?php echo membersByDepartment() ?>,"", "group_table_dept");
	$ ( "#group_chart_dept" ).tableToD3Chart({
	  chartDataTable: "#group_table_dept #d3-table",
	  chartType: "pie",
	  rows:false
	})
	
	window.group_member_time_table = new Table(<?php echo membersByDate() ?>,"", "group_table_line");
	$ ( "#group_chart_line" ).tableToD3Chart({
	  chartDataTable: "#group_table_line #d3-table",
	  chartType: "area"
	})
	
	console.log("I was Called!!!!!!!!!!");
}
</script>
</html>