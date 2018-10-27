<?php
namespace Admin\Controller;
use Think\Image;

/**
 * BaseController 控件基类
 *     公共控制文件
 * 
 * @author vition
 * @date 2017-11-17
 */

class BaseController extends \Common\Controller\BaseController{
    protected $userCom;
    public $userId;
    protected $authority;
    protected $nodeAuth;
    protected $exemption;
    protected $pageSize=30;
    // protected $statusType=[0=>"提交申请",1=>"批准",2=>"审核中",3=>"无效",4=>"删除"];
    protected $statusType=[0=>"提交",1=>"批准",2=>"等待",3=>"驳回",4=>"删除",5=>"拒绝"];

    protected $statusLabel=[0=>"blue",1=>"green",2=>"yellow",3=>"orange ",4=>"red",5=>'black'];
    /**
     * 对admin的每一个控制器和方法做权限检查
     */ 
    public function _initialize() {
        // echo 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'];exit;
        parent::_initialize();
        $nowConAct=MODULE_NAME."/".CONTROLLER_NAME.'/'.ACTION_NAME;
        $this->configCom=getComponent('Config');

        $locks = $this->configCom->get_val("web_lock");
        
        if(isset($locks['value']) && $locks['value'] != session('web_lock_password') && strtolower($nowConAct) !="admin/index/lock"){
            $this->redirect('Index/lock');
        }else{
            if($locks['value'] == session('web_lock_password') && strtolower($nowConAct) =="admin/index/lock"){
                $this->redirect('Index/Login');
            }
        }
        $this->userCom=getComponent('User');
        $this->LogCom=getComponent('Log');
        $this->ApprLogCom=getComponent('ApproveLog');
        $this->nodeCom=getComponent('Node');
        $this->authority=C('authority');
        $this->nodeAuth=session('nodeAuth');
        $this->basicCom=getComponent('Basic');
        $this->resetCom=getComponent('ResetApply');
        $this->QiyeCom=getComponent('Qiye');
        
        $this->exemption=[//排除的控制器
            'Admin/Index/Login',
            'Admin/Index/Main',
            'Admin/Index/logOut',
            'Admin/Index/checkLogin',
            'Admin/Index/Index',
            'Admin/Index/lock',
        ];
        
        $this->refreNode();

        if($_GET['code'] && ACTION_NAME=='Login'){
            $userInfo=$this->Wxqy->user()->getUserInfo($_GET['code'],true);
            if($userInfo->userid!=""){
                $data['qiye_id']=$userInfo->userid;
                $userResult=$this->userCom->checkUser($data);
                if(isset($userResult->errCode) && $userResult->errCode==0 && !$this->isLogin()){
                    $this->setLogin($userResult->data);
                    if(session("history")){
                        $history = session("history");
                        session("history",NULL);
                        redirect($history);
                    }
                    
                    // $this->redirect('Index/Main');
                }else{
                    session('qiye_id',$userInfo->userid);
                }
            }
        }
        if($_GET['isWechat']){
            session('is_wechat',true);
        }
        // print_r($this->nodeAuth);
        // $this->setLogin();
        

        if(in_array($nowConAct,$this->exemption) || ( in_array(ACTION_NAME,['excel_import','upload_filesAdd','excel_export','template_down','reset_apply']) && $this->isLogin())){
            if(!$this->isLogin() && !in_array(ACTION_NAME,['checkLogin','Login','lock'])){
                session("history",domain(false).__SELF__);
                if(session('is_wechat')){
                    session('is_wechat',NULL);
                    redirect('https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx650b23fa694c8ff7&redirect_uri=http://twsh.twoway.com.cn/Admin/Index/Login&response_type=code&scope=SCOPE&state=STATE#wechat_redirect');
                }else{
                    $this->redirect('Index/Login');
                }
                
            }elseif($this->isLogin() && ACTION_NAME=='Login'){
                if($_GET['code']){
                    $this->vlog(9);
                }
                $this->redirect('Index/Main');
            }
        }else{ 
            $conAct=CONTROLLER_NAME.'/'.ACTION_NAME;
            $auth=$this->authVerify($conAct);
            // print_r($conAct);exit;
            if(!$auth){
                // echo $conAct;
                if($this->isLogin() && (in_array(ucfirst(CONTROLLER_NAME),["Tools","Public"]) || $conAct == "Index/home")){

                }else{
                    $this->prompt(1,'警告!','您不具备访问此页面的权限，如果您认为值得拥有，请联系管理员！');
                    exit;
                }
            }
            $vtabId	= I("vtabId");
            if($vtabId){
                $this->assign('vtabId',ltrim($vtabId,"#"));
            }

            $this->processAuth=$this->iniProcessAuth();
            // print_r($this->processAuth);exit;
            $this->assign('nodeAuth',$this->nodeAuth[CONTROLLER_NAME.'/'.ACTION_NAME]);
            $this->assign('userId',session("userId"));
            $this->assign('processAuth',$this->processAuth);
            $this->assign('statusType',$this->statusType);
            $this->assign('statusTypeJ',json_encode($this->statusType));
            $this->assign('statusLabel',$this->statusLabel);
            $this->assign('entries',[30,35,40,45,50]);
            // $nodeId = getTabId(I("vtabId"));
            $this->nodeId = getTabId(I("vtabId"));
            // $this->assign('processType',$this->processType);
            // $this->assign('processTypeJ',json_encode($this->processType));
            
            $this->assign('url',U(CONTROLLER_NAME.'/'.ACTION_NAME));
            $this->assign("pageId",$this->createId());
            //临时处理程序，处理所有图片的缩图
            // $fileList=[];
            // getFiles('Uploads',$fileList);
            // $image = new Image();
            // foreach ($fileList as $file) {
            //     preg_match("/[\S]+\_thumb\.[\S]+$/",$file,$match);
            //     if(empty($match)){
            //         preg_match_all("/([^\/]+)\.([\S]+)$/",$file,$match2);
            //         if(in_array($match2[2][0],["jpeg","jpg","png","gif"])){
            //             $newAvatar = preg_replace("/([^\/]+)\.[\S]+$/",$match2[1][0]."_thumb.".$match2[2][0],$file);
            //             if(PHP_OS=="WINNT"){
            //                 $file = iconv("utf-8","gbk",$file);
            //                 $newAvatar = iconv("utf-8","gbk",$newAvatar);
            //             }
            //             if(!file_exists($newAvatar)){
            //                 $image->open($file);
            //                 $width = $image->width();
            //                 $height = $image->height();
            //                 if($width>250){
            //                     $height = (250/$width)*$height;
            //                     $width = 250;
            //                 }
            //                 $image->thumb( $width, $height);
            //                 $image->save($newAvatar);
            //             }
            //         }
            //     }
            // }
            //处理所有图片缩图结束            
        }
        // exit;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-02-04 00:39:38 
     * @Desc: 权限验证 
     */    
    private function authVerify($conAct){
        
        $reqType=I("reqType");
        // $statusType = I('statusType');
        if(!$reqType){
            $reqType="List";
                I("reqType",$reqType);
        }
        
        if(!in_array($reqType,C("authority.6"))){
            preg_match("/\S([A-Z]+[^[A-Z]*\S]*)$/",$reqType,$match);
            if(count($match)<1){
                $reqType="List";
                I("reqType",$reqType);
            }else{
                $reqType=$match[1];
            }
        }
        
        // $logType=$this->LogCom->getType(strtolower($reqType));	
        // print_r($logType);exit;
        // if($logType>2 && $logType<=8){
        //     if(I("delType")=="deepDel"){
        //         $logType=6;
        //     }
        // }
        // print_r($this->nodeAuth[$conAct]);exit;
        if($this->nodeAuth[$conAct]>=7){
            return true;
        }else if(in_array($reqType,$this->authority[$this->nodeAuth[$conAct]])){
            return true;
        }
        return false;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-01-14 17:04:19 
     * @Desc:  判断是否登录
     */          
    protected function isLogin(){
        $isLogin=session('isLogin');
        $loginName=session('loginName');
        $roleId=session('roleId');
        if($isLogin && $loginName && $roleId){
            return true;
        }
        return false;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-01-15 00:12:15 
     * @Desc: 设置登录和退出 
     */    
    protected function setLogin($userInfo=[]){
        // $this->log($userInfo);
        if(empty($userInfo)){
	        $this->vlog(0);
            //退出设置
            session("userInfo",NULL);
            session('userId',NULL);
            session('userName',NULL);
            session('isLogin',NULL);
            session('loginName',NULL);
            session('roleId',NULL);
            session('avatar',NULL);
            session('rolePid',NULL);
            session('usertype',NULL);
            session('nodeAuth',[]);
            session('nodeInfo',[]);
            session("history",NULL);
            session('web_lock_password',NULL);
            $this->clearRedis('config_web_lock');
            $this->redirect('Index/Login');
        }else{
            //登录设置
            if($userInfo['avatar']=="" || !file_exists($userInfo['avatar'])){
                $userInfo['avatar']='Public'.'/admintmpl'."/dist/img/avatar/avatar".rand(1,5).".png";
            }else{
                $userInfo['avatar'] = imge2thumb($userInfo['avatar']);
            }
            foreach (['userId','loginName','userName','roleId','rolePid','avatar','usertype','roleId','rolePid'] as $key ) {
                if(isset($userInfo[$key])){
                    session($key,$userInfo[$key]);
                }
            }
            session("userInfo",$userInfo);
            session('isLogin',1);
            
            if(session('qiye_id')){
                $this->vlog(9);
                session('qiye_id',NULL);
            }else{
                $this->vlog(1);
            }
            $this->userCom->logIORec($userInfo['userId']);
        }
    }
    /** 
     * @Author: vition 
     * @Date: 2018-01-27 11:47:30 
     * @Desc: 显示提示 
     */    
    protected function prompt($type=1,$title='',$content=''){
        switch ($type) {
            case 1: default:
                $alert="alert-danger";
                $icon="fa-ban";
                break;
            case 2:
                $alert="alert-info";
                $icon="fa-info";
                break;
            case 3:
                $alert="alert-warning";
                $icon="fa-warning";
                break;
            case 4:
                $alert="alert-check";
                $icon="fa-ban";
                break;
        }
        $this->assign("alert",$alert);
        $this->assign("title",$title);
        $this->assign("content",$content);
        $this->assign("icon",$icon);
        if(IS_AJAX){
            if($this->isLogin()){
                $this->ajaxReturn(['html'=>$this->fetch("Index/Prompt"),'errCode'=>404,'error'=>getError(404)]);
            }else{
                $this->ajaxReturn(['html'=>$this->fetch("Index/Prompt"),'errCode'=>405,'error'=>getError(405)]);
            }
        }
        $this->assign("load",true);
        $this->display("Index/Prompt");
    }
    /** 
     * @Author: vition 
     * @Date: 2018-01-27 12:32:37 
     * @Desc: 返回html 
     */    
    function returnHtml($view=''){
        if(IS_AJAX){
            $this->ajaxReturn(['html'=>$this->fetch($view)]);
        }else{
            $this->assign("load",true);
            $this->display($view);
        }
    }
    /** 
     * @Author: vition 
     * @Date: 2018-01-29 23:05:00 
     * @Desc: 更新node 
     */    
    function refreNode(){
        $this->Redis->set($this->refreNode,1,3600);
    }

    /** 
     * @Author: vition 
     * @Date: 2018-05-28 00:05:43 
     * @Desc: 生成id 
     */    
    function createId(){
        $header=strtolower(str_replace("Controller","",CONTROLLER_NAME));
        $middle=strtolower(substr(ACTION_NAME,0,(strlen(ACTION_NAME)>5?5:strlen(ACTION_NAME))));
        $index=substr((string)time(),7,4);
        return "{$header}{$middle}{$index}";
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-31 21:51:08 
     * @Desc: 写指定日志到数据库 
     */    
    function vlog($type){
	    $this->LogCom->log($type);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-31 22:54:23 
     * @Desc: 初始化每个节点的权限了 
     */    
    protected function iniProcessAuth(){
        $actionRedis = ACTION_NAME.session("userId");
        // $authNodeId=I("authNodeId");
        $authNodeId=getTabId(I("vtabId"));
        $roleId=session('roleId');
        $rolePid=session('rolePid');
        $isLevel=0;
        $allLevel=0;
        if(!$authNodeId){
            $processAuth = $this->Redis->get($actionRedis);
            if($processAuth){
                return $processAuth;
            }
        }
        $parameter=[
            "where"=>["nodeId"=>$authNodeId],
            "fields"=>"nodeId,processIds"
        ];
        $nodeInfo=$this->nodeCom->getNodeOne($parameter);
        // print_r($nodeInfo);exit;
        if(!empty($nodeInfo["list"])){
            $processArray=explode(",",$nodeInfo["list"]["processIds"]);
            $processCom=getComponent('Process');
            $processPar=[
                "where"=>["processId"=>["IN",$processArray]],
                "fields"=>"processOption",
            ];
            $processRes=$processCom->getProcessList($processPar);
            // print_r($processRes);exit;
            if(!empty($processRes["list"])){
                foreach ($processRes["list"] as $process) {
                    $processOption = json_decode($process["processOption"],true);
                    foreach ($processOption as $level => $subProcess) {
                        // print_r($subProcess);
                        if(in_array("99999999",$subProcess["role"])){
                            $isLevel = 1;
                            $allLevel = count($processOption);
                        }else if($subProcess["type"]==1){
                            if(in_array($rolePid,$subProcess["role"])){
                                $isLevel=$level + 1;
                                $allLevel = count($processOption);
                            }
                        }else{
                            if($roleId==$subProcess["role"]){
                                $isLevel=$level + 1;
                                $allLevel = count($processOption);
                            }
                        }
                    }
                }
            }
        }
        $processAuth = ["level"=>$isLevel,"allLevel"=> $allLevel];
        $this->Redis->set($actionRedis,$processAuth,86400);
        return $processAuth;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-06-02 10:54:10 
     * @Desc: 全局修改指定信息状态 
     */    
    function globalStatusEdit(){
        extract($_REQUEST);
        $dbObject=M($db,NULL);
        $msg="删除成功！";
        $logType = 5;
        $qiye_id = 0;
        if(!$id && $db!='v_purcha'){
            $this->ajaxReturn(['errCode'=>110,'error'=>'ID异常']);
        }
        if(!$db){
            $this->ajaxReturn(['errCode'=>110,'error'=>'数据表名称异常']);
        }
        if(is_array($ids) && !empty($ids)){
            $id = ["IN",$ids];
        }
        if($db=='v_user'){
            $qiye_id = $dbObject->field('qiye_id')->where([$dbObject->getPk()=>$id])->find()['qiye_id'];
        }
        if($statusType=="del"){
            if($db!='v_purcha'){
                $conResult=$dbObject->save([$dbObject->getPk()=>$id,"status"=>$status]);
            }else{
                $conResult=$dbObject->where(['project_id'=>$id])->save(["status"=>$status]);
            }
            // echo $dbObject->getPk();exit;
        }else if($statusType=="deepDel"){
            $logType = 6;
            $seniorResult=$this->userCom->checkSeniorPwd(session("userId"),$seniorPwd);
            if($seniorResult->errCode!==0){
                $this->ajaxReturn(['errCode'=>$seniorResult->errCode,'error'=>$seniorResult->error]);
            }
            if($db!='v_purcha'){
                $conResult=$dbObject->where([$dbObject->getPk()=>$id])->delete();
            }else{
                $id = $id ? $id : 0;
                $conResult=$dbObject->where(['project_id'=>$id])->delete();
            }
            
        }else{
            $updateData=[
                $dbObject->getPk()=>$id,
            ];
            $findResult=$dbObject->where($updateData)->find();
            $updateData['process_level'] = $this->processAuth["level"];
            $updateData = $this->status_update($findResult,$status,$updateData);
            
            $updateData["updateTime"]=time();
            $conResult = $conResult=$dbObject->save($updateData);
            $msg = "操作成功！";
        }
        if($conResult){
            $this->LogCom->log($logType);
            if($db=="v_user"){ //用户表需要执行企业微信号的通讯录
                $this->Wxqy->secret($this->WxConf["contacts"]["corpsecret"]);
                if($statusType=="del"){
                    $userData = [
                        "userid"=>$qiye_id,
                        "enable" => 0,
                    ];
                    $this->Wxqy->user()->updateUser($userData);
                }else if($statusType=="deepDel"){
                    $this->Wxqy->user()->deleteUser($qiye_id);
                }
            }
            $this->ajaxReturn(['errCode'=>0,'error'=>$msg]);
        }
        $msg = "操作异常！";
        $this->ajaxReturn(['errCode'=>110,'error'=>$msg]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-06-02 17:24:05 
     * @Desc: 全局 global-modal 
     * 
     */
    protected function modalOne($parameter=[]){
        // print_r($_REQUEST);
        $assign=[];
        $assign["control"] = $parameter["con"] ? $parameter["con"] : I("con");
        $assign["gettype"] = $parameter["gettype"] ? $parameter["gettype"] : I("gettype");
        $assign["title"] = $parameter["title"] ? $parameter["title"] : ($gettype=="Add" ? "新增":"编辑");
        $assign["btnTitle"] = $parameter["btnTitle"] ? $parameter["btnTitle"] : ($gettype=="Add" ? "新增":"编辑");
        $data = $parameter["data"] ? $parameter["data"] : [];
        $assign["data"] = $parameter["data"] ? $parameter["data"] : [];
        $tpFolder = $parameter["tpFolder"] ? $parameter["tpFolder"] : CONTROLLER_NAME;
        $folder = $parameter["folder"] ? $parameter["folder"] : strtolower(CONTROLLER_NAME).'Table';
        $templet = $parameter["template"] ? $parameter["template"] : strtolower($control).'Modal';
        $templets = $parameter["templets"] ? $parameter["templets"] : $tpFolder.'/'.$folder.'/'.$templet;
        $errCode = 0;
        $error = "数据获取成功";
        foreach ($assign as $key => $value) {
            $this->assign($key,$value);
        }
        if(isset($parameter["dataList"])){
            $this->assign("list",$data["list"]);
            $html=$this->fetch($templets);
            $this->ajaxReturn(['html'=>$html,"errCode"=>$errCode,"error"=>$error]);
        }
        $html=$this->fetch($templets);
        $this->ajaxReturn(['html'=>$html,"data"=>$data,"errCode"=>$errCode,"error"=>$error]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-09-12 09:32:09 
     * @Desc: 获取城市 
     */    
    function getCityList(){
        $this->ajaxReturn(["data"=>A("Project")->_getOption("city")]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-06-03 14:28:59 
     * @Desc: 返回表格和分页数据 
     */    
    function tablePage($data,$templet,$redisName="",$pageSize=false,$countStr="",$config=[]){
        $returnData=['errCode'=>0];
        if($data){
            //导出数据
            if(isset($config['control'])){
                $sql = $config['sql'] ? $config['sql'] : explode("LIMIT",M()->_sql())[0];
                $this->Redis->set(md5($sql),$sql,300);
                $this->ajaxReturn(['sql'=>md5($sql),'url'=>U($config['control'].'/excel_export')."?sql=".md5($sql)]);
            }
            if($redisName){
                $this->Redis->set($redisName,json_encode($data['list']),7200);
            }
            if(isset($config["noPage"])){
                $pageShow = "";
            }else{
                $rollPage = isset($config["rollPage"]) ? $config["rollPage"] : false;
                $page = new \Think\VPage($data['count'], $pageSize ? $pageSize : $this->pageSize,$rollPage);
                if(isset($config["bigSize"])){
                    $page->bigSize = $config["bigSize"];
                }
                
                $pageShow = isset($config["onlyPage"]) ? $page->show(true) : $page->show();
            }
            
            if(isset($config["returnData"])){
                $returnData["data"] = $data['list'];
            }
            
            $this->assign('list',$data['list']);
            $returnData["table"]=$this->fetch($templet);
            $returnData["page"]=$pageShow;
            if($countStr){
                $returnData["count"]=$countStr;
            }
        }else{
            $returnData["table"]="";
            $returnData["page"]="";
            $returnData["count"]="";
        }
        $this->ajaxReturn($returnData);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-06-10 10:16:00 
     * @Desc: 修改状态值 
     */    
    function status_update($result,$status,$data){
        if($status > 0){
            if($result["examine"]==""){
                $data['examine']=session("userId");
            }else{
                $examineArr = explode(",",$result["examine"]);
                if(!in_array(session("userId"),$examineArr)){
                    array_push($examineArr,session("userId"));
                    $data['examine']=implode(",",$examineArr);
                }
            }
        }
        
        if($status==1 && $this->processAuth["level"] == $this->processAuth["allLevel"]){
            $data['status']=$status;
            $data['process_level'] = 0;
        }else if($status==1){
            $data['status']=2;
            $data['process_level'] = $this->processAuth["level"];
        }else if($status==3){
            $data['status']=$status;
        }else{
	   $data['status']=$status;	
	}
        return $data;
    }
    function upload_filesAdd(){
        $cb = 0;
        if(isset($_FILES['file'])){
            $uploadFile = $_FILES['file'];
        }else if(isset($_FILES['upload'])){
            $uploadFile = $_FILES['upload'];
            // $cb = $_GET['CKEditorFuncNum'];
        }else{
            $this->ajaxReturn(['errCode'=>100,'error'=>'文件上传参数有误！']);
        }
        $url=ROOT_PATH.'Uploads/'.CONTROLLER_NAME.'/'.date('Ymd',time())."/";
        if(!file_exists($url)){
            mkdir($url, 0755,true);
        }
        $viewName = $uploadFile['name'];
        $name = $viewName;
        $type =  explode('.',$name)[count(explode('.',$name))-1];
       
        if(PHP_OS=="WINNT"){
            $name = iconv("UTF-8","gb2312",$viewName);
        }
        $file=$url.$name;
        $copyState =  copy($uploadFile['tmp_name'],$file);
        if(in_array($type,["jpeg","jpg","png","gif"])){
            $image = new Image();
            $image->open($file);
            $width = $image->width();
            $height = $image->height();
            if($width>250){
                $height = (250/$width)*$height;
                $width = 250;
            }
            $image->thumb( $width,  $height);
            $image->save(preg_replace("/\.".$type."$/","_thumb.".$type,$file));
        }
        if(isset($_FILES['upload'])){
            if($copyState){
                $url = '/Uploads/'.CONTROLLER_NAME.'/'.date('Ymd',time())."/".$viewName;
                $this->ajaxReturn(['uploaded'=>1,'fileName'=>$viewName,'url'=>$url]);
                // echo "<script>window.parent.CKEDITOR.tools.callFunction($cb, '$url', '');</script>";
            }else{
                $this->ajaxReturn(['uploaded'=>0,'error'=>['message'=>'文件上传错误！']]);
                // echo "<script>window.parent.CKEDITOR.tools.callFunction($cb, '', '上传失败');</script>";
            }
            exit;
            
        }else{
            if($copyState){
                $this->ajaxReturn(['errCode'=>0,'fileName'=>$viewName,"url"=>$url,"url2"=>'Uploads/'.CONTROLLER_NAME.'/'.date('Ymd',time())."/".$viewName]);
            }else{
                $this->ajaxReturn(['errCode'=>100,'error'=>'文件上传错误！']);
            }
        }
        
    }
    /** 
     * @Author: vition 
     * @Date: 2018-09-26 11:16:36 
     * @Desc: 统一修改排序 
     */    
    function change_sort(){
        extract($_REQUEST);
        $dbObject=M($db,NULL);
        $allNum = count($data);
        $num = 0;
        foreach ($data as $id => $sort) {
            // echo $id ,"=>", $sort,';';
            $sortResult = $dbObject->where([$dbObject->getPk()=>$id])->save(["sort"=>$sort]);
            if($sortResult){
                $num ++ ;
            }
        }
        $this->ajaxReturn(['errCode'=>0,'error'=>'成功']);
        // if($allNum>0 && $allNum == $num){
        //     $this->ajaxReturn(['errCode'=>0,'error'=>'成功']);
        // }else{
        //     $this->ajaxReturn(['errCode'=>100,'error'=>'排序出错']);
        // }
    }
    function getOptionList(){
        $this->AProject=A("Project");
        $key=I("key");
        $type=I("type");
        $this->ajaxReturn(["data"=>$this->AProject->_getOption($type,$key)]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-09-30 23:21:33 
     * @Desc: excel 导入基础 
     */    
    function excel_import(){
        $table = I("db");
        $con = I("con");
        if(isset($_FILES["excel"])){
            $excelData = excelImport(["file"=>$_FILES["excel"]["tmp_name"]]);
            // print_r($excelData);exit;
            if($excelData){
                
                $db = M($table,NULL);
                $dbFileds = $db->getDbFields();
                $priKey = $db->getPk();
                //验证上传字段是否存在数据表中
                foreach ($excelData[0] as $key ) {
                    if(!empty($key) && !in_array($key,$dbFileds)){
                        $this->ajaxReturn(['errCode'=>116,'error'=>getError(116).":".$key]);
                    }
                }
                if(method_exists($this,$con."_import")){
                    $method = $con."_import";
                    $insertData = $this->$method($excelData);
                }else{
                    $insertData = [];
                    foreach ($excelData as $index => $excelRow) {
                        if($index>0){
                            $temp = [];
                            foreach ($excelData[0] as $i=>$key) {
                                $temp[$key] = $excelRow[$i];
                            }
                            array_push($insertData,$temp);
                        }
                    }
                }
                // 
                if($insertData && is_array($insertData)){
                    //验证上传字段是否存在数据表中
                    // print_r($insertData[0]);
                    foreach ($insertData[0] as $key => $val ) {
                        // dump(in_array($key,$dbFileds));
                        if(!empty($key) && !in_array($key,$dbFileds)){
                            $this->ajaxReturn(['errCode'=>116,'error'=>getError(116).":".$key]);
                        }
                    }
                    $db->startTrans();
                    $this->ApprLogCom->startTrans();
                    
                    foreach ($insertData as $row => $data) {
                        // print_r($data);
                        if(isset($data[$priKey]) && $data[$priKey] > 0 ){
                            $result = $db->save($data);
                        }else{
                            $this->ApprLogCom->createApp($table,$result,session("userId"),"");
                            $result = $db->add($data);
                        }
                        if(!$result){
                            $db->rollback();
                            $this->ajaxReturn(['errCode'=>116,'error'=>getError(116)."；错误行数：".$row]);
                        }
                    }
                    $db->commit();
                    $this->ApprLogCom->commit();
                    $this->LogCom->log(8);
                    $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
                }else{
                    $this->ajaxReturn(['errCode'=>116,'error'=>getError(116)."；错误：".$insertData]);
                }
            }
        }
    }
    /** 
     * @Author: vition 
     * @Date: 2018-10-08 09:42:32 
     * @Desc: excel 导出基础接口 
     */    
    function excel_export(){
        $sqlmd5 = I("sql");
        $con = I("con");
        $sql = $this->Redis->get($sqlmd5);
        $resultData = M()->query($sql);
        if(method_exists($this,$con."_export")){
            $method = $con."_export";
            //返回必须为数据格式['data'=>[],'schema'=>[],'fileName'=>'','template'=>false,'callback'=>false] 数据和表头
            $excelData = $this->$method($resultData);
        }else{
            $excelData = ['data'=>$resultData,'schema'=>$resultData[0],'fileName'=>'excel','template'=>false,'callback'=>false];
        }
        extract($excelData);
        if(isset($excelData['data']) && isset($excelData['schema']) && isset($excelData['fileName'])){
            $this->LogCom->log(7);
            excelExport(["data"=>$data,'schema'=>$schema,'fileName'=>$fileName,'template'=>$template,'callback'=>$callback]);
        }
    }
    function template_down(){
        $con = I('con');
        if(in_array($con,['Basic_basic_brand','Basic_basic_stage','Basic_basic_projectType','Basic_expenClas','Basic_basic_expense_type'])){
            $con = 'Basic_bsme';
        }
        //模板中文名配置
        $templates = [
            'Supplier_supType'=>'供应商类别模板',
            'Basic_basic_module'=>'供应商承接模块模板',
            'Supplier_sup_company'=>'供应商模板',
            'Supplier_supcontact'=>'供应商联系人模板',
            'Public_work_order'=>'工单提交模板',
            'Basic_bsme'=>'品牌、项目阶段、项目类型、固定支出类别、报销类别模板',
            'Basic_basic_field'=>'场地模板',
        ];
        if(isset($templates[$con])){
            $templateName = $templates[$con];
        }else{
            $templateName ='找不到模板';
        }
        header("Location:/Public/excel_template/{$templateName}.xlsx");
    }
    /** 
     * @Author: vition 
     * @Date: 2018-10-09 15:10:50 
     * @Desc: 提交重审信息 
     */    
    function reset_apply(){
        $data = I('data');
        $param = [
            'table_name' => $data['db'],
            'table_id' => $data['id'],
            'user_id' => session('userId'),
            'add_time' => time(),
            'datas' => json_encode($data['datas'],JSON_UNESCAPED_UNICODE),
            'status' => 0,
        ];
        $result = $this->resetCom->insert($param);
        $this->ApprLogCom->createApp($data['db'],$data['id'],session("userId"),"",2);
        // print_r($param);
        $this->ajaxReturn(['errCode'=>$result->errCode,'error'=>getError($result->errCode)]);
        // $this->resetCom->a();
    }
}
