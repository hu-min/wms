<?php
namespace Component\Controller;
use Common\Controller\BaseController;

class NodeController extends BaseController{
    protected $NodeDB;
    public function _initialize(){
        parent::_initialize();
        $this->NodeDB = D('Component/Node');
    }
    /** 
     * @Author: vition 
     * @Date: 2018-02-03 21:30:25 
     * @Desc: 获取角色列表 
     */    
    function getNodeList($parameter=[]){
        $res=$this->initRes();
        $where=$parameter['where']?$parameter['where']:true;
        $fields=$parameter['fields']?$parameter['fields']:true;
        $orderStr=$parameter['orderStr']?$parameter['orderStr']:null;
        $page=$parameter['page']?$parameter['page']:0;
        $pageNum=$parameter['pageSize']?$parameter['pageSize']:0;
        $groupBy=$parameter['groupBy']?$parameter['groupBy']:null;
        $nodeList=$this->NodeDB->getList($where , $fields, $orderStr, $page, $pageNum, $groupBy);
        $count=$this->NodeDB->countList($where);
        if($nodeList){
            return ['list'=>$nodeList,'count'=>$count];
        }
        return false;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-02-22 22:42:50 
     * @Desc: 获取单独一条节点信息 
     */    
    function getNodeOne($parameter=[]){
        $res=$this->initRes();
        $where=$parameter['where']?$parameter['where']:true;
        $fields=$parameter['fields']?$parameter['fields']:true;
        $orderStr=$parameter['orderStr']?$parameter['orderStr']:null;
        $nodeList=$this->NodeDB->getOne(['where'=>$where]);
        if($nodeList){
            return ['list'=>$nodeList];
        }
        return false;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-02-06 22:20:42 
     * @Desc: 添加角色 
     */    
    function inserNode($parameter){
        $res=$this->initRes();
        $insertResult=$this->NodeDB->insert($parameter);
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
    function updateNode($parameter){
        $res=$this->initRes();
        $insertResult=$this->NodeDB->modify($parameter["where"],$parameter["data"]);
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