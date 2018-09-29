<?php
namespace Admin\Controller;

class PublicController extends BaseController{

    public function _initialize() {
        parent::_initialize();
        $this->MesCom=getComponent('Message');
        $this->workOrderCom=getComponent('WorkOrder');
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
        $type = I("type") ? I("type") : I("param")['type'];
        // print_r($type);exit;
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
            'orderStr'=>"`status` ASC,add_time DESC",
            'groupBy'=>$groupBy,
            "joins"=>[
                'LEFT JOIN (SELECT userId,userName user_name FROM v_user) u ON u.userId='.$userKey,
            ],
        ];
        
        $listResult = $this->MesCom->getList($parameter);
        $this->assign("type",$type);
        // print_r($parameter);
        $this->tablePage($listResult,'Public/publicTable/messageList',"lastLoginList",10,"",["bigSize"=>false,"returnData"=>true]);
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
        $newMesg = $this->MesCom->newMesg();
        $html = "";
        if($newMesg[0]){
            $html .='<li><a class="nodeOn" data-nodeid="10001" href="'.U("Public/messageControl").'" data-title="内部消息"><div class="pull-left">';
            if($newMesg[0]['avatar'] !=""){
                $html .='<img src="/'.$newMesg[0]["avatar"].'" class="img-circle" alt="用户头像">';
            }else{
                $html .='<img src="'.__ROOT__.'/Public'.'/admintmpl'.'/dist/img/minlogo.png" class="img-circle" alt="用户头像">';
            }
            $html .='</div><h4>'.utf8_substr($newMesg[0]['title'],8).'<small><i class="fa fa-clock-o"></i> '.disTime($newMesg[0]['add_time']).' </small></h4><p>'.utf8_substr($newMesg[0]['content'],16).'</p></a></li>';
           $data = $newMesg[0];
        }
        $this->ajaxReturn(['errCode'=>$result->errCode,'error'=>getError($result->errCode),'data'=>$html]);
    }
    function userProfile(){
        $reqType=I('reqType');
        if($reqType){
            $this->$reqType();
        }else{         
            $this->returnHtml();
        }
    }

    function seniorOne(){
        $datas = I('data');
        $senior = I('senior');
        // $datas['birthday'] = strtotime($datas['birthday']);
        // echo $senior;
        $param = [
            'where' => ['seniorPassword'=>sha1(sha1($senior)),'userId'=>session('userId')],
            'fields' => 'userId',
        ];
        $userRes = $this->userCom->getOne($param);
        if($userRes){
            $updateData=[];
            if(isset($datas['avatar'])){
                $updateData['avatar'] =  $datas['avatar'];
            }
            foreach (['seniorPassword','password'] as $key) {
                if(isset($datas[$key] )){
                    $updateData[$key] =  sha1(sha1($datas[$key]));
                }
            }
            $updateRes = $this->userCom->update(["where"=>['userId'=>session('userId')],"data"=>$updateData]);

            if(isset($updateRes->errCode) && $updateRes->errCode == 0){
                $parArray=[
                    'where'=>['userId'=>session('userId')],
                    'fields'=>'*',
                    'joins'=>[
                        'LEFT JOIN (SELECT roleId role_id ,rolePid,roleName FROM v_role) r ON r.role_id = roleId',
                        'LEFT JOIN (SELECT roleId role_pid ,roleName rolePName FROM v_role) rp ON rp.role_pid = r.rolePid',
                    ],
                ];
                $userInfo = $this->userCom->getOne($parArray)['list'];
                $this->setLogin($userInfo);
                $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
            }
            $this->ajaxReturn(['errCode'=>$updateRes->errCode,'error'=>getError($updateRes->errCode)]);
        }
        $this->ajaxReturn(['errCode'=>10006,'error'=>getError(10006)]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-09-28 10:28:33 
     * @Desc: 工单 
     */    
    function workOrder(){
        $reqType=I('reqType');
        $this->assign("controlName","work_order");
        $this->assign("orderType",["1"=>"个人信息","2"=>"项目相关","3"=>"其他"]);
        $this->assign("tableName",$this->workOrderCom->tableName());
        if($reqType){
            $this->$reqType();
        }else{         
            $this->returnHtml();
        }
    }
    function work_order_modalOne(){
        $title = "新建工单";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"workOrderModal",
        ];
        $this->modalOne($modalPara);
    }
}