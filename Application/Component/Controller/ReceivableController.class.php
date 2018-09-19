<?php
namespace Component\Controller;
class ReceivableController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/Receivable');
    }
    function createOrder($project_id,$author){
        $info=[
            "project_id"=>$project_id,
            "add_time"=>time(),
            "author"=>$author,
            "status"=>1,
        ];
        $this->insert($info);
    }
}