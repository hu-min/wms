<?php
namespace Admin\Controller;

/** 
 * @Author: vition 
 * @Date: 2018-05-06 09:45:33 
 * @Desc: 成本管理 
 */
class CostController extends BaseController{
    protected $pageSize=15;
    public function _initialize() {
        parent::_initialize();
        $this->projectCom=getComponent('Project');
        $this->project=A('Project');
        $this->configCom=getComponent('Config');
        $this->customerCom=getComponent('Customer');
        $this->costCom=getComponent('Cost');
        $this->debitCom=getComponent('Debit');
        Vendor("levelTree.levelTree");
        $this->levelTree=new \levelTree();
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-16 23:35:32 
     * @Desc: 成本控制 
     */    
    function costControl(){
        $reqType=I('reqType');
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    /** 
     * @Author: vition 
     * @Date: 2018-07-16 00:17:35 
     * @Desc: 借支控制 
     */    
    function debitControl(){
        $reqType=I('reqType');
        $accountType = ["1"=>"现金","2"=>"微信支付","3"=>"支付宝","4"=>"银行卡","5"=>"支票","6"=>"其它"];
        $this->assign('accountType',$accountType);
        $this->assign('projectArr',$this->project->_getOption("project_id"));
        $this->assign("controlName","debit");
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function debitList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $where=[];
        if($this->nodeAuth[CONTROLLER_NAME.'/'.ACTION_NAME]<7){
            $where['user_id'] = session('userId');
        }
        // if($data['expenClas']){
        //     $where['expenClas']=$data['expenClas'];
        // }
        $parameter=[
            'fields'=>"*,FROM_UNIXTIME(debit_date,'%Y-%m-%d') debit_date,FROM_UNIXTIME(require_date,'%Y-%m-%d') require_date,FROM_UNIXTIME(loan_date,'%Y-%m-%d') loan_date",
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$this->pageSize,
            'orderStr'=>"id DESC",
            "joins"=>[
                "LEFT JOIN (SELECT projectId,code,name project_name,FROM_UNIXTIME(project_time,'%Y-%m-%d') project_date,business,leader FROM v_project ) p ON p.projectId = project_id ",
                "LEFT JOIN (SELECT userId,userName business_name FROM v_user) bu ON bu.userId = p.business",
                "LEFT JOIN (SELECT userId,userName leader_name FROM v_user) lu ON lu.userId = p.leader",
                "LEFT JOIN (SELECT basicId,name free_name FROM v_basic WHERE class='feeType') f ON f.basicId=free_type",
            ]
        ];
        $listResult=$this->debitCom->getList($parameter);
        $this->tablePage($listResult,'Cost/costTable/debitList',"debitList");
    }
    function debit_modalOne(){
        $title = "新增借支";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        
        if($gettype=="Edit"){
            $title = "编辑借支";
            $btnTitle = "保存数据";
            $redisName="debitList";
            $resultData=$this->debitCom->redis_one($redisName,"id",$id);
            // $resultData=[];
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"debitModal",
        ];
        $option='<option value="0">费用类别</option>';
        foreach (A("Basic")->getFeeTypeTree() as $key => $value) {
            // print_r($value);
            $option.=A("Basic")->getfeeType($value,0);
        }
        $this->assign("pidoption",$option);
        $this->modalOne($modalPara);
    }
    function manageDebitInfo(){
        $reqType=I("reqType");
        $datas=I("data");
        if($datas["debit_date"] == 0){
            unset($datas["debit_date"] );
        }
        foreach (['require_date'] as  $date) {
            if(isset($datas[$date])){
                $datas[$date]=strtotime($datas[$date]);
            }
        }
        if($reqType=="debitAdd"){
            $datas['add_time']=time();
            $datas['debit_date']=time();
            $datas['user_id']=session('userId');
            $datas['author']=session('userId');
            $datas['processLevel']=$this->processAuth["level"];
            unset($datas['id']);
            return $datas;
        }else if($reqType=="debitEdit"){
            $where=["id"=>$datas['id']];
            $data=[];
            
            foreach (["project_id","user_id","debit_money","debit_date","debit_cause","account","account_type","free_type","require_date","remark","voucher_file","loan_date"] as  $key) {
                if(isset($datas[$key])){
                    $data[$key]=$datas[$key];
                }
            }
            if(isset($datas['status'])){
                $parameter=[
                    'where'=>["id"=>$datas['id']],
                ];
                $result=$this->debitCom->getOne($parameter);
                $data = $this->status_update($result,$datas["status"],$data);
            }
            $data['upate_time']=time();
            
            return ["where"=>$where,"data"=>$data];
        }
        return "";
    }
    function debitAdd(){
        $info=$this->manageDebitInfo();
        if($info){
            // print_r($info);
            $insertResult=$this->debitCom->insert($info);
            if($insertResult && $insertResult->errCode==0){
                $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
            }
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    }
    function debitEdit(){
        $info=$this->manageDebitInfo();
        $updateResult=$this->debitCom->update($info);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
    function getProjectOne(){
        // print_r($option);
        
        $this->ajaxReturn(["data"=>A("Purcha")->getProjectOne()["list"]]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-07-18 01:06:00 
     * @Desc: 借支管理 
     */    
    function finance_debitControl(){
        $reqType=I('reqType');
        $this->assign("controlName","finance_debit");
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function finance_debitList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $where=[];
        $parameter=[
            'fields'=>"*,FROM_UNIXTIME(debit_date,'%Y-%m-%d') debit_date,FROM_UNIXTIME(require_date,'%Y-%m-%d') require_date,FROM_UNIXTIME(loan_date,'%Y-%m-%d') loan_date",
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$this->pageSize,
            'orderStr'=>"id DESC",
            "joins"=>[
                "LEFT JOIN (SELECT projectId,code,name project_name,FROM_UNIXTIME(project_time,'%Y-%m-%d') project_date,business,leader FROM v_project ) p ON p.projectId = project_id ",
                "LEFT JOIN (SELECT userId,userName business_name FROM v_user) bu ON bu.userId = p.business",
                "LEFT JOIN (SELECT userId,userName leader_name FROM v_user) lu ON lu.userId = p.leader",
                "LEFT JOIN (SELECT basicId,name free_name FROM v_basic WHERE class='feeType') f ON f.basicId=free_type",
            ]
        ];
        $listResult=$this->debitCom->getList($parameter);
        $this->tablePage($listResult,'Cost/costTable/financedebitList',"debitList");
    }
    function finance_debit_modalOne(){
        $title = "借支控制";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        
        if($gettype=="Edit"){
            $title = "编辑借支";
            $btnTitle = "保存数据";
            $redisName="finance_debitList";
            // $resultData=$this->fixExpenCom->redis_one($redisName,"id",$id);
            $resultData=[];
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"financedebitModal",
        ];
        $this->modalOne($modalPara);
    }

    function expenseControl(){
        $reqType=I('reqType');
        $this->assign("controlName","expense");
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function expense_modalOne(){
        $title = "个人报销";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        
        if($gettype=="Edit"){
            $title = "编辑报销";
            $btnTitle = "保存数据";
            $redisName="finance_debitList";
            // $resultData=$this->fixExpenCom->redis_one($redisName,"id",$id);
            $resultData=[];
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"expenseModal",
        ];
        $this->modalOne($modalPara);
    }
    function fin_expenseControl(){
        $reqType=I('reqType');
        $this->assign("controlName","fin_expense");
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
}