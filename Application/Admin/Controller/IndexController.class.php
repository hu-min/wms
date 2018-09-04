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
        if($this->isLogin()){
            $this->redirect('Index/Main');
        }else{
            $this->redirect('Index/Login');
        }
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
        // print_r($_SESSION);
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
        if(session("history")){
            $history = session("history");
            session("history",NULL);
            $userResult->data["history"] = $history;
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
    /** 
     * @Author: vition 
     * @Date: 2018-08-27 08:56:02 
     * @Desc:  个人首页
     */    
    function home(){
        $reqType=I('reqType');
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
        // $this->display();
    }
    /** 
     * @Author: vition 
     * @Date: 2018-08-27 08:56:16 
     * @Desc: 获取最新的审核 
     */    
    function getAppList(){
        $page=I("p")?I("p"):1;
        $pageNum = 5;
        $nodeProce = A("Component/Node")->nodeProcess();
        $nodeAuth = session('nodeAuth');
        $roleId = session("roleId");
        // print_r($nodeProce);exit;
        // print_r($nodeAuth);
        $db = M();
        $sqlArr = [];
        foreach ($nodeProce as $npInfo) {
            $user_id = "user_id";
            $add_time = "add_time";
            $project_id = "project_id";
            if(in_array($npInfo["db_table"],["v_project"])){
                $user_id = "author `user_id`";
                $add_time = "addTime `add_time`";
                $project_id = "`projectId` project_id";
            }
            if(isset($nodeAuth[$npInfo["controller"]]) && $nodeAuth[$npInfo["controller"]] > 0){
                $s = "SELECT '{$npInfo["nodeId"]}' nodeId , {$project_id} ,'{$npInfo["nodeTitle"]}' `moudle_name`,{$user_id},`process_level`,`status`,{$add_time},'{$npInfo["controller"]}' controller,examine FROM {$npInfo['db_table']} WHERE `status` IN (0,2) AND process_level = FIND_IN_SET({$roleId},examine) AND process_level > 0";
                array_push($sqlArr,$s);
            }
        }
        $sql = implode(" UNION ALL ",$sqlArr);
        if($sql != ""){
            $sqls = "SELECT nodeId, project_id,project_name,`moudle_name`,`user_id`,`user_name`,`process_level`,`status`,examine,FROM_UNIXTIME(add_time,'%Y-%m-%d %H:%i:%s') add_time,controller FROM ({$sql}) p LEFT JOIN (SELECT userId,userName `user_name` FROM v_user WHERE status =1) u ON userId = `user_id` LEFT JOIN (SELECT projectId pId,name project_name FROM v_project) pr ON pr.pId = project_id ORDER BY add_time DESC LIMIT ".($page - 1) * $pageNum.",".$pageNum; 
            // echo $sqls;exit;
            $cqls = "SELECT count(*) `count` FROM ({$sql}) p LEFT JOIN (SELECT userId,userName `user_name` FROM v_user WHERE status =1) u ON userId = `user_id` ";
            $result = $db ->query($sqls);
            $countRes = $db ->query($cqls);
            $listResult = ["list"=>$result,"count"=>$countRes[0]["count"]];
        }else{
            $listResult = ["list"=>[],"count"=>0];
        }
        
        // echo "SELECT `moudle_name`,`name`,`user_id`,`user_name`,`process_level`,`all`,`status`,FROM_UNIXTIME(add_time,'%Y-%m-%d %H:%i:%s') add_time FROM ({$sql}) p LEFT JOIN (SELECT userId,userName `user_name` FROM v_user WHERE status =1) u ON userId = `user_id` ORDER BY add_time DESC";
        
        $this->tablePage($listResult,'Index/table/appList',"homeAppList",5,["bigSize"=>false]);
        // $this->ajaxReturn($result);
    }
}

/** 
 * @Author: vition 
 * @Date: 2018-01-17 23:22:18 
 * @Desc: 7 最大权限，6 增删改查导入导出 5 增删改查导出 4 增删改查 3 增改查 2 增查 1 查 
 */
