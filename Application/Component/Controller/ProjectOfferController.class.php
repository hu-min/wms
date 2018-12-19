<?php
namespace Component\Controller;
class ProjectOfferController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/ProjectOffer');
    }
    /** 
     * @Author: vition 
     * @Date: 2018-12-17 17:44:37 
     * @Desc: 根据报价id生成 成本数据
     */    
    function toCost($id,$vtabId){
        $param = [
            'where'=>['id'=>$id],
            'one'=>true,
            'joins' => [
                "LEFT JOIN (SELECT projectId,leader,cost_user FROM v_project ) p ON p.projectId = project_id",
            ],
        ];
        $offerResult = $this->getOne($param);
        $datas = [];
        foreach (['project_id','section','flag'] as $key) {
            $datas[$key] = $offerResult[$key];
        }
        $projectCost = A('Component/ProjectCost');

        $hasCost = $projectCost -> getOne(['where'=>$datas,'one'=>true]);
        if(!$hasCost){
            $examines = A('Component/Process')->getExamine($vtabId,$offerResult['leader']);
            $datas['process_id'] = $examines["process_id"];
            $datas['examine'] = $examines["examine"];
            $datas['process_level'] = 0;
            $datas['status'] = 0;
            $datas['add_time'] = time();
            $datas['user_id'] = $offerResult['cost_user'] > 0 ? $offerResult['cost_user'] : $offerResult['user_id'];
            return $projectCost->insert($datas);
        }
        return false;
    }
}