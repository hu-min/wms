<?php
namespace Admin\Controller;


class UserController extends BaseController{
    protected $pageSize=15;

    public function _initialize() {
        parent::_initialize();
        $this->roleCom=getComponent('Role');
        $this->rNodeCom=getComponent('RoleNode');
        $this->processCom=getComponent('Process');
        Vendor("levelTree.levelTree");
        $this->levelTree=new \levelTree();
    }
    /*用户管理*/
    /** 
     * @Author: vition 
     * @Date: 2018-01-23 00:31:36 
     * @Desc: 用户列表页面
     */    
    function userControl(){
        $userType=C("userType");
        $userStatus=C("userStatus");
        $regFrom=C("regFrom");
        $reqType=I('reqType');
        $this->assign("controlName","user");
        $this->assign('userType',$userType);
        $this->assign('userStatus',$userStatus);
        $this->assign('regFrom',$regFrom);
        $this->assign('dbName',"User");//删除数据的时候需要
        $where=[
            'rolePid'=>['gt',0],
            'status'=>['eq',1],
        ];
        $parameter=[
            'where'=>$where,
            'page'=>0,
            'pageSize'=>9999,
        ];
        $roleResult=$this->roleCom->getRoleList($parameter);
        $roleKeys=array_column($roleResult['list'],'roleId');
        $roleVals=array_column($roleResult['list'],'roleName');
        $roleArr=array_combine($roleKeys,$roleVals);
        $this->assign('roleArr',$roleArr);
        $this->assign('roleList',$roleResult['list']);
        if($reqType){
            $this->$reqType();
        }else{   
            $this->returnHtml();
        }
    }
    function user_modalOne(){
        $title = "新建用户";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        
        if($gettype=="Edit"){
            $title = "编辑用户";
            $btnTitle = "保存数据";
            $redisName="userList";
            $resultData=$this->userCom->redis_one($redisName,"userId",$id);
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"userModal",
        ];
        $this->modalOne($modalPara);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-02-03 00:24:54 
     * @Desc: 处理添加和修改 
     */    
    function manageUserInfo(){
        $reqType=I("reqType");
        $datas=I("data");
        $filesData=I("filesData");
        if($filesData[urlencode($datas['avatar'])]){
            $datas['avatar']=base64Img($filesData[urlencode($datas['avatar'])])["url2"];
        }
        preg_match("/\S([A-Z]+[^[A-Z]*\S]*)$/",$reqType,$match);
        $reqType=$match[1];
        $userInfo=[];
        if($reqType=="Add"){
            $userInfo=[
                'userId'=>$datas['userId'],
                'loginName'=>$datas['loginName'],
                'userName'=>$datas['userName'],
                'avatar'=>$datas['avatar'],
                'password'=>sha1(sha1($datas['password'])),
                'phone'=>$datas['phone']?$datas['phone']:0,
                'gender'=>$datas['gender'],
                'userType'=>$datas['userType'],
                'regFrom'=>1,
                'regTime'=>time(),
                'openId'=>'',
                'lastIp'=>ipTolong(getIp()),
                'lastTime'=>time(),
                'loginNum'=>0,
                'roleId'=>$datas['roleId'],
                'status'=>$datas['status'],
            ];
        }elseif($reqType=="Edit"){
            $userInfo=[
                'userId'=>$datas['userId'],
                'loginName'=>$datas['loginName'],
                'userName'=>$datas['userName'],
                'avatar'=>$datas['avatar'],
                'phone'=>$datas['phone'],
                'gender'=>$datas['gender'],
                'userType'=>$datas['userType'],
                'status'=>$datas['status'],
                'roleId'=>$datas['roleId'],
            ];
            if($datas['password']!=""){
                $userInfo['password']=sha1(sha1($datas['password']));
            }
        }
        return $userInfo;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-01-27 22:45:23 
     * @Desc: 添加、修改用户信息 
     */    
    function userEdit(){
        $userInfo=$this->manageUserInfo();
        if($userInfo["userId"]==1 && session("userId")!=1){
            $this->ajaxReturn(['errCode'=>10003,'error'=>getError(10003)]);
        }
        $updateResult=$this->userCom->updateUser($userInfo);
        
        if($updateResult->errCode==0){
            $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100),'reqType'=>$reqType]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-02-03 00:25:06 
     * @Desc: 执行添加 
     */    
    function userAdd(){
        $userInfo=$this->manageUserInfo();
        $insertResult=$this->userCom->insertUser($userInfo);
        if($insertResult->errCode==0){
            $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
        }
        
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100),'reqType'=>$reqType]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-01-27 14:01:45 
     * @Desc: 获取用户列表 
     */    
    protected function userList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $where=[];
        if($data['loginName']){
            $where['loginName']=['LIKE','%'.$data['loginName'].'%'];
        }
        if($data['userName']){
            $where['userName']=['LIKE','%'.$data['userName'].'%'];
        }
        if($data['gender']){
            $where['gender']=$data['gender'];
        }
        if($data['userType']){
            $where['userType']=$data['userType'];
        }
        if(isset($data['status'])){
            $where['status']=$data['status'];
        }
        if(isset($data['userType'])){
            $where['userType']=$data['userType'];
        }
        if(isset($data['roleId'])){
            $where['roleId']=$data['roleId'];
        }
        if($data['regFrom']){
            $where['regFrom']=$data['regFrom'];
        }
        $parameter=[
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$this->pageSize,
        ];
        
        $userResult=$this->userCom->getUserList($parameter);
        $this->tablePage($userResult,'User/userTable/userList',"userList");
        // if($userResult){
        //     $uListRed="userList_".session("userId");
        //     $this->Redis->set($uListRed,json_encode($userResult['list']),3600);
        //     $page = new \Think\VPage($userResult['count'], $this->pageSize);
        //     $pageShow = $page->show();
            
        //     $this->assign('userList',$userResult['list']);
        //     $this->ajaxReturn(['errCode'=>0,'table'=>$this->fetch('User/userTable/userList'),'page'=>$pageShow]);
        // }
        // $this->ajaxReturn(['errCode'=>0,'table'=>'无数据','page'=>'']);

    }
    /** 
     * @Author: vition 
     * @Date: 2018-02-04 00:57:27 
     * @Desc: 获取单条用户信息 
     */    
    function userOne(){
        $id	=I("id");
        $parameter=[
            'userId'=>$id,
        ];
        $uListRed="userList_".session("userId");
        $userList=$this->Redis->get($uListRed);
        if($userList){
            foreach ($userList as $user) {
               if($user['userId']==$id){
                $this->ajaxReturn(['errCode'=>0,'info'=>$user]);
               }
            }
        }
        $userResult=$this->userCom->getUser($parameter);
        if($userResult->errCode==0){
            $this->ajaxReturn(['errCode'=>0,'info'=>$userResult->data]);
        }
        $this->ajaxReturn(['errCode'=>110,'info'=>'无数据']);
    }
    /*角色管理*/
    /** 
     * @Author: vition 
     * @Date: 2018-02-04 18:06:49 
     * @Desc: 角色控制 
     */    
    function roleControl(){
        $regFrom=C("regFrom");
        $reqType=I('reqType');
        $this->assign('dbName',"Role");//删除数据的时候需要
        $this->assign("controlName","rolerNode");
        if($reqType){
            $this->$reqType();
        }else{
            
            $this->returnHtml();
        }
    }
    /** 
     * @Author: vition 
     * @Date: 2018-02-04 18:55:38 
     * @Desc: 获取角色列表 
     */    
    function roleList(){
       
        $where=[];
        $parameter=[
            'where'=>$where,
            'page'=>0,
            'pageSize'=>9999,
            'orderStr'=>'rolePid ASC',
        ];
        $roleTree=[];
        $roleResult=$this->roleCom->getRoleList($parameter);
        $level1=[];
        foreach ($roleResult["list"] as $key => $value) {
            $backColor=$value["status"]!=1?"#FF8C00":null;
            $color=$value["status"]!=1?"#FFFF00":null;
            if($value["rolePid"]==0){
                array_push($level1,$value["roleId"]);
                array_push($roleTree,["text"=>$value["roleName"],"icon"=>"fa fa-users",'nodes'=>[],"id"=>$value["roleId"],"status"=>$value["status"],"backColor"=>$backColor,"color"=>$color,"remark"=>$value["remark"]]);
            }else{
                array_push($roleTree[array_search($value["rolePid"],$level1)]['nodes'],["text"=>$value["roleName"],"icon"=> "fa fa-user","id"=>$value["roleId"],"status"=>$value["status"],"backColor"=>$backColor,"color"=>$color,"remark"=>$value["remark"]]);
            }
        }
        $this->ajaxReturn(["tree"=>$roleTree]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-02-06 18:01:33 
     * @Desc: 添加角色 
     */    
    function roleAdd(){
        $roleInfo=$this->manageRoleInfo();
        $insertResult=$this->roleCom->insertRole($roleInfo);
        if($insertResult->errCode==0){
            $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-02-06 21:34:36 
     * @Desc: 编辑角色 
     */    
    function roleEdit(){
        $roleInfo=$this->manageRoleInfo();
        $insertResult=$this->roleCom->updateRole($roleInfo);
        $this->ajaxReturn(['errCode'=>$insertResult->errCode,'error'=>$insertResult->error]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-02-25 16:56:20 
     * @Desc: 角色节点权限 
     */    
    function rolerNodeEdit(){
        $roleId=I("roleId",0,'int');
        $authData=I("data");
        // $this->log($authData);
        foreach ($authData as $nodeId => $authority) {
            $parameter=[
                'where'=>['roleId'=>$roleId,"nodeId"=>$nodeId],
            ];
            $rnodeResult=$this->rNodeCom->getRoleNodeOne($parameter);
            if($rnodeResult){
                $rnodeResult["list"]["authority"]=$authority;
                $result=$this->rNodeCom->updateRoleNode(["where"=>["rnId"=>$rnodeResult["list"]["rnId"]],"data"=>$rnodeResult["list"]]);
            }else{
                $result=$this->rNodeCom->insertRoleNode(["roleId"=>$roleId,"nodeId"=>$nodeId,"authority"=>$authority]);
            }
        }
        $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
        
    }
    function rnodeOne(){
        $roleId=I("roleId",0,'int');
        $parameter=[
            'where'=>['roleId'=>$roleId],
            'page'=>0,
            'pageSize'=>9999,
            'orderStr'=>'rnId ASC',
        ];
        $rNodeResult=$this->rNodeCom->getRoleNodeList($parameter);
        $authList=[];
        if($rNodeResult){
            $authList=$rNodeResult['list'];
        }
        $this->assign("nodeTree",$this->getNodeTree());
        $this->assign("auth",json_encode($authList));
        // $this->log($authList);
        $this->ajaxReturn(['errCode'=>0,'info'=>$this->fetch("User/roleNodeControl")]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-02-06 18:03:02 
     * @Desc: 处理添加和修改角色的数据 
     */    
    function manageRoleInfo(){
        $reqType=I("reqType");
        $datas=I("data");
        if($reqType=="roleAdd"){
            unset($datas['roleId']);
            return $datas;
        }else if($reqType=="roleEdit"){
            $where=["roleId"=>$datas['roleId']];
            $data=[];
            if(isset($datas['remark'])){
                $data['remark']=$datas['remark'];
            }
            if(isset($datas['roleName'])){
                $data['roleName']=$datas['roleName'];
            }
            if(isset($datas['rolePid'])){
                $data['rolePid']=$datas['rolePid'];
            }
            if(isset($datas['status'])){
                $data['status']=$datas['status'];
            }
            return ["where"=>$where,"data"=>$data];
        }
    }
    /*节点控制*/
    /** 
     * @Author: vition 
     * @Date: 2018-02-06 23:24:00 
     * @Desc: 节点控制 
     */    
    function nodeControl(){
        $regFrom=C("regFrom");
        $reqType=I('reqType');
        $this->assign('dbName',"Node");//删除数据的时候需要
        if($reqType){
            $this->$reqType();
        }else{
            
            $this->returnHtml();
        }
    }
    /** 
     * @Author: vition 
     * @Date: 2018-02-06 23:24:11 
     * @Desc: 节点列表 
     */    
    function nodeList(){
        $this->ajaxReturn(["tree"=>$this->getNodeTree()]);
    }
    function getNodeTree(){
        $parameter=[
            'page'=>0,
            'pageSize'=>9999,
            'orderStr'=>'level DESC,sort ASC',
        ];
        $nodeResult=$this->nodeCom->getNodeList($parameter);
        $nodeTree=[];
        $level=[];
        
        $nodeArray=$nodeResult["list"];
        foreach ($nodeArray AS $key => $nodeInfo) {
            $level[$nodeInfo["level"]][$nodeInfo["nodePid"]][]= $nodeInfo;
            unset($nodeArray[$key]);
        }
        $this->Redis->set("nodeArray",json_encode($nodeResult["list"]),3600);
        asort($level);
        
        $this->levelTree->setKeys(["idName"=>"nodeId","pidName"=>"nodePid"]);
        $this->levelTree->setReplace(["nodeTitle"=>"text","nodeIcon"=>"icon","nodeId"=>"id"]);
        $this->levelTree->switchOption(["beNode"=>false,"idAsKey"=>false]);
        $nodeTree=$this->levelTree->createTree($nodeResult["list"]);
        return $nodeTree;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-02-22 18:42:35 
     * @Desc: 获取单个节点信息 
     */    
    function nodeOne(){
        $nodeId	=I("nodeId");
        $nodeInfo=$this->getNodeOne($nodeId);
        if(!empty($nodeInfo)){
            $this->ajaxReturn(['errCode'=>0,'info'=>$nodeInfo]);
        }
        $this->ajaxReturn(['errCode'=>110,'info'=>'无数据']);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-02-22 23:21:29 
     * @Desc: get单条节点信息 
     */    
    function getNodeOne($nodeId){
        $parameter=[
            'nodeId'=>$nodeId,
        ];
        $nListRed="nodeArray";
        $nodeList=$this->Redis->get($nListRed);
        if($nodeList){
            foreach ($nodeList as $node) {
                if($node['nodeId']==$nodeId){
                    return $node;
                }
            }
        }
        $nodeResult=$this->nodeCom->getNodeOne($parameter);
        if($nodeResult->errCode==0){
            return $nodeResult->data['list'];
        }
        return [];
    }
    /** 
     * @Author: vition 
     * @Date: 2018-02-22 22:43:55 
     * @Desc: 添加节点 
     */    
    function nodeAdd(){
        $nodeInfo=$this->manageNodeInfo();
        $insertResult=$this->nodeCom->insertNode($nodeInfo);
        if($insertResult->errCode==0){
            $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-02-22 23:11:49 
     * @Desc: 编辑节点 
     */    
    function nodeEdit(){
        $nodeInfo=$this->manageNodeInfo();
        $updateResult=$this->nodeCom->updateNode($nodeInfo);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-02-23 10:05:33 
     * @Desc: 节点数据处理 
     */    
    function manageNodeInfo(){
        $reqType=I("reqType");
        $datas=I("data");
        $nodePInfo=$this->getNodeOne($datas['nodePid']);
        $datas['level']=$nodePInfo['level']+1;
        if($reqType=="nodeAdd"){
            unset($datas['nodeId']);
            return $datas;
        }else if($reqType=="nodeEdit"){
            $where=["nodeId"=>$datas['nodeId']];
            $data=[];
            if(isset($datas['nodeNames'])){
                $data['nodeNames']=$datas['nodeNames'];
            }
            if(isset($datas['controller'])){
                $data['controller']=$datas['controller'];
            }
            if(isset($datas['nodeIcon'])){
                $data['nodeIcon']=$datas['nodeIcon'];
            }
            if(isset($datas['nodePid'])){
                $data['nodePid']=$datas['nodePid'];
            }
            if(isset($datas['nodeTitle'])){
                $data['nodeTitle']=$datas['nodeTitle'];
            }
            if(isset($datas['sort'])){
                $data['sort']=$datas['sort'];
            }
            $data['level']=$datas['level'];
            if(isset($datas['status'])){
                $data['status']=$datas['status'];
            }
            return ["where"=>$where,"data"=>$data];
        }
    }
    /** 
     * @Author: vition 
     * @Date: 2018-02-23 10:07:12 
     * @Desc: 获取iconlist 
     */    
    function iconList(){
        $this->ajaxReturn(['errCode'=>0,'info'=>$this->fetch("Index/Icons")]);
    }

    function processControl(){
        $reqType=I('reqType');
        $this->assign("controlName","user_process");
        $this->assign("groupData",$this->getRoles(1));
	    $this->assign("roleData",$this->getRoles(2));
        if($reqType){
            $this->$reqType();
        }else{
	    
            
            $this->returnHtml();
        }
    }
    function user_process_modalOne(){
        $title = "新建审核流程";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        
        if($gettype=="Edit"){
            $title = "编辑审核流程";
            $btnTitle = "保存数据";
            $redisName="user_processList";
            $resultData=$this->processCom->redis_one($redisName,"processId",$id);
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"processModal",
        ];
        $this->modalOne($modalPara);
    }
    function user_processList(){
	    $data=I("data");
        $p=I("p")?I("p"):1;
        $where=[];
        if($data['processName']){
            $where['processName']=['LIKE','%'.$data['processName'].'%'];
        }
        if($data['processDepict']){
            $where['processDepict']=['LIKE','%'.$data['processDepict'].'%'];
        }
        if(isset($data['status'])){
            $where['status']=$data['status'];
        }
        $parameter=[
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$this->pageSize,
        ];
        
        $processResult=$this->processCom->getProcessList($parameter);
        $this->tablePage($processResult,'User/userTable/processList',"user_processList");
        // if($processResult){
        //     $pListRed="processList_".session("userId");
        //     $this->Redis->set($pListRed,json_encode($processResult['list']),3600);
        //     $page = new \Think\VPage($processResult['count'], $this->pageSize);
        //     $pageShow = $page->show();
            
        //     $this->assign('list',$processResult['list']);
        //     $this->ajaxReturn(['errCode'=>0,'table'=>$this->fetch('User/userTable/processList'),'page'=>$pageShow]);
        // }
        // $this->ajaxReturn(['errCode'=>0,'table'=>'无数据','page'=>'']);

    }
    function getRolesList(){
        $key=I("key");
        $this->ajaxReturn(["data"=>$this->getRoles($key)]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-09 23:51:01 
     * @Desc: 客户列表 
     */    
    function getRoles($roleType=1,$key="",$option=true){
        $where=["status"=>"1"];
        $join="";
        $pName="";
        if($roleType==1){
            $where["rolePid"]=0;
        }else{
            $where["rolePid"]=["gt",0];
            $join="LEFT JOIN (SELECT roleId pid,roleName pname FROM v_role) pr ON pr.pid=rolePid";
            $pName=",pname";
        }
        if($key!=""){
            $where["roleName"]=["LIKE","%{$key}%"];
        }
        $parameter=[
            'fields'=>"roleId,roleName".$pName,
            'where'=>$where,
            'page'=>1,
            'pageSize'=>9999,
            'orderStr'=>"roleId DESC",
	        'joins'=>$join,
        ];
        $roleResult=$this->roleCom->getRoleList($parameter);
        if($option){
            $optionStr='<option value=""></option>';
            foreach($roleResult['list'] as $opt){
                $optionStr.='<option value="'.$opt["roleId"].'">'.(isset($opt["pname"])?$opt["pname"]."——":"").$opt["roleName"].'</option>';
            }
            return $optionStr;
        }
        return $roleResult['list'] ? $roleResult['list'] : [];
    }

    function manageProcessInfo(){
        $reqType=I("reqType");
        $datas=I("data");
        $datas["processDepict"]=json_encode($datas["Depict"],JSON_UNESCAPED_UNICODE);
        unset($datas["Depict"]);

        $datas["processOption"]=json_encode($datas["Option"],JSON_UNESCAPED_UNICODE);
        unset($datas["Option"]);
        if($reqType=="user_processAdd"){
	    $datas["addTime"]=time();
            unset($datas['processId']);
            return $datas;
        }else if($reqType=="user_processEdit"){
            $where=["processId"=>$datas['processId']];
            $data=[];
            if(isset($datas['processName'])){
                $data['processName']=$datas['processName'];
            }
	    if(isset($datas['processDepict'])){
                $data['processDepict']=$datas['processDepict'];
            }
	    if(isset($datas['processOption'])){
                $data['processOption']=$datas['processOption'];
            }
            if(isset($datas['status'])){
                $data['status']=$datas['status'];
            }
	        $data['updateTime']=time();
            return ["where"=>$where,"data"=>$data];
        }
    }

    function user_processAdd(){
	    $Info=$this->manageProcessInfo();
        $insertResult=$this->processCom->insertProcess($Info);
        if($insertResult->errCode==0){
            $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100),'reqType'=>$reqType]);
    }
    function user_processEdit(){
        $Info=$this->manageProcessInfo();
        $updateResult=$this->processCom->updateProcess($Info);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
    // function processOne(){
    //     $id	=I("id");
    //     $parameter=[
    //         'processId'=>$id,
    //     ];
    //     $ListRed="processList_".session("userId");
    //     $List=$this->Redis->get($ListRed);
    //     if($List){
    //         foreach ($List as $process) {
    //            if($process['processId']==$id){
    //             $this->ajaxReturn(['errCode'=>0,'info'=>$process]);
    //            }
    //         }
    //     }
    //     $Result=$this->processCom->getProcess($parameter);
    //     if($Result->errCode==0){
    //         $this->ajaxReturn(['errCode'=>0,'info'=>$Result->data]);
    //     }
    //     $this->ajaxReturn(['errCode'=>110,'info'=>'无数据']);
    // }

    function processAuth(){
        $reqType=I('reqType');
        $this->assign("controlName","process_auth");
        $this->assign('dbName',"Process");//删除数据的时候需要
        if($reqType){
            $this->$reqType();
        }else{
	    $this->assign("processData",$this->getProcess());
            
            $this->returnHtml();
        }
    }
    function nodeAuthOne(){
        $id	=I("nodeId");
        $where=[
            'status'=>1,
            '_string'=>"FIND_IN_SET({$id},processNode)",
        ];
        $parameter=[
            'fields'=>"processId",
            'where'=>$where,
        ];
        $Result=$this->processCom->getProcessList($parameter);
        if($Result->errCode==0){
	    $processArr= $Result["list"] ? array_column($Result["list"],"processId") : [];
            $this->ajaxReturn(['errCode'=>0,'info'=>$processArr]);
        }
        $this->ajaxReturn(['errCode'=>110,'info'=>'无数据']);
    }
    function getProcess($key="",$option=true){
        $where=["status"=>"1"];
        if($key!=""){
            $where["processName"]=["LIKE","%{$key}%"];
        }
        $parameter=[
            'fields'=>"processId,processName",
            'where'=>$where,
            'page'=>1,
            'pageSize'=>9999,
            'orderStr'=>"processId DESC",
        ];
        $result=$this->processCom->getProcessList($parameter);
        if($option){
            $optionStr='<option value=""></option>';
            foreach($result['list'] as $opt){
                $optionStr.='<option value="'.$opt["processId"].'">'.$opt["processName"].'</option>';
            }
            return $optionStr;
        }
        return $result['list'] ? $result['list'] : [];
    }
    function process_authEdit(){
        $datas =I("data");
        $nodeInfo=[
            "nodeId"=>$datas["nodeId"],
            "processIds"=>trim(implode(",",$datas["processIds"]),","),
        ];
        $updateResult=$this->nodeCom->updateNode($nodeInfo);
	$nListRed="nodeArray";
        $nodeList=$this->Redis->get($nListRed);
        if($nodeList){
            foreach ($nodeList as $index => $node) {
                if($node['nodeId']==$nodeInfo["nodeId"]){
                    $nodeList[$index]['processIds']=$nodeInfo["processIds"];
		    $this->Redis->set($nListRed,$nodeList);
		    break;
                }
            }
        }

        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
	
    }
}   
