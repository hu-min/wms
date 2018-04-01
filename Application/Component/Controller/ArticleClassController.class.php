<?php
namespace Component\Controller;
class ArticleClassController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/ArticleClass');
    }
}