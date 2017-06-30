<?php 
    require('php_dbinfo.php');
    if(isset($_COOKIE['login'])) { // detect the login status
        header('Location: profile.php'); // retunr to login page
    }
    $conn = new mysqli($sql_servername, $sql_username, $sql_password);
    if ($conn->connect_error) {
        die('Connected Fail: '. $conn->connect_error). "<br>";
    }
    $conn->query('SET NAMES "utf8"');
    $conn->select_db($database);
    if (isset($_POST['register'])) {
        if (empty($_POST['reg_user'])) {  $empty_reg_user = true; }
        else if (empty($_POST['reg_pass'])) { $empty_reg_pass = true; }
        else if (empty($_POST['reg_check'])) { $empty_reg_check = true; }
        else if (empty($_POST['reg_name'])) { $empty_reg_name = true; }
        else if (empty($_POST['reg_email'])) { $empty_reg_email = true; }
        else {
            $sql = 'SELECT username FROM registration';
            $result = $conn->query($sql);
            while ($row = $result->fetch_assoc()) {
                if (!strcasecmp($row['username'], $_POST['reg_user'])) {
                    $repeat_reg_user = true;
                }
            }
            if (!isset($empty_reg_user)) {
                if (!strcmp($_POST['reg_pass'], $_POST['reg_check'])) {
                    $pass = hash('sha256', $_POST['reg_pass']);
                    $sql = 'INSERT INTO registration (username, password, email, name) VALUES ("'. $_POST['reg_user'] . '", "' . $pass . '", "' . $_POST['reg_email'] . '", "'. $_POST['reg_name'] . '")';
                    $conn->query($sql);
                    $sql = 'INSERT INTO user_info (username, name) VALUES ("' . $_POST['reg_user'] . '", "' . $_POST['reg_name'] . '")';
                    $conn->query($sql);
                    $conn->close();
                    setcookie('login', $_POST['reg_user'], time() + 600,  '/');   
                    header('Location: profile.php');

                } else {
                    $false_reg_pass = true;
                }
            }
        }


    } else if (isset($_POST['login'])) {
        if (empty($_POST['login_user'])) { $login_false = true; }
        else if (empty($_POST['login_pass'])) { $login_false = true; }
        else {
            $pass = hash('sha256', $_POST['login_pass']);
            $sql = 'SELECT username, password FROM registration';
            $result = $conn->query($sql);
            if (!$result) {
                die('Invalid query');
            }
            while($row = $result->fetch_assoc()) {
                if(!strcasecmp($row['username'], $_POST['login_user'])) {

                    if(!strcmp($row['password'], $pass)) {
                        $conn->close();
                        setcookie('login', $_POST['login_user'], time() + 600,  '/');   
                        header('Location: profile.php');
                    }
                }
            }
            $login_false = true;
            $conn->close();
        }
    }
 ?>
<!Doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <title>WeTalk&amp;Learn</title>
    <link rel="stylesheet" href="signIn.css">
    <script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.0/jquery.min.js"></script>
    <link rel="stylesheet" type="text/css" href="semantic.min.css">
    <script src="semantic.min.js"></script>
</head>
<body>
    <div class="back">
        <img class="imgplace"src="globalvillage.png">
    </div>
    <div class="login"></div>
    <div class="register"></div>
    <form action="signIn.php" method="POST">
        <div class="title1">Sign In 登入<hr>
            <?php if(isset($login_false)) echo "<div style='color: red;'>帳號密碼輸入錯誤</div>"; ?> 
            使用者帳號 <input class="textbox" type="text" name="login_user" maxlength="15" > <br><br> 
            使用者密碼 <input class="textbox" type="password" name="login_pass" maxlength="15" > <br><br>
            <input class="button" style="vertical-align:middle" type="submit" name="login" value="Login">
        </div> 
    </form>
    <form action="signIn.php" method="POST">
        <div class="title2">Sign Up 註冊<hr>
            <?php 
                if (isset($empty_reg_user)) echo "<div style='color: red;'>請輸入帳號</div>";
                else if  (isset($empty_reg_pass)) echo "<div style='color: red;'>請輸入密碼</div>";
                else if  (isset($empty_reg_check)) echo "<div style='color: red;'>請輸入確認密碼</div>";
                else if  (isset($empty_reg_name)) echo "<div style='color: red;'>請輸入姓名</div>";
                else if  (isset($empty_reg_email)) echo "<div style='color: red;'>請輸入信箱</div>";
                else if  (isset($repeat_reg_user)) echo "<div style='color: red;'>帳號已被註冊</div>";
                else if  (isset($false_reg_pass)) echo "<div style='color: red;'>密碼與<br>確認密碼不相符</div>";
            ?>
            使用者帳號 <input class="textbox" type="text" name="reg_user" maxlength="15" ><br> 
            使用者密碼 <input class="textbox" type="password" name="reg_pass" maxlength="15" ><br>
            再次確認密碼 <input class="textbox" type="password" name="reg_check" maxlength="15" ><br>
            使用者姓名 <input class="textbox" type="text" name="reg_name" maxlength="15" ><br>
            使用者信箱 <input class="textbox" type="email" name="reg_email" maxlength="35" ><br> 
            <input class="button2" style="vertical-align:middle" type="submit" name="register" value="Register">
        </div>        
    </form>
    <img class="boy"src="boy.png"><img class="girl"src="girl.png">
    <img class="talk1"src="conversation.png"><img class="talk2"src="speech-bubble.png"><img class="talk3"src="heart-black-shape.png">
</body>
</html>