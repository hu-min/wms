<?php
namespace Component\Controller;
class ReceivableController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/Receivable');
    }
    function createOrder($project_id,$user_id){
        $info=[
            "project_id"=>$project_id,
            "add_time"=>time(),
            "user_id"=>$user_id,
            "status"=>1,
        ];
        $this->insert($info);
    }
}