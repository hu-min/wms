<?php
namespace Component\Controller;
class ProcessController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/Process');
    }
    function getProcess($roleId=null,$rolePid=null){
        $roleId = $roleId ? $roleId : session('roleId');
        $rolePid = $rolePid ? $rolePid : session('rolePid');
        $processRedis = "all_process_list";//更新process的时候必须更新这个缓存
        $this->Redis->set($processRedis,"",0);
        $processList = $this->Redis->get($processRedis);
        if(!$processList){
            $processList = [];
            $processRes = $this->getList(["where"=>["status"=>1],"fields"=>"processOption,processId"])["list"];
            foreach ($processRes as $proce) {
                $processList[$proce["processId"]] = json_decode($proce["processOption"],true);
            }
            $this->Redis->set($processRedis,$processList,7200);
        }
        $proccePlace = [];
        
        foreach ($processList as $id => $processArr) {
            // print_r($processArr);
            $place = 0 ;
            foreach ($processArr as $index => $proceData) {
                if($proceData["type"]==1){
                    if(in_array($rolePid,$proceData["role"])){
                        $place = $index + 1;
                    }
                }else{
                    if($roleId==$proceData["role"]){
                        $place = $index + 1;
                    }
                }
            }
            if(!isset($proccePlace[$id])){
                $proccePlace[$id] = ["place" => $place,"all"=>count($processArr)];
            }
            $proccePlace[$id]["place"] = $place;
        }
        $nodeProce = A("Component/Node")->nodeProcess();
        $nodeAuth = session('nodeAuth');
        $processAppList = [];
        foreach ($proccePlace as $processId => $placeInfo) {
            foreach ($nodeProce as $NPData) {
                if(in_array($processId,explode(",",$NPData["processIds"]))){
                    if(isset($processAppList[$NPData["db_table"]])){
                        if(($placeInfo["place"]-1)>$processAppList[$NPData["db_table"]]["level"]){
                            $processAppList[$NPData["db_table"]]["level"] = ($placeInfo["place"]-1);
                            $processAppList[$NPData["db_table"]]["all"] = $placeInfo["all"];
                        }
                    }else{
                        if(isset($nodeAuth[$NPData["controller"]]) && $nodeAuth[$NPData["controller"]]>0){
                            $processAppList[$NPData["db_table"]] = ["title"=>$NPData["nodeTitle"],"level"=>($placeInfo["place"]-1),"all"=>$placeInfo["all"],"controller"=>$NPData["controller"]];
                        }
                    }
                }
            }
        }
        // exit;
        return $processAppList;
        // print_r($proccePlace);
        // print_r($processAppList);
        // print_r($nodeProce);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-11-06 19:58:13 
     * @Desc: 获取 审批流程人员
     */    
    function getExamine($vtabId=false,$leader=0,$roleId=0,$processIds=0){
        // $limitWhite = $this->whiteCom->limitWhite(session('roleId'),$touserRoleId,true);
        // print_r($processIds);exit;
        $vtabId = $vtabId ? $vtabId : I("vtabId");
        $roleId = $roleId ? $roleId : session("roleId");
        $returnData = ["examine"=>'',"place"=>0,'process_id'=>0];
        if(!$vtabId && !$processIds){
            return $returnData;
        }
        $process = A("Component/Node")->getProcess($vtabId);
        // $roleId = '';
        
        if($leader>0){
            $userRole = A("Component/User")->getUserInfo($leader);
            // $roleId = $userRole['roleId'];
            $examines = $userRole['roleId'].",".$process["examine"];
        }elseif($processIds>0){
            $process = A("Component/Node")->getProcess($vtabId,$roleId,null,$processIds);
            $examines = $process["examine"];
        }else{
            $execuProResult = A("Component/Config")->get_val("execu_process");
            
            if($execuProResult){
                $execuProcess = json_decode($execuProResult['value'],true);
                if(isset($execuProcess['db_name']) && !empty($execuProcess['db_name'])){
                    $param = [
                        'where' => ['db_table'=>['IN',$execuProcess['db_name']],"_string" => "FIND_IN_SET(3,nodeType) > 0"],
                        'fields' => "nodeId",
                    ];
                    $nodeIdResult = A("Component/Node")->getList($param);
                    
                    if($nodeIdResult){
                        $nodeId = getTabId($vtabId);
            
                        if(in_array($nodeId,array_column($nodeIdResult['list'],"nodeId"))){
                            $process = A("Component/Node")->getProcess($vtabId,null,null,$execuProcess['processIds']);
                        }
                    }
                }
            }
            $examines = $process["examine"];
        }
        // print_r($examines);exit;
        // $limitWhite = A("Component/White")->limitWhite(session('roleId'),$touserRoleId,true);

        $process["place"] = search_last_key($roleId,array_unique(explode(",",$examines)));
        $returnData['examine'] = trim(implode(",",array_unique(explode(",",$examines))),",");
        $returnData['place'] = $process["place"] + 1;
        $returnData['process_level'] = $roleId ==  explode(",",$examines)[0] ? 2 : 1;
        // $returnData['place'] = $process["place"];
        $returnData['process_id'] = $process["processId"];
        $returnData['auth'] = $process["auth"];
        if($returnData['process_level'] == 1){
            $returnData['status'] = 0;
        }elseif($returnData['process_level']>1){
            if(count(explode(",",$returnData['examine'])) == $returnData['place']){
                $returnData['status'] = 1;
            }elseif(count(explode(",",$returnData['examine'])) > $returnData['place']){
                $returnData['status'] = 2;
            }
        }else{
            $returnData['status'] = 2;
        }
        
        // print_r($returnData);exit;
        if($returnData['examine']==""){
            $this->ajaxReturn(['errCode'=>20001,'error'=>getError(20001)]);
        }
        return $returnData;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-11-06 21:18:32 
     * @Desc: 根据白名单过滤审批流程 
     */    
    function filterExamine($userRoleId,$examines){
        $examine = explode(",",$examines);
        foreach ($examine as $key => $value) {
            $limitWhite = A("Component/White")->limitWhite($userRoleId,$value,true);
            if($limitWhite){
                unset($examine[$key]);
            }
        }
        return implode(",",$examine);
    }
}