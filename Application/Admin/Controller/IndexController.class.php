<?php
namespace Admin\Controller;


class IndexController extends BaseController{

    public function _initialize() {
        parent::_initialize();
        Vendor("levelTree.levelTree");
        $this->levelTree=new \levelTree();
    }
    
    /**
     * 后台管理入口
     */
    function Index(){
        echo "a";
        // if($this->isLogin()){
        //     $this->redirect('Index/Main');
        // }else{
        //     $this->redirect('Index/Login');
        // }
    }
    /** 
     * @Author: vition 
     * @Date: 2018-01-14 21:28:54 
     * @Desc: 登录页面 
     */    
    function Login(){
        $this->display();
    }
    function Main(){
        $this->userId=session('userId');
        if(!$this->userId){
            $this->redirect('Index/LogOut');
        }
        $nodeResult=$this->userCom->getUserNode($this->userId);

        $this->levelTree->setKeys(["nodeName"=>"node"]);

        $this->levelTree->setHtmlAttr(["uAttr"=>" class='treeview-menu' ","aLiAttr"=>"","aAAttr"=>" class='nodeOn' data-level='{[level]}' data-nodeid='{[nodeId]}' href='{[controller|U]}'","aIAttr"=>" class='{[nodeIcon]}'","aSpanAttr"=>"","nLiAttr"=>" class='treeview'","nAAttr"=>" href='{[controller|U]}' class='nodeOn' data-level='{[level]}'","nIAttr"=>" class='{[nodeIcon]}'","nSpanAttr"=>" class='title'","nArAttr"=>" class='pull-right-container'","nArIAttr"=>" class='fa fa-angle-left pull-right'"]);
        $nodeHtml=$this->levelTree->tree2Html($nodeResult->data,'nodeTitle');
        $this->assign('nodeHtml',$nodeHtml);

        $logout="Index/logOut";
        $this->assign('logout',$logout);
        $this->display();
    }
    /** 
     * @Author: vition 
     * @Date: 2018-01-14 21:31:09 
     * @Desc: 检查登录 
     */    
    function checkLogin(){
        $data=I('data');
        $userResult=$this->userCom->checkUser($data);
        if($userResult->errCode==0){
            $this->setLogin($userResult->data);
        }
        $this->ajaxReturn($userResult);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-01-21 09:29:46 
     * @Desc: 退出登录 
     */    
    function logOut(){
        $this->setLogin();
    }

    function createNode($nodeArray){
        if(is_Array($nodeArray['node'])){
            foreach ($nodeArray['node'] as $key => $value) {
                echo $value['nodeTitle']."\n";
                $this->createNode($value);
            }
        }
    }
}

/** 
 * @Author: vition 
 * @Date: 2018-01-17 23:22:18 
 * @Desc: 7 最大权限，6 增删改查导入导出 5 增删改查导出 4 增删改查 3 增改查 2 增查 1 查 
 */
