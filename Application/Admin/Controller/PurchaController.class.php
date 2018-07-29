<?php
namespace Admin\Controller;

/** 
 * @Author: vition 
 * @Date: 2018-05-06 09:45:33 
 * @Desc: 采购管理 
 */
class PurchaController extends BaseController{

    public function _initialize() {
        parent::_initialize();
        $this->basicCom=getComponent('Basic');
        $this->fixExpenCom=getComponent('FixldExpense');
        $this->receivableCom=getComponent('Receivable');
        $this->wouldpayCom=getComponent('Wouldpay');
        $this->purchaCom=getComponent('Purcha');
        $this->payGradeType = ["1"=>"A级[高]","2"=>"B级[次]","3"=>"C级[中]","4"=>"D级[低]"];
        $this->invoiceType = ["0"=>"无","1"=>"收据","2"=>"增值税普通","3"=>"增值税专用"];
        $this->payType = ['1'=>'公对公','2'=>'现金付款','2'=>'支票付款'];
        $this->project=A("Project");
        $this->supplier=A("Supplier");
    }
    /** 
     * @Author: vition 
     * @Date: 2018-07-17 23:12:16 
     * @Desc: 成本录入控制入口 
     */    
    function costInsert(){
        $reqType=I('reqType');
        $this->assign("controlName","cost_insert");
        $this->assign('dbName',"");//删除数据的时候需要
        $this->assign('projectArr',$this->project->_getOption("project_id"));
        $this->assign('supplierArr',$this->supplier->getSupType());
        $this->assign('companyArr',$this->supplier->getSupplier());
        $this->assign('moduleArr',$this->supplier->getModule());
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function getProjectOne(){
        $id = I("id");
        $parameter=[
            "where"=>["projectId"=>$id],
            "fields" => "projectId,code,leader,leader_name,business,business_name",
            "joins"=>[
                "LEFT JOIN (SELECT userId user_id,userName leader_name FROM v_user) lu ON lu.user_id = leader",
                "LEFT JOIN (SELECT userId user_id,userName business_name FROM v_user) bu ON bu.user_id = business",
            ]
        ];
        $resultData = $this->project->projectCom->getOne($parameter)["list"];
        $this->ajaxReturn(["data"=>$resultData]);
    }
    function getSuprtype(){
        $key = I("key",'');
        $resultData =$this->supplier->getSupType($key);
        $this->ajaxReturn(["data"=>$resultData]);
    }
    function getSuprComList(){
        $key = I("key",'');
        $type = I("pid",0);
        $resultData = $this->supplier->getSupplier($key,$type);
        $this->ajaxReturn(["data"=>$resultData]);
    }
    function getSuprContList(){
        $key = I("key",'');
        $companyId = I("pid",0);
        $resultData = $this->supplier->getSuprCont($key,$companyId);
        $this->ajaxReturn(["data"=>$resultData]);
    }
    function getModuleList(){
        $key = I("key",'');
        $resultData = $this->supplier->getModule($key);
        $this->ajaxReturn(["data"=>$resultData]);
    }
    function getSuprLiOne(){
        $rows = I("rows");
        $this->assign('projectArr',$this->project->_getOption("project_id"));
        $this->assign('supplierArr',$this->supplier->getSupType());
        $this->assign('companyArr',$this->supplier->getSupplier());
        $this->assign('moduleArr',$this->supplier->getModule());
        $this->assign('rows',$rows);
        $html=$this->fetch('Purcha/purchaTable/suprLi');
        $this->ajaxReturn(['html'=>$html]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-07-17 23:41:10 
     * @Desc: 成本录入新增编辑控制 
     */    
    function cost_insert_modalOne(){
        $title = "成本录入";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        
        if($gettype=="Edit"){
            $title = "编辑成本";
            $btnTitle = "保存数据";
            $redisName="cost_insertList";
            $where = ["project_id"=>$id];
            $parameter=[
                'where'=>$where,
                'fields'=>"*,FROM_UNIXTIME(sign_date,'%Y-%m-%d') sign_date,FROM_UNIXTIME(advance_date,'%Y-%m-%d') advance_date",
                'page'=>$p,
                'pageSize'=>$this->pageSize,
                'orderStr'=>"id DESC",
                "joins"=>[
                    "LEFT JOIN(SELECT projectId, name,code,business,leader FROM v_project) p ON p.projectId = project_id",
                    "LEFT JOIN (SELECT userId user_id,userName business_name FROM v_user) bu ON bu.user_id = p.business",
                    "LEFT JOIN (SELECT userId user_id,userName leader_name FROM v_user) lu ON lu.user_id = p.leader",
                    "LEFT JOIN (SELECT companyId cid,company supplier_com_name,type,provinceId,cityId FROM v_supplier_company WHERE status=1) c ON c.cid=supplier_com",
                    "LEFT JOIN (SELECT contactId cid,contact supplier_cont_name FROM v_supplier_contact WHERE status=1) ct ON ct.cid=supplier_cont",
                    "LEFT JOIN (SELECT pid ,province province_name FROM v_province) pr ON pr.pid=c.provinceId",
                    "LEFT JOIN (SELECT cid,city city_name,pid FROM v_city) ci ON ci.cid=c.cityId",
                    "LEFT JOIN (SELECT basicId,name module_name FROM v_basic WHERE class='module') m ON m.basicId=module",
                    "LEFT JOIN (SELECT basicId,name type_name FROM v_basic WHERE class='supType') st ON st.basicId=c.type",
                ],
            ];
            $resultData=$this->purchaCom->getList($parameter);
            $resultData["template"] = $this->fetch('Purcha/purchaTable/suprLi');
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"costModal",
        ];
        $this->modalOne($modalPara);
    }
    function cost_insertList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $where=[];
        $parameter=[
            'where'=>$where,
            'fields'=>"project_id,COUNT(supplier_com) supr_num,SUM(contract_amount) amount, name,code,business_name,leader_name",
            'page'=>$p,
            'pageSize'=>$this->pageSize,
            'orderStr'=>"id DESC",
            'groupBy' => 'project_id',
            "joins"=>[
                "LEFT JOIN(SELECT projectId, name,code,business,leader FROM v_project) p ON p.projectId = project_id",
                "LEFT JOIN (SELECT userId user_id,userName business_name FROM v_user) bu ON bu.user_id = p.business",
                "LEFT JOIN (SELECT userId user_id,userName leader_name FROM v_user) lu ON lu.user_id = p.leader",
            ],
        ];
        
        $listResult=$this->purchaCom->getList($parameter);
        $this->tablePage($listResult,'Purcha/purchaTable/costInsertList',"sup_companyList");
    }
    function manageCostInsertInfo($datas,$reqType=false){
        $reqType = $reqType ? $reqType : I("reqType");
        foreach (["sign_date","advance_date"] as $date) {
            $datas[$date] = strtotime($datas[$date]);
        }
        if($reqType=="cost_insertAdd"){
            $datas['add_time']=time();
            unset($datas['id']);
            return $datas;
        }else if($reqType=="cost_insertEdit"){
            $where=["id"=>$datas['id']];
            $data=[];
            $data['updateTime']=time();
            foreach (["project_id","supplier_com","supplier_cont","sign_date","contract_amount","contract_file","offer_file","advance_date","remark","module"] as $key) {
                if(isset($datas[$key])){
                    $data[$key] = $datas[$key];
                } 
            }
            if(isset($datas['status'])){
                $parameter=[
                    'where'=>["id"=>$datas['id']],
                ];
                $result=$this->purchaCom->getList($parameter,true);
                $data = $this->status_update($result,$datas["status"],$data);
            }
            return ["where"=>$where,"data"=>$data];
        }
        return "";
    }
    function cost_insertAdd(){
        $datas=I("data");
        $isInsert =false;
        foreach ($datas as $suprInfo) {
            $dataInfo = $this->manageCostInsertInfo($suprInfo);
            if($dataInfo){
                $insertResult=$this->purchaCom->insert($dataInfo);
                if($insertResult->errCode==0){
                    $this->wouldpayCom->insert(["cost_id"=>$insertResult->data]);
                    $isInsert =true;
                }
            }
        }
        if($isInsert){
            $this->ajaxReturn(['errCode'=>0,'error'=>"添加成功"]);
        }
        $this->ajaxReturn(['errCode'=>$insertResult->errCode,'error'=>$insertResult->error]);
    }
    function cost_insertEdit(){
        $datas=I("data");
        $isUpdate =false;
        foreach ($datas as $suprInfo) {
            
            if($suprInfo["id"]>0){
                $dataInfo = $this->manageCostInsertInfo($suprInfo);
                if($dataInfo){
                    $updateResult=$this->purchaCom->update($dataInfo);
                    if($updateResult->errCode==0){
                        $isUpdate =true;
                    }
                }
            }else{
                $dataInfo = $this->manageCostInsertInfo($suprInfo,"cost_insertAdd");
                if($dataInfo){
                    $insertResult=$this->purchaCom->insert($dataInfo);
                    if($insertResult->errCode==0){
                        $this->wouldpayCom->insert(["cost_id"=>$insertResult->data]);
                        $isUpdate =true;
                    }
                }
            }
            
        }      
        if($isUpdate){
            $this->ajaxReturn(['errCode'=>0,'error'=>"修改成功"]);
        }
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }

    /** 
     * @Author: vition 
     * @Date: 2018-07-17 23:45:33 
     * @Desc: 项目采购成本审批 
     */    
    function purchaApply(){
        $reqType=I('reqType');
        $this->assign("controlName","purcha_apply");
        $this->assign('dbName',"");//删除数据的时候需要
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function purcha_apply_modalOne(){
        $title = "采购成本审批";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        
        if($gettype=="Edit"){
            $title = "采购成本审批";
            $btnTitle = "保存数据";
            $redisName="purcha_applyList";
            // $resultData=$this->fixExpenCom->redis_one($redisName,"id",$id);
            $resultData=[];
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"purchaModal",
        ];
        $this->modalOne($modalPara);
    }
}