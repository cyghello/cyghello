<?php
    $host = '172.19.31.212';
    //$host = '192.168.31.1'; //SocketTool(win)
    //$host = '192.168.18.5'; //xSocket(MacOs)
    
    $port = 6001;
    $sock = null;
    if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false){
        //创建socket
        echo "socket_create failed : reason :".socket_strerror(socket_last_error())."\n";
        return;
    }
    if ($con = socket_connect($sock,$host,$port) === false){
        echo 'connect fail'."\n";
        return;
    }else{
        echo 'connect success'."\n";
    }
    $client_msg = 'hello server!';
    
    if (socket_write($sock,$client_msg,strlen($client_msg)) == false){
        echo 'fail to write'.socket_strerror(socket_last_error());
    }else{
        echo 'client write success'.PHP_EOL."\n";
        //读取服务端返回来的套接流信息
        while($serverback = socket_read($sock, 1024)){
            echo 'server return message is : '.PHP_EOL.$serverback;
        }
    }

?>