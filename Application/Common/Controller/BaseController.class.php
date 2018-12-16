<?php
namespace Common\Controller;
use Think\Controller;
use Think\Cache\Driver\Redis;
/**
 * 所有控制基类
 *     公共控制文件
 * 
 * @author vition
 * @date 2017-11-17
 */

class BaseController extends Controller{
    protected $logDebug=true;
    protected $Redis;
    protected $refreNode='refreNode';
    protected $baseLogPath = MODULE_NAME."/".CONTROLLER_NAME."/";//seasLog 日志目录
    protected $expire=3600;

    public function _initialize() {
        $this->Redis= new Redis();
        vendor('WeixinQy.WeixinQy');//引入WeiXin企业
        $this->redisCom=getComponent('Redis');
        $this->WxConf = $this->redisCom->rget("base_wexin_conf");
        if(!$this->WxConf){
            $this->WxConf = getWeixinConf();
            $this->redisCom->rset("base_wexin_conf",$this->WxConf,7200);
        }
        $this->Wxqy = new \WeixinQy($this->WxConf["1000009"]["corpid"],$this->WxConf["1000009"]["corpsecret"]);
    }
    /**
     * 写日志
     */
    protected function log($content,$type='info',$resetPath=false){
        $logType=[
            'debug',
            'info',
            'notice',
            'warning',
            'error',
            'critical',
            'alert',
            'emergency',
        ];
        $typeFun=is_string($type) && in_array($type,$logType)?$type:$logType[$type];
        if(!$typeFun){
            $typeFun='warning';
        }
        if($this->logDebug){
           if(!$resetPath){
               $logPath = $this->baseLogPath.date('Y-m-d');
               \SeasLog::setLogger($logPath);
           }else{
            \SeasLog::setLogger($resetPath);
           }
           if(is_array($content)) $content = json_encode($content);
           \SeasLog::$typeFun($content); 
        }
    }
    /**
     * 统一返回json格式
     * 
     */
    protected function initRes($isObj=false){
        $res = new \stdClass();
        $res->status = 1;
        $res->errCode = 110;
        $res->error = '未知错误';
        $res->data = $isObj ? new \stdClass() : [];
        return $res;
    }
    /**
     * 无方法返回
     */
    function __call($fun,$argu){
        return $fun.'方法不存在';
    }
    function clearRedis($redisName){
        $this->Redis->set($redisName,null,1);
    }
}