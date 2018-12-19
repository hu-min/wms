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
            'one' => true,
            "joins"=>[
                "LEFT JOIN (SELECT userId,roleId role_id FROM v_user) u ON u.userId = user_id"
            ]
        ];
        $result = $this->getOne($param);
        if($result["whites"]!=""){
            $whites = explode(",",$result["whites"]);

            if(array_search($uId1,$whites) !==false && array_search($uId2,$whites) === false){
                return true;
            }
        }
        return false;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-12-18 10:03:38 
     * @Desc: 过滤掉非白名单 $this->whiteCom->whiteFilter(45,[4,5,9,2],true);
     */    
    function whiteFilter($uId,$uIdSource,$isRole=false){
        $uIdSource = is_string($uIdSource) && strstr($uIdSource,',') ? explode(',',$uIdSource) : $uIdSource;
        $key = $isRole ? "role_id" : "user_id";
        $param = [
            "where"=>["status"=>1],
            "fields"=>"GROUP_CONCAT(".$key.") whites",
            'one' => true,
            "joins"=>[
                "LEFT JOIN (SELECT userId,roleId role_id FROM v_user) u ON u.userId = user_id"
            ]
        ];
        $result = $this->getOne($param); //获取白名单的数据
        if($result['whites']){
            $whites = explode(",",$result["whites"]);
            if(in_array($uId,$whites)){
                if(is_array($uIdSource)){
                    foreach ($uIdSource as $key => $urid) {
                        if(!in_array($urid,$whites)){
                            unset($uIdSource[$key]);
                        }
                    }
                }elseif(!in_array($uIdSource,$whites)){
                    return '';
                }
            }
        }
        return $uIdSource;
    }
}