<?php
namespace Component\Controller;
// use Common\Controller\BaseController;

class RoleController extends BaseController{

    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/Role');
    }
}