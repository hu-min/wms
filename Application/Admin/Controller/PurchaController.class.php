<?php
namespace Admin\Controller;

/** 
 * @Author: vition 
 * @Date: 2018-05-06 09:45:33 
 * @Desc: 采购管理 
 */
class PurchaController extends BaseController{

    public function _initialize() {
        $this->project=A("Project");
        $this->supplier=A("Supplier");
        parent::_initialize();
        $this->basicCom=getComponent('Basic');
        $this->fixExpenCom=getComponent('FixldExpense');
        $this->receivableCom=getComponent('Receivable');
        $this->wouldpayCom=getComponent('Wouldpay');
        $this->purchaCom=getComponent('Purcha');
        $this->payCom=getComponent('Pay');
        $this->InvoiceCom=getComponent('Invoice');
        $this->payGradeType = ["1"=>"A级[高]","2"=>"B级[次]","3"=>"C级[中]","4"=>"D级[低]"];
        $this->invoiceType = ["0"=>"无","1"=>"收据","2"=>"增值税普通","3"=>"增值税专用"];
        $this->payType = ['1'=>'公对公','2'=>'现金付款','3'=>'支票付款'];
        
    }
    /** 
     * @Author: vition 
     * @Date: 2018-07-17 23:12:16 
     * @Desc: 成本录入控制入口 
     */    
    function costInsert(){
        $reqType=I('reqType');
        $this->assign("controlName","cost_insert");
        $this->assign('tableName',$this->purchaCom->tableName());//删除数据的时候需要
        $this->assign('projectArr',$this->project->_getOption("project_id"));
        $this->assign('supplierArr',$this->supplier->getSupType());
        // $this->assign('companyArr',$this->supplier->getSupplier());
        // $this->assign('moduleArr',$this->supplier->getModule());
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function getProjectOne($return=false){
        $id = I("id");
        $parameter=[
            "where"=>["projectId"=>$id],
            "fields" => "projectId,name project_name,FROM_UNIXTIME(project_time,'%Y-%m-%d') project_date,code,leader,leader_name,business,business_name",
            "joins"=>[
                "LEFT JOIN (SELECT userId user_id,userName leader_name FROM v_user) lu ON lu.user_id = leader",
                "LEFT JOIN (SELECT userId user_id,userName business_name FROM v_user) bu ON bu.user_id = business",
            ]
        ];
        $resultData = $this->project->projectCom->getOne($parameter)["list"];
        if($return){
            return $resultData;
        }
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
        $pid = I("pid",'');
        $key = I("key",'');
        $resultData = $this->supplier->getModule($pid,$key);
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
        $roleId = session("roleId");
        
        if($gettype=="Edit"){
            $title = "编辑成本";
            $btnTitle = "保存数据";
            $redisName="cost_insertList";
            $where = ["project_id"=>$id];
            $parameter=[
                'where'=>$where,
                'fields'=>"*,FROM_UNIXTIME(sign_date,'%Y-%m-%d') sign_date,FROM_UNIXTIME(advance_date,'%Y-%m-%d') advance_date,FIND_IN_SET({$roleId},examine) place",
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
            // echo $this->purchaCom->M()->_sql();exit;
            // print_r($resultData);
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
        $roleId = session("roleId");
        $where=[];
        if($this->nodeAuth[CONTROLLER_NAME.'/'.ACTION_NAME]<7){
            $where['user_id'] = session('userId');
        }
        $parameter=[
            'where'=>$where,
            'fields'=>"id,project_id,state status,user_id,COUNT(supplier_com) supr_num,SUM(contract_amount) amount, name,code,business_name,leader_name,FIND_IN_SET({$roleId},examine) place",
            'page'=>$p,
            'pageSize'=>$this->pageSize,
            'orderStr'=>"id DESC",
            'groupBy' => 'project_id',
            "joins"=>[
                "LEFT JOIN(SELECT projectId, name,code,business,leader FROM v_project) p ON p.projectId = project_id",
                "LEFT JOIN (SELECT userId buser_id,userName business_name FROM v_user) bu ON bu.buser_id = p.business",
                "LEFT JOIN (SELECT userId luser_id,userName leader_name FROM v_user) lu ON lu.luser_id = p.leader",
                "LEFT JOIN (SELECT project_id project_sid, CASE WHEN FIND_IN_SET(0,GROUP_CONCAT(status))>0 THEN 0 WHEN FIND_IN_SET(1,GROUP_CONCAT(status))>0 THEN 1 WHEN FIND_IN_SET(2,GROUP_CONCAT(status))>0 THEN 2  WHEN FIND_IN_SET(3,GROUP_CONCAT(status))>0 THEN 3  WHEN FIND_IN_SET(4,GROUP_CONCAT(status))>0 THEN 4 ELSE 0 END state FROM v_purcha GROUP BY project_id) s ON s.project_sid=project_id",
            ],
        ];
        
        $listResult=$this->purchaCom->getList($parameter);
        echo $this->purchaCom->M()->_sql();exit;
        $this->tablePage($listResult,'Purcha/purchaTable/costInsertList',"cost_insertList");
    }
    function manageCostInsertInfo($datas,$reqType=false){
        $reqType = $reqType ? $reqType : I("reqType");
        foreach (["sign_date","advance_date"] as $date) {
            $datas[$date] = strtotime($datas[$date]);
        }
        if($reqType=="cost_insertAdd"){
            $datas['add_time']=time();
            $datas['user_id']=session("userId");
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
        $process = $this->nodeCom->getProcess(I("vtabId"));
        $process_id = $process["processId"];
        if($datas[0]["project_id"]>0){ 
            //存在项目，则第一个审批的人是项目主管,examine需要
            $userRole = $this->userCom->getUserInfo($datas[0]["leader"]);
            $examine = implode(",",array_unique(explode(",",$userRole['roleId'].",".$process["examine"])));
            unset($expInfo['leader']);
        }else{
            $examine = $process["examine"];
        }
        $process_level=$process["place"];
        foreach ($datas as $suprInfo) {
            $dataInfo = $this->manageCostInsertInfo($suprInfo);
            $dataInfo["process_id"] = $process_id;
            $dataInfo["examine"] = $examine;
            $dataInfo['process_level'] = $process_level;
            // print_r($dataInfo);
            unset($dataInfo['leader']);
            if($dataInfo){
                $insertResult=$this->purchaCom->insert($dataInfo);
                
                if($insertResult->errCode==0){
                    $this->ApprLogCom->createApp($this->purchaCom->tableName(),$insertResult->data,session("userId"),"");
                    // $this->wouldpayCom->insert(["cost_id"=>$insertResult->data]);
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
                        $this->ApprLogCom->updateStatus($this->purchaCom->tableName(),$dataInfo["where"]["id"]);
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
        // $this->assign('tableName',"");//删除数据的时候需要
        $this->assign('payType',$this->payType);//
        $this->assign('invoiceType',$this->invoiceType);//
        $this->assign("tableName",$this->purchaCom->tableName());
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }

    function purcha_applyList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $where=[];
        $roleId = session('roleId');
        if($this->nodeAuth[CONTROLLER_NAME.'/'.ACTION_NAME]<7){
            $where["_string"] = "FIND_IN_SET({$roleId},examine) <= process_level AND FIND_IN_SET({$roleId},examine) > 0";
        }
        $parameter=[
            'where'=>$where,
            'fields'=>"*,FIND_IN_SET({$roleId},examine) place",
            'page'=>$p,
            'pageSize'=>$this->pageSize,
            'orderStr'=>"id DESC",
            "joins"=>[
                "LEFT JOIN (SELECT projectId, name project_name,code,business,leader,brand ,project_time project_date,days FROM v_project) p ON p.projectId = project_id",
                "LEFT JOIN (SELECT userId user_id,userName business_name FROM v_user) bu ON bu.user_id = p.business",
                "LEFT JOIN (SELECT userId user_id,userName leader_name FROM v_user) lu ON lu.user_id = p.leader",
                "LEFT JOIN (SELECT companyId cid,company supplier_com_name,type,provinceId,cityId FROM v_supplier_company WHERE status=1) c ON c.cid=supplier_com",
                "LEFT JOIN (SELECT contactId cid,contact supplier_cont_name,phone supplier_cont_phone,email supplier_cont_email FROM v_supplier_contact WHERE status=1) ct ON ct.cid=supplier_cont",
                "LEFT JOIN (SELECT basicId,name type_name FROM v_basic WHERE class='supType') st ON st.basicId=c.type",
                "LEFT JOIN (SELECT basicId,name module_name FROM v_basic WHERE class='module') bm ON bm.basicId=module",
                "LEFT JOIN (SELECT basicId brand_id,name brand_name FROM v_basic WHERE class = 'brand' ) b ON b.brand_id = p.brand",
                "LEFT JOIN (SELECT pid ,province province_name FROM v_province) pr ON pr.pid=c.provinceId",
                "LEFT JOIN (SELECT cid,city city_name,pid FROM v_city) ci ON ci.cid=c.cityId",
            ],
        ];
        
        $listResult=$this->purchaCom->getList($parameter);
        // echo $this->purchaCom->M()->_sql();
        $this->tablePage($listResult,'Purcha/purchaTable/purapplyList',"purapplyList");
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
            $redisName="purapplyList";
            $resultData=$this->purchaCom->redis_one($redisName,"id",$id);
        }
        // $resultData["project_date"] = date("Y-m-d",$resultData["project_time"]);
        foreach (["project_date","sign_date"] as  $date) {
            if(isset($resultData[$date])){
                $resultData[$date] = date("Y-m-d",$resultData[$date]);
            }
        }
        $resultData["tableData"] = [];
        $resultData["tableData"]["suprpay-list"] = ["list"=>$this->payCom->getList(["where"=>["purcha_id"=>$id,"insert_type"=>1],"fields"=>"*,FROM_UNIXTIME(pay_date,'%Y-%m-%d') pay_date"])["list"],"template"=>$this->fetch('Purcha/purchaTable/suprpayLi')];
        $resultData["tableData"]["suprfina-list"] = ["list"=>$this->payCom->getList(["where"=>["purcha_id"=>$id,"insert_type"=>2],"fields"=>"*,FROM_UNIXTIME(pay_date,'%Y-%m-%d') pay_date"])["list"],"template"=>$this->fetch('Purcha/purchaTable/suprfinapayLi')];
        $resultData["tableData"]["invoice-list"] = ["list"=>$this->InvoiceCom->getList(["where"=>["relation_id"=>$id,"relation_type"=>1],"fields"=>"*,FROM_UNIXTIME(invoice_date,'%Y-%m-%d') invoice_date"])["list"],"template"=>$this->fetch('Purcha/purchaTable/invoiceLi')];

        $resultData["end_date"] = date("Y-m-d",strtotime($resultData["project_date"]." +".$resultData["days"]."day"));
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"purchaModal",
        ];
        $this->modalOne($modalPara);
    }
    function purcha_applyEdit(){
        // $data=I("data");
        extract($_POST);
        // $contract_file=I("contract_file");
        // $pay_grade=I("pay_grade");
        // $purcha_id=I("purcha_id");
        $isUpdate = false;
        $dataInfo=["id" => $purcha_id];
        foreach (["contract_file","pay_grade","contract_amount","company","sign_date","remark"] as $key) {
            if(isset($$key) && $$key!=""){
                if($key=="sign_date"){
                    $dataInfo[$key] = strtotime($$key);
                }else{
                    $dataInfo[$key] = $$key;
                }
            }
        }
        if(!isset($data) || !is_array($data)){ 
            $this->ajaxReturn(['errCode'=>114,'error'=>getError(114)]);
        }
        if(count($dataInfo)>1){
            $isUpdate =true;
            $updateResult=$this->purchaCom->update($dataInfo);
            if($updateResult->errCode==0){
                $this->ApprLogCom->updateStatus($this->purchaCom->tableName(),$dataInfo["id"]);
            }
        }
        foreach (["suprpay-list","suprfina-list","invoice-list"] as $itemInfoList) {
            foreach ($data[$itemInfoList] as $key => $itemInfo) {
                // print_r($itemInfo);
                if(in_array($itemInfoList,["suprpay-list","suprfina-list"])){
                    $itemInfo["pay_date"] = strtotime($itemInfo["pay_date"]);
                    $listCom = $this->payCom;
                    
                }else{
                    $itemInfo["invoice_date"] = strtotime($itemInfo["invoice_date"]);
                    $listCom = $this->InvoiceCom;
                }
                if($itemInfo["id"]>0){
                    if($itemInfo){
                        $updateResult=$listCom->update($itemInfo);
                        if($updateResult->errCode==0){
                            $isUpdate =true;
                        }
                    }
                }else{
                    unset($itemInfo["id"]);
                    if($itemInfo){
                        $updateResult=$listCom->insert($itemInfo);
                        if($updateResult->errCode==0){
                            $isUpdate =true;
                        }
                    }
                }
            }
        }
        if($isUpdate){
            $this->ajaxReturn(['errCode'=>0,'error'=>"修改成功"]);
        }
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
    function getSuprpayLiOne(){
        $rows = I("rows");
        $this->assign('projectArr',$this->project->_getOption("project_id"));
        $this->assign('supplierArr',$this->supplier->getSupType());
        $this->assign('companyArr',$this->supplier->getSupplier());
        $this->assign('moduleArr',$this->supplier->getModule());
        $this->assign('rows',$rows);
        $html=$this->fetch('Purcha/purchaTable/suprpayLi');
        $this->ajaxReturn(['html'=>$html]);
    }
    function suprFinapayLiOne(){
        $rows = I("rows");
        $this->assign('projectArr',$this->project->_getOption("project_id"));
        $this->assign('supplierArr',$this->supplier->getSupType());
        $this->assign('companyArr',$this->supplier->getSupplier());
        $this->assign('moduleArr',$this->supplier->getModule());
        $this->assign('rows',$rows);
        $html=$this->fetch('Purcha/purchaTable/suprfinapayLi');
        $this->ajaxReturn(['html'=>$html]);
    }
    function suprInvoiceLiOne(){
        $rows = I("rows");
        $this->assign('rows',$rows);
        $html=$this->fetch('Purcha/purchaTable/invoiceLi');  
        $this->ajaxReturn(['html'=>$html]);
    }
}