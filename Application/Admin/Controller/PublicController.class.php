<?php
namespace Admin\Controller;

class PublicController extends BaseController{

    public function _initialize() {
        parent::_initialize();
        $this->MesCom=getComponent('Message');
    }

    function messageControl(){
        $reqType=I('reqType');
        $this->assign('userArr',A("Project")->_getOption("create_user"));
        $this->assign('no_read',$this->MesCom->noRead());
        if($reqType){
            $this->$reqType();
        }else{         
            $this->returnHtml();
        }
    }
    function getMessageList(){
        $type = I("type");
        $p=I("p")?I("p"):1;
        $where=["to_user"=>session("userId")];
        $userKey = "from_user";
        $groupBy = "";
        
        switch ($type) {
            case 1: default:
                $where["status"]=["lt",2];
                break;
            case 2:
                unset($where["to_user"]);
                $where["from_user"] = session("userId");
                $userKey = "to_user";
                $where["status"]=["lt",2];
                $groupBy = "group_id";
                break;
            case 3:
                unset($where["to_user"]);
                $where["from_user"] = session("userId");
                $userKey = "to_user";
                $where["status"] = 2;
                break;
            case 4:
                $where["status"] = 3;
                $where["_string"] = "to_user = ".session("userId")." OR from_user = ".session("userId");
                unset($where["to_user"]);
                break;
        }
        
        $parameter=[
            "where" => $where,
            'page'=>$p,
            'fields'=>"*,FROM_UNIXTIME(add_time,'%Y-%m-%d %H:%i:%s') date_time",
            'pageSize'=>$this->pageSize,
            'orderStr'=>"add_time DESC",
            'groupBy'=>$groupBy,
            "joins"=>[
                'LEFT JOIN (SELECT userId,userName user_name FROM v_user) u ON u.userId='.$userKey,
            ],
        ];
        
        $listResult = $this->MesCom->getList($parameter);
        $this->assign("type",$type);
        // print_r($parameter);
        $this->tablePage($listResult,'Public/publicTable/messageList',"messageList",10,"",["bigSize"=>false,"returnData"=>true]);
    }
    function readMesOne(){

    }
    function messageAdd(){
        $datas = I("data");
        $this->ajaxReturn($this->MesCom->messageAdd($datas));
    }
    function statusEdit(){
        $id = I("id");
        $status = I("status");
        $result = $this->MesCom->updateState($id,$status);
        $this->ajaxReturn(['errCode'=>$result->errCode,'error'=>getError($result->errCode)]);
    }
}