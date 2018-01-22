<?php
namespace Component\Controller;
use Think\Controller;
use Think\Cache\Driver\Redis;
/**
 * BaseController 控件基类
 *     公共控制文件
 * 
 * @author vition
 * @date 2017-11-17
 */

class BaseController extends Controller{
    protected $ajaxReturn=true;//是否通过ajax返回 api默认通过ajax返回
    protected $_inner=false;//是否内部调用，默认非
    protected $logDebug = true;//是否开启日志调试模式
    protected $baseLogPath = 'GameApi/'.MODULE_NAME.'/'.CONTROLLER_NAME.'/';//seasLog 日志目录
    protected $openAction=['getAccessToken','intReturn']; //开放接口列表
    protected $Redis=NULL;
    protected $openAccToken=false;//是否开启令牌
    private   $ATLimit=100;//每天获取令牌的次数
    private   $ATExpire=7200;//令牌时效 2小时
    public function __construct($ajaxReturn=true){
        $this->ajaxReturn=$ajaxReturn;
        if($ajaxReturn==false){
            $this->_inner=true;
        }else{
            $this->_inner=false;
        }
        parent::__construct();
    }
    /**
     * 基础类访问控制
     *
     * @return void
     * @author vition
     * @date 2017-12-26
     */
    public function _initialize() {
        /*判断方式是否开放*/
        $this->Redis=new Redis();
        if($this->_inner==false && $this->openAccToken==false){
            if(!in_array(ACTION_NAME,$this->openAction)){
                // $this->show("<div style='text-align:center;'><h2>您无权限访问本网站</h2></div>");
                // exit();
                return $this->ajaxReturn(["errCode"=>404,'error'=>'您无权限访问本网站']);
            }
        }
        
        if($this->openAccToken==true && $this->_inner==false && ACTION_NAME!='getAccessToken'){
            $res=$this->initRes();
            $userId=I('userId');
            $accessToken=I('accessToken');
            if(!$userId){
                $res->errCode=20002;
                $res->error=getError(20002);
                $this->intReturn($res);
            }
            if(!$accessToken){
                $res->errCode=20003;
                $res->error=getError(20003);
                $this->intReturn($res);
            }
            $redisAT=$this->Redis->get('accessToken'.$userId);
            if($redisAT!=$accessToken){
                $res->errCode=20004;
                $res->error=getError(20004);
                $this->intReturn($res);
            }
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
     * 初始化参数
     *
     * @param array $parameter
     * @return void
     * @author vition
     * @date 2017-12-20
     */
    protected function iniParam($parameter=[]){
        if($this->_inner==true){
           $this-> _Log($parameter);
            return $parameter;
        }else{
            $this-> _Log($_REQUEST,0);
            return $_REQUEST;
        }
    } 
    /**
     * 返回方法
     *
     * @param [type] $res
     * @return void
     * @author vition
     * @date 2017-12-19
     */
    protected function intReturn($res){
        if($this->ajaxReturn==false){
            if($this->_inner==false){
                $this->ajaxReturn=true;
            }
            return $res;
        }else{
            $this->ajaxReturn($res);
        }
    }
    /**
     * 内部调用方法
     *
     * @param [type] $Method
     * @param array $parameter
     * @return void
     * @author vition
     * @date 2017-12-19
     */
    protected function _inner($Method,$parameter=[]){
        $this->ajaxReturn=false;
        // $this->_inner=true;
        return $this->$Method($parameter);
    }

    /**
     * Seaslog 日志记录
     * @return [type] [description]
     */
    public function _Log($content,$type='info',$resetPath=false){
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
     * 根据用户id获取AccessToken
     *
     * @return void
     * @author vition
     * @date 2017-12-29
     */
    public function getAccessToken(){
        $res=$this->initRes();
        if(!$this->openAccToken){
            $res->errCode=20000;
            $res->error=getError(20000);
            $this->intReturn($res);
        }
        if(IS_POST){
            $AccessToken=base64_encode(sha1('vition'.time().rand(1000000000,9999999999)));
            // $userId='2106438';
            $userId=$_POST['userId'];
            if(!$userId){
                $res->errCode=20002;
                $res->error=getError(20002);
                $this->intReturn($res);
            }
            $getNum=$this->Redis->get('expire'.$userId);
            if(!$getNum){
                $getNum=0;
            }elseif($getNum>$this->ATLimit){
                $res->errCode=20001;
                $res->error=getError(20001).$this->ATLimit;
                $this->intReturn($res);
            }
            $getNum++;
            $expire=strtotime(date('Y-m-d')." 23:59:59")-time()+1;
            $this->Redis->set('expire'.$userId,$getNum,$expire);
            $this->Redis->set('accessToken'.$userId,$AccessToken,$this->ATExpire);
            $res->errCode=0;
            $res->error='';
            $res->data=['accessToken'.$userId=>$AccessToken,'expire'=>time()+$this->ATExpire,'getNum'=>$getNum];
            $this->intReturn($res);
        }else{
            $res->errCode=20005;
            $res->error=getError(20005);
            $this->intReturn($res);
        }
        
    }
    private function checkUser(){

    }
    /**
     * 走丢方法
     *
     * @param [type] $fun
     * @param [type] $argu
     * @return void
     * @author vition
     * @date 2017-12-30
     */
    function __call($fun,$argu){
        $this->intReturn(["errCode"=>404,'error'=>'您访问的'.$fun.'走丢了']);
    }
}
