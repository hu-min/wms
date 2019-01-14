<?php
namespace Component\Controller;
class ProjectDatePlaceController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/ProjectDatePlace');
    }
}