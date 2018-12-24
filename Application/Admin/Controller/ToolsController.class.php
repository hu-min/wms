<?php
namespace Admin\Controller;

/** 
 * @Author: vition 
 * @Date: 2018-05-06 09:45:33 
 * @Desc: 工具类 
 */
class ToolsController extends BaseController{

    /** 
     * @Author: vition 
     * @Date: 2018-08-20 12:19:30 
     * @Desc: 查看pdf文件 
     */    
    function viewPdf(){
        $title="查看pdf文件";
        $src = I("src");
        $this->assign("title",$title);
        $this->assign("pdfsrc",$src);
        $this->returnHtml();
    }
    /** 
     * @Author: vition 
     * @Date: 2018-08-20 12:19:38 
     * @Desc: 获取审核记录 
     */    
    function getApproveList(){
        extract($_REQUEST);
        $this->approveCom=getComponent('ApproveLog');
        if(!$id){
            $this->ajaxReturn(['errCode'=>115,'error'=>getError(115)."；id不存在哦！"]);
        }
        $nodeId = getTabId($vtabId);
        $processResult = $this->nodeCom->getOne(["fields"=>"processIds,processOption","where"=>["nodeId"=> $nodeId],"joins"=>["LEFT JOIN (SELECT processId,processOption FROM v_process) p ON p.processId = processIds"] ]);
        $allProcess = 1;
        if($processResult){
            $process = json_decode($processResult["list"]["processOption"],true);
            $allProcess = count($process);
        }
        $statusSql="";
        foreach ($this->statusType as $key => $value) {
            $statusSql.=" WHEN {$key} THEN '{$value}' ";
        }
        $parameter=[
            "where" => ["table_name"=>$table,"table_id"=>$id,"effect"=>1],
            "fields" => "*, FROM_UNIXTIME(add_time,'%Y-%m-%d %H:%i:%s') add_time,CASE status {$statusSql} ELSE '无效' END state",
            "joins" => [
                "LEFT JOIN (SELECT userId,userName user_name,roleId FROM v_user) u ON u.userId = user_id",
                "LEFT JOIN (SELECT roleId role_id,roleName role_name FROM v_role) r ON r.role_id = u.roleId",
            ],
            "orderStr" => "add_time DESC",
        ];
        $resultData = $this->approveCom->getList($parameter);
        $appUserList = [];
        if($resultData){
            foreach ($resultData['list'] as $info) {
                if($info['status'] == 1){
                    array_push($appUserList,$info['user_id']);
                }
            }
        }
        $db = M($table,NULL);
        $examineRes = $db ->field("process_level,examine,status")->where([$db->getPk()=>$id])->find();
        $examine = explode(",",$examineRes["examine"]);
        $allProcess = count($examine);
        
        $allApprove = $this->userCom->getList(['where'=>['roleId'=>['IN',$examine]]])['count'];
        // $this->log($this->userCom->_sql());
        if(in_array($examineRes["status"],[3,5])){
            $nextExamine = "已".$this->statusType[$examineRes["status"]];
        }else{

            if(($examineRes["process_level"]-1) == $allProcess || $examineRes["status"] ==1 ){
                $nextExamine = "已完成";
            }else{
                if($examineRes["process_level"]>0){
                    $nextRoleId = $examine[$examineRes["process_level"]-1];
                }else{
                    $nextRoleId = $examine[0];
                }
                
                if($nextRoleId==session("roleId")){
                    // $this->log($appUserList);
                    if(in_array(session("userId"),$appUserList)){
                        $userParam = [
                            'fields' => 'userId,roleId,userName,roleName',
                            'where' => ['roleId'=>session("roleId"),'status'=>1,"userId"=>["neq",session("userId")]],
                            'joins' => [
                                'LEFT JOIN (SELECT roleId role_id,roleName FROM v_role WHERE status = 1) r ON r.role_id = roleId'
                            ]
                        ];
                        $userResult = $this->userCom->getList($userParam);
                        if($userResult){
                            if($userResult['count']>1){
                                foreach ($userResult['list'] as $userInfo) {
                                    $nextExamine .= $userInfo["userName"]."、";
                                }
                                $nextExamine = rtrim($nextExamine,"、");
                                $nextExamine ="【{$userInfo['roleName']}】".$nextExamine;
                            }else{
                                $nextExamine = $userResult['list'][0]["userName"]."【".$userResult['list'][0]['roleName']."】";
                            }
                        }
                    }else{
                        $nextExamine = "说的就是你啊！";
                    }

                }else{

                    $nextExamine = '';
                    $process_level = $examineRes["process_level"] - 1;
                    do {
                        $userParam = [
                            'fields' => 'roleId,userName,roleName',
                            'where' => ['roleId'=>$nextRoleId,'status'=>1],
                            'joins' => [
                                'LEFT JOIN (SELECT roleId role_id,roleName FROM v_role WHERE status = 1) r ON r.role_id = roleId'
                            ]
                        ];
                        $userResult = $this->userCom->getList($userParam);
                        $process_level ++ ;
                        $nextRoleId = $examine[$process_level];
                        
                    } while ($process_level < $allProcess && !$userResult);
        
                    if($userResult){
                        if($userResult['count']>1){
                            foreach ($userResult['list'] as $userInfo) {
                                $nextExamine .= $userInfo["userName"]."、";
                            }
                            $nextExamine = rtrim($nextExamine,"、");
                            $nextExamine ="【{$userInfo['roleName']}】".$nextExamine;
                        }else{
                            if($userResult['list'][0]["roleId"] == session("roleId")){
                                $nextExamine = "说的就是你啊！";
                            }else{
                                $nextExamine = $userResult['list'][0]["userName"]."【".$userResult['list'][0]['roleName']."】";
                            }
                        }
                    }else{
                        $nextExamine = "没有人可以审批";
                    }
                    
                }
            }
        }
        
        // $this->log($nextExamine);
        if($resultData && !empty($resultData["list"])){
            $this->ajaxReturn(['errCode'=>0,'error'=>getError(0),"data"=>$resultData["list"],"allProcess"=>$allProcess,"nextExamine"=>$nextExamine,'allApprove'=>$allApprove]);
        }
        $this->ajaxReturn(['errCode'=>115,'error'=>getError(115)."；可能是系统生成所以无记录","allProcess"=>$allProcess,"nextExamine"=>$nextExamine,'allApprove'=>$allApprove]);
        
    }
    /** 
     * @Author: vition 
     * @Date: 2018-09-19 14:48:17 
     * @Desc: 审核 
     */    
    function approveEdit(){
        extract($_POST);
        $this->nodeCom=getComponent('Node');
        if($table=="v_expense_sub"){
            $tableInfo = $this->nodeCom->getOne(['db_table'=>"v_expense","_string" => "FIND_IN_SET(2,nodeType) > 0"]);
        }else{
            $tableInfo = $this->nodeCom->getOne(['db_table'=>$table,"_string" => "FIND_IN_SET(2,nodeType) > 0"]);
        }
        if(!$tableInfo){
            $this->ajaxReturn(['errCode'=>100,'error'=>'当前数据表异常，请联系管理员']);
        }
        
        $title = $tableInfo['nodeTitle'];
        $controller = $tableInfo['controller'];
        
        // table:v_project
        // id:13
        // remark:尝试着驳回
        // status:3
        // vtabId:#vtabs57
        
        $db = M($table,NULL);
        $fields = $db->getDbFields();
        if(in_array("bind_id",$fields)){
            $bind_id = $db -> where(["id"=>$id])->find()['bind_id'];
            $idResult = $db -> where(['bind_id'=>$bind_id])->select();
            if($idResult){
                $tableIds = array_column($idResult,'id');
            }
        }
      
        $tableId = $tableId ? $tableId : $id;
        $tableIds = isset($tableIds) ? $tableIds : false;
        $this->approveCom=getComponent('ApproveLog');
        // $allItem = $db ->where(["parent_id"=>$tableId])->count();
        // print_r($allItem);exit;
        
        $userId = session('userId');
        $roleId = session('roleId');
        


        $userParam = [
            'fields' => 'userId,roleId,userName,roleName',
            'where' => ['roleId'=>$roleId,'status'=>1],
            'joins' => [
                'LEFT JOIN (SELECT roleId role_id,roleName FROM v_role WHERE status = 1) r ON r.role_id = roleId'
            ]
        ];
        $userResult = $this->userCom->getList($userParam);
        $userIds = $userResult ? array_column($userResult['list'],'userId') : [];
        



        //1,先执行插入审批记录
        $parameter=[
            "table_name" => $table,
            "table_id" => $id,
            "add_time" => time(),
            "user_id" => $userId,
            "status" => $status, //审批流程里的状态是实际状态
            "remark" => $remark,
            'effect' => 1,
        ];
        $hasParam = [
            'fields' => 'user_id',
            'where'=>["table_name" => $table,"table_id" => $id,"user_id"=>["IN",$userIds],'effect' => 1,],
        ];
        
        $hasApp = $this->approveCom->getList($hasParam);
        $appUsers = $hasApp ? array_column($hasApp['list'],'user_id') : [];
        if(in_array($userId,$appUsers)){
            //防止频繁操作
            $this->ajaxReturn(['errCode'=>409,'error'=>getError(409)."您已经执行过审批，请不要重复操作"]);
        }
        $diff = array_diff($userIds,$appUsers);
        
        
        $this->approveCom->M()->startTrans();
        if($tableIds){
            foreach ($tableIds as $tid) {
                $parameter['table_id'] = $tid;
                $insertRes = $this->approveCom->insert($parameter);
            }
        }else{
            $insertRes = $this->approveCom->insert($parameter);
        }
        

        if(count($diff)>1 && $status ==1 ){
            $this->approveCom->commit();//如果同一个角色多个审批者必须要达到所有审批者都审核通过才可以执行下面一步，必须是状态为批准，
            $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
        }
        $db ->startTrans();

        if(isset($insertRes->errCode) && $insertRes->errCode==0){
            $examineRes = $db ->field("user_id,process_level,examine")->where([$db->getPk()=>$id])->find();
            $uparam = [
                'fields' => 'roleId,userName',
                'where' => ['userId'=>$examineRes['user_id']],
                'one' => true,
            ];
            $authorResult = $this->userCom->getOne($uparam);
            $authRoleId = $authorResult['roleId'];
            $authName = $authorResult['userName'];

            $examine = explode(",",$examineRes["examine"]);
            //再查询用的的角色信息
            $param = [
                'fields' => 'roleId',
                'where' => ['roleId'=>['IN',$examine],'status'=>1],
                'groupBy' => "roleId",
            ];
            $userResult = $this->userCom->getList($param);
            if(isset($userResult['list'])){
                $roles = array_column($userResult['list'],"roleId");
            }else{
                $this->ajaxReturn(['errCode'=>100,'error'=>'查询用户信息失败']);
            }
            $place = $examineRes['process_level'];
            //审批通过才会修改下一级审核数据
            if($status == 1){
                if($place <= count($examine)){
                    if(count($examine) > 0 && count($examine) > count($roles)){
                        $diffRoles = array_diff($examine,$roles);
                        for ($place ; $place <= count($examine) ; $place++) { 
                            if(!in_array($examine[$place],$diffRoles)){
                                $place ++;
                                break;
                            }
                        }
                    }else{
                        $place ++;
                    }
                }else{
                    $this->ajaxReturn(['errCode'=>100,'error'=>'不需要再审核了']);
                }
                if($examine[($place-1)] == $authRoleId){
                    //如果下一个审批者是提交者本身则自动审批
                    $parameter=[
                        "table_name" => $table,
                        "table_id" => $id,
                        "add_time" => time(),
                        "user_id" => $examineRes['user_id'],
                        "status" => $status, //审批流程里的状态是实际状态
                        "remark" => '审批者(角色)与申请者(角色)一致自动审批',
                    ];

                    if($tableIds){
                        foreach ($tableIds as $tid) {
                            $parameter['table_id'] = $tid;
                            $insertRes = $this->approveCom->insert($parameter);
                        }
                    }else{
                        $insertRes = $this->approveCom->insert($parameter);
                    }
                    // $insertRes = $this->approveCom->insert($parameter);
                    
                    for ($place ; $place <= count($examine) ; $place++) { 
                        if(!in_array($examine[$place],$diffRoles)){
                            $place ++;
                            break;
                        }
                    }
                }
            }
            
            // if(array_search($roleId,$examine)!==false){
            //     // $place = array_search($roleId,$examine)+1;
            //     $place = $examineRes + 1;
            // }else{
            //     $this->ajaxReturn(['errCode'=>100,'error'=>'当前用户不能执行审核']);
            // }
            // //2，根据$vtabId获取当前应用的权限
            // $nodeId = getTabId($vtabId);
            // $process = $this->nodeCom->getProcess($nodeId);
            $state = $status;//v_expense_sub 项状态，
            // //3，当前审批者的位置如果小于总流程数量，且审批值是1
            // $place = $process["place"];
            if($place <= count($examine) && $status==1){
                $state = 2;//v_expense_sub 项状态，
            }
            $authPlace = array_search($authRoleId,$examine);//判断提交者在流程中的位置
            if(($authPlace+1) == count($examine) && $status == 1 && $place >= $authPlace){

                $state = 1;
            }
       
            // $this->log($examine);
            // $this->log($place);
            // $this->log($state);
            //这里判断下财务资金库存的数据
            if(in_array($table,C('finan_table')) && $state == 1){
                if($monacc_id <= 0){
                    $this->approveCom->rollback();
                    $this->ajaxReturn(['errCode'=>100,'error'=>'没有现金库存id']);
                }
                $this->moneyAccCom=getComponent('MoneyAccount');
                $param = [
                    'fields'=>'cash_stock',
                    'where' => ['id'=>$monacc_id],
                    'one' => true,
                ];
                $MAresult = $this->moneyAccCom->getOne($param);

                $debitResult = $db ->field("*")->where([$db->getPk()=>$id])->join("LEFT JOIN (SELECT projectId, code project_code,name project_name FROM v_project ) p ON p.projectId = project_id ")->join('LEFT JOIN (SELECT userId,userName user_name FROM v_user) u ON u.userId = user_id')->find();
                if($debitResult['debit_money']>$MAresult['cash_stock']){
                    $this->approveCom->rollback();
                    $this->ajaxReturn(['errCode'=>100,'error'=>'当前现金库存金额不足【'.$MAresult['cash_stock'].'元】，无法操作']);
                }
                $debit_money = $debitResult['debit_money'];
            }

            //4,更新$table 表的状态
            $dbData = ["process_level" => $place,"status" => $state];
            
            if(isset($file) && $file["file"]!=""){
                $dbData[$file["key"]] = $file["file"];
                $hasStatus = $db ->query("SELECT COLUMN_NAME FROM information_schema. COLUMNS WHERE TABLE_NAME = '".$table."' AND COLUMN_NAME = 'loan_date'");
                if($hasStatus){
                    $dbData["loan_date"] = time();
                }
            }
            if($tableIds){
                $updateRes = $db ->where([$db->getPk()=>["IN",$tableIds]])->save($dbData);
            }else{
                $updateRes = $db ->where([$db->getPk()=>$id])->save($dbData);
            }
            
            // $this->log($db ->_sql());
            if($updateRes || $state == 2){
               
                //5，统计 $table 数量
                
                //6，统计审批状态为1的审批记录
                // if($table=="v_expense_sub"){
                    // $allItem = $db ->where(["parent_id"=>$tableId])->count();
                    // $approveSql = "SELECT count(*) all_approve FROM v_approve_log WHERE user_id = {$userId} AND table_name = '{$table}' AND  FIND_IN_SET(table_id,(SELECT GROUP_CONCAT(id) FROM {$table} WHERE parent_id = {$tableId})) AND status = 1 AND effect = 1";
                // }else{
                    // $allItem = $db ->where([$db->getPk()=>$tableId])->count();
                    // $allItem = 1;
                    // $approveSql = "SELECT count(*) all_approve FROM v_approve_log WHERE user_id = {$userId} AND table_name = '{$table}' AND  table_id = {$tableId} AND status = 1 AND effect = 1";
                // }

                // $approveRes = $this->approveCom->M()->query($approveSql);
                // $allApprove = 0;
 
                // if($approveRes[0]["all_approve"]){
                    // $allApprove = $approveRes[0]["all_approve"];
                // }
                // if($allItem == $allApprove){
                    // $place ++;
                    // if($table=="v_expense_sub"){
                    //     $db->where(["parent_id"=>$tableId])->save(["status"=>$state,"process_level"=>$place]);
                    // }else{
                    //     $db->where([$db->getPk()=>$tableId])->save(["status"=>$state,"process_level"=>$place]);
                    // }
                // }

                // if($table=="v_expense_sub"){
                    // $mainDb = M("v_expense",NULL);
                    // $mainDb->where([$mainDb->getPk()=>$tableId])->save(["status"=>$state,"process_level"=>$place]);
                // }else 
                if($state==1){
                    switch ($table) {
                        case 'v_project':
                            $this->ReceCom=getComponent('Receivable');
                            $this->ReceCom->createOrder($tableId,session('userId'));
                            break;
                        case 'v_float_capital_log':
                            if($tableIds){
                                foreach ($tableIds as $tid) {
                                    getComponent('FlCapLog')->computeFloat($tid);
                                }
                            }else{
                                getComponent('FlCapLog')->computeFloat($id);
                            }
                            
                            break;
                        case in_array($table,C('finan_table')):
                            $data = [
                                'account_id' => $monacc_id,
                                'float_type' => 2,
                                'happen_time' => date("Y-m-d H:i:s"),
                                'inner_detail' => $debitResult['debit_cause'],
                                'log_type' => 2,
                                'money' => $debitResult['debit_money'],
                                'object' =>  $debitResult['user_name'],
                                'project_code' => $debitResult['project_code'],
                                'project_id' =>$debitResult['project_id'],
                            ];
                            getComponent('FlCapLog')->flo_cap_logAdd($data,true);
                            break;
                        case 'v_project_offer':
                            $nodeResult = A('Component/Node')->getOne(['fields'=>'nodeId','where'=>['db_table'=>'v_project_cost','processIds'=>['GT',0],'is_process'=>1],'one'=>true]);
                            getComponent('ProjectOffer')->toCost($tableId,$nodeResult['nodeId']);
                            break;
                        default:
                            break;
                    }
                }
                // if($table=="v_project" && $state==1){
                //     $this->ReceCom=getComponent('Receivable');
                //     $this->ReceCom->createOrder($tableId,session('userId'));
                // }else if($table=="v_float_capital_log" && $state==1){
                //     getComponent('FlCapLog')->computeFloat($id);
                // }else if(in_array($table,C('finan_table')) && $state==1){
                //     $data = [
                //         'account_id' => $monacc_id,
                //         'float_type' => 2,
                //         'happen_time' => date("Y-m-d H:i:s"),
                //         'inner_detail' => $debitResult['debit_cause'],
                //         'log_type' => 2,
                //         'money' => $debitResult['debit_money'],
                //         'object' =>  $debitResult['user_name'],
                //         'project_code' => $debitResult['project_code'],
                //         'project_id' =>$debitResult['project_id'],
                //     ];
                //     getComponent('FlCapLog')->flo_cap_logAdd($data,true);
                // }
                // $examineRes = $db ->field("process_level,examine")->where([$db->getPk()=>$id])->find();
                               
                if(isset($examine[$place-1]) && $examine[$place-1] > 0){
                    $touser = $this->userCom->getQiyeId($examine[$place-1],true);
                    if(!empty($touser)){
                        $desc = "<div class='gray'>".date("Y年m月d日",time())."</div> <div class='normal'>{$authName}在【{$title}】中提交的申请，".session('userName')."已经通过了审批，现在轮到您审批，点击进入审批吧！</div>";
                        $url = C('qiye_url')."/Admin/Index/Main.html?action={$controller}&id=".$tableId;
                        $msgResult = $this->QiyeCom-> textcard($touser,"【{$title}】审批",$desc,$url);
                    }
                }
                $db ->commit();
                $this->approveCom->M() ->commit();

                $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
            }else{
                $this->approveCom->M()->rollback();
            }
        }

        
        
        
        // if($status==1 && "all"){
        //     $process_level = $place;
        // }
        

        // print_r($process);exit;
        
        // $hasStatus = $db ->query("SELECT COLUMN_NAME FROM information_schema. COLUMNS WHERE TABLE_NAME = '".$table."' AND COLUMN_NAME = 'status'");
        // if(!$hasStatus){
        //     $this->ajaxReturn(['errCode'=>116,'error'=>getError(116).":status"]);
        // }
        

        
        
        
        
        // $parameter=[
        //     "table_name" => $table,
        //     "table_id" => $id,
        //     "add_time" => time(),
        //     "user_id" => session('userId'),
        //     "status" => $status,
        //     "remark" => $remark,
        // ];
        // print_r($parameter);exit;
        // if($updateRes){
            // $insertRes = $this->approveCom->insert($parameter);
            // if($insertRes->errCode==0){
                // $db ->commit();

                //SELECT GROUP_CONCAT(id),count(*) FROM {$table} WHERE parent_id = {$tableId};
        //SELECT * FROM v_approve_log WHERE table_name = '{$table}' AND  FIND_IN_SET(table_id,(SELECT GROUP_CONCAT(id) FROM {$table} WHERE parent_id = {$tableId})) and status = 1;

                // if($table == "v_expense_sub"){
                //     getComponent('Expense')->update(["where"=>[]]);
                // }
            //     $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
            // }else{
            //     $db ->rollback();
            //     $this->ajaxReturn(['errCode'=>112,'error'=>getError(112)]);
            // }
        // }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
        // print_r($result);
        // echo str_replace(" ","",ucwords(str_replace("_"," ",str_replace("v_","",$table))));
        // id	3
        // remark	随便写点什么
        // status	2
        // table	v_expense
    }
    function toAlias(){
        Vendor("pinyin.pinyin");
        $pinyin=new \pinyin();
        echo $pinyin->pinyin("来点中文");
    }
    //获取金额
    function getMoneyAccountList(){
        extract($_GET);
     
        $this->approveCom=getComponent('ApproveLog');

        $db = M($table,NULL);

        $examineRes = $db ->field("process_level,examine,status")->where([$db->getPk()=>$id])->find();
        
        $allApprove = $this->userCom->getList(['fields'=>'userId','where'=>['roleId'=>explode(",",$examineRes['examine'])[$place-1]]]);
        $userIds = array_column($allApprove['list'],"userId");
        // $this->log($allApprove);
        $appResult = $this ->approveCom ->getList(['where'=>["table_name"=>$table,"table_id"=>$id,"effect"=>1,'user_id' => ["IN",$userIds]]]);
        $appCount = $appResult ? $appResult['count'] : $appResult['count'];
        // $this->log($appResult);

        if(($appCount + 1) < $allApprove['count']){
            $this->ajaxReturn(['data'=>false]);
        }


        if(in_array($table,C('finan_table'))){
            $this->moneyAccCom=getComponent('MoneyAccount');
            $param = [
                'fields'=>'id,cs_title,cash_stock',
                'where' => [],
            ];
            $result = $this->moneyAccCom->getList($param);
            if($result){
                $this->ajaxReturn(['data'=>$result['list']]);
            }
        }
        $this->ajaxReturn(['data'=>false]);
    }
}