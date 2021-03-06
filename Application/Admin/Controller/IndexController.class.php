<?php
namespace Admin\Controller;


class IndexController extends BaseController{

    public function _initialize() {
        $this->AUser = A('Admin/User');
        
        parent::_initialize();
        Vendor("levelTree.levelTree");
        $this->levelTree=new \levelTree();
        $this->logType = [
            'logout'=>'退出',
            'login'=>'登录',
            'insert'=>'添加',
            'edit'=>'编辑',
            'del'=>'浅删除',
            'deepdel'=>'深度删除',
            'export'=>'导出',
            'import'=>'导入',
            'bankstock'=>'银行库存',
            'cashstock'=>'现金库存',
        ]; 
        
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
        $identify = cookie('identify');
        if($identify && !$this->isLogin()){
            $prama = [
                'where'=>['_string'=>" SHA1(loginName) = '{$identify}'"],
                'joins'=>[
                    'LEFT JOIN (SELECT roleId role_id ,rolePid,roleName FROM v_role) r ON r.role_id = roleId',
                    'LEFT JOIN (SELECT roleId role_pid ,roleName rolePName FROM v_role) rp ON rp.role_pid = r.rolePid',
                ],
            ];
            $userInfo = $this->userCom->getOne($prama);
            if($userInfo){
                $userData = $userInfo['list'];
                unset($userInfo['list']);
                unset($userData['password']);
                unset($userData['seniorPassword']);
                $this->setLogin($userData);
                $this->redirect('Index/Main');
            }
            
        }
        // $this->userCom->getOne();
        $this->display();
    }
    function Main(){
        // $this->LogCom->log(100,$_SERVER['HTTP_USER_AGENT']);
        $this->userId=session('userId');
        if(!$this->userId){
            $this->redirect('Index/LogOut');
        }
        $mesgCom = getComponent('Message');
        $this->assign('no_read',$mesgCom->noRead());

        $newMesg = $mesgCom->newMesg();
        if($newMesg[0]){
            $this->assign('new_mesg',$newMesg[0]);
        }
        // print_r($newMesg[0]);
        // $this->assign('no_read',);
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
        $remember=I('remember');
        if(session('qiye_id')){
            $data['qiye_id'] = session('qiye_id');
        }
        $userResult=$this->userCom->checkUser($data);
        if($userResult->errCode==0){
            if($remember){
                cookie('identify',sha1($data['loginName']),7948800);//设置cookie
            }
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
     * @Date: 2018-12-14 16:53:33 
     * @Desc: 首页板块整合 
     */    
    function homePanl(){
        extract($_GET);
        $return = [];
        if($reqArr){
            foreach ($reqArr as $req) {
                if(method_exists($this,$req)){
                    $return[$req] = $this->$req(true);
                }
            }
        }
        $this->ajaxReturn($return);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-08-27 08:56:16 
     * @Desc: 获取最新的审核 
     */    
    function getAppList($return=false,$option=[]){
        $page=I("p")?I("p"):1;
        $pageNum = 5;
        if(isset($option['wait']) && $option['wait']){
            $this->assign('wait',true);
            $nodeProce = A("Component/Node")->nodeProcess(4);
        }else{
            $nodeProce = A("Component/Node")->nodeProcess();
        }
        
        $nodeAuth = session('nodeAuth');
        $roleId = session("roleId");
        $userId = session("userId");
        
        // print_r($nodeProce);exit;
        // print_r($nodeAuth);
        $db = M();
        $sqlArr = [];
        foreach ($nodeProce as $npInfo) {
            $user_id = "user_id";
            $add_time = "add_time";
            $project_id = "project_id";
            $id = "id";
            $idW = "id";
            if(in_array($npInfo["db_table"],["v_project"])){
                $add_time = "addTime `add_time`";
                $project_id = "`projectId` project_id";
                $id = "`projectId` id";
                $idW = "projectId";
            }elseif(in_array($npInfo["db_table"],["v_work_order"])){
                $project_id = "`relation_project` project_id";
            }
            if(isset($nodeAuth[$npInfo["controller"]]) && $nodeAuth[$npInfo["controller"]] > 0){
                $whereStr = "`status` IN (0,2) AND process_level = FIND_IN_SET({$roleId},examine)";
                if(isset($option['wait']) && $option['wait']){
                    $whereStr = "`status` IN (0,2,3) AND user_id = {$userId}";
                    
                }
                $s = "SELECT {$id}, '{$npInfo["nodeId"]}' nodeId , {$project_id} ,'{$npInfo["nodeTitle"]}' `moudle_name`,{$user_id},`process_level`,`status`,{$add_time},'{$npInfo["controller"]}' controller,examine,'{$npInfo['db_table']}' tableName,approve_id FROM {$npInfo['db_table']} LEFT JOIN (SELECT id approve_id,table_id FROM v_approve_log WHERE table_name='{$npInfo['db_table']}' AND `status` > 0 AND {$user_id} = {$userId} AND effect = 1 ) a ON a.table_id = {$idW} WHERE {$whereStr} AND process_level > 0";
                array_push($sqlArr,$s);
                // $this->log($s);
            }
        }
        $sql = implode(" UNION ALL ",$sqlArr);
        // $this->log($sql);
        // print_r($sqlArr);exit;
        if($sql != ""){
            //白名单处理
            // $whites = $this->whiteCom->getWhites();
            // $where = "";
            // if($whites){
            //     $where = " WHERE user_id NOT IN (".implode(",",$whites).")";
            // }

            $sqls = "SELECT id,nodeId, project_id,project_name,`moudle_name`,`user_id`,`user_name`,`process_level`,`status`,examine,FROM_UNIXTIME(add_time,'%Y-%m-%d %H:%i:%s') add_time,controller ,tableName,approve_id FROM ({$sql}) p LEFT JOIN (SELECT userId,userName `user_name` FROM v_user WHERE status =1) u ON userId = `user_id` LEFT JOIN (SELECT projectId pId,name project_name FROM v_project) pr ON pr.pId = project_id ".$where." ORDER BY add_time DESC LIMIT ".($page - 1) * $pageNum.",".$pageNum;
            // echo $sqls;exit;
            $cqls = "SELECT count(*) `count` FROM ({$sql}) p LEFT JOIN (SELECT userId,userName `user_name` FROM v_user WHERE status =1) u ON userId = `user_id` ".$where;
            // $this->log($sqls);
            $result = $db ->query($sqls);
            $countRes = $db ->query($cqls);
            $listResult = ["list"=>$result,"count"=>$countRes[0]["count"]];
        }else{
            $listResult = ["list"=>[],"count"=>0];
        }
        
        // echo "SELECT `moudle_name`,`name`,`user_id`,`user_name`,`process_level`,`all`,`status`,FROM_UNIXTIME(add_time,'%Y-%m-%d %H:%i:%s') add_time FROM ({$sql}) p LEFT JOIN (SELECT userId,userName `user_name` FROM v_user WHERE status =1) u ON userId = `user_id` ORDER BY add_time DESC";
        
        return $this->tablePage($listResult,'Index/table/appList',"homeAppList",5,"",["bigSize"=>false,'return'=>$return]);
        // $this->ajaxReturn($result);
    }
    function getWaitList($return=false,$option=[]){
        return $this->getAppList($return,['wait'=>true]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-10-16 10:48:55 
     * @Desc: 与我有关的列表 
     */    
    function relItemList($return=false){
        $page=I("p")?I("p"):1;
        $pageNum = 5;
        $where = [];
        $userId = session("userId");
        $where["_string"] = "business = {$userId} OR leader = {$userId} OR FIND_IN_SET({$userId},earlier_user) > 0 OR FIND_IN_SET({$userId},scene_user) > 0";
        $param=[
            "where" => $where,
            'page'=>$page,
            "fields" => "*,CASE WHEN user_id = {$userId} THEN '立项人' WHEN business = {$userId} THEN '营业主担' WHEN leader = {$userId} THEN '项目主担' WHEN FIND_IN_SET({$userId},earlier_user) > 0 THEN '前期项目人员' WHEN FIND_IN_SET({$userId},scene_user) THEN '现场执行人员' ELSE '其他职务' END duties ",
            'pageSize' => 5,
            'orderStr' => "addTime DESC",
            'joins' => [
                "LEFT JOIN (SELECT basicId stage_id,name stage_name FROM v_basic WHERE class = 'stage' ) s ON s.stage_id = stage",
            ]
        ];
        $listResult = getComponent('Project')->getList($param);
        return $this->tablePage($listResult,'Index/table/relItemList',"relItemList",5,"",["rollPage"=>5,"onlyPage"=>true,"bigSize"=>false,'return'=>$return]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-10-16 10:49:09 
     * @Desc: 最后登录的列表 
     */    
    function lastLoginList($return=false){
        $page=I("p")?I("p"):1;
        $param=[
            "where" => ["class"=>"login"],
            'page'=>$page,
            "fields" => "userName user_name,FROM_UNIXTIME(addTime,'%Y-%m-%d %H:%i:%s') login_time",
            'pageSize' => 5,
            'orderStr' => "addTime DESC",
        ];
        $listResult = $this->LogCom->getList($param);
        $listResult["count"] = $listResult["count"]>100 ? 100 : $listResult["count"];
        $onlineData = $this->redisCom->onlineList();
        $countStr = $onlineData['count'];
        return $this->tablePage($listResult,'Index/table/lastLoginList',"lastLoginList",5,$countStr,["rollPage"=>5,"onlyPage"=>true,"bigSize"=>false,'return'=>$return]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-10-16 10:51:52 
     * @Desc: 项目概要 
     */    
    function projectDescList($return=false){
        $projectCom=getComponent('Project');
        $page=I("p")?I("p"):1;
        $param=[
            "where" => ["project_id"=>"0",'stage'=>["gt","0"],'status'=>1],
            'page'=>$page,
            "fields" => "count(stage) num,stage,stage_name",
            'pageSize' => 9999999999,
            'orderStr' => "addTime DESC",
            'groupBy' => 'stage',
            'joins' =>[
                "LEFT JOIN (SELECT basicId stage_id,name stage_name FROM v_basic WHERE class = 'stage' ) s ON s.stage_id = stage"
            ],
        ];
        $listResult = $projectCom->getList($param);
        $count = array_sum(array_column($listResult["list"],'num'));
        return $this->tablePage($listResult,'Index/table/projectDescList',"projectDescList",5,$count,["rollPage"=>5,"onlyPage"=>false,"bigSize"=>false,'return'=>$return]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-09-29 11:57:10 
     * @Desc: 全局配置 
     */    
    function globalConf(){
        $reqType=I('reqType');
        $this->configCom=getComponent('Config');
        
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    /** 
     * @Author: vition 
     * @Date: 2018-09-29 14:20:03 
     * @Desc: 非项目审批流程 
     */    
    function index_process_modalOne(){
        $title = "非项目流程配置";
        $btnTitle = "修改配置";
        extract($_REQUEST);
        $resultData=[];
        $result = $this->Com ->get_option('get_processes');
        $optionStr='';
        foreach($result as $opt){
            $optionStr.='<option value="'.$opt["processId"].'">'.$opt["processName"].'</option>';
        }
        $this->assign("processData",$optionStr);
        $this->assign("controlName","index_process");
        $dbnames = [
            "v_debit"=>"借支",
            "v_expense"=>"报销",
        ];
        $this->assign("dbnames",$dbnames);
        $processType = [
            'execu_process' => '非项目流程',
            'wuser_process' => '工单个人信息流程',
            'wother_process' => '工单其他流程',
        ];
        $this->assign("processType",$processType);
        $modalPara=[
            "data"=> $this->_get_index_processList('execu_process'),
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "tpFolder"=>'Index',
            "folder"=>'table',
            "template"=>"processModal",
        ];
        $this->modalOne($modalPara);
    }

    function get_index_processList(){
        $process_type = I('process_type');
        $this->ajaxReturn(['errCode'=>0,'data'=>$this->_get_index_processList($process_type)]);
    }
    function _get_index_processList($process_type){
        $resultData = $this->configCom->get_val($process_type);
        if(!$resultData){
            $this->configCom->set_val($process_type,""); 
        }else if(!empty($resultData['value'])){
            return array_merge(json_decode($resultData['value'],true),['name'=>$process_type]);
        }
        return [];
    }
    function index_processEdit(){
        $reqType=I("reqType");
        $datas=I("data"); 
        $process_type = $datas['process_type'];
        unset($datas['process_type']);
        if($process_type!="execu_process"){
            unset($datas['db_name']);
        }
        $result = $this->configCom->set_val($process_type,json_encode($datas));
        $this->ajaxReturn(['errCode'=>$result->errCode,'error'=>getError($result->errCode)]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-10-08 19:40:02 
     * @Desc: 日志查看控制 
     */    
    function logControl(){
        $reqType=I('reqType'); 
        
        $this->assign('logType',$this->logType);
        $this->assign("controlName","logCon");
        $this->assign("tableName",$this->LogCom->tableName());
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function logConList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $export = I('export');
        $where=[];
        $where['class']=['IN',array_keys($this->logType)];
        foreach (['userName','describe'] as $key) {
            if(isset($data[$key])){
                $where[$key]=['LIKE','%'.$data[$key].'%'];
            }
        }
        if(isset($data['class'])){
            $where['class']=$data['class'];
        }
        $pageSize = isset($data['pageSize']) ? $data['pageSize'] : $this->pageSize;
        $parameter=[
            'where'=>$where,
            'fields' => '*,FROM_UNIXTIME(addTime,"%Y-%m-%d %H:%i:%s") add_time',
            'page'=>$p,
            'pageSize'=>$pageSize,
            'orderStr'=>"logId DESC",
            'joins'=>[]
        ];
        if($export){
            $config = ['control'=>CONTROLLER_NAME];
        }
        
        $basicResult=$this->LogCom->getList($parameter);
        $this->tablePage($basicResult,'Index/table/logList',"logList",$pageSize,'',$config);
    }
    function logCon_modalOne(){
        $title = "查看日志";
        $btnTitle = "查看日志";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        
        if($gettype=="Edit"){
            $title = "查看日志";
            $btnTitle = "查看日志";
            $redisName="logList";
            $resultData=$this->LogCom->redis_one($redisName,"logId",$id);
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "tpFolder"=>'Index',
            "folder"=>'table',
            "template"=>"logModal",
        ];
        $this->modalOne($modalPara);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-10-27 08:18:56 
     * @Desc: 维护界面，锁屏 
     */    
    function lock(){
        $reqType=I('reqType'); 
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    /** 
     * @Author: vition 
     * @Date: 2018-10-27 08:19:09 
     * @Desc: 检测维护密码 
     */    
    function checkRepair(){
        $datas = I("data");
        $locks = $this->configCom->is_web_lock();
        if(isset($datas['password']) && !empty($datas['password']) && $locks['value']==sha1(sha1($datas['password']))){
            session('web_lock_password',sha1(sha1($datas['password'])));
            $this->ajaxReturn(["errCode"=>0,"error"=>getError(0)]);
        }else{
            $this->ajaxReturn(["errCode"=>406,"error"=>getError(406)]);
        }
    }
    /** 
     * @Author: vition 
     * @Date: 2018-11-02 10:56:22 
     * @Desc: 系统锁定显示 
     */    
    function index_lock_modalOne(){
        $title = "系统锁定";
        $btnTitle = "修改配置";
        extract($_REQUEST);
        $this->assign("controlName","index_lock");
        $this->assign("tableName",$this->configCom->tableName());
        $lockInfo = $this->configCom->getOne(['where'=>['name'=>"web_lock"]]);
        if(isset($lockInfo['list']['value'])){
            unset($lockInfo['list']['value']);
        }
        $modalPara=[
            "data"=> $lockInfo['list'],
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "tpFolder"=>'Index',
            "folder"=>'table',
            "template"=>"lockModal",
        ];
        $this->modalOne($modalPara);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-11-02 10:57:18 
     * @Desc: 系统锁定编辑 
     */    
    function index_lockEdit(){
        extract($_REQUEST);
        if(!empty($data["password"])){
            $data["value"] = sha1(sha1($data["password"]));
        }else{
            unset($data["password"]);
        }
        if($data["config_id"]>0){
            $config_id = $data["config_id"];
            unset($data["config_id"]);
            $result = $this->configCom->update(['where'=>['id'=>$config_id],'data'=>$data]);
        }else{
            $data['name'] = 'web_lock';
            $data["value"] = isset($data["value"]) && !empty($data["value"]) ? $data["value"] : sha1(sha1('123456'));
            $result = $this->configCom->insert($data);
        }
        session('web_lock_password',NULL);
        $this->redisCom->delAll("","config_web_lock");
        if($data["status"] == 1){
            $result->errCode = 407;
            $result->error = getError(407);
        }
        $this->ajaxReturn($result);
    }

    function index_cost_conf_modalOne(){
        $title = "采购编辑配置";
        $btnTitle = "修改配置";
        extract($_REQUEST);
        $resultData=[];
        $this->assign("processData",$this->AUser->getProcess());
        $this->assign("controlName","index_process");

        $controlType = [
            'supplier' => '供应商合同相关',
            'purchase' => '采购付款安排',
            'invoice' => '发票添加',
        ];
        $this->assign("controlType",$controlType);
        $modalPara=[
            "data"=> [],
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "tpFolder"=>'Index',
            "folder"=>'table',
            "template"=>"processModal",
        ];
        $this->modalOne($modalPara);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-12-20 13:30:26 
     * @Desc: 服务器推送客户端 
     */    
    function serverSent(){
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        
        if($this->isLogin()){
            $time = date('r');
            $data = [
                'title'=>'通过时间来测试事件流',
                'time' =>date('r'),
            ];
            echo "data:".json_encode($data)."\n\n";
            // echo "data: 通过时间来测试事件流: {$time}\n\n";
            flush();
        }else{
            exit;
        }
        
    }
}

/** 
 * @Author: vition 
 * @Date: 2018-01-17 23:22:18 
 * @Desc: 7 最大权限，6 增删改查导入导出 5 增删改查导出 4 增删改查 3 增改查 2 增查 1 查 
 */
