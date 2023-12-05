<?php
    // $servername = "localhost"; $username = "dateuser"; $password = "Ldy1208155863@";
    // $conn = mysqli_connect($servername, $username, $password);
    // if (!$conn)
    // {
    //     die("Connection failed : " .mysqli_connect_error());
    //     echo "连接失败";
    // }
    // echo "连接成功";
    // mysqli_close($conn);
    
    $servername = "localhost";  //域名
    $username = "dateuser"; //账户名
    $password = "Ldy1208155863@"; //密码
    $dbname = "dateuser";   //数据库名
    
    //创建连接
    $conn = new mysqli($servername, $username, $password, $dbname);
    //检测连接
    if ($conn->connect_error)
    {
        die ("连接失败 ：".$conn->connect_error);
    }
    // $sql = "DELETE FROM user WHERE username = 'John'";
    
    // if ($conn->query($sql) === TRUE)
    // {
    //     echo "新纪录插入成功";
    // }else{
    //     echo "Error : ". $sql . "<br>" .$conn->error;
    // }
    // mysqli_query($conn, $sql);
    
    //查询
    $sql = "SELECT * FROM  user";
    $reslut = $conn->query($sql);
    if ($reslut)
    {
        $res = $reslut->fetch_all();
        var_dump($res);
        
        foreach ($res as $k=>$v){
            echo "Key=". $k. ", Value=".$v[3];
            echo "<br>";
        }
    }
    
    
    $conn->close();
    
?>
