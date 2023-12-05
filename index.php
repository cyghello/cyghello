<?php
    header("Content-Type: text/html; charset=utf8");
    
    $name=$_POST['username'];//post获取表单里的name
    // $password=md5($_POST['password']);//post获取表单里的password
    $password=$_POST['password'];
    // echo "$name";
    // echo "$password";
    $server = "localhost";//主机
    $db_username = "dateuser";// MySQL 数据库用户名
    $db_password = "Ldy1208155863@";// MySQL 数据库密码
    $db_name = "dateuser";//你的数据库名字
    
    $con = new mysqli($server, $db_username, $db_password,$db_name);//链接数据库
    
    // 检测连接
    if (!$con) {
        die("Connection failed: " . mysqli_connect_error());
    }
    
    // $name = 1;
    // $password = 1;
    //include('connect.php');//链接数据库  //同'C'语言
    $q="select * from `user` where `uid` = '$name' and `gender` = '$password'";
    
    //向数据库查询表单传来的值的sql
    $con->query('SET NAMES UTF8');
    $result = $con->query($q);// 执行 sql
    
    // 获取执行 sql 后的返回对象
    $obj=$result->fetch_assoc();
    if (mysqli_num_rows($result) > 0){
    
        // 管理员
        if($obj["role"] == '1'){
            
            echo"管理员登录成功";
        }else{
            $query=mysqli_query($con,$q);
		    $rs=mysqli_fetch_assoc($query);
		    $js=json_encode($rs);
		    echo "$js";
		  //  printf ($js);
            //echo "普通用户登录成功";
        }
    
    }else{
        echo "用户名或密码错误";
    }
    $con->close();//关闭数据库
// 	echo "first program"."1315997208"."<br/>";
// 	echo 1+34;
//     // 使用美元符号 $ (表示变量的前缀)
//     $money = 0; //int
//     //$money = "no body"; //string
//     //$money = "89.3";  //float
//     //$money = false; //boolean true or false
//     //PHP是解释性语言  ，编译语言，非强类型 //C语言必须定义变量类型
//     echo "i have $money";
//     //格式 ： if （判断语句）{执行语句}
//     if ($money)
//     {
//         echo "i have $money";
//     }else {
//         echo "i have no money";
//     }
    
//     // 单引号 
//     // 双引号 可以判断变量
//     echo '$money';
//     echo "<br/>$money";
//     //print //web 了解就行
//     //printf //web 了解就行
//     // 前端和PHP的配合
//     $n = 0;
//     while ($n < 6)
//     {
//         echo "<div> <video src='' height='' width='' preload='none' autoplay='autoplay' controls=''></video> </div>";
//         $n++;
//     }
?>
