<?php
require("php_dbinfo.php");


// Start XML file, create parent node
$dom = new DOMDocument("1.0","UTF-8");
//是否將文件格式化，true 生成的XML會斷行，false生成的xml不會斷行(打開文件只會有一行)
$dom  ->  formatOutput=true;         
$node = $dom->createElement("information");
$parnode = $dom->appendChild($node);

// Opens a connection to a MySQL server
$conn = new mysqli($sql_servername, $sql_username, $sql_password);
if ($conn->connect_error) {
  die('Connected Failed : ' . $conn->connect_error) . "<br>";
}

// Set the active MySQL database
// $conn->query('SET NAMES "utf8"'); 
$conn->select_db($database);

$user = $_REQUEST['user'];
$sql = 'SELECT room FROM user_info WHERE username="' . $user . '"';
$result = $conn->query($sql); 
$row = $result->fetch_assoc();
$room = $row['room'];
// $sql = "SELECT username, sex, age, career, nation FROM user_info WHERE room='" . $room . '"';
$sql = "SELECT username, sex, age, career, nation FROM user_info WHERE room = '" . $room . "' and username NOT IN ('" . $user . "')";
$result = $conn->query($sql);

if (!$result) {
  die('Invalid query: ' . $conn->error) . "<br>";
}

header("Content-type: text/xml;charset=utf-8");

// Iterate through the rows, adding XML nodes for each
while ($row = $result->fetch_assoc()) {
  // ADD TO XML DOCUMENT NODE
  $node = $dom->createElement("partner");
  $newnode = $parnode->appendChild($node);

  $newnode->setAttribute("username", $row['username']);
  $newnode->setAttribute("sex", $row['sex']);
  $newnode->setAttribute("age", $row['age']);
  $newnode->setAttribute("career", $row['career']);
  $newnode->setAttribute("nation", $row['nation']);
}
$conn->close();

$xmlfile = $dom->saveXML();
echo $xmlfile;

?>