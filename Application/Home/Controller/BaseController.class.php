<?php
namespace Home\Controller;

/**
 * BaseController 控件基类
 *     公共控制文件
 * 
 * @author vition
 * @date 2017-11-17
 */

class BaseController extends \Common\Controller\BaseController{
    /**
     * 对admin的每一个控制器和方法做权限检查
     */
    public function _initialize() {
        parent::_initialize();
    }
}
