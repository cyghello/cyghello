<?php
/**
 * 分包程序.切记不能有die或exit出现.
 *
 * User: yzm
 * Data: 2018/1/16
 */
 
require_once './vendor/workerman/workerman/Autoloader.php';
require_once './Lib/Function.php';
 
require_once __DIR__ . '/Lib/Db.php';
require_once __DIR__ . '/Lib/DbConnection.php';
require_once __DIR__ . '/Config/Db.php';
 
use Workerman\Worker;
 
// #### create socket and listen 1234 port ####
$tcp_worker = new Worker("tcp://0.0.0.0:9998");
 
/**
 * 定义常量.
 */
define('REP_SUCCESS', 0); // 成功
define('REP_FAIL', -1); // 失败
define('REP_FAIL_NO_COMPLETED', 1); // 文件未上传完成
 
 
// 16 processes,与cpu个数相同
$tcp_worker->count = 16;
$msg = '';
 
define('ORGPKG', '/Volumes/VMware\ Shared\ Folders/orgpkg/');
define('DISTPKG', '/Volumes/VMware\ Shared\ Folders/');
//define('SYS_IP', '39.108.223.28');
define('SYS_IP', '120.92.142.115');
define('IOS_URL','http://ios.package.tonguu.cn/');
 
 
// Emitted when new connection come
$tcp_worker->onConnect = function ($connection) {
    $connection->sized = 0;
 
    // xcode调用脚本
    $certMobile = '/mnt/www/DIVIDE_PKG/Cert/%d/mslabEnt.mobileprovision'; // 证书文件
    $shell = "/mnt/www/DIVIDE_PKG/Lib/dividePkg/resign  sign -ipapath  %s  -destpath %s  -pppath %s -agentid %s";
 
    $connection->shell = $shell;
    $connection->pppath = $certMobile;
 
    echo date("Y-m-d H:i:s") . " connect!" . getclientip() . PHP_EOL;
 
};
 
/**
 * 响应结果.
 *
 * @author yzm
 */
function resonse($conn, $msg, $error = REP_FAIL, $data = [])
{
    $res = ['msg' => $msg, 'error' => intval($error)];
    if (!empty($data)) {
        $res['content'] = $data;
    }
 
    debug($res);
 
    // 返回JSON数据格式到客户端 包含状态信息
    $rst = json_encode($res);
 
    $conn->send($rst);
}
 
 
// Emitted when data received
$tcp_worker->onMessage = function ($connection, $data) {
    set_time_limit(0);
    ini_set('memory_limit', -1);
 
    $db = \Lib\Db::instance('btmox');
    $data = @json_decode($data, true);
 
    try{
        if (empty($data['authId'])) {
            throw new \Exception('授权文件参数错误');
        }
 
        //1. 查询所有待分包的ios渠道包
        $iosPkg = $db
            ->select('a.id,a.vid,a.filename,a.agent,d.pinyin,b.name,c.package_name')
            ->from('cy_ct_ios_package a')
            ->where("a.status=0 AND c.is_send=1")
            ->leftJoin('cy_ct_ios_mobileversion b','b.id=a.m_v_id')
            ->rightJoin('cy_ct_ios_version c','c.id=a.vid')
            ->leftJoin('cy_game d','d.id=c.game_id')
            ->orderByASC(['a.create_time'])->query();
 
        if(empty($iosPkg)) throw new \Exception('没有需要待分包的数据'.PHP_EOL);
 
        //2. 分包
        foreach($iosPkg as $one){
            try{
                //对当前正要分的包把状态改为‘分包中’
                $db->update('cy_ct_ios_package')->cols([
                    'status' => 2,
                ])->where("id=".$one['id'])->query();
 
                $filename = $one['pinyin'];
                // 渠道分包
                $verId = @$one['vid'];
                $agent = @$one['agent'];
                $location = isset($data['location']) ? $data['location'] : 1;
                $authId = @intval($data['authId']); // 授权文件
 
                if (empty($verId) || empty($agent)) {
                    throw new \Exception("分包失败：".$one['id']."版本、渠道为空\r\n");
                }
 
                // 替换\,否则PHP验证不文件是否存在
                $orgPkg = str_replace('\\', '', ORGPKG) . "{$filename}.ipa";
 
                debug($one['id'].'原包：' . $orgPkg);
 
                debug($one['id'].'是否是文件：' . is_file($orgPkg));
 
                if (!is_file($orgPkg)) {
                    throw new \Exception("分包失败：".$one['id']."母包不存在-$orgPkg\r\n");
                }
 
                // 从新拼接文件
                $orgPkg = ORGPKG . "{$filename}.ipa";
 
                // 获取目标包存放路径
                $distPkgPath = getDistPkgPath($location);
 
                $distPkg = $distPkgPath . "$filename/vers_{$verId}/{$filename}_$agent.ipa";
                debug('渠道分包地址：' . $distPkg);
                if (file_exists($filename)) {
                    @unlink($filename);
                }
 
                // 替换授权文件
                $certMobile = sprintf($connection->pppath, $authId);
 
                // 渠道分包
                list($msg, $code) = dividePkg($connection->shell, $orgPkg, $distPkg, $agent, $certMobile);
 
                debug('$code' . $code);
 
                if ($code != 0) {
                    throw new \Exception("分包失败：".$msg."\r\n");
                }
 
                $distPkg = str_replace($distPkgPath, '', $distPkg);
 
            }catch (\Exception $ex){
                debug($ex->getMessage());
                $code = -1;
                $msg = $ex->getMessage();
            }
 
            //3. 分包后更新分包结果，状态，下载地址
            $status = $code == 0 ? 1 : 2;
            $sdata['status'] = $status;
            $sdata['message'] = $msg;
            if($status == 1){
                $sdata['url'] = IOS_URL.$distPkg;
            }
            $db->update('cy_ct_ios_package')->cols($sdata)->where("id=".$one['id'])->query();
        }
 
        resonse($connection, $msg,$code);
    }catch (\Exception  $ex){
        resonse($connection, $ex->getMessage());
    }
};
 
// Emitted when new connection come
$tcp_worker->onClose = function ($connection) {
    echo date("Y-m-d H:i:s") . " closed!" . PHP_EOL;
};
 
Worker::runAll();

?>