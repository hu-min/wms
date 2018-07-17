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
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
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
            // $resultData=$this->fixExpenCom->redis_one($redisName,"id",$id);
            $resultData=[];
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "templet"=>"costModal",
        ];
        $this->modalOne($modalPara);
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
            "templet"=>"purchaModal",
        ];
        $this->modalOne($modalPara);
    }
}