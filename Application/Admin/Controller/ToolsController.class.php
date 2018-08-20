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
        
        $nodeId = str_replace("#vtabs","",$vtabId);
        $processResult = $this->nodeCom->getOne(["fields"=>"processIds,processOption","where"=>["nodeId"=> $nodeId],"joins"=>["LEFT JOIN (SELECT processId,processOption FROM v_process) p ON p.processId = processIds"] ]);
        $allProcess = 1;
        if($processResult){
            $process = json_decode($processResult["list"]["processOption"],true);
            $allProcess = count($process);
        }

        $parameter=[
            "where" => ["table_name"=>$table,"table_id"=>$id],
            "fields" => "*, FROM_UNIXTIME(add_time,'%Y-%m-%d %H:%s:%i') add_time,CASE status WHEN 0 THEN '未审核' WHEN 1 THEN '批准' WHEN 2 THEN '驳回' WHEN 3 THEN '拒绝' WHEN 4 THEN '删除' ELSE '无效' END state",
            "joins" => [
                "LEFT JOIN (SELECT userId,userName user_name,roleId FROM v_user) u ON u.userId = user_id",
                "LEFT JOIN (SELECT roleId role_id,roleName role_name FROM v_role) r ON r.role_id = u.roleId",
            ],
            "orderStr" => "add_time DESC",
        ];
        $resultData = $this->approveCom->getList($parameter);

        if($resultData && !empty($resultData["list"])){
            $this->ajaxReturn(['errCode'=>0,'error'=>getError(0),"data"=>$resultData["list"],"allProcess"=>$allProcess]);
        }
        $this->ajaxReturn(['errCode'=>115,'error'=>getError(115)]);
        
    }
    function approveEdit(){

    }
}