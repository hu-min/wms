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
/** 
 * @Author: vition 
 * @Date: 2018-01-28 21:46:45 
 * @Desc: base64转文件 
 */
function base64Img($data,$scr="",$name=""){
    preg_match('/^(data:\s*image\/(\w+);base64,)/', $data, $match);
    $type = $match[2];
    
    if($name==""){
        $name=md5($data).".".$type;
    }else{
        $name=$name.".".$type;
    }
    if($scr==""){
        $url=ROOT_PATH.'Uploads/'.date('Ymd',time())."/";
        $file=$url.$name;
    }else{
        $url=$scr;
        $file=$scr.$name;
    }
    if(!file_exists($url)){
        mkdir($url, 0755,true);
    }
    if (file_put_contents($file, base64_decode(str_replace($match[1], '', $data)))){
        if($scr==""){
            return ['errCode'=>0,'fileName'=>$name,"url"=>$url,"url2"=>'Uploads/arena/'.date('Ymd',time())."/".$name];
        }else{
            return ['errCode'=>0,'fileName'=>$name,"url"=>$file];
        }
        
    }else{
        return ['errCode'=>110,'error'=>"文件保存失败"];
    }
}
/** 
 * @Author: vition 
 * @Date: 2018-02-26 22:38:16 
 * @Desc: 设置节点树 
 */
function setNodeTree($param=[]){
    if(!$param["nodeList"]){
        return [];
    }
    $level=$param["level"]?$param["level"]:"level";
    $id=$param["id"]?$param["id"]:"id";
    $pid=$param["pid"]?$param["pid"]:"pid";
    $nodes=$param["nodes"]?$param["nodes"]:"nodes";
    $nodeLevel=[];
    foreach ($param["nodeList"] as $value) {
        $nodeLevel[$value[$level]][]=$value;
    }
    //内部设置树    
    function _setTree($nodeLevel,$i,$id,$pid,$nodes){
        if(isset($nodeLevel[$i+1])){
            $temp=_setTree($nodeLevel,$i+1,$id,$pid,$nodes);
            $tempArr=[];
            foreach ($nodeLevel[$i] as $key => $value) {
                foreach ($temp as $keySub => $valueSub) { 
                    if($value[$id]==$valueSub[$pid]){
                        $value[$nodes][$valueSub[$id]]=$valueSub;
                    }
                }
                $tempArr[$value[$id]]=$value;
            }
            return $tempArr;
        }else{
            return $nodeLevel[$i];
        }
    }
    return [$nodes=>_setTree($nodeLevel,1,$id,$pid,$nodes)];
}