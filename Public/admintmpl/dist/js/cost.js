//给主分类加字母
var cost_class_title= function (){
    $(tabId+" .global-modal").find(".cost-class-alpha").each(function(index,ele){
         $(ele).text(alpha(index));
    })
}