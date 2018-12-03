<?php
Class leshui
{
    function __construct($appKey,$appSecret){

		$this->appKey=$appKey;
		$this->appSecret=$appSecret;
		$this->aTFile="accesstoken/leshuitoken.php";
		$this->getToken();
	}
    /** 
     * @Author: vition 
     * @Date: 2018-11-30 15:03:04 
     * @Desc: 通过发票代码等信息查询 
     */    
    public function codeQuery($param){
        if(!$param['token']) $param['token'] = $this->getToken();
        $url = "https://open.leshui365.com/api/invoiceInfoForCom";
        return $this->getResponse($url,$param);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-11-30 15:42:28 
     * @Desc: 根据图片base64 获取发票信息 
     */    
    function orcQuery($param){
        if(!$param['token']) $param['token'] = $this->getToken();
        $url = "https://open.leshui365.com/api/ocrVatInfoForInvoice";
        // print_r($param);exit;
        return $this->getResponse($url,$param);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-11-30 15:09:16 
     * @Desc: 获取token 
     */    
    private function getToken()
    {
        if(file_exists($this->aTFile)){
			$tokenData=json_decode(trim(substr(file_get_contents($this->aTFile), 15)));
			if ($tokenData->expire_time < time()) {
				$this->accessToken=$this->createToken();
			}else{
				$this->accessToken= $tokenData->access_token;
			}
		}else{
			if(!is_dir("accesstoken/")){
				mkdir("accesstoken/",0755,true);
            }
			$this->accessToken=$this->createToken();
        }
        return $this->accessToken;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-11-30 15:09:24 
     * @Desc: 生成token 
     */    
    private function createToken(){
        $url = 'https://open.leshui365.com/getToken?appKey='.$this->appKey.'&appSecret='.$this->appSecret;
        $token = $this->getResponse($url)['token'];
		if ($token) {
			$aTFile = fopen($this->aTFile, "w");
			fwrite($aTFile, "<?php exit();?>" . json_encode(array("expire_time"=>time() + 7000,"access_token"=>$token)));
			fclose($aTFile);
			return $token;
		}
		return false;
	}

    protected function getResponse($url, $data = array())
    {
        $ch = curl_init();
        $headers = array(
            "Content-type: application/json"
        );
        $param = json_encode($data);
        $curlPost = $param;
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL, $url);//抓取指定网页
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $datas = curl_exec($ch);//运行curl
        curl_close($ch);
        $array = json_decode($datas, true);
        return $array;
    }
}
