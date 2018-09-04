<?php
namespace Component\Controller;
class LiquidateController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/Liquidate');
    }
}