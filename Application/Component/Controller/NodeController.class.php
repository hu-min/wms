<?php
namespace Component\Controller;
// use Common\Controller\BaseController;

class NodeController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/Node');
    }
    function getProcess($nodeId,$roleId=null,$rolePid=null){
        $nodeId = getTabId($nodeId);
        $roleId = $roleId ? $roleId : session('roleId');
        $rolePid = $rolePid ? $rolePid : session('rolePid');
        $processResult = $this->getList(["fields"=>"processId,processIds,processOption","where"=>["nodeId"=> $nodeId],"joins"=>["LEFT JOIN (SELECT processId,processOption FROM v_process WHERE status = 1) p ON FIND_IN_SET(p.processId,processIds)"] ]);
        $processInfo = ["process"=>[],"allProcess"=>1,"place"=>0,"processId"=>0,"examine"=>''];
        $processList = [];
        if(is_array($processResult["list"])){
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
                if(($process[0]["type"] ==1 && in_array($rolePid,$process[0]["role"])) || ($process[0]["type"] == 2) && $roleId==$process[0]["role"]){
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

    function nodeProcess(){
        $parameter=[
            "where"=>["status"=>1,"processIds"=>['neq',""],"db_table"=>['neq',""]],
            "fields"=>'nodeId,db_table,controller,nodeTitle,processIds',
        ];
        $nodeRes = $this->getList($parameter);
        if(isset($nodeRes["list"]) && !empty($nodeRes["list"])){
            return $nodeRes["list"];
        }
        return [];
    }
}