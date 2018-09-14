<?php
namespace Admin\Controller;

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
    protected $pageSize=15;
    // protected $statusType=[0=>"提交申请",1=>"批准",2=>"审核中",3=>"无效",4=>"删除"];
    protected $statusType=[0=>"提交申请",1=>"批准",2=>"审核中",3=>"驳回",4=>"删除",5=>"拒绝"];

    protected $statusLabel=[0=>"blue",1=>"green",2=>"yellow",3=>"orange ",4=>"red",5=>'black'];
    /**
     * 对admin的每一个控制器和方法做权限检查
     */ 
    public function _initialize() {
        // echo 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'];exit;
        parent::_initialize();
        $this->userCom=getComponent('User');
        $this->LogCom=getComponent('Log');
        $this->ApprLogCom=getComponent('ApproveLog');
        $this->nodeCom=getComponent('Node');
        $this->authority=C('authority');
        $this->nodeAuth=session('nodeAuth');
        $this->basicCom=getComponent('Basic');
        $this->exemption=[//排除的控制器
            'Admin/Index/Login',
            'Admin/Index/Main',
            'Admin/Index/logOut',
            'Admin/Index/checkLogin',
            'Admin/Index/Index',
        ];
        $this->refreNode();
        
        // print_r($this->nodeAuth);
        // $this->setLogin();
        $nowConAct=MODULE_NAME."/".CONTROLLER_NAME.'/'.ACTION_NAME;
        if(in_array($nowConAct,$this->exemption) || (ACTION_NAME == 'upload_filesAdd' && $this->isLogin())){
            if(!$this->isLogin() && !in_array(ACTION_NAME,['checkLogin','Login']) ){
                session("history",domain(false).__SELF__);
                $this->redirect('Index/Login');
            }elseif($this->isLogin() && ACTION_NAME=='Login'){
                $this->redirect('Index/Main');
            }
        }else{ 
            $conAct=CONTROLLER_NAME.'/'.ACTION_NAME;
            $auth=$this->authVerify($conAct);
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
            $this->assign('nodeAuth',$this->nodeAuth[CONTROLLER_NAME.'/'.ACTION_NAME]);
            $this->assign('userId',session("userId"));
            $this->assign('processAuth',$this->processAuth);
            $this->assign('statusType',$this->statusType);
            $this->assign('statusTypeJ',json_encode($this->statusType));
            $this->assign('statusLabel',$this->statusLabel);
            // $nodeId = getTabId(I("vtabId"));
            $this->nodeId = getTabId(I("vtabId"));
            // $this->assign('processType',$this->processType);
            // $this->assign('processTypeJ',json_encode($this->processType));
            
            $this->assign('url',U(CONTROLLER_NAME.'/'.ACTION_NAME));
            $this->assign("pageId",$this->createId());
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
        $logType=$this->LogCom->getType(strtolower($reqType));	
        if($logType>2 && $logType<=8){
            if(I("delType")=="deepDel"){
                $logType=6;
            }
            $this->vlog($logType);
        }
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
        $this->log($userInfo);
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
            $this->redirect('Index/Login');
        }else{
            //登录设置
            foreach (['userId','loginName','userName','roleId','rolePid','avatar','usertype','roleId','rolePid'] as $key ) {
                if(isset($userInfo[$key])){
                    session($key,$userInfo[$key]);
                }
            }
            session("userInfo",$userInfo);
            session('isLogin',1);
	        $this->vlog(1);
            if($userInfo['avatar']==""){
                $userInfo['avatar']=U(__ROOT__.'/Public'.'/admintmpl'."/dist/img/avatar/avatar".rand(1,5).".png",'','');
            }else{
                $userInfo['avatar']=U($userInfo['avatar'],'','');
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
        
        $authNodeId=I("authNodeId");
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
        if(!empty($nodeInfo["list"])){
            $processArray=explode(",",$nodeInfo["list"]["processIds"]);
            $processCom=getComponent('Process');
            $processPar=[
                "where"=>["processId"=>["IN",$processArray]],
                "fields"=>"processOption",
            ];
            $processRes=$processCom->getProcessList($processPar);
            if(!empty($processRes["list"])){
                
                foreach ($processRes["list"] as $process) {
                    $processOption = json_decode($process["processOption"],true);
                    foreach ($processOption as $level => $subProcess) {
                        if($subProcess["type"]==1){
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
        if(!$id){
            $this->ajaxReturn(['errCode'=>110,'error'=>'ID异常']);
        }
        if(!$db){
            $this->ajaxReturn(['errCode'=>110,'error'=>'数据表名称异常']);
        }
        if($statusType=="del"){
            $conResult=$dbObject->save([$dbObject->getPk()=>$id,"status"=>$status]);
            // echo $dbObject->getPk();exit;
        }else if($statusType=="deepDel"){
            $seniorResult=$this->userCom->checkSeniorPwd(session("userId"),$seniorPwd);
            if($seniorResult->errCode!==0){
                $this->ajaxReturn(['errCode'=>$seniorResult->errCode,'error'=>$seniorResult->error]);
            }
            $conResult=$dbObject->where([$dbObject->getPk()=>$id])->delete();
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

        $url=ROOT_PATH.'Uploads/'.CONTROLLER_NAME.'/'.date('Ymd',time())."/";
        if(!file_exists($url)){
            mkdir($url, 0755,true);
        }
        $viewName = $_FILES['file']['name'];
        $name = $viewName;
        if(PHP_OS=="WINNT"){
            $name = iconv("UTF-8","gb2312",$viewName);
        }
        $file=$url.$name;
        
        $copyState =  copy($_FILES['file']['tmp_name'],$file);
        if($copyState){
            $this->ajaxReturn(['errCode'=>0,'fileName'=>$viewName,"url"=>$url,"url2"=>'Uploads/'.CONTROLLER_NAME.'/'.date('Ymd',time())."/".$viewName]);
        }else{
            $this->ajaxReturn(['errCode'=>100,'error'=>'文件上传错误！']);
        }
    }
}
