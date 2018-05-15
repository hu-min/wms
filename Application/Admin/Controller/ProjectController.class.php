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
     * @Date: 2018-05-06 11:00:23 
     * @Desc: 项目列表 
     */    
    function projectList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $where=[];
        if($data['name']){
            $where['name']=['LIKE','%'.$data['name'].'%'];
        }
        if($data['toCompany']){
            $where['toCompany']=['LIKE','%'.$data['toCompany'].'%'];
        }
        if($data['followUp']){
            $where['followUp']=['LIKE','%'.$data['followUp'].'%'];
        }
        if($data['business']){
            $where['business']=['LIKE','%'.$data['business'].'%'];
        }
        if($data['leader']){
            $where['leader']=['LIKE','%'.$data['leader'].'%'];
        }
        if($data['responsible']){
            $where['responsible']=['LIKE','%'.$data['responsible'].'%'];
        }
        if($data['time']){
            $times=explode("~",$data['time']);
            $where['time']=[["EGT",strtotime($times[0])],["ELT",strtotime($times[1])]];
        }
        if(isset($data['status'])){
            $where['status']=$data['status'];
        }
        $parameter=[
            'where'=>$where,
            'fields'=>"projectId,code,name,time,status,toCompany,followUp,business,leader,responsible,num,invoice,paySign,advanceDate,amount,advance,surplus,cost,profit,profitRate,addTime,creator,updateTime,updateUser,authority,c.company toCompanyStr,c.contact toContactStr",
            'page'=>$p,
            'pageSize'=>$this->pageSize,
            'orderStr'=>"time DESC",
            "joins"=>"LEFT JOIN (SELECT customerId,company,contact FROM v_customer) c ON c.customerId=toCompany",
        ];
        
        $projectResult=$this->projectCom->getProjectList($parameter);
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
        if(isset($datas['followUp'])){
            $datas['followUp']=implode(",",$datas['followUp']);
        }
        if(isset($datas['business'])){
            $datas['business']=implode(",",$datas['business']);
        }
        if(isset($datas['leader'])){
            $datas['leader']=implode(",",$datas['leader']);
        }
        if(isset($datas['responsible'])){
            $datas['responsible']=implode(",",$datas['responsible']);
        }
        if($reqType=="projectAdd"){
            $datas['addTime']=time();
            $datas['time']=strtotime($datas['time']);
            $datas['creator']=session('userId');
            unset($datas['projectId']);
            return $datas;
        }else if($reqType=="projectEdit"){
            $where=["projectId"=>$datas['projectId']];
            $data=[];
            $data['updateUser']=session('userId');
            if(isset($datas['name'])){
                $data['name']=$datas['name'];
            }
            if(isset($datas['time'])){
                $data['time']=strtotime($datas['time']);
            }
            if(isset($datas['toCompany'])){
                $data['toCompany']=$datas['toCompany'];
            }
            $data['updateTime']=time();
            if(isset($datas['followUp'])){
                $data['followUp']=$datas['followUp'];
            }
            if(isset($datas['business'])){
                $data['business']=$datas['business'];
            }
            if(isset($datas['leader'])){
                $data['leader']=$datas['leader'];
            }
            if(isset($datas['responsible'])){
                $data['responsible']=$datas['responsible'];
            }
            if(isset($datas['num'])){
                $data['num']=$datas['num'];
            }
            if(isset($datas['invoice'])){
                $data['invoice']=$datas['invoice'];
            }
            if(isset($datas['paySign'])){
                $data['paySign']=$datas['paySign'];
            }
            if(isset($datas['advanceDate'])){
                $data['advanceDate']=strtotime($datas['advanceDate']);
            }
            if(isset($datas['amount'])){
                $data['amount']=$datas['amount'];
            }
            if(isset($datas['advance'])){
                $data['advance']=$datas['advance'];
            }
            if(isset($datas['surplus'])){
                $data['surplus']=$datas['surplus'];
            }
            if(isset($datas['cost'])){
                $data['cost']=$datas['cost'];
            }
            if(isset($datas['profit'])){
                $data['profit']=$datas['profit'];
            }
            if(isset($datas['profitRate'])){
                $data['profitRate']=$datas['profitRate'];
            }
            if(isset($datas['status'])){
                $data['status']=$datas['status'];
            }
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
}