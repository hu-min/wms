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
    // \SeasLog::setLogger(MODULE_NAME."/".CONTROLLER_NAME."/");
    // \SeasLog::info($file);
    if (file_put_contents($file, base64_decode(str_replace($match[1], '', $data)))){
        if($scr==""){
            return ['errCode'=>0,'fileName'=>$name,"url"=>$url,"url2"=>''.date('Ymd',time())."/".$name];
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
function setLevelTree($param=[]){
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
/** 
 * @Author: vition 
 * @Date: 2018-06-09 23:01:27 
 * @Desc: utf8格式的截取字符串 
 */
function utf8_substr($str,$length,$middle=false){
    $suf="";
    if(mb_strlen($str)>$length){
        $suf="……";
    }
    if($middle){
        $len=(int)$length/2;
        return mb_substr($str,0,$len,"utf8").$suf.mb_substr($str,-$len,$len,"utf8");
    }
    return mb_substr($str,0,$length,"utf8").$suf;
}

function status_label($defind_vars){
    $statusLabel = $defind_vars["statusLabel"];
    
    $statusType = $defind_vars["statusType"];
    $item = $defind_vars["item"];
    print_r($item);
    echo "<span class='label bg-{$statusLabel[$item['status']]}'>{$statusType[$item['status']]}</span>";
}
//各类权限按钮全局函数开始
/** 
 * @Author: vition 
 * @Date: 2018-06-09 23:01:13 
 * @Desc: list列表中的按钮状态 
 */
function list_btn($defind_vars,$id,$inlink=[],$onlycat=false){
    $statusLabel = $defind_vars["statusLabel"];
    $statusType = $defind_vars["statusType"];
    $statusType = $defind_vars["statusType"];
    $item = $defind_vars["item"];
    $tableName = $defind_vars["tableName"];
    $controlName = $defind_vars["controlName"];
    $url = $defind_vars["url"];
    $userId = $defind_vars["userId"];
    $nodeAuth = $defind_vars["nodeAuth"];
    $processAuth = $defind_vars["processAuth"];
    echo "<td><span class='label bg-{$statusLabel[$item['status']]}'>{$statusType[$item['status']]}</span></td>";
    echo "<td class='status-con' data-db='{$tableName}' data-con='{$controlName}' data-id='".$item[$id]."' data-url='{$url}' >";
    if($nodeAuth >= 1){
        if(empty($inlink)){
            echo "<button type='button' data-gettype='Edit' data-toggle='modal' data-vtarget='.global-modal' class='btn btn-sm btn-primary v-showmodal'>查看</button>";
        }else{
            if(isset($inlink["title"])){
                $title = 'data-title="'.$inlink["title"].'"';
            }

            echo '<a class="btn btn-sm btn-primary nodeOn" role="button" data-id="'.$item[$id].'" '.$title.' data-nodeid="'.$inlink["nodeid"].'" href="'.$inlink["href"].'"><span>查看</span></a>';
        }
        
    }
    // if($processAuth['level'] > 1 && ($item['status'] == 0 or  $item['status'] == 2 ) && !$onlycat){
    //     echo "  <button type='button' class='btn btn-success submit-status btn-sm' data-status='1' name='approve' >{$statusType[1]}</button>";
    //     if($item['author'] != $userId && isset($statusType[3])){
    //         echo "  <button type='button' class='btn btn-warning submit-status btn-sm' data-status='3' name='refuse' >{$statusType[3]}</button>";
    //     }
    // }
    if($nodeAuth >= 4  && !$onlycat){
        echo "  <button type='button' class='btn btn-danger btn-sm status-info' data-status='4' data-reqtype='Del'  >{$statusType[4]}</button>";
    }
    echo "</td>";
}
/** 
 * @Author: vition 
 * @Date: 2018-06-09 23:57:37 
 * @Desc: modal里按钮权限 
 */
function modal_btn($defind_vars){
    $userId = $defind_vars["userId"];
    $nodeAuth = $defind_vars["nodeAuth"];
    $processAuth = $defind_vars["processAuth"];
    $gettype = $defind_vars["gettype"];
    $item = $defind_vars["data"];
    $statusType = $defind_vars["statusType"];
    
    if($nodeAuth >= 7){
        echo "<button  type='button' name='0' class='btn btn-default btn-sm active status-btn'><i class='fa fa-square text-default'></i> 未启用 </button>";
    }
    // if($processAuth['level'] > 1 && !in_array($userId,explode(',',$item['examine'])) || $item['status'] == 1 || $nodeAuth>= 7){
    //     echo "<button type='button' name='1' class='btn btn-success btn-sm status-btn'><i class='fa fa-square text-default'> $statusType[1] </i></button>";
    // }
    // if(isset($statusType[3]) && $item['author']!= $userId && $gettype!="Add" && $item['status']!=1){
    //     echo "<button type='button' name='3' class='btn btn-warning btn-sm status-btn'><i class='fa fa-square text-default'> {$statusType[3]} </i></button>";
    // }
    // if($processAuth['level'] > 1 && ($item['status'] == 0 ||  $item['status'] == 2 ) && !in_array($userId,explode(',',$item['examine'])) || ($nodeAuth>= 7 && $gettype == "Edit") && isset($statusType[3]) && $gettype != "Add"){
    //     echo "<button type='button' name='3' class='btn btn-warning btn-sm status-btn'><i class='fa fa-square text-default'> {$statusType[3]} </i>{$gettype}</button>";
    // }
    if($nodeAuth >= 4){
        // echo "  <button type='button' name='4' class='btn btn-danger btn-sm status-btn'><i class='fa fa-square text-default'> {$statusType[4]} </i></button>";
        echo "  <button type='button' class='btn btn-danger btn-sm status-info' data-status='4' data-reqtype='Del'  >{$statusType[4]}</button>";
    }
}
function approve_btn($tableName){
    echo '<div class="approve-group" data-table="'.$tableName.'" data-id="" >
        <button type="button" data-url="'.U('Tools/getApproveList').'" class="btn btn-sm bg-purple approve-log">记录</button>
        <button type="button" data-url="'.U('Tools/approveEdit').'" class="btn btn-sm bg-orange approve-con">操作</button>
    </div>';
}
/** 
 * @Author: vition 
 * @Date: 2018-06-10 00:42:43 
 * @Desc: 新增保存按钮权限 
 */
function save_btn($defind_vars){
    $processAuth = $defind_vars["processAuth"];
    $nodeAuth = $defind_vars["nodeAuth"];
    $controlName = $defind_vars["controlName"];
    $gettype = $defind_vars["gettype"];
    $btnTitle = $defind_vars["btnTitle"];
    $item = $defind_vars["data"];
    $userId = $defind_vars["userId"];
    $url = $defind_vars["url"];
    $noModal = $defind_vars["noModal"] ? "" : "data-modal='true'";
    if((($item["author"] == $userId || $item["user_id"] == $userId) && in_array($item['status'],[0,3])) || ($gettype == "Add" && $processAuth['level'] > 0)){
        echo "<button type='button' class='btn btn-primary save-info' data-con='{$controlName}' data-gettype='{$gettype}' data-url='{$url}' {$noModal}>{$btnTitle}</button>";
    }
    
    // if((($item["author"] == $userId && $item['status'] == 0) || ($gettype == "Add" && $processAuth['level'] > 0) || (($processAuth['level'] -1) == $item["processLevel"] && $item["processLevel"] > 0))  || $nodeAuth>= 7){
    //     echo "<button type='button' class='btn btn-primary save-info' data-con='{$controlName}' data-gettype='{$gettype}' data-url='{$url}' {$noModal}>{$btnTitle}</button>";
    // }
}
/** 
 * @Author: vition 
 * @Date: 2018-06-10 08:59:16 
 * @Desc: 新增的按钮权限 
 */
function add_btn($defind_vars,$title="新增"){
    $processAuth = $defind_vars["processAuth"];
    $nodeAuth = $defind_vars["nodeAuth"];
    $url = $defind_vars["url"];
    $controlName = $defind_vars["controlName"];
    if(($processAuth['level'] > 0 && $nodeAuth >= 2) || $nodeAuth >= 7){
        echo "<button type='button' data-gettype='Add' data-toggle='modal'  data-url='{$url}' data-vtarget='.global-modal' data-con='{$controlName}' class='btn btn-info info-edit v-showmodal'><i class='fa fa-fw fa-user-plus '></i> {$title} </button>";
    }
}
//各类权限按钮全局函数结束

function domain($diagonal=true){
    $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://'; 
    if($diagonal){
        return $http_type.$_SERVER['HTTP_HOST']."/";
    }
    return $http_type.$_SERVER['HTTP_HOST'];
}

function getTabId($vtabId){
    return str_replace("#vtabs","",$vtabId);
}