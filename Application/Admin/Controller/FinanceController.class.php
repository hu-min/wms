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
    protected function setStock($stockName){
        $datas=I("data");
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
                        $logInfo["describe"].="将子公司名 原名【".$stockList[$basicId]["name"]."】修改为：【".$value['company']."】;";
                    }
                    if($stockList[$basicId]["alias"] != $value['val']){
                        $logInfo["describe"].="将【{$value['company']}】的原值【".$stockList[$basicId]["alias"]."】修改为：【".$value['val']."】;";
                    }
                    
                }
            }else{
                $Info=[
                    "class"=>$stockName,
                    "name"=>$value['company'],
                    "alias"=>$value['val'],
                ];
                $res= $this->basicCom->insertBasic($Info);
                $logInfo["describe"]="新增{$value['company']}，值为:{$value['val']}";
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
            "templet"=>"fix_expenseModal",
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
}