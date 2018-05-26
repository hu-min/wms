<?php
namespace Component\Controller;
// use Common\Controller\BaseController;

class CustomerController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->companyDB = D('Component/CustomerCompany');
        $this->contactDB = D('Component/CustomerContact');
    }

    function getCompanyList($parameter=[],$one=false){
        $this->selfDB=$this->companyDB;
        if($one){
            return $this->getOne($parameter);
        }
        return $this->getList($parameter);
    }
    function insertCompany($parameter){
        $this->selfDB=$this->companyDB;
        return $this->insert($parameter);
    }
    function updateCompany($parameter){
        $this->selfDB=$this->companyDB;
        return $this->update($parameter);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-26 06:56:01 
     * @Desc: 根据关键字查找指定公司列表了 
     */    
    function find_company($key=""){
        $this->selfDB=$this->companyDB;
        $where=["status"=>1];
        if($key!=""){
            $where["company"]=["LIKE","%{$key}%"];
        }
        $parameter=[
            'where'=>$where,
            'fields'=>"`companyId`,`company`",
            'page'=>1,
            'pageSize'=>20,
            'orderStr'=>"companyId DESC",
        ];
        return $this->getList($parameter); 
    }
    function getCustomerList($parameter=[],$one=false){
        $this->selfDB=$this->contactDB;
        if($one){
            return $this->getOne($parameter);
        }
        return $this->getList($parameter);
    }
    function insertContact($parameter){
        $this->selfDB=$this->contactDB;
        return $this->insert($parameter);
    }
    function updateContact($parameter){
        $this->selfDB=$this->contactDB;
        return $this->update($parameter);
    }
}