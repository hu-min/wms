<?php
namespace Component\Controller;
class ProjectController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/Project');
    }

    function count($where){
        return $this->selfDB->field("SUM(amount) totalAmount,SUM(cost) totalCost,SUM(profit) totalProfit")->where($where)->find();
    }
}