<?php
namespace Component\Controller;
use Common\Controller\BaseController;

class UserController extends BaseController{
    protected $userDB;
    public function _initialize(){
        parent::_initialize();
        $this->userDB = D('Component/User');
        $this->nodeDB = D('Component/Node');
    }
    /** 
     * @Author: vition 
     * @Date: 2018-01-14 22:08:13 
     * @Desc: 检查用户 
     */    
    function checkUser($parameter=[]){
        $this->log($parameter);
        $res=$this->initRes();
        if(!$parameter['loginName'] || !$parameter['password']){
            $res->errCode=110;
            $res->error=getError(110);
            return $res;
        }
        $parameter['password']=sha1(sha1($parameter['password']));
        $parArray=[
            'where'=>$parameter,
            'fields'=>'password',
            'noField'=>true,
        ];
        $userResult=$this->userDB->getOne($parArray);
        $this->log($this->userDB->_sql());
        if($userResult){
            if($userResult['status']!=1){
                $res->errCode=10002;
                $res->error=getError(10002);
                return $res;

            }
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
        $mNodeResult=$this->nodeDB->query("SELECT * FROM v_node WHERE nodeId IN (SELECT nodeId FROM v_role_node WHERE roleId IN (SELECT roleId FROM v_user_role WHERE userId={$userId})) ORDER BY nodePid ASC, `level` ASC, `sort` ASC");
        $newAllNodes = array();
        $mNodes=[];
        if($mNodeResult) {
            foreach ($mNodeResult AS $nodeInfo) {
                $newAllNodes[$nodeInfo['nodeId']] = $nodeInfo;
                if($nodeInfo['controller']!=""){
                    $authority[$nodeInfo['controller']]=$nodeInfo['nodeType'];
                }
                
                if($nodeInfo['nodePid']==0){
                    array_push($mNodes,['nodeId'=>$nodeInfo['nodeId'],'showType'=>$nodeInfo['showType']]);
                }
            }
            $mNodeResult = &$newAllNodes;
        }else{
            $res->errCode=10001;
            $res->error=getError(10001);
            return $res;
        }
        foreach ($mNodes as $node1) {
            if($node1['showType']==1){
                $menus['node'][$node1['nodeId']]=$mNodeResult[$node1['nodeId']];
                unset($mNodeResult[$node1['nodeId']]);
                foreach ($mNodeResult as $node2) {
                    if($node1['nodeId']==$node2['nodePid'] && $node2['showType']==1){
                        $menus['node'][$node1['nodeId']]['node'][$node2['nodeId']]=$node2;
                        unset($mNodeResult[$node2['nodeId']]);
                        foreach ($mNodeResult as $node3) {
                            if($node2['nodeId']==$node3['nodePid'] && $node3['showType']==1){
                                $menus['node'][$node1['nodeId']]['node'][$node2['nodeId']]['node'][$node3['nodeId']]=$node3;
                                unset($mNodeResult[$node3['nodeId']]);
                                foreach ($mNodeResult as $node4) {
                                    if($node3['nodeId']==$node4['nodePid']  && $node4['showType']==1){
                                        $menus['node'][$node1['nodeId']]['node'][$node2['nodeId']]['node'][$node3['nodeId']]['node'][$node4['nodeId']]=$node4;
                                        unset($mNodeResult[$node2['nodeId']]);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        session('nodeAuth',$authority);
        // print_r($menus);
        $this->Redis->set($refreNode,2,3600);
        $this->Redis->set($nodeName,$menus,3600);
        $res->errCode=10001;
        $res->error=getError(10001);
        $res->data=$menus;
        return $res;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-01-27 14:50:51 
     * @Desc: 获取用户列表 
     */    
    function getUserList($parameter=[]){
        $res=$this->initRes();
        $where=$parameter['where']?$parameter['where']:true;
        $fields=$parameter['fields']?$parameter['fields']:true;
        $orderStr=$parameter['orderStr']?$parameter['orderStr']:null;
        $page=$parameter['page']?$parameter['page']:0;
        $pageNum=$parameter['pageSize']?$parameter['pageSize']:0;
        $groupBy=$parameter['groupBy']?$parameter['groupBy']:null;
        $userList=$this->userDB->getList($where , $fields, $orderStr, $page, $pageNum, $groupBy);
        $count=$this->userDB->countList($where);
        if($userList){
            return ['list'=>$userList,'count'=>$count];
        }
        return false;
    }
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
        $userResult=$this->userDB->getOne(['where'=>$where,'fields'=>'password','noField'=>true]);
        if($userResult){
            $res->errCode=0;
            $res->error=getError(0);
            $res->data=$userResult;
            return $res;
        }
    }
    /** 
     * @Author: vition 
     * @Date: 2018-01-30 00:42:29 
     * @Desc:  
     */    
    function insertUser($userInfo){
        $res=$this->initRes();
        $insertResult=$this->userDB->insert($userInfo);
        if($insertResult){
            $res->errCode=0;
            $res->error=getError(0);
            return $res;
        }
        $res->errCode=111;
        $res->error=getError(111);
        return $res;
    }
}
