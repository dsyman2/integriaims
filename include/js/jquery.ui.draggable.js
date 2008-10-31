(function(A){A.widget("ui.draggable",A.extend({},A.ui.mouse,{getHandle:function(C){var B=!this.options.handle||!A(this.options.handle,this.element).length?true:false;A(this.options.handle,this.element).find("*").andSelf().each(function(){if(this==C.target){B=true}});return B},createHelper:function(){var C=this.options;var B=A.isFunction(C.helper)?A(C.helper.apply(this.element[0],[e])):(C.helper=="clone"?this.element.clone():this.element);if(!B.parents("body").length){B.appendTo((C.appendTo=="parent"?this.element[0].parentNode:C.appendTo))}if(B[0]!=this.element[0]&&!(/(fixed|absolute)/).test(B.css("position"))){B.css("position","absolute")}return B},_init:function(){if(this.options.helper=="original"&&!(/^(?:r|a|f)/).test(this.element.css("position"))){this.element[0].style.position="relative"}(this.options.cssNamespace&&this.element.addClass(this.options.cssNamespace+"-draggable"));(this.options.disabled&&this.element.addClass("ui-draggable-disabled"));this._mouseInit()},_mouseCapture:function(B){var C=this.options;if(this.helper||C.disabled||A(B.target).is(".ui-resizable-handle")){return false}this.handle=this.getHandle(B);if(!this.handle){return false}return true},_mouseStart:function(D){var E=this.options;this.helper=this.createHelper();if(A.ui.ddmanager){A.ui.ddmanager.current=this}this.margins={left:(parseInt(this.element.css("marginLeft"),10)||0),top:(parseInt(this.element.css("marginTop"),10)||0)};this.cssPosition=this.helper.css("position");this.offset=this.element.offset();this.offset={top:this.offset.top-this.margins.top,left:this.offset.left-this.margins.left};this.offset.click={left:D.pageX-this.offset.left,top:D.pageY-this.offset.top};this.cacheScrollParents();this.offsetParent=this.helper.offsetParent();var B=this.offsetParent.offset();if(this.offsetParent[0]==document.body&&A.browser.mozilla){B={top:0,left:0}}this.offset.parent={top:B.top+(parseInt(this.offsetParent.css("borderTopWidth"),10)||0),left:B.left+(parseInt(this.offsetParent.css("borderLeftWidth"),10)||0)};if(this.cssPosition=="relative"){var C=this.element.position();this.offset.relative={top:C.top-(parseInt(this.helper.css("top"),10)||0)+this.scrollTopParent.scrollTop(),left:C.left-(parseInt(this.helper.css("left"),10)||0)+this.scrollLeftParent.scrollLeft()}}else{this.offset.relative={top:0,left:0}}this.originalPosition=this._generatePosition(D);this.cacheHelperProportions();if(E.cursorAt){this.adjustOffsetFromHelper(E.cursorAt)}A.extend(this,{PAGEY_INCLUDES_SCROLL:(this.cssPosition=="absolute"&&(!this.scrollTopParent[0].tagName||(/(html|body)/i).test(this.scrollTopParent[0].tagName))),PAGEX_INCLUDES_SCROLL:(this.cssPosition=="absolute"&&(!this.scrollLeftParent[0].tagName||(/(html|body)/i).test(this.scrollLeftParent[0].tagName))),OFFSET_PARENT_NOT_SCROLL_PARENT_Y:this.scrollTopParent[0]!=this.offsetParent[0]&&!(this.scrollTopParent[0]==document&&(/(body|html)/i).test(this.offsetParent[0].tagName)),OFFSET_PARENT_NOT_SCROLL_PARENT_X:this.scrollLeftParent[0]!=this.offsetParent[0]&&!(this.scrollLeftParent[0]==document&&(/(body|html)/i).test(this.offsetParent[0].tagName))});if(E.containment){this.setContainment()}this._propagate("start",D);this.cacheHelperProportions();if(A.ui.ddmanager&&!E.dropBehaviour){A.ui.ddmanager.prepareOffsets(this,D)}this.helper.addClass("ui-draggable-dragging");this._mouseDrag(D);return true},cacheScrollParents:function(){this.scrollTopParent=function(B){do{if(/auto|scroll/.test(B.css("overflow"))||(/auto|scroll/).test(B.css("overflow-y"))){return B}B=B.parent()}while(B[0].parentNode);return A(document)}(this.helper);this.scrollLeftParent=function(B){do{if(/auto|scroll/.test(B.css("overflow"))||(/auto|scroll/).test(B.css("overflow-x"))){return B}B=B.parent()}while(B[0].parentNode);return A(document)}(this.helper)},adjustOffsetFromHelper:function(B){if(B.left!=undefined){this.offset.click.left=B.left+this.margins.left}if(B.right!=undefined){this.offset.click.left=this.helperProportions.width-B.right+this.margins.left}if(B.top!=undefined){this.offset.click.top=B.top+this.margins.top}if(B.bottom!=undefined){this.offset.click.top=this.helperProportions.height-B.bottom+this.margins.top}},cacheHelperProportions:function(){this.helperProportions={width:this.helper.outerWidth(),height:this.helper.outerHeight()}},setContainment:function(){var E=this.options;if(E.containment=="parent"){E.containment=this.helper[0].parentNode}if(E.containment=="document"||E.containment=="window"){this.containment=[0-this.offset.relative.left-this.offset.parent.left,0-this.offset.relative.top-this.offset.parent.top,A(E.containment=="document"?document:window).width()-this.offset.relative.left-this.offset.parent.left-this.helperProportions.width-this.margins.left-(parseInt(this.element.css("marginRight"),10)||0),(A(E.containment=="document"?document:window).height()||document.body.parentNode.scrollHeight)-this.offset.relative.top-this.offset.parent.top-this.helperProportions.height-this.margins.top-(parseInt(this.element.css("marginBottom"),10)||0)]}if(!(/^(document|window|parent)$/).test(E.containment)){var C=A(E.containment)[0];var D=A(E.containment).offset();var B=(A(C).css("overflow")!="hidden");this.containment=[D.left+(parseInt(A(C).css("borderLeftWidth"),10)||0)-this.offset.relative.left-this.offset.parent.left,D.top+(parseInt(A(C).css("borderTopWidth"),10)||0)-this.offset.relative.top-this.offset.parent.top,D.left+(B?Math.max(C.scrollWidth,C.offsetWidth):C.offsetWidth)-(parseInt(A(C).css("borderLeftWidth"),10)||0)-this.offset.relative.left-this.offset.parent.left-this.helperProportions.width-this.margins.left-(parseInt(this.element.css("marginRight"),10)||0),D.top+(B?Math.max(C.scrollHeight,C.offsetHeight):C.offsetHeight)-(parseInt(A(C).css("borderTopWidth"),10)||0)-this.offset.relative.top-this.offset.parent.top-this.helperProportions.height-this.margins.top-(parseInt(this.element.css("marginBottom"),10)||0)]}},_convertPositionTo:function(C,D){if(!D){D=this.position}var B=C=="absolute"?1:-1;return{top:(D.top+this.offset.relative.top*B+this.offset.parent.top*B-(this.cssPosition=="fixed"||this.PAGEY_INCLUDES_SCROLL||this.OFFSET_PARENT_NOT_SCROLL_PARENT_Y?0:this.scrollTopParent.scrollTop())*B+(this.cssPosition=="fixed"?A(document).scrollTop():0)*B+this.margins.top*B),left:(D.left+this.offset.relative.left*B+this.offset.parent.left*B-(this.cssPosition=="fixed"||this.PAGEX_INCLUDES_SCROLL||this.OFFSET_PARENT_NOT_SCROLL_PARENT_X?0:this.scrollLeftParent.scrollLeft())*B+(this.cssPosition=="fixed"?A(document).scrollLeft():0)*B+this.margins.left*B)}},_generatePosition:function(E){var F=this.options;var B={top:(E.pageY-this.offset.click.top-this.offset.relative.top-this.offset.parent.top+(this.cssPosition=="fixed"||this.PAGEY_INCLUDES_SCROLL||this.OFFSET_PARENT_NOT_SCROLL_PARENT_Y?0:this.scrollTopParent.scrollTop())-(this.cssPosition=="fixed"?A(document).scrollTop():0)),left:(E.pageX-this.offset.click.left-this.offset.relative.left-this.offset.parent.left+(this.cssPosition=="fixed"||this.PAGEX_INCLUDES_SCROLL||this.OFFSET_PARENT_NOT_SCROLL_PARENT_X?0:this.scrollLeftParent.scrollLeft())-(this.cssPosition=="fixed"?A(document).scrollLeft():0))};if(!this.originalPosition){return B}if(this.containment){if(B.left<this.containment[0]){B.left=this.containment[0]}if(B.top<this.containment[1]){B.top=this.containment[1]}if(B.left>this.containment[2]){B.left=this.containment[2]}if(B.top>this.containment[3]){B.top=this.containment[3]}}if(F.grid){var D=this.originalPosition.top+Math.round((B.top-this.originalPosition.top)/F.grid[1])*F.grid[1];B.top=this.containment?(!(D<this.containment[1]||D>this.containment[3])?D:(!(D<this.containment[1])?D-F.grid[1]:D+F.grid[1])):D;var C=this.originalPosition.left+Math.round((B.left-this.originalPosition.left)/F.grid[0])*F.grid[0];B.left=this.containment?(!(C<this.containment[0]||C>this.containment[2])?C:(!(C<this.containment[0])?C-F.grid[0]:C+F.grid[0])):C}return B},_mouseDrag:function(B){this.position=this._generatePosition(B);this.positionAbs=this._convertPositionTo("absolute");this.position=this._propagate("drag",B)||this.position;if(!this.options.axis||this.options.axis!="y"){this.helper[0].style.left=this.position.left+"px"}if(!this.options.axis||this.options.axis!="x"){this.helper[0].style.top=this.position.top+"px"}if(A.ui.ddmanager){A.ui.ddmanager.drag(this,B)}return false},_mouseStop:function(C){var D=false;if(A.ui.ddmanager&&!this.options.dropBehaviour){var D=A.ui.ddmanager.drop(this,C)}if((this.options.revert=="invalid"&&!D)||(this.options.revert=="valid"&&D)||this.options.revert===true||(A.isFunction(this.options.revert)&&this.options.revert.call(this.element,D))){var B=this;A(this.helper).animate(this.originalPosition,parseInt(this.options.revertDuration,10)||500,function(){B._propagate("stop",C);B._clear()})}else{this._propagate("stop",C);this._clear()}return false},_clear:function(){this.helper.removeClass("ui-draggable-dragging");if(this.options.helper!="original"&&!this.cancelHelperRemoval){this.helper.remove()}this.helper=null;this.cancelHelperRemoval=false},plugins:{},uiHash:function(B){return{helper:this.helper,position:this.position,absolutePosition:this.positionAbs,options:this.options}},_propagate:function(C,B){A.ui.plugin.call(this,C,[B,this.uiHash()]);if(C=="drag"){this.positionAbs=this._convertPositionTo("absolute")}return this.element.triggerHandler(C=="drag"?C:"drag"+C,[B,this.uiHash()],this.options[C])},destroy:function(){if(!this.element.data("draggable")){return }this.element.removeData("draggable").unbind(".draggable").removeClass("ui-draggable ui-draggable-dragging ui-draggable-disabled");this._mouseDestroy()}}));A.extend(A.ui.draggable,{defaults:{appendTo:"parent",axis:false,cancel:":input",delay:0,distance:1,helper:"original",scope:"default",cssNamespace:"ui"}});A.ui.plugin.add("draggable","cursor",{start:function(D,C){var B=A("body");if(B.css("cursor")){C.options._cursor=B.css("cursor")}B.css("cursor",C.options.cursor)},stop:function(C,B){if(B.options._cursor){A("body").css("cursor",B.options._cursor)}}});A.ui.plugin.add("draggable","zIndex",{start:function(D,C){var B=A(C.helper);if(B.css("zIndex")){C.options._zIndex=B.css("zIndex")}B.css("zIndex",C.options.zIndex)},stop:function(C,B){if(B.options._zIndex){A(B.helper).css("zIndex",B.options._zIndex)}}});A.ui.plugin.add("draggable","opacity",{start:function(D,C){var B=A(C.helper);if(B.css("opacity")){C.options._opacity=B.css("opacity")}B.css("opacity",C.options.opacity)},stop:function(C,B){if(B.options._opacity){A(B.helper).css("opacity",B.options._opacity)}}});A.ui.plugin.add("draggable","iframeFix",{start:function(C,B){A(B.options.iframeFix===true?"iframe":B.options.iframeFix).each(function(){A('<div class="ui-draggable-iframeFix" style="background: #fff;"></div>').css({width:this.offsetWidth+"px",height:this.offsetHeight+"px",position:"absolute",opacity:"0.001",zIndex:1000}).css(A(this).offset()).appendTo("body")})},stop:function(C,B){A("div.ui-draggable-iframeFix").each(function(){this.parentNode.removeChild(this)})}});A.ui.plugin.add("draggable","scroll",{start:function(D,C){var E=C.options;var B=A(this).data("draggable");E.scrollSensitivity=E.scrollSensitivity||20;E.scrollSpeed=E.scrollSpeed||20;B.overflowY=function(F){do{if(/auto|scroll/.test(F.css("overflow"))||(/auto|scroll/).test(F.css("overflow-y"))){return F}F=F.parent()}while(F[0].parentNode);return A(document)}(this);B.overflowX=function(F){do{if(/auto|scroll/.test(F.css("overflow"))||(/auto|scroll/).test(F.css("overflow-x"))){return F}F=F.parent()}while(F[0].parentNode);return A(document)}(this);if(B.overflowY[0]!=document&&B.overflowY[0].tagName!="HTML"){B.overflowYOffset=B.overflowY.offset()}if(B.overflowX[0]!=document&&B.overflowX[0].tagName!="HTML"){B.overflowXOffset=B.overflowX.offset()}},drag:function(E,D){var F=D.options,B=false;var C=A(this).data("draggable");if(C.overflowY[0]!=document&&C.overflowY[0].tagName!="HTML"){if((C.overflowYOffset.top+C.overflowY[0].offsetHeight)-E.pageY<F.scrollSensitivity){C.overflowY[0].scrollTop=B=C.overflowY[0].scrollTop+F.scrollSpeed}if(E.pageY-C.overflowYOffset.top<F.scrollSensitivity){C.overflowY[0].scrollTop=B=C.overflowY[0].scrollTop-F.scrollSpeed}}else{if(E.pageY-A(document).scrollTop()<F.scrollSensitivity){B=A(document).scrollTop(A(document).scrollTop()-F.scrollSpeed)}if(A(window).height()-(E.pageY-A(document).scrollTop())<F.scrollSensitivity){B=A(document).scrollTop(A(document).scrollTop()+F.scrollSpeed)}}if(C.overflowX[0]!=document&&C.overflowX[0].tagName!="HTML"){if((C.overflowXOffset.left+C.overflowX[0].offsetWidth)-E.pageX<F.scrollSensitivity){C.overflowX[0].scrollLeft=B=C.overflowX[0].scrollLeft+F.scrollSpeed}if(E.pageX-C.overflowXOffset.left<F.scrollSensitivity){C.overflowX[0].scrollLeft=B=C.overflowX[0].scrollLeft-F.scrollSpeed}}else{if(E.pageX-A(document).scrollLeft()<F.scrollSensitivity){B=A(document).scrollLeft(A(document).scrollLeft()-F.scrollSpeed)}if(A(window).width()-(E.pageX-A(document).scrollLeft())<F.scrollSensitivity){B=A(document).scrollLeft(A(document).scrollLeft()+F.scrollSpeed)}}if(B!==false){A.ui.ddmanager.prepareOffsets(C,E)}}});A.ui.plugin.add("draggable","snap",{start:function(D,C){var B=A(this).data("draggable");B.snapElements=[];A(C.options.snap.constructor!=String?(C.options.snap.items||":data(draggable)"):C.options.snap).each(function(){var F=A(this);var E=F.offset();if(this!=B.element[0]){B.snapElements.push({item:this,width:F.outerWidth(),height:F.outerHeight(),top:E.top,left:E.left})}})},drag:function(P,K){var E=A(this).data("draggable");var Q=K.options.snapTolerance||20;var O=K.absolutePosition.left,N=O+E.helperProportions.width,D=K.absolutePosition.top,C=D+E.helperProportions.height;for(var M=E.snapElements.length-1;M>=0;M--){var L=E.snapElements[M].left,J=L+E.snapElements[M].width,I=E.snapElements[M].top,S=I+E.snapElements[M].height;if(!((L-Q<O&&O<J+Q&&I-Q<D&&D<S+Q)||(L-Q<O&&O<J+Q&&I-Q<C&&C<S+Q)||(L-Q<N&&N<J+Q&&I-Q<D&&D<S+Q)||(L-Q<N&&N<J+Q&&I-Q<C&&C<S+Q))){if(E.snapElements[M].snapping){(E.options.snap.release&&E.options.snap.release.call(E.element,null,A.extend(E.uiHash(),{snapItem:E.snapElements[M].item})))}E.snapElements[M].snapping=false;continue}if(K.options.snapMode!="inner"){var B=Math.abs(I-C)<=Q;var R=Math.abs(S-D)<=Q;var G=Math.abs(L-N)<=Q;var H=Math.abs(J-O)<=Q;if(B){K.position.top=E._convertPositionTo("relative",{top:I-E.helperProportions.height,left:0}).top}if(R){K.position.top=E._convertPositionTo("relative",{top:S,left:0}).top}if(G){K.position.left=E._convertPositionTo("relative",{top:0,left:L-E.helperProportions.width}).left}if(H){K.position.left=E._convertPositionTo("relative",{top:0,left:J}).left}}var F=(B||R||G||H);if(K.options.snapMode!="outer"){var B=Math.abs(I-D)<=Q;var R=Math.abs(S-C)<=Q;var G=Math.abs(L-O)<=Q;var H=Math.abs(J-N)<=Q;if(B){K.position.top=E._convertPositionTo("relative",{top:I,left:0}).top}if(R){K.position.top=E._convertPositionTo("relative",{top:S-E.helperProportions.height,left:0}).top}if(G){K.position.left=E._convertPositionTo("relative",{top:0,left:L}).left}if(H){K.position.left=E._convertPositionTo("relative",{top:0,left:J-E.helperProportions.width}).left}}if(!E.snapElements[M].snapping&&(B||R||G||H||F)){(E.options.snap.snap&&E.options.snap.snap.call(E.element,null,A.extend(E.uiHash(),{snapItem:E.snapElements[M].item})))}E.snapElements[M].snapping=(B||R||G||H||F)}}});A.ui.plugin.add("draggable","connectToSortable",{start:function(D,C){var B=A(this).data("draggable");B.sortables=[];A(C.options.connectToSortable).each(function(){if(A.data(this,"sortable")){var E=A.data(this,"sortable");B.sortables.push({instance:E,shouldRevert:E.options.revert});E._refreshItems();E._propagate("activate",D,B)}})},stop:function(D,C){var B=A(this).data("draggable");A.each(B.sortables,function(){if(this.instance.isOver){this.instance.isOver=0;B.cancelHelperRemoval=true;this.instance.cancelHelperRemoval=false;if(this.shouldRevert){this.instance.options.revert=true}this.instance._mouseStop(D);this.instance.element.triggerHandler("sortreceive",[D,A.extend(this.instance.ui(),{sender:B.element})],this.instance.options["receive"]);this.instance.options.helper=this.instance.options._helper}else{this.instance._propagate("deactivate",D,B)}})},drag:function(F,E){var D=A(this).data("draggable"),B=this;var C=function(K){var H=K.left,J=H+K.width,I=K.top,G=I+K.height;return(H<(this.positionAbs.left+this.offset.click.left)&&(this.positionAbs.left+this.offset.click.left)<J&&I<(this.positionAbs.top+this.offset.click.top)&&(this.positionAbs.top+this.offset.click.top)<G)};A.each(D.sortables,function(G){if(C.call(D,this.instance.containerCache)){if(!this.instance.isOver){this.instance.isOver=1;this.instance.currentItem=A(B).clone().appendTo(this.instance.element).data("sortable-item",true);this.instance.options._helper=this.instance.options.helper;this.instance.options.helper=function(){return E.helper[0]};F.target=this.instance.currentItem[0];this.instance._mouseCapture(F,true);this.instance._mouseStart(F,true,true);this.instance.offset.click.top=D.offset.click.top;this.instance.offset.click.left=D.offset.click.left;this.instance.offset.parent.left-=D.offset.parent.left-this.instance.offset.parent.left;this.instance.offset.parent.top-=D.offset.parent.top-this.instance.offset.parent.top;D._propagate("toSortable",F)}if(this.instance.currentItem){this.instance._mouseDrag(F)}}else{if(this.instance.isOver){this.instance.isOver=0;this.instance.cancelHelperRemoval=true;this.instance.options.revert=false;this.instance._mouseStop(F,true);this.instance.options.helper=this.instance.options._helper;this.instance.currentItem.remove();if(this.instance.placeholder){this.instance.placeholder.remove()}D._propagate("fromSortable",F)}}})}});A.ui.plugin.add("draggable","stack",{start:function(D,B){var C=A.makeArray(A(B.options.stack.group)).sort(function(F,E){return(parseInt(A(F).css("zIndex"),10)||B.options.stack.min)-(parseInt(A(E).css("zIndex"),10)||B.options.stack.min)});A(C).each(function(E){this.style.zIndex=B.options.stack.min+E});this[0].style.zIndex=B.options.stack.min+C.length}})})(jQuery)