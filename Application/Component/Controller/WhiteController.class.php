<?php
namespace Component\Controller;
class WhiteController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/White');
    }
    function getWhites($userId=false){
        $userId = $userId ? $userId : session("userId");
        $result = $this->getOne(["where"=>["status"=>1],"fields"=>"GROUP_CONCAT(user_id) whites"]);
        if($result["list"]["whites"]!=""){
            $whites = explode(",",$result["list"]["whites"]);
            // print_r($whites);
            // print_r($userId);
            // echo array_search($userId,$whites);
            if(array_search($userId,$whites)===false){
                return $whites;
            }else{
                return false;
            }
        }
        return false;
    }
}