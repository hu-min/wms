<?php
namespace Component\Controller;
class PublicFilesController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/PublicFiles');
    }
}