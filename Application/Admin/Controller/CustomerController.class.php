<?php
namespace Admin\Controller;

/** 
 * @Author: vition 
 * @Date: 2018-05-09 22:46:55 
 * @Desc: 客户管理 
 */
class CustomerController extends BaseController{
    protected $pageSize=10;
    public function _initialize() {
        parent::_initialize();
        $this->basicCom=getComponent('Basic');
        $this->customerCom=getComponent('Customer');
    }

    //内部公用方法
    /** 
     * @Author: vition 
     * @Date: 2018-05-27 15:13:52 
     * @Desc: 内部获取供应商类型列表
     */    
    protected function getCusCompany($key=""){
        $where=["status"=>"1"];
        if ($key!=""){
            $where["company"]=["LIKE","%{$key}%"];
        }
        $parameter=[
            'where'=>$where,
            'fields'=>'companyId,company',
            'page'=>1,
            'pageSize'=>20,
            'orderStr'=>"companyId DESC",
        ];
        $cusCompanyResult = $this->customerCom->getCompanyList($parameter);
        return $cusCompanyResult['list'] ? $cusCompanyResult['list'] : [];
    }
    //内部公用方法结束

    //客户公司管理开始
    function companyControl(){
        $reqType=I('reqType');
        
        if($reqType){
            $this->$reqType();
        }else{
            $this->assign("controlName","company");
            $this->assign("province",$this->basicCom->get_provinces());
            $this->returnHtml();
        }
    }

    function getCityList(){
        $pid=I("pid");
        $cityList=$this->basicCom->get_citys($pid);
        $this->ajaxReturn(['errCode'=>0,'citys'=>$cityList]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-09 23:51:01 
     * @Desc: 客户列表 
     */    
    function companyList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        // $where=["processLevel"=>[($this->processAuth["level"]-1),0,"OR"]];
        $where['_string']=" (processLevel = ".($this->processAuth["level"]-1)." OR processLevel = 0 OR author = ".session("userId")." OR FIND_IN_SET(".session("userId").",examine))";
        if($data['company']){
            $where['company']=['LIKE','%'.$data['company'].'%'];
        }
        if($data['alias']){
            $where['alias']=['LIKE','%'.$data['alias'].'%'];
        }
        if($data['provinceId']){
            $where['provinceId']=$data['provinceId'];
        }
        if($data['cityId']){
            $where['cityId']=$data['cityId'];
        }
        if(isset($data['status'])){
            $where['status']=$data['status'];
        }else{
            $where['_string'].=" AND status < 3";
        }
        $parameter=[
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$this->pageSize,
            'orderStr'=>"companyId DESC",
        ];
        
        $listResult=$this->customerCom->getCompanyList($parameter);
        if($listResult){
            $page = new \Think\VPage($listResult['count'], $this->pageSize);
            $pageShow = $page->show();
            $this->assign('list',$listResult['list']);

            $this->ajaxReturn(['errCode'=>0,'table'=>$this->fetch('Customer/customerTable/companyList'),'page'=>$pageShow]);
        }
        $this->ajaxReturn(['errCode'=>0,'table'=>'无数据','page'=>'']);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-09 23:43:33 
     * @Desc: 添加和修改信息管理 
     */    
    function manageCompanyInfo(){
        $reqType=I("reqType");
        $datas=I("data");
        if($reqType=="companyAdd"){
            $datas['addTime']=time();
            $datas['processLevel']=$this->processAuth["level"];
            $datas['author']=session("userId");
            unset($datas['companyId']);
            return $datas;
        }else if($reqType=="companyEdit"){
            $where=["companyId"=>$datas['companyId']];
            $data=[];
            if(isset($datas['company'])){
                $data['company']=$datas['company'];
            }
            if(isset($datas['alias'])){
                $data['alias']=$datas['alias'];
            }
            if(isset($datas['provinceId'])){
                $data['provinceId']=$datas['provinceId'];
            }
            if(isset($datas['cityId'])){
                $data['cityId']=$datas['cityId'];
            }
            if(isset($datas['address'])){
                $data['address']=$datas['address'];
            }
            if(isset($datas['status'])){
                $parameter=[
                    'where'=>["companyId"=>$id],
                ];
                $result=$this->customerCom->getCompanyList($parameter,true);
                if($result["examine"]==""){
                    $data['examine']=session("userId");
                }else{
                    $data['examine'].=",".session("userId");
                }
		        if($datas['status']==1 && $this->processAuth["level"] == $this->processAuth["allLevel"]){
                    $data['status']=$datas['status'];
                    $data['processLevel'] = 0;
                }else if($datas['status']==1){
                    $data['status']=2;
                    $data['processLevel'] = $this->processAuth["level"];
                }
            }
            $data['upateTime']=time();
            if(isset($datas['remarks'])){
                $data['remarks']=$datas['remarks'];
            }
            return ["where"=>$where,"data"=>$data];
        }
        return "";
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-09 23:46:44 
     * @Desc: 添加客户信息 
     */    
    function companyAdd(){
        $dataInfo=$this->manageCompanyInfo();
        if($dataInfo){
            $insertResult=$this->customerCom->insertCompany($dataInfo);
            if($insertResult && $insertResult->errCode==0){
                $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
            }
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-09 23:59:28 
     * @Desc: 获取单一条客户信息 
     */    
    function companyOne(){
        $id	=I("id");
        // $result=$this->id_get_company($id);
        $parameter=[
            'where'=>["companyId"=>$id],
        ];
        $result=$this->customerCom->getCompanyList($parameter,true);
        if(!empty($result)){
            $this->ajaxReturn(['errCode'=>0,'info'=>$result]);
        }
        $this->ajaxReturn(['errCode'=>110,'info'=>'无数据']);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-10 00:02:10 
     * @Desc: 修改客户信息 
     */    
    function companyEdit(){
        $companyInfo=$this->manageCompanyInfo();
        $updateResult=$this->customerCom->updateCompany($companyInfo);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
    //客户公司管理结束

    //客户联系人管理开始
    /** 
     * @Author: vition 
     * @Date: 2018-05-09 23:42:41 
     * @Desc: 客户信息控制 
     */    
    function contactControl(){
        $reqType=I('reqType');
        if($reqType){
            $this->$reqType();
        }else{
            $this->assign("cusCompanyList",$this->getCusCompany());
            $this->returnHtml();
        }
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-27 15:16:49 
     * @Desc: 接口获取客户公司列表 
     */    
    function getCusCompanyList(){
        $key=I("key");
        $this->ajaxReturn(["data"=>$this->getCusCompany($key)]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-09 23:51:01 
     * @Desc: 客户列表 
     */    
    function contactList(){
        $data=I("data");
        $p=I("p")?I("p"):1;

        $where['_string']=" (processLevel = ".($this->processAuth["level"]-1)." OR processLevel = 0 OR author = ".session("userId")." OR FIND_IN_SET(".session("userId").",examine))";
        if($data['companyId']){
            $where['companyId']=$data['companyId'];
        }
        if($data['contact']){
            $where['contact']=['LIKE','%'.$data['contact'].'%'];
        }
        if(isset($data['status'])){
            $where['status']=$data['status'];
        }else{
            $where['_string'].=" AND status < 3";
        }
        $parameter=[
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$this->pageSize,
            'orderStr'=>"contactId DESC",
        ];
        
        $listResult=$this->customerCom->getCustomerList($parameter);
        // echo $this->customerCom->M()->_sql();
        // print_r($listResult);
        if($listResult){
            $page = new \Think\VPage($listResult['count'], $this->pageSize);
            $pageShow = $page->show();
            $this->assign('list',$listResult['list']);

            $this->ajaxReturn(['errCode'=>0,'table'=>$this->fetch('Customer/customerTable/contactList'),'page'=>$pageShow]);
        }
        $this->ajaxReturn(['errCode'=>0,'table'=>'无数据','page'=>'']);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-09 23:43:33 
     * @Desc: 添加和修改信息管理 
     */    
    function manageContactInfo(){
        $reqType=I("reqType");
        $datas=I("data");
        if($reqType=="contactAdd"){
            $datas['addTime']=time();
            $datas['processLevel']=$this->processAuth["level"];
            $datas['author']=session("userId");
            unset($datas['contactId']);
            return $datas;
        }else if($reqType=="contactEdit"){
            $where=["contactId"=>$datas['contactId']];
            $data=[];
            $data['updateTime']=time();
            if(isset($datas['companyId'])){
                $data['companyId']=$datas['companyId'];
            }
            if(isset($datas['contact'])){
                $data['contact']=$datas['contact'];
            }
            if(isset($datas['phone'])){
                $data['phone']=$datas['phone'];
            }
            if(isset($datas['email'])){
                $data['email']=$datas['email'];
            }
            if(isset($datas['address'])){
                $data['address']=$datas['address'];
            }
            if(isset($datas['remarks'])){
                $data['remarks']=$datas['remarks'];
            }
            if(isset($datas['status'])){
                $parameter=[
                    'where'=>["contactId"=>$id],
                ];
                $result=$this->customerCom->getCustomerList($parameter,true);
                if($result["examine"]==""){
                    $data['examine']=session("userId");
                }else{
                    $data['examine'].=",".session("userId");
                }
                if($datas['status']==1 && $this->processAuth["level"] == $this->processAuth["allLevel"]){
                    $data['status']=$datas['status'];
                    $data['processLevel'] = 0;
                }else if($datas['status']==1){
                    $data['status']=2;
                    $data['processLevel'] = $this->processAuth["level"];
                }
            }
            $data['upateTime']=time();
            return ["where"=>$where,"data"=>$data];
        }
        return "";
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-09 23:46:44 
     * @Desc: 添加客户信息 
     */    
    function contactAdd(){
        $dataInfo=$this->manageContactInfo();
        if($dataInfo){
            $insertResult=$this->customerCom->insertContact($dataInfo);
            if($insertResult && $insertResult->errCode==0){
                $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
            }
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-09 23:59:28 
     * @Desc: 获取单一条客户信息 
     */    
    function contactOne(){
        $id	=I("id");
        $parameter=[
            'where'=>["contactId"=>$id],
        ];
        $result=$this->customerCom->getCustomerList($parameter,true);
        if(!empty($result)){
            $this->ajaxReturn(['errCode'=>0,'info'=>$result]);
        }
        $this->ajaxReturn(['errCode'=>110,'info'=>'无数据']);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-10 00:02:10 
     * @Desc: 修改客户信息 
     */    
    function contactEdit(){
        $contactInfo=$this->manageContactInfo();
        $updateResult=$this->customerCom->updateContact($contactInfo);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
}
