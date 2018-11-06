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

    /** 
     * @Author: vition 
     * @Date: 2018-11-06 19:46:16 
     * @Desc: 是否限制，只要用户1在白名单中，而用户2不在白名单中就限制 
     */    
    function limitWhite($uId1,$uId2,$isRole=false){

        $key = $isRole ? "role_id" : "user_id";
        $param = [
            "where"=>["status"=>1],
            "fields"=>"GROUP_CONCAT(".$key.") whites",
            "joins"=>[
                "LEFT JOIN (SELECT userId,roleId role_id FROM v_user) u ON u.userId = user_id"
            ]
        ];
        $result = $this->getOne($param);
        if($result["list"]["whites"]!=""){
            $whites = explode(",",$result["list"]["whites"]);

            if(array_search($uId1,$whites) !==false && array_search($uId2,$whites) === false){
                return true;
            }
        }
        return false;
    }
}