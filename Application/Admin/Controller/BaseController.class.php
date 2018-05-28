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
    protected $pageSize=15;
    /**
     * 对admin的每一个控制器和方法做权限检查
     */
    public function _initialize() {
        parent::_initialize();
        $this->userCom=getComponent('User');
        $this->LogCom=getComponent('Log');
        $this->authority=C('authority');
        $this->nodeAuth=session('nodeAuth');
        $this->exemption=[//排除的控制器
            'Admin/Index/Login',
            'Admin/Index/Main',
            'Admin/Index/logOut',
            'Admin/Index/checkLogin',
            'Admin/Index/Index',
        ];
        $this->refreNode();
        // print_r($this->nodeAuth);
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
            $auth=$this->authVerify($conAct);
            if(!$auth){
                $this->prompt(1,'警告!','您不具备访问此页面的权限，如果您认为值得拥有，请联系管理员！');
                exit;
            }
            $this->assign('url',U(CONTROLLER_NAME.'/'.ACTION_NAME));
            $this->assign("pageId",$this->createId());
        }
        // exit;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-02-04 00:39:38 
     * @Desc: 权限验证 
     */    
    private function authVerify($conAct){
        $reqType=I("reqType");
        if($this->nodeAuth[$conAct]>=7){
            return true;
        }
        if(!in_array($reqType,C("authority.6"))){
            preg_match("/\S([A-Z]+[^[A-Z]*\S]*)$/",$reqType,$match);
            if(count($match)<1){
                $reqType="List";
                I("reqType",$reqType);
            }else{
                $reqType=$match[1];
            }
        }
        if(in_array($reqType,$this->authority[$this->nodeAuth[$conAct]])){
            return true;
        }
        return false;
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
            session('avatar',NULL);
            session('rolePid',NULL);
            session('usertype',NULL);
            session('nodeAuth',[]);
            $this->redirect('Index/Login');
        }else{
            //登录设置
            session('userId',$userInfo['userId']);
            session('isLogin',1);
            session('loginName',$userInfo['loginName']);
            session('userName',$userInfo['userName']);
            session('roleId',$userInfo['roleId']);
            session('rolePid',$userInfo['rolePid']);
            if($userInfo['avatar']==""){
                $userInfo['avatar']=U(__ROOT__.'/Public'.'/admintmpl'."/dist/img/avatar/avatar".rand(1,5).".png",'','');
            }else{
                $userInfo['avatar']=U('Uploads/'.$userInfo['avatar'],'','');
            }
            session('avatar',$userInfo['avatar']);
            session('usertype',$userInfo['usertype']);
            $this->userCom->logIORec($userInfo['userId']);
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
            $this->ajaxReturn(['html'=>$this->fetch("Index/Prompt"),'errCode'=>404,'error'=>getError(404)]);
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
    /** 
     * @Author: vition 
     * @Date: 2018-01-29 23:05:00 
     * @Desc: 更新node 
     */    
    function refreNode(){
        $this->Redis->set($this->refreNode,1,3600);
    }

    /** 
     * @Author: vition 
     * @Date: 2018-05-28 00:05:43 
     * @Desc: 生成id 
     */    
    function createId(){
        $header=strtolower(str_replace("Controller","",CONTROLLER_NAME));
        $middle=strtolower(substr(ACTION_NAME,0,(strlen(ACTION_NAME)>5?5:strlen(ACTION_NAME))));
        $index=substr((string)time(),7,4);
        return "{$header}{$middle}{$index}";
    }
}
