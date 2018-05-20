<?php
namespace Admin\Controller;

/** 
 * @Author: vition 
 * @Date: 2018-05-20 22:24:35 
 * @Desc: 基本数据管理 
 */
class BasicController extends BaseController{
    public function _initialize() {
        parent::_initialize();
        $this->basicCom=getComponent('Basic');
    }
    //品牌管理开始
    /** 
     * @Author: vition 
     * @Date: 2018-05-20 22:30:14 
     * @Desc: 品牌管理 
     */    
    function brandControl(){
        $reqType=I('reqType');
        if($reqType){
            $this->$reqType();
        }else{
            $this->assign('url',U(CONTROLLER_NAME.'/'.ACTION_NAME));
            $this->returnHtml();
        }
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-20 22:45:25 
     * @Desc: 品牌列表 
     */    
    function brandList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $where=["class"=>"brand"];

        if($data['name']){
            $where['name']=['LIKE','%'.$data['name'].'%'];
        }
        if($data['alias']){
            $where['alias']=['LIKE','%'.$data['alias'].'%'];
        }
        $parameter=[
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$this->pageSize,
            'orderStr'=>"basicId DESC",
        ];
        $basicResult=$this->basicCom->getBasicList($parameter);
        if($basicResult){
            $basicRed="brandList";
            $this->Redis->set($basicRed,json_encode($basicResult['list']),3600);
            $page = new \Think\VPage($basicResult['count'], $this->pageSize);
            $pageShow = $page->show();
            $this->assign('url',U(CONTROLLER_NAME.'/'.ACTION_NAME));
            $this->assign('brandList',$basicResult['list']);
            $this->ajaxReturn(['errCode'=>0,'table'=>$this->fetch('Basic/basicTable/brandList'),'page'=>$pageShow,"count"=>$count]);
        }
        $this->ajaxReturn(['errCode'=>0,'table'=>'无数据','page'=>'']);
    }

    function brandOne(){
        $id	=I("id");
        $parameter=[
            'basicId'=>$id,
        ];
        $blistRed="brandList";
        $brandList=$this->Redis->get($blistRed);
        $blist=[];
        if($brandList){
            foreach ($brandList as $brand) {
               if($brand['basicId']==$id){
                $blist=$brand;
                break;
               }
            }
        }
        if(empty($blist)){
            $basicResult=$this->basicCom->getOne($parameter);
            if($basicResult->errCode==0){
                $blist=$basicResult->data;
            }
        }
        if(!empty($blist)){
            $this->ajaxReturn(['errCode'=>0,'info'=>$blist]);
        }
        $this->ajaxReturn(['errCode'=>110,'info'=>'无数据']);
    }
    function manageBrandInfo(){
        $reqType=I("reqType");
        $datas=I("data");
        if($reqType=="brandAdd"){
            $datas['class']="brand";
            unset($datas['basicId']);
            return $datas;
        }else if($reqType=="brandEdit"){
            $where=["basicId"=>$datas['basicId']];
            $data=[];
            if(isset($datas['name'])){
                $data['name']=$datas['name'];
            }
            if(isset($datas['alias'])){
                $data['alias']=$datas['alias'];
            }
            if(isset($datas['remark'])){
                $data['remark']=$datas['remark'];
            }
            return ["where"=>$where,"data"=>$data];
        }
        return "";
    }
    function brandAdd(){
        $brandInfo=$this->manageBrandInfo();
        if($brandInfo){
            $insertResult=$this->basicCom->insertBasic($brandInfo);
            if($insertResult && $insertResult->errCode==0){
                $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
            }
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    } 
    function brandEdit(){
        $brandInfo=$this->managebrandInfo();
        $updateResult=$this->basicCom->updateBasic($brandInfo);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
    //品牌管理结束
    //场地管理开始
    function fieldControl(){
        $reqType=I('reqType');
        if($reqType){
            $this->$reqType();
        }else{
            $this->assign('url',U(CONTROLLER_NAME.'/'.ACTION_NAME));
            $this->returnHtml();
        }
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-20 22:45:25 
     * @Desc: 品牌列表 
     */    
    function fieldList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $where=["class"=>"field"];

        if($data['name']){
            $where['name']=['LIKE','%'.$data['name'].'%'];
        }
        if($data['alias']){
            $where['alias']=['LIKE','%'.$data['alias'].'%'];
        }
        $parameter=[
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$this->pageSize,
            'orderStr'=>"basicId DESC",
        ];
        $basicResult=$this->basicCom->getBasicList($parameter);
        if($basicResult){
            $basicRed="fieldList";
            $this->Redis->set($basicRed,json_encode($basicResult['list']),3600);
            $page = new \Think\VPage($basicResult['count'], $this->pageSize);
            $pageShow = $page->show();
            $this->assign('url',U(CONTROLLER_NAME.'/'.ACTION_NAME));
            $this->assign('fieldList',$basicResult['list']);
            $this->ajaxReturn(['errCode'=>0,'table'=>$this->fetch('Basic/basicTable/fieldList'),'page'=>$pageShow,"count"=>$count]);
        }
        $this->ajaxReturn(['errCode'=>0,'table'=>'无数据','page'=>'']);
    }

    function fieldOne(){
        $id	=I("id");
        $parameter=[
            'basicId'=>$id,
        ];
        $blistRed="fieldList";
        $fieldList=$this->Redis->get($blistRed);
        $blist=[];
        if($fieldList){
            foreach ($fieldList as $field) {
               if($field['basicId']==$id){
                $blist=$field;
                break;
               }
            }
        }
        if(empty($blist)){
            $basicResult=$this->basicCom->getOne($parameter);
            if($basicResult->errCode==0){
                $blist=$basicResult->data;
            }
        }
        if(!empty($blist)){
            $this->ajaxReturn(['errCode'=>0,'info'=>$blist]);
        }
        $this->ajaxReturn(['errCode'=>110,'info'=>'无数据']);
    }
    function managefieldInfo(){
        $reqType=I("reqType");
        $datas=I("data");
        if($reqType=="fieldAdd"){
            $datas['class']="field";
            unset($datas['basicId']);
            return $datas;
        }else if($reqType=="fieldEdit"){
            $where=["basicId"=>$datas['basicId']];
            $data=[];
            if(isset($datas['name'])){
                $data['name']=$datas['name'];
            }
            if(isset($datas['alias'])){
                $data['alias']=$datas['alias'];
            }
            if(isset($datas['remark'])){
                $data['remark']=$datas['remark'];
            }
            return ["where"=>$where,"data"=>$data];
        }
        return "";
    }
    function fieldAdd(){
        $fieldInfo=$this->managefieldInfo();
        if($fieldInfo){
            $insertResult=$this->basicCom->insertBasic($fieldInfo);
            if($insertResult && $insertResult->errCode==0){
                $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
            }
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    } 
    function fieldEdit(){
        $fieldInfo=$this->managefieldInfo();
        $updateResult=$this->basicCom->updateBasic($fieldInfo);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
    //场地管理结束
}