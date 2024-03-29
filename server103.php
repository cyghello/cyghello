<?php
/*
    version-1.03 设计聊天室场景，发送消息给指定的人，难点是要给每一个连接的人做一个ID号进行识别。
    键值key3：ID存储的键值
    二维数组：将套接字和ID组成一个数组
*/
    $socket1 = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    
    //172.19.31.212 
    //socket_bind($socket1, '172.19.31.212', 6001);
    
    //0.0.0.0 
    socket_bind($socket1, '0.0.0.0', 6001);
    
    //$socket1
    socket_listen($socket1,3); //套接字$socket1监听队列数为3
    
    //创建121.43.237.156网页的主ID号
    $clients = array($socket1);
    // $id = 0;
    // $idslave = 0;
    $idlist = array();
    while (TRUE){
        //socket_select
        $read = $clients;
        $write = null;
        $except = null;
        if (socket_select($read,$write,$except,null) > 0){
            foreach ($read as $socket_item){
                if ($socket_item == $socket1)
                {
                    if(!$socket_client = socket_accept($socket_item)) continue;
                        $hello = @socket_read($socket_client,1024);  //hello可以作为ID号的识别
                        if ($hello === false){
                            socket_close($socket_client);
                            continue;
                        }
                    $id = handshake($hello,$socket_client);
                    array_push($idlist,$id); //向数组中插入ID
                    socket_getpeername($socket_client,$ip,$port);//获取请求ip port
                    $clients[$ip.":".$port.":".$id] = $socket_client;
                    echo "new client[$clients]\n";
                    echo "new client[$socket_client]\n";
                    // handshake($hello,$socket_client);
                    // socket_getpeername($socket_client,$ip,$port);//获取请求ip port
                    // $clients[$ip.":".$port] = $socket_client;
                    //$idlist[$id] = $hello;
                    echo "new client[".$ip.":".$port."]\n";
                    echo "hello msg [".$hello."]\n";
                    echo "hello msg [".$id."]\n";
                }
                else
                {
                    $result = @socket_recv($socket_item,$msg1,1024,0);///
                    if ($result === false) continue;
                    if ($result === 0){
                        socket_close($socket_item);
                        $key1 = array_search($socket_item, $read);
                        unset($read[$key1]);
                        $key2 = array_search($socket_item, $clients);
                        unset($clients[$key2]);
                        // $key3 = array_search($id, $idlist);
                        // unset($idlist[$key3]);
                        echo "client [$socket_item] is closed!\n";
                    }
                    else{
                        //想要发送指定的ID
                        $idslave = recv_id ($result);
                        //                         /**
                        //  * 给指定的人发送消息
                        //  * @param message
                        //  */
                        // private void sendToUser(String message) {
                        //     String sendUserno = message.split("[|]")[1];
                        //     String sendMessage = message.split("[|]")[0];
                        //     String now = getNowTime();
                        //     try {
                        //         if (webSocketSet.get(sendUserno) != null) {
                        //             webSocketSet.get(sendUserno).sendMessage(now + "用户" + userno + "发来消息：" + " <br/> " + sendMessage);
                        //         } else {
                        //             System.out.println("当前用户不在线");
                        //         }
                        //     } catch (IOException e) {
                        //         e.printStackTrace();
                        //     }
                        // }
                        $web_msg = decode($msg1);
                        echo "$web_msg";
                        foreach ($clients as $client_socket){
                        //
                        if ($client_socket != $socket1)
                        {
                            $broadcast = encode("打开成功");
                            //sleep(1);
                            //socket_write($client_socket,$broadcast,strlen($broadcast));
                            if (false === @socket_write($client_socket,$broadcast,strlen($broadcast)))  //@表示写程序失败，继续执行下面的语句
                            {
                                socket_close($client_socket);
                                $key1 = array_search($client_socket, $read);
                                unset($read[$key1]);  //删除键值对应的socket
                                $key2 = array_search($client_socket,$read);
                                unset($clients[$key2]);
                                echo "otherclient [$socket_item] is closed!\n";
                                }
                            }
                        }
                        }
                    }
                }
            }
        
        else{
            continue;
        }
    }
    function recv_id ($buffer)
    {
        $pos = strrpos($buffer,"id");
        //这里先设置为固定的id字符长度
        $temp_str = substr($buffer,strpos($buffer, 'Sec-WebSocket-Key:')+18);
        $client_key = trim(substr($temp_str,0,strpos($temp_str,"\r\n")));
        $id = substr($buffer,$pos,strlen($client_key));
        return $id;
    }
    function handshake($buffer,$client_socket){
        // Sec-WebSocket-Key
        $temp_str = substr($buffer,strpos($buffer, 'Sec-WebSocket-Key:')+18);
        $client_key = trim(substr($temp_str,0,strpos($temp_str,"\r\n")));
        $server_key = base64_encode(sha1($client_key."258EAFA5-E914-47DA-95CA-C5AB0DC85B11",TRUE));
        echo "client_key[".$client_key."]\n";
        echo "server_key[".$server_key."]\n\n";
        
        //websocket
        
        $handshake_msg = "HTTP/1.1 101 Switching Protocols\r\n";
        $handshake_msg .= "Upgrade: websocket\r\n";
        $handshake_msg .= "Sec-WebSocket-Version: 13\r\n";
        $handshake_msg .= "Connection: Upgrade\r\n";
        $handshake_msg .= "Sec-WebSocket-Accept: " . $server_key . "\r\n\r\n";
        socket_write($client_socket,$handshake_msg,strlen($handshake_msg));
        echo $handshake_msg;
        
        return $client_key;
    }
    function encode($buffer){
        $len = strlen($buffer);
        if ($len <= 125)
        {
            return "\x81" . chr($len) . $buffer;
        }elseif ($len <= 65535) {
            return "\x81" . chr(126) . pack("n", $len) . $buffer;
        }else {
            return "\x81" . chr(127) . pack("xxxxN", $len) . $buffer;
        }
    }
    function decode($buffer){
        $len = $masks = $data = $decoded = null;
        $len = ord($buffer[1]) & 127;
        if ($len === 126)
        {
            $masks = substr($buffer, 4, 4);
            $data = substr($buffer, 8);
        }elseif ($len === 127) { 
            $masks = substr($buffer,10,4);
            $data = substr($buffer, 14);
        }else{
            $masks = substr($buffer, 2, 4);
            $data = substr($buffer, 6);
        }
        for ($index = 0; $index < strlen($data); $index++)
        {
            $decoded .= $data[$index] ^ $masks[$index % 4];
        }
        return $decoded;
    }
    function search($socket_client,$clients){
        $search = array_search($socket_client, $clients, TRUE);
        if ($search === null) $search = false;
        return $search;
    }
?>