<?php
namespace Admin\Controller;

/** 
 * @Author: vition 
 * @Date: 2018-05-06 09:45:33 
 * @Desc: 成本管理 
 */
class CostController extends BaseController{
    protected $pageSize=10;
    protected $accountType = ["1"=>"现金","2"=>"微信支付","3"=>"支付宝","4"=>"银行卡","5"=>"支票","6"=>"其它"];
    protected $expVouchType = ["无","收据","签收单+身份证","发票","其他"];
    public function _initialize() {

        $this->project=A('Project');
        parent::_initialize();
        $this->projectCom=getComponent('Project');
        
        $this->configCom=getComponent('Config');
        $this->customerCom=getComponent('Customer');
        $this->costCom=getComponent('Cost');
        $this->debitCom=getComponent('Debit');
        $this->expenseCom=getComponent('Expense');
        $this->expenseSubCom=getComponent('ExpenseSub');
        $this->whiteCom=getComponent('White');
        $this->pCostCom=getComponent('ProjectCost');
        Vendor("levelTree.levelTree");
        $this->levelTree=new \levelTree();
        $this->accounts = ["2"=>session("userInfo.wechat"),"3"=>session("userInfo.alipay"),"4"=>session("userInfo.bank_card")];
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
        $this->assign('accountType',$this->accountType);
        $this->assign('projectArr',$this->project->_getOption("project_id"));
        $this->assign("controlName","debit");
        $this->assign("tableName",$this->debitCom->tableName()); 
        $nodeId = getTabId(I("vtabId"));
        $process = $this->nodeCom->getProcess($nodeId);
        $this->assign("place",$process["place"]);
        $this->assign('accounts',json_encode($this->accounts));

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
        
        foreach (['project_name'] as $key ) {
            if(isset($data[$key])){
                $where[$key]=["LIKE","%{$data[$key]}%"];
            }
        }
        foreach (['clear_status'] as $key ) {
            if(isset($data[$key])){
                $where[$key]=$data[$key];
            }
        }
        // if($data['expenClas']){
        //     $where['expenClas']=$data['expenClas'];
        // }
        $pageSize = isset($data['pageSize']) ? $data['pageSize'] : $this->pageSize;
        $parameter=[
            'fields'=>"*,FROM_UNIXTIME(debit_date,'%Y-%m-%d') debit_date,FROM_UNIXTIME(require_date,'%Y-%m-%d') require_date,FROM_UNIXTIME(loan_date,'%Y-%m-%d') loan_date",
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$pageSize,
            'orderStr'=>"id DESC",
            "joins"=>[
                "LEFT JOIN (SELECT projectId,code,name project_name,FROM_UNIXTIME(project_time,'%Y-%m-%d') project_date,business,leader FROM v_project ) p ON p.projectId = project_id ",
                "LEFT JOIN (SELECT userId,userName business_name FROM v_user) bu ON bu.userId = p.business",
                "LEFT JOIN (SELECT userId,userName leader_name FROM v_user) lu ON lu.userId = p.leader",
                "LEFT JOIN (SELECT basicId,name free_name FROM v_basic WHERE class='feeType') f ON f.basicId=free_type",
                "LEFT JOIN (SELECT table_id tid , SUBSTRING_INDEX( GROUP_CONCAT(user_id),',',-1) tuserid,SUBSTRING_INDEX(GROUP_CONCAT(remark),',',-1) aremark FROM v_approve_log WHERE status > 0 AND effect = 1 AND table_name ='".$this->debitCom->tableName()."' GROUP BY table_id ORDER BY add_time DESC) ap ON ap.tid=id",
                "LEFT JOIN (SELECT userId auser_id,userName approve_name FROM v_user) au ON au.auser_id = ap.tuserid",
            ]
        ];
        $listResult=$this->debitCom->getList($parameter);
        $this->tablePage($listResult,'Cost/costTable/debitList',"debitList",$pageSize);
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
        // print_r(A("Basic")->getFeeTypeTree());exit;
        foreach (A("Basic")->getFeeTypeTree() as $key => $value) {
            $option.=A("Basic")->getfeeType($value,0);
        }
        $this->assign("pidoption",$option);
        $this->modalOne($modalPara);
    }
    function manageDebitInfo(){
        $reqType=I("reqType");
        $datas=I("data");
        if($datas["project_id"]>0){
            if($reqType=="debitEdit"){
                $ids = [$datas["id"]];
                $dbCom = "debit";
            }else{
                $dbCom="";
                $ids=[];
            }
            $this->projectCom->checkCost($datas["project_id"],$datas["debit_money"],$dbCom,$ids);
            // $costBudget = $this->projectCom->getCostBudget($datas["project_id"]);
            // $allCost = $this->projectCom->getCosts($datas["project_id"]);
            // // print_r($allCost);
            // $array_column = array_sum(array_column($datas,'contract_amount'));
            // if(($array_column+$allCost['allCost']) > $costBudget){
            //     //<p>其中已批准成本：【'.$allCost['active'].'】</p><p>其中其他状态成本：【'.$allCost['waiting'].'】</p>
            //     $html='<p>成本预算超支:</p><p>该项目立项成本预算【'.$costBudget.'】</p><p>当前使用已使用成本：【'.$allCost['allCost'].'】</p><p>请联系管理员修改成本预算</p>';
            //     $this->ajaxReturn(['errCode'=>77,'error'=>$html]);
            // }
        }
        $roleId = session("roleId");
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

            //添加时必备数据
            $examines = getComponent('Process')->getExamine(I("vtabId"),$datas['leader']);
            // $process = $this->nodeCom->getProcess(I("vtabId"));
            $datas['process_id'] = $examines["process_id"];
            $datas['examine'] = $examines["examine"];
            // $datas['process_id'] = $process["processId"];
            if($datas["project_id"]>0){
                // print_r($datas);exit;
                //存在项目，则第一个审批的人是项目主管,examine需要
                // $userRole = $this->userCom->getUserInfo($datas['leader']);
                // $datas['examine'] = implode(",",array_unique(explode(",",$userRole['roleId'].",".$process["examine"]))) ;
                unset($datas['leader']);
            }else{
                // $datas['examine'] = $process["examine"];
            }
            // $examineArr = explode(",",$datas['examine']);
            // $rolePlace = search_last_key($roleId,$examineArr);
            $rolePlace = $examines['place'];
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
            //如果自己处于某个申请阶段，直接跳过下级;
            // $datas['process_level']=$this->processAuth["level"];
            $datas['examine'] = getComponent('Process')->filterExamine(session('roleId'),$datas['examine']);
            unset($datas['id']);
            return $datas;
        }else if($reqType=="debitEdit"){
            $where=["id"=>$datas['id']];
            $data=[];
            
            foreach (["project_id","user_id","debit_money","debit_date","debit_cause","account","account_type","free_type","require_date","remark","voucher_file","loan_date",'status'] as  $key) {
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
    /** 
     * @Author: vition 
     * @Date: 2018-09-07 09:25:06 
     * @Desc: 新增借支 
     */    
    function debitAdd(){
        $info=$this->manageDebitInfo();
        if($info){
            
            $insertResult=$this->debitCom->insert($info);
            if(isset($insertResult->errCode) && $insertResult->errCode==0){
                //检查下一个审批者是否存在白名单中，和当前用户判断，如果当前用户在白名单中，指定用户未在白名单中将不会发送信息
                $touserRoleId = explode(',',$info['examine'])[0];
                $limitWhite = $this->whiteCom->limitWhite(session('roleId'),$touserRoleId,true);
                if(!$limitWhite){
                    $touser = $this->userCom->getQiyeId($touserId,true);
                    if(!empty($touser)){
                        $desc = "<div class=\"gray\">".date("Y年m月d日",time())."</div> <div class=\"normal\">".session('userName')."申请借支，@你了，点击进入审批吧！</div>";
                        $url = C('qiye_url')."/Admin/Index/Main.html?action=Cost/debitControl";
                        $msgResult = $this->QiyeCom-> textcard($touser,session('userName')."申请了借支",$desc,$url);
                    }
                }
               
                
                $this->ApprLogCom->createApp($this->debitCom->tableName(),$insertResult->data,session("userId"),"");
                $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
            }
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-09-07 09:25:14 
     * @Desc: 编辑借支 
     */    
    function debitEdit(){
        $updateInfo=$this->manageDebitInfo();
        $updateResult=$this->debitCom->update($updateInfo);
        if(isset($updateResult->errCode) && $updateResult->errCode == 0){
            $this->ApprLogCom->updateStatus($this->debitCom->tableName(),$updateInfo["where"]["id"]); 
        }
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
    function getProjectOne(){
      
        $data = A("Purcha")->getProjectOne(true);

        $data['modules'] = [];
        $param = [
            'fields' => 'cs.classify classify,SUM(cs.cost_total) cost_total,mname',
            'page'=>1,
            'pageSize'=>999999999,
            'where' => ['project_id'=> $data['projectId']],
            'groupBy' => 'cs.classify',
            'isCount' => false,
            'joins' => [
                'LEFT JOIN (SELECT parent_id,cost_class,classify,cost_total FROM v_project_cost_sub WHERE scompany_id = 0) cs ON cs.parent_id = id',
                'LEFT JOIN (SELECT basicId cid,name mname FROM v_basic WHERE class="module") m ON m.cid = cs.classify',
            ],
        ];
        $result = $this->pCostCom->getList($param);
        if($result){
            $data['modules'] = $result['list'];
        }
        $this->ajaxReturn(["data" => $data]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-07-18 01:06:00 
     * @Desc: 借支管理 
     */    
    function finance_debitControl(){
        $reqType=I('reqType');
        $this->assign('accountType',$this->accountType);
        $this->assign('projectArr',$this->project->_getOption("project_id"));

        $this->assign("controlName","finance_debit");
        $this->assign("tableName",$this->debitCom->tableName()); 
        // $nodeId = getTabId(I("vtabId"));
        // $process = $this->nodeCom->getProcess($nodeId);
        // $this->assign("place",$process["place"]);
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function finance_debitList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $nodeId = getTabId(I("vtabId"));
        $process = $this->nodeCom->getProcess($nodeId);
        $this->assign("place",$process["place"]);
        $where=[];
        $whites = $this->whiteCom->getWhites();
        if($whites){
            $where = ["user_id"=>["NOT IN",$whites]];
        }
        $roleId = session('roleId');
        if($this->nodeAuth[CONTROLLER_NAME.'/'.ACTION_NAME]<7){
            $where["_string"] = "FIND_IN_SET({$roleId},examine) <= process_level AND FIND_IN_SET({$roleId},examine) > 0";
            if($process["place"]>0){
                // $where=["process_level"=>''];
                // $where=["process_level"=>[["eq",($process["place"]-1)],["egt",($process["place"])],"OR"],"status"=>1,'_logic'=>'OR'];
            }else{
                // $where=["status"=>1];
            }
        }
        $pageSize = isset($data['pageSize']) ? $data['pageSize'] : $this->pageSize;
        $parameter=[
            'fields'=>"*,FROM_UNIXTIME(debit_date,'%Y-%m-%d') debit_date,FROM_UNIXTIME(require_date,'%Y-%m-%d') require_date,FROM_UNIXTIME(loan_date,'%Y-%m-%d') loan_date,FIND_IN_SET({$roleId},examine) place",
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$pageSize ,
            'orderStr'=>"id DESC",
            "joins"=>[
                "LEFT JOIN (SELECT projectId,code,name project_name,FROM_UNIXTIME(project_time,'%Y-%m-%d') project_date,business,leader FROM v_project ) p ON p.projectId = project_id ",
                "LEFT JOIN (SELECT userId,userName user_name FROM v_user) un ON un.userId = user_id",
                "LEFT JOIN (SELECT userId,userName business_name FROM v_user) bu ON bu.userId = p.business",
                "LEFT JOIN (SELECT userId,userName leader_name FROM v_user) lu ON lu.userId = p.leader",
                "LEFT JOIN (SELECT basicId,name free_name FROM v_basic WHERE class='feeType') f ON f.basicId=free_type",
            ]
        ];
        $listResult=$this->debitCom->getList($parameter);
        $this->tablePage($listResult,'Cost/costTable/financedebitList',"finance_debitList",$pageSize );
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
            $resultData=$this->debitCom->redis_one($redisName,"id",$id);
            // $resultData=$this->fixExpenCom->redis_one($redisName,"id",$id);
            // $resultData=[];
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"financedebitModal",
        ];
        $this->modalOne($modalPara);
    }

    //个人报销开始
    function expenseControl(){
        $reqType=I('reqType');
        $this->assign("controlName","expense");
        $this->assign("tableName",$this->expenseSubCom->tableName());
        $this->assign('accountType',$this->accountType);
        $this->assign('projectArr',$this->project->_getOption("project_id"));
        $this->assign('expTypeArr',$this->project->_getOption("expense_type"));
        $this->assign('expVouchType',$this->expVouchType);
        $this->assign('accounts',json_encode($this->accounts));
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
        $this->assign("provinceArr",$this->basicCom->get_provinces());
        $option='<option value="0">费用类别</option>';
        foreach (A("Basic")->getFeeTypeTree() as $key => $value) {
            $option.=A("Basic")->getfeeType($value,0);
        }
        $this->assign("pidoption",$option);

        if($gettype=="Edit"){
            $title = "编辑报销";
            $btnTitle = "保存数据";
            $redisName="expenseList";
            $resultData=$this->expenseCom->redis_one($redisName,"id",$id);
        }
        if($resultData){
            $resultData["tableData"] = [];
            $subExpRes = $this->expenseSubCom->getList(["where"=>["parent_id"=>$id],"fields"=>"*,FROM_UNIXTIME(happen_date,'%Y-%m-%d') happen_date",'joins'=>["LEFT JOIN (SELECT cid ctid ,city city_name,pid cpid FROM v_city ) ct ON ct.ctid = city","LEFT JOIN (SELECT table_id tid , SUBSTRING_INDEX( GROUP_CONCAT(user_id),',',-1) tuserid,SUBSTRING_INDEX(GROUP_CONCAT(remark),',',-1) aremark FROM v_approve_log WHERE status > 0 AND effect = 1 AND table_name ='".$this->expenseSubCom->tableName()."' GROUP BY table_id ORDER BY add_time DESC) ap ON ap.tid=id","LEFT JOIN (SELECT userId auser_id,userName approve_name FROM v_user) au ON au.auser_id = ap.tuserid",]])["list"];
            // echo $this->expenseSubCom->M()->_sql();exit;
            foreach ($subExpRes as $key => $subInfo) {
                $subExpRes[$key]["citys"] = $this->basicCom->get_citys($subInfo["cpid"]);
            }
            $resultData["tableData"]["expense-list"] = ["list"=>$subExpRes,"template"=>$this->fetch('Cost/costTable/expenseLi')];
        }
        
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"expenseModal",
        ];
        $this->modalOne($modalPara);
    }
    function expenseList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $this->assign("tableName",$this->expenseCom->tableName());
        $where=[];
        if($this->nodeAuth[CONTROLLER_NAME.'/'.ACTION_NAME]<7){
            // print_r(session('userId'));
            $where['user_id'] = session('userId');
        }
        foreach (['project_name'] as $key ) {
            if(isset($data[$key])){
                $where[$key]=["LIKE","%{$data[$key]}%"];
            }
        }
        foreach (['clear_status'] as $key ) {
            if(isset($data[$key])){
                $where[$key]=$data[$key];
            }
        }
        $pageSize = isset($data['pageSize']) ? $data['pageSize'] : $this->pageSize;
        $parameter=[
            'where'=>$where,
            'fields'=>"*,FROM_UNIXTIME(add_time,'%Y-%m-%d') add_date ",
            'page'=>$p,
            'pageSize'=>$pageSize,
            'orderStr'=>"id DESC",
            "joins"=>[
                "LEFT JOIN (SELECT projectId,code,name project_name,FROM_UNIXTIME(project_time,'%Y-%m-%d') project_date,business,leader FROM v_project ) p ON p.projectId = project_id ",
                "LEFT JOIN (SELECT userId buser_id,userName business_name FROM v_user) bu ON bu.buser_id = p.business",
                "LEFT JOIN (SELECT userId luser_id,userName leader_name FROM v_user) lu ON lu.luser_id = p.leader",
                "LEFT JOIN (SELECT parent_id,count(*) all_item,SUM(money) all_money FROM v_expense_sub GROUP BY parent_id ) c ON parent_id = id",
            ],
        ];
        
        $listResult=$this->expenseCom->getList($parameter);
        $this->tablePage($listResult,'Cost/costTable/expenseList',"expenseList",$pageSize);
    }
    function expenseManage($datas,$reqType=false){
        $reqType = $reqType ? $reqType : I("reqType");
        foreach (["happen_date"] as $key) {
            $datas[$key] = isset($datas[$key]) && !empty($datas[$key]) ? strtotime($datas[$key]) : time();
        }
        if($reqType=="expenseAdd"){
            $datas['add_time']=time();
            $datas['user_id']=session('userId');
            

            unset($datas['id']);
            return $datas;
        }else if($reqType=="expenseEdit"){
            $where=["id"=>$datas['id']];
            $data=[];
            $data['updateTime']=time();
            foreach (["id","account","account_type","cost_desc","expen_vouch_type","expense_type","happen_date","money","remark","vouch_file","fee_type","city","status"] as $key) {
                if(isset($datas[$key])){
                    $data[$key] = $datas[$key];
                } 
            }
            // if(isset($datas['status'])){
                // $parameter=[
                //     'where'=>["id"=>$datas['id']],
                // ];
                // $result=$this->purchaCom->getList($parameter,true);
                // $data = $this->status_update($result,$datas["status"],$data);
            // }
            return ["where"=>$where,"data"=>$data];
        }
        return "";
    }
    function expenseAdd(){
        $datas=I("data");
        $project_id=I("project_id");
        $leader=I("leader");
        
        $expInfo = [
            "project_id"=>$project_id,
            "user_id"=>session("userId"),
            "add_time"=>time(),
        ];
        //添加时必备数据
        $examines = getComponent('Process')->getExamine(I("vtabId"),$leader);
        // print_r($examines);
        // exit;
        // $process = $this->nodeCom->getProcess(I("vtabId"));
        // $expInfo['process_id'] = $process["processId"];
        $expInfo['process_id'] = $examines["process_id"];
        // $expInfo['examine'] = $examines["examine"];
        $expInfo['examine'] = getComponent('Process')->filterExamine(session('roleId'),$examines['examine']);
        if($expInfo["project_id"]>0){ 
            //检查成本预算是否超支
            $this->projectCom->checkCost($expInfo["project_id"],array_sum(array_column($datas["expense-list"],'money')));
            //存在项目，则第一个审批的人是项目主管,examine需要
            // $userRole = $this->userCom->getUserInfo($leader);
            // $expInfo['examine'] = implode(",",array_unique(explode(",",$userRole['roleId'].",".$process["examine"]))) ;
            // unset($expInfo['leader']);
        }else{
            // $expInfo['examine'] = $process["examine"];
        }
        //如果是审批者自己提交的执行下列代码
        $roleId = session("roleId");
        // $examineArr = explode(",",$expInfo['examine']);
        // $rolePlace = search_last_key($roleId,$examineArr);
        $rolePlace = $examines['place'];
        $expInfo['status'] = 0;
        if($rolePlace!==false){
            $expInfo['process_level']=$rolePlace+2;
            if(count(explode(",",$examines['examine'])) <= ($rolePlace+1)){
                $expInfo['status'] = 1;
            }else{
                $expInfo['status'] = 2;
            }
        }else{
            $expInfo['process_level']=$process["place"] > 0 ? $process["place"] : 1;
        }
        
        // $expInfo['process_level']=$process["place"];

        $isInsert = false;
        // print_r($expInfo);
        // $dataInfo = $this->expenseManage($datas["expense-list"][0]);
        // print_r($dataInfo);
        // exit;
        $insertRes = $this->expenseCom->insert($expInfo);
        if($insertRes->errCode==0){
            foreach ($datas["expense-list"] as $subExpInfo) {
                $dataInfo = $this->expenseManage($subExpInfo);
                $dataInfo["parent_id"] = $insertRes->data;
                $dataInfo["examine"] = $expInfo['examine'];
                $dataInfo["status"] = $expInfo['status'];
                $dataInfo['process_level'] = $expInfo["process_level"];
                if($dataInfo){
                    $insertResult=$this->expenseSubCom->insert($dataInfo); 
                    $this->ApprLogCom->createApp($this->expenseSubCom->tableName(),$insertResult->data,session("userId"),"");
                    $isInsert = true;
                }
            }
            if($isInsert){
                $touser = $this->userCom->getQiyeId(explode(',',$examines["examine"])[0],true);
                if(!empty($touser)){
                    $desc = "<div class='gray'>".date("Y年m月d日",time())."</div> <div class='normal'>".session('userName')."申请报销，@你了，点击进入审批吧！</div>";
                    $url = C('qiye_url')."/Admin/Index/Main.html?action=Cost/expenseControl";
                    $msgResult = $this->QiyeCom-> textcard($touser,session('userName')."申请了报销",$desc,$url);
                }
                $this->ajaxReturn(['errCode'=>0,'error'=>"添加成功"]);
            }
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    }
    function expenseEdit(){
        $datas=I("data");
        $project_id=I("project_id");
        if($project_id>0){
            $ids = array_column($datas["expense-list"],'id');
            $dbCom = "expense";
            $this->projectCom->checkCost($project_id,array_sum(array_column($datas["expense-list"],'money')),$dbCom,$ids);
        }
        $expense_id=I("expense_id");
        $this->expenseCom->update(["where"=>["id"=>$project_id],"data"=>["update"=>time()]]);
        $isUpdate =false;
        if($insertRes->errCode==0){
            foreach ($datas["expense-list"] as $subExpInfo) {
                if($subExpInfo["id"]>0){
                    $dataInfo = $this->expenseManage($subExpInfo);
                    if($dataInfo){
                        $insertResult=$this->expenseSubCom->update($dataInfo);
                        $this->ApprLogCom->updateStatus($this->expenseSubCom->tableName(),$subExpInfo["id"],$expense_id);
                        $isUpdate = true;
                        //这里需要判断状态是否改过来了
                    }
                }else{
                    $dataInfo = $this->expenseManage($subExpInfo,"expenseAdd");
                    $dataInfo["parent_id"] = $expense_id;
                    if($dataInfo){
                        $insertResult=$this->expenseSubCom->insert($dataInfo);
                        $isUpdate = true;
                    }
                }
            }
            if($isUpdate){
                $this->ajaxReturn(['errCode'=>0,'error'=>"修改成功"]);
            }
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    }
    function feeLimitOne(){
        $datas = I("data");
        // print_r($datas);
        if($datas['feeType']>0){
            $param = [
                'where' => ["basicId"=>$datas['feeType']],
            ];
            $feeRes = $this->basicCom->getOne($param)['list'];
            $baseLimit = $feeRes['remark'];
            $where = ["class"=>'regLimit','pId'=>$datas['feeType']];
            if($datas['city']){
                $where['_string'] = "FIND_IN_SET({$datas['city']},name)";
            }
            $param = [
                'where' => $where,
                'fields' => 'remark limit_money',
            ];
            $reLimit = $this->basicCom->getOne($param)['list']['limit_money'];
            $limit_money = $reLimit > 0 ? $reLimit : $baseLimit;
            if(($datas['money']>$limit_money && $limit_money > 0) || ($datas['money']>100000 && $limit_money <= 0)){
                $limit_money = $limit_money > 0 ? $limit_money : 100000;
                $this->ajaxReturn(['errCode'=>100,'error'=>$feeRes['name'].'报销金额不能超过'.$limit_money,'data'=>['limit_money'=>$limit_money]]);
            }else{
                $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
            }
        }
        $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
    }
    //个人报销结束
    //财务报销管理开始
    function fin_expenseControl(){
        $reqType=I('reqType');
        $this->assign("controlName","fin_expense");
        $this->assign("tableName",$this->expenseSubCom->tableName());
        $this->assign('projectArr',$this->project->_getOption("project_id"));
        $this->assign('userArr',$this->project->_getOption("create_user"));
        $this->assign('accountType',$this->accountType);
        $this->assign('expTypeArr',$this->project->_getOption("expense_type"));
        $this->assign('expVouchType',$this->expVouchType);
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function fin_expenseList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $this->assign("tableName",$this->expenseCom->tableName());
        $whites = $this->whiteCom->getWhites();
        if($whites){
            $where = ["user_id"=>["NOT IN",$whites]];
        }
        // $nodeId = getTabId(I("vtabId"));
        // $process = $this->nodeCom->getProcess($nodeId);
        $roleId = session('roleId');
        // print_r($process);exit;
        // $map['process_level']  = [["eq",($process["place"]-1)],["gt",$process["place"]],"OR"];
        // $map['title']  = array('like','%thinkphp%');
        // $map['_logic'] = 'or';
        if($this->nodeAuth[CONTROLLER_NAME.'/'.ACTION_NAME]<7){
            $where["_string"] = "FIND_IN_SET({$roleId},examine) <= process_level AND FIND_IN_SET({$roleId},examine) > 0";
            // if($process["place"]>0){
            //     $where=["process_level"=>[["eq",($process["place"]-1)],["egt",($process["place"])],"OR"],"status"=>1,'_logic'=>'OR'];
            // }else{
            //     $where=["status"=>1];
            // }
        }
        
        $pageSize = isset($data['pageSize']) ? $data['pageSize'] : $this->pageSize;
        $parameter=[
            'where'=>$where,
            'fields'=>"*,FROM_UNIXTIME(add_time,'%Y-%m-%d') add_date",
            'page'=>$p,
            'pageSize'=>$pageSize,
            'orderStr'=>"id DESC",
            "joins"=>[
                // "LEFT JOIN (SELECT id pId , project_id,user_id FROM v_expense) e ON e.pId = parent_id",
                "LEFT JOIN (SELECT projectId,code,name project_name,FROM_UNIXTIME(project_time,'%Y-%m-%d') project_date,business,leader FROM v_project ) p ON p.projectId = project_id ",
                "LEFT JOIN (SELECT userId ,userName user_name FROM v_user) u ON u.userId = user_id",
                "LEFT JOIN (SELECT userId ,userName business_name FROM v_user) bu ON bu.userId = p.business",
                "LEFT JOIN (SELECT userId ,userName leader_name FROM v_user) lu ON lu.userId = p.leader",
                "LEFT JOIN (SELECT parent_id,count(*) all_item, SUM(money) all_money FROM v_expense_sub GROUP BY parent_id ) c ON parent_id = id",
            ],
        ];
        
        //"LEFT JOIN (SELECT GROUP_CONCAT(table_id ORDER BY add_time DESC) aid ,GROUP_CONCAT(status ORDER BY add_time DESC) last_status FROM v_approve_log WHERE table_name = '".$this->expenseSubCom->tableName()."' GROUP BY table_id) al ON al.aid = id",
        $listResult=$this->expenseCom->getList($parameter);
        // print_r($listResult);exit;
        $this->tablePage($listResult,'Cost/costTable/fin_expenseList',"fin_expenseList",$pageSize);
    }
    function fin_expense_modalOne(){
        $title = "报销管理";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        $roleId = session('roleId');
        $nodeId = getTabId(I("vtabId"));
        $this->assign("provinceArr",$this->basicCom->get_provinces());
        $option='<option value="0">费用类别</option>';
        foreach (A("Basic")->getFeeTypeTree() as $key => $value) {
            $option.=A("Basic")->getfeeType($value,0);
        }
        $this->assign("pidoption",$option);
        // print_r($process);
        if($gettype=="Edit"){
            $title = "编辑报销";
            $btnTitle = "保存数据";
            $redisName="fin_expenseList";
            $resultData=$this->expenseCom->redis_one($redisName,"id",$id);
        }
        if($resultData){
            $resultData["tableData"] = [];
            $resultData["process"] = $this->nodeCom->getProcess($nodeId);
            $subPar=[
                "where" => ["parent_id"=>$id],
                "fields"=> "*,FROM_UNIXTIME(happen_date,'%Y-%m-%d') happen_date,FIND_IN_SET({$roleId},examine) place",
                "joins" =>[
                    "LEFT JOIN (SELECT table_id,status a_status FROM v_approve_log WHERE table_name = '".$this->expenseSubCom->tableName()."' AND user_id = ".session("userId").") ap ON ap.table_id = id",
                    "LEFT JOIN (SELECT cid ctid ,city city_name,pid cpid FROM v_city ) ct ON ct.ctid = city",
                    "LEFT JOIN (SELECT GROUP_CONCAT(table_id ORDER BY add_time DESC) aid ,GROUP_CONCAT(status ORDER BY add_time DESC) last_status FROM v_approve_log WHERE table_name = '".$this->expenseSubCom->tableName()."' GROUP BY table_id) al ON al.aid = id",
                ],
            ];
            $subExpRes = $this->expenseSubCom->getList($subPar)["list"];
            foreach ($subExpRes as $key => $subInfo) {
                $subExpRes[$key]["citys"] = $this->basicCom->get_citys($subInfo["cpid"]);
            }
            $resultData["tableData"]["expense-list"] = ["list"=>$subExpRes,"template"=>$this->fetch('Cost/costTable/expenseLi')];
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"fin_expenseModal",
        ];
        $this->modalOne($modalPara);
    }
    function getExpenseLiOne(){
        $rows = I("rows");
        $this->assign("provinceArr",$this->basicCom->get_provinces());
        $option='<option value="0">费用类别</option>';
        foreach (A("Basic")->getFeeTypeTree() as $key => $value) {
            $option.=A("Basic")->getfeeType($value,0);
        }
        $this->assign("pidoption",$option);
        $this->assign('rows',$rows);
        $html=$this->fetch('Cost/costTable/expenseLi');
        $this->ajaxReturn(['html'=>$html]);
    }
    //财务报销管理结束
}