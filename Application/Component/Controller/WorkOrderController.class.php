<?php
namespace Component\Controller;
class WorkOrderController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/WorkOrder');
    }
}