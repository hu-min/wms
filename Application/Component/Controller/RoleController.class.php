<?php
namespace Component\Controller;
use Common\Controller\BaseController;

class RoleController extends BaseController{
    protected $roleDB;
    public function _initialize(){
        parent::_initialize();
        $this->roleDB = D('Component/Role');
    }
    /** 
     * @Author: vition 
     * @Date: 2018-02-03 21:30:25 
     * @Desc: 获取角色列表 
     */    
    function getRoleList($parameter=[]){
        $res=$this->initRes();
        $where=$parameter['where']?$parameter['where']:true;
        $fields=$parameter['fields']?$parameter['fields']:true;
        $orderStr=$parameter['orderStr']?$parameter['orderStr']:null;
        $page=$parameter['page']?$parameter['page']:0;
        $pageNum=$parameter['pageSize']?$parameter['pageSize']:0;
        $groupBy=$parameter['groupBy']?$parameter['groupBy']:null;
        $userList=$this->roleDB->getList($where , $fields, $orderStr, $page, $pageNum, $groupBy);
        $count=$this->roleDB->countList($where);
        if($userList){
            return ['list'=>$userList,'count'=>$count];
        }
        return false;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-02-06 22:20:42 
     * @Desc: 添加角色 
     */    
    function inserRole($parameter){
        $res=$this->initRes();
        $insertResult=$this->roleDB->insert($parameter);
        if($insertResult){
            $res->errCode=0;
            $res->error=getError(0);
            return $res;
        }
        $res->errCode=111;
        $res->error=getError(111);
        return $res;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-02-06 22:39:09 
     * @Desc: 修改数据 
     */    
    function updateRole($parameter){
        $res=$this->initRes();
        $insertResult=$this->roleDB->modify($parameter["where"],$parameter["data"]);
        if($insertResult){
            $res->errCode=0;
            $res->error=getError(0);
            return $res;
        }
        $res->errCode=114;
        $res->error=getError(114);
        return $res;
    }
}