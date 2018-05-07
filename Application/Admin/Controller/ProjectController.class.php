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
        $this->processArr=["0"=>"未中标","1"=>"已完成","2"=>"洽谈中","3"=>"进行中","4"=>"已删除"];
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
        if($data['time']){
            $times=explode("~",$data['time']);
            $where['time']=[["EGT",strtotime($times[0])],["ELT",strtotime($times[1])]];
        }
        if(isset($data['status'])){
            $where['status']=$data['status'];
        }
        $parameter=[
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$this->pageSize,
            'orderStr'=>"time DESC"
        ];
        
        $projectResult=$this->projectCom->getProjectList($parameter);
        if($projectResult){
            $projectRed="projectList_".session("userId");
            $this->Redis->set($projectRed,json_encode($projectResult['list']),3600);
            $page = new \Think\VPage($projectResult['count'], $this->pageSize);
            $pageShow = $page->show();
            $this->assign('url',U(CONTROLLER_NAME.'/'.ACTION_NAME));
            $this->assign('projectList',$projectResult['list']);
            $this->assign('artClsList',$this->getArtClsList());
            $countResult=$this->projectCom->count($where);
            $count="营业总额：".number_format($countResult['totalAmount'])." | 总成本：".number_format($countResult['totalCost'])." | 总利润：".number_format($countResult['totalProfit']);
            $this->ajaxReturn(['errCode'=>0,'table'=>$this->fetch('Project/projectTable/projectList'),'page'=>$pageShow,"count"=>$count]);
        }
        $this->ajaxReturn(['errCode'=>0,'table'=>'无数据','page'=>'']);
    }
    function manageProjectInfo(){
        $reqType=I("reqType");
        $datas=I("data");

        if($reqType=="projectAdd"){
            $datas['addTime']=time();
            $datas['time']=strtotime($datas['time']);
            unset($datas['projectId']);
            return $datas;
        }else if($reqType=="projectEdit"){
            $where=["projectId"=>$datas['projectId']];
            $data=[];

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
                $data['advanceDate']=$datas['advanceDate'];
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
    function projectConfig(){

    }
}
