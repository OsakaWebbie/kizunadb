(function($){$.fn.wiseSelect=function(rel,options)
{settings=jQuery.extend({classPrefix:'wise_select_',width:'150px',all:'All'},options);$("select[rel="+rel+"]").each(function(){$(this).attr("style","display:none");$(this).wiseSelectCreate(settings);});$(document).click(function(e)
{e=e||event
var target=e.target||e.srcElement
$(this).wiseSelectCheckOutsideClick(target,settings);});};$.fn.wiseSelectCheckOutsideClick=function(target,options)
{$('div.'+options.classPrefix+'main_holder').each(function()
{if(this!=target)
{if(!$(this).wiseSelectCheckOutsideClickSubNodes(target,this))
{$('div.'+options.classPrefix+'list',$(this)).each(function(){if($(this).css('display')!='none')
{$(this).slideToggle('fast');}});}}});}
$.fn.wiseSelectCheckOutsideClickSubNodes=function(target,list)
{if(list.hasChildNodes()){var nodes=list.childNodes;for(var i=0;i<nodes.length;i++){if(nodes[i]==target)
return true;if(nodes[i].hasChildNodes()){if($(this).wiseSelectCheckOutsideClickSubNodes(target,nodes[i]))
return true;}}}
else{if(list==target)
return true;}
return false;}
$.fn.wiseSelectCreate=function(options)
{var _mainHolder=document.createElement('div');$(_mainHolder).addClass(options.classPrefix+"main_holder");$(this).parent().append(_mainHolder);var _holder=$(this).wiseSelectGetHolder(options);$(_mainHolder).append(_holder);var _list=$(this).wiseSelectGetList(options,_holder);$(_mainHolder).append(_list);}
$.fn.wiseSelectGetHolder=function(options,_mainHolder)
{var _holder=document.createElement('div');_holder.setAttribute('id',$(this).attr('id')+'_wise_select_holder');_holder.setAttribute('class',options.classPrefix+'holder');return _holder;}
$.fn.wiseSelectGetList=function(options,_mainHolder)
{var _holder=document.createElement('div');$(_holder).css('display','none');$(_mainHolder).click(function(e){$(_holder).slideToggle("fast");})
_holder.setAttribute('id',$(this).attr('id')+'_wise_select_list');_holder.setAttribute('parent_id',$(this).attr('id'));_holder.setAttribute('class',options.classPrefix+'list');var _list=document.createElement('ul');var _mainList=$(this);var _allItem=null;if($(this).attr('multiple'))
{_allItem=document.createElement('li');$(_allItem).html(options.all);$(_allItem).hover(function(){if(this.className!=options.classPrefix+'list_item_selected')
$(this).addClass(options.classPrefix+'list_item_hover');},function(){if(this.className!=options.classPrefix+'list_item_selected')
$(this).removeClass(options.classPrefix+'list_item_hover');});$(_allItem).click(function()
{if($(this).is('.'+options.classPrefix+'list_item_selected'))
{$('option',$(_mainList)).each(function(){$(this).removeAttr('selected');});}
else
{$('option',$(_mainList)).each(function(){$(this).attr('selected','selected');});}
$(this).wiseSelectRedrawList(_list,_mainList,options,_mainHolder,_allItem);});_list.appendChild(_allItem);}
$('option',$(this)).each(function(){var _item=document.createElement('li');$(_item).html($(this).html());$(_item).attr('value',$(this).val());_list.appendChild(_item);$(_item).hover(function(){if(this.className!=options.classPrefix+'list_item_selected')
$(this).addClass(options.classPrefix+'list_item_hover');},function(){if(this.className!=options.classPrefix+'list_item_selected')
$(this).removeClass(options.classPrefix+'list_item_hover');});$(_item).click(function(e)
{var _optValue=$(this).attr('value');var _parent=$(this);$('option',$("#"+$(this).parent().parent().attr('parent_id'))).each(function(){if($(this).val()==_optValue)
{if($(this).attr('selected'))
{this.selected=false;$(this).wiseSelectRedrawList(_list,_mainList,options,_mainHolder,_allItem);}
else
{this.selected=true;$(this).wiseSelectRedrawList(_list,_mainList,options,_mainHolder,_allItem);}}});});});$(this).wiseSelectRedrawList(_list,_mainList,options,_mainHolder,_allItem);_holder.appendChild(_list);return _holder;}
$.fn.wiseSelectRedrawList=function(list,select,options,_mainHolder,_allItem)
{var _selected="";var _numberOfSelected=0;var _totalItems=$('option',$(select)).size();$('option',$(select)).each(function(){var _option=$(this);$('li',$(list)).each(function(){if($(this).attr('value')==$(_option).val())
{if($(_option).attr('selected'))
{if(_selected.length>0)
{_selected+=", ";}
_selected+=$(_option).html();$(this).addClass(options.classPrefix+'list_item_selected');_numberOfSelected++;}
else
{$(this).removeClass(options.classPrefix+'list_item_selected');}}});});$(_mainHolder).html(_selected);$(_mainHolder).attr('title',_selected);if(_allItem!=null)
{if(_numberOfSelected==_totalItems)
{$(_allItem).addClass(options.classPrefix+'list_item_selected');$(_mainHolder).html(options.all);}
else
{$(_allItem).removeClass(options.classPrefix+'list_item_selected');}}}})(jQuery);