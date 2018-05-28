<?php
namespace Component\Controller;
// use Common\Controller\BaseController;
/** 
 * @Author: vition 
 * @Date: 2018-05-20 22:17:37 
 * @Desc: 日志组件 
 */
class LogController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/Log');
    }
}