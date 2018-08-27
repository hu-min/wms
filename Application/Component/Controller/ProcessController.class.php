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
        print_r($processAppList);
        // print_r($nodeProce);
    }
}