//设置联级的权限
var setLevelAuth = function (element){
    //判断值范围在0-7
    if(element.val()>7){
        element.val(7)
    }else if(element.val()<0){
        element.val(0)
    }
    if(element.hasClass("auth-val")){
        if(element.parents("ul").length>0){
            level2=element.parents("li").next("li").children("ul");
            if(level2.length>0){
                level2.find("input").val(element.val())
            }
            var level2=element.parents("ul").eq(0).parent().prev().find("input")
            if(level2.val()==0 && element.val()>0){
                level2.val(1)
            }     
            var nodeId=element.parents(".panel-collapse").attr("id").replace("collapse","");
            var level1=$(tabId+" .rnaccordion .auth-val[name='node"+nodeId+"']");
            if(level1.val()==0 && element.val()>0){
                level1.val(1)
            }
        }else{
            $("#collapse"+element.data("id")+" input").val(element.val())
        }
    }else if(element.hasClass("node-input")){
        var treeData = $(tabId+' #node2roletree').treeview('treeData')
        var nodeId = element.data("id");
        treeData.forEach(function(ele,key) {
            // console.log(key)
            
            if(ele['id'] == nodeId){
                treeData[key].inputVal = element.val()
                
                if(typeof ele['nodes'] !== "undefined"){
                    ele['nodes'].forEach(function(subele,subkey){
                        treeData[key]['nodes'][subkey].inputVal = element.val()
                    })
                }
                
            }
        });

        element.parent("li").nextAll().each(function(){
            if($(this).find(".indent").length == 0){
                return false;
            }else{
                $(this).find("input").val(element.val())
            }
        })
    }
    
}
$(function(){
    //权限编辑
    $(tabId).on("click",".rnaccordion .auth-val, .rnaccordion .node-input",function(){
        if($(".auth-con-btns").hasClass("none")){
            $(".auth-con-btns").removeClass("none")
            $(".auth-con-btns").mouseleave(function(){
                $(".auth-con-btns").addClass("none")
            })
        }
        authNodeId=$(this).data("id")
        var offset = $(this).offset();
        $(tabId+" .auth-con-btns").offset({ top: offset.top-22, left: offset.left-22 });
    })
    //切换焦点
    $(".auth-edit").on("click",function(){
        var element = $(tabId+" .rnaccordion .auth-val[name='node"+authNodeId+"']")
        if(element.length==0){
            $(tabId+" .rnaccordion .node-input[name='node"+authNodeId+"']")
        }
        element.focus();
    })
    //点击按钮逻辑
    $(tabId).on("click",".auth-con-btns .setauth-btn",function(){
       
        var element = $(tabId+" .rnaccordion .auth-val[name='node"+authNodeId+"']");
        if(element.length==0){
            element = $(tabId+" .rnaccordion .node-input[name='node"+authNodeId+"']");
        }
        element.val($(this).data("val"));
        element.change()
        setLevelAuth(element);
    })
    //权限输入框逻辑
    $(tabId).on("click",".rnaccordion .auth-val, .rnaccordion .node-input",function(){
        if($(this).val()!=""){
            setLevelAuth($(this));
        }
    })
})