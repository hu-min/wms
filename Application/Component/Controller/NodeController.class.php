<?php
namespace Component\Controller;
// use Common\Controller\BaseController;

class NodeController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/Node');
    }
    /** 
     * @Author: vition 
     * @Date: 2018-09-05 00:00:41 
     * @Desc: 根据节点或者角色获取对应的流程信息 
     */    
    function getProcess($nodeId,$roleId=null,$rolePid=null,$processId=null){
        $nodeId = getTabId($nodeId);
        $roleId = $roleId ? $roleId : session('roleId');
        $rolePid = $rolePid ? $rolePid : session('rolePid');
        if($processId){
            $processResult =  A("Component/Process")->getList(['where'=>['processId'=>$processId],"fields"=>"processId, {$processId} processIds,processOption"]);
            // print_r($processResult);exit;
        }else{
            $processResult = $this->getList(["fields"=>"processId,controller,processIds,processOption","where"=>["nodeId"=> $nodeId],"joins"=>["LEFT JOIN (SELECT processId,processOption FROM v_process WHERE status = 1) p ON FIND_IN_SET(p.processId,processIds)"] ]);
        }
        
        $processInfo = ["process"=>[],"allProcess"=>1,"place"=>0,"processId"=>0,"examine"=>'',"auth"=>0];
        $processList = [];
        // print_r($processResult);exit;
        // $this->log($processResult);
        if(is_array($processResult["list"])){
            $nodeAuth = session('nodeAuth');
            $auth = $nodeAuth[$processResult["list"][0]["controller"]];
            $processInfo["auth"] = $auth;
            $processIds = explode(",",$processResult["list"][0]["processIds"]);
            foreach ($processIds as  $processId) {
                foreach ($processResult["list"] as $key => $processData) {
                    if($processData["processId"] == $processId){
                        $processList[$processId] = $processData;
                        unset($processResult["list"][$key]);
                    }
                }
            }
            
            foreach ($processList as $processData) {

                $process = json_decode($processData["processOption"],true);
                $processId = $processData["processId"];
                if( $auth >=7 || ($process[0]["type"] ==1 && in_array(99999999,$process[0]["role"])) || (($process[0]["type"] ==1 && in_array($rolePid,$process[0]["role"])) || ($process[0]["type"] == 2) && $roleId==$process[0]["role"])){
                    
                    $processInfo["place"] =0;
                    $examine = [];
                    $processInfo["processId"] = $processId;
                    $processInfo['process'] = $process;
                    $processInfo['allProcess'] = count($processInfo['process']);
                    foreach ($processInfo['process'] as $key => $proceData) {
                        if($key>0){
                            array_push($examine,$proceData["role"]);
                        }
                        if($proceData["type"]==1){
                            if(in_array($rolePid,$proceData["role"])){
                                $processInfo["place"] = $key + 1;
                            }
                        }else{
                            if($roleId==$proceData["role"]){
                                $processInfo["place"] = $key + 1;
                            }
                        }
                    }
                    $processInfo["examine"] = implode(",",$examine);
                }
            }
        }
        return $processInfo;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-09-05 00:00:11 
     * @Desc: 查询节点对应的流程 
     */    
    function nodeProcess($nodeType=2){
        $where = [
            "status"=>1,
            // "processIds"=>['neq',""],
            "db_table"=>['neq',""],
            "nodeType"=>$nodeType,
        ];
        $parameter=[
            "where"=>$where,
            "fields"=>'nodeId,db_table,controller,nodeTitle,processIds',
        ];
        $nodeRes = $this->getList($parameter);
        if(isset($nodeRes["list"]) && !empty($nodeRes["list"])){
            return $nodeRes["list"];
        }
        return [];
    }
    /** 
     * @Author: vition 
     * @Date: 2018-09-05 00:00:02 
     * @Desc: 获取节点信息 
     */    
    function getNodeInfo($key,$val,$outkey=false){
        $nodeInfos = session('nodeInfo');
        foreach ($nodeInfos as $nodeInfo) {
            if(strtoupper($nodeInfo[$key])  == strtoupper($val)){
                if($outkey){
                    if(is_array($outkey)){
                        $return =[];
                        foreach ($outkey as $oKey) {
                            $return[$oKey] = $nodeInfo[$oKey];
                        }
                        return $return;
                    }else{
                        return $nodeInfo[$outkey];
                    }
                }
                return $nodeInfo;
            }
        }
        return false;
    }
}