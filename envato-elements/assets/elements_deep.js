(()=>{"use strict";var e=null;const t=wp.media&&wp.media.View?wp.media.View.extend({tagName:"div",className:"attachments-browser envatoelements-attachments-browser",attribution:null,initialize:function(){_.defaults(this.options,{search:!1}),e=this,window.envatoElements.initPhotos(this.$el.get(0),this.photoUploadComplete)},photoUploadComplete:function(t){t&&t.attachment_data&&(e.model.frame.content.mode("browse"),e.model.get("selection").add(t.attachment_data),e.model.frame.trigger("library:selection:add"),e.model.get("selection"),jQuery(".media-frame .media-button-select").click())},dispose:function(){return wp.media.View.prototype.dispose.apply(this,arguments),this}}):null;"undefined"!=typeof jQuery&&function(e){if("undefined"!=typeof wp&&wp.media){var i=wp.media.view.MediaFrame.Post,o=wp.media.view.MediaFrame.Select;wp.media.view.MediaFrame.Post=i.extend({browseRouter(e){i.prototype.browseRouter.apply(this,arguments),e.set({envatoElements:{text:"Envato Elements",priority:60}})},bindHandlers(){i.prototype.bindHandlers.apply(this,arguments),this.on("content:create:envatoElements",this.envatoElementsContent,this)},envatoElementsContent(e){var i=this.state();this.$el.addClass("hide-toolbar"),e.view=new t({collection:i.get("envatoElements-images"),selection:i.get("envatoElements-selection"),controller:this,model:i,idealColumnWidth:i.get("idealColumnWidth"),suggestedWidth:i.get("suggestedWidth"),suggestedHeight:i.get("suggestedHeight")})},getFrame(e){return this.states.findWhere({id:e})}}),wp.media.view.MediaFrame.Select=o.extend({browseRouter(e){o.prototype.browseRouter.apply(this,arguments),e.set({envatoElements:{text:"Envato Elements",priority:60}})},bindHandlers(){o.prototype.bindHandlers.apply(this,arguments),this.on("content:create:envatoElements",this.envatoElementsContent,this)},envatoElementsContent(e){var i=this.state();i.get("provider")||i.set("provider",""),this.$el.addClass("hide-toolbar"),e.view=new t({collection:i.get("envatoElements-images"),selection:i.get("envatoElements-selection"),controller:this,model:i,idealColumnWidth:i.get("idealColumnWidth"),suggestedWidth:i.get("suggestedWidth"),suggestedHeight:i.get("suggestedHeight"),provider:i.get("provider")})},getFrame(e){return this.states.findWhere({id:e})}})}}(jQuery)})();