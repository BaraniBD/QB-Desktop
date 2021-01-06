<?php

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

// We need to make sure the correct timezone is set, or some PHP installations will complain
if (function_exists('date_default_timezone_set'))
{
	// * MAKE SURE YOU SET THIS TO THE CORRECT TIMEZONE! *
	// List of valid timezones is here: http://us3.php.net/manual/en/timezones.php
	date_default_timezone_set('Asia/Kolkata');
}


// Require the framework
require_once '../../QuickBooks.php';

// A username and password you'll use in: 
//	a) Your .QWC file
//	b) The Web Connector
//	c) The QuickBooks framework
//
// 	NOTE: This has *no relationship* with QuickBooks usernames, Windows usernames, etc. 
// 		It is *only* used for the Web Connector and SOAP server! 
$user = 'quickbooks';
$pass = 'Yaali';
$map = array(
	QUICKBOOKS_ADD_CUSTOMER => array( '_quickbooks_customer_add_request', '_quickbooks_customer_add_response' ),
	//QUICKBOOKS_ADD_SALESRECEIPT => array( '_quickbooks_salesreceipt_add_request', '_quickbooks_salesreceipt_add_response' ), 
	//'*' => array( '_quickbooks_customer_add_request', '_quickbooks_customer_add_response' ), 
	// ... more action handlers here ...
	);

// This is entirely optional, use it to trigger actions when an error is returned by QuickBooks
$errmap = array(
	3070 => '_quickbooks_error_stringtoolong',				// Whenever a string is too long to fit in a field, call this function: _quickbooks_error_stringtolong()
	// 'CustomerAdd' => '_quickbooks_error_customeradd', 	// Whenever an error occurs while trying to perform an 'AddCustomer' action, call this function: _quickbooks_error_customeradd()
	// '*' => '_quickbooks_error_catchall', 				// Using a key value of '*' will catch any errors which were not caught by another error handler
	// ... more error handlers here ...
	);

// An array of callback hooks
$hooks = array(
	// There are many hooks defined which allow you to run your own functions/methods when certain events happen within the framework
	// QuickBooks_WebConnector_Handlers::HOOK_LOGINSUCCESS => '_quickbooks_hook_loginsuccess', 	// Run this function whenever a successful login occurs
	);

/*
function _quickbooks_hook_loginsuccess($requestID, $user, $hook, &$err, $hook_data, $callback_config)
{
	// Do something whenever a successful login occurs...
}
*/

// Logging level
//$log_level = QUICKBOOKS_LOG_NORMAL;
//$log_level = QUICKBOOKS_LOG_VERBOSE;
$log_level = QUICKBOOKS_LOG_DEBUG;				
//$log_level = QUICKBOOKS_LOG_DEVELOP;		// Use this level until you're sure everything works!!!

// What SOAP server you're using 
//$soapserver = QUICKBOOKS_SOAPSERVER_PHP;			// The PHP SOAP extension, see: www.php.net/soap
$soapserver = QUICKBOOKS_SOAPSERVER_BUILTIN;		// A pure-PHP SOAP server (no PHP ext/soap extension required, also makes debugging easier)

$soap_options = array(		// See http://www.php.net/soap
	);

$handler_options = array(
	//'authenticate' => ' *** YOU DO NOT NEED TO PROVIDE THIS CONFIGURATION VARIABLE TO USE THE DEFAULT AUTHENTICATION METHOD FOR THE DRIVER YOU'RE USING (I.E.: MYSQL) *** '
	//'authenticate' => 'your_function_name_here', 
	//'authenticate' => array( 'YourClassName', 'YourStaticMethod' ),
	'deny_concurrent_logins' => false, 
	'deny_reallyfast_logins' => false, 
	);		// See the comments in the QuickBooks/Server/Handlers.php file

$driver_options = array(		// See the comments in the QuickBooks/Driver/<YOUR DRIVER HERE>.php file ( i.e. 'Mysql.php', etc. )
	//'max_log_history' => 1024,	// Limit the number of quickbooks_log entries to 1024
	//'max_queue_history' => 64, 	// Limit the number of *successfully processed* quickbooks_queue entries to 64
	);

$callback_options = array(
	);

// * MAKE SURE YOU CHANGE THE DATABASE CONNECTION STRING BELOW TO A VALID MYSQL USERNAME/PASSWORD/HOSTNAME *
// 
// This assumes that:
//	- You are connecting to MySQL with the username 'root'
//	- You are connecting to MySQL with an empty password
//	- Your MySQL server is located on the same machine as the script ( i.e.: 'localhost', if it were on another machine, you might use 'other-machines-hostname.com', or '192.168.1.5', or ... etc. )
//	- Your MySQL database name containing the QuickBooks tables is named 'quickbooks' (if the tables don't exist, they'll be created for you) 
$dsn = 'mysqli://root:@localhost/quickbooks';
echo "dsn".$dsn;
//$dsn = 'mysql://root:password@localhost/your_database';				// Connect to a MySQL database with user 'root' and password 'password'
//$dsn = 'mysqli://root:@localhost/quickbooks_mysqli';					// Connect to a MySQL database using the PHP MySQLi extension
//$dsn = 'mssql://kpalmer:password@192.168.18.128/your_database';		// Connect to MS SQL Server database
//$dsn = 'pgsql://pgsql:password@localhost/your_database';				// Connect to a PostgreSQL database 
//$dsn = 'pearmdb2.mysql://root:password@localhost/your_database';		// Connect to MySQL using the PEAR MDB2 database abstraction library
//$dsn = 'sqlite://example.sqlite';										// Connect to an SQLite database
//$dsn = 'sqlite:///Users/keithpalmerjr/Projects/QuickBooks/docs/example.sqlite';	// Connect to an SQLite database
$dbinitialized=QuickBooks_Utilities::initialized($dsn);
$initmsg='DB Initialized Check ' . $dbinitialized;
// $Driver = QuickBooks_Utilities::driverFactory($dsn);
$call_log_func = QuickBooks_Utilities::log($dsn,$initmsg,$log_level);
if (!$dbinitialized)
{
	// Initialize creates the neccessary database schema for queueing up requests and logging
	QuickBooks_Utilities::initialize($dsn);
	
	// This creates a username and password which is used by the Web Connector to authenticate
	QuickBooks_Utilities::createUser($dsn, $user, $pass);
	
	// Queueing up a test request
	// 
	// You can instantiate and use the QuickBooks_Queue class to queue up 
	//	actions whenever you want to queue something up to be sent to 
	//	QuickBooks. So, for instance, a new customer is created in your 
	//	database, and you want to add them to QuickBooks: 
	//	
	//	Queue up a request to add a new customer to QuickBooks
	//	$Queue = new QuickBooks_Queue($dsn);
	//	$Queue->enqueue(QUICKBOOKS_ADD_CUSTOMER, $primary_key_of_new_customer);
	//	
	// Oh, and that new customer placed an order, so we want to create an 
	//	invoice for them in QuickBooks too: 
	// 
	//	Queue up a request to add a new invoice to QuickBooks
	//	$Queue->enqueue(QUICKBOOKS_ADD_INVOICE, $primary_key_of_new_order);
	// 
	// Remember that for each action type you queue up, you should have a 
	//	request and a response function registered by using the $map parameter 
	//	to the QuickBooks_Server class. The request function will accept a list 
	//	of parameters (one of them is $ID, which will be passed the value of 
	//	$primary_key_of_new_customer/order that you passed to the ->enqueue() 
	//	method and return a qbXML request. So, your request handler for adding 
	//	customers might do something like this: 
	// 
	//	$arr = mysql_fetch_array(mysql_query("SELECT * FROM my_customer_table WHERE ID = " . (int) $ID));
	//	// build the qbXML CustomerAddRq here
	//	return $qbxml;
	// 
	// We're going to queue up a request to add a customer, just as a test...
	// 
	// NOTE: You would normally *never* want to do this in this file! This is 
	//	meant as an initial test ONLY. See example_web_connector_queueing.php for more 
	//	details!
	// 
	// IMPORTANT NOTE: This particular example of queueing something up will 
	//	only ever happen *once* when these scripts are first run/used. After 
	//	this initial test, you MUST do your queueing in another script. DO NOT 
	//	DO YOUR OWN QUEUEING IN THIS FILE! See 
	//	docs/example_web_connector_queueing.php for more details and examples 
	//	of queueing things up.
	
	$primary_key_of_your_customer = 5;

	$Queue = new QuickBooks_WebConnector_Queue($dsn);
	$Queue->enqueue(QUICKBOOKS_ADD_CUSTOMER, $primary_key_of_your_customer);
	
	// Also note the that ->enqueue() method supports some other parameters: 
	// 	string $action				The type of action to queue up
	//	mixed $ident = null			Pass in the unique primary key of your record here, so you can pull the data from your application to build a qbXML request in your request handler
	//	$priority = 0				You can assign priorities to requests, higher priorities get run first
	//	$extra = null				Any extra data you want to pass to the request/response handler
	//	$user = null				If you're using multiple usernames, you can pass the username of the user to queue this up for here
	//	$qbxml = null				
	//	$replace = true				
	// 
	// Of particular importance and use is the $priority parameter. Say a new 
	//	customer is created and places an order on your website. You'll want to 
	//	send both the customer *and* the sales receipt to QuickBooks, but you 
	//	need to ensure that the customer is created *before* the sales receipt, 
	//	right? So, you'll queue up both requests, but you'll assign the 
	//	customer a higher priority to ensure that the customer is added before 
	//	the sales receipt. 
	// 
	//	Queue up the customer with a priority of 10
	// 	$Queue->enqueue(QUICKBOOKS_ADD_CUSTOMER, $primary_key_of_your_customer, 10);
	//	
	//	Queue up the invoice with a priority of 0, to make sure it doesn't run until after the customer is created
	//	$Queue->enqueue(QUICKBOOKS_ADD_SALESRECEIPT, $primary_key_of_your_order, 0);
}

// Create a new server and tell it to handle the requests
// __construct($dsn_or_conn, $map, $errmap = array(), $hooks = array(), $log_level = QUICKBOOKS_LOG_NORMAL, $soap = QUICKBOOKS_SOAPSERVER_PHP, $wsdl = QUICKBOOKS_WSDL, $soap_options = array(), $handler_options = array(), $driver_options = array(), $callback_options = array()
$Server = new QuickBooks_WebConnector_Server($dsn, $map, $errmap, $hooks, $log_level, $soapserver, QUICKBOOKS_WSDL, $soap_options, $handler_options, $driver_options, $callback_options);
$response = $Server->handle(true, true);
