<?php 
    require('php_dbinfo.php');
    if(!isset($_COOKIE['login'])) { // detect the login status
        header('Location: signin.php'); // retunr to login page
    }
    setcookie('login', $_COOKIE['login'], time() + 600,  '/');  // reset the cookie time: 10min
    $conn = new mysqli($sql_servername, $sql_username, $sql_password);
    if($conn->connect_error) {  // detect the connection to database
        die('Connected Failed: ' . $conn->connect_error) . "<br>";
    }
    $conn->query('SET NAMES "utf8"'); // set the database charset: utf 8
    $conn->select_db($database);
    $sql = 'SELECT username, name, sex, age, career, nation, learning_language, coin FROM user_info WHERE username="' . $_COOKIE['login'] .'"';
    $result = $conn->query($sql);
    $row = $result->fetch_assoc(); 
    $username = $row['username'];
    $name = $row['name'];
    $sex = $row['sex'];
    $age = $row['age'];
    $career = $row['career'];
    $nation = $row['nation'];
    $learning_language = $row['learning_language'];

 ?>
<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <title>WeTalk&amp;Learn</title>
    <link rel="stylesheet" href="chatting.css">
    <script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.0/jquery.min.js"></script>
    <link rel="stylesheet" type="text/css" href="semantic.min.css">
    <script src="semantic.min.js"></script>
</head>
<body>
    <div class="back" id="background">
<!--             <div class="you">  -->   
    <div class="chat_wrapper" id=chat_wrapper>
        <div class="message_box" id="message_box"></div>
        <div class="panel ui fluid input" id="panel">

            <button class="ui red button" id="send-btn">Send</button>
            <input type="text" name="message" id="message" placeholder="Message" maxlength="80" paddding-left:10px; />

<!--             <input type="text" name="name" id="name" placeholder="Your Name" maxlength="10" style="width:20%"  /> -->
<!--             <input type="text" name="message" id="message" placeholder="Message" maxlength="80" style="width:60%" />
            <button id="send-btn">Send</button>
         -->
        </div>
    </div>
<!--     <button id="re-btn">Retry</button> -->
        <img src="ellipsis.gif" id="loading_img">

<!--         <div class="chatboxbig">
            <div class="chatbox2"></div>
           <div class="chatbox3"></div>
           <textarea></textarea>
           <button class="passbutton">
            <img src="enter-arrow.png">
           </button>
        </div>
        <div class="me"></div> -->
<!--     </div> -->
<script>
$(document).ready(function() {
    var wsUri = "ws://127.0.0.1:8080/";
    websocket = new WebSocket(wsUri);
    websocket.onopen = function(ev) {
        $('#message_box').append("<div class=\"system_msg\">Connected!</div>");
        // $('#background').css("transition", "2s");
        // $('#chat_wrapper').css("transition", "2s");
        // $('#chat_wrapper').css("height", "50%");
        // $('#panel').css("height", "150%");
        // $('#message_box').css("height", "150%");
       // $('#background').css("padding-top", "25%");

        console.log('連接成功');
        sendMessage('', 'Connect');
    };
    websocket.onmessage = function(ev) {
        var msg = JSON.parse(ev.data);
        var type = msg.type;
        var umsg = msg.message;
        var uname = msg.name;
        var partner = msg.partner;
        var room = msg.room;
            // $('#panel').append("<button id=\"send-btn\">Send</button>");
        if (type == 'usermsg') 
        {
            cleanMessageBox();
            downloadUrl("php_genxml.php?user=" + <?php echo '"' . $username.'"' ?>, function(data) {
                var xml = data.responseXML;
                var messages = xml.documentElement.getElementsByTagName("messages");
                for (var i = 0; i < messages.length; i++) {
                    var user = messages[i].getAttribute('user');
                    var message = messages[i].getAttribute('message');
                    $('#message_box').append("<div><span class=\"user_name\">"+user+"</span> : <span class=\"user_message\">"+message+"</span></div>");
                }
            });
            $('#message').val('');
            console.log("新訊息");

           // $('#message_box').append("<div><span class=\"user_name\">"+uname+"</span> : <span class=\"user_message\">"+umsg+"</span></div>");
        }
        else if (type == 'system')
        {
            if (umsg == 'Matching Failed') {
                //sendMessage('lobby', 'Retry');
                console.log('無法配對');
            }
            else if (umsg == 'Matching Succeed') {
                var user = <?php echo '"'.$username.'"' ?>;
                downloadUrl("php_partnerinfo.php?user=" + user, function(data) {
                    var xml = data.responseXML;
                    var partner = xml.documentElement.getElementsByTagName("partner");
                    var pname = partner[0].getAttribute('username');
                    var psex = partner[0].getAttribute('sex');
                    var page = partner[0].getAttribute('age');
                    var pcareer = partner[0].getAttribute('career');
                    var pnation = partner[0].getAttribute('nation');
                });
                if (user == uname || user == partner) { 
                        displayMatchingInfo(uname, partner);
                }
            } else if (umsg == 'Disconnected') {
                var user = <?php echo '"'.$username.'"' ?>;
                // $('#message_box').append("<div class =\"system_msg\">" + partner + "</div>");
                if (user == partner) {
                    $('#message_box').append("<div class =\"system_msg\">對話結束</div>");
                    $('#message_box').append("<div class =\"system_msg\">" + uname + "離開房間</div>");
                    window.history.go(-1);
                }

            }
        }
    };

    websocket.onerror = function(ev) {

    };
    websocket.onclose = function(ev) {
       // sendMessage('zero', 'Disconnect');

    };
    function sendMessage(message, request) {
        var mymessage  =  message
        var myusername = <?php echo '"'.$username.'"' ?>;
        var myrequest = request
        var msg = {
            message: mymessage, 
            username: myusername,
            request: myrequest
        };
        websocket.send(JSON.stringify(msg));

    }
    function downloadUrl(url, callback) {
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (xhttp.readyState == 4 && xhttp.status == 200) {
                callback(xhttp, xhttp.status);
            }
        };
        xhttp.open("GET", url, true);
        xhttp.send();

    }
    function displayMatchingInfo(username, partner) {
        $('#send-btn').click(function() {
            var message = $('#message').val();
            if(message == "") {
                return;
            } 

            console.log('傳送');
            sendMessage(message, 'Send');

        });

        $(document).keypress(function(e) {
            if(e.which == 13) {
                var message = $('#message').val();
                if(message == "") {
                    return;
                } 

                console.log('傳送');
                sendMessage(message, 'Send');
            }
        });
        $('#message_box').append("<div class =\"system_msg\">" + username + " get in the room.</div>");
        $('#message_box').append("<div class =\"system_msg\">" + partner + " get in the room.</div>");
        $("#loading_img").remove();
    }
    function cleanMessageBox() {
        var box = document.getElementById("message_box");
        box.innerHTML = "";
    }

    $('#re-btn').click(function() {
        sendMessage('', 'Match');
        console.log("重試");
    });
    $(window).on("beforeunload", function() {
        sendMessage('', "Disconnect");
    });


});

</script>
</body>
</html>