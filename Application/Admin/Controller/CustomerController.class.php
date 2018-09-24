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
        $this->statusType=[0=>"未启用",1=>"启用",3=>"无效",4=>"删除"];
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
        $this->assign("province",$this->basicCom->get_provinces());
        $this->assign('tableName',"VCustomerCompany");//删除数据的时候需要
        $this->assign("controlName","cust_company");//名字对应cust_company_modalOne，和cust_companyModal.html
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    /** 
     * @Author: vition 
     * @Date: 2018-06-02 23:47:09 
     * @Desc: 添加和修改获取modal html页面 
     */    
    function cust_company_modalOne(){
        $title = "新建客户公司";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        
        if($gettype=="Edit"){
            $title = "编辑客户公司";
            $btnTitle = "保存数据";
            $redisName="cust_companyList";
            $resultData=$this->customerCom->redis_one($redisName,"companyId",$id,"ccompanyDB");
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"companyModal",
        ];
        $this->modalOne($modalPara);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-06-02 23:47:44 
     * @Desc: 取城市列表 
     */    
    function getCityList(){
        $pid=I("pid");
        $cityList=$this->basicCom->get_citys($pid);
        $this->ajaxReturn(['errCode'=>0,'data'=>$cityList]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-09 23:51:01 
     * @Desc: 客户列表 
     */    
    function cust_companyList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        // $where=["process_level"=>[($this->processAuth["level"]-1),0,"OR"]];
        $where['_string']=" (process_level = ".($this->processAuth["level"]-1)." OR process_level = 0 OR author = ".session("userId")." OR FIND_IN_SET(".session("userId").",examine))";

        foreach (['company','alias'] as $key) {
            if($data[$key]){
                $where[$key]=['LIKE','%'.$data[$key].'%'];
            }
        }
        foreach (['provinceId','cityId'] as $key) {
            if($data[$key]){
                $where[$key]=$data[$key];
            }
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
        $this->tablePage($listResult,'Customer/customerTable/companyList',"cust_companyList");
        // if($listResult){
        //     $page = new \Think\VPage($listResult['count'], $this->pageSize);
        //     $pageShow = $page->show();
        //     $this->assign('list',$listResult['list']);
        //     $this->assign('tableName',"CustomerCompany");
        //     $this->ajaxReturn(['errCode'=>0,'table'=>$this->fetch('Customer/customerTable/companyList'),'page'=>$pageShow]);
        // }
        // $this->ajaxReturn(['errCode'=>0,'table'=>'无数据','page'=>'']);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-09 23:43:33 
     * @Desc: 添加和修改信息管理 
     */    
    function manageCompanyInfo(){
        $reqType=I("reqType");
        $datas=I("data");
        if($reqType=="cust_companyAdd"){
            $datas['addTime']=time();
            $datas['process_level']=$this->processAuth["level"];
            $datas['author']=session("userId");
            unset($datas['companyId']);
            return $datas;
        }else if($reqType=="cust_companyEdit"){
            $where=["companyId"=>$datas['companyId']];
            $data=[];
            foreach (['company','alias','provinceId','cityId','address','status'] as $key ) {
                if(isset($datas[$key])){
                    $data[$key]=$datas[$key];
                }
            }
            // if(isset($datas['company'])){
            //     $data['company']=$datas['company'];
            // }
            // if(isset($datas['alias'])){
            //     $data['alias']=$datas['alias'];
            // }
            // if(isset($datas['provinceId'])){
            //     $data['provinceId']=$datas['provinceId'];
            // }
            // if(isset($datas['cityId'])){
            //     $data['cityId']=$datas['cityId'];
            // }
            // if(isset($datas['address'])){
            //     $data['address']=$datas['address'];
            // }
            if(isset($datas['status'])){
                // $parameter=[
                //     'where'=>["companyId"=>$datas['companyId']],
                // ];
                // $result=$this->customerCom->getCompanyList($parameter,true);
                // $data = $this->status_update($result,$datas["status"],$data);
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
    function cust_companyAdd(){
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
    // function companyOne(){
    //     $id	=I("id");
    //     // $result=$this->id_get_company($id);
    //     $parameter=[
    //         'where'=>["companyId"=>$id],
    //     ];
    //     $result=$this->customerCom->getCompanyList($parameter,true);
    //     if(!empty($result)){
    //         $this->ajaxReturn(['errCode'=>0,'info'=>$result]);
    //     }
    //     $this->ajaxReturn(['errCode'=>110,'info'=>'无数据']);
    // }
    /** 
     * @Author: vition 
     * @Date: 2018-05-10 00:02:10 
     * @Desc: 修改客户信息 
     */    
    function cust_companyEdit(){
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
        $this->assign("cusCompanyList",$this->getCusCompany());
        $this->assign('tableName',"VCustomerContact");//删除数据的时候需要
        $this->assign("controlName","cust_contact");//名字对应cust_company_modalOne，和cust_companyModal.html
        if($reqType){
            $this->$reqType();
        }else{
            
            $this->returnHtml();
        }
    }
    function cust_contact_modalOne(){
        $title = "新建客户联系人";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        
        if($gettype=="Edit"){
            $title = "编辑客户联系人";
            $btnTitle = "保存数据";
            $redisName="cust_contactList";
            $resultData=$this->customerCom->redis_one($redisName,"contactId",$id,"ccontactDB");
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"contactModal",
        ];
        $this->modalOne($modalPara);
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
    function cust_contactList(){
        $data=I("data");
        $p=I("p")?I("p"):1;

        $where['_string']=" (process_level = ".($this->processAuth["level"]-1)." OR process_level = 0 OR author = ".session("userId")." OR FIND_IN_SET(".session("userId").",examine))";
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
        $this->tablePage($listResult,'Customer/customerTable/contactList',"cust_contactList");
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-09 23:43:33 
     * @Desc: 添加和修改信息管理 
     */    
    function manageContactInfo(){
        $reqType=I("reqType");
        $datas=I("data");
        if($reqType=="cust_contactAdd"){
            $datas['addTime']=time();
            $datas['process_level']=$this->processAuth["level"];
            $datas['author']=session("userId");
            unset($datas['contactId']);
            return $datas;
        }else if($reqType=="cust_contactEdit"){
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
                    'where'=>["contactId"=>$datas['contactId']],
                ];
                $result=$this->customerCom->getCustomerList($parameter,true);
                $data = $this->status_update($result,$datas["status"],$data);
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
    function cust_contactAdd(){
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
    // function contactOne(){
    //     $id	=I("id");
    //     $parameter=[
    //         'where'=>["contactId"=>$id],
    //     ];
    //     $result=$this->customerCom->getCustomerList($parameter,true);
    //     if(!empty($result)){
    //         $this->ajaxReturn(['errCode'=>0,'info'=>$result]);
    //     }
    //     $this->ajaxReturn(['errCode'=>110,'info'=>'无数据']);
    // }
    /** 
     * @Author: vition 
     * @Date: 2018-05-10 00:02:10 
     * @Desc: 修改客户信息 
     */    
    function cust_contactEdit(){
        $contactInfo=$this->manageContactInfo();
        $updateResult=$this->customerCom->updateContact($contactInfo);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
}
