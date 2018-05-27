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
        $this->statusType=["0"=>"未启用","1"=>"启用"];
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
        $this->assign('statusType',$this->statusType);
        if($reqType){
            $this->$reqType();
        }else{
            $this->assign('url',U(CONTROLLER_NAME.'/'.ACTION_NAME));
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
        $where=[];
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
        }
        $parameter=[
            'where'=>$where,
            'fields'=>"`companyId`,`company`,`alias`,`provinceId`,`cityId`,`province`,`city`,`address`,`remarks`,`addTime`,`updateTime`,`status`",
            'page'=>$p,
            'pageSize'=>$this->pageSize,
            'orderStr'=>"companyId DESC",
            "joins"=>["LEFT JOIN v_province p ON p.pid=provinceId","LEFT JOIN v_city c ON c.pid=p.pid AND c.cid=cityId"],
        ];
        
        $listResult=$this->customerCom->getCompanyList($parameter);
        if($listResult){
            $companyRed="cuscompanyList";
            $this->Redis->set($companyRed,json_encode($listResult['list']),3600);
            $page = new \Think\VPage($listResult['count'], $this->pageSize);
            $pageShow = $page->show();
            $this->assign('url',U(CONTROLLER_NAME.'/'.ACTION_NAME));
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
            if(isset($datas['province'])){
                $data['province']=$datas['province'];
            }
            if(isset($datas['city'])){
                $data['city']=$datas['city'];
            }
            if(isset($datas['address'])){
                $data['address']=$datas['address'];
            }
            if(isset($datas['status'])){
                $data['status']=$datas['status'];
            }
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
        $parameter=[
            'companyId'=>$id,
        ];
        $pListRed="cuscompanyList";
        $companyList=$this->Redis->get($pListRed);
        $plist=[];
        if($companyList){
            foreach ($companyList as $company) {
               if($company['companyId']==$id){
                $plist=$company;
                break;
               }
            }
        }
        if(empty($plist)){
            $companyResult=$this->customerCom->getCompanyList($parameter,true);
            if($companyResult->errCode==0){
                $plist=$companyResult->data;
            }
        }
        if(!empty($plist)){
            $this->ajaxReturn(['errCode'=>0,'info'=>$plist]);
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
            $this->assign('url',U(CONTROLLER_NAME.'/'.ACTION_NAME));
            $this->assign('statusType',$this->statusType);
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
        $where=[];
        if($data['companyId']){
            $where['companyId']=$data['companyId'];
        }
        if($data['contact']){
            $where['contact']=['LIKE','%'.$data['contact'].'%'];
        }
        $parameter=[
            'fields'=>"`contactId`,`companyId`,`contact`,`phone`,`email`,`address`,`remarks`,`addTime`,`updateTime`,`status`,company",
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$this->pageSize,
            'orderStr'=>"contactId DESC",
            "joins"=>"LEFT JOIN (SELECT companyId cid,company FROM v_customer_company WHERE status=1) c ON c.cid=companyId",
        ];
        
        $listResult=$this->customerCom->getCustomerList($parameter);
        // print_r($listResult);
        if($listResult){
            $contactRed="cuscontactList";
            $this->Redis->set($contactRed,json_encode($listResult['list']),3600);
            $page = new \Think\VPage($listResult['count'], $this->pageSize);
            $pageShow = $page->show();
            $this->assign('url',U(CONTROLLER_NAME.'/'.ACTION_NAME));
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
                $data['status']=$datas['status'];
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
            'contactId'=>$id,
        ];
        $pListRed="cuscontactList";
        $contactList=$this->Redis->get($pListRed);
        $plist=[];
        if($contactList){
            foreach ($contactList as $contact) {
               if($contact['contactId']==$id){
                $plist=$contact;
                break;
               }
            }
        }
        if(empty($plist)){
            $contactResult=$this->customerCom->getUser($parameter);
            if($contactResult->errCode==0){
                $plist=$contactResult->data;
            }
        }
        if(!empty($plist)){
            $this->ajaxReturn(['errCode'=>0,'info'=>$plist]);
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