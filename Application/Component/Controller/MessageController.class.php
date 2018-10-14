<?php
namespace Component\Controller;
class MessageController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/Message');
    }
    function messageAdd($datas){
        $datas['add_time'] = time();
        $datas['from_user'] = session("userId");
        
        $datas['content'] = urldecode($datas['content']);
        $relationArray = $datas['to_user'];
        $allNum = count($relationArray);
        $current = 0;
        $datas['group_id'] = $datas['from_user'].$datas['add_time'];
        
        $relationRes = A("Component/User")->getOne(["where"=>["userId"=>["IN",$relationArray]],"fields"=>"GROUP_CONCAT(userName) relation_uname,GROUP_CONCAT(qiye_id) qiye_ids"]);
        if($allNum>1){
            $datas['relation_user'] = implode(",",$datas['to_user']);
            $datas['relation_uname'] = $relationRes["list"]["relation_uname"];
        }
        $touser = str_replace(",","|",$relationRes["list"]["qiye_ids"]);
        
        $msgData = [
            "touser" => $touser,
            "msgtype" => "textcard",
            "agentid" => "0",
            "textcard" => [
                        "title" => $datas['title'],
                        "description" => "<div class=\"gray\">".date("Y年m月d日",$datas['add_time'])."</div> <div class=\"highlight\">".utf8_substr($datas['content'],30)."</div>",
                        "url" => C('qiye_url')."/Admin/Index/Main.html?action=Public/messageControl",
                        "btntxt"=>"更多"
            ]
        ];
        $this->startTrans();
        foreach ($relationArray as $to_user) {
            $datas['to_user'] = $to_user;
            // print_r($datas);
            $insertResult=$this->insert($datas);
            if(isset($insertResult->errCode) && $insertResult->errCode==0){
                $current++;
            }
        }
        $this->Wxqy->secret($this->WxConf["helper"]["corpsecret"]);
        $msgResult = $this->Wxqy->message()->send($msgData);
        // print_r($msgResult );
        if($allNum > 0 && $current==$allNum && $msgResult['errcode']==0){
            $this->commit();
            return ['errCode'=>0,'error'=>getError(0)];
        }else{
            $this->rollback();
            return ['errCode'=>100,'error'=>getError(100)];
        }
        
    }
    /** 
     * @Author: vition 
     * @Date: 2018-09-06 20:07:33 
     * @Desc: 获取未读取邮件 
     */    
    function noRead($userId=NULL){
        $userId = $userId ? $userId : session("userId") ;
        return $this->M()->countList(["to_user"=>$userId,"status"=>0]);
    }
    function newMesg($userId=NULL){
        $userId = $userId ? $userId : session("userId") ;
        $param = [
            'where' => ["to_user"=>$userId,"status"=>0],
            'orderStr'=>'add_time DESC',
            'pageSize'=>5,
            'joins' => [
                "LEFT JOIN (SELECT userId,userName user_name,avatar FROM v_user) u ON u.userId = from_user",
            ],
        ];
        return $this->getList($param)['list'];
    }
    /** 
     * @Author: vition 
     * @Date: 2018-09-06 20:08:09 
     * @Desc: 修改状态 
     */    
    function updateState($id,$status){
        return $this->update(["where"=>["id"=>$id],"data"=>["status"=>$status]]);
    }
}