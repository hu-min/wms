<?php
namespace Component\Controller;
class ArticleController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/Article');
    }
}