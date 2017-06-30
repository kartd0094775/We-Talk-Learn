<?php 
    require("php_dbinfo.php");
    if(isset($_POST['logout'])) {
        unset($_COOKIE['login']);
        setcookie('login', '', time() - 3600,  '/');
    }
    if(!isset($_COOKIE['login'])) { //detect the login status
        header('Location: signin.php'); // retunr to login page
    }
    setcookie('login', $_COOKIE['login'], time() + 600,  '/');  //reset the cookie tine: 10min
    $conn = new mysqli($sql_servername, $sql_username, $sql_password);
    if($conn->connect_error) {  // detect the connection to database
        die('Connected Failed: ' . $conn->connect_error) . "<br>";
    }
    $conn->query('SET NAMES "utf8"'); // set the database charset: utf 8
    $conn->select_db($database);
    if(isset($_POST['vertify'])) {
        if (empty($_POST['modify_age'])) {
            $_POST['modify_age'] = 'NULL';
        } 
        $sql = 'UPDATE user_info  SET
        sex = "' . $_POST['modify_sex'] .'",
        age = ' . $_POST['modify_age'] .', 
        career = "' . $_POST['modify_career'] .'", 
        nation = "' . $_POST['modify_nation'] .'", 
        learning_language = "' . $_POST['modify_learning_language'] .'"
        WHERE username = "' . $_COOKIE['login'] .'"';
        $result = $conn->query($sql);
        if (!$result) {
            echo 'Invalid query: ' . $sql;
        }
    }
    $sql = 'SELECT name, sex, age, career, nation, learning_language, coin FROM user_info WHERE username="' . $_COOKIE['login'] .'"';
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();

 ?>
<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <title>WeTalk&amp;Learn</title>
    <link rel="stylesheet" href="profile.css">
    <script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.0/jquery.min.js"></script>
    <link rel="stylesheet" type="text/css" href="semantic.min.css">
    <script src="semantic.min.js"></script>
</head>
<body>
    <div class="back">
        <div class="round-button">
            <div class="round-button-circle">
                <a href="chatting.php" class="round-button">GO!</a>
            </div>
        </div>
        <form action="profile.php" method="POST">
            <div class="register form">
                <div class="personHead"><div class="personHead-circle"></div></div>
                <div class="list">
                    <p>姓名: <?php echo $row['name']; ?> </p>
                    <p>性別: <?php echo isset($_POST['modify'])? 
                    ' <select name="modify_sex" id="modify_sex"><option value="男">男</option><option value="女">女</option></select>'
                    : $row['sex']; ?> </p>
                    <p>年齡: <?php echo isset($_POST['modify'])? '<input class="ui input" min="0"  max="150" type="number"  name="modify_age" value="' . $row['age'] .'">' : $row['age']; ?> </p>
                    <p>職業: <?php echo isset($_POST['modify'])? '<input class="ui input" type="text" name="modify_career" value="' . $row['career'] .'">' : $row['career']; ?> </p>
                    <p>國籍: <?php echo isset($_POST['modify'])? '<select name="modify_nation" id="modify_nation"><option value="台灣">台灣</option><option value="美國">美國</option></select>' 
                    : $row['nation']; ?> </p>
                    <p>想學習語言: <?php echo isset($_POST['modify'])? 
                    '<select  name="modify_learning_language" id="modify_language"><option value="中文">中文</option><option value="英文">英文</option></select>' 
                    : $row['learning_language']; ?> </p>
                    <p>目前coin數: <?php echo $row['coin']; ?> </p>
                </div>
                <div style="position: absolute; bottom: 0; right: 0;">
                    <?php 
                        echo isset($_POST['modify'])?'<div class="ui buttons"><input class="positive ui button" type="submit" name="vertify" value="儲存"><div class="or"></div><input class="ui button" type="submit" value="取消"></div>':
                        '<input class="positive ui button" type="submit" name="modify" value="修改">';
                     ?>
                     <input class="negative ui button" type="submit"  name="logout" value="登出">
                </div>
            </div>
        </form>
    </div>
</body>
</html>