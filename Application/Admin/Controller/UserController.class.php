<?php
namespace Admin\Controller;


class UserController extends BaseController{
    protected $pageSize=15;
    /** 
     * @Author: vition 
     * @Date: 2018-01-23 00:31:36 
     * @Desc: 用户列表页面
     */    
    function userRead(){
        $userType=C("userType");
        $userStatus=C("userStatus");
        $regFrom=C("regFrom");
        $reqType=I('reqType');
        if($reqType){
            $this->$reqType();
        }else{
            $this->assign('userType',$userType);
            $this->assign('userStatus',$userStatus);
            $this->assign('regFrom',$regFrom);
            $this->returnHtml();
        }
    }
    /** 
     * @Author: vition 
     * @Date: 2018-01-27 22:45:23 
     * @Desc: 添加、修改用户信息 
     */    
    function userEdit(){
        $reqType=I("reqType");
    }
    /** 
     * @Author: vition 
     * @Date: 2018-01-27 14:01:45 
     * @Desc: 获取用户列表 
     */    
    protected function userList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $where=[];
        if($data['loginName']){
            $where['loginName']=['LIKE','%'.$data['loginName'].'%'];
        }
        if($data['userName']){
            $where['userName']=['LIKE','%'.$data['userName'].'%'];
        }
        if($data['gender']){
            $where['gender']=$data['gender'];
        }
        if($data['userType']){
            $where['userType']=$data['userType'];
        }
        if(isset($data['status'])){
            $where['status']=$data['status'];
        }
        if($data['regFrom']){
            $where['regFrom']=$data['regFrom'];
        }
        $parameter=[
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$this->pageSize,
        ];
        
        $userResult=$this->userCom->getUserList($parameter);
        if($userResult){
            $uListRed="userList_".session("userId");
            $this->Redis->set($uListRed,json_encode($userResult['list']),3600);
            $page = new \Think\VPage($userResult['count'], $this->pageSize);
            $pageShow = $page->show();
            $this->assign('userList',$userResult['list']);
            $this->ajaxReturn(['errCode'=>0,'table'=>$this->fetch('User/userTable/userList'),'page'=>$pageShow]);
        }
        $this->ajaxReturn(['errCode'=>0,'table'=>'无数据','page'=>'']);

    }

    function userOne(){
        $id	=I("id");
        $parameter=[
            'userId'=>$id,
        ];
        $uListRed="userList_".session("userId");
        $userList=$this->Redis->get($uListRed);
        if($userList){
            foreach ($userList as $user) {
               if($user['userId']==$id){
                $this->ajaxReturn(['errCode'=>0,'info'=>$user]);
               }
            }
        }
        $userResult=$this->userCom->getUser($parameter);
        if($userResult->errCode==0){
            $this->ajaxReturn(['errCode'=>0,'info'=>$userResult->data]);
        }
        $this->ajaxReturn(['errCode'=>110,'info'=>'无数据']);
    }
}