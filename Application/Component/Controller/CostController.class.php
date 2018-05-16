<?php
namespace Component\Controller;
class CostController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/Cost');
    }
}