<?php
namespace Component\Controller;
class ProjectCostController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/ProjectCost');
    }
}