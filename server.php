<?php 
require('php_dbinfo.php');

$conn = new mysqli($sql_servername, $sql_username, $sql_password);
if($conn->connect_error) {  // detect the connection to database
    die('Connected Failed: ' . $conn->connect_error) . "<br>";
}
$conn->query('SET NAMES "utf8"'); // set the database charset: utf 8
$conn->select_db($database);

$host = '127.0.0.1';
$port = 8080;
$null = NULL;
set_time_limit(0);

// create TCP/IP stream socket
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
// reuseable port
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);

socket_bind($socket, $host, $port);

socket_listen($socket);

// inital the clients array and add the created socket to the list
$clients = array($socket);

// $lobby = array();
// $room_list = ['zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nigh'];

$count = 0;
while (true) {
	//manage multipal connections
	$changed = $clients;
	//returns the socket resources in $changed array
	// var_dump($changed);
	socket_select($changed, $null, $null, 0, 10);
	// $count++;
	// var_dump($changed);
	// if ($count == 2)
	// 	die('');
	//check for new socket
	if (in_array($socket, $changed)) {
		// var_dump($socket);
		// var_dump($changed);
		$socket_new = socket_accept($socket); //accpet new socket
		$clients[] = $socket_new; //add socket to client array
		
		$header = socket_read($socket_new, 1024); //read data sent by the socket
		perform_handshaking($header, $socket_new, $host, $port); //perform websocket handshake
		
		//make room for new socket
		$found_socket = array_search($socket, $changed);
		unset($changed[$found_socket]);
	}
	
	//loop through all connected sockets
	foreach ($changed as $changed_socket) {	
		
		//check for any incomming data
		while(socket_recv($changed_socket, $buf, 1024, 0) >= 1)
		{
			$received_text = unmask($buf); //unmask data
			$tst_msg = json_decode($received_text); //json decode 
			$user_username = $tst_msg->username; //sender name
			$user_message = $tst_msg->message; //message text
			$user_request = $tst_msg->request; //room
			if (!strcmp($user_request, 'Connect')) {	// add new user to the lobby
					// $lobby[] = $user_username;
					// $user_room = 'lobby';
					$sql = 'SELECT room FROM user_info WHERE username ="' . $user_username . '"';
					$result = $conn->query($sql);
					$row = $result->fetch_assoc();
					if (!strcmp($row['room'], 'none')) {
						$sql = 'UPDATE user_info SET room="lobby" WHERE username = "' . $user_username . '"';
						$conn->query($sql);
						Match($conn, $user_username);
					} else if (!strcmp($row['room'], 'lobby')) {
						Match($conn, $user_username);
					} else {
						Disconnect($conn, $user_username);
						$sql = 'UPDATE user_info SET room="lobby" WHERE username = "' . $user_username . '"';
						$conn->query($sql);
						Match($conn, $user_username);
					}

					//die($sql);
			} else if (!strcmp($user_request, 'Disconnect')) {
				Disconnect($conn, $user_username);
			} else if (!strcmp($user_request, 'Match')) {
				Match($conn, $user_username);
				// $sql = 'SELECT room FROM user_info  WHERE username="' . $user_username . '"';
				// $result = $conn->query($sql);
				// $row = $result->fetch_assoc();
				// if (!strcmp($row['room'], 'lobby')) {

			} else if(!strcmp($user_request, 'Send')) {	
				Send($conn, $user_username, $user_message);
			}
			break 2; //exist this loop
		}
		
		$buf = @socket_read($changed_socket, 1024, PHP_NORMAL_READ);
		if ($buf === false) { // check disconnected client
			// remove client for $clients array
			$found_socket = array_search($changed_socket, $clients);
			unset($clients[$found_socket]);
			//notify all users about disconnected connection
		}
	}
}
// close the listening socket
socket_close($socket);
// function match_other($user, $conn, $lobby, $rooms) {
// 	$sql = 'SELECT room FROM user_info  WHERE username="' . $user . '"';
// 	$result = $conn->query($sql);
// 	$row = $result->fetch_assoc();
// 	if (!strcmp($row['room'], 'none')) {
// 		foreach ($lobby as $key => $value) {
// 			if(strcmp($user, $value)) {
// 				$room = $rooms[0];	// rend the room
// 				$sql = 'UPDATE user_info SET room="' . $room . '" WHERE username = "' . $user . '" or username="' . $value . '"';
// 				$conn->query($sql);
// 				$sql = 'CREATE TABLE `'. $room .'` (
// 				`id` INT NOT NULL AUTO_INCREMENT,
// 				`user` VARCHAR(50) NOT NULL,
// 				`message` TEXT NOT NULL,
// 				`time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
// 				PRIMARY KEY (`id`)
// 				)';
// 				$conn->query($sql);
// 				$index  = array_search($user, $lobby);
// 				unset($rooms[0]);
// 				unset($lobby[$index]);
// 				unset($lobby[$key]);
// 				$response_text = mask(json_encode(array('type'=>'system', 'name'=>$user, 'message'=>'Matching Succeed', 'partner'=>$value)));
// 				send_message($response_text); //send data
// 				return;
// 			}
// 		}
// 		$response_text = mask(json_encode(array('type'=>'system', 'name'=>$user, 'message'=>'Matching Failed')));
// 		send_message($response_text); //send data
// 	}
// }
function Match($conn, $user_username) {
	$sql = 'SELECT username FROM user_info WHERE room="lobby"';
	$result = $conn->query($sql);
	while(($row = $result->fetch_assoc()) == TRUE) {
		if(strcmp($user_username, $row['username'])) {
			$room = findEmptyRoom($conn);
			if (strcmp($room, 'none_room')) { // there is a room
				$sql = 'UPDATE user_info SET room="' . $room . '" WHERE username = "' . $user_username . '" or username="' . $row['username'] . '"';
				$conn->query($sql);
				$sql = 'CREATE TABLE `'. $room .'` (
				`id` INT NOT NULL AUTO_INCREMENT,
				`user` VARCHAR(50) NOT NULL,
				`message` TEXT NOT NULL,
				`time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`)
				)';
				$conn->query($sql);
				$response_text = mask(json_encode(array('type'=>'system', 'name'=>$user_username, 'message'=>'Matching Succeed', 'partner'=>$row['username'])));
				send_message($response_text); //send data
				return;
			} 
		}
	}
	$response_text = mask(json_encode(array('type'=>'system', 'name'=>$user_username, 'message'=>'Matching Failed')));
	send_message($response_text); //send data
}
function Disconnect($conn, $user_username) {
	$sql = 'SELECT room FROM user_info WHERE username="' . $user_username . '"';
	$result = $conn->query($sql);
	$row = $result->fetch_assoc();
	if (strcmp($row['room'], 'none')) {
		// $room_list[] = $user_room;	//return room
		returnTheUsedRoom($conn, $row['room']);
		$sql = "SELECT username FROM user_info WHERE room = '" . $row['room'] . "' and username NOT IN ('" . $user_username . "')";
		$result = $conn->query($sql);
		$partner_row = $result->fetch_assoc();
		$partner = $partner_row['username'];
		$sql = 'UPDATE user_info SET room="none" WHERE room = "' . $row['room'] . '"';
		$conn->query($sql);
	} 
	$response_text = mask(json_encode(array('type'=>'system', 'name'=>$user_username, 'message'=>'Disconnected', 'partner'=>$partner)));
	send_message($response_text); //send data
}
function Send($conn, $user_username, $user_message) {
	//prepare data to be sent to client
	$sql = 'SELECT room FROM user_info WHERE username="' . $user_username . '"';
	$result = $conn->query($sql);
	$row =  $result->fetch_assoc();
	$room = $row['room'];
	$sql = 'INSERT INTO ' . $room . ' (user, message) VALUE ("' . $user_username . '", "' . $user_message . '")';
	$conn->query($sql);
	$response_text = mask(json_encode(array('type'=>'usermsg', 'name'=>$user_username, 'room'=>$room)));
	send_message($response_text); //send data
}
function findEmptyRoom($connection) {
	$sql = 'SELECT * FROM room_list';
	$result = $connection->query($sql);
	while(($row = $result->fetch_assoc()) == TRUE) {
		if (!strcmp($row['status'], 'empty')) {
			$sql = 'UPDATE room_list SET status ="full" WHERE id=' . $row['id'];
			$connection->query($sql);
			return $row['room'];
		}
	}
	return 'none_room';	// there doesn't contain empty room
}
function returnTheUsedRoom($connection, $room) {
	$sql = 'UPDATE room_list SET status="empty" WHERE room="' . $room .'"';
	$connection->query($sql); 
	$sql = 'DROP TABLE ' . $room;
	$connection->query($sql);
	//die($sql);
}
function send_message($msg)
{
	global $clients;
	foreach($clients as $changed_socket)
	{
		@socket_write($changed_socket,$msg,strlen($msg));
	}
	return true;
}


//Unmask incoming framed message
function unmask($text) {
	$length = ord($text[1]) & 127;
	if($length == 126) {
		$masks = substr($text, 4, 4);
		$data = substr($text, 8);
	}
	elseif($length == 127) {
		$masks = substr($text, 10, 4);
		$data = substr($text, 14);
	}
	else {
		$masks = substr($text, 2, 4);
		$data = substr($text, 6);
	}
	$text = "";
	for ($i = 0; $i < strlen($data); ++$i) {
		$text .= $data[$i] ^ $masks[$i%4];
	}
	return $text;
}

//Encode message for transfer to client.
function mask($text)
{
	$b1 = 0x80 | (0x1 & 0x0f);
	$length = strlen($text);
	
	if($length <= 125)
		$header = pack('CC', $b1, $length);
	elseif($length > 125 && $length < 65536)
		$header = pack('CCn', $b1, 126, $length);
	elseif($length >= 65536)
		$header = pack('CCNN', $b1, 127, $length);
	return $header.$text;
}

//handshake new client.
function perform_handshaking($receved_header,$client_conn, $host, $port)
{
	$headers = array();
	$lines = preg_split("/\r\n/", $receved_header);
	foreach($lines as $line)
	{
		$line = chop($line);
		if(preg_match('/\A(\S+): (.*)\z/', $line, $matches))
		{
			$headers[$matches[1]] = $matches[2];
		}
	}

	$secKey = $headers['Sec-WebSocket-Key'];
	$secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
	//hand shaking header
	$upgrade  = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
	"Upgrade: websocket\r\n" .
	"Connection: Upgrade\r\n" .
	"WebSocket-Origin: $host\r\n" .
	"WebSocket-Location: ws://$host:$port/demo/shout.php\r\n".
	"Sec-WebSocket-Accept:$secAccept\r\n\r\n";
	socket_write($client_conn,$upgrade,strlen($upgrade));
}



 ?>