<?php
/*

Hasnain Syed (Summer 2016)

---------------------------------------------------------------------------------------------------------------------------
This class contains a constructor that takes in a type mySQLi object, which in this case would be the elgg database, and
initializes the database object with the database information given when called. The information on the elgg database can 
then be accessed using queries built in the mySQLi function already included in PHP.

---------------------------------------------------------------------------------------------------------------------------
The class contains functions that get information about groups --> members per department, growth of a group overtime and 
the group content information, and returns graph data to be put into a javascript file. Most of the functions related to 
groups are taking in a 'guid' parameter which refers to the group guid, which is used to query and get information of that 
specific group.

---------------------------------------------------------------------------------------------------------------------------
The class also contains function for general statistics about GCconnex --> Total number of blogs, discussions, pages and
other types of entities, the percent increase in the last 365 days and also information about active users and their 
percent increase. 

---------------------------------------------------------------------------------------------------------------------------
*/

class Database_Object{

	//instanace variables
	private $db;

	//return a database object when it is called
	public function __construct(mysqli $db) {
		$this->db = $db;
		$this->db->set_charset('utf8mb4');  
	}
	
	public function checkDatabaseConnection(){
		if ($this->db->connect_errno){
       		echo "Database Not Connected", '<br>';
	   	}
	   	else {
	   		echo "Connection Successful", '<br>';
	   	}
	}
	
	public function query($q){
		$data = $this->db->query($q);
		return $data->fetch_all(MYSQLI_ASSOC);
	}

	//--------------------------------------------------------------------------------Classes for Group Related Stats-----------------------------------------------------------------------------

	//Get stats for blogs, pages, wires, etc for each group
	public function getGroupEntityStats($guid){

		//create search query and put it in a associative array
		$rows = $this->query("SELECT subtype, container_guid FROM elggentities
			WHERE container_guid = '$guid'");

		/*'blogs': 5,
		'discussions': 7,
		'pages': 10,
		'wires': 17,
		'files': 1,
		'images': 19,
		'bookmarks': 8,
		'ideas': 42*/

		//Declare vairables for counter
		$itemStats = array();
		$itemStats["Blogs"] = 0;
		$itemStats["Discussions"] = 0;
		$itemStats["Pages"]  = 0;
		$itemStats["Ideas"] = 0;
		$itemStats["Files"] = 0;
		$itemStats["Images"] = 0;
		$itemStats["Bookmarks"] = 0;
	
		//Loop for counting each entitiy
		foreach($rows as $row){
			//echo json_encode($row);
			if ($row['subtype'] == '5'){
				$itemStats["Blogs"] += 1;
			}
			elseif ($row['subtype'] == '7'){
				$itemStats["Discussions"] += 1;
			}
			elseif ($row['subtype'] == '10' || $row['subtype'] == '9'){
				$itemStats["Pages"] += 1;
			}
			elseif ($row['subtype'] == '42'){
				$itemStats["Ideas"] += 1;
			}
			elseif ($row['subtype'] == '1'){
				$itemStats["Files"] += 1;
			}
			elseif ($row['subtype'] == '19'){
				$itemStats["Images"] += 1;
			}
			elseif ($row['subtype'] == '8'){
				$itemStats["Bookmarks"] += 1;
			}
		}

		//return data
		return $itemStats;
	}

	//Arranges members per department data in a way so it can be used to graph in javascript
	public function getPieGraphData($guid){
		//create search query and put it in a associative array
		$rows = $this->query("SELECT elggusers_entity.email, elggentity_relationships.guid_one, elggentity_relationships.guid_two, elggentity_relationships.relationship
			FROM elggentity_relationships, elggusers_entity
			WHERE elggusers_entity.guid = elggentity_relationships.guid_one 
				AND elggentity_relationships.guid_two = '$guid'
				AND elggentity_relationships.relationship = 'member'");

		//array to store each user's department
		$userDept = array();

		//loop for email tail
		foreach($rows as $row){
			$split = explode("@", $row['email']); //split it at email tail
			array_push($userDept, strtolower($split[1]));	
		}

		//array to store all the email tails and count how many times they have occurred
		/* $occurence = array(); */
		$occurence = array_count_values($userDept);

		//stores all the users
		$deptTotals = array();
		//sort array
		arsort($occurence);
		//loop to store key and value 
		foreach($occurence as $key => $value) {
			$temp = array();
			$temp["department"] = $key;
			$temp["members"] = $value;
			array_push($deptTotals, $temp);
		}
	
		return $deptTotals;
	}

	//Arranges group growth data in a way so it can be used to graph in javascript
	public function getLineGraphData($guid){
		
		//create search query and put it in a associative array
		$rows = $this->query("SELECT time_created
			FROM elggentity_relationships
			WHERE guid_two = '$guid'  AND relationship = 'member'");
	
		//Array for dates
		$all_dates = array();
		foreach($rows as $row){
			array_push($all_dates, gmdate("Ymd", $row['time_created']));
		}
		
		$occurence = array_count_values($all_dates);
		$lineGraphData = array();
		$total = 0;
		
		foreach($occurence as $key => $value) {
			$temp = array();
			$temp["date"] = (string)$key;
			$temp["newMembers"] = $value;
			$total += $value;
			$temp["cummMembers"] = $total;
			array_push($lineGraphData, $temp);
		}
		
		return $lineGraphData; 
	}

	//---------------------------------------------------------------------Classes for General Stats Below----------------------------------------------------------------------------------

	//Gets the total number of users
	public function getTotalUsers (){
		//create search query and put it in a associative array
		$rowsTotal = $this->query("SELECT name, last_login FROM elggusers_entity");

		//Count the total number of users
		foreach($rowsTotal as $rowTotal){
			$count++;
		}
		return $count;
	}

	//Gets the percent increase of users in the last 365 days
	public function getUserPercentIncrease (){

		//Time a year ago in UNIX timestamp
		$timeYearAgo = time() - 31536000;

		//create search query and put it in a associative array
		$rowsYearAgo = $this->query("SELECT subtype, time_created FROM elggentities
			WHERE subtype = 'user' AND time_created > '$timeYearAgo'");
	
		//Counting all users joined in the past year
		foreach($rowsYearAgo as $row){
			$countPastYear ++;
		}
                                                           
		//Count all the users, and also counting all the active users
		//Active Users defined by users who have logged in in the past 6 months
		foreach($rowsTotal as $rowTotal){
			$lastLoginInt = (int)$rowTotal['last_login'];
			if ($lastLoginInt > time() - 31536000/2){
				$activeUserCount++;
			}
			$countTotal ++;
		}

		$percentIncrease = round($countPastYear/$this->getTotalUsers()*100);

		return $percentIncrease;
	}

	//Gets the total number of blogs
	public function getBlogTotal (){

		//query for all blogs
		$allrowsBlog = $this->query("SELECT time_created FROM elggentities 
				WHERE subtype = '5'");

		//Loop for total blogs
		foreach($allrowsBlog as $allrowBlog){
			$blogTotal++;
		}
		
		return $blogTotal;
	}

	//Gets percent increase for blogs in the last 365 days
	public function getBlogPercentIncrease(){
		
		//Time year ago
		$timeYearAgo = time() - 31536000;

		//query all blogs in the last 365 days
		$rowsBlog = $this->query("SELECT time_created FROM elggentities 
				WHERE subtype = '5' AND time_created > '$timeYearAgo'");

		//Counting all blogs posted in the past year
		foreach($rowsBlog as $rowBlog){
			$blogYearAgoCount++;
		}

		$percentIncrease = round($blogYearAgoCount/$this->getBlogTotal()*100);

		return $percentIncrease;
	}

	//Gets total number of discussiosn
	public function getDiscussionTotal (){
		
		//query for all discussions
		$allrowsDis = $this->query("SELECT time_created FROM elggentities 
				WHERE subtype = '7'");

		//Loop for total discussions
		foreach($allrowsDis as $allrowDis){
			$disTotal++;
		}

		return $disTotal;
	}

	//Gets percent increase for discussions in the last 365 days
	public function getDisPercentIncrease(){
		
		//Time year ago
		$timeYearAgo = time() - 31536000;

		//query all discussionsss in the last 365 days
		$rowsDis = $this->query("SELECT time_created FROM elggentities 
				WHERE subtype = '7' AND time_created > '$timeYearAgo'");

		//counting for posts in the last year
		foreach($rowsDis as $rowDis){
			$disYearAgoCount++;
		}

		$percentIncrease = round($disYearAgoCount/$this->getDiscussionTotal()*100);

		return $percentIncrease;
	}

	//Gets total number of pages
	public function getPageTotal (){

		//query for all pages
		$allrowsPage = $this->query("SELECT time_created FROM elggentities 
				WHERE subtype = '10'");

		//COUNTING FOR PAGES
		foreach($allrowsPage as $allrowPage){
			$pageTotal++;
		}

		return $pageTotal;
	}

	//Gets percent increase for pages in the last 365 days
	public function getPagePercentIncrease(){

		//Time year ago
		$timeYearAgo = time() - 31536000;

		//query all pages in the last 365 days
		$rowsPage = $this->query("SELECT time_created FROM elggentities 
				WHERE subtype = '10' AND time_created > '$timeYearAgo'");

		//counting for posts in the last year
		foreach($rowsPage as $rowPage){
			$pageYearAgoCount++;
		}

		$percentIncrease = round($pageYearAgoCount/$this->getPageTotal()*100);

		return $percentIncrease;
	}

	//Gets total number of ideas
	public function getIdeaTotal (){

		//query for all ideas
		$allrowsIdea = $this->query("SELECT time_created FROM elggentities 
				WHERE subtype = '42'");

		//COUNTING FOR IDEAS
		foreach($allrowsIdea as $allrowIdea){
			$ideaTotal++;
		}

		return $ideaTotal;
	}

	//Gets percent increase for ideas in the last 365 days
	public function getIdeaPercentIncrease(){

		//Time year ago
		$timeYearAgo = time() - 31536000;

		//query all ideeas in the last 365 days
		$rowsIdea = $this->query("SELECT time_created FROM elggentities 
				WHERE subtype = '42' AND time_created > '$timeYearAgo'");

		//counting for posts in the last year
		foreach($rowsIdea as $rowIdea){
			$ideaYearAgoCount++;
		}

		$percentIncrease = round($ideaYearAgoCount/$this->getIdeaTotal()*100);

		return $percentIncrease;
	}

	//Gets the total number of files
	public function getFileTotal (){

		//query for all files
		$allrowsFile = $this->query("SELECT time_created FROM elggentities 
				WHERE subtype = '1'");

		//COUNTING FOR FILESSSSS
		foreach($allrowsFile as $allrowFile){
			$fileTotal++;
		}

		return $fileTotal;
	}

	//Gets percent increase for files in the last 365 days
	public function getFilePercentIncrease(){

		//Time year ago
		$timeYearAgo = time() - 31536000;

		//query all filess in the last 365 days
		$rowsFile = $this->query("SELECT time_created FROM elggentities 
				WHERE subtype = '1' AND time_created > '$timeYearAgo'");

		//counting for posts in the last year
		foreach($rowsFile as $rowFile){
			$fileYearAgoCount++;
		}

		$percentIncrease = round($fileYearAgoCount/$this->getFileTotal()*100);

		return $percentIncrease;
	}

	//Get the total number of wire posts
	public function getWireTotal (){

		//query for all wire postsss
		$allrowsWire = $this->query("SELECT time_created FROM elggentities 
				WHERE subtype = '17'");

		//COUNTING FOR WIRESSSSSSSSS
		foreach($allrowsWire as $allrowWire){
			$wireTotal++;
		}

		return $wireTotal;
	}

	//Gets percent increase for files in the last 365 days
	public function getWirePercentIncrease(){

		//Time year ago
		$timeYearAgo = time() - 31536000;

		//query all wire posts in the last 365 days
		$rowsWire = $this->query("SELECT time_created FROM elggentities 
				WHERE subtype = '17' AND time_created > '$timeYearAgo'");

		//counting for posts in the last year
		foreach($rowsFile as $rowFile){
			$wireYearAgoCount++;
		}

		$percentIncrease = round($wireYearAgoCount/$this->getWireTotal()*100);

		return $percentIncrease;
	}

}

 ?>