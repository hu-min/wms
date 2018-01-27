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
    protected $authority;
    protected $nodeAuth;
    protected $exemption;
    /**
     * 对admin的每一个控制器和方法做权限检查
     */
    public function _initialize() {
        parent::_initialize();
        $this->userCom=getComponent('User');
        $this->authority=C('authority');
        $this->nodeAuth=session('nodeAuth');
        $this->exemption=[
            'Admin/Index/Login',
            'Admin/Index/Main',
            'Admin/Index/logOut',
            'Admin/Index/checkLogin',
            'Admin/Index/Index',
        ];
        // $this->setLogin();
        $nowConAct=MODULE_NAME."/".CONTROLLER_NAME.'/'.ACTION_NAME;
        if(in_array($nowConAct,$this->exemption)){
            if(!$this->isLogin() && !in_array(ACTION_NAME,['checkLogin','Login']) ){
                $this->redirect('Index/Login');
            }elseif($this->isLogin() && ACTION_NAME=='Login'){
                $this->redirect('Index/Main');
            }
        }else{
            $conAct=CONTROLLER_NAME.'/'.ACTION_NAME;
            preg_match("/\S([A-Z]+[\S]*)$/",$conAct,$match);
            if(!$match[1] || !in_array($match[1],$this->authority[$this->nodeAuth[$conAct]])){
                $this->prompt(1,'警告!','您不具备访问此页面的权限，如果您认为值得拥有，请联系管理员！');
                exit;
            }  
            
        }

        // if(!$this->isLogin() && $login!='Admin/Index/Login'){
        //     if(IS_POST && ACTION_NAME=='checkLogin'){

        //     }else{
        //         $this->redirect('Index/Login');
        //     }
        // }else{
        //     // 对控制器做权限判断;
        //     $conAct=CONTROLLER_NAME.'/'.ACTION_NAME;
        //     if($conAct!=='Index/Main'){
        //         preg_match("/\S([A-Z]+[\S]*)$/",$conAct,$match);
        //         if(!$match[1] || !in_array($match[1],$this->authority[$this->nodeAuth[$conAct]])){
        //             $this->prompt(1,'警告!','您不具备访问此页面的权限，如果您认为值得拥有，请联系管理员！');
        //             exit;
        //         }  
        //     }
        // }
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
            session('userId',NULL);
            session('userName',NULL);
            session('isLogin',NULL);
            session('loginName',NULL);
            session('roleId',NULL);
            session('nodeAuth',[]);
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
    /** 
     * @Author: vition 
     * @Date: 2018-01-27 11:47:30 
     * @Desc: 显示提示 
     */    
    protected function prompt($type=1,$title='',$content=''){
        switch ($type) {
            case 1: default:
                $alert="alert-danger";
                $icon="fa-ban";
                break;
            case 2:
                $alert="alert-info";
                $icon="fa-info";
                break;
            case 3:
                $alert="alert-warning";
                $icon="fa-warning";
                break;
            case 4:
                $alert="alert-check";
                $icon="fa-ban";
                break;
        }
        $this->assign("alert",$alert);
        $this->assign("title",$title);
        $this->assign("content",$content);
        $this->assign("icon",$icon);
        if(IS_AJAX){
            $this->ajaxReturn(['html'=>$this->fetch("Index/Prompt")]);
        }
        $this->assign("load",true);
        $this->display("Index/Prompt");
    }
    /** 
     * @Author: vition 
     * @Date: 2018-01-27 12:32:37 
     * @Desc: 返回html 
     */    
    function returnHtml($view=''){
        if(IS_AJAX){
            $this->ajaxReturn(['html'=>$this->fetch($view)]);
        }else{
            $this->assign("load",true);
            $this->display($view);
        }
    }

}
