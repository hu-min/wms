<?php
namespace Component\Controller;
// use Common\Controller\BaseController;

class UserController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/User');
        $this->nodeDB = D('Component/Node');
        $this->roleDB = D('Component/Role');
    }
    /** 
     * @Author: vition 
     * @Date: 2018-01-14 22:08:13 
     * @Desc: 检查用户 
     */    
    function checkUser($parameter=[]){
        // $this->log($parameter);
        $qiye_id = false;
        $res=$this->initRes();
        if($parameter['qiye_id'] && (!$parameter['loginName'] && !$parameter['password'])){

        }else if($parameter['loginName'] && $parameter['password']){
            $parameter['loginName'] = strtolower($parameter['loginName']);
            $parameter['password']=sha1(sha1($parameter['password']));
            if($parameter['qiye_id']){
                $qiye_id = $parameter['qiye_id'];
                unset($parameter['qiye_id']);
            }
        }else{
            $res->errCode=110;
            $res->error=getError(110);
            return $res;
        }
        
        $parArray=[
            'where'=>$parameter,
            'fields'=>'*',
            'joins'=>[
                'LEFT JOIN (SELECT roleId role_id ,rolePid,roleName FROM v_role) r ON r.role_id = roleId',
                'LEFT JOIN (SELECT roleId role_pid ,roleName rolePName FROM v_role) rp ON rp.role_pid = r.rolePid',
            ],
        ];
        $userResult=$this->selfDB->getOne($parArray);
        // $this->log($this->selfDB->_sql());
        if($userResult){
            unset($userResult["password"]);
            unset($userResult["seniorPassword"]);
            if($qiye_id){
                session("userId",$userResult["userId"]);
			    session("userName",$userResult["userName"]);
                $param = [
                    "where"=>$parameter,
                    "data"=>['qiye_id'=>$qiye_id],
                ];
                $this->update($param);
            }
            if($userResult['status']!=1){
                $res->errCode=10002;
                $res->error=getError(10002);
                return $res;

            }
            if($userResult['roleId']<=0){
                $res->errCode=10004;
                $res->error=getError(10004);
                return $res;
            }
            // $roleResult = $this->roleDB->getOne(["where"=>["roleId"=>$userResult['roleId']],"fields"=>"rolePid"]);
            // $userResult['rolePid'] = $roleResult["rolePid"];
            $this->getUserNode($userResult['userId']);
            $res->errCode=0;
            $res->error=getError(0);
            $res->data=$userResult;
            return $res;
        }
        $res->errCode=10000;
        $res->error=getError(10000);
        return $res;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-06-02 09:59:18 
     * @Desc: 验证高级密码 
     */    
    function checkSeniorPwd($userId,$seniorPwd){
        $where['seniorPassword']=sha1(sha1($seniorPwd));
        $where['userId']=$userId;
        $parArray=[
            'where'=>$where,
            'fields'=>'loginName,status',
        ];
        $userResult=$this->selfDB->getOne($parArray);
        if($userResult){
            if($userResult['status']!=1){
                $res->errCode=10002;
                $res->error=getError(10002);
                return $res;
            }
            $res->errCode=0;
            $res->error=getError(0);
            $res->data=$userResult;
            return $res;
        }
        $res->errCode=10006;
        $res->error=getError(10006);
        return $res;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-01-18 00:17:50 
     * @Desc: 通过用户userId获取节点信息 4层
     */    
    function getUserNode($userId){
        $res=$this->initRes();
        
        $nodeName='userNode_'.$userId;

        $authority=[];
        $refre=$this->Redis->get($this->refreNode);
        if($refre && $refre!=1){
            $menus=$this->Redis->get($nodeName);
        }
        if($menus){
            $res->errCode=0;
            $res->error=getError(0);
            $res->data=$menus;
            return $res;
        }
        $menus=[];
        $mNodeResult=$this->nodeDB->query("SELECT * FROM v_node n INNER JOIN (SELECT nodeId,authority FROM v_role_node WHERE roleId IN (SELECT roleId FROM v_user WHERE userId={$userId} AND authority>0)) nr ON nr.nodeId=n.nodeId WHERE n.showType=1 AND status=1 ORDER BY n.nodePid ASC, n.`level` ASC, n.`sort` ASC");
        $newAllNodes = array();
        $mNodes=[];
        Vendor("levelTree.levelTree");
        $levelTree=new \levelTree();
        $levelTree->setKeys(["idName"=>"nodeId","pidName"=>"nodePid","nodeName"=>"node"]);
        // $levelTree->setReplace(["roleid"=>"nodeId","roleTitle"=>"nodeTitle","rolePid"=>"nodePid"]);
        $menus=$levelTree->createTree($mNodeResult);
        // $menus=setLevelTree(["nodeList"=>$mNodeResult,"id"=>"nodeId","pid"=>"nodePid","nodes"=>"node"]);
        $nodeInfos = [];
        if($mNodeResult) {
            foreach ($mNodeResult AS $nodeInfo) {
                
                $newAllNodes[$nodeInfo['nodeId']] = $nodeInfo;
                if($nodeInfo['controller']!=""){
                    $authority[$nodeInfo['controller']]=$nodeInfo['authority'];
                    array_push($nodeInfos,$nodeInfo);
                }
            }
        }else{
            $res->errCode=10001;
            $res->error=getError(10001);
            return $res;
        }
        session('nodeAuth',$authority);
        session('nodeInfo',$nodeInfos);
        $this->Redis->set($refreNode,2,3600);
        $this->Redis->set($nodeName,$menus,3600);
        $res->errCode=10001;
        $res->error=getError(10001);
        $res->data=$menus;
        return $res;
    }
    // /** 
    //  * @Author: vition 
    //  * @Date: 2018-01-27 14:50:51 
    //  * @Desc: 获取用户列表 
    //  */    
    // function getUserList($parameter=[]){
    //     $res=$this->initRes();
    //     $where=$parameter['where']?$parameter['where']:true;
    //     $fields=$parameter['fields']?$parameter['fields']:true;
    //     $orderStr=$parameter['orderStr']?$parameter['orderStr']:null;
    //     $page=$parameter['page']?$parameter['page']:0;
    //     $pageNum=$parameter['pageSize']?$parameter['pageSize']:0;
    //     $groupBy=$parameter['groupBy']?$parameter['groupBy']:null;
    //     $userList=$this->selfDB->getList($where , $fields, $orderStr, $page, $pageNum, $groupBy);
    //     $count=$this->selfDB->countList($where);
    //     if($userList){
    //         return ['list'=>$userList,'count'=>$count];
    //     }
    //     return false;
    // }
    /** 
     * @Author: vition 
     * @Date: 2018-01-14 21:49:32 
     * @Desc: 获取用户信息，除了密码 
     */    
    function getUser($parameter=[]){
        $res=$this->initRes();
        if(empty($parameter)){
            $res->errCode=110;
            $res->error=getError(110);
            return $res;
        }
        $where=[];
        if($parameter['userId']){
            $where['userId']=$parameter['userId'];
        }
        $userResult=$this->selfDB->getOne(['where'=>$where,'fields'=>'password','noField'=>true]);
        if($userResult){
            $res->errCode=0;
            $res->error=getError(0);
            $res->data=$userResult;
            return $res;
        }
    }
    // /** 
    //  * @Author: vition 
    //  * @Date: 2018-01-30 00:42:29 
    //  * @Desc:  
    //  */    
    // function insertUser($userInfo){
    //     $res=$this->initRes();
    //     $insertResult=$this->selfDB->insert($userInfo);
    //     if($insertResult){
    //         $res->errCode=0;
    //         $res->error=getError(0);
    //         return $res;
    //     }
    //     $res->errCode=111;
    //     $res->error=getError(111);
    //     return $res;
    // }
    // /** 
    //  * @Author: vition 
    //  * @Date: 2018-02-03 00:42:15 
    //  * @Desc:  
    //  */    
    // function updateUser($userInfo){
    //     $res=$this->initRes();
    //     $insertResult=$this->selfDB->save($userInfo);
    //     $this->log($userInfo);
    //     if($insertResult){
    //         $res->errCode=0;
    //         $res->error=getError(0);
    //         return $res;
    //     }
    //     $res->errCode=111;
    //     $res->error=getError(111);
    //     return $res;
    // }
    /** 
     * @Author: vition 
     * @Date: 2018-02-04 00:15:49 
     * @Desc: 登录退出记录 
     */    
    function logIORec($userId){
        $this->selfDB->where(['userId'=>$userId])->setInc("loginNum");
        $this->selfDB->modify(['userId'=>$userId],['lastTime'=>time(),'lastIp'=>ipTolong(getIp())]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-08-28 16:09:42 
     * @Desc:  userid和roleid互换
     */    
    function getUserInfo($userId,$roleId=0){
        if($roleId>0){
            $where=["roleId"=>$roleId];
        }else{
            $where=["userId"=>$userId];
        }
        $parameter=[
            'where'=>$where,
            'fields'=>'userId,loginName,userName,avatar,phone,gender,userType,roleId,roleName,rolePid,rolePName',
            'joins'=>[
                'LEFT JOIN (SELECT roleId role_id ,rolePid,roleName FROM v_role) r ON r.role_id = roleId',
                'LEFT JOIN (SELECT roleId role_pid ,roleName rolePName FROM v_role) rp ON rp.role_pid = r.rolePid',
            ],
        ];
        $userRoleRes =  $this->getOne($parameter);
        if(!empty($userRoleRes["list"])){
            return $userRoleRes["list"];
        }else{
            return [];
        }
    }
    function getWhites(){
        $redisName = "whiteIds_redis";
        $whiteIds = $this->Redis->get($redisName);
        if(!$whiteIds){
            $whiteDb = M();
            $whiteRes = $whiteDb->query("SELECT GROUP_CONCAT(user_id) white_ids FROM v_white WHERE `status` = 1");
            $whiteIds = $whiteRes[0]["white_ids"];
            $this->Redis->set($redisName,$whiteIds,7200);
        }
        return$whiteIds;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-10-14 16:08:58 
     * @Desc: 用户id转企业微信id 
     */    
    function getQiyeId($userId){
        $where = [];
        if(is_array($userId)){
            $where['userId'] = ["IN",$userId];
        }else{
            $where['userId'] = $userI; 
        }
        $parameter=[
            'where'=>$where,
            'fields'=>'qiye_id',
        ];
        $userResult=$this->getList($parameter);
        $qiye_id = "";
        $qiye_arr = [];
        foreach ($userResult['list'] as $value) {
            if($value['qiye_id']){
                array_push($qiye_arr,$value['qiye_id']);
            }
        }
        return implode("|",$qiye_arr);
    }
}
