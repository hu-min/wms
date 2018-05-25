<?php
namespace Component\Controller;
// use Common\Controller\BaseController;

class CustomerController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->companyDB = D('Component/CustomerCompany');
        $this->customerDB = D('Component/CustomerContact');
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
}