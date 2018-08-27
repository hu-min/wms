<?php
namespace Component\Controller;
// use Common\Controller\BaseController;

class NodeController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/Node');
    }
    function getProcess($nodeId,$roleId=null,$rolePid=null){
        $roleId = $roleId ? $roleId : session('roleId');
        $rolePid = $rolePid ? $rolePid : session('rolePid');
        $processResult = $this->getOne(["fields"=>"processIds,processOption","where"=>["nodeId"=> $nodeId],"joins"=>["LEFT JOIN (SELECT processId,processOption FROM v_process) p ON p.processId = processIds"] ]);
        $processInfo = ["process"=>[],"allProcess"=>1,"place"=>0];
        if(is_array($processResult["list"])){
            $processInfo['process'] = json_decode($processResult["list"]["processOption"],true);
            foreach ($processInfo['process'] as $key => $proceData) {

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
            $processInfo['allProcess'] = count($processInfo['process']);
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