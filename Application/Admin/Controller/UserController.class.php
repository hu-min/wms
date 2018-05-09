<?php
namespace Admin\Controller;


class UserController extends BaseController{
    protected $pageSize=15;

    public function _initialize() {
        parent::_initialize();
        $this->roleCom=getComponent('Role');
        $this->nodeCom=getComponent('Node');
        $this->rNodeCom=getComponent('RoleNode');
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
            $this->assign('userType',$userType);
            $this->assign('userStatus',$userStatus);
            $this->assign('regFrom',$regFrom);
            $this->assign('url',U(CONTROLLER_NAME.'/'.ACTION_NAME));
            $this->returnHtml();
        }
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
        if($userResult){
            $uListRed="userList_".session("userId");
            $this->Redis->set($uListRed,json_encode($userResult['list']),3600);
            $page = new \Think\VPage($userResult['count'], $this->pageSize);
            $pageShow = $page->show();
            $this->assign('url',U(CONTROLLER_NAME.'/'.ACTION_NAME));
            $this->assign('userList',$userResult['list']);
            $this->ajaxReturn(['errCode'=>0,'table'=>$this->fetch('User/userTable/userList'),'page'=>$pageShow]);
        }
        $this->ajaxReturn(['errCode'=>0,'table'=>'无数据','page'=>'']);

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
        if($reqType){
            $this->$reqType();
        }else{
            $this->assign('url',U(CONTROLLER_NAME.'/'.ACTION_NAME));
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
                // $this->log($rnodeResult["list"]);
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
        if($reqType){
            $this->$reqType();
        }else{
            $this->assign('url',U(CONTROLLER_NAME.'/'.ACTION_NAME));
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
        $newAllNodes=[];
        
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
}
