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
        $this->supplierCom=getComponent('Supplier');
        $this->purchaCom=getComponent('Purcha');
        $this->fieldCom=getComponent('Field');
        $this->filesCom=getComponent('ProjectFiles');
        $this->ReceCom=getComponent('Receivable');
        $this->whiteCom=getComponent('White');
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
        $this->assign("controlName","project");//名字对应cust_company_modalOne，和cust_companyModal.html
        $this->assign('processArr',$this->processArr);
        $this->assign('btnTitle','确认立项');
        $project=$this->configCom->get_val("project");
        $this->assign("tableName",$this->projectCom->tableName());
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
       
        $this->assign("nodeAuth",$this->nodeAuth[CONTROLLER_NAME.'/'.ACTION_NAME]);
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
        $onlydata = I("onlydata");
        $roleId = session("roleId");
        $resultData=[];
        $id = I("id");
        if($gettype=="Edit"){
            $title = "编辑项目";
            $btnTitle = "保存数据";
            $redisName="projectList";

            $parameter=[
                "where" => ["projectId"=>$id],
                "fields" => "*,FIND_IN_SET({$roleId},examine) place",
                "joins" =>[
                    "LEFT JOIN (SELECT projectId project_pid,name project_name FROM v_project ) p ON p.project_pid = project_id",
                    "LEFT JOIN (SELECT basicId brand_id,name brand_name FROM v_basic WHERE class = 'brand' ) b ON b.brand_id = brand",
                    "LEFT JOIN (SELECT companyId company_id,company customer_com_name FROM v_customer_company ) c ON c.company_id = customer_com",
                    "LEFT JOIN (SELECT contactId contact_id,contact customer_cont_name FROM v_customer_contact ) c2 ON c2.contact_id = customer_cont",
                    "LEFT JOIN (SELECT id field_id,name field_name,city f_city FROM v_field ) f ON f.field_id = field AND f.f_city=city",
                    "LEFT JOIN (SELECT userId user_id,userName create_user_name FROM v_user) cu ON cu.user_id = create_user",
                    "LEFT JOIN (SELECT userId user_id,userName business_name FROM v_user) bu ON bu.user_id = business",
                    "LEFT JOIN (SELECT userId user_id,userName leader_name FROM v_user) lu ON lu.user_id = leader",
                    "LEFT JOIN (SELECT basicId execute_sub_id,name execute_sub_name FROM v_basic WHERE class = 'execute' ) e ON e.execute_sub_id = execute_sub",
                    "LEFT JOIN (SELECT basicId type_id,name type_name FROM v_basic WHERE class = 'projectType' ) pt ON pt.type_id = type",
                    "LEFT JOIN (SELECT basicId stage_id,name stage_name FROM v_basic WHERE class = 'stage' ) s ON s.stage_id = stage",
                    "LEFT JOIN (SELECT project_id project_sid,COUNT(projectId) session_count FROM v_project WHERE projectId = 0 GROUP BY project_id) sc ON sc.project_sid = projectId",
                ]
            ];
            $resultData = $this->projectCom->getOne($parameter)["list"];

            $resultData["project_time"] = date("Y-m-d",$resultData["project_time"]);
            $resultData["create_time"] = date("Y-m-d",$resultData["create_time"]);
            $resultData["bid_date"] = date("Y-m-d",$resultData["bid_date"]);
            $resultData["bid_time"] = date("H:i:s",$resultData["bid_time"]);
            $resultData["end_date"] = date("Y-m-d",strtotime($resultData["project_time"]." +".$resultData["days"]."day"));
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
        if($this->nodeAuth[CONTROLLER_NAME.'/'.ACTION_NAME] >= 7 || ($resultData['business'] == session('userId')) || ($resultData['author'] == session('userId')) ){
            
        }else{
            $resultData['contract'] = '';
        }
        if($onlydata){
            $this->ajaxReturn(["data"=>$resultData]);
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"projectModal",
        ];
        $this->modalOne($modalPara);
    }

    function getOptionList(){
        $key=I("key");
        $type=I("type");
        $this->ajaxReturn(["data"=>$this->_getOption($type,$key)]);
    }
    function _getOption($type,$key="",$option=[]){
        $roleId = session("roleId");
        $userId = session("userId");
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
            case 'relation_project':
                $where["project_id"]=0;
                $where["_string"] = "(FIND_IN_SET({$roleId},examine) <= process_level AND FIND_IN_SET({$roleId},examine) > 0) OR (author = {$userId}) OR (( FIND_IN_SET({$userId},business) OR FIND_IN_SET({$userId},leader) OR FIND_IN_SET({$userId},earlier_user) OR FIND_IN_SET({$userId},scene_user) OR (author = {$userId})) AND status =1 )";
                $key_type = "name";
                if($option['key_type'] == "code"){
                    $key_type = "code";
                }
                if($key!=""){
                    $where[$key_type]=["LIKE","%{$key}%"];
                }
                $parameter=[
                    'where'=>$where,
                    'fields'=>"projectId,name",
                    'orderStr'=>"addTime DESC",
                ];
                // print_r($key);exit;
                $result=$this->projectCom->getProjectList($parameter);
                // echo $this->projectCom->M()->_sql();exit;
                if($result){
                    return $result["list"];
                }
            break;
            case 'brand': case 'execute_sub':  case 'projectType': case 'stage': case 'finance_id': case 'expense_type':
                if($type=="execute_sub"){
                    $type = "execute";
                }elseif($type == 'finance_id'){
                    $type = "bankstock";
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
            case 'city': case 'cityId':
                $result = $this->basicCom->get_citys(I("pid"));
                if($result){
                    return $result;
                }
                break;
            case 'create_user': case 'business': case 'leader': case 'earlier_user': case 'scene_user': case 'author' : case 'to_user' :
                if($key!=""){
                    $where["userName"]=["LIKE","%{$key}%"];
                }
                $parameter=[
                    'where'=>$where,
                    'fields'=>"userId,userName,roleName",
                    'orderStr'=>"userId DESC",
                    'pageSize' => 50,
                    'joins'=>'LEFT JOIN (SELECT roleId rid,roleName FROM v_role ) r ON r.rid = roleId',
                ];
                $result=$this->userCom->getUserList($parameter);
                if($result){
                    if($type=="to_user"){
                        foreach ($result["list"] as $key => $value) {
                            $result["list"][$key]['userName'] =  "【{$value['roleName']}】{$value['userName']}";
                        }
                    }
                    return $result["list"];
                }
                break;
            case 'supplier_com':
                if ($key!=""){
                    $where["company"]=["LIKE","%{$key}%"];
                }
                $parameter=[
                    'where'=>$where,
                    'fields'=>'companyId,company,supr_type,typename,provinceId,province,cityId,city',
                    'orderStr'=>"companyId DESC",
                    'joins'=>[
                        "LEFT JOIN (SELECT basicId,name typename FROM v_basic WHERE class='supType') b ON b.basicId=supr_type",
                        "LEFT JOIN (SELECT pid ,province FROM v_province ) p ON p.pid = provinceId",
                        "LEFT JOIN (SELECT cid ctid ,city,pid cpid FROM v_city ) ct ON ct.ctid = cityId AND ct.cpid = provinceId",
                    ]
                ];
                $result = $this->supplierCom->getCompanyList($parameter);
                if($result){
                    return $result["list"];
                }
                break;
            case 'supplier_cont':
                $where["companyId"]=I("pid");
                if ($key!=""){
                    $where["contact"]=["LIKE","%{$key}%"];
                }
                $parameter=[
                    'where'=>$where,
                    'fields'=>'contactId,contact',
                    'orderStr'=>"contactId DESC",
                ];
                $result = $this->supplierCom->getSupplierList($parameter);
                if($result){
                    return $result["list"];
                }
                break;
            case 'cost_id':
                $where['status'] = 1;
                if ($key!=""){
                    $map["project_name"]=["LIKE","%{$key}%"];
                    $map["supplier_com_name"]=["LIKE","%{$key}%"];
                    $map['_logic'] = 'or';
                    $where['_complex'] = $map;
                }
                $parameter=[
                    'where'=>$where,
                    'fields'=>'id,project_name,supplier_com,supplier_com_name',
                    'orderStr'=>"id DESC",
                    "joins"=>[
                        "LEFT JOIN (SELECT projectId,name project_name FROM v_project ) p ON p.projectId = project_id ",
                        "LEFT JOIN (SELECT companyId company_id,company supplier_com_name FROM v_supplier_company ) c ON c.company_id = supplier_com",
                    ],
                ];
                $result = $this->purchaCom->getList($parameter);
                if($result){
                    $costs = [];
                    foreach ($result["list"] as $key => $item) {
                        $costs[$key] = ["id"=>$item["id"],"name"=>$item['project_name']."-".$item['supplier_com_name']];
                    }
                    return $costs;
                }
                break;
            case 'field':
                $where['status'] = 1;
                $pid = I("pid");
                if(isset($pid) && $pid >0){
                    $where["city"]=$pid;
                }
                if ($key!=""){
                    $map["name"]=["LIKE","%{$key}%"];
                    $map["remark"]=["LIKE","%{$key}%"];
                    $map['_logic'] = 'or';
                    $where['_complex'] = $map;
                }
                $parameter=[
                    'where'=>$where,
                    'fields'=>'id,name',
                    'orderStr'=>"id DESC",
                ];
                $result = $this->fieldCom->getList($parameter);
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
    function projectList($search=""){
        $data=I("data");
        $p=I("p")?I("p"):1;
 
        $userId = session("userId");
        $nodeAuth = $this->nodeAuth[CONTROLLER_NAME.'/'.ACTION_NAME];
        $nodeId = getTabId(I("vtabId"));
        $process = $this->nodeCom->getProcess($nodeId);
        $this->assign("place",$process["place"]);
        $where=[];
        // $where['_string']=" (process_level = ".($this->processAuth["level"]-1)." OR process_level = 0 OR author = ".session("userId")." OR FIND_IN_SET(".session("userId").",examine)) OR (create_user = '{$userId}') OR FIND_IN_SET({$userId},business) OR FIND_IN_SET({$userId},leader) OR FIND_IN_SET({$userId},earlier_user) OR FIND_IN_SET({$userId},scene_user) OR (create_user = {$userId}') ";

        foreach (['name','code','customer_com_name','customer_cont_name','business_name','leader_name'] as $key) {
            if(isset($data[$key])){
                $where[$key]=['LIKE','%'.$data[$key].'%'];
            }
        }
        foreach (['brand'] as $key) {
            if(isset($data[$key])){
                $where[$key]=$data[$key];
            }
        }
        if(isset($data["project_time"])){
            $dateArr = explode(" - ",$data["project_time"]);
            $where["project_time"]  = array('between',array(strtotime($dateArr[0]." 00:00:00"),strtotime($dateArr[1]." 23:59:59")));
        }
        // print_r($where);
        $whiteWhere = "";
        $roleId = session('roleId');
        if($this->nodeAuth[CONTROLLER_NAME.'/'.ACTION_NAME]<7){
            $where["_string"] = "(FIND_IN_SET({$roleId},examine) <= process_level AND FIND_IN_SET({$roleId},examine) > 0) OR (author = {$userId}) OR (( FIND_IN_SET({$userId},business) OR FIND_IN_SET({$userId},leader) OR FIND_IN_SET({$userId},earlier_user) OR FIND_IN_SET({$userId},scene_user) OR (author = {$userId})) AND status =1 )";
            $whites = $this->whiteCom->getWhites();
            if($whites && $search==""){
                $whiteWhere = " AND user_id NOT IN (".implode(',',$whites).")";
            }
        }
        // $whites = $this->whiteCom->getWhites();
        // print_r($whites);exit;
        // if($nodeAuth<7){
        //     $where['_string'] = " ((create_user = {$userId})  OR FIND_IN_SET({$userId},business) OR FIND_IN_SET({$userId},leader) OR FIND_IN_SET({$userId},earlier_user) AND status =1) OR FIND_IN_SET({$userId},scene_user) OR (create_user = {$userId}) OR ((process_level = ".($this->processAuth["level"]-1)." OR process_level >= ".$this->processAuth["level"].") AND process_level <>0) OR FIND_IN_SET({$userId},examine) OR (author = {$userId})";
            // $where['user_id'] = session('userId');
            // if($process["place"]>0){
            //     $where=["process_level"=>[["eq",($process["place"]-1)],["egt",($process["place"])],"OR"],"status"=>1,'_logic'=>'OR'];
            // }else{
            //     $where=["status"=>1];
            // }
            // $where['_string'] = " ((create_user = {$userId})  OR FIND_IN_SET({$userId},business) OR FIND_IN_SET({$userId},leader) OR FIND_IN_SET({$userId},earlier_user) AND status =1) OR FIND_IN_SET({$userId},scene_user) OR (create_user = {$userId}) OR (process_level = ".($this->processAuth["level"]-1)." AND process_level <>0) OR FIND_IN_SET({$userId},examine) OR (author = {$userId})";
        // }
        //OR process_level = 0 OR author = {$userId} OR FIND_IN_SET({$userId},examine)

        if(isset($data['status'])){
            $where['status']=$data['status'];
        }else{
            
            $where['_string'] = isset($where['_string']) ? $where['_string'].=" AND status < 3" : $where['_string'].=" status < 3";
        }
        $file_type="1,2";
        if($data['template'] == 'schedule'){
            $file_type="3,4";
        }
        $parameter=[
            'where'=>$where,
            'fields'=>"*,FIND_IN_SET({$roleId},examine) place",
            'page'=>$p,
            'pageSize'=>$this->pageSize,
            'orderStr'=>"addTime DESC",
            "joins"=>[
                "LEFT JOIN (SELECT basicId brand_id,name brand_name FROM v_basic WHERE class = 'brand' ) b ON b.brand_id = brand",
                "LEFT JOIN (SELECT companyId company_id,company customer_com_name FROM v_customer_company ) c ON c.company_id = customer_com",
                "LEFT JOIN (SELECT contactId contact_id,contact customer_cont_name FROM v_customer_contact ) c2 ON c2.contact_id = customer_cont",
                "LEFT JOIN (SELECT userId user_id,userName business_name FROM v_user) bu ON bu.user_id = business",
                "LEFT JOIN (SELECT userId user_id,userName leader_name FROM v_user) lu ON lu.user_id = leader",
                "LEFT JOIN (SELECT basicId stage_id,name stage_name FROM v_basic WHERE class = 'stage' ) s ON s.stage_id = stage",
                "LEFT JOIN (SELECT basicId type_id,name type_name FROM v_basic WHERE class = 'projectType' ) t ON t.type_id = type",
                "LEFT JOIN (SELECT cid ctid ,city city_name,pid cpid FROM v_city ) ct ON ct.ctid = city AND ct.cpid = province",
                "LEFT JOIN (SELECT project_id fproject_id , count(id) file_num FROM v_project_files WHERE file_type IN ({$file_type}) GROUP BY fproject_id) f ON f.fproject_id = projectId ",
                "LEFT JOIN (SELECT project_id rproject_id ,advance radvance,surplus rsurplus FROM v_receivable ) r ON r.rproject_id = projectId",
                "LEFT JOIN (SELECT project_id pproject_id,SUM(contract_amount) pcontract_amount FROM v_purcha  WHERE status = 1 GROUP BY pproject_id) pu ON pu.pproject_id = projectId",
                "LEFT JOIN (SELECT project_id dproject_id,SUM(debit_money) ddebit_money FROM v_debit WHERE status = 1 {$whiteWhere} GROUP BY dproject_id ) de ON de.dproject_id = projectId",
                "LEFT JOIN (SELECT project_id eproject_id,SUM(money) emoney  FROM v_expense_sub LEFT JOIN (SELECT id exId,project_id FROM v_expense WHERE `status`=1 {$whiteWhere} ) m1 ON m1.exId=parent_id WHERE clear_status = 1) ex ON ex.eproject_id = projectId",
            ],
        ];
        $listResult=$this->projectCom->getProjectList($parameter);
        // echo $this->projectCom->M()->_sql();exit;
        if( isset($data['template'])){
            $listRedis = $data['template'].'List';
            $template = 'Project/projectTable/'.$listRedis;
            if($data['template'] == 'schedule'){
                foreach ($listResult['list'] as $key => $project) {
                    $uResult = $this->userCom->getUserOne(["fields"=>'group_concat(userName) earlier_users',"where"=>['userId'=>["IN",explode(",",$project["earlier_user"])]]]);
                    // print_r($project);
                    $listResult['list'][$key]["earlier_users"] = $uResult['list']['earlier_users'];
                }
            }elseif($data['template'] == 'business'){

            }
        }else{
            $listRedis = 'projectList';
            $template = 'Project/projectTable/'.$listRedis;
        }
        $this->tablePage($listResult,$template,$listRedis);
        // if($projectResult){
        //     $projectRed="projectList";
        //     $this->Redis->set($projectRed,json_encode($projectResult['list']),3600);
        //     $page = new \Think\VPage($projectResult['count'], $this->pageSize);
        //     $pageShow = $page->show();
        //     $this->assign('url',U(CONTROLLER_NAME.'/'.ACTION_NAME));
        //     $this->assign('projectList',$projectResult['list']);
        //     $countResult=$this->projectCom->count($where);
        //     $count="合同额：".number_format($countResult['totalAmount'])." | 总成本：".number_format($countResult['totalCost'])." | 总纯利：".number_format($countResult['totalProfit'])." | 总纯利率：".round($countResult['totalProfit']/$countResult['totalAmount']*100,2)."%";
        //     $this->ajaxReturn(['errCode'=>0,'table'=>$this->fetch('Project/projectTable/projectList'),'page'=>$pageShow,"count"=>$count]);
        // }
        // $this->ajaxReturn(['errCode'=>0,'table'=>'无数据','page'=>'']);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-08 20:31:11 
     * @Desc: 管理项目添加和修改的信息 
     */    
    function manageProjectInfo(){
        $reqType=I("reqType");
        $datas=I("data");
        $datas['project_id'] = $datas['project_id'] > 0? $datas['project_id'] : 0;
        if(isset($datas['earlier_user'])){
            $datas['earlier_user']=implode(",",$datas['earlier_user']);
        }
        if(isset($datas['scene_user'])){
            $datas['scene_user']=implode(",",$datas['scene_user']);
        }
        if(isset($datas['bid_date'])){
            $datas['bid_date']=strtotime($datas['bid_date']);
        }
        if(isset($datas['create_time'])){
            $datas['create_time']=strtotime($datas['create_time']);
        }
        if(isset($datas['project_time'])){
            $datas['project_time']= $datas['project_time'] !="" ? strtotime($datas['project_time']) : time();
        }
        $datas['days'] = $datas['days'] > 0 ? $datas['days'] : 1;
        // print_r($datas);exit;
        if($reqType=="projectAdd"){
            $datas['addTime']=time();
            $datas['time']=strtotime($datas['time']);
            $datas['author']=session('userId');
            // $datas['process_level']=$this->processAuth["level"];
            //添加时必备数据
            // $process = $this->nodeCom->getProcess(I("vtabId"));

            // $datas['process_id'] = $process["processId"];
            // $userRole = $this->userCom->getUserInfo($datas['leader']);
            // $datas['examine'] = $userRole['roleId'].",".$process["examine"];
            $examines = getComponent('Process')->getExamine(I("vtabId"),$datas['leader']);
            $datas['examine'] = $examines['examine'];
            $datas['process_id'] = $examines['process_id'];
            //如果是审批者自己提交的执行下列代码
            $roleId = session("roleId");
            // $examineArr = explode(",",$datas['examine']);
            // $rolePlace = search_last_key($roleId,$examineArr);
            $rolePlace = $examines['place'];
            $datas['status'] = 0;
            if($rolePlace!==false){
                $datas['process_level']=$rolePlace+2;
                if(count(explode(",",$examines['examine'])) <= ($rolePlace+1)){
                    $status = 1;
                }else{
                    $status = 2;
                }
            }else{
                $datas['process_level'] = $examines["place"] > 0 ? $examines["place"] : 1;
            }
            // if($rolePlace!==false){
            //     $datas['process_level']=$rolePlace+2;
            //     if(count($examineArr) <= ($rolePlace+1)){
            //         $datas['status'] = 1;
            //     }else{
            //         $datas['status'] = 2;
            //     }
            // }else{
            //     $datas['process_level']=$examines["place"] > 0 ? $examines["place"] : 1;
            // }

            unset($datas['projectId']);
            return $datas;
        }else if($reqType=="projectEdit"){
            $where=["projectId"=>$datas['projectId']];
            $data=[];

            $data['updateUser']=session('userId');
            foreach (['project_id','amount','bid_date','contract','bid_time','bidding','brand','city','code','create_time','author','customer_com','customer_cont','customer_other','days','earlier_user','execute_sub','execute','field','is_bid','business','leader','name','project_time','project_id','projectType','province','scene_user','session_all','type','session_cur','stage','status','cost_budget'] as  $key) {
                if(isset($datas[$key])){
                    $data[$key]=$datas[$key];
                }
            }
            if(isset($datas['status'])){
                $data['status'] = $datas['status'] == 3 ? 0 : $datas['status'];
                // $parameter=[
                //     'where'=>["projectId"=>$datas['projectId']],
                // ];
                // $result=$this->projectCom->getOne($parameter);
                // $data = $this->status_update($result,$datas["status"],$data);
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
        $datas=I("data");
        $projectInfo=$this->manageProjectInfo();
        // print_r($projectInfo);exit;
        if($projectInfo){
            $userArray = [];
            foreach (['leader','business','earlier_user','scene_user'] as $key) {
                if($datas[$key]){
                    if(is_array($datas[$key])){
                        $userArray = array_merge($userArray,$datas[$key]);
                    }else{
                        array_push($userArray,$datas[$key]);
                    }
                }
            }
            $userArray = array_unique($userArray);
            if(!empty($userArray)){
                $touser = $this->userCom->getQiyeId($userArray);
            }
            $insertResult=$this->projectCom->insertProject($projectInfo);
            if($insertResult && $insertResult->errCode==0){
                if(!empty($touser)){
                    $desc = "<div class=\"gray\">".date("Y年m月d日",time())."</div> <div class=\"normal\">".session('userName')."创建了项目【{$projectInfo['name']}】，当中@了你，来围观吧！</div>";
                    $url = C('qiye_url')."/Admin/Index/Main.html?action=Project/projectItem";
                    $msgResult = $this->QiyeCom-> textcard($touser,"立项【{$projectInfo['name']}】",$desc,$url);
                }
                $this->ApprLogCom->createApp($this->projectCom->tableName(),$insertResult->data,session("userId"),"");
                if($projectInfo['status']==1){
                    $this->ReceCom->createOrder($insertResult->data,session('userId'));
                }
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
        if(isset($updateResult->errCode) && $updateResult->errCode == 0){
            $this->ApprLogCom->updateStatus($this->projectCom->tableName(),$projectInfo["where"]["projectId"]);
        }
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
    function createCodeOne($companyId=0){
        $prefix = "TWSH";
        $retData = "";
        $cId = $companyId >0 ? $companyId : I('id');
        $numResult = $this->projectCom->M()->where(["project_id"=>0])->count();
        $comNumResult = $this->projectCom->M()->where(["customer_com"=>$cId])->count();
        $parameter=[
            'where'=>["companyId"=>$cId],
        ];
        $companyResult=$this->customerCom->getCompanyList($parameter,true);
        $retData = $prefix.($numResult+1).$companyResult['alias'].($comNumResult+1).date("YmdH");
        if($companyId>0){
            return $retData;
        }else{
            $this->ajaxReturn(['errCode'=>0,'data' => $retData]);
        }
        
    }
    /** 
     * @Author: vition 
     * @Date: 2018-07-03 22:22:24 
     * @Desc: 项目安排 
     */    
    function scheduleControl(){
        $reqType=I('reqType');
        $this->assign("controlName","project");//名字对应cust_company_modalOne，和cust_companyModal.html
        $this->assign('processArr',$this->processArr);

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
    function businessControl(){
        $reqType=I('reqType');
        $this->assign("controlName","business");//名字对应cust_company_modalOne，和cust_companyModal.html
        $this->assign('processArr',$this->processArr);

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
    /** 
     * @Author: vition 
     * @Date: 2018-09-19 19:45:46 
     * @Desc: 营业数据 
     */    
    function businessList(){
        $this->projectList("finance");
    }
    function business_modalOne(){
        $title = "立项/添加场次";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        if($gettype=="Edit"){
            $title = "项目营业数据汇总";
            $btnTitle = "保存数据";
            $redisName="businessList";

            $parameter=[
                "where" => ["projectId"=>$id],
                "fields" => "*",
                "joins" =>[
                    "LEFT JOIN (SELECT projectId project_pid,name project_name FROM v_project ) p ON p.project_pid = project_id",
                    "LEFT JOIN (SELECT basicId brand_id,name brand_name FROM v_basic WHERE class = 'brand' ) b ON b.brand_id = brand",
                    "LEFT JOIN (SELECT companyId company_id,company customer_com_name FROM v_customer_company ) c ON c.company_id = customer_com",
                    "LEFT JOIN (SELECT contactId contact_id,contact customer_cont_name FROM v_customer_contact ) c2 ON c2.contact_id = customer_cont",
                    "LEFT JOIN (SELECT id field_id,name field_name,city f_city FROM v_field ) f ON f.field_id = field AND f.f_city=city",
                    "LEFT JOIN (SELECT userId user_id,userName create_user_name FROM v_user) cu ON cu.user_id = author",
                    "LEFT JOIN (SELECT userId user_id,userName business_name FROM v_user) bu ON bu.user_id = business",
                    "LEFT JOIN (SELECT userId user_id,userName leader_name FROM v_user) lu ON lu.user_id = leader",
                    "LEFT JOIN (SELECT basicId execute_sub_id,name execute_sub_name FROM v_basic WHERE class = 'execute' ) e ON e.execute_sub_id = execute_sub",
                    "LEFT JOIN (SELECT basicId type_id,name type_name FROM v_basic WHERE class = 'projectType' ) pt ON pt.type_id = type",
                    "LEFT JOIN (SELECT basicId stage_id,name stage_name FROM v_basic WHERE class = 'stage' ) s ON s.stage_id = stage",
                    "LEFT JOIN (SELECT project_id project_sid,COUNT(projectId) session_count FROM v_project WHERE projectId = 0 GROUP BY project_id) sc ON sc.project_sid = projectId",
                ]
            ];
            $resultData = $this->projectCom->getOne($parameter)["list"];

            $resultData["project_time"] = date("Y-m-d",$resultData["project_time"]);
            $resultData["create_time"] = date("Y-m-d",$resultData["create_time"]);
            $resultData["bid_date"] = date("Y-m-d",strtotime($resultData["bid_date"]));
            $resultData["bid_time"] = date("H:i:s",$resultData["bid_time"]);
            $resultData["end_date"] = date("Y-m-d",strtotime($resultData["project_time"]." +".$resultData["days"]."day"));
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
            "template"=>"businessModal",
        ];
        $this->modalOne($modalPara);
    }
    function project_file_modalOne(){
        $title = "报价/成本 文件列表";
        $btnTitle = "确定添加";
        $gettype = I("gettype");
        $this->assign("controlName","project_file");
        if($gettype=="business"){
            $this->assign("fileType",[1=>"报价",2=>"成本"]);
            $typeWhere = ["IN",[1,2]];
        }else{
            $this->assign("fileType",[3=>"方案",4=>"标书"]);
            $typeWhere = ["IN",[3,4]];
        }
        $project_id = I("id");
        
        $projectResult = $this->projectCom->getOne(["where"=>["projectId"=>$project_id],"fields"=>"business,leader"])["list"];
        $hasEdit = false;
        if($projectResult[$gettype] == session("userId")){
            $hasEdit = true;
        }
        $this->assign("hasEdit",$hasEdit);
        $this->assign("project_id",$project_id);
        $where = [
            "file_type" => $typeWhere,
            "project_id" => $project_id,
        ];
        $param = [
            "fields" => "*,FROM_UNIXTIME(add_time,'%Y-%m-%d %H:%i:%s') add_time",
            "where" => $where,
            "joins" =>[
                "LEFT JOIN (SELECT userId,userName user_name FROM v_user) u ON u.userId = user_id",
                "LEFT JOIN(SELECT projectId,business,leader FROM v_project) p ON p.projectId = project_id",
            ],
        ];
        $resultData = $this->filesCom->getList($param);
        // print_r($resultData);
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"filesModal",
            "dataList" => true,
        ];
        
        $this->modalOne($modalPara);
    }
    function project_fileAdd(){
        extract($_REQUEST);
        $allNum = count($data);
        $num = 0;
        $this->filesCom->M()->startTrans();
        foreach ($data as $fileInfo) {
            $insertData = [
                'project_id' => $project_id,
                'file_type' => $fileInfo["file_type"],
                'user_id' => session("userId"),
                'add_time' => time(),
                'file_path' => $fileInfo["file_path"],
            ];
            $fileArr = explode("/",$fileInfo["file_path"]);
            $insertData['file_name'] = isset($fileInfo["file_name"]) && $fileInfo["file_name"]!="" ? $fileInfo["file_name"] : explode(".",$fileArr[count($fileArr)-1])[0];
            $insertRes = $this->filesCom->insert($insertData);
            if(isset($insertRes->errCode) && $insertRes->errCode ==0){
                $num++;
            }
        }
        if($num>0 && $allNum==$num){
            $this->filesCom->M()->commit();
            $this->ajaxReturn(['errCode'=>0,'data' => getError(0)]);
        }else{
            $this->filesCom->M()->rollback();
            $this->ajaxReturn(['errCode'=>111,'data' => getError(111)]);
        }
        // $this->filesCom->insert($insertData);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-09-19 09:03:06 
     * @Desc: 创建应收列表 
     */    
    function createRec(){

    }
}