<?php
namespace Admin\Controller;


class UserController extends BaseController{
    /** 
     * @Author: vition 
     * @Date: 2018-01-23 00:31:36 
     * @Desc: 用户列表 
     */    
    function userRead(){
        $userType=C("userType");
        $userStatus=C("userStatus");
        $regFrom=C("regFrom");
        $this->assign('userType',$userType);
        $this->assign('userStatus',$userStatus);
        $this->assign('regFrom',$regFrom);
        // $this->ajaxReturn(['html'=>$this->fetch()]);
        $this->returnHtml();
    }
}