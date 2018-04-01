<?php
namespace Home\Controller;

class IndexController extends BaseController {
    public function Index(){
        $this->display("Index/Index");
    }
    public function sendMessage(){
    	$contact=db("contact");
    	$contactData=input('post.data/a');
    	$contactData["contact_ip"]=request()->ip();
    	$contactData["contact_time"]=date("Y-m-d H:i:s",time());
    	if($contact->where(array("contact_ip"=>array("eq",$contactData["contact_ip"]),"contact_time"=>array("EGT",date("Y-m-d H:i:s",strtotime("-1 hours")))))->find()!=false){
			echo "同一个IP一小时内只能发送一条信息";
			return;
    	}
    	$result=$contact->insert($contactData);

    	if($result>0){
    		echo "感谢您的支持";
    	}else{
    		echo "很遗憾，服务器有点有问题！提交失败";
    	}

    }
}
