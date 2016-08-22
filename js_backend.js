/* 
This javascript file defines all the functions used to create all the graphs and data visualizations.
The data is send from a php file that is from the elgg database and Google Analytics.
This file uses a Javascript Chart library called C3, which is based on D3.

Hasnain Syed (Summer 2016)
Esther Raji (Summer 2016)

*/

//Function for drawing a generic bar graph, which takes in data
function drawBarGraph(stats, value){
	var margin = {top: 10, right: 20, bottom: 350, left: 80},
	    width = 1140 - margin.left - margin.right,
	    height = 950 - margin.top - margin.bottom;
		
	var dimen = getDimen(stats);
	
	var x = d3.scale.ordinal()
	    .rangeRoundBands([0, width], .1);

	var y = d3.scale.linear()
	    .range([height, 0]);

	var xAxis = d3.svg.axis()
	    .scale(x)
	    .orient("bottom")
		.tickFormat(function(d){
			var label = d;
			if(label.length > 50){ label = d.substr(0,50) + "..."; }
			return label;
		});

	var yAxis = d3.svg.axis()
	    .scale(y)
	    .orient("left");
	
	data = groupByPageTitle(stats);
		
	if(data.length > 1){
		
	var svg = d3.select("#chart_pages").append("svg")
		.attr("aria-hidden","true")
		.attr("width", width + margin.left + margin.right)
		.attr("height", height + margin.top + margin.bottom)
	  .append("g")
		.attr("transform", "translate(" + margin.left + "," + margin.top + ")");

		  x.domain(data.map(function(d) { return d[dimen]; }));
		  y.domain([0, d3.max(data, function(d) {return +parseInt(d[value]); })]);
		  
		  svg.append("g")
			  .attr("class", "x axis")
			  .attr("transform", "translate(0," + height + ")").call(xAxis)
			  .selectAll("text")
				.attr("y", 0)
				.attr("x", 3)
				.attr("transform", "rotate(-90), translate(-15,0)")
				.attr("dy", ".35em")
				.style("text-anchor", "end");

		  svg.append("g")
			  .attr("class", "y axis")
			  .call(yAxis)
			.append("text")
			  .attr("transform", "rotate(-90)")
			  .attr("y", 6)
			  .attr("dy", ".71em")
			  .style("text-anchor", "end")
			  .text(value);

		  svg.selectAll(".bar")
			  .data(data)
			.enter().append("a")
			  .attr("tabindex", "-1")
			  .attr("aria-hidden","true")
			  .attr("href", function(d) { return "dash_ga_elggENV_html.php?filter=" + d.pagePath.split("/")[3]; })
			.append("rect")
			  .attr("aria-hidden","true")
			  .attr("class", "bar")
			  .attr("x", function(d) {
				return x(d[dimen]); })
			  .attr("width", x.rangeBand())
			  .attr("y", function(d) { return y(d[value]); })
			  .attr("height", function(d) { return height - y(d[value]); })
			 
			.append("svg:title").text(function(d) {
					var term;
					if(value == "avgTimeOnPage" || value == 'timeOnPage'){
						term = "seconds spent on this page";
						var sec = d[value]/1
						d[value] = sec.toPrecision(4);
					} else { 
						term = value + " for this page";	
					}
				var format = d3.format(",");
				return format(d[value]) + " " + term ; });
	} else {
		d3.select("#title_bar").remove();
		d3.select("#table_bar").remove();
		
		console.log(location.href);
		if(!location.href.includes("filter=")){
			location.href = "dash_ga_elggENV_html.php?filter=" + data[0].pagePath.split("/")[3];
		}
	}
};

function drawDrillDownBarGraph(data, value, level){
	var lvl = level;
	var margin = {top: 10, right: 20, bottom: 350, left: 80},
		width = 1140 - margin.left - margin.right,
		height = 950 - margin.top - margin.bottom;
	
	var dimen = getDimen(data);
	
	var x = d3.scale.ordinal()
		.rangeRoundBands([0, width], .1);
		
	var x2 = d3.scale.ordinal()
		.rangeRoundBands([0, width], .1);

	var y = d3.scale.linear()
		.range([height, 0]);

	var xAxis = d3.svg.axis()
		.scale(x)
		.orient("bottom")
		.tickFormat(function(d){
			var label = d;
			if(label.length > 50){ label = d.substr(0,50) + "..."; }
			label = label.replace(/\_/g," ").replace(/\//g,"");
			return label;
		});

	var yAxis = d3.svg.axis()
		.scale(y)
		.orient("left");

	var tip = d3.tip()
	  .attr("class", "d3-tip")
	  .offset([-10, 0])
	  .html(function(d) {
		  var link_subpages = "dash_ga_wikiENV_html.php?filter=" + d[dimen].replace(/\//g,"");
		  var link_toppage = "dash_ga_wikiENV_html.php?filter=" + d[dimen].replace(/\//g,"") + "**";
		return "<a tabindex=-1 href="+link_toppage+"><strong style=\'color:white\'>Go to Stats Page</strong></a><br /><a href="+link_subpages+"><strong style=\'color:#ff8c00\'>Go to Subpages</strong></a>";
	  });
	
	data_subpage = groupOnlySubpages(data, lvl);
	data_grouped = groupByPageLevel(data);
	
	if(data_grouped.length > 1){
		
		var svg = d3.select("#chart_pages").append("svg")
			.attr("width", width + margin.left + margin.right)
			.attr("height", height + margin.top + margin.bottom)
			.call(tip)
		  .append("g")
			.attr("transform", "translate(" + margin.left + "," + margin.top + ")")
			
			x.domain(data_grouped.map(function(d) { return d[dimen].replace(/\//g,"").replace(/\_/g," "); }));
			y.domain([0, d3.max(data_grouped, function(d) {return +parseInt(d[value]); })]);
		  
		  svg.append("g")
			  .attr("class", "x axis")
			  .attr("transform", "translate(0," + height + ")").call(xAxis)
			  .selectAll("text")
				.attr("y", 0)
				.attr("x", 3)
				.attr("transform", "rotate(-90), translate(-15,0)")
				.attr("dy", ".35em")
				.style("text-anchor", "end");

		  svg.append("g")
			  .attr("class", "y axis")
			  .call(yAxis)
			.append("text")
			  .attr("transform", "rotate(-90)")
			  .attr("y", 6)
			  .attr("dy", ".71em")
			  .style("text-anchor", "end")
			  .text(value);
			  
		  svg.selectAll(".drillbar")
			  .data(data_grouped)
			.enter().append("a").attr("href", function(d) { 
				return "dash_ga_wikiENV_html.php?filter=" + d[dimen].replace(/\//g,"") + "**"; })
			.append("rect")
			  .attr("class", "drillbar")
			  .attr("x", function(d) { 
				return x(d[dimen].replace(/\//g,"").replace(/\_/g," ")); })
			  .attr("width", x.rangeBand())
			  .attr("y", function(d) { return y(d[value]); })
			  .attr("height", function(d) { return height - y(d[value]); })
			.append("svg:title").text(function(d) {
						var term;
						if(value == "avgTimeOnPage" || value == 'timeOnPage'){
							term = "seconds spent on this page";
							var sec = d[value]/1
							d[value] = sec.toPrecision(4);
						} else { 
							term = value + " for this page";	
						}
					var format = d3.format(",");
					return format(d[value]) + " " + term ; });
			
			svg.selectAll(".sub_bar")
			  .data(data_subpage)
			.enter().append("rect")
			  .attr("class", "sub_bar")
			  .attr("x", function(d) {
				return x(d[dimen].replace(/\//g,"").replace(/\_/g," ")); })
			  .attr("width", x.rangeBand())
			  .attr("y", function(d) { return y(+parseInt(d[value])); })
			  .attr("height", function(d) { 
				//console.log(d["pagePathLevel2"] + " : " + d[value]);
				return height - y(d[value]); })
				 .on("click",tip.show)
					//.on("mouseout", tip.hide)
			  .append("svg:title").text(function(d) {
						var term;
						if(value == "avgTimeOnPage" || value == 'timeOnPage'){
							term = "seconds spent on this page";
							var sec = d[value]/1
							d[value] = sec.toPrecision(4);
						} else { 
							term = value + " for this page";	
						}
					var format = d3.format(",");
					return format(d[value]) + " " + term ; });
	} else {
		d3.select("#title_bar").remove();
		d3.select("#table_bar").remove();
	}
};

function getDimen(info){
	var dimen = Object.keys(info[0])[0];
	
	if(dimen == "pagePath"){
		dimen = "pageTitle";
	}
	
	return dimen;
}

function groupByPageLevel(stats){
	var data =[];
	var uniqueLabel = [];
	
	var dimen = getDimen(stats);
	
	for (d in stats){
		if( uniqueLabel.indexOf(stats[d][dimen].replace(/\//g,"")) != -1){
			//console.log("Already Inside!!");
			
			var uniqueIndex = uniqueLabel.indexOf(stats[d][dimen].replace(/\//g,""));
			
			//console.log("ORIGINAL: " + data[uniqueIndex]["pageviews"]);
			//console.log("CHANGE: " + stats[d]["pageviews"]);
			
			data[uniqueIndex]["pageviews"] = parseInt(data[uniqueIndex]["pageviews"]) + parseInt(stats[d]["pageviews"]);
			data[uniqueIndex]["uniquePageviews"] = parseInt(data[uniqueIndex]["uniquePageviews"]) + parseInt(stats[d]["uniquePageviews"]);
			data[uniqueIndex]["avgTimeOnPage"] = parseInt(data[uniqueIndex]["avgTimeOnPage"]) + parseInt(stats[d]["avgTimeOnPage"]);
			
			//console.log("SUM: " + data[uniqueIndex]["pageviews"]);
		} else {
			//console.log(stats[d]);
			data.push(stats[d]);
			uniqueLabel.push(stats[d][dimen].replace(/\//g,""));
		}
	}
	return data;
}

function groupOnlySubpages(stats, level){
	data =[];
	uniqueLabel = [];
	
	for (d in stats){
			var temp = stats[d]["pagePathLevel"+level];
		if( uniqueLabel.indexOf(temp) != -1){
			//console.log("Already Inside!!");
			
			var uniqueIndex = uniqueLabel.indexOf(temp);
			
			//console.log("ORIGINAL: " + data[uniqueIndex]["pageviews"]);
			//console.log("CHANGE: " + stats[d]["pageviews"]);
			
			data[uniqueIndex]["pageviews"] = parseInt(data[uniqueIndex]["pageviews"]) + parseInt(stats[d]["pageviews"]);
			data[uniqueIndex]["uniquePageviews"] = parseInt(data[uniqueIndex]["uniquePageviews"]) + parseInt(stats[d]["uniquePageviews"]);
			data[uniqueIndex]["avgTimeOnPage"] = parseInt(data[uniqueIndex]["avgTimeOnPage"]) + parseInt(stats[d]["avgTimeOnPage"]);
			
			//console.log("SUM: " + data[uniqueIndex]["pageviews"]);
		} else {
			if(temp[temp.length-1] == "/"){
				//console.log(temp);
				//console.log(stats[d]);
				data.push(stats[d]);
				uniqueLabel.push(temp);
			}
		}
	}
	return data;
}

//
function groupByPageTitle(stats){
	data =[];
	uniqueLabel = [];
	for (d in stats){
		if( uniqueLabel.indexOf(stats[d]["pageTitle"]) != -1){
			//console.log("Already Inside!!");
			
			var uniqueIndex = uniqueLabel.indexOf(stats[d]["pageTitle"]);
			
			//console.log("ORIGINAL: " + data[uniqueIndex]["pageviews"]);
			//console.log("CHANGE: " + stats[d]["pageviews"]);
			
			data[uniqueIndex]["pageviews"] = parseInt(data[uniqueIndex]["pageviews"]) + parseInt(stats[d]["pageviews"]);
			data[uniqueIndex]["uniquePageviews"] = parseInt(data[uniqueIndex]["uniquePageviews"]) + parseInt(stats[d]["uniquePageviews"]);
			data[uniqueIndex]["avgTimeOnPage"] = parseInt(data[uniqueIndex]["avgTimeOnPage"]) + parseInt(stats[d]["avgTimeOnPage"]);
			
			//console.log("SUM: " + data[uniqueIndex]["pageviews"]);
		} else {
			data.push(stats[d]);
			uniqueLabel.push(stats[d]["pageTitle"]);
		}
	}

	return data
}

function exporter(str){
	var i = 1;
	
	if(!d3.select("#main_barChart svg").empty()){ i = 0; }
	var titles = d3.selectAll(".btn-export:not(.group_title)")
	
	if(str == "elggENV"){
		if (!d3.select("#main_barChart svg").empty()) {
			titles = d3.selectAll(".btn-export:not(.group_title)")
			i = -1;
		} else if(d3.selectAll(".group_title").select("a").empty()){
			titles = d3.selectAll(".btn-export")
			i = -2;
		} else {
			titles = d3.selectAll(".btn-export:not(.group_title)")
			i = 0
		}
	}
	
	titles.append("div")
		.attr("style", "float:right;")
		.append("a").attr("class", "btn btn-default")
				.attr("href", function(d){
					i++;
					console.log("export_" + str + ".php?export="+ i);
					return "export_"+ str +".php?export="+ i;})
				.text(ex);
}

function writeTotals(data){
	totals = data; //php variable
	for(t in totals){
		d3.select(".basic_info").append("p").text(capitalizeFirstLetter(titles[t.replace("ga:","")]) + " : " + totals[t]);
	}
}

function writeGroupTotals(data){
	items = data; //php variable
	var local = d3.select("#group_stats").append("div").attr("class", "item_stats basic_info");
	local.append("h3").text( "Group Stats: " );
	for(i in items){
		local.append("p").text( i + " : " + items[i] );
	}
}

function capitalizeFirstLetter(string) {
	return string.charAt(0).toUpperCase() + string.slice(1);
}

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

if (lang == 'en'){
	var description = {
		"pageviews":"Pageviews are the total number of pages viewed. Repeated views of a single page are counted.",
		"uniquePageviews":"Unique Pageviews are the number of sessions during which the specified page was viewed at least once. A unique pageview is counted for each page URL + page Title combination.",
		"timeOnPage":"The amount of time spent on a specified page or screen, or set of pages or screens. Measured in seconds.",
		"bounces":"Bounces are the number of single-page visits.",
		"entrances":"Entrances are the number of times visitors entered your site through a specified page or set of pages",
		"exits":"Exits are the number of times visitors exited your site from a specified page or set of pages.",
		"avgTimeOnPage":"The average amount of time users spent viewing a specified page or screen, or set of pages or screens."
	}
	var titles = {
		"pageviews":"pageviews",
		"uniquePageviews":"uniquePageViews",
		"timeOnPage":"timeOnPage",
		"bounces":"bounces",
		"entrances":"extrances",
		"exits":"exits",
		"avgTimeOnPage":"avgTimeOnPage"
	}
	var ex = "Export Data";
	var title_dict = {
		'date':'Date', 
		'link':'Links', 
		'city':'City', 
		'region':'Region', 
		'pagePath':'Page URL', 
		'pageTitle':'Page Title',
		'pageviews': 'Pageviews', 
		'uniquePageviews': 'Unique Pageviews',
		'avgTimeOnPage':'Average Seconds Spent On Page',
		'department':'Department', 
		'members':'Members', 
		'newMembers':'Number of Members Joined Today' , 
		'cummMembers': 'Total Members in Group to Date'
	}
} else {
	var description = {
		"pageviews":"Vues sont le nombre total de pages vues . vues répétées d'une seule page sont comptés.",
		"uniquePageviews":"Vues uniques sont le nombre de sessions au cours desquelles la page spécifiée a été vu au moins une fois . Une page unique est compté pour chaque + la page Titre combinaison page URL ",
		"timeOnPage":"La quantité de temps passé sur une page ou un écran spécifié , ou un ensemble de pages ou écrans . Mesurée en secondes .",
		"bounces":"Rebonds sont le nombre de visites d'une seule page.",
		"entrances":"Les entrées sont le nombre de fois que les visiteurs est entré dans votre site à travers une page spéciale ou un ensemble de pages",
		"exits":"Les sorties sont le nombre de fois les visiteurs ont quitté votre site à partir d'une page spéciale ou un ensemble de pages .",
		"avgTimeOnPage":"Le montant moyen des utilisateurs de temps passé à regarder une page ou un écran spécifié , ou un ensemble de pages ou écrans ."
	}
	var titles = {
		"pageviews":"pagevues.",
		"uniquePageviews":"pageVuesUnqiues",
		"timeOnPage":"timeOnPage",
		"bounces":"bounces",
		"entrances":"extrances",
		"exits":"exits",
		"avgTimeOnPage":"delaiMoyen"
	}
	var ex = "Exporteeeeee";
	var title_dict = {
		'date':'Datefr', 
		'link':'Linksfr', 
		'city':'Cityfr', 
		'region':'Regionfr', 
		'pagePath':'Page URL', 
		'pageTitle':'Page Title',
		'pageviews': 'Pageviews', 
		'uniquePageviews': 'Unique Pageviews',
		'avgTimeOnPage':'Average Seconds Spent On Page',
		'department':'Department', 
		'members':'Members', 
		'newMembers':'Number of Members Joined Today' , 
		'cummMembers': 'Total Members in Group to Date'
	}
}

function writeGroupStats(data){
	var items = data;
	var local = d3.select("#group_stats").append("div").attr("class", "item_stats basic_info");
	local.append("h3").text( "Group Stats: " );
	for(i in items){
		local.append("p").text( i + " : " + items[i] );
	}
}


function drawSummary(data, id){
	/********************************************* Optional ******************************************/
	
	var assoc_values = data;
    // column definitions
    var columns = [
        { head: 'Date', cl: 'text', html: ƒ('date', formatDate()) },
        { head: 'Pageviews', cl: 'num', html: ƒ('pageviews') },//, d3.format(','),
		{ head: 'Unique Pageviews', cl: 'num', html: ƒ('uniquePageviews') },//,, d3.format(',')
        { head: 'Average Time On Page', cl: 'num', html: ƒ('avgTimeOnPage') }//, formatTime()
    ];

    // create table
    var table = d3.select('#' + id)
        .append('table').attr("id", "d3-data").attr("tabindex", "0");
		
	table.append("caption").text("This is the page summary");

    // create table header
    table.append('thead').append('tr')
        .selectAll('th')
        .data(columns).enter()
        .append('th')
        .attr('class', ƒ('cl'))
        .text(ƒ('head'));

    // create table body
    table.append('tbody')
        .selectAll('tr')
        .data(assoc_values).enter()
        .append('tr')
        .selectAll('td')
        .data(function(row, i) {
            return columns.map(function(c) {
                // compute cell values for this specific row
                var cell = {};
                d3.keys(c).forEach(function(k) {
                    cell[k] = typeof c[k] == 'function' ? c[k](row,i) : c[k];
                });
                return cell;
            });
        }).enter()
        .append('td')
        .html(ƒ('html'))
        .attr('class', ƒ('cl'));
}

class Table {

	constructor(data, value, id, td = title_dict){
		this._data = data;
		this._value = value;
		this._id = id;
		this._title_dict = td; 
		this.drawTable(data, value, id);
		console.log(lang);
	}
	
	drawTable(data, value, id){
		// column definitions
		//console.log(Object.keys(data[0]));
		if(value == ""){
			var titles = Object.keys(data[0]);
		} else {
			if(Object.keys(data[0])[0] == 'pagePath'){
				this.addLinks();
				var titles = [Object.keys(data[0])[0], value, "link"];
			} else if (Object.keys(data[0])[0].includes('pagePathLevel')){
				this.addSubLinks(Object.keys(data[0])[0]);
				var titles = [Object.keys(data[0])[0], value, "link_1", "link_2"];
			} else {
				var titles = [Object.keys(data[0])[0], value];
			}
		}
		
		/* var obj_other = ["Other", (window.totals["ga:" + value] - d3.sum(info, function(d){ return +parseInt(d[indx])}))];
		if(obj_other[1] > 0){
			data.push(obj_other);
		} */
		
		var columns = [];
		for( var key in titles){
			if(titles[key] == 'avgTimeOnPage'){
				columns.push({head: this._title_dict[titles[key]], html:ƒ(titles[key], this.formatSeconds())});
			} else if(titles[key] == 'date'){
				columns.push({head: this._title_dict[titles[key]], html:ƒ(titles[key], this.formatDate())});
			} else if(titles[key].includes('pagePath')){
				columns.push({head: this._title_dict['pageTitle'], html:ƒ('pageTitle')});
			} else {
				columns.push({head: this._title_dict[titles[key]], html:ƒ(titles[key])});
				
				/* if(titles[key] == 'city' || titles[key] == 'region'){
					columns.push({head: "Percent Value", cl:"percent", html:ƒ('percent')});
				} */
			}
		}
		
		//var columns = col; [
			/*{ head: 'Date', cl: 'date', html: ƒ('date', formatDate()) },
			{ head: 'Pageviews', cl: 'center', html: ƒ('pageviews') },//, d3.format(','),
			{ head: 'Unique Pageviews', cl: 'center', html: ƒ('uniquePageviews') },//,, d3.format(',')
			{ head: 'Average Seconds Spent On Page', cl: 'num', html: ƒ('avgTimeOnPage', formatSeconds())},//
		]; */

		// create table
		var table = d3.select('#' + id)
			.append('table').attr("id", "d3-table").attr("tabindex", "0");

		table.append("caption").text("This is the " + id.replace("_", " ").replace("table_", " ") + " table");

		// create table header
		table.append('thead').append('tr')
			.selectAll('th')
			.data(columns).enter()
			.append('th')
			.attr('class', ƒ('cl'))
			.text(ƒ('head'));
		
		// create table body
		table.append('tbody')
			.selectAll('tr')
			.data(data).enter()
			.append('tr')
			.selectAll('td')
			.data(function(row, i) {
				return columns.map(function(c) {
					// compute cell values for this specific row
					var cell = {};
					d3.keys(c).forEach(function(k) {
						//console.log(k);
						cell[k] = typeof c[k] == 'function' ? c[k](row,i) : c[k];
					});
					//console.log(cell);
					return cell;
				});
			}).enter()
			.append('td')
			.html(ƒ("html"))
			.attr('class', ƒ('cl'));

	}
	
	formatTime() {
		var fmt = d3.format("02d");
		return function(l) {
			var hours = Math.floor(l / 3600);
			var mins = Math.floor((l % 3600)/60);
			var seconds = (l % 60);
			return fmt(hours) + ':' + fmt(mins) +':' + seconds.toPrecision(4) + ''; };
	}
		
	formatSeconds() {
		var fmt = d3.format("02d");
		return function(l) {
			var seconds = (l / 1);
			return seconds.toPrecision(4) + ''; };
	}
		
	formatDate(){
		return function(d){
			var parseTime = d3.time.format("%Y%m%d");
			var displayTime = d3.time.format("%B %e, %Y");
			//console.log(parseTime.parse(d));
			
			return displayTime(parseTime.parse(d));
		};
	}
	changeVal(newVal){
		d3.select('#' + this._id).select("#d3-table").remove();
		this._value = newVal;
		this.drawTable(this._data, newVal, this._id);
	}
	
	addLinks(){
		for (d in this._data) {
			//console.log(this._data[d]["pagePath"]);
			var link_pages = "dash_ga_elggENV_html.php?filter=" + this._data[d].pagePath.split("/")[3];
			this._data[d]["link"] = "<a href='"+link_pages+"'>See more</a>";
		}
		return data
	}
	
	addSubLinks(dimen){
		for (d in this._data) {
			var link_toppage = "dash_ga_wikiENV_html.php?filter=" + this._data[d][dimen].replace(/\//g,"") + "**";
			this._data[d]["link_1"] = "<a href='"+link_toppage+"'>See this Page</a>";
			//console.log("dash_ga_wikiENV_html.php?filter=" + this._data[d][dimen].replace(/\//g,""));
			if(this._data[d][dimen].slice(-1) == "/" && !this._data[d][dimen].includes("4")){
				//console.log("this happend");
				var link_subpages = "dash_ga_wikiENV_html.php?filter=" + this._data[d][dimen].replace(/\//g,"");
				this._data[d]["link_2"] = "<a href='"+link_subpages+"'>See Subpages</a>";
			}

		}
		return data
	}
}
