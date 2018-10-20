<?php
namespace Component\Controller;
class ResetApplyController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/ResetApply');
    }
    /** 
     * @Author: vition 
     * @Date: 2018-10-19 11:07:39 
     * @Desc:  
     */    
    // function getResetApply(){
    //     $param = [

    //     ];
    //     $this->getOne();
    // }
}