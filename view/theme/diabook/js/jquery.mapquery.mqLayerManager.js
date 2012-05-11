/* Copyright (c) 2011 by MapQuery Contributors (see AUTHORS for
 * full list of contributors). Published under the MIT license.
 * See https://github.com/mapquery/mapquery/blob/master/LICENSE for the
 * full text of the license. */

/**
#jquery.mapquery.mqLayerManager.js
The file containing the mqLayerManager Widget

### *$('selector')*.`mqLayerManager([options])`
_version added 0.1_
####**Description**: create a widget to manage layers

 + **options**:
  - **map**: the mapquery instance
  - **title**: Title that will be displayed at the top of the 
  layer manager (default: Layer Manager)


>Returns: widget

>Requires: jquery.mapquery.legend.js


The mqLayerManager allows us to control the order, opacity and visibility
of layers. We can also remove layers. It also shows the legend of the layer if
available and the error messages provided by the legend plugin. It listens to
layerchange event for order, transparancy and opacity changes. It listens to
addlayer and removelayer events to keep track which layers are on the map.


      $('#layermanager').mqLayerManager({map:'#map'});


 */
(function($) {
$.template('mqLayerManager',
    '<div class="mq-layermanager ui-widget-content  ">'+
    '</div>');

$.template('mqLayerManagerElement',
    '<div class="mq-layermanager-element ui-widget-content ui-corner-all" id="mq-layermanager-element-${id}">'+
    '<div class="mq-layermanager-element-header ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix">'+
    '<span class="mq-layermanager-label ui-dialog-title">${label}</span>'+
    '<a class="ui-dialog-titlebar-close ui-corner-all" href="#" role="button">'+
    '<span class="ui-icon ui-icon-closethick">close</span></a></div>'+
    '<div class="mq-layermanager-element-content">'+
        '<div class="mq-layermanager-element-visibility">'+
            '<input type="checkbox" class="mq-layermanager-element-vischeckbox" id="${id}-visibility" {{if visible}}checked="${visible}"{{/if}} />'+
            '<div class="mq-layermanager-element-slider-container">'+
        '<div class="mq-layermanager-element-slider"></div></div>'+
        '</div>'+
        '<div class="mq-layermanager-element-legend">'+
            '{{if imgUrl}}'+
                '<img src="${imgUrl}" style="opacity:${opacity}"/>'+
            '{{/if}}'+
            '{{if errMsg}}'+
                '${errMsg}'+
            '{{/if}}'+
        '</div>'+
    '</div>'+
    '</div>');

$.widget("mapQuery.mqLayerManager", {
    options: {
        // The MapQuery instance
        map: undefined,

        // Title that will be displayed at the top of the popup
        title: "Layer Manager"
    },
    _create: function() {
        var map;
        var zoom;
        var numzoomlevels;
        var self = this;
        var element = this.element;

        //get the mapquery object
        map = $(this.options.map).data('mapQuery');

        this.element.addClass('ui-widget  ui-helper-clearfix ' +
                              'ui-corner-all');

        var lmElement = $.tmpl('mqLayerManager').appendTo(element);
        element.find('.ui-icon-closethick').button();

        lmElement.sortable({
            axis:'y',
            handle: '.mq-layermanager-element-header',
            update: function(event, ui) {
                var layerNodes = ui.item.siblings().andSelf();
                var num = layerNodes.length-1;
                layerNodes.each(function(i) {
                    var layer = $(this).data('layer');
                    var pos = num-i;
                    self._position(layer, pos);
                });
            }
        });

        //these layers are already added to the map as such won't trigger 
    //and event, we call the draw function directly
        $.each(map.layers().reverse(), function(){
           self._layerAdded(lmElement, this);
        });

        element.delegate('.mq-layermanager-element-vischeckbox',
            'change',function() {
            var checkbox = $(this);
            var element = checkbox.parents('.mq-layermanager-element');
            var layer = element.data('layer');
            var self = element.data('self');
            self._visible(layer,checkbox.attr('checked'));
         });

        element.delegate('.ui-icon-closethick', 'click', function() {
            var control = $(this).parents('.mq-layermanager-element');
            self._remove(control.data('layer'));
        });

        //binding events
        map.bind("mqAddLayer",
            {widget:self,control:lmElement},
            self._onLayerAdd);

        map.bind("mqRemoveLayer",
            {widget:self,control:lmElement},
            self._onLayerRemove);

        map.bind("changelayer",
            {widget:self,map:map,control:lmElement},
            self._onLayerChange);

        map.bind("moveend",
            {widget:self,map:map,control:lmElement},
            self._onMoveEnd);
    },
    _destroy: function() {
        this.element.removeClass(' ui-widget ui-helper-clearfix ' +
                                 'ui-corner-all')
            .empty();
    },
    //functions that actually change things on the map
    //call these from within the widget to do stuff on the map
    //their actions will trigger events on the map and in return
    //will trigger the _layer* functions
    _add: function(map,layer) {
        map.layers(layer);
    },

    _remove: function(layer) {
        layer.remove();
    },

    _position: function(layer, pos) {
        layer.position(pos);
    },

    _visible: function(layer, vis) {
        layer.visible(vis);
    },

    _opacity: function(layer,opac) {
        layer.opacity(opac);
    },

    //functions that change the widget
    _layerAdded: function(widget, layer) {
        var self = this;
        var error = layer.legend().msg;
        var url;
        switch(error){
            case '':
                url =layer.legend().url;
                if(url==''){error='No legend for this layer';}
                break;
            case 'E_ZOOMOUT':
                error = 'Please zoom out to see this layer';
                break;
            case 'E_ZOOMIN':
                error = 'Please zoom in to see this layer';
                break;
            case 'E_OUTSIDEBOX':
                error = 'This layer is outside the current view';
                break;
        }

        var layerElement = $.tmpl('mqLayerManagerElement',{
            id: layer.id,
            label: layer.label,
            position: layer.position(),
            visible: layer.visible(),
            imgUrl: url,
            opacity: layer.visible()?layer.opacity():0,
            errMsg: error
        })
            // save layer layer in the DOM, so we can easily
            // hide/show/delete the layer with live events
            .data('layer', layer)
            .data('self',self)
            .prependTo(widget);

       $(".mq-layermanager-element-slider", layerElement).slider({
           max: 100,
           step: 1,
           value: layer.visible()?layer.opacity()*100:0,
           slide: function(event, ui) {
               var layer = layerElement.data('layer');
               var self =  layerElement.data('self');
               self._opacity(layer,ui.value/100);
           },
           //using the slide event to check for the checkbox often gives errors.
           change: function(event, ui) {
               var layer = layerElement.data('layer');
               var self =  layerElement.data('self');
               if(ui.value>=0.01) {
                   if(!layer.visible()){layer.visible(true);}
               }
               if(ui.value<0.01) {
                   if(layer.visible()){layer.visible(false);}
               }
           }
       });
    },

    _layerRemoved: function(widget, id) {
        var control = $("#mq-layermanager-element-"+id);
        control.fadeOut(function() {
            $(this).remove();
        });
    },

    _layerPosition: function(widget, layer) {
        var layerNodes = widget.element.find('.mq-layermanager-element');
        var num = layerNodes.length-1;
        var tmpNodes = [];
        tmpNodes.length = layerNodes.length;
        layerNodes.each(function() {
            var layer = $(this).data('layer');
            var pos = num-layer.position();
            tmpNodes[pos]= this;
        });
        for (i=0;i<tmpNodes.length;i++) {
            layerNodes.parent().append(tmpNodes[i]);
        }
    },

    _layerVisible: function(widget, layer) {
        var layerElement =
        widget.element.find('#mq-layermanager-element-'+layer.id);
        var checkbox =
        layerElement.find('.mq-layermanager-element-vischeckbox');
        checkbox[0].checked = layer.visible();
        //update the opacity slider as well
        var slider = layerElement.find('.mq-layermanager-element-slider');
        var value = layer.visible()?layer.opacity()*100: 0;
        slider.slider('value',value);

        //update legend image
        layerElement.find('.mq-layermanager-element-legend img').css(
            {visibility:layer.visible()?true:'hidden'});
    },

    _layerOpacity: function(widget, layer) {
        var layerElement = widget.element.find(
            '#mq-layermanager-element-'+layer.id);
        var slider = layerElement.find(
            '.mq-layermanager-element-slider');
        slider.slider('value',layer.opacity()*100);
        //update legend image
        layerElement.find(
            '.mq-layermanager-element-legend img').css(
            {opacity:layer.opacity()});
    },

    _moveEnd: function (widget,lmElement,map) {
        lmElement.empty();
        $.each(map.layers().reverse(), function(){
           widget._layerAdded(lmElement, this);
        });
    },

    //functions bind to the map events
    _onLayerAdd: function(evt, layer) {
        evt.data.widget._layerAdded(evt.data.control,layer);
    },

    _onLayerRemove: function(evt, id) {
        evt.data.widget._layerRemoved(evt.data.control,id);
    },

    _onLayerChange: function(evt, data) {
        var layer;
        //since we don't know which layer we get we've to loop through
        //the openlayers.layer.ids to find the correct one
        $.each(evt.data.map.layers(), function(){
           if(this.olLayer.id == data.layer.id) {layer=this;}
        });
        //(name, order, opacity, params, visibility or attribution)
         switch(data.property) {
            case 'opacity':
                evt.data.widget._layerOpacity(evt.data.widget,layer);
            break;
            case 'order':
                evt.data.widget._layerPosition(evt.data.widget,layer);
            break;
            case 'visibility':
                evt.data.widget._layerVisible(evt.data.widget,layer);
            break;
        }
    },
    _onMoveEnd: function(evt) {
        evt.data.widget._moveEnd(evt.data.widget,evt.data.control,evt.data.map);
    }
});
})(jQuery);
