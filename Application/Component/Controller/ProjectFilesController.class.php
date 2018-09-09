<?php
namespace Component\Controller;
class ProjectFilesController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/ProjectFiles');
    }
}