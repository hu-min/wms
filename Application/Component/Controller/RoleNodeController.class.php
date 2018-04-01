<?php
namespace Component\Controller;
// use Common\Controller\BaseController;

class RoleNodeController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/RoleNode');
    }
}