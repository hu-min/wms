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
        $this->processArr=["0"=>"未中标","1"=>"已完成","2"=>"洽谈中","3"=>"进行中","4"=>"清算期","5"=>"结案","6"=>"已删除"];
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
            $count="营业总额：".number_format($countResult['totalAmount'])." | 总成本：".number_format($countResult['totalCost'])." | 总利润：".number_format($countResult['totalProfit']);
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
            $this->assign("project",$project);
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
}
