<?php
namespace Component\Controller;
// use Common\Controller\BaseController;

class NodeController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/Node');
    }
}