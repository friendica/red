/* Copyright (c) 2011 by MapQuery Contributors (see AUTHORS for
 * full list of contributors). Published under the MIT license.
 * See https://github.com/mapquery/mapquery/blob/master/LICENSE for the
 * full text of the license. */

/**
#jquery.mapquery.mqMousePosition.js
The file containing the mqMousePosition Widget

### *$('selector')*.`mqMousePosition([options])`
_version added 0.1_
####**Description**: create a widget to show the location under the mouse pointer

 + **options**
  - **map**: the mapquery instance
  - **precision**: the number of decimals (default 2)
  - **x**: the label for the x-coordinate (default x)
  - **y**: the label for the y-coordinate (default y)


>Returns: widget


The mqMousePosition allows us to show the coordinates under the mouse pointer


     $('#mousepointer').mqMousePointer({
        map: '#map'
     });

 */
(function($) {
$.template('mqMousePosition',
    '<div class="mq-mouseposition ui-widget ui-helper-clearfix ">'+
    '<span class="ui-widget-content ui-helper-clearfix ui-corner-all ui-corner-all">'+
    '<div id="mq-mouseposition-x" class="mq-mouseposition-coordinate">'+
    '</div><div id="mq-mouseposition-y" class="mq-mouseposition-coordinate">'+
    '</div></div></span>');

$.widget("mapQuery.mqMousePosition", {
    options: {
        // The MapQuery instance
        map: undefined,

        // The number of decimals for the coordinates
        // default: 2
        // TODO: JCB20110630 use dynamic precision based on the pixel
        // resolution, no need to configure precision
        precision: 2,

        // The label of the x-value
        // default: 'x'
        x: 'x',
        // The label of the y-value
        // default: 'y'
        y: 'y'

    },
    _create: function() {
        //get the mapquery object
        this.map = $(this.options.map).data('mapQuery');

        this.map.element.bind('mousemove', {widget: this}, this._onMousemove);
        $.tmpl('mqMousePosition', {}).appendTo(this.element);

    },
    _destroy: function() {
        this.element.removeClass('ui-widget ui-helper-clearfix ' +
                                 'ui-corner-all')
            .empty();
    },
    _onMousemove: function(evt) {
        var self = evt.data.widget;
        var x = evt.pageX;
        var y = evt.pageY;
        var mapProjection = new OpenLayers.Projection(self.map.projection);
        var displayProjection = new OpenLayers.Projection(
            self.map.displayProjection);
        var pos = self.map.olMap.getLonLatFromLayerPx(
            new OpenLayers.Pixel(x, y));
        //if the coordinates should be displayed in something else,
        //set them via the map displayProjection option
        if(!mapProjection.equals(self.map.displayProjection)) {
            pos = pos.transform(mapProjection, displayProjection);
        }
        $("#id_diabook_ELPosX", document.element).val(
            self.options.x + pos.lon.toFixed(self.options.precision));
        $("#id_diabook_ELPosY", document.element).val(
            self.options.y + pos.lat.toFixed(self.options.precision));
    }
});
})(jQuery);
