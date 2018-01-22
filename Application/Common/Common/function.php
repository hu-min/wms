<?php
/**
 * 获取错误信息
 */
function getError($errCode){
    if($errCode<0){
        return 'errCode不能为空或者小于0';
    }
    $errCodeFile=APP_PATH.'Common/Common/errCode.php';
    if(!file_exists($errCodeFile)){
        return 'errCode文件不存在';
    }
    include $errCodeFile;

    if(!$errCodeList[$errCode]){
        return '未知错误,errCode文件里可能未增加';
    }
    return $errCodeList[$errCode];
}
/**
 * 获取组件
 */
function getComponent($conName,$arrParam=[]){
    $conName=ucfirst($conName).'Controller';
    $fileUrl=APP_PATH.'Component/Controller/'.$conName.'.class.php';
    if(!file_exists($fileUrl)){
        \SeasLog::setLogger('function/'.__FUNCTION__.'/'.date('Y-m-d'));
        \SeasLog::warning($conName.'对应的文件不存在');
        return new \Component\Controller\BaseController();//防止调用出错，直接返回基类
    }
    $className= '\\Component\\Controller\\'.$conName;
   return new $className();
}
