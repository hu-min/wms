<?php
namespace Component\Controller;

class QiyeController extends \Common\Controller\BaseController{

    function textcard($touser,$title,$desc,$url,$agentid="0",$secret=false){
        $secret = $secret ? $secret : $this->WxConf["helper"]["corpsecret"];
        $msgData = [
            "touser" => $touser,
            "msgtype" => "textcard",
            "agentid" => $agentid,
            "textcard" => [
                "title" => $title,
                "description" => $desc,
                "url" => $url,
                "btntxt"=>"更多"
            ]
        ];
        $this->Wxqy->secret($secret);
        $return = $this->Wxqy->message()->send($msgData);
        $this->log($return);
        return $return;
    }
}