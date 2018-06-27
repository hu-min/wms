<?php
namespace Admin\Controller;

/** 
 * @Author: vition 
 * @Date: 2018-05-06 09:45:33 
 * @Desc: 项目管理 
 */
class ProjectController extends BaseController{
    protected $pageSize=15;
    public function _initialize() {
        parent::_initialize();
        $this->projectCom=getComponent('Project');
        $this->configCom=getComponent('Config');
        $this->customerCom=getComponent('Customer');
        $this->processArr=["0"=>"沟通","1"=>"完结","2"=>"裁决","3"=>"提案","4"=>"签约","5"=>"LOST","6"=>"筹备","7"=>"执行","8"=>"完成"];
        $this->dateArr=["0"=>"立项日期","1"=>"提案日期","2"=>"项目日期","3"=>"结束日期"];
        Vendor("levelTree.levelTree");
        $this->levelTree=new \levelTree();
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-06 10:59:44 
     * @Desc: 项目控制 
     */    
    function projectControl(){
        $reqType=I('reqType');
        $this->assign('processArr',$this->processArr);
        $project=$this->configCom->get_val("project");
        $this->assign("project",$project);
        if($reqType){
            $this->$reqType();
        }else{
            $this->assign('url',U(CONTROLLER_NAME.'/'.ACTION_NAME));
            $this->assign('responList',$this->getResponsList());
            $this->returnHtml();
        }
    }
    /** 
     * @Author: vition 
     * @Date: 2018-06-20 23:58:38 
     * @Desc: 立项、添加场次 
     */    
    function createProject(){
        $reqType=I('reqType');
        $id=I('id');
        $this->assign('processArr',$this->processArr);
        $this->assign('btnTitle','确认立项');
        $project=$this->configCom->get_val("project");
        $this->assign("controlName","project");
        $this->assign("gettype","Add");
        //一堆立项初始化数据开始了
        $this->assign('projectArr',$this->_getOption("project_id"));
        $this->assign('brandArr',$this->_getOption("brand"));
        $this->assign('fieldArr',$this->_getOption("field"));
        $this->assign('exeRootdArr',$this->basicCom->get_exe_root());
        // $this->assign('executedArr',$this->_getOption("execute_sub"));
        $this->assign('cusComArr',$this->_getOption("customer_com"));
        $this->assign('userArr',$this->_getOption("create_user"));
        $this->assign('ptypeArr',$this->_getOption("projectType"));
        $this->assign('stageArr',$this->_getOption("stage"));
        $this->assign("provinceArr",$this->basicCom->get_provinces());
        //一堆立项初始化数据结束了
        $this->assign("project",$project);
        if($reqType){
            $this->$reqType();
        }else{
            $this->assign('url',U(CONTROLLER_NAME.'/'.ACTION_NAME));
            // $this->assign('responList',$this->getResponsList());
            $this->returnHtml();
        }
    }
    function projectItem(){
        $reqType=I('reqType');
        $this->assign('dbName',"Project");//删除数据的时候需要
        $this->assign("controlName","project");//名字对应cust_company_modalOne，和cust_companyModal.html
        $this->assign('processArr',$this->processArr);
        $this->assign('btnTitle','确认立项');
        $project=$this->configCom->get_val("project");
        // $this->assign("gettype","Add");
        //一堆立项初始化数据开始了
        $this->assign('projectArr',$this->_getOption("project_id"));
        $this->assign('brandArr',$this->_getOption("brand"));
        $this->assign('fieldArr',$this->_getOption("field"));
        $this->assign('exeRootdArr',$this->basicCom->get_exe_root());
        // $this->assign('executedArr',$this->_getOption("execute_sub"));
        $this->assign('cusComArr',$this->_getOption("customer_com"));
        $this->assign('userArr',$this->_getOption("create_user"));
        $this->assign('ptypeArr',$this->_getOption("projectType"));
        $this->assign('stageArr',$this->_getOption("stage"));
        $this->assign("provinceArr",$this->basicCom->get_provinces());
        if($reqType){
            $this->$reqType();
        }else{
            $this->assign('url',U(CONTROLLER_NAME.'/'.ACTION_NAME));
            $this->returnHtml();
        }
    }
    function project_modalOne(){
        $title = "立项/添加场次";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        if($gettype=="Edit"){
            $title = "编辑项目";
            $btnTitle = "保存数据";
            $redisName="projectList";

            $parameter=[
                "where" => ["projectId"=>$id],
                "fields" => "*",
                "joins" =>[
                    "LEFT JOIN (SELECT projectId project_pid,name project_name FROM v_project ) p ON p.project_pid = project_id",
                    "LEFT JOIN (SELECT basicId brand_id,name brand_name FROM v_basic WHERE class = 'brand' ) b ON b.brand_id = brand",
                    "LEFT JOIN (SELECT companyId company_id,company customer_com_name FROM v_customer_company ) c ON c.company_id = customer_com",
                    "LEFT JOIN (SELECT contactId contact_id,contact customer_cont_name FROM v_customer_contact ) c2 ON c2.contact_id = customer_cont",
                    "LEFT JOIN (SELECT basicId field_id,name field_name FROM v_basic WHERE class = 'field' ) f ON f.field_id = field",
                    "LEFT JOIN (SELECT userId user_id,userName create_user_name FROM v_user) cu ON cu.user_id = create_user",
                    "LEFT JOIN (SELECT userId user_id,userName business_name FROM v_user) bu ON bu.user_id = business",
                    "LEFT JOIN (SELECT userId user_id,userName leader_name FROM v_user) lu ON lu.user_id = leader",
                    "LEFT JOIN (SELECT basicId execute_sub_id,name execute_sub_name FROM v_basic WHERE class = 'execute' ) e ON e.execute_sub_id = execute_sub",
                    "LEFT JOIN (SELECT basicId type_id,name type_name FROM v_basic WHERE class = 'projectType' ) pt ON pt.type_id = type",
                    "LEFT JOIN (SELECT basicId stage_id,name stage_name FROM v_basic WHERE class = 'stage' ) s ON s.stage_id = stage",
                ]
            ];
            $resultData = $this->projectCom->getOne($parameter)["list"];

            $resultData["project_time"] = date("Y-m-d",$resultData["project_time"]);
            $resultData["create_time"] = date("Y-m-d",$resultData["create_time"]);
            $resultData["bid_date"] = date("Y-m-d",$resultData["bid_date"]);
            $resultData["bid_time"] = date("H:i:s",$resultData["bid_time"]);
            $resultData["citys"] = $this->basicCom->get_citys($resultData["province"]);
            
            $parameter=[
                'where'=>$where,
                'fields'=>"basicId,name",
                'orderStr'=>"basicId DESC",
            ];
            $result=$this->basicCom->getBasicList($parameter);
            $resultData["execute_subs"] = $this->basicCom->get_citys($resultData["province"]);
            $parameter=[
                'where'=>["userId"=>["IN",array_unique(explode(",",$resultData["earlier_user"].",".$resultData["scene_user"]))]],
                'fields'=>"userId,userName,roleName",
                'orderStr'=>"userId DESC",
                'joins'=>'LEFT JOIN (SELECT roleId rid,roleName FROM v_role ) r ON r.rid = roleId',
            ];
            $result=$this->userCom->getUserList($parameter);
            $resultData["user_ids"]=$result["list"];
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "templet"=>"projectModal",
        ];
        $this->modalOne($modalPara);
    }

    function getOptionList(){
        $key=I("key");
        $type=I("type");
        $this->ajaxReturn(["data"=>$this->_getOption($type,$key)]);
    }
    function _getOption($type,$key=""){
        $where=["status"=>1];
        switch ($type) {
            case 'project_id':
                $where["project_id"]=0;
                if($key!=""){
                    $where["name"]=["LIKE","%{$key}%"];
                }
                $parameter=[
                    'where'=>$where,
                    'fields'=>"projectId,name",
                    'orderStr'=>"addTime DESC",
                ];
                $result=$this->projectCom->getProjectList($parameter);
                if($result){
                    return $result["list"];
                }
                break;
            case 'brand': case 'field': case 'execute_sub':  case 'projectType': case 'stage':
                if($type=="execute_sub"){
                    $type = "execute";
                }
                $where["class"]=$type;
                $pid = I("pid");
                if(isset($pid) && $pid >0){
                    $where["pId"]=$pid;
                }
                if($key!=""){
                    $where["name"]=["LIKE","%{$key}%"];
                }
                $parameter=[
                    'where'=>$where,
                    'fields'=>"basicId,name",
                    'orderStr'=>"basicId DESC",
                ];
                $result=$this->basicCom->getBasicList($parameter);
                if($result){
                    return $result["list"];
                }
                break;
            case 'customer_com':
                if ($key!=""){
                    $where["company"]=["LIKE","%{$key}%"];
                }
                $parameter=[
                    'where'=>$where,
                    'fields'=>'companyId,company',
                    'orderStr'=>"companyId DESC",
                ];
                $result = $this->customerCom->getCompanyList($parameter);
                if($result){
                    return $result["list"];
                }
                break;
            case 'customer_cont':
                $where["companyId"]=I("pid");
                if ($key!=""){
                    $where["contact"]=["LIKE","%{$key}%"];
                }
                $parameter=[
                    'where'=>$where,
                    'fields'=>'contactId,contact',
                    'orderStr'=>"contactId DESC",
                ];
                $result = $this->customerCom->getCustomerList($parameter);
                if($result){
                    return $result["list"];
                }
                break;
            case 'city':
                $result = $this->basicCom->get_citys(I("pid"));
                if($result){
                    return $result;
                }
                break;
            case 'create_user': case 'business': case 'leader': case 'earlier_user': case 'scene_user':
                if($key!=""){
                    $where["userName"]=["LIKE","%{$key}%"];
                }
                $parameter=[
                    'where'=>$where,
                    'fields'=>"userId,userName,roleName",
                    'orderStr'=>"userId DESC",
                    'joins'=>'LEFT JOIN (SELECT roleId rid,roleName FROM v_role ) r ON r.rid = roleId',
                ];
                $result=$this->userCom->getUserList($parameter);
                if($result){
                    return $result["list"];
                }
                break;
            default:
                # code...
                break;
        }
        return [];
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-06 11:00:23 
     * @Desc: 项目列表 
     */    
    function projectList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $userId = session("userId");
        $nodeAuth = $this->nodeAuth[CONTROLLER_NAME.'/'.ACTION_NAME];
        $where=[];
        if($data['name']){
            $where['name']=['LIKE','%'.$data['name'].'%'];
        }
        if($nodeAuth<7){
            $where['_string'] = ' (create_user = '.$userId .')  OR FIND_IN_SET('.$userId.',business) OR FIND_IN_SET('.$userId.',leader) OR FIND_IN_SET('.$userId.',earlier_user) OR FIND_IN_SET('.$userId.',scene_user) OR (create_user = '.$userId .') ';
        }
        // if($data['toCompany']){
        //     $where['toCompany']=['LIKE','%'.$data['toCompany'].'%'];
        // }
        // if($data['followUp']){
        //     $where['followUp']=['LIKE','%'.$data['followUp'].'%'];
        // }
        // if($data['business']){
        //     $where['business']=['LIKE','%'.$data['business'].'%'];
        // }
        // if($data['leader']){
        //     $where['leader']=['LIKE','%'.$data['leader'].'%'];
        // }
        // if($data['responsible']){
        //     $where['responsible']=['LIKE','%'.$data['responsible'].'%'];
        // }
        // if($data['time']){
        //     $times=explode("~",$data['time']);
        //     $where['time']=[["EGT",strtotime($times[0])],["ELT",strtotime($times[1])]];
        // }
        if(isset($data['status'])){
            $where['status']=$data['status'];
        }
        $parameter=[
            'where'=>$where,
            'fields'=>"*",
            'page'=>$p,
            'pageSize'=>$this->pageSize,
            'orderStr'=>"addTime DESC",
            "joins"=>"",
        ];
        $listResult=$this->projectCom->getProjectList($parameter);
        $this->tablePage($listResult,'Project/projectTable/projectList',"projectList");
        if($projectResult){
            $projectRed="projectList";
            $this->Redis->set($projectRed,json_encode($projectResult['list']),3600);
            $page = new \Think\VPage($projectResult['count'], $this->pageSize);
            $pageShow = $page->show();
            $this->assign('url',U(CONTROLLER_NAME.'/'.ACTION_NAME));
            $this->assign('projectList',$projectResult['list']);
            $countResult=$this->projectCom->count($where);
            $count="合同额：".number_format($countResult['totalAmount'])." | 总成本：".number_format($countResult['totalCost'])." | 总纯利：".number_format($countResult['totalProfit'])." | 总纯利率：".round($countResult['totalProfit']/$countResult['totalAmount']*100,2)."%";
            $this->ajaxReturn(['errCode'=>0,'table'=>$this->fetch('Project/projectTable/projectList'),'page'=>$pageShow,"count"=>$count]);
        }
        $this->ajaxReturn(['errCode'=>0,'table'=>'无数据','page'=>'']);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-08 20:31:11 
     * @Desc: 管理项目添加和修改的信息 
     */    
    function manageProjectInfo(){
        $reqType=I("reqType");
        $datas=I("data");

        $datas['project_id'] = $datas['project_id'] ? $datas['project_id'] : 0;
        if(isset($datas['earlier_user'])){
            $datas['earlier_user']=implode(",",$datas['earlier_user']);
        }
        if(isset($datas['scene_user'])){
            $datas['scene_user']=implode(",",$datas['scene_user']);
        }
        if(isset($datas['bid_date'])){
            $datas['bid_date']=date("Ymd",strtotime($datas['bid_date']));
        }
        if(isset($datas['create_time'])){
            $datas['create_time']=strtotime($datas['create_time']);
        }
        if(isset($datas['project_time'])){
            $datas['project_time']=strtotime($datas['project_time']);
        }
        if($reqType=="projectAdd"){
            $datas['addTime']=time();
            $datas['time']=strtotime($datas['time']);
            $datas['author']=session('userId');
            unset($datas['projectId']);
            return $datas;
        }else if($reqType=="projectEdit"){
            $where=["projectId"=>$datas['projectId']];
            $data=[];

            $data['updateUser']=session('userId');
            foreach (['project_id','amount','bid_date','bid_time','bidding','brand','city','code','create_time','create_user','customer_com','customer_cont','customer_other','days','earlier_user','execute_sub','execute','field','is_bid','leader','name','project_time','project_id','projectType','province','scene_user','session_all','session_cur','stage'] as  $key) {
                if(isset($datas[$key])){
                    $data[$key]=$datas[$key];
                }
            }
            if(isset($datas['status'])){
                $parameter=[
                    'where'=>["companyId"=>$datas['companyId']],
                ];
                $result=$this->projectCom->getOne($where);
                $data = $this->status_update($result,$datas["status"],$data);
            }
            $data['upateTime']=time();
            
            return ["where"=>$where,"data"=>$data];
        }
        return "";
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-08 20:31:31 
     * @Desc: 项目添加 
     */    
    function projectAdd(){
        $projectInfo=$this->manageProjectInfo();
        if($projectInfo){
            $insertResult=$this->projectCom->insertProject($projectInfo);
            if($insertResult && $insertResult->errCode==0){
                $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
            }
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-08 20:58:39 
     * @Desc: 修改项目 
     */    
    function projectEdit(){
        $projectInfo=$this->manageProjectInfo();
        $updateResult=$this->projectCom->updateProject($projectInfo);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-08 20:33:48 
     * @Desc: 获取单一条项目 
     */    
    function projectOne(){
        $id	=I("id");
        $parameter=[
            'projectId'=>$id,
        ];
        $pListRed="projectList";
        $projectList=$this->Redis->get($pListRed);
        $plist=[];
        if($projectList){
            foreach ($projectList as $project) {
               if($project['projectId']==$id){
                $plist=$project;
                break;
               }
            }
        }
        if(empty($plist)){
            $projectResult=$this->projectCom->getUser($parameter);
            if($projectResult->errCode==0){
                $plist=$projectResult->data;
            }
        }
        if(!empty($plist)){
            $plist["time"]=date("Y-m-d",$plist["time"]);
            $plist["advanceDate"]=date("Y-m-d",$plist["advanceDate"]);
            $this->ajaxReturn(['errCode'=>0,'info'=>$plist]);
        }
        $this->ajaxReturn(['errCode'=>110,'info'=>'无数据']);
    }

    function customerList(){
        $datas=I("data");
        $where=[];
        if(isset($datas['company'])){
            $where['company']=["LIKE","%{$datas['company']}%"];
        }
        $parameter=[
            'where'=>$where,
            'fields'=>"customerId,company,contact",
            'pageSize'=>$this->pageSize,
            'orderStr'=>"customerId DESC"
        ];
        $listResult=$this->customerCom->getCustomerList($parameter);
        $this->ajaxReturn($listResult);
    }
    function userList(){
        $datas=I("data");
        $where=[];
        if(isset($datas['userName'])){
            $where['userName']=["LIKE","%{$datas['userName']}%"];
        }
        $parameter=[
            'where'=>$where,
            'fields'=>"userName",
            'pageSize'=>$this->pageSize,
            'orderStr'=>"userId DESC"
        ];
        $listResult=$this->userCom->getUserList($parameter);
        $this->ajaxReturn(["list"=>array_column($listResult["list"],"userName")]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-08 20:31:43 
     * @Desc: 项目配置 
     */    
    function projectConfig(){
        $reqType=I('reqType');
        $this->assign('processArr',$this->processArr);
        if($reqType){
            $this->$reqType();
        }else{
            $project=$this->configCom->get_val("project");
            $responsList=$this->getResponsList();
            $this->assign("project",$project);
            $this->assign("responsible",$responsList);
            $this->assign('url',U(CONTROLLER_NAME.'/'.ACTION_NAME));
            $this->returnHtml();
        }
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-08 22:39:43 
     * @Desc: 更新项目配置 
     */    
    function projectConfEdit(){
        $datas=I("data");
        $updateResult=[
            "where"=>["name"=>"project"],
            "data"=>["value"=>json_encode($datas)],
        ];
        $updateResult=$this->configCom->updateConfig($updateResult);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-12 10:15:00 
     * @Desc: 管理承接模块请求数据 
     */    
    function manageRespon(){
        $responList=$this->getResponsList();
        $datas=I("data");
        $reqType=I("reqType");
        if($reqType=="responsibleAdd"){
            if(!in_array($datas["responsible"],$responList)){
                array_push($responList,$datas["responsible"]);
            }
        }elseif($reqType=="responsibleEdit" || $reqType=="responsibleDel"){
            foreach ($responList as $key => $value) {
                if($value==$datas["fromResponsible"]){
                    unset($responList[$key]);
                }
            }
            if($reqType=="responsibleEdit"){
                array_push($responList,$datas["responsible"]);
            }
        }
        $this->Redis->set("responsible",$responList);
        return $responList;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-12 10:03:37 
     * @Desc: 新增承接模块 
     */    
    function responsibleAdd(){
        $responList=$this->manageRespon();
        $return=$this->configCom->set_val("responsible",$responList);
        if($return){
            $this->ajaxReturn(['errCode'=>0,'error'=>"添加成功"]);
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-12 10:03:46 
     * @Desc: 修改承接模块 
     */    
    function responsibleEdit(){
        $responList=$this->manageRespon();
        $return=$this->configCom->set_val("responsible",$responList);
        if($return){
            $this->ajaxReturn(['errCode'=>0,'error'=>"修改成功"]);
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-12 16:07:49 
     * @Desc: 删除 承接模块 
     */    
    function responsibleDel(){
        $responList=$this->manageRespon();
        $return=$this->configCom->set_val("responsible",$responList);
        if($return){
            $this->ajaxReturn(['errCode'=>0,'error'=>"删除"]);
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-12 16:13:49 
     * @Desc:  
     */
    function responsList(){
        $responList=$this->getResponsList();
        $html='<option value="">承接模块</option>';
        foreach ($responList as  $value) {
            $html.='<option value="'.$value.'">'.$value.'</option>';
        }
        $this->ajaxReturn(['html'=>$html]);
    }
    function getResponsList(){
        $responList=$this->Redis->get("responsible");
        if(!$responList){
            $responsible=$this->configCom->get_val("responsible");
            $responList=$responsible?$responsible:[];
            $this->Redis->set("responsible",$responList);
        }
        return $responList;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-06-20 23:54:57 
     * @Desc: 生成项目编码 
     */    
    function createCode(){
        $prefix = "TWSH";
        $numResult = $this->projectCom->M()->where(["project_id"=>0])->count();

        return $prefix.($numResult+1);
    }
}