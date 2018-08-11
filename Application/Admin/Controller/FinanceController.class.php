<?php
namespace Admin\Controller;

/** 
 * @Author: vition 
 * @Date: 2018-05-06 09:45:33 
 * @Desc: 财务管理 
 */
class FinanceController extends BaseController{

    public function _initialize() {
        parent::_initialize();
        $this->basicCom=getComponent('Basic');
        $this->fixExpenCom=getComponent('FixldExpense');
        $this->receivableCom=getComponent('Receivable');
        $this->wouldpayCom=getComponent('Wouldpay');
        $this->purchaCom=getComponent('Purcha');
        $this->payCom=getComponent('Pay');
        $this->payGradeType = ["1"=>"A级[高]","2"=>"B级[次]","3"=>"C级[中]","4"=>"D级[低]"];
        $this->invoiceType = ["0"=>"无","1"=>"收据","2"=>"增值税普通","3"=>"增值税专用"];
        $this->payType = ['1'=>'公对公','2'=>'现金付款','3'=>'支票付款'];
        $this->payStatus = ['0'=>'未支付','1'=>'已支付','2'=>'支付无效'];
    }
    function stockControl(){
        $reqType=I('reqType');
        $this->assign('dbName',"Basic");//删除数据的时候需要
        if($reqType){
            $this->$reqType();
        }else{
            $bankstock=$this->basicCom->get_class_data("bankstock");
            $cashstock=$this->basicCom->get_class_data("cashstock");
            $this->assign("bankstockList",$bankstock);
            $this->assign("cashstockList",$cashstock);
            $this->assign("bankstockCount",array_sum(array_column($bankstock,"alias")));
            $this->assign("cashstockCount",array_sum(array_column($cashstock,"alias")));
            $this->assign("banksLogList",$this->LogCom->getLogList(['where'=>["class"=>"bankstock"],'pageSize'=>5,'orderStr'=>"addTime DESC",])["list"]);
            $this->assign("cashsLogList",$this->LogCom->getLogList(['where'=>["class"=>"cashstock"],'pageSize'=>5,'orderStr'=>"addTime DESC",])["list"]);
            $this->returnHtml();
        }
    }
    function bankstockEdit(){
        $this->setStock("bankstock");
    }
    function cashstockEdit(){
        $this->setStock("cashstock");
    }
    function stockDelete(){
        $delResult = $this->basicCom->del(["basicId"=>I("basicId")]);
        if($delResult){
            $this->ajaxReturn(['errCode'=>0,'error'=>"更新成功"]);
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>"更新失败"]);
    }
    protected function setStock($stockName){
        $datas=I("data");
        $remark=I("remark");
        $result=false;
        $stockRes=$this->basicCom->get_class_data($stockName);
        $stockList=[];
        foreach ($stockRes as $stock) {
            $stockList[$stock["basicId"]]=["name"=>$stock["name"],"alias"=>$stock["alias"],"addTime"=>time()];
        }
        $logInfo=["userId"=>session("userId"),"userName"=>session("userName"),"class"=>$stockName,"addTime"=>time()];
        foreach ($datas as $basicId => $value) {
            $res=false;
            if(isset($stockList[$basicId])){
                if($stockList[$basicId]["alias"] != $value['val'] || $stockList[$basicId]["name"]!=$value['company']){
                    $Info=["basicId"=>$basicId,"name"=>$value['company'],"alias"=>$value['val']];
                    // print_r($Info);
                    $logInfo["describe"]="";
                    $res= $this->basicCom->updateBasic($Info);
                    if($stockList[$basicId]["name"] != $value['company']){
                        $logInfo["describe"].="子公司名 【".$stockList[$basicId]["name"]."】修改为：【".$value['company']."】;原因：".$remark;
                    }
                    if($stockList[$basicId]["alias"] != $value['val']){
                        $logInfo["describe"].="【{$value['company']}】值【".$stockList[$basicId]["alias"]."】修改为：【".$value['val']."】;原因：".$remark;
                    }
                    
                }
            }else{
                $Info=[
                    "class"=>$stockName,
                    "name"=>$value['company'],
                    "alias"=>$value['val'],
                ];
                $res= $this->basicCom->insertBasic($Info);
                $logInfo["describe"]="新增{$value['company']}，值为:{$value['val']};原因：".$remark;
            }
            if ($res){
                $this->LogCom->insert($logInfo);
                $result=true;
            }
        }
        if($result){
            $this->basicCom->clear_cache($stockName);
            $logHtml="";
            foreach ($this->LogCom->getLogList(['where'=>["class"=>$stockName],'pageSize'=>5,'orderStr'=>"addTime DESC",])["list"] as $log) {
                $logHtml.="<tr><td>".date("Y-m-m H:i:s",$log["addTime"])."</td><td>{$log['userName']}</td><td>{$log['describe']}</td></tr>";
            }

            $this->ajaxReturn(['errCode'=>0,'error'=>"更新成功","stock"=>$stockName,"html"=>$logHtml]);
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>"更新失败"]);
    }

     //固定费用支出开始
    /** 
     * @Author: vition 
     * @Date: 2018-06-05 23:05:28 
     * @Desc: 固定费用之支出 
     */    
    function fix_expenseControl(){
        $reqType=I('reqType');
        $this->assign("controlName","fix_expense");
        $this->assign('dbName',"FixldExpense");//删除数据的时候需要
        $parameter=[
            'where'=>["class"=>"expenClas"],
            'page'=>1,
            'pageSize'=>99999,
            'orderStr'=>"basicId DESC",
        ];
        $basicResult=$this->basicCom->getList($parameter);
        $this->assign("expenClasArr",$basicResult["list"]);//固定支出类别
        $parameter=[
            'where'=>["class"=>["IN",["bankstock","cashstock"]]],
            "fields"=>"basicId,name,CASE class WHEN 'bankstock' THEN '银行库存' ELSE '现金库存' END accName",
            'page'=>1,
            'pageSize'=>99999,
            'orderStr'=>"basicId DESC",
        ];
        $stockResult=$this->basicCom->getList($parameter);
        $this->assign("companyAccount",$stockResult["list"]);//账户类型
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function fix_expense_modalOne(){
        $title = "新建固定支出";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        
        if($gettype=="Edit"){
            $title = "编辑固定支出";
            $btnTitle = "保存数据";
            $redisName="fix_expenseList";
            $resultData=$this->fixExpenCom->redis_one($redisName,"id",$id);
        }
        $resultData["startDate"] = date("Y-m-d",$resultData["startDate"] );
        $resultData["endDate"] = date("Y-m-d",$resultData["endDate"] );
        $resultData["payTime"] = date("Y-m-d",$resultData["payTime"] );
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"fix_expenseModal",
        ];
        $this->modalOne($modalPara);
    }
    function fix_expenseList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $where=[];
        if($data['expenClas']){
            $where['expenClas']=$data['expenClas'];
        }
        $parameter=[
            'fields'=>"`id`,`expenClas`,expenClass,`finanAccount`,finanAccs,`toObject`,`content`,`startDate`,`endDate`,`fee`,`payment`,noPayment,payTime,remark,addTime,status,processLevel,author,examine",
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$this->pageSize,
            'orderStr'=>"id DESC",
            "joins"=>["LEFT JOIN (SELECT basicId , `name` expenClass FROM v_basic WHERE status=1 AND class='expenClas' ) bt ON bt.basicId=expenClas","LEFT JOIN (SELECT basicId , `name` finanAccs FROM v_basic WHERE class in ('bankstock','cashstock') ) bf ON bf.basicId=finanAccount"],
        ];
        
        $listResult=$this->fixExpenCom->getList($parameter);
        $this->tablePage($listResult,'Finance/financeTable/fix_expenseList',"fix_expenseList");
    }
    function manageFixExpenInfo(){
        $reqType=I("reqType");
        $datas=I("data");
        if(isset($datas['startDate'])){
            $datas['startDate']=strtotime($datas['startDate']);
        }
        if(isset($datas['endDate'])){
            $datas['endDate']=strtotime($datas['endDate']);
        }
        if(isset($datas['payTime'])){
            $datas['payTime']=strtotime($datas['payTime']);
        }
        if($reqType=="fix_expenseAdd"){
            $datas['addTime']=time();
            $datas['author']=session("userId");
            unset($datas['id']);
            return $datas;
        }else if($reqType=="fix_expenseEdit"){
            $where=["id"=>$datas['id']];
            $data=[];
            $data['updateTime']=time();
            if(isset($datas['expenClas'])){
                $data['expenClas']=$datas['expenClas'];
            }
            if(isset($datas['finanAccount'])){
                $data['finanAccount']=$datas['finanAccount'];
            }
            if(isset($datas['toObject'])){
                $data['toObject']=$datas['toObject'];
            }
            if(isset($datas['content'])){
                $data['content']=$datas['content'];
            }
            if(isset($datas['startDate'])){
                $data['startDate']= $datas['startDate'];
            }
            if(isset($datas['endDate'])){
                $data['endDate']=$datas['endDate'];
            }
            if(isset($datas['fee'])){
                $data['fee']=$datas['fee'];
            }
            if(isset($datas['payment'])){
                $data['payment']=$datas['payment'];
            }
            if(isset($datas['noPayment'])){
                $data['noPayment']=$datas['noPayment'];
            }
            if(isset($datas['payTime'])){
                $data['payTime']=$datas['payTime'];
            }
            if(isset($datas['remark'])){
                $data['remark']=$datas['remark'];
            }
            if(isset($datas['status'])){
                $parameter=[
                    'where'=>["id"=>$id],
                ];
                $result=$this->fixExpenCom->getList($parameter,true);
                $data = $this->status_update($result,$datas["status"],$data);
            }
            return ["where"=>$where,"data"=>$data];
        }
        return "";
    }
    function fix_expenseAdd(){
        $Info=$this->manageFixExpenInfo();
        if($Info){
            $insertResult=$this->fixExpenCom->insert($Info);
            if($insertResult && $insertResult->errCode==0){
                $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
            }
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    } 
    function fix_expenseEdit(){
        $Info=$this->manageFixExpenInfo();
        $updateResult=$this->fixExpenCom->update($Info);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
    //固定费用支出结束
    function project_one(){
        
    }
    //应收款项开始
    function receivableControl(){
        $reqType=I('reqType');
        $this->assign('dbName',"Receivable");//删除数据的时候需要
        $this->assign("controlName","receivable");//名字对应cust_company_modalOne，和cust_companyModal.html
        $this->assign('projectArr',A("Project")->_getOption("project_id"));
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function receivable_modalOne(){
        $title = "新建收款";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        
        if($gettype=="Edit"){
            $title = "编辑收款";
            $btnTitle = "保存数据";
            $redisName="receivableList";
            $resultData=$this->receivableCom->redis_one($redisName,"id",$id);
            foreach (['project_time','advance_date','contract_date','next_date','pay_date','surplus_date'] as  $date) {
                if(isset($resultData[$date])){
                    $resultData[$date] = date ("Y-m-d",$resultData[$date]);
                }
            }
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"receivableModal",
        ];
        $this->modalOne($modalPara);
    }
    function manageReceivableInfo(){
        $reqType=I("reqType");
        $datas=I("data");

        $datas['project_id'] = $datas['project_id'] ? $datas['project_id'] : 0;
        if(isset($datas['startDate'])){
            $datas['startDate']=strtotime($datas['startDate']);
        }
        foreach (['advance_date','contract_date','next_date','pay_date','surplus_date'] as  $date) {
            if(isset($datas[$date])){
                $datas[$date]=strtotime($datas[$date]);
            }
        }

        if($reqType=="receivableAdd"){
            $datas['add_time']=time();
            $datas['time']=strtotime($datas['time']);
            $datas['author']=session('userId');
            $datas['processLevel']=$this->processAuth["level"];
            unset($datas['id']);
            return $datas;
        }else if($reqType=="receivableEdit"){
            $where=["id"=>$datas['id']];
            $data=[];

            $data['updateUser']=session('userId');
            foreach (['advance_date','advance','contract_date','id','next_date','pay_amount','pay_date','project_id','remark','status','surplus_date','surplus'] as  $key) {
                if(isset($datas[$key])){
                    $data[$key]=$datas[$key];
                }
            }
            if(isset($datas['status'])){
                $parameter=[
                    'where'=>["id"=>$datas['id']],
                ];
                $result=$this->receivableCom->getOne($parameter);
                $data = $this->status_update($result,$datas["status"],$data);
            }
            $data['upate_time']=time();
            
            return ["where"=>$where,"data"=>$data];
        }
        return "";
    }
    function receivableList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $where=[];

        $parameter=[
            'fields'=>"*",
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$this->pageSize,
            'orderStr'=>"add_time DESC",
            "joins"=>[
                "LEFT JOIN (SELECT projectId,session_all,code,name,project_time,brand,customer_com,business,type,amount FROM v_project ) p ON p.projectId = project_id ",
                "LEFT JOIN (SELECT basicId brand_id,name brand_name FROM v_basic WHERE class = 'brand' ) b ON b.brand_id = p.brand",
                "LEFT JOIN (SELECT companyId company_id,company customer_com_name FROM v_customer_company ) c ON c.company_id = p.customer_com",
                "LEFT JOIN (SELECT userId user_id,userName business_name FROM v_user) bu ON bu.user_id = p.business",
                "LEFT JOIN (SELECT basicId type_id,name type_name FROM v_basic WHERE class = 'projectType' ) t ON t.type_id = p.type",
            ],
        ];
        
        $listResult=$this->receivableCom->getList($parameter);
        $this->tablePage($listResult,'Finance/financeTable/receivableList',"receivableList");
    }
    function receivableAdd(){
        $info=$this->manageReceivableInfo();
        if($info){
            $insertResult=$this->receivableCom->insertReceivable($info);
            if($insertResult && $insertResult->errCode==0){
                $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
            }
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-08 20:58:39 
     * @Desc: 修改 
     */    
    function receivableEdit(){
        $info=$this->manageReceivableInfo();
        $updateResult=$this->receivableCom->updateReceivable($info);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
    //应收款项结束

    //应付款项开始wouldpayControl
    function wouldpayControl(){
       
        $this->assign("payGradeType",$this->payGradeType);
        $this->assign("invoiceType",$this->invoiceType);
        $this->assign("payType",$this->payType);
        $this->assign("payStatus",$this->payStatus);
        $supplier = A("Supplier");
        // print_r($supplier->getSupplier());
        $this->assign("supComArr",A("Project")->_getOption("supplier_com"));
        $reqType=I('reqType');
        $this->assign('dbName',"Wouldpay");//删除数据的时候需要
        $this->assign("controlName","wouldpay");//名字对应cust_company_modalOne，和cust_companyModal.html
        $this->assign('costArr',A("Project")->_getOption("cost_id"));
        $this->assign('financeArr',A("Project")->_getOption("finance_id"));
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function wouldpay_modalOne(){
        $title = "新建付款";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        
        if($gettype=="Edit"){
            $title = "编辑付款";
            $btnTitle = "保存数据";
            $redisName="wouldpayList";
            $resultData=$this->wouldpayCom->redis_one($redisName,"id",$id);
            foreach (['project_time','late_pay_date','advance_date','next_date','pay_date'] as  $date) {
                if(isset($resultData[$date]) && $resultData[$date] > 0){
                    $resultData[$date] = date ("Y-m-d",$resultData[$date]);
                }
            }
        }
        $resultData["tableData"] = [];
        $payList = $this->payCom->getList(["where"=>["purcha_id"=>$id,"insert_type"=>2],"fields"=>"*,FROM_UNIXTIME(pay_date,'%Y-%m-%d') pay_date"])["list"];
        foreach ($payList as $key => $value) {
            $payList[$key]["pay_status"] = $this->payStatus[$value["pay_status"]];
            # code...
        }
        $resultData["tableData"]["suprfina-list"] = ["list"=>$payList,"template"=>$this->fetch('Purcha/purchaTable/suprfinapayLi')];

        $resultData["end_date"] = date("Y-m-d",strtotime($resultData["project_date"]." +".$resultData["days"]."day"));
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            // "template"=>"wouldpayModal",
            "template"=>"purchaModal",
        ];
        $this->modalOne($modalPara);
    }
    function manageWouldpayInfo(){
        $reqType=I("reqType");
        $datas=I("data");

        // $datas['project_id'] = $datas['project_id'] ? $datas['project_id'] : 0;
        foreach (['late_pay_date','advance_date','next_date','pay_date'] as  $date) {
            if(isset($datas[$date])){
                $datas[$date]=strtotime($datas[$date]);
            }
        }
        if($reqType=="wouldpayAdd"){
            $datas['add_time']=time();
            $datas['author']=session('userId');
            $datas['processLevel']=$this->processAuth["level"];
            unset($datas['id']);
            return $datas;
        }else if($reqType=="wouldpayEdit"){
            $where=["id"=>$datas['id']];
            $data=[];

            $data['updateUser']=session('userId');
            foreach (['finance_id','supplier_com','supplier_cont','pay_amount','pay_ratio','pay_date','detail','contract_amount','late_pay_date','advance','advance_date','surplus','next_date','advance_ratio','surplus_ratio','pay_grade','invoice_type','remark'] as  $key) {
                if(isset($datas[$key])){
                    $data[$key]=$datas[$key];
                }
            }
            if(isset($datas['status'])){
                $parameter=[
                    'where'=>["id"=>$datas['id']],
                ];
                $result=$this->wouldpayCom->getOne($parameter);
                $data = $this->status_update($result,$datas["status"],$data);
            }
            $data['upate_time']=time();
            
            return ["where"=>$where,"data"=>$data];
        }
        return "";
    }
    function wouldpayList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $where=["status"=>1];

        // $parameter=[
        //     'fields'=>"*",
        //     'where'=>$where,
        //     'page'=>$p,
        //     'pageSize'=>$this->pageSize,
        //     'orderStr'=>"add_time DESC",
        //     "joins"=>[
        //         "LEFT JOIN (SELECT id puId ,project_id,supplier_com,supplier_cont,sign_date,contract_amount,late_pay_date FROM v_purcha) pu ON pu.puId = cost_id",
        //         "LEFT JOIN (SELECT projectId,session_all,code,name project_name,project_time,brand,customer_com,leader,type,amount,DATE_ADD(FROM_UNIXTIME(project_time,'%Y-%m-%d'),INTERVAL days day) end_date FROM v_project ) p ON p.projectId = pu.project_id ",
        //         "LEFT JOIN (SELECT basicId brand_id,name brand_name FROM v_basic WHERE class = 'brand' ) b ON b.brand_id = p.brand",
        //         "LEFT JOIN (SELECT companyId company_id,company supplier_com_name,type FROM v_supplier_company ) c ON c.company_id = pu.supplier_com",
        //         "LEFT JOIN (SELECT basicId,name type_name FROM v_basic WHERE class='supType') sb ON sb.basicId=c.type",
        //         "LEFT JOIN (SELECT contactId contact_id,contact supplier_cont_name FROM v_supplier_contact ) sc ON sc.contact_id = pu.supplier_cont",
        //         "LEFT JOIN (SELECT basicId bankstock_id,name finance_name FROM v_basic WHERE class = 'bankstock' ) bf ON bf.bankstock_id = finance_id",
        //         "LEFT JOIN (SELECT userId user_id,userName leader_name FROM v_user) lu ON lu.user_id = p.leader",
        //         "LEFT JOIN (SELECT basicId type_id,name supplier_type_name FROM v_basic WHERE class = 'supType' ) t ON t.type_id = c.type",
        //     ],
        // ];
        
        // $listResult=$this->wouldpayCom->getList($parameter);
        // print_r( $listResult);
        $parameter=[
            'where'=>$where,
            'fields'=>"*",
            'page'=>$p,
            'pageSize'=>$this->pageSize,
            'orderStr'=>"id DESC",
            "joins"=>[
                "LEFT JOIN (SELECT projectId, name project_name,code,business,leader,brand ,FROM_UNIXTIME(project_time,'%Y-%m-%d') project_date,days FROM v_project) p ON p.projectId = project_id",
                "LEFT JOIN (SELECT userId user_id,userName business_name FROM v_user) bu ON bu.user_id = p.business",
                "LEFT JOIN (SELECT userId user_id,userName leader_name FROM v_user) lu ON lu.user_id = p.leader",
                "LEFT JOIN (SELECT companyId cid,company supplier_com_name,type,provinceId,cityId FROM v_supplier_company WHERE status=1) c ON c.cid=supplier_com",
                "LEFT JOIN (SELECT contactId cid,contact supplier_cont_name,phone supplier_cont_phone,email supplier_cont_email FROM v_supplier_contact WHERE status=1) ct ON ct.cid=supplier_cont",
                "LEFT JOIN (SELECT basicId,name type_name FROM v_basic WHERE class='supType') st ON st.basicId=c.type",
                "LEFT JOIN (SELECT basicId,name module_name FROM v_basic WHERE class='module') bm ON bm.basicId=module",
                "LEFT JOIN (SELECT basicId brand_id,name brand_name FROM v_basic WHERE class = 'brand' ) b ON b.brand_id = p.brand",
                "LEFT JOIN (SELECT pid ,province province_name FROM v_province) pr ON pr.pid=c.provinceId",
                "LEFT JOIN (SELECT cid,city city_name,pid FROM v_city) ci ON ci.cid=c.cityId",
                "LEFT JOIN (SELECT purcha_id lpid ,FROM_UNIXTIME(SUBSTRING_INDEX(GROUP_CONCAT(pay_date ORDER BY pay_date DESC),',',1),'%Y-%m-%d') last_pay_date FROM v_pay  WHERE insert_type =2 GROUP BY purcha_id) lp ON lp.lpid=id",
                "LEFT JOIN (SELECT purcha_id npid ,FROM_UNIXTIME(SUBSTRING_INDEX(GROUP_CONCAT(pay_date ORDER BY pay_date DESC),',',1),'%Y-%m-%d') next_pay_date FROM v_pay  WHERE insert_type =2 AND pay_date > ".strtotime(date("Y-m-d ")."23:59:59")." GROUP BY purcha_id) np ON np.npid=id",
                "LEFT JOIN (SELECT purcha_id ppid, SUM(pay_money) paid FROM v_pay WHERE insert_type =2 GROUP BY purcha_id) pd ON pd.ppid=id",
                
            ],
        ];
        
        $listResult=$this->purchaCom->getList($parameter);
        // print_r($listResult);
        foreach ($listResult["list"] as $key => $value) {
            $listResult["list"][$key]["end_date"] = date("Y-m-d",strtotime($value["project_date"]." +".$value["days"]."day"));
        }
        // print_r($listResult);
        $this->tablePage($listResult,'Finance/financeTable/wouldpayList',"wouldpayList");
    }
    function wouldpayAdd(){
        $info=$this->manageWouldpayInfo();
        if($info){
            $insertResult=$this->wouldpayCom->insertWouldpay($info);
            // echo $this->wouldpayCom->M()->_sql();
            if($insertResult && $insertResult->errCode==0){
                $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
            }
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-08 20:58:39 
     * @Desc: 修改 
     */    
    function wouldpayEdit(){
        // $info=$this->manageWouldpayInfo();
        // $updateResult=$this->wouldpayCom->updateWouldpay($info);
        // $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
        $isUpdate = false;
        $data=I("data");

        foreach ($data as $key => $itemInfo) {
            // print_r($itemInfo);
            $listCom = $this->payCom;
            if($itemInfo["id"]>0 && $itemInfo["fact_pay_money"]>0){
                $itemInfo["pay_date"] = strtotime($itemInfo["pay_date"]);
                $itemInfo["fact_pay_date"] = strtotime($itemInfo["fact_pay_date"]);
                $itemInfo["pay_status"] = 1;
                    $updateResult=$listCom->update($itemInfo);
                    if($updateResult->errCode==0){
                        $isUpdate =true;
                    }
                
            }else{
                $updateResult->error ="没有更改项";
            }
        }
        
        if($isUpdate){
            $this->ajaxReturn(['errCode'=>0,'error'=>"修改成功"]);
        }
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
    function getOptionList(){
        $key=I("key");
        $type=I("type");
        $project = A("Project");
        $this->ajaxReturn(["data"=>$project->_getOption($type,$key)]);
    }
    function project_modalOne(){
        $project = A("Project");
        $project->project_modalOne();
    }
    //应付款项结束

    //采购系统开始
    /** 
     * @Author: vition 
     * @Date: 2018-07-10 23:42:27 
     * @Desc: 采购 
     */    
    function purchaControl(){
        $reqType=I('reqType');
        $this->assign('dbName',"Purcha");//删除数据的时候需要
        $this->assign("controlName","purcha");//名字对应cust_company_modalOne，\
        $this->assign('projectArr',A("Project")->_getOption("project_id"));
        $this->assign("supComArr",A("Project")->_getOption("supplier_com"));
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function purcha_modalOne(){
        $title = "新建采购";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        if($gettype=="Edit"){
            $title = "编辑采购";
            $btnTitle = "保存数据";
            $redisName="purchaList";
            $resultData=$this->purchaCom->redis_one($redisName,"id",$id);
            foreach (['project_time','late_pay_date','advance_date','next_date','sign_date'] as  $date) {
                if(isset($resultData[$date])){
                    $resultData[$date] = date ("Y-m-d",$resultData[$date]);
                }
            }
            if($resultData["id"] > 0){
                $parameter=[
                    'fields'=>"id,pay_date,pay_amount,pay_ratio,pay_type,invoice_type,remark",
                    'where'=>["cost_id"=>$resultData["id"],"pay_amount"=>["gt",0]],
                    'page'=>0,
                    'pageSize'=>999,
                    'orderStr'=>"add_time DESC",
                ];
                
                $listResult=$this->wouldpayCom->getList($parameter);
                if(isset($listResult["list"])){
                    foreach ($listResult["list"] as $key => $value) {
                        $listResult["list"][$key]["invoice_type"] = $this->invoiceType[$value["invoice_type"]];
                        $listResult["list"][$key]["pay_type"] = $this->payType[$value["pay_type"]];
                        if($value["pay_date"]>0){
                            $listResult["list"][$key]["pay_type"] = date("Y-m-d",$value["pay_date"]);
                        }
                    }
                }
                $resultData["paid-list"] = $listResult["list"];
            }
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"purchaModal",
        ];
        $this->modalOne($modalPara);
    }
    function managePurchaInfo(){
        $reqType=I("reqType");
        $datas=I("data");

        // $datas['project_id'] = $datas['project_id'] ? $datas['project_id'] : 0;
        foreach (['sign_date'] as  $date) {
            if(isset($datas[$date])){
                $datas[$date]=strtotime($datas[$date]);
            }
        }
        if($reqType=="purchaAdd"){
            $datas['add_time']=time();
            $datas['author']=session('userId');
            $datas['processLevel']=$this->processAuth["level"];
            unset($datas['id']);
            return $datas;
        }else if($reqType=="purchaEdit"){
            $where=["id"=>$datas['id']];
            $data=[];

            $data['updateUser']=session('userId');
            foreach (['project_id','supplier_com','supplier_cont','sign_date','contract_amount','contract','remark'] as  $key) {
                if(isset($datas[$key])){
                    $data[$key]=$datas[$key];
                }
            }
            if(isset($datas['status'])){
                $parameter=[
                    'where'=>["id"=>$datas['id']],
                ];
                $result=$this->purchaCom->getOne($parameter);
                $data = $this->status_update($result,$datas["status"],$data);
            }
            $data['upate_time']=time();
            
            return ["where"=>$where,"data"=>$data];
        }
        return "";
    }
    function purchaList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $id = I("id");
        $onlydata = I("onlydata");
        $where=[];
        if($id>0){
            $where["id"] = $id;
        }
        $parameter=[
            'fields'=>"*",
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$this->pageSize,
            'orderStr'=>"add_time DESC",
            "joins"=>[
                "LEFT JOIN (SELECT projectId, name project_name,brand,code,project_time,DATE_ADD(FROM_UNIXTIME(project_time,'%Y-%m-%d'),INTERVAL days day) end_date,type,leader FROM v_project ) p ON p.projectId = project_id ",
                "LEFT JOIN (SELECT companyId company_id,company supplier_com_name,type,provinceId,cityId FROM v_supplier_company ) c ON c.company_id = supplier_com",
                "LEFT JOIN (SELECT contactId contact_id,contact supplier_cont_name FROM v_supplier_contact ) sc ON sc.contact_id = supplier_cont",
                "LEFT JOIN (SELECT basicId,name supplier_type FROM v_basic WHERE class='supType') sb ON sb.basicId=c.type",
                "LEFT JOIN (SELECT pid ,province FROM v_province ) pr ON pr.pid = c.provinceId",
                "LEFT JOIN (SELECT cid ctid ,city,pid cpid FROM v_city ) ct ON ct.ctid = c.cityId AND ct.cpid = c.provinceId",
                "LEFT JOIN (SELECT basicId brand_id,name brand_name FROM v_basic WHERE class = 'brand' ) b ON b.brand_id = p.brand",
                "LEFT JOIN (SELECT userId user_id,userName leader_name FROM v_user) lu ON lu.user_id = p.leader",
                "LEFT JOIN (SELECT basicId type_id,name supplier_type_name FROM v_basic WHERE class = 'supType' ) t ON t.type_id = c.type",
                "LEFT JOIN (SELECT cost_id, advance,advance_date,advance_ratio FROM v_wouldpay WHERE advance > 0 limit 1 ) a ON a.cost_id = id",
                "LEFT JOIN (SELECT cost_id, sum(pay_amount) paid_amount FROM v_wouldpay) pay ON pay.cost_id = id",
                "LEFT JOIN (SELECT cost_id, count(*) pay_num FROM v_wouldpay) co ON co.cost_id = id",
                // "LEFT JOIN (SELECT project_id wproject_id , COUNT(*) pay_num FROM v_wouldpay GROUP BY project_id) pn ON pn.wproject_id = project_id",
                // "LEFT JOIN (SELECT project_id aproject_id , SUM(advance) advance FROM v_wouldpay GROUP BY project_id) pa ON pa.aproject_id = project_id",
                // "LEFT JOIN (SELECT project_id pproject_id , SUM(pay_amount) paid FROM v_wouldpay GROUP BY project_id) pp ON pp.pproject_id = project_id",
                // "LEFT JOIN (SELECT project_id dproject_id , next_date FROM v_wouldpay ORDER BY id DESC LIMIT 1) dp ON dp.dproject_id=project_id",
            ],
        ];
        
        $listResult=$this->purchaCom->getList($parameter);
        // echo $this->purchaCom->M()->_sql();
        if($onlydata && isset($listResult["list"][0])){
            foreach (['project_time','late_pay_date','advance_date'] as  $date) {
                if(isset($listResult["list"][0][$date]) && $listResult["list"][0][$date]>0){
                    $listResult["list"][0][$date] = date ("Y-m-d",$listResult["list"][0][$date]);
                }
            }
            $this->ajaxReturn(["data"=>$listResult["list"][0]]);
        }
        // print_r( $listResult);
        $this->tablePage($listResult,'Finance/financeTable/purchaList',"purchaList");
    }
    function purchaAdd(){
        $info=$this->managePurchaInfo();
        if($info){
            $insertResult=$this->purchaCom->insertPurcha($info);
            // echo $this->wouldpayCom->M()->_sql();
            if($insertResult && $insertResult->errCode==0){
                //添加成功，同时插入应付数据
                $wouldInfo = [
                    'cost_id' => $insertResult->data,
                    'add_time' => time(),
                    'status' => 1,
                ];
                $insertResult=$this->wouldpayCom->insertWouldpay($wouldInfo);
                $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
            }
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-08 20:58:39 
     * @Desc: 修改 
     */    
    function purchaEdit(){
        $info=$this->managePurchaInfo();
        $updateResult=$this->purchaCom->updatePurcha($info);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
    //采购系统结束

    function staffClearControl(){//Finance/staffClearControl
        $reqType=I('reqType');
        $this->assign('dbName',"Clear");//删除数据的时候需要
        $this->assign("controlName","staffClear");
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function staffClear_modalOne(){
        $title = "提交清算";
        $btnTitle = "提交清算";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        if($gettype=="Edit"){
            $title = "编辑清算";
            $btnTitle = "保存数据";
            $redisName="staffClearList";
            $resultData=$this->clearCom->redis_one($redisName,"id",$id);
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"staffClearModal",
        ];
        $this->modalOne($modalPara);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-07-26 23:28:36 
     * @Desc: 清算查阅 
     */    
    function readClearControl(){//Finance/staffClearControl
        $reqType=I('reqType');
        $this->assign('dbName',"Clear");//删除数据的时候需要
        $this->assign("controlName","readClear");
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function readClear_modalOne(){
        $title = "提交清算";
        $btnTitle = "提交清算";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        if($gettype=="Edit"){
            $title = "编辑清算";
            $btnTitle = "保存数据";
            $redisName="readClearList";
            $resultData=$this->clearCom->redis_one($redisName,"id",$id);
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"readClearModal",
        ];
        $this->modalOne($modalPara);
    }
    function financeClearControl(){//Finance/financeClearControl
        $reqType=I('reqType');
        $this->assign('dbName',"Clear");//删除数据的时候需要
        $this->assign("controlName","financeClear");
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function financeClear_modalOne(){
        $title = "清算审核";
        $btnTitle = "清算审核";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        if($gettype=="Edit"){
            $title = "清算审核";
            $btnTitle = "清算审核";
            $redisName="financeClearList";
            $resultData=$this->clearCom->redis_one($redisName,"id",$id);
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"financeClearModal",
        ];
        $this->modalOne($modalPara);
    }
    
}