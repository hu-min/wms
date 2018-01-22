<?php
namespace Admin\Controller;


class UserController extends BaseController{
    /** 
     * @Author: vition 
     * @Date: 2018-01-23 00:31:36 
     * @Desc: 用户列表 
     */    
    function userList(){
        $this->ajaxReturn(["errCode"=>0]);
    }
}