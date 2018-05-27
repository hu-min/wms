<?php
namespace Component\Controller;
// use Common\Controller\BaseController;

class SupplierController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->companyDB = D('Component/SupplierCompany');
        $this->contactDB = D('Component/SupplierContact');
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
    function getSupplierList($parameter=[],$one=false){
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