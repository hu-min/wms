<?php
namespace Component\Controller;
class FieldController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/Field');
    }
}