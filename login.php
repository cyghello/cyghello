<?php
    // $username = $_POST["username"];
    // $passwd = $_POST["passwd"];
    // echo "该用户${username}已经登陆，密码为${passwd}";
    //session缓存
    session_start();
    
    if(isset($_POST['username']) && isset($_POST['passwd'])){
        $username = $_POST['username'];
        $passwd = $_POST['passwd'];
        if (strlen(trim($username)) > 0 && strlen(trim($passwd)) > 6 && !isset($_SESSION['username'])){
            $_SESSION['username'] = $username;
            echo"<h1>欢迎尊贵的".$username.",您已经登录成功</h1>";
            
        }elseif (strlen(trim($username)) > 0 && strlen(trim($passwd)) > 6  && $_SESSION['username']) {
            echo "<h1>尊贵的".$username."您已经登录过，无需重复登录！</h1>";
            unset($_SESSION['username']);
        }else {
            echo "<h1>登录失败，用户名不符合规范</h1>";
        }
    }else {
        echo "<h1> 参数错误，已被服务器记录在案</h1>";
    }


?>