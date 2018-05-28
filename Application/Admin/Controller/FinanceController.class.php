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
    }
    function stockControl(){
        $reqType=I('reqType');
        if($reqType){
            $this->$reqType();
        }else{
            $this->assign("bankstockList",$this->basicCom->get_class_data("bankstock"));
            $this->assign("cashstockList",$this->basicCom->get_class_data("cashstock"));
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
            $stockList[$stock["name"]]=["basicId"=>$stock["basicId"],"alias"=>$stock["alias"],"addTime"=>time()];
        }
        $logInfo=["userId"=>session("userId"),"userName"=>session("userName"),"class"=>$stockName,"addTime"=>time()];
        foreach ($datas as $company => $value) {
            $res=false;
            if(isset($stockList[$company]["basicId"])){
                if($stockList[$company]["alias"] != $value){
                    $Info=["basicId"=>$stockList[$company]["basicId"],"alias"=>$value];
                    $res= $this->basicCom->updateBasic($Info);
                    $logInfo["describe"]="将{$company}的原始值".$stockList[$company]["alias"]."修改为：".$value;
                }
            }else{
                $Info=[
                    "class"=>$stockName,
                    "name"=>$company,
                    "alias"=>$value,
                ];
                $res= $this->basicCom->insertBasic($Info);
                $logInfo["describe"]="新增{$company}，值为:{$value}";
            }
            if ($res){
                $this->LogCom->insert($logInfo);
                $result=true;
            }
        }
        if($result){
            $this->basicCom->clear_cache($stockName);
            $this->ajaxReturn(['errCode'=>0,'error'=>"更新成功"]);
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>"更新失败"]);
    }
}