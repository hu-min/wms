<?php
namespace Component\Controller;
class ProjectCostSubController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/ProjectCostSub');
    }
}