<!DOCTYPE html>
<html>
<head>
	<title>Your houseplant</title>

	<style>
	h1 {text-align: center;}
	p {text-align: center;}
	div {text-align: center;}
	</style>
</head>

<body>

	<?php

	require __DIR__ . "/config.php";

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
	
	$sql = "SELECT * FROM houseplant_data WHERE id > ((SELECT max(id) FROM houseplant_data) - 10);";
	$result = $conn->query($sql);
	
	if ($result !== false && $result->num_rows > 0) {
	  // output data of each row
	  while($row = $result->fetch_assoc()) {
		  
		$last_soil = $row["soil_moisture"];
		
		$last_temp = $row["room_temp"];
		
		$hasBeenWatered = $row["hasBeenWatered"] ? 'Yes' : 'No';
		
		$date_time = $row["date_time"];
		
		echo "ID: " . $row["ID"] . "&nbsp " . "Moisture: " . $last_soil. "% &nbsp Room temp: " . $last_temp. "°C " . "&nbsp Date/Time: " . $date_time . "&nbsp Watered: " . $hasBeenWatered . "<br>";
				
		$last_update = strtotime ( $date_time );
		
	  }
	} else {
	  echo "0 results";
	}

// get last entry where soil level is below 30
	$sql_last_watered = "SELECT * FROM houseplant_data WHERE id=(SELECT max(id) FROM houseplant_data WHERE hasBeenWatered= true);";
	$result_last_watered = $conn->query($sql_last_watered);
	
	if ($result_last_watered !== false && $result_last_watered->num_rows > 0) {
	  // output data of each row
	  while($row = $result_last_watered->fetch_assoc()) {
		
		$last_watered_time = $row["date_time"];
		$last_watered = strtotime ( $last_watered_time );
		
	  }
	} else {
	  echo "0 results";
	}
	$conn->close();
	?>

	<h1>
	Plant last watered <?php echo date ( 'd M Y h:i:s' , $last_watered ) ?>
	<br>
	===========================================
	<br>
	Last update (<?php echo date ( 'd M Y h:i:s' , $last_update ) ?>):
	<br>
	<br>
	Soil moisture level recorded at <?php echo $last_soil ?> %
	<br>
	<br>
	Temperature recorded at <?php echo $last_temp ?> °C 
	
	</h1>

</body>

</html>