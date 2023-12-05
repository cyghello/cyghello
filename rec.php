<?php
    session_start();
    $name = $_POST['username'];
    echo "骗你的呐~";
    // echo "$name";
    $_SESSION["uname"] = $name;
    
    // if ($_SESSION["name"] == "张三"){
    //     echo "是张三";
        
    // }else {
    //     echo "登录错了，因为你的名字".$name."不是张三";
    // }
    
    // if (3 > 2){
    //     echo "2222";
    // }
    // $a = 14;
    // if ($a >= 12 && $a < 14){
    //     echo "a变量在12~14的区间内";
    // }else{
    //     echo "不在";
    // }
?>