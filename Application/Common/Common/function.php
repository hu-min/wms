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
 * @Date: 2018-11-30 15:45:00 
 * @Desc: 图片转base64 
 */
function base64Encode ($image_file) {
    $base64_image = '';
    $image_info = getimagesize($image_file);
    $image_data = fread(fopen($image_file, 'r'), filesize($image_file));
    $base64_image = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode($image_data));
    return $base64_image;
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
    $str = strip_tags($str);
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
    // print_r($item);
    echo "<span class='label bg-{$statusLabel[$item['status']]}'>{$statusType[$item['status']]}</span>";
}
/** 
 * @Author: vition 
 * @Date: 2018-12-29 20:17:19 
 * @Desc: 状态组 
 */
function status_group($param=[]){
    if(isset($param['vars']['vars']['data'])){
        $data = $param['vars']['vars']['data'];
    }else{
        return '';
    }

    $nodeAuth = $param['vars']['vars']['nodeAuth'];
    $statusLabel = $param['vars']['vars']['statusLabel'];
    $statusType = $param['vars']['vars']["statusType"];
    $status = $data['status'] ? $data['status'] : 0;
    echo '<div class="btn-group modal-status status-group" data-toggle="btn-toggle"><input class="modal-info"  value="'.$status.'" name="status" type="hidden">'; 
    if($nodeAuth>=7){
        
        foreach ($statusLabel as $index => $scolor) {
            if($index == $status){
                echo '<button type="button" name="'.$index.'" class="btn btn-default btn-sm status-btn"><i class="fa fa-check-square text-'.$scolor.'"></i> '.$statusType[$index].' </button>';
            }else{
                if($index != 10){
                    echo '<button type="button" name="'.$index.'" class="btn btn-default btn-sm status-btn"><i class="fa fa-square text-'.$scolor.'"></i> '.$statusType[$index].' </button>';
                }
            }
        }
        
    }elseif($status != ""){
        echo '<button type="button" name="'.$status.'" class="btn btn-default btn-sm status-btn" disabled="disabled"><i class="fa fa-check-square text-'.$statusLabel[$status].'"></i> '.$statusType[$status].' </button>';
    }
    echo '</div>';
}
//各类权限按钮全局函数开始
/** 
 * @Author: vition 
 * @Date: 2018-06-09 23:01:13 
 * @Desc: list列表中的按钮状态 
 */
function list_btn($defind_vars,$idStr,$inlink=[],$onlycat=false,$onlyState=false,$hasState=true,$text = '查看',$option=[]){
    $statusLabel = $defind_vars["statusLabel"];
    $statusType = $defind_vars["statusType"];
    $item = $defind_vars["item"];
    $tableName = $option["tableName"] ? $option["tableName"] : $defind_vars["tableName"];
    $controlName = $option["controlName"] ? $option["controlName"] : $defind_vars["controlName"];
    $url = $defind_vars["url"];
    $userId = $defind_vars["userId"];
    $nodeAuth = $defind_vars["nodeAuth"];
    $processAuth = $defind_vars["processAuth"];
    if($hasState){
        echo "<td><span class='label bg-{$statusLabel[$item['status']]}'>{$statusType[$item['status']]}</span></td>";
    }
    if(!$onlyState){
        echo "<td class='status-con' data-db='{$tableName}' data-con='{$controlName}' data-id='".$item[$idStr]."' data-url='{$url}' >";
    }
    if($nodeAuth >= 1 && !$onlyState){
        if(empty($inlink)){
            echo "<button type='button' data-gettype='Edit' data-toggle='modal' data-vtarget='.global-modal' class='btn btn-xs btn-primary v-showmodal'>{$text}</button>";
        }else{
            if(isset($inlink["title"])){
                $title = 'data-title="'.$inlink["title"].'"';
            }

            echo '<a class="btn btn-xs btn-primary nodeOn" role="button" data-id="'.$item[$idStr].'" '.$title.' data-nodeid="'.$inlink["nodeid"].'" href="'.$inlink["href"].'"><span>'.$text.'</span></a>';
        }
        
    }
    // if($processAuth['level'] > 1 && ($item['status'] == 0 or  $item['status'] == 2 ) && !$onlycat){
    //     echo "  <button type='button' class='btn btn-success submit-status btn-xs' data-status='1' name='approve' >{$statusType[1]}</button>";
    //     if($item['user_id'] != $userId && isset($statusType[3])){
    //         echo "  <button type='button' class='btn btn-warning submit-status btn-xs' data-status='3' name='refuse' >{$statusType[3]}</button>";
    //     }
    // }
    if($nodeAuth >= 4  && !$onlycat && !$onlyState){
        echo "  <button type='button' class='btn btn-danger btn-xs status-info' data-status='4' data-reqtype='Del'  >{$statusType[4]}</button>";
    }
    echo "</td>";
}
/** 
 * @Author: vition 
 * @Date: 2018-06-09 23:57:37 
 * @Desc: modal里按钮权限 
 */
function modal_btn($defind_vars,$status=false){
    $userId = $defind_vars["userId"];
    $nodeAuth = $defind_vars["nodeAuth"];
    $processAuth = $defind_vars["processAuth"];
    $gettype = $defind_vars["gettype"];
    $item = $defind_vars["data"];
    $statusType = $defind_vars["statusType"];
    
    if($nodeAuth >= 7){
        echo "<button  type='button' name='0' class='btn btn-default btn-sm active status-btn'><i class='fa fa-square text-default'></i> 未启用 </button>";
    }
    
    if($status){
        
        if($processAuth['level'] > 1 && !in_array($userId,explode(',',$item['examine'])) || $item['status'] == 1 || $nodeAuth>= 7){
            echo "<button type='button' name='1' class='btn btn-success btn-sm status-btn'><i class='fa fa-square text-default'> $statusType[1] </i></button>";
        }
        // if(isset($statusType[3]) && $item['user_id']!= $userId && $gettype!="Add" && $item['status']!=1){
        //     echo "<button type='button' name='3' class='btn btn-warning btn-sm status-btn'><i class='fa fa-square text-default'> {$statusType[3]} </i></button>";
        // }
        if($processAuth['level'] > 1 && ($item['status'] == 0 ||  $item['status'] == 2 ) && !in_array($userId,explode(',',$item['examine'])) || ($nodeAuth>= 7 && $gettype == "Edit") && isset($statusType[3]) && $gettype != "Add"){
            echo "<button type='button' name='3' class='btn btn-warning btn-sm status-btn'><i class='fa fa-square text-default'> {$statusType[3]} </i></button>";
        }

    }
    // if($processAuth['level'] > 1 && !in_array($userId,explode(',',$item['examine'])) || $item['status'] == 1 || $nodeAuth>= 7){
    //     echo "<button type='button' name='1' class='btn btn-success btn-sm status-btn'><i class='fa fa-square text-default'> $statusType[1] </i></button>";
    // }
    // if(isset($statusType[3]) && $item['user_id']!= $userId && $gettype!="Add" && $item['status']!=1){
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
function approve_btn($tableName,$param=[]){
    // echo $tableName,",",$id,",",$place,",",$level,",",$status,",";
    // print_r(get_defined_vars()['param']['vars']['data']);exit;
    // print_r($param['vars']);exit;
    if(isset($param['vars']['vars']['data'])){
        $data = $param['vars']['vars']['data'];
    }elseif(isset($param['item'])){
        $data = $param['item'];
    }
    $tableName = isset($param['vars']['tableName']) ? $param['vars']['tableName'] : $tableName;
    $idName = isset($param['idName']) ? $param['idName'] : "id";
    $id = isset($data[$idName])  ? $data[$idName] : $id;
    $place = isset($data['place']) ? $data['place'] : $place;
    $level = isset($data['process_level']) ? $data['process_level'] : $level;
    $status = isset($data['status']) ? $data['status'] : $status;
    $approved = isset($data['approve_id']) && $data['approve_id'] > 0  ? true : false;
    
    

    $all_level = 0;
    $examine = isset($data['examine']) ? $data['examine'] : (isset($param['examine']) ? $param['examine'] : "");

    $place = search_last_key(session('roleId'),array_unique(explode(",",$examine)));
    $place = $place !== false ? ++$place : 0;

    // echo $tableName,",",$id,",",$place,",",$level,",",$status,",",$approved;
    // $place ++;
    // var_dump($examine);
    // echo $examine;
    if($examine != ""){
        $all_level = count(explode(",",$examine));
    }
    
    // print_r($param['examine']);exit; 
    $levelStr = ' data-place="'.$place.'" data-level="'.$level.'" data-alllevel="'.$all_level.'" data-maurl="'.U('Tools/getMoneyAccountList').'"';
    if($level > 0){
        echo '<div class="approve-group" '.$levelStr.' data-table="'.$tableName.'" data-id="'.$id.'" >
        <button type="button" data-url="'.U('Tools/getApproveList').'" class="btn btn-xs bg-black approve-log">记录</button> ';
    }
    if($place===false || $place > 0){
        $disabled = "";
   
        // if(($place !== false && $level >= $place) || $status == 3){
        if(( $level != $place) || $status == 3 || $status == 1 || $approved){
            $disabled = "disabled";
        }
        if($level > 0 && !isset($param['nocon']) ){
            echo '<button type="button" '.$disabled.' data-url="'.U('Tools/approveEdit').'" class="btn btn-xs bg-orange approve-con '.$disabled.'">操作</button>';
        }
        
    }
    echo '</div>';
}
/** 
 * @Author: vition 
 * @Date: 2018-06-10 00:42:43 
 * @Desc: 新增保存按钮权限 
 */
function save_btn($defind_vars,$always=false,$hide=false){
    $processAuth = $defind_vars["processAuth"];
    
    $nodeAuth = $defind_vars["nodeAuth"];
    $controlName = $defind_vars["controlName"];
    $tableName = $defind_vars["tableName"];
    $gettype = $defind_vars["gettype"];
    $btnTitle = $defind_vars["btnTitle"];
    $item = $defind_vars["data"];
    $userId = $defind_vars["userId"];
    $url = $defind_vars["url"];
    $noModal = $defind_vars["noModal"] ? "" : "data-modal='true'";
    // echo $item["user_id"],$gettype,$processAuth['level'];
    // echo $item['status'],",",$item['user_id'],",", $userId,$gettype,$processAuth['level'];
    if((in_array($item['status'],[0,3,10]) && in_array($userId,[$item['user_id'],$item['puser_id']])) || $gettype == "Add" || $nodeAuth >= 7 || ($item['status'] == 1 && isset($item['business']) && $item['business'] == $userId) || $always ){
    // if(($item["user_id"] == $userId && in_array($item['status'],[0,3])) || ($gettype == "Add" && $processAuth['level'] > 0) || $always || $nodeAuth >= 7 || ($defind_vars['data']['status'] == 1 && isset($defind_vars['data']['business']) && $defind_vars['data']['business'] == $defind_vars["userId"]) || ($item['status'] == 1 && $item['user_id'] == $userId) || $item['status'] == 10){
        echo "<button type='button' class='btn btn-sm btn-primary save-info' data-con='{$controlName}' data-gettype='{$gettype}' data-url='{$url}' {$noModal}>{$btnTitle}</button>";
    }elseif($hide){
        echo "<button type='button' class='btn btn-sm btn-primary save-info none' data-con='{$controlName}' data-gettype='{$gettype}' data-url='{$url}' {$noModal}>{$btnTitle}</button>";
    }
    // echo $item['status'],",";
    if((in_array($item['status'],[0,3,10]) && in_array($userId,[$item['user_id'],$item['puser_id']])) || $gettype == "Add" || $nodeAuth >= 7){
        echo "<button type='button' class='btn btn-sm bg-maroon save-info' data-status='10' data-con='{$controlName}' data-gettype='{$gettype}' data-url='{$url}' {$noModal}>储存草稿</button>";
    }
    
    if(($item["user_id"] == $userId || $item["user_id"] == $userId) && $item['status'] == 1){
        // echo "<button type='button' class='btn btn-sm btn-info reset-info-active' data-con='{$controlName}' data-gettype='reset_apply' data-db='{$tableName}' data-url='{$url}' {$noModal}>重新提审</button>";
    }
    
    // if((($item["user_id"] == $userId && $item['status'] == 0) || ($gettype == "Add" && $processAuth['level'] > 0) || (($processAuth['level'] -1) == $item["process_level"] && $item["process_level"] > 0))  || $nodeAuth>= 7){
    //     echo "<button type='button' class='btn btn-primary save-info' data-con='{$controlName}' data-gettype='{$gettype}' data-url='{$url}' {$noModal}>{$btnTitle}</button>";
    // }
}
/** 
 * @Author: vition 
 * @Date: 2018-06-10 08:59:16 
 * @Desc: 新增的按钮权限 
 */
function add_btn($defind_vars,$title="新增",$auth = 2,$icon = "fa fa-plus-square",$option=[]){
    $processAuth = $defind_vars["processAuth"];
    $nodeAuth = $defind_vars["nodeAuth"];
    $url = $defind_vars["url"];
    $controlName = $option['controlName'] ? $option['controlName'] : $defind_vars["controlName"];
    if(($processAuth['level'] > 0 && $nodeAuth >= $auth ) || $nodeAuth >= 7){
        echo "<button type='button' data-gettype='Add' data-toggle='modal'  data-url='{$url}' data-vtarget='.global-modal' data-con='{$controlName}' class='btn btn-info info-edit v-showmodal'><i class='{$icon} '></i> {$title} </button>";
    }
}
/** 
 * @Author: vition 
 * @Date: 2018-10-02 12:40:27 
 * @Desc: 导入按钮 
 */
function import_btn($defind_vars){
    $nodeAuth = $defind_vars["nodeAuth"];
    $tableName = $defind_vars["tableName"];
    $controlName = $defind_vars["controlName"];
    if($nodeAuth >= 1){//6
        echo "<button type='button' data-db='{$tableName}' data-con='{$controlName}' class='btn bg-navy excel-import'><i class='fa fa-fw fa-cloud-upload '></i> 导入 </button>";
        echo '<a class="btn btn-social-icon bg-navy" href="'.U('Base/template_down').'?con='.CONTROLLER_NAME."_".$controlName.'" title="模板下载"><i class="fa fa-file-excel-o"></i><div></div></a>';
    }
}
/** 
 * @Author: vition 
 * @Date: 2018-10-02 12:40:27 
 * @Desc: 导入按钮 
 */
function export_btn($defind_vars,$config=[]){
    $nodeAuth = $defind_vars["nodeAuth"];
    $tableName = $defind_vars["tableName"];
    $controlName = $defind_vars["controlName"];
    $controlPrefix = $config['controlPrefix'] ? $config['controlPrefix'] : 'List';
    $title = $config['title'] ? $config['title'] : '导出';
    if($nodeAuth >= 1){//6
        echo "<button type='button' data-url='".$defind_vars["url"]."' data-export='export' data-con='{$controlName}' data-reqtype='{$controlName}{$controlPrefix}'  class='btn bg-maroon excel-export'><i class='fa fa-fw fa-cloud-download'></i> {$title} </button>";
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
/** 
 * @Author: vition 
 * @Date: 2018-09-06 11:02:38 
 * @Desc: 格式化tabId 
 */
function getTabId($vtabId){
    return str_replace("#vtabs","",$vtabId);
}
/** 
 * @Author: vition 
 * @Date: 2018-09-06 11:10:12 
 * @Desc: 格式化时间，距离当前时间 
 */
function disTime($timeStamp){
    $time = time() - $timeStamp;
    if($time<0){
        return "穿越到过去了吧";
    }else{
        if($time<60){
            return intval($time)." 秒之前";
        }elseif($time<3600){
            return intval($time/60)." 分钟之前";
        }elseif($time<86400){
            return intval($time/3600)." 小时之前";
        }elseif($time<2592000){
            return intval($time/86400)." 天之前";
        }else{
            return date("Y-m-d H:i:s",$timeStamp);  
        }
    }
}
/** 
 * @Author: vition 
 * @Date: 2018-09-19 17:35:13 
 * @Desc: 查看字符串在数组中最后一个key 
 */
function search_last_key($search,$array){
    $return = false;
    foreach ($array as $key => $value) {
        if($value == $search){
            $return = $key;
        }
    }
    return $return;
}
function time_format($dateStr){
    if(in_array($dateStr ,['1970-01-01','1970-1-1'])){
        return '';
    }
    return $dateStr;
}
/** 
 * @Author: vition 
 * @Date: 2018-10-29 12:40:14 
 * @Desc: 格式化项目编码 包含-的隔开分两行 
 */
function pcode_format($project_code){
    $pnameArr = explode("-",$project_code);
    if(count($pnameArr) > 1){
        return '<div>'.$pnameArr[0].'</div><div>'.$pnameArr[1].'</div>';
    }
    return $project_code ;
}
/** 
 * @Author: vition 
 * @Date: 2018-09-30 19:02:38 
 * @Desc: 导出
 *  
 */
function excelExport(array $param){
    $data = $param['data'] ? $param['data'] : false;
    $schema = $param['schema'] ? $param['schema'] : false;
    $fileName = $param['fileName'] ? $param['fileName'] : 'excel';
    $template = $param['template'] ? $param['template'] : false;
    $callback = $param['callback'] ? $param['callback'] : false;
    $newFileName = false;
    require(ROOT_PATH.'ThinkPHP/Library/Vendor/PHPExcel/PHPExcel.php');

    if($template){
        $objReader =  PHPExcel_IOFactory::createReader('Excel2007');
        $objExcel = $objReader->load ($template);
    }else{
        $objExcel = new PHPExcel();
    }
    $objWriter =  PHPExcel_IOFactory::createWriter($objExcel, 'Excel2007');
    $objExcel->setActiveSheetIndex(0);
    // $objExcel->getDefaultStyle()->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objActSheet = $objExcel->getActiveSheet();

    if($template){
        $control = A(CONTROLLER_NAME);
        if($control && $callback && method_exists($control,$callback)){
            $control->$callback($data,$objExcel,$objActSheet,$newFileName);
        }else{
            echo '调用控制器失败';
            exit;
        }
    }else{
        $colA = ord('A');
        $colLen = count($schema);
        $i = 0;
        foreach($schema AS $k=>$v){
            $col = chr($colA+$i);
            $objActSheet->setCellValueExplicit($col.'1', isset($v['name']) ? $v['name'] : $k);
            $i++;
        }
        // 固定行
        $objActSheet->freezePane('A2');
    
        foreach ($data AS $k => $v) {
            $cols = $colA;
            $thisRow = $k + 2;
            foreach($schema AS $sk=>$sv){
                if(isset($sv['func']) && is_callable($sv['func'])){
                     $v[$sk] = $sv['func']($v[$sk]);
                }
                $objActSheet->setCellValueExplicit(chr($cols++) . $thisRow, $v[$sk]);
            }
        }
    }
    
    $fileName = $newFileName ? $newFileName. '.xlsx' : $fileName. date('YmdHis', time()) . '.xlsx';
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control:must-revalidate,post-check=0,pre-check=0");
    header("Content-Type:application/force-download");
    header("Content-Type:application/vnd.ms-execl");
    header("Content-Type:application/octet-stream");
    header("Content-Type:application/download");
    header('Content-Disposition:attachment;filename="' . $fileName . '"');
    header("Content-Transfer-Encoding:binary");
    // ob_end_clean();
    $objWriter->save("php://output");
}
/** 
 * @Author: vition 
 * @Date: 2018-09-30 22:38:32 
 * @Desc: 导入数据 
 */
function excelImport($parameter=[]){
    if(!isset($parameter['filename']) && !isset($parameter['file'])){
        return [];
    }else{
        $filename = $parameter['file'] ? $parameter['file'] : ROOT_PATH.$parameter['filename'];
        if(!file_exists($filename)){
            return [];
        }
    }
    $callback = $parameter['callback'] ? $parameter['callback'] : false;
    require(ROOT_PATH.'ThinkPHP/Library/Vendor/PHPExcel/PHPExcel.php');
    // $objExcel = new PHPExcel();
    $objReader =  PHPExcel_IOFactory::createReader('Excel2007');
    // $filename = ROOT_PATH."Uploads/Project/20180927/test.xlsx";
    
    $dataArray = [];
    $objPHPExcel = $objReader->load($filename); //$filename可以是上传的文件，或者是指定的文件
    $sheet = $objPHPExcel->getSheet(0);
    if($callback){
        $control = A(CONTROLLER_NAME);
        if($control && $callback && method_exists($control,$callback)){
            $control->$callback($objPHPExcel);
        }else{
            echo '调用控制器失败';
            exit;
        }
    }else{
        $highestRow = $sheet->getHighestRow(); // 取得总行数
        $highestColumn = $sheet->getHighestColumn(); // 取得总列数
        for($row=1;$row<=$highestRow;$row++){
            $rowData = [];
            for ($col=1; $col <= alphaIndex($highestColumn); $col++) { 
                $cell = $objPHPExcel->getActiveSheet()->getCell(alphaIndex(false,$col).$row)->getValue();
                if(is_object($cell))  $cell = $cell->__toString();
                array_push($rowData,$cell);
            }
            array_push($dataArray,$rowData);
        }
        return $dataArray;
    }
}
/** 
 * @Author: vition 
 * @Date: 2018-09-30 22:19:22 
 * @Desc: 字母和数字互转excel 最大ZZ 
 */
function alphaIndex($alpha=false,$index=false){
    $alpha = strtoupper($alpha);
    $alphaArr = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
    $alphaNew = $alphaArr;
    if(strlen($alpha)>1 || $index>26){
        $tempAlpha="";
        foreach ($alphaArr as  $alpha1) {
            foreach ($alphaArr as  $alpha2) {
                array_push($alphaNew,$alpha1.$alpha2);
            }
        }
    }
    if($index!==false){
        $return = $alphaNew[$index-1];
        
    }else{
        $return = array_search($alpha,$alphaNew);
        if($return!==false){
            return ($return+1);
        }
    }
    return $return;
}
/** 
 * @Author: vition 
 * @Date: 2018-10-10 10:15:37 
 * @Desc: 取文件夹下所有文件 
 */
function getFiles($dir,&$fileArr){
    $uploadDir = scandir($dir);
    foreach ($uploadDir as $file) {
        if(!in_array($file,['.','..'])){
            if(is_dir($dir."/".$file)){
                getFiles($dir."/".$file,$fileArr);
            }elseif(is_file($dir."/".$file)){
                if(PHP_OS=="WINNT"){
                    $file = iconv("gbk","utf-8",$file);
                }
                if($file=='_thumb'){
                    @unlink($dir."/".$file);
                }else{
                    array_push($fileArr,$dir."/".$file);
                }
                
            }
        }
    }
}
/** 
 * @Author: vition 
 * @Date: 2018-10-10 12:27:52 
 * @Desc: 获取图片的缩略图 
 */
function imge2thumb($file){
    if(!$file){
        return '';
    }
    preg_match_all("/([^\/]+)\.([\S]+)$/",$file,$match);
    if(isset($match[1][0])){
        return preg_replace("/([^\/]+)\.[\S]+$/",$match[1][0]."_thumb.".$match[2][0],$file);
    }
    return "";
}
/** 
 * @Author: vition 
 * @Date: 2018-10-11 17:27:24 
 * @Desc: 取微信配置信息 
 */
function getWeixinConf(){
    $WxConf = '{"1000009":{"corpid":"wx650b23fa694c8ff7","corpsecret":"HdwjcqIUmY80OR4wzEeMDMO9AsFlebGq1yjT3E2hang"},"contacts":{"corpid":"wx650b23fa694c8ff7","corpsecret":"GDTwLEDVdhiCz0ViKGOVKJZbQc3eLN9URy9ugIVJNm0"},"helper":{"corpsecret":"m7RF-7gpvjgIrguJSV6d8NrHX9gL9-R89EWEGLbDJv8"}}';
    return json_decode($WxConf,true);
}
/** 
 * @Author: vition 
 * @Date: 2018-11-10 10:39:36 
 * @Desc: 格式化文件的大写格式 
 */
function fsizeFormat($size){
    if(round($size/1024/1024) < 1){
        $fileSize = round($size/1024,2)."K";
    }else{
        $fileSize = round($size/1024/1024,2)."M";
    }
    return $fileSize;
}
/** 
 * @Author: vition 
 * @Date: 2018-11-26 09:31:47 
 * @Desc: 判断时候微信浏览器 
 */
function is_wechat() { 
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) { 
        return true; 
    } return false; 
}
/** 
 * @Author: vition 
 * @Date: 2018-12-04 14:50:51 
 * @Desc: 一百以内数字转中文 
 */
function chinese_num($num){
    if( $num<0 || $num > 99){
        return '只支持零到九十九的数字';
    }
    $chinesArr = ['零','一','二','三','四','五','六','七','八','九','十'];
    $chinesNum = "";
    if(strlen($num)>1){
        $ten = substr($num,0,1);
        $single = substr($num,1,1);
        if($ten==1){
            $chinesNum = "十";
        }else{
            $chinesNum = $chinesArr[$ten]."十";
        }
        if($single!=0){
            $chinesNum .= $chinesArr[$single];
        }
    }else{
        $chinesNum = $chinesArr[$num];
    }
    return $chinesNum;
}
/** 
 * @Author: vition 
 * @Date: 2019-01-11 09:18:18 
 * @Desc: 数字转字母 
 */
function num_alpha($num){
    $alphas = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
    return $alphas[$num];
}
/** 
 * @Author: vition 
 * @Date: 2019-01-11 09:19:46 
 * @Desc: get 获取数据 
 */
function curlGet($url){
    $ch = curl_init(); // 创建一个 cURL 资源 
    curl_setopt($ch, CURLOPT_URL, $url); // CURLOPT_URL 目标 url 地址 
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // CURLOPT_RETURNTRANSFER 返回原生的（Raw）输出 
    $output = curl_exec($ch); 
    curl_close($ch);

    return $output;
} 