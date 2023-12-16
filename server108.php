<?php
/*
    server107 与 server108作为两个服务器同时向同一个数据库访问，进行数据传输
    server108作为网页端服务器
*/
    $socket1 = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    
    //172.19.31.212 
    //socket_bind($socket1, '172.19.31.212', 6001);
    
    //0.0.0.0 
    socket_bind($socket1, '0.0.0.0', 6001);
    
    //$socket1
    socket_listen($socket1,3); //套接字$socket1监听队列数为3
    
    $clients = array($socket1);
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
                        $hello = @socket_read($socket_client,1024);
                        if ($hello === false){
                            socket_close($socket_client);
                            continue;
                        }
                    handshake($hello,$socket_client);
                    socket_getpeername($socket_client,$ip,$port);//获取请求ip port
                    $clients[$ip.":".$port] = $socket_client;
                    echo "new client[".$ip.":".$port."]\n";
                    echo "hello msg [".$hello."]\n";
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
                        echo "client [$socket_item] is closed!\n";
                    }
                    else{
                        
                        $web_msg = decode($msg1);
                        echo "$web_msg";
                        $broadcast = encode("打开成功");
                        //$id = search($socket_item,$clients);
                        //echo "client [".$id."] say:".$web_msg."\n"; ///recv + decode 
                        foreach ($clients as $client_socket){
                        //
                        if ($client_socket != $socket1)
                        {
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