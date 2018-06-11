<?php
namespace Admin\Controller;

/** 
 * @Author: vition 
 * @Date: 2018-03-03 16:20:00 
 * @Desc: 文章管理 
 */
class ArticleController extends BaseController{
    protected $pageSize=15;

    public function _initialize() {
        $this->statusType = [0=>"未启用",1=>"启用",3=>"无效",4=>"删除"];
        $this->processType=[0=>"未启用",1=>"启用",4=>"删除"];
        parent::_initialize();
        $this->articleCom=getComponent('Article');
        $this->classCom=getComponent('ArticleClass');
        Vendor("levelTree.levelTree");
        $this->levelTree=new \levelTree();
    }
    /** 
     * @Author: vition 
     * @Date: 2018-03-03 16:21:16 
     * @Desc: 文章控制 
     */    
    function articleControl(){
        $reqType=I('reqType');
        $option='<option value="0">根Root</option>';
        foreach ($this->getArtClsTree() as $key => $value) {
            $option.=$this->getArtCls($value,0);
        }
        $this->assign("classOption",$option);
        $this->assign('dbName',"Article");//删除数据的时候需要
        $this->assign("controlName","article");//名字对应cust_company_modalOne，和cust_companyModal.html

        $reqType=I('reqType');
        if($reqType){
            $this->$reqType();
        }else{
            
            $this->returnHtml();
        }
    }
    function article_modalOne(){
        $title = "新建文章";
        $btnTitle = "添加";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        
        if($gettype=="Edit"){
            $title = "编辑文章";
            $btnTitle = "保存数据";
            $redisName="articleList";
            $resultData=$this->articleCom->redis_one($redisName,"articleId",$id);
            $resultData['content'] = isset($resultData['content']) ? htmlspecialchars_decode($resultData['content']) :'';
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "templet"=>"articleModal",
        ];
        $option='<option value="0">根Root</option>';
        foreach ($this->getArtClsTree() as $key => $value) {
            $option.=$this->getArtCls($value,0);
        }
        $this->assign("classOption",$option);
        $this->modalOne($modalPara);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-03-31 20:52:01 
     * @Desc: 文章分类控制 
     */    
    function classControl(){
        $reqType=I('reqType');
        $this->assign("controlName","artclass");
        // $fee_t_main=$this->classCom->get_class_data("FTMClass");//文章分类主类
        // $main_array=array_combine(array_column($fee_t_main,"basicId"),array_column($fee_t_main,"name"));
        // $this->assign("fee_main",$fee_t_main);
        // $this->assign("main_array",$main_array);
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function artclass_modalOne(){
        $title = "新建文章分类";
        $btnTitle = "添加分类";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        
        if($gettype=="Edit"){
            $title = "编辑文章分类";
            $btnTitle = "保存数据";
            $redisName="artclassList";
            $resultData=$this->classCom->redis_one($redisName,"classId",$id);
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "templet"=>"artclassModal",
        ];
        $option='<option value="0">根Root</option>';
        foreach ($this->getArtClsTree() as $key => $value) {
            $option.=$this->getArtCls($value,0);
        }
        $this->assign("classOption",$option);
        $this->modalOne($modalPara);
    }
    protected function getArtCls($element,$level){
        $option="";
        $strs="";
        for ($i=0; $i < $level; $i++) { 
            $strs.="——";
        }
        if(is_array($element["nodes"])){
            $level++;
            foreach ($element["nodes"] as $key => $value) {
                $option.= $this->getArtCls($value,$level);
            }
        }
        return '<option value="'.$element["id"].'">'.$strs.$element["text"].'</option>'.$option;
    }
    function getArtClsTree(){
        $parameter=[
            'page'=>0,
            'pageSize'=>9999,
            'orderStr'=>'level DESC,classId ASC',
        ];
        $result=$this->classCom->getArticleClassList($parameter);
        $nodeTree=[];
        $level=[];
        
        $nodeArray=$result["list"];
        foreach ($nodeArray AS $key => $nodeInfo) {
            $level[$nodeInfo["level"]][$nodeInfo["classPid"]][]= $nodeInfo;
            unset($nodeArray[$key]);
        }
        $this->Redis->set("artClsArray",json_encode($result["list"]),3600);
        asort($level);
        
        $this->levelTree->setKeys(["idName"=>"classId","pidName"=>"classPid"]);
        $this->levelTree->setReplace(["className"=>"text","classId"=>"id"]);
        $this->levelTree->switchOption(["beNode"=>false,"idAsKey"=>false]);
        $nodeTree=$this->levelTree->createTree($result["list"]);
        return $nodeTree;
    }

    /** 
     * @Author: vition 
     * @Date: 2018-03-31 23:01:55 
     * @Desc: 获取文章分类数据 
     */    
    function artclassList(){
        $this->ajaxReturn(["tree"=>$this->getArtClsTree()]);
    }
    function manageArticleInfo(){
        $reqType=I("reqType");
        $datas=I("data");
        $filesData=I("filesData");
        
        if($filesData[urlencode($datas['cover'])]){
            $datas['cover']=base64Img($filesData[urlencode($datas['cover'])])["url2"];
        }
        if($reqType=="articleAdd"){
            $datas['author']=session("loginName");
            $datas['addTime']=time();
            unset($datas['articleId']);
            return $datas;
        }else if($reqType=="articleEdit"){
            $where=["articleId"=>$datas['articleId']];
            $data=[];

            if(isset($datas['title'])){
                $data['title']=$datas['title'];
            }
            if(isset($datas['cover'])){
                $data['cover']=$datas['cover'];
            }
            if(isset($datas['tags'])){
                $data['tags']=$datas['tags'];
            }
            $data['updateTime']=time();
            if(isset($datas['class'])){
                $data['class']=$datas['class'];
            }
            if(isset($datas['content'])){
                $data['content']=$datas['content'];
            }
            if(isset($datas['status'])){
                $data['status']=$datas['status'];
            }
            return ["where"=>$where,"data"=>$data];
        }
        return "";
    }
    function articleList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $where=[];
        if($data['title']){
            $where['title']=['LIKE','%'.$data['title'].'%'];
        }
        if($data['author']){
            $where['author']=['LIKE','%'.$data['author'].'%'];
        }
        if($data['class']){
            $where['class']=$data['class'];
        }
        if($data['userType']){
            $where['userType']=$data['userType'];
        }
        if(isset($data['status'])){
            $where['status']=$data['status'];
        }
        $parameter=[
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$this->pageSize,
            'orderStr'=>"articleId DESC"
        ];
        
        $listResult=$this->articleCom->getArticleList($parameter);
        $this->tablePage($listResult,'Article/articleTable/articleList',"articleList");
        // if($articleResult){
        //     $articleRed="articleList_".session("userId");
        //     $this->Redis->set($articleRed,json_encode($articleResult['list']),3600);
        //     $page = new \Think\VPage($articleResult['count'], $this->pageSize);
        //     $pageShow = $page->show();
            
        //     $this->assign('articleList',$articleResult['list']);
        //     $this->assign('artClsList',$this->getArtClsList());
        //     $this->ajaxReturn(['errCode'=>0,'table'=>$this->fetch('Article/articleTable/articleList'),'page'=>$pageShow]);
        // }
        // $this->ajaxReturn(['errCode'=>0,'table'=>'无数据','page'=>'']);
    }
    // function articleOne(){
    //     $id	=I("id");
    //     $parameter=[
    //         'articleId'=>$id,
    //     ];
    //     $articleRed="articleList_".session("userId");
    //     $articleList=$this->Redis->get($articleRed);
    //     if($articleList){
    //         foreach ($articleList as $article) {
    //            if($article['articleId']==$id){
    //             $article['content'] = htmlspecialchars_decode($article['content']);
    //             $this->ajaxReturn(['errCode'=>0,'info'=>$article]);
    //            }
    //         }
    //     }
    //     $articleResult=$this->articleCom->getArticleOne($parameter);
    //     if($articleResult->errCode==0){
    //         $htmlResult->data['content']=htmlspecialchars_decode($articleResult->data['content']);
    //         $this->ajaxReturn(['errCode'=>0,'info'=>$articleResult->data]);
    //     }
    //     $this->ajaxReturn(['errCode'=>110,'info'=>'无数据']);
    // }
    function articleAdd(){
        $articleInfo=$this->manageArticleInfo();
        if($articleInfo){
            $insertResult=$this->articleCom->insertArticle($articleInfo);
            if($insertResult && $insertResult->errCode==0){
                $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
            }
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    }
    function articleEdit(){
        $nodeInfo=$this->manageArticleInfo();
        $updateResult=$this->articleCom->updateArticle($nodeInfo);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
    
    function getArtClsList(){
        $artClsRed="artClsArray";
        $artClsArray=$this->Redis->get($artClsRed);
        if(!$artClsArray){
            $parameter=[
                'page'=>0,
                'pageSize'=>9999,
                'orderStr'=>'level DESC,classId ASC',
            ];
            $artClsResult=$this->classCom->getArticleClassList($parameter);
            $this->Redis->set("artClsArray",json_encode($artClsResult["list"]),3600);
            $artClsArray=$artClsResult["list"];
        }
        $artClsList=[];
        foreach ($artClsArray as $classInfo) {
            $artClsList[$classInfo["classId"]]=$classInfo["className"];
        }
        return $artClsList;
    }
    // function artClsOne(){
    //     $classId=I("classId");
    //     $nodeInfo=$this->getArtClsOne($classId);
    //     if(!empty($nodeInfo)){
    //         $this->ajaxReturn(['errCode'=>0,'info'=>$nodeInfo]);
    //     }
    //     $this->ajaxReturn(['errCode'=>110,'info'=>'无数据']);
    // }
    function getArtClsOne($classId){
        $parameter=[
            'classId'=>$classId,
        ];
        $artClsRed="artClsArray";
        $nodeList=$this->Redis->get($artClsRed);
        if($nodeList){
            foreach ($nodeList as $node) {
                if($node['classId']==$classId){
                    return $node;
                }
            }
        }
        $nodeResult=$this->classCom->getArticleClassOne($parameter);
        if($nodeResult->errCode==0){
            return $nodeResult->data['list'];
        }
        return [];
    }
    function manageClassInfo(){
        $reqType=I("reqType");
        $datas=I("data");
        $nodePInfo=$this->getArtClsOne($datas['classPid']);
        $datas['level']=$nodePInfo['level']+1;
        if($reqType=="artclassAdd"){
            unset($datas['classId']);
            return $datas;
        }else if($reqType=="artclassEdit"){
            $where=["classId"=>$datas['classId']];
            $data=[];

            if(isset($datas['className'])){
                $data['className']=$datas['className'];
            }
            if(isset($datas['alias'])){
                $data['alias']=$datas['alias'];
            }
            if(isset($datas['classPid'])){
                $data['classPid']=$datas['classPid'];
            }
            if(isset($datas['remark'])){
                $data['remark']=$datas['remark'];
            }
            $data['level']=$datas['level'];
            if(isset($datas['status'])){
                $data['status']=$datas['status'];
            }
            return ["where"=>$where,"data"=>$data];
        }
    }
    /** 
     * @Author: vition 
     * @Date: 2018-03-31 22:10:48 
     * @Desc: 添加文章分类 
     */    
    function artclassAdd(){
        $info=$this->manageClassInfo();
        $result=$this->classCom->insertArticleClass($info);
        if($result->errCode==0){
            $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    }
    function artclassEdit(){
        $info=$this->manageClassInfo();
        $updateResult=$this->classCom->updateArticleClass($info);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
}