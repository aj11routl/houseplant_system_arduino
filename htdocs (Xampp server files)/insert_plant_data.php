<?php

// library for twitter api oauth
require 'vendor/autoload.php';
use Abraham\TwitterOAuth\TwitterOAuth;

if( isset($_GET["soil"]) && isset($_GET["room"]) && isset($_GET["hasBeenWatered"]) ) {
   $soil = $_GET["soil"]; // get soil value from HTTP GET
   $room = $_GET["room"]; // get room temp val
   $hasBeenWatered = $_GET["hasBeenWatered"]; // get boolean value
   
   require __DIR__ . "/config.php";
   
   date_default_timezone_set('Europe/London');
   
   $date = date('Y-m-d H:i:s', time());

   $servername = SERVER_NAME;
   $username = USERNAME;
   $password = PASSWORD;
   $dbname = DATABASE_NAME;

   // Create connection
   $conn = new mysqli($servername, $username, $password, $dbname);
   // Check connection
   if ($conn->connect_error) {
      die("Connection failed: " . $conn->connect_error);
   }

   $sql = "INSERT INTO houseplant_data (soil_moisture, hasBeenWatered, room_temp, date_time) VALUES ($soil, $hasBeenWatered, $room, '$date')";

   if ($conn->query($sql) === TRUE) {
      echo "New record created successfully";
   } else {
      echo "Error: " . $sql . " => " . $conn->error;
   }

   $conn->close();
   
   // Twitter API Post
   
   $connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, ACCESS_TOKEN, ACCESS_TOKEN_SECRET);
   
   // possible tweet openings
   $tweetOptionsGreeting = Array(
		"HEY! ", 
		"GUESS WHAT!? ", 
		"Hello there.. ", 
		"Update! ", 
		"Time for a plant update. ", 
		"It's that time again. "
   );
   
   // main body of the tweet
   $tweetOptionsBody = Array(
		"Soil moisture levels detected at " . strval($soil) . "%. Room temp is " . strval($room) . " degrees celcius.",
		"Soil moisture level is now " . strval($soil) . "%! Room temp is " . strval($room) . " degrees celcius.",
		"Soil moisture level now at " . strval($soil) . "%. Room temp is " . strval($room) . " degrees celcius!",
		"This plant's soil moisture level is now " . strval($soil) . "%! Room temp is " . strval($room) . " degrees celcius."
	);
	
	// options if temp & soil are in good condition
	$tweetOptionsGood = Array(
		" Everything is good.",
		" Healthy growing conditions.",
		" All is well with our house plant"
	);
	
	// options if the plant has been watered
	$tweetOptionsWatered = Array(
		"House plant has been watered! ",
		"House plant has been watered. ",
		"Water levels were too low so the pump was turned on. ",
		"Moisture levels below 30 were detected. Plant has been watered. "
	);
	
	// tweet creation and concatenation
	
	$tweetText = $tweetOptionsGreeting[array_rand($tweetOptionsGreeting)];
	
	$tweetText = $tweetText . $tweetOptionsBody[array_rand($tweetOptionsBody)];
	
	if ($room > 15 && $room > 25) {
		$tweetText = $tweetText . $tweetOptionsGood[array_rand($tweetOptionsGood)];
	}
	
	if ($hasBeenWatered == "true") {
		$tweetText = $tweetOptionsWatered[array_rand($tweetOptionsWatered)] . $tweetText;
	}
	
	if ($room < 15) {
		$tweetText = $tweetText . " Temperature is fairly low. This might be an issue.";
	} else {
		if ($room > 25) {
			$tweetText = $tweetText . " Temperature is pretty high. This might be an issue.";
		}
	}
   
	$data =  [
	   'text' => $tweetText
	];

	$connection->setApiVersion('2');
	$content = $connection->post("tweets", $data, true);

	var_dump($content);
	
} else {
   echo "values not set";
}
?>