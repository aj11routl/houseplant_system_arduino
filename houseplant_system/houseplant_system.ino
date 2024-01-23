//include libraries
#include <WiFiNINA.h>
#include "ThingSpeak.h"
#include "secrets.h"

//network ssid and password defined in secrets file
char ssid[] = SECRET_SSID;
char pass[] = SECRET_PASS;

WiFiClient client;
int status = WL_IDLE_STATUS;

// 
int HTTP_PORT = 80;
String HTTP_METHOD = "GET";
char HOST_NAME[] = "192.168.0.19";  // web server address
String PATH_NAME = "/insert_plant_data.php";

//thingspeak channel id and write key defined in secrets file
unsigned long myChannelNumber = SECRET_CH_ID;
const char * myWriteAPIKey = SECRET_WRITE_APIKEY;

// initialize values
int soilMoisture = 0;
int roomTemp = 0;

// define min and max values for soil moisture sensor
const int dry = 510;
const int wet = 195;

// define pins connected to arduino 
const int MOIST_SENSOR_PIN = A0;
const int RELAY_PIN = 5;
const int TEMP_SENSOR_PIN = 6;

void sendToWebServer(int stat1, int stat2, int stat3) {
  Serial.println("===========+++++===========");
  Serial.println("Attempt to update web server");
  Serial.println("===========+++++===========");

  // print your board's IP address:
  Serial.print("IP Address: ");
  Serial.println(WiFi.localIP());

  // connect to web server on port 80:
  if (client.connect(HOST_NAME, HTTP_PORT)) {
    // if connected:
    Serial.println("Connected to server");
    // make http request:
    // send http header
    client.println(HTTP_METHOD + " " + PATH_NAME + "?soil=" + String(stat1) + "&room=" + String(stat2) + "&hasBeenWatered=" + String(stat3) + " HTTP/1.1");
    client.println("Host: " + String(HOST_NAME));
    client.println("Connection: close");
    client.println();

    while (client.connected()) {
      if (client.available()) {
        // read incoming byte from the server
        char c = client.read();
        // print byte to serial
        Serial.print(c);
      }
    }

    // the server's disconnected, stop the client:
    client.stop();
    Serial.println();
    Serial.println("disconnected");
  } else {  // if not connected:
    Serial.println("connection failed");
  }

  Serial.println("===========+++++===========");
  Serial.println();
}

// function to send data to thingspeak channel
// parameters for each statistic on thingspeak channel
// optional string parameter for updating channel status
void sendToThingSpeak(int stat1, int stat2, String newStatus = "") {
  Serial.println("===========+++++===========");
  Serial.println("Attempt to update ThingSpeak");
  Serial.println("===========+++++===========");

  

  // set the fields with the values
  ThingSpeak.setField(1, stat1);
  ThingSpeak.setField(2, stat2);
  
  // set the status if parameter passed
  if (newStatus != "") {
    ThingSpeak.setStatus(newStatus);
  }
  
  // write to the ThingSpeak channel
  int x = ThingSpeak.writeFields(myChannelNumber, myWriteAPIKey);
  if(x == 200){
    Serial.println("Channel update successful.");
  }
  else{
    Serial.print("Problem updating channel. HTTP error code ");
    Serial.println(String(x));
  }
  Serial.println("===========+++++===========");
  Serial.println();
}

void setup() {
  //set pump to off
  pinMode(RELAY_PIN, OUTPUT);
  digitalWrite(RELAY_PIN, LOW);

  //initialise serial
  Serial.begin(9600); 
  while (!Serial) {
    ;
  }
  Serial.println("Serial port connected?");

  
  // check for the WiFi module:
  if (WiFi.status() == WL_NO_MODULE) {
    Serial.println("Communication with WiFi module failed!");
    // don't continue
    while (true);
  }

  // connect or reconnect to wifi
  if(WiFi.status() != WL_CONNECTED){
    Serial.print("Attempting to connect to SSID: ");
    Serial.println(SECRET_SSID);
    while(WiFi.status() != WL_CONNECTED){
      WiFi.begin(ssid, pass); // Connect to WPA/WPA2 network. Change this line if using open or WEP network
      Serial.print(".");
      delay(5000);     
    } 
    Serial.println("\nConnected.");
  }

  String fv = WiFi.firmwareVersion();
  if (fv != "1.0.0") {
    Serial.println("Please upgrade the firmware");
  }
    
  // initialise thingspeak
  ThingSpeak.begin(client);

  delay(11000);
}

void loop() {

  //temperature code
  int a = analogRead(TEMP_SENSOR_PIN);
  float resistance=(float)(1023-a)*10000/a; //get the resistance of the sensor;
  roomTemp=1/(log(resistance/10000)/4275+1/298.15)-273.15;//convert to temperature via datasheet&nbsp;;
  delay(1000);

  Serial.println("Room temperature: " + String(roomTemp) + " degrees celcius");
  //----------------

  bool hasBeenWatered = false;

  //moisture level as a percentage
  int soilMoisture = map(analogRead(MOIST_SENSOR_PIN), wet, dry, 100, 0);
 
  Serial.println("Soil moisture level: " + String(soilMoisture) + "%");

  // loop until level is above 30%
  while (soilMoisture < 30) {
    Serial.println("Moisture level too low. Watering plant");
    //turn on pump (low)
    digitalWrite(RELAY_PIN, HIGH);
    //wait 3 seconds
    delay(2000);
    //turn off pump
    digitalWrite(RELAY_PIN, LOW);

    //wait a minute before checking again
    delay(60000);

    soilMoisture = map(analogRead(MOIST_SENSOR_PIN), wet, dry, 100, 0);
 
    Serial.println("New soil moisture level: " + String(soilMoisture) + "%");
  }

  

  //-----------------------
  
  sendToThingSpeak(soilMoisture, roomTemp);
  sendToWebServer(soilMoisture, roomTemp, hasBeenWatered);

  delay(1200000); // wait 20 minutes
}