<?php
namespace Admin\Controller;

/** 
 * @Author: vition 
 * @Date: 2018-05-06 09:45:33 
 * @Desc: 专门提供给wap 
 */
class WapController extends BaseController{

    public function _initialize() {
        parent::_initialize();
    }

    function modal(){
        $place = 4;
        $array1 = [53,5,45,50,2,14];
        $array2 = [53,5,50,2,14];
        echo array_search(14,$array1),"\n";
        unset($array1[2]);
        echo "\n";
     
        if($place<=count($array1)){
            if(count($array1) > 0 && count($array1) > count($array2)){
                $array3 = array_diff($array1,$array2);
                print_r($array3);
                for ($place ; $place <= count($array1) ; $place++) { 
                    if(!in_array($array1[$place],$array3)){
                        echo $place+1;
                        break;
                    }
                }
            }else{
                echo ++$place;
            }
        }
        
        
    }
}