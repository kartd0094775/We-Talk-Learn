<?php
require("php_dbinfo.php");


// Start XML file, create parent node
$dom = new DOMDocument("1.0","UTF-8");
//是否將文件格式化，true 生成的XML會斷行，false生成的xml不會斷行(打開文件只會有一行)
$dom  ->  formatOutput=true;         
$node = $dom->createElement("chat");
$parnode = $dom->appendChild($node);

// Opens a connection to a MySQL server
$conn = new mysqli($sql_servername, $sql_username, $sql_password);
if ($conn->connect_error) {
  die('Connected Failed : ' . $conn->connect_error) . "<br>";
}

// Set the active MySQL database
// $conn->query('SET NAMES "utf8"'); 
$sql = 'SET NAMES "utf8"';
$conn->query($sql);
$conn->select_db($database);

$username = $_REQUEST['user'];

$sql = "SELECT room FROM user_info WHERE username='" . $username ."'";
// $sql = "SELECT room FROM user_info WHERE username='test'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$room = $row['room'];

// Select all the rows in the markers table
$sql = "SELECT * FROM " . $room;
 // $sql = "SELECT * FROM zero";
$result = $conn->query($sql);

if (!$result) {
  die('Invalid query: ' . $conn->error) . "<br>";
}

header("Content-type: text/xml;charset=utf-8");

// Iterate through the rows, adding XML nodes for each
while ($row = $result->fetch_assoc()) {
  // ADD TO XML DOCUMENT NODE
  $node = $dom->createElement("messages");
  $newnode = $parnode->appendChild($node);

  $newnode->setAttribute("id", $row['id']);
  $newnode->setAttribute("user", $row['user']);
  $newnode->setAttribute("message", $row['message']);
  $newnode->setAttribute("time", $row['time']);
}
$conn->close();

$xmlfile = $dom->saveXML();
echo $xmlfile;

?>