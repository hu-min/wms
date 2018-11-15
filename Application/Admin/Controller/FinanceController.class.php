<?php
namespace Admin\Controller;

/** 
 * @Author: vition 
 * @Date: 2018-05-06 09:45:33 
 * @Desc: 财务管理 
 */
class FinanceController extends BaseController{

    public function _initialize() {
        $this->Aproject=A("Project");
        parent::_initialize();
        $this->basicCom=getComponent('Basic');
        $this->fixExpenCom=getComponent('FixldExpense');
        $this->receivableCom=getComponent('Receivable');
        $this->wouldpayCom=getComponent('Wouldpay');
        $this->purchaCom=getComponent('Purcha');
        $this->payCom=getComponent('Pay');
        $this->clearCom=getComponent('Liquidate');
        $this->moneyAccCom=getComponent('MoneyAccount');
        $this->flCapLogCom=getComponent('flCapLog');
        $this->projectCom=getComponent('Project');
        $this->payGradeType = ["1"=>"A级[高]","2"=>"B级[次]","3"=>"C级[中]","4"=>"D级[低]"];
        $this->invoiceType = ["0"=>"无","1"=>"收据","2"=>"增值税普通","3"=>"增值税专用"];
        $this->payType = ['1'=>'公对公','2'=>'现金付款','3'=>'支票付款'];
        $this->payStatus = ['0'=>'未支付','1'=>'已支付','2'=>'支付无效'];
    }
    function stockControl(){
        $reqType=I('reqType');
        $this->assign('tableName',$this->basicCom->tableName());//删除数据的时候需要
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
        $this->assign('tableName',"VFixldExpense");//删除数据的时候需要
        $this->statusType = [0=>"未启用",1=>"启用",3=>"无效",4=>"删除"];
        $this->assign('statusType',$this->statusType );
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
    function fix_expenseCount(){
        $reqType=I('reqType');
        $this->assign("controlName","fix_expCount");

        $fixexpNodeId = $this->nodeCom->getNodeInfo("controller","Finance/fix_expenseControl","nodeId");
        $this->assign("fixexpNodeId",$fixexpNodeId);
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    /** 
     * @Author: vition 
     * @Date: 2018-09-07 15:10:58 
     * @Desc: 月固定统计查询列表
     */    
    function fix_expCountList(){
        $datas = I("data");
        $p=I("p")?I("p"):1;
        $where=["status"=>1,'payTime'=>['GT',0]];
        
        $fields = "FROM_UNIXTIME(payTime,'%Y') year ,FROM_UNIXTIME(payTime,'%m') date_time,SUM(fee) all_fee,SUM(payment) payment,SUM(noPayment) no_payment";
        $groupBy = "FROM_UNIXTIME(payTime,'%Y'), FROM_UNIXTIME(payTime,'%m')";
        if($datas["time-type"] == "year"){
            $fields = "FROM_UNIXTIME(payTime,'%Y') date_time,SUM(fee) all_fee,SUM(payment) payment,SUM(noPayment) no_payment";
            $groupBy = "FROM_UNIXTIME(payTime,'%Y')";
        }else{
            $year = $datas["year_date"] ? $datas["year_date"] : date("Y");
            $where["_string"] = "FROM_UNIXTIME(payTime,'%Y') = '{$year}'";
        }
       

        $parameter=[
            'fields'=>$fields,
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$this->pageSize,
            'orderStr'=>"payTime DESC",
            'groupBy' => $groupBy,
            "joins"=>[""],
        ];
        
        $listResult=$this->fixExpenCom->getList($parameter);
        //统计数据
        $parameter["sum"] = [];
        $countResult = $this->fixExpenCom->getOne($parameter);
        $countStr = "<div><label>支出总额：<span class='text-light-blue'>".$countResult["list"]["all_fee"]."</span></label> | <label>已付总额：<span class='text-light-blue'>".$countResult["list"]["payment"]."</span></label> | <label>未支付总额：<span class='text-light-blue'>".$countResult["list"]["no_payment"]."</span></label></div>";
        
        // echo $this->fixExpenCom->M()->_sql();
        $this->tablePage($listResult,'Finance/financeTable/fix_expCountList',"fix_expCount{}List",false,$countStr);
    }
    function fix_expenseList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $where=[];
        $seatime = "addTime";
        if(isset($data['status']) && $data['status']==1){
            $where["status"] = $data['status'];
            $seatime = "payTime";
        }

        //
        // $fields = "FROM_UNIXTIME(payTime,'%Y') year ,FROM_UNIXTIME(payTime,'%m') date_time,SUM(fee) all_fee,SUM(payment) payment,SUM(noPayment) no_payment";
        // $groupBy = "FROM_UNIXTIME(payTime,'%Y'), FROM_UNIXTIME(payTime,'%m')";
        if($data["month_date"] != ""){
            $where["_string"] = "({$seatime} > 0 AND FROM_UNIXTIME({$seatime},'%Y-%m') = '{$data['month_date']}')";
            // $fields = "FROM_UNIXTIME(addTime,'%Y') date_time,SUM(fee) all_fee,SUM(payment) payment,SUM(noPayment) no_payment";
            // $groupBy = "FROM_UNIXTIME(payTime,'%Y')";
        }else{
            $year = $data["year_date"] ? $data["year_date"] : date("Y");
            $where["_string"] = "({$seatime} > 0 AND FROM_UNIXTIME({$seatime},'%Y') = '{$year}')";
        }
        //
        if($data['expenClas']){
            $where['expenClas']=$data['expenClas'];
        }
        $parameter=[
            'fields'=>"`id`,`expenClas`,expenClass,`finanAccount`,finanAccs,`toObject`,`content`,`startDate`,`endDate`,`fee`,`payment`,noPayment,payTime,remark,addTime,status,process_level,author,examine,detail_file",
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$this->pageSize,
            'orderStr'=>"id DESC",
            "joins"=>["LEFT JOIN (SELECT basicId , `name` expenClass FROM v_basic WHERE status=1 AND class='expenClas' ) bt ON bt.basicId=expenClas","LEFT JOIN (SELECT basicId , `name` finanAccs FROM v_basic WHERE class in ('bankstock','cashstock') ) bf ON bf.basicId=finanAccount"],
        ];
        
        $listResult=$this->fixExpenCom->getList($parameter);
        // echo $this->fixExpenCom->M()->_sql();exit;
        //统计数据
        $parameter["sum"] = ["fee","payment","noPayment"];
        $countResult = $this->fixExpenCom->getOne($parameter);
        $countStr = "<div><label>支出总额：<span class='text-light-blue'>".$countResult["list"]["fee"]."</span></label> | <label>已付总额：<span class='text-light-blue'>".$countResult["list"]["payment"]."</span></label> | <label>未支付总额：<span class='text-light-blue'>".$countResult["list"]["noPayment"]."</span></label></div>";

        $this->tablePage($listResult,'Finance/financeTable/fix_expenseList',"fix_expenseList",false,$countStr);
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
            $datas['status']=1;
            unset($datas['id']);
            return $datas;
        }else if($reqType=="fix_expenseEdit"){
            $where=["id"=>$datas['id']];
            $data=[];
            $data['updateTime']=time();
            foreach (['expenClas','finanAccount','toObject','content','startDate','endDate','fee','payment','noPayment','payTime','remark','detail_file','status'] as $key ) {
                if(isset($datas[$key])){
                    $data[$key]=$datas[$key];
                }
            }
           
            // if(isset($datas['status'])){
            //     $parameter=[
            //         'where'=>["id"=>$id],
            //     ];
            //     $result=$this->fixExpenCom->getList($parameter,true);
            //     $data = $this->status_update($result,$datas["status"],$data);
            // }
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
        $this->assign('tableName',"VReceivable");//删除数据的时候需要
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
            $datas['process_level']=$this->processAuth["level"];
            unset($datas['id']);
            return $datas;
        }else if($reqType=="receivableEdit"){
            $where=["id"=>$datas['id']];
            $data=[];

            $data['updateUser']=session('userId');
            foreach (['advance_date','advance','contract_date','id','next_date','pay_amount','pay_date','project_id','remark','status','surplus_date','surplus','invoice'] as  $key) {
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
        if($this->nodeAuth[CONTROLLER_NAME.'/'.ACTION_NAME]<7){
            if($this->nodeAuth[CONTROLLER_NAME.'/'.ACTION_NAME]<3){
                $where['_string'] = "leader=".session('userId')." OR business=".session('userId');
            }
        }
        $pageSize = isset($data['pageSize']) ? $data['pageSize'] : $this->pageSize;
        $parameter=[
            'fields'=>"*,FROM_UNIXTIME(project_time,'%Y-%m-%d') project_time,FROM_UNIXTIME(contract_date,'%Y-%m-%d') contract_date",
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$pageSize,
            'orderStr'=>"add_time DESC",
            "joins"=>[
                "LEFT JOIN (SELECT projectId,session_all,code,name,project_time,brand,customer_com,leader,business,type,amount FROM v_project ) p ON p.projectId = project_id ",
                // "LEFT JOIN (SELECT basicId brand_id,name brand_name FROM v_basic WHERE class = 'brand' ) b ON b.brand_id = p.brand",
                "LEFT JOIN (SELECT companyId company_id,company customer_com_name FROM v_customer_company ) c ON c.company_id = p.customer_com",
                "LEFT JOIN (SELECT userId user_id,userName business_name FROM v_user) bu ON bu.user_id = p.business",
                "LEFT JOIN (SELECT basicId type_id,name type_name FROM v_basic WHERE class = 'projectType' ) t ON t.type_id = p.type",
            ],
        ];
        
        $listResult=$this->receivableCom->getList($parameter);
        // echo $this->receivableCom->M()->_sql();exit;
        $this->tablePage($listResult,'Finance/financeTable/receivableList',"receivableList",$pageSize);
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
        $this->assign('tableName',"VWouldpay");//删除数据的时候需要
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
        if(($resultData['business'] == session('userId') || $resultData['leader'] == session('userId')) || $nodeAuth >= 7 ){

        }else{
            $resultData['contract_file'] = "";
        }
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
            $datas['process_level']=$this->processAuth["level"];
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
        if($this->nodeAuth[CONTROLLER_NAME.'/'.ACTION_NAME]<7){
            if($this->nodeAuth[CONTROLLER_NAME.'/'.ACTION_NAME]<3){
                $where['_string'] = "leader=".session('userId')." OR business=".session('userId');
            }
        }
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
        // print_r( $listResult); type
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
                "LEFT JOIN (SELECT companyId cid,company supplier_com_name,supr_type,provinceId,cityId FROM v_supplier_company WHERE status=1) c ON c.cid=supplier_com",
                "LEFT JOIN (SELECT contactId cid,contact supplier_cont_name,phone supplier_cont_phone,email supplier_cont_email FROM v_supplier_contact WHERE status=1) ct ON ct.cid=supplier_cont",
                "LEFT JOIN (SELECT basicId,name type_name FROM v_basic WHERE class='supType') st ON st.basicId=c.supr_type",
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
        $this->assign('tableName',"VPurcha");//删除数据的时候需要
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
            $datas['process_level']=$this->processAuth["level"];
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
        // $this->assign('tableName',"Clear");//删除数据的时候需要
        $this->assign("controlName","staffClear");
        $this->assign("tableName",$this->clearCom->tableName()); 
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function getReckonList($return=false){
        $sql="SELECT p.project_id project_id,CASE WHEN ISNULL(vp.name) THEN '非项目' ELSE vp.name END `name` ,CASE WHEN ISNULL(vp.code) THEN '非项目' ELSE vp.code END `code`,SUM(debit_money) debit_money,COUNT(debit_money) debit_num,SUM(money) expense_money,COUNT(money) expense_num,SUM(invoice_money) invoice_money,GROUP_CONCAT(did) debit_ids,GROUP_CONCAT(eid) expense_ids,leader FROM (SELECT project_id FROM v_debit WHERE `status`=1 AND user_id=".session("userId")." AND clear_status = 0 UNION SELECT project_id FROM v_expense_sub RIGHT JOIN (SELECT id exId,project_id FROM v_expense WHERE `status`=1 AND user_id=".session("userId").") m1 ON m1.exId=parent_id WHERE clear_status = 0) p LEFT JOIN (SELECT project_id,debit_money,id did FROM v_debit WHERE `status`=1 AND user_id=".session("userId")." AND clear_status = 0) d ON d.project_id=p.project_id LEFT JOIN (SELECT project_id,parent_id,money,invoice_money,id eid FROM v_expense_sub LEFT JOIN (SELECT id exId,project_id FROM v_expense WHERE `status`=1 AND user_id=".session("userId").") m ON m.exId=parent_id WHERE clear_status = 0) e ON e.project_id=p.project_id LEFT JOIN (SELECT projectId,name,code,leader FROM v_project) vp ON vp.projectId=p.project_id GROUP BY p.project_id";
        // echo $sql;exit;
        $db = M();
        $addResult = $db->query($sql);
        $this->assign("list",$addResult);
        $allReckon = 0;
        if($addResult){
            foreach ($addResult as $reckon) {
                $allReckon += ($reckon["expense_money"]-$reckon["debit_money"]);
            }
        }
        $html=$this->fetch('Finance/financeTable/reckonLi');
        $html = empty($html) ?  '<tr><td colspan="9">暂无数据</td></tr>' : $html;
        $this->assign("allReckon",$allReckon);
        if($return){
            return $html;
        }else{
            $this->ajaxReturn(['table'=>$html]);
        }
    }
    function staffClearList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $where=[];
        if($this->nodeAuth[CONTROLLER_NAME.'/'.ACTION_NAME]<7){
            $where['user_id'] = session('userId');
        }
        $pageSize = isset($data['pageSize']) ? $data['pageSize'] : $this->pageSize;
        $parameter=[
            'fields'=>"*,FROM_UNIXTIME(add_time,'%Y-%m-%d %H:%i:%s') add_time",
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$pageSize,
            'orderStr'=>"id DESC",
            "joins"=>[
                "LEFT JOIN (SELECT projectId,code,name FROM v_project ) p ON p.projectId = project_id ",
                "LEFT JOIN (SELECT table_id tid , SUBSTRING_INDEX( GROUP_CONCAT(user_id),',',-1) tuserid,SUBSTRING_INDEX(GROUP_CONCAT(remark),',',-1) aremark FROM v_approve_log WHERE status > 0 AND effect = 1 AND table_name ='".$this->clearCom->tableName()."' GROUP BY table_id ORDER BY add_time DESC) ap ON ap.tid=id",
                "LEFT JOIN (SELECT userId auser_id,userName approve_name FROM v_user) au ON au.auser_id = ap.tuserid",
            ]
        ];
        $listResult=$this->clearCom->getList($parameter);
        $this->tablePage($listResult,'Finance/financeTable/staffClearList',"staffClearList",$pageSize);
    }
    function staffClear_modalOne(){
        $title = "提交清算";
        $btnTitle = "提交清算";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        if($gettype=="Edit"){
            $title = "查看清算数据";
            $btnTitle = "保存数据";
            $redisName="staffClearList";
            $resultData=$this->clearCom->redis_one($redisName,"id",$id);
            // print_r($resultData);exit;
            $this->assign("list",[$resultData]);
            $this->assign("gettype",$gettype);
            $html = $this->fetch('Finance/financeTable/reckonLi');
            $this->assign("allReckon",$resultData["all_money"]);
        }else{
            $html = $this->getReckonList(true);
        }
        $this->assign("tables",$html);
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"staffClearModal",
            // "assign"=>["table"=>$html],
        ];
        $this->modalOne($modalPara);
    }
    function manageSClear($datas,$reqType=false){
        $reqType = $reqType ? $reqType : I("reqType");
        if($reqType=="staffClearAdd"){
            $datas['add_time']=time();
            $datas['user_id']=session('userId');

            //添加时必备数据
            $examines = getComponent('Process')->getExamine(I("vtabId"),$datas['leader']);
            // $process = $this->nodeCom->getProcess(I("vtabId"));
            $datas['process_id'] = $examines["process_id"];
            $datas['examine'] = $examines["examine"];
            if($datas["project_id"]>0){ 
                unset($datas['leader']);
            }
            // $datas['process_id'] = $process["processId"];
            // if($datas["project_id"]>0){ 
            //     //存在项目，则第一个审批的人是项目主管,examine需要
            //     $userRole = $this->userCom->getUserInfo($datas['leader']);
            //     $datas['examine'] = implode(",",array_unique(explode(",",$userRole['roleId'].",".$process["examine"])));
            //     unset($datas['leader']);
            // }else{
            //     $datas['examine'] = $process["examine"];
            // }
            //如果是审批者自己提交的执行下列代码
            $roleId = session("roleId");
            $rolePlace = $examines['place'];
            // $examineArr = explode(",",$datas['examine']);
            // $rolePlace = search_last_key($roleId,$examineArr);
            $datas['status'] = 0;
            if($rolePlace!==false){
                $datas['process_level']=$rolePlace+2;
                if(count(explode(",",$examines['examine'])) <= ($rolePlace+1)){
                    $datas['status'] = 1;
                }else{
                    $datas['status'] = 2;
                }
            }else{
                $datas['process_level']=$process["place"] > 0 ? $process["place"] : 1;
            }
            $datas['examine'] = getComponent('Process')->filterExamine(session('roleId'),$datas['examine']);
            // $datas['process_level']=$process["place"];
            unset($datas['id']);
            return $datas;
        }else if($reqType=="staffClearEdit"){
            $where=["id"=>$datas['id']];
            $data=[];
            
            foreach (["debit_num","debit_money","expense_num","expense_money","invoice_money","all_money","project_id","debit_ids","expense_ids"] as  $key) {
                if(isset($datas[$key])){
                    $data[$key]=$datas[$key];
                }
            }
            if(isset($datas['status'])){
                $data['status'] = $datas['status'] == 3 ? 0 : $datas['status'];
            }
            $data['upate_time']=time();
            
            return ["where"=>$where,"data"=>$data];
        }
        return "";
    }
    function staffClearAdd(){
        $datas=I("data");
        $debitCom=getComponent('Debit');
        $expenseCom=getComponent('ExpenseSub');
        $allCount = count($datas);
        $upPoint = 0;
        $debitCom->startTrans();
        $expenseCom->startTrans();
        $this->clearCom->startTrans();
        $this->ApprLogCom->startTrans();
        foreach ($datas as $key => $clearInfo) {
            $updateStatus = false;
            $updateStatus = true;
            $this->log($clearInfo);
            foreach (["debit","expense"]as $item) {
                $Ids = explode(",",$clearInfo[$item."_ids"]);
                if(!empty($clearInfo[$item."_ids"])){
                    $parameter=[
                        "where"=>["id"=>["IN",$Ids]],
                        "data"=>["update_time"=>time(),"clear_status"=>2],
                    ];

                    $com = $item."Com";
                    $this->log($parameter);
                    $updateRes = $$com->update($parameter);
                    if(isset($updateRes->errCode) && $updateRes->errCode == 0){
                        $updateStatus = true;
                    }else{
                        $updateStatus = false;
                    }
                }
            }
            
            
            if($updateStatus){
                $updateInfo = $this->manageSClear($clearInfo);
                $examine = $updateInfo['examine'];
                $this->log($updateInfo);
                $insertRes = $this->clearCom->insert($updateInfo);
                if(isset($insertRes->errCode) && $insertRes->errCode == 0){
                    $upPoint++; 
                    $this->ApprLogCom->createApp($this->clearCom->tableName(),$insertRes->data,session("userId"),"");
                }
            }
        }
        if($allCount == $upPoint){
            $debitCom->commit();
            $expenseCom->commit();
            $this->clearCom->commit();
            $this->ApprLogCom->commit();

            $touser = $this->userCom->getQiyeId(explode(',',$examine)[0],true);
            if(!empty($touser)){
                $desc = "<div class='gray'>".date("Y年m月d日",time())."</div> <div class='normal'>".session('userName')."申请清算，@你了，点击进入审批吧！</div>";
                $url = C('qiye_url')."/Admin/Index/Main.html?action=Finance/staffClearControl";
                $msgResult = $this->QiyeCom-> textcard($touser,session('userName')."申请了清算",$desc,$url);
            }
            $this->ajaxReturn(['errCode'=>0,'error'=>"添加成功"]);
        }
        $debitCom->rollback();
        $expenseCom->rollback();
        $this->clearCom->rollback();
        $this->ApprLogCom->rollback();
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-07-26 23:28:36 
     * @Desc: 清算查阅 
     */    
    function readClearControl(){//Finance/staffClearControl
        $reqType=I('reqType');
        $this->assign('tableName',"Clear");//删除数据的时候需要
        $this->assign("controlName","readClear");
        $this->assign("tableName",$this->clearCom->tableName()); 
        $clearNodeId = $this->nodeCom->getNodeInfo("controller","Finance/financeClearControl","nodeId");
        $this->assign("clearNodeId",$clearNodeId);
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function readClearList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        // $where=[];
        
        $roleId = session('roleId');
        $clearType = [["title"=>"未清算","color"=>"blue"],["title"=>"已清算","color"=>"green"],["title"=>"清算中","color"=>"orange"]];
        // if($this->nodeAuth[CONTROLLER_NAME.'/'.ACTION_NAME]<7){
        //     $where["_string"] = "FIND_IN_SET({$roleId},examine) <= process_level AND FIND_IN_SET({$roleId},examine) > 0";
        // }
        // $table  = "SELECT p.project_id project_id,vp.name,vp.code,SUM(debit_money) debit_money,COUNT(debit_money) debit_num,SUM(money) expense_money,COUNT(money) expense_num,SUM(invoice_money) invoice_money,GROUP_CONCAT(did) debit_ids,GROUP_CONCAT(eid) expense_ids,leader,clear_status,user_id,user_name FROM (SELECT project_id,user_id,clear_status FROM v_debit WHERE `status`=1 UNION SELECT project_id,user_id,clear_status FROM v_expense_sub LEFT JOIN (SELECT id exId,project_id,user_id FROM v_expense WHERE `status`=1) m1 ON m1.exId=parent_id ) p LEFT JOIN (SELECT project_id,debit_money,id did FROM v_debit WHERE `status`=1) d ON d.project_id=p.project_id LEFT JOIN (SELECT project_id,parent_id,money,invoice_money,id eid FROM v_expense_sub LEFT JOIN (SELECT id exId,project_id FROM v_expense WHERE `status`=1) m ON m.exId=parent_id ) e ON e.project_id=p.project_id LEFT JOIN (SELECT projectId,name,code,leader FROM v_project) vp ON vp.projectId=p.project_id LEFT JOIN (SELECT userId ,userName user_name FROM v_user) u ON u.userId = user_id GROUP BY p.project_id ORDER BY project_id DESC";
        $whites = $this->whiteCom->getWhites();
        if($whites){
            $where = " WHERE c.user_id NOT IN (".implode(',',$whites).")";
        }else{
            $where = "";
        }
        $pageSize = isset($data['pageSize']) ? $data['pageSize'] : $this->pageSize;

        $table = "SELECT p.project_id project_id,SUM(debit_money) debit_money,COUNT(debit_money) debit_num,SUM(money) expense_money,COUNT(money) expense_num,SUM(invoice_money) invoice_money,GROUP_CONCAT(did) debit_ids,GROUP_CONCAT(eid) expense_ids,user_id, clear_status,vp.name,vp.code,user_name  FROM (SELECT project_id,user_id,clear_status FROM v_debit WHERE `status`=1 UNION SELECT project_id,user_id,clear_status FROM v_expense_sub RIGHT JOIN (SELECT id exId,project_id,user_id FROM v_expense WHERE `status`=1) m1 ON m1.exId=parent_id) p LEFT JOIN (SELECT project_id,debit_money,id did,user_id user_did FROM v_debit WHERE `status`=1) d ON d.project_id=p.project_id AND d.user_did = p.user_id LEFT JOIN (SELECT project_id,parent_id,money,invoice_money,id eid,user_id user_eid  FROM v_expense_sub RIGHT JOIN (SELECT id exId,project_id,user_id FROM v_expense WHERE `status`=1) m ON m.exId=parent_id ) e ON e.project_id=p.project_id AND e.user_eid=p.user_id LEFT JOIN (SELECT projectId,name,code,leader FROM v_project) vp ON vp.projectId=p.project_id LEFT JOIN (SELECT userId ,userName user_name FROM v_user) u ON u.userId = user_id GROUP BY p.project_id,clear_status";

        $sql="SELECT * FROM ({$table}) c {$where} LIMIT ".(($p-1)*$pageSize).",".$pageSize;
        $db = M();
        $addResult = $db->query($sql);
        
        $countResult = $db->query("SELECT count(*) vcount FROM ({$table}) c {$where}");
        // print_r($countResult);
        $count = isset($countResult[0]["vcount"]) ? $countResult[0]["vcount"] : 0;
        $listResult=["list" => $addResult,"count" => $count];
        $this->assign("clearType",$clearType);
        $this->tablePage($listResult,'Finance/financeTable/readClearList',"readClearList",$pageSize);
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
        // $this->assign('tableName',"Clear");//删除数据的时候需要
        $this->assign("controlName","financeClear");
        $this->assign("tableName",$this->clearCom->tableName()); 
        $readNodeId = $this->nodeCom->getNodeInfo("controller","Finance/readClearControl","nodeId");
        $this->assign("readNodeId",$readNodeId);
        // print_r($readNodeId);exit;
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function financeClearList(){
        
        $data=I("data");
        $p=I("p")?I("p"):1;
        // $group = I("group");
        $countype = $data["countype"];
        // $projectGroup = I("projectGroup");
        $project_id = I("projectId");
        // $userGroup = I("userGroup");
        $user_id = I("userId");
        $where=[];

        $roleId = session('roleId');
        //test
        // $group = true;
        // $userGroup = true;
        // $user_id = 3;
        //test
        if($this->nodeAuth[CONTROLLER_NAME.'/'.ACTION_NAME]<7){
            $where["_string"] = "FIND_IN_SET({$roleId},examine) <= process_level AND FIND_IN_SET({$roleId},examine) > 0";
        }
        foreach (["name","user_name"] as $key) {
            if(isset($data[$key])){
                $where[$key] = ['LIKE',"%{$data[$key]}%"];
            }
        }

        $whites = $this->whiteCom->getWhites();
        if($whites){
            if(isset($where["_string"])){
                $where["_string"].=" AND user_id NOT IN (".implode(',',$whites).")";
            }else{
                $where["_string"] = " user_id NOT IN (".implode(',',$whites).")";
            }
        }

        $fields = "*,FIND_IN_SET({$roleId},examine) place,FROM_UNIXTIME(add_time,'%Y-%m-%d') add_time";
        $groupBy = NULL;
        $orderStr = "id DESC";
        $template = "financeClearList";
        if($countype==2){
            $fields .= ",SUM(debit_num) debit_num,SUM(debit_money) debit_money,SUM(expense_num) expense_num,SUM(expense_money) expense_money,SUM(invoice_money) invoice_money,SUM(all_money) all_money";
            $groupBy = "project_id ,user_id,status";
            $orderStr = "id DESC,project_id DESC";
            $template = "financeClearPList";
        }elseif($countype==1){
            $fields .= ",COUNT(DISTINCT project_id) project_num,SUM(debit_num) debit_num,SUM(debit_money) debit_money,SUM(expense_num) expense_num,SUM(expense_money) expense_money,SUM(invoice_money) invoice_money,SUM(all_money) all_money";
            $groupBy = "user_id,status";
            $orderStr = "id DESC,project_id DESC";
            $template = "financeClearUList";
        }
        $pageSize = isset($data['pageSize']) ? $data['pageSize'] : $this->pageSize;
        $parameter=[
            'fields'=>$fields,
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$pageSize,
            'orderStr'=>$orderStr,
            "groupBy" =>$groupBy,
            "joins"=>[
                "LEFT JOIN (SELECT projectId,code,name,leader FROM v_project ) p ON p.projectId = project_id ",
                "LEFT JOIN (SELECT userId,userName user_name FROM v_user) un ON un.userId = user_id",
                // "LEFT JOIN (SELECT userId,userName business_name FROM v_user) bu ON bu.userId = p.business",
                // "LEFT JOIN (SELECT userId,userName leader_name FROM v_user) lu ON lu.userId = p.leader",
                // "LEFT JOIN (SELECT basicId,name free_name FROM v_basic WHERE class='feeType') f ON f.basicId=free_type",
            ]
        ];
        $listResult=$this->clearCom->getList($parameter);
        // $sumField = 
        // echo $this->clearCom->M()->_sql();exit;
        $parameter["sum"] = ["debit_num","debit_money","expense_num","expense_money","invoice_money","all_money"];
        $countResult = $this->clearCom->getOne($parameter);
        // print_r($countResult);
        // echo $this->clearCom->M()->_sql();exit;
        $countStr = "<div><label>借支次数总计：<span class='text-light-blue'>".$countResult["list"]["debit_num"]."</span></label> | <label>借支金额总计：<span class='text-light-blue'>".$countResult["list"]["debit_money"]."</span></label> | <label>报销次数总计：<span class='text-light-blue'>".$countResult["list"]["expense_num"]."</span></label> | <label>报销金额总计：<span class='text-light-blue'>".$countResult["list"]["expense_money"]."</span></label> | <label>清算金额总计：<span class='text-light-blue'>".$countResult["list"]["all_money"]."</span></label></div>";
        // echo $countStr;exit;
        $this->tablePage($listResult,'Finance/financeTable/'.$template,"financeClearList",$pageSize,$countStr);
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

            $this->assign("list",[$resultData]);
            $this->assign("gettype",$gettype);
            $html = $this->fetch('Finance/financeTable/fin_reckonLi');
            $this->assign("allReckon",$resultData["all_money"]);
            $this->assign("tables",$html);
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"financeClearModal",
        ];
        $this->modalOne($modalPara);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-10-10 12:47:21 
     * @Desc: 发票管理 
     */    
    function invoiceControl(){
        $reqType=I('reqType'); 
        $this->assign("controlName","invoiceCon");
        if($reqType){
            $this->$reqType();
        }else{
            $fileArr = [];
            getFiles("Download",$fileArr);
            foreach ($fileArr as $file) {
                if((time()-filectime($file))>600){ 
                    @unlink($file);
                }
            }
            
            $this->returnHtml();
        }
    }
    /** 
     * @Author: vition 
     * @Date: 2018-10-10 12:47:28 
     * @Desc: 发票列表 
     */    
    function invoiceConList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $export = I('export');
        $where=[];
        $whereStr="";
        foreach (['project_name','user_name','from_source'] as $key) {
            if(isset($data[$key])){
                array_push($where,$key.' LIKE "%'.$data[$key].'%" ');
            }
        }
        if(!empty($where)){
            $whereStr = " WHERE ".implode("AND",$where);
        }
        $pageSize = isset($data['pageSize']) ? $data['pageSize'] : $this->pageSize;
        $sql = "SELECT from_source,project_name,user_name,invoice,money,table_name,id,inv.add_time add_time FROM (SELECT '报销' from_source,project_id,user_id,vouch_file invoice,invoice_money money,'v_expense_sub' table_name,es.id id,es.add_time add_time FROM v_expense_sub es LEFT JOIN v_expense e ON e.id=parent_id WHERE vouch_file <> '' AND invoice_money > 0 UNION ALL
        SELECT '供应商成本' from_source,project_id,i.user_id user_id,invoice_file invoice,invoice_money money,'v_invoice' table_name,i.id id,pu.add_time add_time FROM v_invoice  i LEFT JOIN v_purcha pu ON pu.id=relation_id WHERE relation_type=1) inv LEFT JOIN (SELECT projectId,name project_name FROM v_project ) p ON p.projectId = inv.project_id LEFT JOIN (SELECT userId user_id,userName user_name FROM v_user) bu ON bu.user_id = inv.user_id".$whereStr;
        // echo $sql,exit;
        $basicResult['list'] = M()->query($sql);
        if($export){
            $config = ['control'=>CONTROLLER_NAME];
        }
        
        // $basicResult=$this->LogCom->getList($parameter);
        $this->tablePage($basicResult,'Finance/financeTable/invoiceConList',"invoiceConList",$pageSize,'',$config);
    }
    function invoiceEdit(){
        $request = I('request');
        $reqType = I('reqType');
        $data = I('data');
        if($request){
            $data = $this->Redis->get($request);
            if(count($data)<2){
                $this->LogCom->log(7);
                preg_match_all("/([^\/]+)\.([\S]+)$/",basename($data[0]['url']),$match);
                $fileName = preg_replace("/([^\/]+)\.[\S]+$/",$data[0]['from']."_".$data[0]['project']."_".$data[0]['user']."_".$data[0]['id'].".".$match[2][0],basename($data[0]['url']));
                header('Content-Disposition:attachment;filename=' . $fileName);
                header('Content-Length:' . filesize($data[0]['url']));
                readfile($data[0]['url']);
            }else{
                $zip = new \ZipArchive();
                $filename = "Download/invoice".date("YmdHis").".zip";
                if ($zip->open($filename, \ZIPARCHIVE::CREATE)!==TRUE) {  
                    exit('无法打开文件，或者文件创建失败');
                  }  
                foreach ($data as $file) {
                    if(file_exists(iconv("utf-8","gbk",$file['url']))){
                        preg_match_all("/([^\/]+)\.([\S]+)$/",basename($file['url']),$match);
                        $fName = preg_replace("/([^\/]+)\.[\S]+$/",$file['from']."_".$file['project']."_".$file['user']."_".$file['id'].".".$match[2][0],basename($file['url']));
                        // echo $fName,";";
                        // echo iconv("utf-8","gbk",$file['url']);
                        // $zip->addFile( iconv("utf-8","gbk",$file['url']),$fName);
                        $zip->addFromString( iconv('utf-8', 'gbk//ignore', $fName), file_get_contents(iconv("utf-8","gbk",$file['url'])));
                    }
                }
                // echo $zip->numFiles ;
                // exit;
                // echo $filename;
                $zip->close();
                if(!file_exists($filename)){  
                    exit("无法找到文件"); //即使创建，仍有可能失败。。。。  
                }
                $this->LogCom->log(7);
                header("Cache-Control: public"); 
                header("Content-Description: File Transfer"); 
                header('Content-disposition: attachment; filename='.basename($filename)); //文件名  
                header("Content-Type: application/zip"); //zip格式的  
                header("Content-Transfer-Encoding: binary"); //告诉浏览器，这是二进制文件  
                header('Content-Length: '. filesize($filename)); //告诉浏览器，文件大小  
                @readfile($filename);
            }
        }else{
            $request = md5(json_encode($data));
            $this->Redis->set(md5(json_encode($data)),$data,3000);
            $this->ajaxReturn(['errCode'=>0,'error'=>getError(0),'url'=>U(CONTROLLER_NAME."/".ACTION_NAME)."?reqType=invoiceEdit&request=".$request]);
        }
    }
    /** 
     * @Author: vition 
     * @Date: 2018-11-14 09:38:08 
     * @Desc: 流动资金 
     */    
    function float_capital(){
        $reqType=I('reqType'); 
        $this->assign("controlName","float_capital");
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    //公司账号显示
    function float_capital_modalOne(){
        $title = "新建公司账户";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        
        if($gettype=="Edit"){
            $title = "编辑公司账户";
            $btnTitle = "保存数据";
            $redisName = "fix_expenseList";
            $resultData = $this->moneyAccCom->redis_one($redisName,"id",$id);
        }

        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"moneyAccountModal",
        ];
        $this->modalOne($modalPara);
    }
    //获取流动资金
    function getMoneyAccList(){
        $tables = [];
        $param = [
            'where' => [],
        ];
        $result = $this->moneyAccCom->getList($param);
        $flCapLogNodeId = $this->nodeCom->getNodeInfo("controller","Finance/float_capital_log","nodeId");
        $this->assign("flCapLogNodeId",$flCapLogNodeId);

        $this->assign('list',$result['list']);
        foreach (['bank','cash','strongbox'] as $key ) {
            $this->assign('stockType',$key);
            $tables[$key.'Table'] = $this->fetch('Finance/financeTable/money_accountList');
        }
        $tables['allBank'] = round(array_sum(array_column($result['list'],'bank_stock')),2);
        $tables['allCash'] = round((array_sum(array_column($result['list'],'cash_stock'))+array_sum(array_column($result['list'],'strongbox'))),2);

        $inOut = ["day-income"=>0,"day-expend"=>0,"month-income"=>0,"month-expend"=>0];
        $where['happen_time'] = [['egt',strtotime(date("Y-m-d")." 00:00:00")],['lt',strtotime(date("Y-m-d")." 23:59:59")]];
        $param = [
            'where' => $where,
            'fields' => " float_type ,SUM(money) money",
            'groupBy' => 'float_type',
        ];
        $dayResult = $this->flCapLogCom->getList($param);
        if($dayResult){
            foreach ($dayResult['list'] as $value) {
                if($value['float_type'] == 1){
                    $inOut['day-income'] =  $value['money'];
                }else if($value['float_type'] == 2){
                    $inOut['day-expend'] =  $value['money'];
                }
            }
        }
        $param['where']['happen_time'] = [['egt',strtotime(date("Y-m-")."01 00:00:00")],['lt',strtotime(date("Y-m-d")." 23:59:59")]];
        $monthResult = $this->flCapLogCom->getList($param);
        if($monthResult){
            foreach ($monthResult['list'] as $value) {
                if($value['float_type'] == 1){
                    $inOut['month-income'] =  $value['money'];
                }else if($value['float_type'] == 2){
                    $inOut['month-expend'] =  $value['money'];
                }
            }
        }
        $this->ajaxReturn(['tables'=>$tables,'inOut'=>$inOut]);
    }
    function manageMoneyAccInfo($param){
        $reqType = $param['reqType'] ? $param['reqType'] : I("reqType");
        $datas = $param['data'] ? $param['data'] : I("data");
        if($reqType=="float_capitalAdd"){

            $datas['status']=1;
            $datas['add_time'] = time();
            $datas['user_id'] = session("userId");
            unset($datas['id']);
            return $datas;
        }else if($reqType=="float_capitalEdit"){
            $where=["id"=>$datas['id']];
            $data=[];
            foreach (['account','bs_title','cs_title','sb_title'] as $key) {
                if(isset($datas[$key])){
                    $data[$key]=$datas[$key];
                }
            }
            return ["where"=>$where,"data"=>$data];
        }
        return "";
    }
    //公司账户添加
    function float_capitalAdd(){
        $Info = $this->manageMoneyAccInfo();
        $hasData = $this->moneyAccCom->getOne(['where'=>['account'=>$Info['account']]]);
        if($hasData){
            $this->ajaxReturn(['errCode'=>117,'error'=>getError(117).":[".$Info['account']."]"]);
        }
        if($Info){
            $insertResult=$this->moneyAccCom->insert($Info);
            $this->ajaxReturn(['errCode'=>$insertResult->errCode,'error'=>getError($insertResult->errCode)]);
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    }
    function float_capitalEdit(){}
    /** 
     * @Author: vition 
     * @Date: 2018-11-14 17:19:14 
     * @Desc: 流动资金明细 
     */    
    function float_capital_log(){
        $reqType=I('reqType'); 
        $this->assign("controlName","flo_cap_log");
        $accountResult = $this->moneyAccCom->getList(['fields'=>'id,account','pageSize'=>999999])['list'];
        $this->assign('accountArr',$accountResult);
        $this->assign('capTypeArr',["1"=>"银行存款","2"=>"库存现金","3"=>"保险箱","4"=>"库存现金（含保险箱）"]);
        $this->assign('floatTypeArr',["1"=>"收入","2"=>"支出"]);
        $this->assign('projectArr',$this->Aproject->_getOption("project_id"));
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function flo_cap_log_modalOne(){
        $title = "新增资金明细";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        
        if($gettype=="Edit"){
            $title = "编辑资金明细";
            $btnTitle = "保存数据";
            $redisName = "floCapLogList";
            $resultData = $this->flCapLogCom->redis_one($redisName,"id",$id);
        }

        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"floCapLogModal",
        ];
        $this->modalOne($modalPara);
    }
    function flo_cap_logList(){
        $datas = I("data");
        $p=I("p")?I("p"):1;
        $where=[];
        
        foreach (['project_id','account_id','log_type','float_type'] as $key ) {
            if(isset($datas[$key])){
                if( $key == "log_type" && $datas[$key] == 4){
                    $where[$key] = ['IN',[2,3]];
                }else{
                    $where[$key] = $datas[$key];
                }
            }
        }
        if(isset($datas['happen_time'])){
           $date = explode(" - ",$datas['happen_time']);
           if(count($date)>1){
                $where['happen_time'] = [['egt',strtotime($date[0]." 00:00:00")],['lt',strtotime($date[1]." 23:59:59")]];
           }
        }
        $parameter=[
            'fields'=>"*,FROM_UNIXTIME(happen_time,'%Y-%m-%d %H:%i:%s') happen_time,FROM_UNIXTIME(add_time,'%Y-%m-%d %H:%i:%s') add_time",
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$this->pageSize,
            'orderStr'=>"happen_time DESC",
            "joins"=>[
                "LEFT JOIN (SELECT projectId,code project_code,name project_name FROM v_project ) p ON p.projectId = project_id",
                "LEFT JOIN (SELECT userId,userName user_name FROM v_user) u ON u.userId = user_id",
            ],
        ];
        
        $listResult=$this->flCapLogCom->getList($parameter);
        // print_r($listResult);
        // echo $this->flCapLogCom->M()->_sql();
        $this->tablePage($listResult,'Finance/financeTable/floCapLogList',"floCapLogList",$pageSize);
    }
    function getPcodeOne(){
        extract($_REQUEST);
        
        $result = $this->projectCom->getProject($project_id,"code");
        if($result){
            $this->ajaxReturn(['errCode'=>0,'error'=>getError(0),'data' => $result]);
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    }
    function manageFlCapLogInfo($param){
        $reqType = $param['reqType'] ? $param['reqType'] : I("reqType");
        $datas = $param['data'] ? $param['data'] : I("data");
        if(isset($datas['happen_time'])){
            $datas['happen_time']=strtotime($datas['happen_time']);
        }
        if($reqType=="flo_cap_logAdd"){
            $datas['status']=1;
            $datas['add_time'] = time();
            $datas['user_id'] = session("userId");
            unset($datas['id']);
            return $datas;
        }else if($reqType=="flo_cap_logEdit"){
            $where=["id"=>$datas['id']];
            $data=[];
            foreach (['account_id','log_type','project_id','happen_time','subject','inner_detail','bank_detail','object','float_type','remark','status','proof'] as $key) {
                if(isset($datas[$key])){
                    $data[$key]=$datas[$key];
                }
            }
            return ["where"=>$where,"data"=>$data];
        }
        return "";
    }
    function flo_cap_logAdd(){
        $Info = $this->manageFlCapLogInfo();
        $this->flCapLogCom->startTrans();
        if($Info){
            if($Info['log_type']==1){
                $key = 'bank_stock';
            }elseif($Info['log_type']==2){
                $key = 'cash_stock';
            }elseif($Info['log_type']==3){
                $key = 'strongbox';
            }

            $stockResult = $this->moneyAccCom->getOne(['where'=>['id'=>$Info['account_id']],'fields'=>$key]);
            if($Info['float_type'] == 1){
                $Info['balance'] = $stockResult['list'][$key] + $Info['money'];
            }elseif($Info['float_type'] == 2){
                $Info['balance'] = $stockResult['list'][$key] - $Info['money'];
                if($Info['balance'] < 0){
                    $this->ajaxReturn(['errCode'=>100,'error'=>'账户金额不足。仅剩下：'.$stockResult['list'][$key]]);
                }
            }
            $insertResult = $this->flCapLogCom->insert($Info);
            if($insertResult){
                if($Info['float_type'] == 1){
                    $updateResult = $this->moneyAccCom->M()->where(['id'=>$Info['account_id']])->setInc($key,$Info['money']); 
                }elseif($Info['float_type'] == 2){
                    $updateResult =$this->moneyAccCom->M()->where(['id'=>$Info['account_id']])->setDec($key,$Info['money']); 
                }
                if($updateResult){
                    $this->flCapLogCom->commit();
                    $this->ajaxReturn(['errCode'=>$insertResult->errCode,'error'=>getError($insertResult->errCode)]);
                }
            }
            
        }
        $this->flCapLogCom->rollback();
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    }
    function flo_cap_logEdit(){
        $Info = $this->manageFlCapLogInfo();
        // print_r($Info );
        $updateResult=$this->flCapLogCom->update($Info);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
}