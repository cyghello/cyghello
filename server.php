<?php
    /*
        AF_INET:  IPv4 网络协议。TCP 和 UDP都可使用此协议。
        AF_INET6: IPv6 网络协议。TCP 和 UDP都可使用此协议。
        AF_INET:  IPv4 网络协议。TCP 和 UDP都可使用此协议。
        AF_UNIX:  本地通讯协议。具有高性能和低成本的IPC（进程间通讯）。
        
        SOCK_STREAM   TCP 协议套接字。
        SOCK_DGRAM    UDP 协议套接字。
        
        SOL_TCP  TCP协议
        SOL_UDP  UDP协议
        
        
    */
    $socket1 = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    
    //172.19.31.212 本地监听
    //socket_bind($socket1, '172.19.31.212', 6001);
    
    //0.0.0.0 网络监听
    socket_bind($socket1, '0.0.0.0', 6001);
    
    //$socket1负责监听
    socket_listen($socket1,3);
    
    $clients = array($socket1);
    while (TRUE){
        //socket_select对读写套字节的数字是引用，为了保证clients不被改变，拷贝一份。
        $read = $clients;
        $write = null;
        $except = null;
        /*
            当read发生变化，说明：有客户端发生连接操作了，
            $write/$except均为null,所以只监测read数组的变化
            第四个参数：
            null,表示阻塞；socket_select()会阻塞一直到发生变化，0表示非阻塞，
            调用socke_select()后，立即返回，然后继续调用socke_select()不断循环
            返回值是修改后的数组中包含的套接字资源的数量
        */
        if (socket_select($read,$write,$except,null) > 0){
            if (in_array($socket1,$read))
            {
                //socket2 负责处理通信（接收、发生）
                $socket2 = socket_accept($socket1);
                $clients[] = $socket2;
                socket_write($socket2, 'You are '.(count($clients) - 1)." $clients connected\r\n");
                socket_getpeername($socket2,$ip,$port);
                echo "\nnew client[".$ip.":".$port."]\n";
                $key = array_search($socket1, $read);
                unset($read[$key]);
            }
        }
             if (count($read) > 0){
            foreach ($read as $socket_item){
                //读取客户端发来的数据
                //$msg = socket_read($socket_item,1024);
                $result = socket_recv($socket_item,$msg,1024,0);
                if ($result === false){
                    // no data 客户端未发消息
                    echo "no data".PHP_EOL;
                    continue;
                }
                elseif ($result === 0)
                {
                    //socket closed 客户端断开
                    socket_close($socket_item);  //关闭服务器端的socket
                    //清理已关闭的连接信息
                    $key1 = array_search($socket_item,$read);
                    unset($read[$key1]);
                    $key2 = array_search($socket_item,$clients);
                    unset($clients[$key2]);
                    echo "client [$socket_item] is closed!\n";
                }
                else{
                    echo "Server receive success [".$msg."]".time().PHP_EOL;
                }
                // if (false === $msg = @socket_read($socket_item,1024))
                // {
                //     socket_close($socket_item);
                //     $key = array_search($socket_item,$read);
                //     unset($read[$key]);
                //     unset($clients[$key]);
                //     echo "READ ERROR client $key is closed!\n";
                //     continue;
                // }
                
                // //发给自己 : $socket_item;
                // //socket_write($socket_item,'received:'.$msg);
                // if (false === @socket_write($socket_item, 'received:'.$msg))
                // {
                //     socket_close($socket_item);
                //     $key1 = array_search($socket_item,$read);
                //     unset($read[$key1]);
                //     $key2 = array_search($socket_item,$clients);
                //     unset($clients[$key2]);
                //     echo "Myclient $key is closed\n";
                //     continue;
                    
                // }
                
                foreach ($clients as $client_socket){
                    //发给别人（除去监听和自己）
                    if ($client_socket != $socket1 && $client_socket != $socket_item)
                    {
                        sleep(1);
                        //socket_write($client_socket,$msg,strlen($msg));
                        // if (false === @socket_write($client_socket,$msg,strlen(msg)))
                        // {
                        //     socket_close($client_socket);
                        //     $key1 = array_search($client_socket, $read);
                        //     unset($read[$key1]);
                        //     $key2 = array_search($client_socket,$read);
                        //     unset($clients[$key2]);
                        //     echo "otherclient $key is closed!\n";
                        // }
                        if (false === @socket_write($client_socket,$msg,strlen(msg)))
                        {
                            socket_close($client_socket);
                            $key1 = array_search($client_socket, $read);
                            unset($read[$key1]);
                            $key2 = array_search($client_socket,$read);
                            unset($clients[$key2]);
                            echo "otherclient [$socket_item] is closed!\n";
                        }
                    }
                }
            }
        }
        else{
            continue;
        }
        // if (count($read) > 0){
        //     foreach ($read as $socket_item){
        //         //读取客户端发来的数据
        //         $msg = socket_read($socket_item,1024);
                
        //         //发给自己 : $socket_item;
        //         socket_write($socket_item,'received:'.$msg);
                
        //         foreach ($clients as $client_socket){
        //             //发给别人（除去监听和自己）
        //             if ($client_socket != $socket1 && $client_socket != $socket_item)
        //             {
        //                 sleep(1);
        //                 socket_write($client_socket,$msg,strlen($msg));
        //             }
        //         }
        //     }
        // }
    }
    
    // //$socket2负责处理通信（接收、发送）
    // $socket2 = socket_accept($socket1);
    
    // //读取客户端发送的数据
    // $res = socket_read($socket2, 1024);
    // echo $res.PHP_EOL;
    
    // handshake($res,$socket2);
    // socket_write($socket2, encode('hello client'));
    
    // socket_close($socket2);
    // socket_close($socket1);
    
    ////建立websocket
    // function handshake($buffer,$client_socket){
    //     // 截取Sec-WebSocket-Key的值并加密
    //     $temp_str = substr($buffer,strpos($buffer, 'Sec-WebSocket-Key:')+18);
    //     $client_key = trim(substr($temp_str,0,strpos($temp_str,"\r\n")));
    //     $server_key = base64_encode(sha1($client_key."258EAFA5-E914-47DA-95CA-C5AB0DC85B11",TRUE));
    //     echo "client_key[".$client_key."]\n";
    //     echo "server_key[".$server_key."]\n\n";
        
    //     //组合信息并返回（浏览器接收后并验证，通过验证后则成功建立websocket连接）
        
    //     $handshake_msg = "HTTP/1.1 101 Switching Protocols\r\n";
    //     $handshake_msg .= "Upgrade: websocket\r\n";
    //     $handshake_msg .= "Sec-WebSocket-Version: 13\r\n";
    //     $handshake_msg .= "Connection: Upgrade\r\n";
    //     $handshake_msg .= "Sec-WebSocket-Accept: " . $server_key . "\r\n\r\n";
    //     socket_write($client_socket,$handshake_msg,strlen($handshake_msg));
    //     echo $handshake_msg;
        
        
    // }
    // function encode($buffer){
    //     $len = strlen($buffer);
    //     if ($len <= 125)
    //     {
    //         return "\x81" . chr($len) . $buffer;
    //     }elseif ($len <= 65535) {
    //         return "\x81" . chr(126) . pack("n", $len) . $buffer;
    //     }else {
    //         return "\x81" . chr(127) . pack("xxxxN", $len) . $buffer;
    //     }
    // }


?>