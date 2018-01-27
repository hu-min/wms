<?php
/** 
 * @Author: vition 
 * @Date: 2018-01-28 00:22:32 
 * @Desc: 获取错误信息 
 */
function getError($errCode){
    if($errCode<0){
        return 'errCode不能为空或者小于0';
    }
    $errCodeFile=APP_PATH.'Common/Common/errCode.php';
    if(!file_exists($errCodeFile)){
        return 'errCode文件不存在';
    }
    include $errCodeFile;

    if(!$errCodeList[$errCode]){
        return '未知错误,errCode文件里可能未增加';
    }
    return $errCodeList[$errCode];
}
/** 
 * @Author: vition 
 * @Date: 2018-01-28 00:22:25 
 * @Desc: 获取组件 
 */
function getComponent($conName,$arrParam=[]){
    $conName=ucfirst($conName).'Controller';
    $fileUrl=APP_PATH.'Component/Controller/'.$conName.'.class.php';
    if(!file_exists($fileUrl)){
        \SeasLog::setLogger('function/'.__FUNCTION__.'/'.date('Y-m-d'));
        \SeasLog::warning($conName.'对应的文件不存在');
        return new \Component\Controller\BaseController();//防止调用出错，直接返回基类
    }
    $className= '\\Component\\Controller\\'.$conName;
   return new $className();
}
/** 
 * @Author: vition 
 * @Date: 2018-01-28 00:24:29 
 * @Desc: ip转成长整型 ,修正ip2long可能返回负数
 */
function ipTolong($ip){
   return sprintf("%u", ip2long($ip));
}
/** 
 * @Author: vition 
 * @Date: 2018-01-28 00:31:10 
 * @Desc: 返回真实ip地址 
 */
function getIp(){
    $arr_ip_header = array(
        'HTTP_CDN_SRC_IP',
        'HTTP_PROXY_CLIENT_IP',
        'HTTP_WL_PROXY_CLIENT_IP',
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'REMOTE_ADDR',
    );
    $client_ip = 'unknown';
    foreach ($arr_ip_header as $key)
    {
        if (!empty($_SERVER[$key]) && strtolower($_SERVER[$key]) != 'unknown')
        {
            $client_ip = $_SERVER[$key];
            break;
        }
    }
    return $client_ip;
}
