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
        $this->AProjectCost = A('Component/ProjectCost');
        $this->AUser = A("Component/User");
        $this->ACustomer = A('Component/Customer');
        $this->ASupplier = A('Component/Supplier');
        $this->APurcha = A('Component/Purcha');
        $this->AField = A('Component/Field');
        $this->AMoneyAcc = A('Component/MoneyAccount');
        $this->AProcess = A('Component/Process');
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
            //基础数据
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
            //项目id
            case 'project': case 'project_id':
                $where["project_id"]=0;
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
            //成本项目，必须成本已审批
            case 'cost_project':
                $where['status'] = 1;
                if($key!=""){
                    $where["name"]=["LIKE","%{$key}%"];
                }
                $parameter=[
                    'where'=>$where,
                    'fields'=>"project_id projectId,name",
                    'orderStr'=>"add_time DESC",
                    'groupBy'=>"project_id",
                    'pageSize'=> 99999999,
                    'joins' => [
                        "RIGHT JOIN (SELECT projectId,name FROM v_project) p ON p.projectId = project_id",
                    ],
                ];
                $result = $this->AProjectCost->getList($parameter);
                if($result){
                    return $result["list"];
                }
                break;
            //成本和员工关联的项目
            case 'cost_relation_project':
                $where['status'] = 1;
                if($key!=""){
                    $where["name"]=["LIKE","%{$key}%"];
                }
                $parameter=[
                    'where'=>$where,
                    'fields'=>"project_id projectId,name",
                    'orderStr'=>"add_time DESC",
                    'groupBy'=>"project_id",
                    'pageSize'=> 99999999,
                    'joins' => [
                        "RIGHT JOIN (SELECT projectId,name FROM v_project WHERE (FIND_IN_SET({$roleId},examine) <= process_level AND FIND_IN_SET({$roleId},examine) > 0) OR (user_id = {$userId}) OR (( FIND_IN_SET({$userId},business) OR FIND_IN_SET({$userId},leader) OR FIND_IN_SET({$userId},earlier_user) OR FIND_IN_SET({$userId},scene_user) OR (user_id = {$userId})) AND status =1 ) ) p ON p.projectId = project_id",
                    ],
                ];
                $result = $this->AProjectCost->getList($parameter);
                $this->log($this->AProjectCost->M()->_sql());
                if($result){
                    return $result["list"];
                }
                break;
            //员工关联的项目
            case 'relation_project':
                $where["project_id"]=0;
                $where["_string"] = "(FIND_IN_SET({$roleId},examine) <= process_level AND FIND_IN_SET({$roleId},examine) > 0) OR (user_id = {$userId}) OR (( FIND_IN_SET({$userId},business) OR FIND_IN_SET({$userId},leader) OR FIND_IN_SET({$userId},earlier_user) OR FIND_IN_SET({$userId},scene_user) OR (user_id = {$userId})) AND status =1 )";
                $key_type = "name";
                if($option['key_type'] == "code"){
                    $key_type = "code";
                }
                if($key!=""){
                    $where[$key_type]=["LIKE","%{$key}%"];
                }
                $parameter=[
                    'where'=>$where,
                    'fields'=>"projectId,name",
                    'orderStr'=>"addTime DESC",
                    'pageSize'=> 99999999,
                ];
                $result=$this->AProject->getList($parameter);
                if($result){
                    return $result["list"];
                }
            break;
            //客户列表
            case 'customer_com':
                if ($key!=""){
                    $where["company"]=["LIKE","%{$key}%"];
                }
                $parameter=[
                    'where'=>$where,
                    'fields'=>'companyId,company',
                    'orderStr'=>"companyId DESC",
                    'pageSize'=> 99999999,
                ];
                $result = $this->ACustomer->getCompanyList($parameter);
                if($result){
                    return $result["list"];
                }
                break;
            //客户公司联系人列表
            case 'customer_cont':
                $where["companyId"]=I("pid");
                if ($key!=""){
                    $where["contact"]=["LIKE","%{$key}%"];
                }
                $parameter=[
                    'where'=>$where,
                    'fields'=>'contactId,contact',
                    'orderStr'=>"contactId DESC",
                    'pageSize'=> 99999999,
                ];
                $result = $this->ACustomer->getCustomerList($parameter);
                if($result){
                    return $result["list"];
                }
                break;
            //城市列表
            case 'city': case 'cityId':
                $result = $this->ABasic->get_citys(I("pid"));
                if($result){
                    return $result;
                }
                break;
            //用户
            case 'user': case 'create_user': case 'business': case 'leader': case 'earlier_user': case 'scene_user': case 'user_id' : case 'to_user' :
                if($key!=""){
                    $where["userName"]=["LIKE","%{$key}%"];
                }
                $parameter=[
                    'where'=>$where,
                    'fields'=>"userId,userName,roleName",
                    'orderStr'=>"userId DESC",
                    'pageSize'=> 99999999,
                    'joins'=>'LEFT JOIN (SELECT roleId rid,roleName FROM v_role ) r ON r.rid = roleId',
                ];
                $result=$this->AUser->getList($parameter);
                if($result){
                    if($type=="to_user"){
                        foreach ($result["list"] as $key => $value) {
                            $result["list"][$key]['userName'] =  "【{$value['roleName']}】{$value['userName']}";
                        }
                    }
                    return $result["list"];
                }
                break;
            //供应商列表
            case 'supplier_com':
                if ($key!=""){
                    $where["company"]=["LIKE","%{$key}%"];
                }
                $parameter=[
                    'where'=>$where,
                    'fields'=>'companyId,company,supr_type,typename,provinceId,province,cityId,city',
                    'orderStr'=>"companyId DESC",
                    'pageSize'=> 99999999,
                    'joins'=>[
                        "LEFT JOIN (SELECT basicId,name typename FROM v_basic WHERE class='supType') b ON b.basicId=supr_type",
                        "LEFT JOIN (SELECT pid ,province FROM v_province ) p ON p.pid = provinceId",
                        "LEFT JOIN (SELECT cid ctid ,city,pid cpid FROM v_city ) ct ON ct.ctid = cityId AND ct.cpid = provinceId",
                    ]
                ];
                $result = $this->ASupplier->getCompanyList($parameter);
                if($result){
                    return $result["list"];
                }
                break;
            //供应商联系人列表
            case 'supplier_cont':
                $where["companyId"]=I("pid");
                if ($key!=""){
                    $where["contact"]=["LIKE","%{$key}%"];
                }
                $parameter=[
                    'where'=>$where,
                    'fields'=>'contactId,contact',
                    'orderStr'=>"contactId DESC",
                    'pageSize'=> 99999999,
                ];
                $result = $this->ASupplier->getSuprContList($parameter);
                if($result){
                    return $result["list"];
                }
                break;
            //承接模块下的供应商列表
            case 'module_supplier':
                $pid=I("pid");
                if($pid){
                    $where["_string"] = "FIND_IN_SET({$pid},module)";
                }
                if ($key!=""){
                    $where["company"]=["LIKE","%{$key}%"];
                }
                $parameter=[
                    'where'=>$where,
                    'fields'=>'companyId,company',
                    'orderStr'=>"companyId DESC",
                    'pageSize'=> 99999999,
                ];
                $result = $this->ASupplier->getCompanyList($parameter);
                if($result){
                    return $result["list"];
                }
                break;
            //未知，哈哈
            case 'cost_id':
                $where['status'] = 1;
                if ($key!=""){
                    $map["project_name"]=["LIKE","%{$key}%"];
                    $map["supplier_com_name"]=["LIKE","%{$key}%"];
                    $map['_logic'] = 'or';
                    $where['_complex'] = $map;
                }
                $parameter=[
                    'where'=>$where,
                    'fields'=>'id,project_name,supplier_com,supplier_com_name',
                    'orderStr'=>"id DESC",
                    'pageSize'=> 99999999,
                    "joins"=>[
                        "LEFT JOIN (SELECT projectId,name project_name FROM v_project ) p ON p.projectId = project_id ",
                        "LEFT JOIN (SELECT companyId company_id,company supplier_com_name FROM v_supplier_company ) c ON c.company_id = supplier_com",
                    ],
                ];
                $result = $this->APurcha->getList($parameter);
                if($result){
                    $costs = [];
                    foreach ($result["list"] as $key => $item) {
                        $costs[$key] = ["id"=>$item["id"],"name"=>$item['project_name']."-".$item['supplier_com_name']];
                    }
                    return $costs;
                }
                break;
            //场地列表
            case 'field':
                $where['status'] = 1;
                $pid = I("pid");
                if(isset($pid) && $pid >0){
                    $where["city"]=$pid;
                }
                if ($key!=""){
                    $map["name"]=["LIKE","%{$key}%"];
                    $map["remark"]=["LIKE","%{$key}%"];
                    $map['_logic'] = 'or';
                    $where['_complex'] = $map;
                }
                $parameter=[
                    'where'=>$where,
                    'fields'=>'id,name',
                    'orderStr'=>"id DESC",
                    'pageSize'=> 99999999,
                ];
                $result = $this->AField->getList($parameter);
                if($result){
                    return $result["list"];
                }                               
                break;
            //公司财务账户列表
            case 'fin_account':
                if ($key!=""){
                    $where["account"]=["LIKE","%{$key}%"];
                }
                $parameter=[
                    'where'=>$where,
                    'fields'=>'id,account',
                    'orderStr'=>"id DESC",
                    'pageSize'=> 99999999,
                ];
                $result = $this->AMoneyAcc->getList($parameter);
                if($result){
                    return $result["list"];
                }                     
                break;
            //获取审批流程
            case 'get_processes':
                if ($key!=""){
                    $where["account"]=["LIKE","%{$key}%"];
                }
                $parameter=[
                    'fields'=>"processId,processName",
                    'where'=>$where,
                    'pageSize'=>99999999,
                    'orderStr'=>"processId DESC",
                ];
                $result = $this->AProcess->getList($parameter);
                if($result){
                    return $result["list"];
                }               
                break;
            default:
                return [];
                break;
        }
    }
}