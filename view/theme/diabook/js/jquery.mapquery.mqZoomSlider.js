/* Copyright (c) 2011 by MapQuery Contributors (see AUTHORS for
 * full list of contributors). Published under the MIT license.
 * See https://github.com/mapquery/mapquery/blob/master/LICENSE for the
 * full text of the license. */


/**
#jquery.mapquery.mqZoomSlider.js
The file containing the mqZoomSlider Widget

### *$('selector')*.`mqZoomSlider([options])`
_version added 0.1_
####**Description**: create a widget to show a zoom slider

 + **options**:
  - **map**: the mapquery instance

>Returns: widget


The mqZoomSlider widget allows us to display a vertical zoom slider.


     $('#zoomslider').mqZoomSlider({
        map: '#map'
     });

 */
(function($) {
$.template('mqZoomSlider',
    '<div class="mq-zoomslider ui-widget ui-helper-clearfix ">'+
    '<div class="mq-zoomslider-slider"></div>'+
    '</div>');

$.widget("mapQuery.mqZoomSlider", {
    options: {
        // The MapQuery instance
        map: undefined

    },
    _create: function() {
        var map;
        var zoom;
        var numzoomlevels;
        var self = this;
        var element = this.element;

        //get the mapquery object
        map = $(this.options.map).data('mapQuery');

        $.tmpl('mqZoomSlider').appendTo(element);

        numzoomlevels = map.options.numZoomLevels;
        $(".mq-zoomslider-slider", element).slider({
           max: numzoomlevels,
           min:2,
           orientation: 'vertical',
           step: 1,
           value: numzoomlevels - map.center().zoom,
           slide: function(event, ui) {
               map.center({zoom:numzoomlevels-ui.value});
           },
           change: function(event, ui) {
               map.center({zoom:numzoomlevels-ui.value});
           }
       });
       map.bind("zoomend",
            {widget:self,map:map,control:element},
            self._onZoomEnd);

    },
    _destroy: function() {
        this.element.removeClass(' ui-widget ui-helper-clearfix ' +
                                 'ui-corner-all')
            .empty();
    },
    _zoomEnd: function (element,map) {
        var slider = element.find('.mq-zoomslider-slider');
        slider.slider('value',map.options.numZoomLevels-map.center().zoom);
    },
    _onZoomEnd: function(evt) {
        evt.data.widget._zoomEnd(evt.data.control,evt.data.map);
    }
});
})(jQuery);