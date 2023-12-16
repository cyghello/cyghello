
<?php
 
/**
 * 读取socket数据.
 *
 * @author yzm
 *
 * @param $socket
 * @param bool|true $isDividePkg
 * @return array|null|string
 */
function socketRead($socket, $isDividePkg = true)
{
    $rst = null;
 
    $buf = socket_read($socket, 8192);
    if ($isDividePkg) {
        $_buf = @json_decode($buf, true);
        $rst = !empty($_buf) ? [$_buf['error'], $_buf['msg'], @$_buf['content']] : $buf;
    } else {
        $rst = $buf;
    }
 
    return $rst;
}
 
/**
 * 向物理机发起socket请求.
 *
 * @param $args 参数
 * @return bool
 * @throws \Exception
 */
function sendSocket($args)
{
    set_time_limit(0);
    ini_set('memory_limit', -1);
 
    $type = isset($args['type']) ? $args['type'] : 0;
 
    if (!$type) throw new \Exception('类型参数错误');
 
    $port = 9998;
    $ip = "127.0.0.1";
 
    // 创建socket
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
 
    if ($socket <= 0) throw new \Exception('创建socket失败,REASON:' . socket_strerror($socket));
 
    try {
 
        // 连接服务器
        $result = socket_connect($socket, $ip, $port);
        if ($result < 0 || is_null($result) || !$result) throw new \Exception('连接失败,REASON:' . socket_strerror($result));
 
        $in = json_encode($args);
 
        // 写入文件信息
        if (!socket_write($socket, $in, strlen($in))) throw new \Exception('消息发送失败,REASON:' . socket_strerror($socket));
 
        // 读取socket返回的数据
        list($error, $msg, $data) = socketRead($socket);
 
        if ($type != 3 && $error != 0) throw new \Exception('104服务器异常,REASON:' . $msg);
 
        // 关闭socket
        socket_close($socket);
 
        switch ($type) {
            case 2: // 分包
                $rst = $data['url'];
                break;
            case 3: // 检测文件
                if ($error == -1) {
                    throw new \Exception('检测文件失败,REASON:' . $msg);
                }
 
                $rst = $error;
                break;
            default:
                $rst = true;
                break;
        }
 
    } catch (\Exception $ex) {
 
        // 关闭socket
        @socket_close($socket);
 
        throw new \Exception($ex->getMessage());
    }
 
    return $rst;
}
 
 
/**
 * 分包程序.切记不能有die或exit出现.
 *
 * User: yzm
 * Data: 2018/1/16
 */
require_once './Lib/Function.php';
 
$i=0;
while ($i<30){
    try{
        $data['type'] = 1;
        $data['authId'] = 2;
        $data['location'] = 1;
        sendSocket($data);
    }catch (\Exception $ex){
        echo $ex->getMessage();
    }
    $i++;
    sleep(5);
}

?>