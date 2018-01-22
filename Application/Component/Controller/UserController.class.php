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
     * @Date: 2018-01-14 21:49:32 
     * @Desc: 获取用户信息，除了密码 
     */    
    protected function getUser($parameter=[]){
        $res=$this->initRes();
        if(empty($parameter)){
            $res->errCode=110;
            $res->error=getError(110);
            return $res;
        }
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
        $menus=$this->Redis->get($nodeName);
        if($menus){
            $res->errCode=10001;
            $res->error=getError(10001);
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
                if($nodeInfo['nodePid']==0){
                    array_push($mNodes,$nodeInfo['nodeId']);
                }
            }
            $mNodeResult = &$newAllNodes;
        }else{
            $res->errCode=10001;
            $res->error=getError(10001);
            return $res;
        }
        foreach ($mNodes as $node1) {
            $menus['node'][$node1]=$mNodeResult[$node1];
            unset($mNodeResult[$node1]);
            foreach ($mNodeResult as $node2) {
                if($node1==$node2['nodePid']){
                    $menus['node'][$node1]['node'][$node2['nodeId']]=$node2;
                    unset($mNodeResult[$node2['nodeId']]);
                    foreach ($mNodeResult as $node3) {
                        if($node2['nodeId']==$node3['nodePid']){
                            $menus['node'][$node1]['node'][$node2['nodeId']]['node'][$node3['nodeId']]=$node3;
                            unset($mNodeResult[$node3['nodeId']]);
                            foreach ($mNodeResult as $node4) {
                                if($node3['nodeId']==$node4['nodePid']){
                                    $menus['node'][$node1]['node'][$node2['nodeId']]['node'][$node3['nodeId']]['node'][$node4['nodeId']]=$node4;
                                    unset($mNodeResult[$node2['nodeId']]);
                                }
                            }
                        }
                    }
                }
            } 
        }
        $this->Redis->set($nodeName,$menus,3600);
        $res->errCode=10001;
        $res->error=getError(10001);
        $res->data=$menus;
        return $res;
    }
}
