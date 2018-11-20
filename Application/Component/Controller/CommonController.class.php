<?php
namespace Component\Controller;
/** 
 * @Author: vition 
 * @Date: 2018-11-17 19:46:52 
 * @Desc: 公共控制 
 */
class CommonController extends \Common\Controller\BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->ABasic = A("Component/Basic");
        $this->AProject = A("Component/Project");
    }
    /** 
     * @Author: vition 
     * @Date: 2018-11-17 19:49:04 
     * @Desc: 获取各类 
     */    
    function get_option($type,$key="",$option=[])
    {
        $roleId = session("roleId");
        $userId = session("userId");
        $where=["status"=>1];
        switch ($type) {
            case 'costClass': case 'brand': case 'execute_sub':  case 'projectType': case 'stage': case 'finance_id': case 'expense_type': case 'module': case 'unit':
            if($type=="execute_sub"){
                $type = "execute";
            }elseif($type == 'finance_id'){
                $type = "bankstock";
            }
            $where["class"]=$type;
            $pid = I("pid");
            if(isset($pid) && $pid >0){
                $where["pId"]=$pid;
            }
            if($key!=""){
                $where["name"]=["LIKE","%{$key}%"];
            }
            $parameter=[
                'where'=>$where,
                'fields'=>"basicId,name",
                'orderStr'=>"basicId DESC",
                'pageSize'=> 99999999,
            ];
            $result=$this->ABasic -> getList($parameter);
            if($result){
                return $result["list"];
            }
            break;
            case 'project':
            if($key!=""){
                $where["name"]=["LIKE","%{$key}%"];
            }
            $parameter=[
                'where'=>$where,
                'fields'=>"projectId,name",
                'orderStr'=>"projectId DESC",
                'pageSize'=> 99999999,
            ];
            $result = $this->AProject ->getList($parameter);
            if($result){
                return $result["list"];
            }
            break;
        }
    }
}