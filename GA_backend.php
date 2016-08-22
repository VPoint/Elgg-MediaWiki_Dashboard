<?php

/**
*
* This class defines the constructor and functions for the google analytics api behind the dashboard. This simplifies
 and automates generating simple queries from the Core reporting API V4 and converts them to associative arrays in PHP.
 It also extends the functionality of the queries to create drill down functions.
*
* This class is intended for use with an AJAX and javascript associated files as a data collection platform for the
  resulting dashboard. The queries were intended for use with an elgg enabled social network (GCConnex), as well as
  a mediawiki site (GCPedia).
* 
* It has a dependency on the Google API PHP Client Library: google-api-php-client/src/Google/autoload.php
* 
* Esther Raji (Summer 2016)
*
**/

// Load the Google API PHP Client Library.
require_once 'google-api-php-client/src/Google/autoload.php';

class Google_Analytics_Object{
	// Defines API variables, such as the Analytics objects and the designated accountID
	private $accountId = 0;
	protected $service;
	protected $profile;
	
	// Defines query variables, such as the filter to use, the time span as well
	// as the metrics to return...
	private $use_filter;
	private $date_start;
	private $date_end;
	private $metrics;
	
	private $pageName; // displays the formatted name or keyword
	
	public function __construct($id) {
		$this->getService();
		//echo "before I break...";
		 // Get the user's chosen view (profile) ID.
		$this->getprofileId($this->service, $id);

		// Initializes the default values for the query parameters
		$this->metrics = 'ga:pageviews,ga:uniquePageviews,ga:avgTimeOnPage';
		$this->use_filter= 'wiki,';
		$this->date_start = "7daysAgo";
		$this->date_end = "today";
	}
	
	private function getService(){
	  // Creates and returns the Analytics service object.
		
	  // Use the developers console and replace the values with your
	  // service account email, and relative location of your key file.
	  $service_account_email = 'SERVICE ACCOUNT';
	  $key_file_location = 'KEY FILE';

	  // Create and configure a new client object.
	  $client = new Google_Client();
	  $client->setApplicationName("Visual Statistics");
	  $analytics = new Google_Service_Analytics($client);

	  // Read the generated client_secrets.p12 key.
	  $key = file_get_contents($key_file_location);
	  $cred = new Google_Auth_AssertionCredentials(
		  $service_account_email,
		  array(Google_Service_Analytics::ANALYTICS_READONLY),
		  $key
	  );
	  $client->setAssertionCredentials($cred);
	  if($client->getAuth()->isAccessTokenExpired()) {
		$client->getAuth()->refreshTokenWithAssertion($cred);
	  }

	  $this->service = $analytics;
	  return $analytics;
	}
	
	private function getprofileId(&$analytics, $id) {
	  // Get the user's chosen view (profile) ID.

	  // Get the list of accounts for the authorized user.
	  // This function is initialized to take the first value of everything,
	  // feel free to change the indexes to correspod to a specific view
	  $accounts = $analytics->management_accounts->listManagementAccounts();
		//echo "in GET PROFILE ";
	  if (count($accounts->getItems()) > 0) {
		$items = $accounts->getItems();
		$firstAccountId = $items[$id]->getId();
		
		// Get the list of properties for the authorized user.
		$properties = $analytics->management_webproperties
			->listManagementWebproperties($firstAccountId);
		
		if (count($properties->getItems()) > 0) {
		  $items = $properties->getItems();
		  $firstPropertyId = $items[0]->getId();
		
		  // Get the list of views (profiles) for the authorized user.
		  $profiles = $analytics->management_profiles
			  ->listManagementProfiles($firstAccountId, $firstPropertyId);

		  if (count($profiles->getItems()) > 0) {
			$items = $profiles->getItems();
			//echo "EXitting Get profile ";
			// Return the view (profile) ID in this position.
			$this->profile = $items[0]->getId();

		  } else {
			throw new Exception('No views (profiles) found for this user.');
		  }
		} else {
		  throw new Exception('No properties found for this user.');
		}
	  } else {
		throw new Exception('No accounts found for this user.');
	  }
	}
	
	public function getPageResults() {
		// Gets the stats for the top 50 pages for a certain filter
		
		// Returns an associative array containing the results of the query
		return $this->toAssocArray(
						$this->queryPageResults(
								$this->service,
								$this->profile
						)
				);
	}
		
 	protected function queryPageResults(&$analytics, $profileId) {
		// Defines the optional parameters such as dimensions, max-results, sort and filters
		// and limits the results to the top 50 pages for the query
		$optParams = array(
		  'dimensions' => 'ga:pagePath, ga:pageTitle',
		  'max-results' => '50',
		  'sort' => '-ga:pageviews',
		  'filters' => 'ga:pagePath=@' . $this->use_filter . 'ga:pagePath!~:|=|setlang;ga:pageTitle!~Error|#');

	  // Calls the Core Reporting API and queries for certain metrics
	  // within the defined timespan.
	   return $analytics->data_ga->get(
		   'ga:' . $profileId,
		   $this->date_start,
		   $this->date_end,
		   $this->metrics,
		   $optParams);
	}
	
	public function getLocationResults($dimen) {
		// Gets the stats for the top 10 locations visiting the site for a certain filter
		
		// Returns an associative array containing the results of the query
		$results = $this->queryLocationResults(
							$this->service,
							$this->profile,
							$dimen
						);
		return $this->toAssocArray($results);
	}

	protected function queryLocationResults(&$analytics, $profileId, $dimen) {
		// Defines the optional parameters such as dimensions, max-results, sort and filters
		// and limits the results to the top 10 locations for the query
		$optParams = array(
		  'dimensions' => 'ga:'. $dimen, // $dimen here can refer to either city, region or country to be a valid location
		  'max-results' => '10',
		  'sort' => '-ga:pageviews',
		  'filters' => 'ga:pagePath=@' . $this->use_filter . 'ga:pagePath!~:|=|setlang;ga:pageTitle!~Error|#'
		);

		// Calls the Core Reporting API and queries for certain metrics
	    // within the defined timespan.
		return $analytics->data_ga->get(
			   'ga:' . $profileId,
			   $this->date_start,
			   $this->date_end,
			   $this->metrics,
			   $optParams);
	}
	
	public function getTimeSpanResults() {
		// Gets the stats for the top days the site was visited for a certain filter
		
		// Returns an associative array containing the results of the query
		$results = $this->queryTimeSpanResults(
								$this->service,
								$this->profile
						);
		return $this->toAssocArray($results);
	}
	
	protected function queryTimeSpanResults(&$analytics, $profileId) {
		// Defines the optional parameters such as dimensions and filters.
		// This query is for the change in metrics over time
		$optParams = array(
		  'dimensions' => 'ga:date', // ga:month and ga:year are also valid parameters
		  'filters' => 'ga:pagePath=@' . $this->use_filter . 'ga:pagePath!~:|=|setlang;ga:pageTitle!~Error|#');
		  // Calls the Core Reporting API and queries for certain metrics
		  // within the defined timespan.
		   return $analytics->data_ga->get(
			   'ga:' . $profileId,
			   $this->date_start,
			   $this->date_end,
			   $this->metrics,
			   $optParams);
	}
	
	public function getTotalMetrics(){
		$results = $this->queryPageResults(
						$this->service,
						$this->profile
				);
		return $results->getTotalsForAllResults();
	}
	
	protected function toAssocArray(&$results) {
	  // Parses the response from the Core Reporting API and returns an associative array containing the values
	  // with the dimensions (or table headers) as keys
	  
	   if (count($results->getRows()) > 0) {
		   // if there are values in the results, get all values and column headers
			$rows = $results->getRows();
			$titles = $results->getColumnHeaders();
			
			$stats = array();
		
			foreach($rows as $value){
				// for each orw in the response, create a temporary array and fill it with the values as values and their
				// corresponding column headers as keys.
				$temp = array();
				foreach($value as $key => $item){
					$temp[substr($titles[$key]["name"], 3)] = str_replace([": GCconnex","GCconnex: ", "-", "—", " GCpedia"],"", $item);
				}
				$stats[] = $temp;
			}
		// Return associative array
		return $stats;
		
	  } else {
		return [];
	  }
	}
	
	protected function toColumnArray(&$results) {
	  // Parses the response from the Core Reporting API and returns a simple array with the column headers at the top
	  // and populated with values from that column.
	  if (count($results->getRows()) > 0) {
		   // if there are values in the results, get all values and column headers
			$rows = $results->getRows();
			$titles = $results->getColumnHeaders();
			
			$stats = array();
		
			foreach($rows as $value){
				// for each orw in the response, create a temporary array and fill it with the values as values and their
				// corresponding column headers as keys.
				$temp = array();
				foreach($value as $key => $item){
					$temp[substr($titles[$key]["name"], 3)] = str_replace([": GCconnex","GCconnex: ", "-", "—", " GCpedia"],"", $item);
				}
				$stats[] = $temp;
			}
		// Return associative array
		return $stats;
		
	  } else {
		return [];
	  }
	}
	
	public function resetObject(){
		// Initializes the default values for the query parameters
		$this->metrics = 'ga:pageviews,ga:uniquePageviews,ga:avgTimeOnPage';
		
		$this->date_start = "7daysAgo";
		$this->date_end = "today";
		
		$this->pageName = '';
	}
	
	public function setAccountID($newID){
		$this->accountId = $newID;
	}
	
	public function setFilter($newFilter){
		$this->use_filter = $newFilter;
	}
	
	public function getFilter(){
		return $this->use_filter;
	}
	
	public function setPageName($newName){
		$this->pageName = $newName;
	}
	
	public function getPageName(){
		return $this->pageName;
	}
	
	public function setTimeSpan($date_1, $date_2){
		$this->date_start = $date_1;
		$this->date_end = $date_2;
	}
	
	public function getTimeSpan(){
		return array("start"=>$this->date_start,"end"=>$this->date_end);
	}
	
	public function setMetrics($newMetric){
		$this->metrics = $newMetric;
	}
	
	public function getMetrics(){
		return $this->metrics;
	}
	
	public function getProfileName(){
		// Get the profile name.
		//return json_encode($this->service->management_accounts->listManagementAccounts());
		$results = $this->getPageResults(
								$this->service,
								$this->profileId
								);
		return $results->getProfileInfo()->getProfileName();
	}
}

/* A subclass of the Google_Analytics_Object, it inherits the dependencies and functions of it's parent.
It creates a Google Analytics Object and queries specialized for an elgg social network site, including group by
object type or group.
*
* This class is intended for use with an AJAX and javascript associated files as a data collection platform for the
  resulting dashboard. The queries were intended for use with an elgg enabled social network (GCConnex).
* 
* It has a dependency on the Google API PHP Client Library: google-api-php-client/src/Google/autoload.php
* 
* Esther Raji (Summer 2016)
*/

class elggENV_Analytics_Object extends Google_Analytics_Object{
	// Defines query variables, such as the filter to use as well as the parent path objects, the time span as well
	// as the metrics to return...
	//private $use_filter;
	private $use_precision;
	// $use_precision is for the elgg based social network as each page was grouped within broad object parent paths
	// this allowed for a more accurate filtering possibility
	
	private $guid;
	
	private $type; // displays the general formatted group of the page
	private $pageName; // displays the formatted name or keyword
	
	public function __construct() {
		// Calls the parent constructor
		parent::__construct(1);
		
		$this->use_precision = '/groups/profile/';
		parent::setFilter($this->use_precision . ';');
		$this->pageName = '';
	}
	
	public function setPrecision($newType){
		$this->use_precision = $newType;
	}
	
	public function getPrecision(){
		return $this->use_precision;
	}
	
	public function setGUID($newGUID){
		$this->guid = $newGUID;
	}
	
	public function getGUID(){
		return $this->guid;
	}
	
	public function getGroup(){
		$this->type = ucfirst (str_replace(['/', 'view', 'profile', '_c', 'missions'], ['', '', '', ' C', 'Micro-Missions'], $this->use_precision));
		return $this->type;
	}
}

/* A subclass of the Google_Analytics_Object, it inherits the dependencies and functions of it's parent.
It creates a Google Analytics Object and queries specialized for a media wiki site, including drill-down functionality.
*
* This class is intended for use with an AJAX and javascript associated files as a data collection platform for the
  resulting dashboard. The queries were intended for use with a mediawiki site (GCPedia).
* 
* It has a dependency on the Google API PHP Client Library: google-api-php-client/src/Google/autoload.php
* 
* Esther Raji (Summer 2016)
*/

class wikiENV_Analytics_Object extends Google_Analytics_Object{
	private $level;
	private $pageName;
	private $memory;
	
	public function __construct() {
		// Calls the parent constructor
		parent::__construct(0);
		$this->level = 2;
	}
	
	/*************************CHANGING PAGE LEVELS****************************/
	public function pageDrillDown(){
		if($this->level <= 4){
			$this->level += 1;
		} else {
			$this->level = 1;
		}
		//return getPageResults();
	}
	
	public function pageDrillUp(){
		if($this->level <= 1){
			$this->level = 1;
			//return array_pop($this->memory);
		} else {
			$this->level -= 1;
		}
		//return getPageResults();
	}
	
	public function getPageResults() {
		// Gets the stats for the top 50 pages at a specific PagePathLevel for a certain filter
		$results = $this->queryTableResults(
								$this->service,
								$this->profile,
								$this->level
						);
		// Returns an associative array containing the results of the query
		return $this->toAssocArray(
						$results
				);
	}
	
	public function queryTableResults(&$analytics, $profileId, $lvl) {
		// Defines the optional parameters such as dimensions, max-results, sort and filters
		// and limits the results to the top 50 pages for the query
		$optParams = array(
		  'dimensions' => 'ga:pagePathLevel' . $lvl .',ga:pageTitle',
		  'max-results' => '50',
		  'sort' => '-ga:pageviews',
		  'filters' => 'ga:pagePath=@' . parent::getFilter() . 'ga:pagePath!~:|=|setlang');
		  
		  $timespan = parent::getTimeSpan();
		
	  // Calls the Core Reporting API and queries for certain metrics
	  // within the defined timespan.\
	   return $analytics->data_ga->get(
		   'ga:' . $profileId,
		   $timespan["start"],
		   $timespan["end"],
		   parent::getMetrics(),
		   $optParams);
	}
	
	public function getLevel(){
		return $this->level;
	}
	
	public function setLevel($lvl){
		$this->level = $lvl;
	}
	
	public function getStatsSpecific($top_page){
		// Specifi
		parent::setFilter('wiki;ga:pagePath==/wiki' . $top_page . ';ga:pagePath!@'. $top_page . '/;');
		$this->level -= 1;
		//return getPageResults();
	}
	
	public function incrementPageName($next_value){
		$this->pageName .= $next_value;
	}
	
	public function printMemory(){
		return var_dump($this->memory);
	}
	
	public function addMemory($temp){
		$this->memory = $temp;
	}
}
?>