<?php
namespace Admin\Controller;

/**
 * BaseController 控件基类
 *     公共控制文件
 * 
 * @author vition
 * @date 2017-11-17
 */

class BaseController extends \Common\Controller\BaseController{
    protected $userCom;
    public $userId;
    /**
     * 对admin的每一个控制器和方法做权限检查
     */
    public function _initialize() {
        parent::_initialize();
        $this->userCom=getComponent('User');
        
        $login=MODULE_NAME."/".CONTROLLER_NAME.'/'.ACTION_NAME;
        if(!$this->isLogin() && $login!='Admin/Index/Login'){
            if(IS_POST && ACTION_NAME=='checkLogin'){

            }else{
                $this->redirect('Index/Login');
            }
        }
    }
    /** 
     * @Author: vition 
     * @Date: 2018-01-14 17:04:19 
     * @Desc:  判断是否登录
     */          
    protected function isLogin(){
        $isLogin=session('isLogin');
        $loginName=session('loginName');
        $roleId=session('roleId');
        if($isLogin && $loginName && $roleId){
            return true;
        }
        return false;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-01-15 00:12:15 
     * @Desc: 设置登录和退出 
     */    
    protected function setLogin($userInfo=[]){
        $this->log($userInfo);
        if(empty($userInfo)){
            //退出设置
            session('isLogin',NULL);
            session('loginName',NULL);
            session('roleId',NULL);
            $this->redirect('Index/Login');
        }else{
            //登录设置
            session('userId',$userInfo['userId']);
            session('isLogin',1);
            session('loginName',$userInfo['loginName']);
            session('userName',$userInfo['userName']);
            session('roleId',$userInfo['loginName']);
            session('avatar',$userInfo['avatar']);
            session('usertype',$userInfo['usertype']);
        }
    }

}
