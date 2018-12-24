<?php
namespace Component\Controller;
// use Common\Controller\BaseController;
/** 
 * @Author: vition 
 * @Date: 2018-05-20 22:17:37 
 * @Desc: 日志组件 
 */
class LogController extends BaseController{
    protected $logType=[0=>"logout",1=>"login",2=>"read",3=>"insert",4=>"edit",5=>"del",6=>"deepdel",7=>"export",8=>"import",9=>"login",100=>'debug'];
    protected $desc=[0=>"用户 %s 于 %s 退出系统。%s %s",1=>"用户 %s 于 %s 通过电脑登录系统。%s %s",2=>"用户 %s 于 %s 访问控制器 %s ; 执行查询，请求参数 %s ",3=>" 用户 %s 于 %s 访问控制器 %s 执行新增 %s",4=>"用户 %s 于 %s 访问控制器 %s ; 执行修改数据 %s",5=>"用户 %s 于 %s 访问控制器 %s ; 执行浅删除，请求参数 %s ",6=>"用户 %s 于 %s 访问控制器 %s ; 执行深度删除，请求参数 %s",7=>"用户 %s 于 %s 访问控制器 %s ; 执行导出;请求参数 %s",8=>"用户 %s 于 %s 访问控制器 %s ; 执行导出 ; %s",9=>"用户 %s 于 %s 通过企业微信登录系统。%s %s",100=>""];
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/Log');
    }
    function log($type,$describe=""){
		$logInfo=[
			"userId"=>session("userId"),
			"userName"=>session("userName"),
			"class"=>$this->logType[$type],
			"describe"=>$describe?$describe:$this->formatDesc($type),
			"addTime"=>time(),
		];
		$lastTime = time() - 5;
		$hasInfo = [
			"userId"=>session("userId"),
			"userName"=>session("userName"),
			"class"=>$this->logType[$type],
			"describe"=>$describe?$describe:$this->formatDesc($type),
			"addTime"=> ["GT",$lastTime],
		];
		$redisName = md5(json_encode($hasInfo));

		$hasLog = $this->Redis->get($redisName);

		if(!$hasLog){
			$hasLog = $this->selfDB->getOne($hasInfo);
			$this->Redis->set($redisName,$hasLog,30);
		}
		
		if(!$hasLog){
			$this->selfDB->insert($logInfo);
		}
		
    }
    protected function formatDesc($type){
			$userName=session("userName");
			$time=date("Y-m-d H:i:s");
			$moduleCon=MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME;
			if(in_array($type,[0,1,4,8])){
				$request="";
			}elseif($type>=100){
				return json_encode($_REQUEST,JSON_UNESCAPED_UNICODE);
			}else{
				$request=json_encode($_REQUEST,JSON_UNESCAPED_UNICODE);
			}
			return sprintf($this->desc[$type],$userName,$time,$moduleCon,$request);
    }
    function getType($class){
		if(in_array(strtolower($class),["list","one"])){
			$class="read";
		}else if(in_array(strtolower($class),["add","insert"])){
			$class="insert";
		}
		foreach($this->logType as $type => $tclass){
			if($class==$tclass){
				return $type;
			}
		}
		return false;
    }
}
