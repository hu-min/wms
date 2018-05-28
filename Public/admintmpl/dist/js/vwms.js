/** 
 * @Author: vition 
 * @Date: 2018-01-23 00:41:37 
 * @Desc: get方式获取数据 
 */    
function get(url,datas,callBack){
    asyncs=arguments[3]!=undefined?arguments[3]:true;
    $.ajax({
        url:url,
        type:'get',
        dataType:'json',
        data:datas,
        async:asyncs,
        success:callBack,
    })
/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-01-27 22:43:43 
 * @Desc: post发送 
 */}
function post(url,datas,callBack){
    asyncs=arguments[3]!=undefined?arguments[3]:true;
    $.ajax({
        url:url,
        type:'post',
        dataType:'json',
        data:datas,
        async:asyncs,
        success:callBack,
    })
}
//enter-input class的输入框键盘回车事件
$(document).on("keypress",".enter-input",function(e){
    if(e.keyCode == 13){
        $($(this).data("btn")).click();
    }
})
var datas={};
var filesData={};
/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-01-27 18:19:11 
 * @Desc: 所有搜索按钮触发事件 
 */
$(document).on("click",".search-list,.vpage",function(){
    datas={}
    var page=$(this).data('page')
    if(page>0){
        datas['p']=page;
        var url=$(this).parents('.page-div').data("url");
        var con=$(this).parents('.page-div').data("con");
        var reqtype=$(this).parents('.page-div').data("reqtype");
    }else{
        var url=$(this).data("url");
        var con=$(this).data("con");
        var reqtype=$(this).data("reqtype");
    }
    var table=con+"Table";
    var page=con+"Page";
    var count=con+"Count";
    datas.reqType=reqtype;
    if(fun_is_exits(con+"SearchFuns")){
        eval(con+"SearchFuns()");//对不同的id设置不同的发送数据
    }
    searchFun(url,datas,table,page,count);
    
})

function searchFun(url,datas,table,page,count){
    get(url,datas,function(result){
        if(result.errCode==0){
            // $(tabId+" ."+table).html(result.table);
            // $(tabId+" ."+page).html(result.page);
            // $(tabId+" ."+count).html(result.count);

            $("#"+table).html(result.table);
            $("#"+page).html(result.page);
            $("#"+count).html(result.count);
        }else{
            alert(result.error);
        }
    })
}
/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-01-27 18:19:36 
 * @Desc: 所有编辑，添加，触发弹出modal事件 
 */
$(document).on("click",".info-edit",function(){

    var target=$(this).data("target");
    var title=$(this).data("title");
    var reqtype=$(this).data("reqtype");
    var show=$(this).data("show");
    var con=$(this).data("con");
    var url=$(this).data("url");
    var name=$(this).attr("name");
    if(name){
        $(target).find(".box-body").html("");
        datas={}
        datas.reqType="formOne";
        
        datas.form=name;
        get(url,datas,function(result){
            if(result.errCode==0){
                $(target).find(".box-body").html(result.html);
            }
            
        })
    }
    $(target).find('.modal-title').text(title)
    $(target).find('.save-info').text(title)
    $(target).find('.save-info').data("reqtype",reqtype)
    if(show=='One'){//编辑要获取数据
        datas={}
        var id=$(this).data("id");
        datas.reqType=con+show;
        datas.id=id
        get(url,datas,function(result){
            if(result.errCode==0){
                if(fun_is_exits(con+"ShowFuns")){
                    eval(con+"ShowFuns(result.info)");//对不同的模块设置不同的响应数据
                }
            }else{
                alert(result.error);
            }
        })
    }else{//新建要重置数据
        $(target).find(".modal-info").val("");
        if(fun_is_exits(con+"ReFuns")){i
            eval(con+"ReFuns()");//对不同的模块的modal数据重置
        }
    }
})
/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-01-27 18:19:54 
 * @Desc: 所有状态选择按钮事件 
 */
$(document).on("click",'.status-btn',function(){
    $(this).parents(".status-group").children(".status-btn").removeClass("active");
    $(this).addClass("active");
    var val= $(this).attr("name");
    $(this).parent(".status-group").children("input[name='status']").val(val);
})
/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-01-27 22:09:37 
 * @Desc: 所有重置按钮 
 */
$(document).on("click",'.search-refresh',function(){
    $(this).parents(".search-body").find(".search-info").val("");
    var con=$(this).data("con")
    if(fun_is_exits(con+"ResetFuns")){
	    eval(con+"ResetFuns()");//弥补不足
    }
})
/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-01-27 22:38:31 
 * @Desc: 保存数据、新增或修改 
 */
$(document).on("click",'.save-info',function(){
    datas={}
    var url=$(this).data("url");
    var reqtype=$(this).data("reqtype");
    var con=$(this).data("con");
    var isModal=$(this).data("modal");
    var search=con+"-search";
    var parent=$(this).parents(".modal").attr("id")
    if($('body').hasClass('modal-open')==false){
        $('body').addClass('modal-open')
    }
    datas.reqType=con+reqtype;
    if(fun_is_exits(con+"InfoFuns")){
    	eval(con+"InfoFuns()");//对不同的id设置不同的发送数据
    } 
    if(JSON.stringify(filesData)!="{}"){
        datas['filesData']=filesData
    }
    if(JSON.stringify(datas["data"])=="{}"){
        alert("没有更新数据");
        throw "没有更新数据";
    }
    // console.log(datas);
    post(url,datas,function(result){
        if(result.errCode==0){
            datas={}
            var url=$("#"+search).data("url");
            var con2=$("#"+search).data("con");
            if(con2==undefined){
                con2=con;
            }
            // console.log(search);
            var reqtype=$("#"+search).data("reqtype");
            var table=con2+"Table";
            var page=con2+"Page";
            datas.reqType=reqtype;
            if(fun_is_exits(con+"SearchFuns")){
                eval(con2+"SearchFuns()");//对不同的id设置不同的发送数据
            }
            if(isModal){
                // console.log(isModal);
                searchFun(url,datas,table,page)
            }
            if($('body').hasClass('modal-open')){
                $(tabId+" #"+parent).modal('toggle')
            }
        }else{
            alert(result.error)
        }
        
    });
})
/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-01-28 22:28:38 
 * @Desc: 上传文件到files 
 */
$(document).on("change",".fileupdate",function(){
    var input=$(this).data("input");
    var imgName=this.files[0].name
    var slefs=$(this)
    var reader = new FileReader();
    reader.readAsDataURL(this.files[0])
    reader.onloadend = function () {  
        $(input).val(imgName);
        filesData[encodeURI(imgName)]=this.result
    };
})
$(document).on("click",'.tree-plus',function(){
    var treeId=$(this).data("id")
    $(treeId).treeview('collapseAll', { silent: true });
})
$(document).on("click",'.tree-minus',function(){
    var treeId=$(this).data("id")
    $(treeId).treeview('expandAll', { silent: true });
})
$(function(){
    /** 
     * javascript comment 
     * @Author: vition 
     * @Date: 2018-03-04 13:42:18 
     * @Desc:  url 中参数响应自动展开level 例如参数 ?action=Article/articleControl
     * action 活动的key
     * Article/articleControl 表示Article控制器的articleControl方法
     */    
    paramMatch=getUrlAction()
    // if(search!="" && search.search(/\?action\=\S/)>=0){
        // var paramMatch=search.match(/\=([\S\/]*)/);
        if(paramMatch){
            var splitArr=paramMatch.split("/");
            var match= window.document.body.innerHTML.match(new RegExp("\/Admin\/"+splitArr[0]+"\/"+splitArr[1]+"[\.a-zA-Z]*","gim"))
            if(match[0]){
                $(document).find(".nodeOn").each(function(){
                    if($(this).attr("href")==match[0]){
                        var result=$(this).parents(".treeview-menu").css("display","block");
                        var nodeOn=$(this);
                        setTimeout(function(){nodeOn.click();},0);
                        return false
                    }
                })
            }
        }
        
    // }
})
/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-03-04 15:22:16 
 * @Desc: 获取浏览器action的值 
 */
function getUrlAction(){
    var clSearch=window.location.search
    if(clSearch!="" && clSearch.search(/\?action\=/)>=0){
        return clSearch.match(/\=([\S\/]*)/)[1];
    }
    return false;
}
/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-03-04 15:26:33 
 * @Desc: 设置浏览器的url 不刷新 
 */
function setUrlAction(title,newUrl){
    var stateObject = {};
    history.pushState(stateObject,title,"?action="+newUrl);
}
/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-03-04 15:40:28 
 * @Desc: 通过nodeId更改url action 
 */
function chUrlAction(nodelId){
    $(document).find(".nodeOn").each(function(elem){
        if(parseInt(nodelId)==parseInt($(this).data("nodeid"))){
            var tController=$(this).attr("href")
            var tMatch=tController.match(/\/Admin\/([\S\/]*)\./)
            urlParam=getUrlAction()
            if(tMatch!=null && tMatch[1]!=urlParam){
                setUrlAction(tMatch[1],tMatch[1])
            }
            return false;
        }
    })
}
var dialogMove=false
$(document).on("click",".full-screen",function(e){
    var modalDialog=$(this).parents(".modal-dialog")
    if(modalDialog.hasClass("modal-full")){
        modalDialog.removeClass("modal-full");
        $(this).children("i").addClass("fa-arrows-alt")
        $(this).children("i").removeClass("fa-compress")
    }else{
        modalDialog.addClass("modal-full");
        $(this).children("i").removeClass("fa-arrows-alt")
        $(this).children("i").addClass("fa-compress")
    }
    // $(this).parents(".modal-dialog").css("width","100%");
    // console.log($(this).parents(".modal-dialog").css("width"));
})
//递归获取select option
function getArtClsNode(element,level){
    var option=""
    var strs="";
    for (let index = 0; index < level; index++) {
        strs+="——";
    }
    if(typeof(element.nodes)=='object'){
        level++
        element.nodes.forEach(elementSub => {
            option+=getArtClsNode(elementSub,level);
        });
    }
    return '<option value="'+element.id+'">'+strs+element.text+'</option>'+option;
}
function clearTags(html){
    return $('<p>'+html+'</p>').text()
}
//格式化月份
function formatMonth(month){
    month++;
    if(month<10){
        return "0"+month;
    }
    return month;
}
//格式化日期
function formatDate(date){
    if(date<10){
        return "0"+date;
    }
    return date;
}
//判断函数是否存在
function fun_is_exits(funcName){
    try {
        if (typeof(eval(funcName)) == "function") {
 	   return true;
        }
   } catch(e) {}
   return false;
}
