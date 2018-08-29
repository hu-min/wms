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
        $db = M($table,NULL);
        $examineRes = $db ->field("process_level,examine,status")->where([$db->getPk()=>$id])->find();
        $examine = explode(",",$examineRes["examine"]);
        $allProcess = count($examine);
        if(in_array($examineRes["status"],[3,5])){
            $nextExamine = "已".$this->statusType[$examineRes["status"]];
        }else{
            if(($examineRes["process_level"]-1)==$allProcess){
                $nextExamine = "已完成";
            }else{
                $nextRoleId = $examine[$examineRes["process_level"]-1];
                if($nextRoleId==session("roleId")){
                    $nextExamine = "说的就是你啊！";
                }else{
                    $userRole = $this->userCom->getUserInfo(0,$nextRoleId);
                    $nextExamine = $userRole["userName"]."【{$userRole['roleName']}】";
                }
                
            }
        }
        
        
        if($resultData && !empty($resultData["list"])){
            $this->ajaxReturn(['errCode'=>0,'error'=>getError(0),"data"=>$resultData["list"],"allProcess"=>$allProcess,"nextExamine"=>$nextExamine]);
        }
        $this->ajaxReturn(['errCode'=>115,'error'=>getError(115),"allProcess"=>$allProcess]);
        
    }
    function approveEdit(){
        
        extract($_REQUEST);
        $this->log($_REQUEST);
        // [table] => v_expense_sub
        // [id] => 8
        // [tableId] => 5
        // [remark] => 
        // [place] => 2
        // [status] => 1
        // [vtabId] => #vtabs33
        
        // table:v_project
        // id:13
        // remark:尝试着驳回
        // status:3
        // vtabId:#vtabs57
        
        $db = M($table,NULL);
        
        $tableId = $tableId ? $tableId : $id;
        $this->approveCom=getComponent('ApproveLog');
        // $allItem = $db ->where(["parent_id"=>$tableId])->count();
        // print_r($allItem);exit;
        $db ->startTrans();
        $userId = session('userId');
        $roleId = session('roleId');
        
        //1,先执行插入审批记录
        $parameter=[
            "table_name" => $table,
            "table_id" => $id,
            "add_time" => time(),
            "user_id" => $userId,
            "status" => $status, //审批流程里的状态是实际状态
            "remark" => $remark,
        ];
        
        $this->approveCom->M()->startTrans();
        $insertRes = $this->approveCom->insert($parameter);
        if(isset($insertRes->errCode) && $insertRes->errCode==0){
            $examineRes = $db ->field("process_level,examine")->where([$db->getPk()=>$id])->find();
            $examine = explode(",",$examineRes["examine"]);
            if(array_search($roleId,$examine)!==false){
                $place = array_search($roleId,$examine)+1;
            }else{
                $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
            }
            // //2，根据$vtabId获取当前应用的权限
            // $nodeId = getTabId($vtabId);
            // $process = $this->nodeCom->getProcess($nodeId);
            $state = $status;//v_expense_sub 项状态，
            // //3，当前审批者的位置如果小于总流程数量，且审批值是1
            // $place = $process["place"];
            if($place < count($examine) && $status==1){
                $state = 2;//v_expense_sub 项状态，
            }
            // //4,更新$table 表的状态
            $dbData = ["status"=>$state];
            if(isset($file) && $file["file"]!=""){
                $dbData[$file["key"]] = $file["file"];
                $hasStatus = $db ->query("SELECT COLUMN_NAME FROM information_schema. COLUMNS WHERE TABLE_NAME = '".$table."' AND COLUMN_NAME = 'loan_date'");
                if($hasStatus){
                    $dbData["loan_date"] = time();
                }
            }
            
            $updateRes = $db ->where([$db->getPk()=>$id])->save($dbData);
            if($updateRes || $state==2){
                $db ->commit();
                $this->approveCom->M() ->commit();
                //5，统计 $table 数量
                
                //6，统计审批状态为1的审批记录
                if($table=="v_expense_sub"){
                    $allItem = $db ->where(["parent_id"=>$tableId])->count();
                    $approveSql = "SELECT count(*) all_approve FROM v_approve_log WHERE user_id = {$userId} AND table_name = '{$table}' AND  FIND_IN_SET(table_id,(SELECT GROUP_CONCAT(id) FROM {$table} WHERE parent_id = {$tableId})) AND status = 1 AND effect = 1";
                }else{
                    // $allItem = $db ->where([$db->getPk()=>$tableId])->count();
                    $allItem = 1;
                    $approveSql = "SELECT count(*) all_approve FROM v_approve_log WHERE user_id = {$userId} AND table_name = '{$table}' AND  table_id = {$tableId} AND status = 1 AND effect = 1";
                }
                // $this->log($approveSql);
                $approveRes = $this->approveCom->M()->query($approveSql);
                $allApprove = 0;
 
                if($approveRes[0]["all_approve"]){
                    $allApprove = $approveRes[0]["all_approve"];
                }
                if($allItem == $allApprove){
                    $place ++;
                    if($table=="v_expense_sub"){
                        $db->where(["parent_id"=>$tableId])->save(["status"=>$state,"process_level"=>$place]);
                    }else{
                        $db->where([$db->getPk()=>$tableId])->save(["status"=>$state,"process_level"=>$place]);
                    }
                    
                }

                
                if($table=="v_expense_sub"){
                    $mainDb = M("v_expense",NULL);
                    $mainDb->where([$mainDb->getPk()=>$tableId])->save(["status"=>$state,"process_level"=>$place]);
                }
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
}