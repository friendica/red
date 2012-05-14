/* Copyright (c) 2011 by MapQuery Contributors (see AUTHORS for
 * full list of contributors). Published under the MIT license.
 * See https://github.com/mapquery/mapquery/blob/master/LICENSE for the
 * full text of the license. */
(function ($) {
/**
# jquery.mapquery.core.js
The main MapQuery file. It contains the MapQuery constructor, the MapQuery.Map
constructor and the MapQuery.Layer constructor.


### *$('selector')*.`mapQuery([options])`
_version added 0.1_
####**Description**: initialise MapQuery and associate it with
the matched element

**options**  an object of key-value pairs with options for the map. Possible
pairs are:

 * **layers** (array of MapQuery.Layer *or* MapQuery.Layer): Either an array 
 or a single layer that should be added to the map
 * **center** ({position: [x,y], zoom: z(int), box: [llx,lly,urx,ury]}):
 Initially go to a certain location. At least one layer (in the `layers`
 option) needs to be specified.

> Returns: $('selector') (jQuery object)


We can initialise MapQuery without any options, or for instance pass in a layer
object. The mapQuery function returns a jQuery object, to access the mapQuery object retrieve
the 'mapQuery' data object.

     var map = $('#map').mapQuery(); //create an empty map
     var map = $('#map').mapQuery({layers:[{type:'osm'}]); //create a map with osm

     var mq = map.data('mapQuery'); //get the MapQuery object
 */
$.MapQuery = $.MapQuery || {};

/**

---

#MapQuery.Map

The MapQuery.Map object. It is automatically constructed from the options
given in the `mapQuery([options])` constructor. The Map object is refered
to as _map_ in the documentation.
 */
$.MapQuery.Map = function(element, options) {
    var self = this;
    //If there are a maxExtent and a projection other than Spherical Mercator
    //automagically set maxResolution if it is not set
    //TODO smo 20110614: put maxExtent and maxResolution setting in the
    //proper option building routine
    if(options){
    if(!options.maxResolution&&options.maxExtent&&options.projection){
        options.maxResolution = (options.maxExtent[2]-options.maxExtent[0])/256;
    }}
    this.options = $.extend({}, new $.fn.mapQuery.defaults.map(), options);

    this.element = element;
    // TODO vmx 20110609: do proper options building
    // TODO SMO 20110616: make sure that all projection strings are uppercase
    // smo 20110620: you need the exact map options in the overviewmap widget
    // as such we need to preserve them
    this.olMapOptions = $.extend({}, this.options);
    delete this.olMapOptions.layers;
    delete this.olMapOptions.maxExtent;
    delete this.olMapOptions.zoomToMaxExtent;
    delete this.olMapOptions.center;

    //TODO SMO20110630 the maxExtent is in mapprojection, decide whether or
    //not we need to change it to displayProjection
    this.maxExtent = this.options.maxExtent;
    this.olMapOptions.maxExtent = new OpenLayers.Bounds(
    this.maxExtent[0],this.maxExtent[1],this.maxExtent[2],this.maxExtent[3]);


    this.projection = this.options.projection;
    this.displayProjection = this.options.displayProjection;

    OpenLayers.IMAGE_RELOAD_ATTEMPTS = 3;
    OpenLayers.Util.onImageLoadErrorColor = "transparent";

    // create the OpenLayers Map
    this.olMap = new OpenLayers.Map(this.element[0], this.olMapOptions);

    //OpenLayers doesn't want to return a maxExtent when there is no baselayer
    //set (eg on an empty map, so we create a fake baselayer
    this.olMap.addLayer(new OpenLayers.Layer('fake', {baseLayer: true}));

    // Keep IDs of vector layer for select feature control
    this.vectorLayers = [];
    this.selectFeatureControl = null;
    // Counts up to create unique IDs
    this.idCounter = 0;

    element.data('mapQuery', this);
    this.layersList = {};

    // To bind and trigger jQuery events
    this.events = $({});

    this.handlers = {
        // Triggers the jQuery events, after the OpenLayers events
        // happened without any further processing
        simple: function(data) {
            this.trigger(data.type);
        }
    };

    // MapQuery doesn't bind all OpenLayers events automatically,
    // but just the ones that make sense.
    // Events that are left out intensionally are:
    //  - changebaselayer: MapQuery doesn't have the concept of base layers
    //  - mouseover, mouseout, mousemove: Handle those with jQuery on the
    //        DOM level
    // Some events can be triggered by MapQuery without listening to the
    // OpenLayers events. This only works for events that are triggered
    // by functionality that MapQuery implements in some custom way, e.g.
    // (pre)addlayer, (pre)removelayer, changelayer.
    // TODO vmx 20120309: Proper docs for the events, here's some quickly
    // written info:
    //  - generally spoken, the map events follow the OpeLayer events
    //  - preaddlayer, movestart, move, moveend, zoomend: no additional
    //        argument
    //  - addlayer, preremovelayer, removelayer: layer as additional argument
    //  - changelayer: layer and the property that changed as additional
    //        argument. Possible values for the property are: position (in
    //        the layer stack), opacity, visibility
    //        Currently this event is always fired, even if the property
    //        was only meant to be changed, but wasn't exctually changed.
    //        I.e. that the event is fired even if you call
    //        `layer.visible(true)` although the layer is already visible.
    //        I'm (vmx) not sure if we want to change that :)
    this.olMap.events.on({
        scope: this,
        movestart: this.handlers.simple,
        move: this.handlers.simple,
        moveend: this.handlers.simple,
        zoomend: this.handlers.simple
    });

    // Add layers to the map
    if (this.options.layers!==undefined) {
        this.layers(this.options.layers);
        // You can only go to some location if there were layers added
        if (this.options.center!==undefined) {
            this.center(this.options.center);
        }
    }

    // zoom to the maxExtent of the map if no precise location was specified
    if (this.options.zoomToMaxExtent && this.options.center===undefined) {
        this.olMap.zoomToMaxExtent();
    }
};

$.MapQuery.Map.prototype = {
 /**
###*map*.`layers([options])`
_version added 0.1_
####**Description**: get/set the layers of the map

**options** an object of key-value pairs with options to create one or
more layers

>Returns: [layer] (array of MapQuery.Layer) _or_ false


The `.layers()` method allows us to attach layers to a mapQuery object. It takes
an options object with layer options. To add multiple layers, create an array of
layer options objects. If an options object is given, it will return the
resulting layer(s). We can also use it to retrieve all layers currently attached
to the map.

When adding layers, those are returned. If the creation is cancled by returning
`false` in the `preaddlayer` event, this function returns `false` to
intentionally break the chain instead of hiding errors subtly).


     var osm = map.layers({type:'osm'}); //add an osm layer to the map
     var layers = map.layers(); //get all layers of the map

     */
    layers: function(options) {
        //var o = $.extend({}, options);
        var self = this;
        switch(arguments.length) {
        case 0:
            return this._allLayers();
        case 1:
            if (!$.isArray(options)) {
                return this._addLayer(options);
            }
            else {
                return $.map(options, function(layer) {
                    return self._addLayer(layer);
                }).reverse();
            }
            break;
        default:
            throw('wrong argument number');
        }
    },
    // Returns all layers as an array, sorted by there order in the map. First
    // element in the array is the topmost layer
    _allLayers: function() {
        var layers = [];
        $.each(this.layersList, function(id, layer) {
            var item = [layer.position(), layer];
            layers.push(item);
        });
        var sorted = layers.sort( function compare(a, b) {
            return a[0] - b[0];
        });
        var result = $.map(sorted, function(item) {
            return item[1];
        });
        return result.reverse();
    },
    _addLayer: function(options) {
        var id = this._createId();
        var layer = new $.MapQuery.Layer(this, id, options);
        // NOTE vmx 20120305: Not sure if this is a good idea, or if it would
        //     be better to include `options` with the preaddlayer event
        if (this._triggerReturn('preaddlayer', [layer])===false) {
            return false;
        }
        this.olMap.addLayer(layer.olLayer);

        this.layersList[id] = layer;
        if (layer.isVector) {
            this.vectorLayers.push(id);
        }
        this._updateSelectFeatureControl(this.vectorLayers);

        layer.trigger('addlayer');
        return layer;
    },
    // Creates a new unique ID for a layer
    _createId: function() {
        return 'mapquery_' + this.idCounter++;
    },
    _removeLayer: function(id) {
        var layer = this.layersList[id];
        if (this._triggerReturn('preremovelayer', [layer])===false) {
            return false;
        }

        // remove id from vectorlayer if it is there list
        this.vectorLayers = $.grep(this.vectorLayers, function(elem) {
            return elem != id;
        });
        this._updateSelectFeatureControl(this.vectorLayers);
        this.olMap.removeLayer(layer.olLayer);

        // XXX vmx: shouldn't the layer be destroyed() properly?
        delete this.layersList[id];

        layer.trigger('removelayer');
        return this;
    },
/**
 ###*map*.`center([options])`
_version added 0.1_
####**Description**: get/set the extent, zoom and position of the map

 * **position** the position as [x,y] in displayProjection (default EPSG:4326)
to center the map at
 * **zoom** the zoomlevel as integer to zoom the map to
 * **box** an array with the lower left x, lower left y, upper right x,
upper right y to zoom the map to,
this will take precedent when conflicting with any of the above values
 * **projection** the projection the coordinates are in, default is
the displayProjection

>Returns: {position: [x,y], zoom: z(int), box: [llx,lly,urx,ury]}


The `.center()` method allows us to move to map to a specific zoom level,
specific position or a specific extent. We can specify the projection of the
coordinates to override the displayProjection. For instance you want to show
the coordinates in 4326, but you have a dataset in EPSG:28992
(dutch projection). We can also retrieve the current zoomlevel, position and
extent from the map. The coordinates are returned in displayProjection.


     var center = map.center(); //get the current zoom, position and extent
     map.center({zoom:4}); //zoom to zoomlevel 4
     map.center({position:[5,52]}); //pan to point 5,52
     map.center(box:[-180,-90,180,90]); //zoom to the box -180,-900,180,90
     //pan to point 125000,485000 in dutch projection
     map.center({position:[125000,485000],projection:'EPSG:28992'});
 */
    center: function (options) {
        var position;
        var mapProjection = new OpenLayers.Projection(this.projection);
        // Determine source projection
        var sourceProjection = null;
        var zoom;
        var box;
        if(options && options.projection) {
            sourceProjection = options.projection.CLASS_NAME ===
            'OpenLayers.Projection' ? options.projection :
            new OpenLayers.Projection(options.projection);
        } else {
            var displayProjection = this.displayProjection;
            if(!displayProjection) {
                // source == target
                sourceProjection = new OpenLayers.Projection('EPSG:4326');
            } else {
                sourceProjection = displayProjection.CLASS_NAME ===
            'OpenLayers.Projection' ? displayProjection :
            new OpenLayers.Projection(displayProjection);
            }
        }

        // Get the current position
        if (arguments.length===0) {
            position = this.olMap.getCenter();
            zoom = this.olMap.getZoom();
            box = this.olMap.getExtent();

            if (!mapProjection.equals(sourceProjection)) {
                position.transform(mapProjection, sourceProjection);
            }
            box.transform(mapProjection,sourceProjection);
            box = box!==null ? box.toArray() : [];
            return {
                position: [position.lon, position.lat],
                zoom: this.olMap.getZoom(),
                box: box
            };
        }

        // Zoom to the extent of the box
        if (options.box!==undefined) {
            box = new OpenLayers.Bounds(
                options.box[0], options.box[1],options.box[2], options.box[3]);
            if (!mapProjection.equals(sourceProjection)) {
                box.transform(sourceProjection,mapProjection);
            }
            this.olMap.zoomToExtent(box);

        }
        // Only zoom is given
        else if (options.position===undefined) {
            this.olMap.zoomTo(options.zoom);
        }
        // Position is given, zoom maybe as well
        else {
            position = new OpenLayers.LonLat(options.position[0],
                                             options.position[1]);
            if (!mapProjection.equals(sourceProjection)) {
                position.transform(sourceProjection, mapProjection);
            }
            // options.zoom might be undefined, so we are good to
            // pass it on
            this.olMap.setCenter(position, options.zoom);
        }
    },
    _updateSelectFeatureControl: function(layerIds) {
        var vectorLayers = [];
        var layersList = this.layersList;
        if (this.selectFeatureControl!==null) {
            this.selectFeatureControl.deactivate();
            this.selectFeatureControl.destroy();
        }
        $.each(layerIds, function() {
            vectorLayers.push(layersList[this].olLayer);
        });
        this.selectFeatureControl = new OpenLayers.Control.SelectFeature(
            vectorLayers);
        this.olMap.addControl(this.selectFeatureControl);
        this.selectFeatureControl.activate();
    },
    // This function got a bit too clever. The reason is, that jQuery's
    // bind() is overloaded with so many possible combinations of arguments.
    // And, of course, MapQuery wants to support them all
    // The essence of the function is to wrap the original callback into
    // the correct scope
    bind: function(types, data, fn) {
        var self = this;

        // A map of event/handle pairs, wrap each of them
        if(arguments.length===1) {
            var wrapped = {};
            $.each(types, function(type, fn) {
                wrapped[type] = function() {
                    return fn.apply(self, arguments);
                };
            });
            this.events.bind.apply(this.events, [wrapped]);
        }
        else {
            var args = [types];
            // Only callback given, but no data (types, fn), hence
            // `data` is the function
            if(arguments.length===2) {
                fn = data;
            }
            else {
                if (!$.isFunction(fn)) {
                    throw('bind: you might have a typo in the function name');
                }
                // Callback and data given (types, data, fn), hence include
                // the data in the argument list
                args.push(data);
            }

            args.push(function() {
                return fn.apply(self, arguments);
            });

            this.events.bind.apply(this.events, args);
        }

        //this.events.bind.call(this.events, types, function() {
        //    data.apply(self, arguments);
        //});
        //this.events.bind.call(this.events, types, function() {
        //    data.apply(self, arguments);
        //});

        //this.events.bind.apply(this.events, arguments);
        //this.events.bind.call(this.events, types, $.proxy(data, self));
        //this.events.bind.apply(this.events, arguments);//.bind(this);
        //this.events.bind.apply(this.events, $.proxy(arguments));//.bind(this);
        //this.events.bind.apply(this.events, $.proxy(arguments));//.bind(this);
        //this.events.bind(types, data, fn);//.bind(this);
        //this.events.bind.call(this.events, types, data, fn);//.bind(this);
        return this;
    },
/**
###*map*.`trigger(name [, parameters])`
_version added 0.2_
####**Description**: triggers an event on the map

 * **name** the name of the event
 * **parameters** additional parameters that will be passed on with the event

>Returns: map (MapQuery.Map)

To subscribe to the triggered events, you need to bind to the mapuuu.

     map.bind('myEvent', function(evt) {
         console.log('the values are: ' + evt.data[0] + ' and ' + evt.data[1])
     });
     map.trigger('myEvent', 'some', 'values');
*/
    trigger: function() {
        // There is no point in using trigger() insted of triggerHandler(), as
        // we don't fire native events
        this.events.triggerHandler.apply(this.events, arguments);
        return this;
    },
    // Basically a trigger that returns the return value of the last listener
    _triggerReturn: function() {
        return this.events.triggerHandler.apply(this.events, arguments);
    },
    destroy: function() {
        this.olMap.destroy();
        this.element.removeData('mapQuery');
    }
};
/**

---

#MapQuery.Layer

The MapQuery.Layer object. It is constructed with layer options object in the
map.`layers([options])` function or by passing a `layer:{options}` object in
the `mapQuery()` constructor. The Layer object is refered to as _layer_ in the
documentation.
 */
$.MapQuery.Layer = function(map, id, options) {

    var self = this;
    // apply default options that are not specific to a layer

    this.id = id;
    this.label = options.label || this.id;
    // a reference to the map object is needed as it stores e.g. the list
    // of all layers (and we need to keep track of it, if we delete a
    // layer)
    this.map = map;

    // true if this layer is a vector layer
    this.isVector = false;

    // to bind and trigger jQuery events
    this.events = $({});

    this.handlers = {
        // Triggers the jQuery events, after the OpenLayers events
        // happened without any further processing
        simple: function(data) {
            this.trigger(data.type);
        },
        // All OpenLayers events that are triggered by user interaction,
        // like clicking somewhere or selecting a feature, need to be
        // handled in a special way. Those OpenLayers events will then be
        // triggered by MapQuery as well
        // In case of the "featureselected" event, this means that the
        // logic of handling the event is completely within the event
        // handler. When ".select()" on a feature is called, it will just
        // trigger the OpenLayers "featureselected" event, whose handler
        // will then trigger the corresponding jQuery event.
        includeFeature: function(data) {
            var feature = new $.MapQuery.Feature(this, {olFeature:
                                                        data.feature});
            this.trigger(data.type, [feature]);
        },
        prependLayer: function(data) {
            this.trigger('layer' + data.type);
        }
    };


    // create the actual layer based on the options
    // Returns layer and final options for the layer (for later re-use,
    // e.g. zoomToMaxExtent).
    var res = $.MapQuery.Layer.types[options.type.toLowerCase()].call(
        this, options);
    this.olLayer = res.layer;
    this.options = res.options;

    // Some good documentation for the events is needed. Here is a short
    // description on how the current events compare to the OpenLayer
    // events on the layer:
    // - added, remove: not needed, there's addlayer and removelayer
    // - visibilitychanged: not needed, there's the changelayer event
    // - move, moveend: not needed as you get them from the map, not the layer
    // - loadstart, loadend: renamed to layerloadstart, layerloadend
    this.olLayer.events.on({
        scope: this,
        loadstart: this.handlers.prependLayer,
        loadend: this.handlers.prependLayer,
        featureselected: this.handlers.includeFeature,
        featureunselected: this.handlers.includeFeature,
        featureremoved: this.handlers.includeFeature
    });

    // To be able to retreive the MapQuery layer, when we only have the
    // OpenLayers layer available. For example on the layeradded event.
    // NOTE vmx 2012-02-26: Any nicer solution is welcome
    this.olLayer.mapQueryId = this.id;
};

$.MapQuery.Layer.prototype = {
/**
###*layer*.`down([delta])`
_version added 0.1_
####**Description**: move the layer down in the layer stack of the map

 * **delta** the amount of layers the layer has to move down in the layer
stack (default 1)

>Returns layer (MapQuery.Layer)


The `.down()` method is a shortcut method for `.position(pos)` which makes
it easier to move a layer down in the layerstack relative to its current
position. It takes an integer and will try to move the layer down the number of
places given. If delta is bigger than the current position in the stack, it
will put the layer at the bottom.


     layer.down();  //move layer 1 place down
     layer.down(3); //move layer 3 places down

 */
    down: function(delta) {
        delta = delta || 1;
        var pos = this.position();
        pos = pos - delta;
        if (pos<0) {pos = 0;}
        this.position(pos);
        return this;
    },
    // NOTE vmx: this would be pretty cool, but it's not easily possible
    // you could use $.each($.geojq.layer())) instead, this is for pure
    // convenience.
    each: function () {},
/**
###*layer*.`remove()`
_version added 0.2_
####**Description**: remove the layer from the map

>Returns: map (MapQuery.Map) or false


The `.remove()` method allows us to remove a layer from the map.
It returns the `map` object if the layer was removed, or `false` if the
removal was prevented in the preremovelayer event.

     var id = layer.remove(); //remove this layer


 */
    remove: function() {
        // remove references to this layer that are stored in the
        // map object
        return this.map._removeLayer(this.id);
    },
/**
###*layer*.`position([position])`
_version added 0.1_
####**Description**: get/set the `position` of the layer in the layer
stack of the map

 * **position** an integer setting the new position of the layer in the layer stack

>Returns: position (integer) _or_ layer (MapQuery.Layer)


The `.position()` method allows us to change the position of the layer in the
layer stack. It will take into account the hidden baselayer that is used by
OpenLayers. The lowest layer is position 0. If no position is given, it will
return the current postion.


     var pos =  layer.position(); //get position of layer in the layer stack
     layer.position(2); //put layer on position 2 in the layer stack

 */
    position: function(pos) {
        if (pos===undefined) {
            return this.map.olMap.getLayerIndex(this.olLayer)-1;
        }
        else {
            this.map.olMap.setLayerIndex(this.olLayer, pos+1);
            this.trigger('changelayer', ['position']);
            return this;
        }
    },
/**
###*layer*.`up([delta])`
_version added 0.1_
####**Description**: move the layer up in the layer stack of the map

 * **delta** the amount of layers the layer has to move up in the layer
stack (default 1)

>Returns: layer (MapQuery.Layer)


The `.up()` method is a shortcut method for `.position(pos)` which makes
it easier to move a layer up in the layerstack relative to its current
position. It takes an integer and will move the layer up the number of places
given.



     layer.up();  //move layer 1 place up
     layer.up(3); //move layer 3 places up
*/
    up: function(delta) {
        delta = delta || 1;
        var pos = this.position();
        pos = pos + delta;
        this.position(pos);
        return this;
    },
/**
###*layer*.`visible([visible])`
_version added 0.1_
####**Description**: get/set the `visible` state of the layer

 * **visible** a boolean setting the visibility of the layer

>Returns: visible (boolean)


The `.visible()` method allows us to change the visibility of the layer.
If no visible is given, it will return the current visibility.


     var vis =  layer.visible(); //get the visibility of layer
     layer.visible(true); //set visibility of layer to true

 */
    visible: function(vis) {
        if (vis===undefined) {
            return this.olLayer.getVisibility();
        }
        else {
            this.olLayer.setVisibility(vis);
            this.trigger('changelayer', ['visibility']);
            return this;
        }
    },
/**
###*layer*.`opacity([opacity])`
_version added 0.1_
####**Description**: get/set the `opacity` of the layer

 * **position** a float [0-1] setting the opacity of the layer

>Returns: opacity (float) _or_ layer (MapQuery.Layer)


The `.opacity()` method allows us to change the opacity of the layer.
If no opacity is given, it will return the current opacity.


     var opac =  layer.opacity(); //get opacity of layer
     layer.opacity(0.7); //set opacity of layer to 0.7

 */
    opacity: function(opac) {
        if (opac===undefined) {
            // this.olLayer.opacity can be null if never
            // set so return the visibility
            var value = this.olLayer.opacity ?
            this.olLayer.opacity : this.olLayer.getVisibility();
            return value;
        }
        else {
            this.olLayer.setOpacity(opac);
            this.trigger('changelayer', ['opacity']);
            return this;
        }
    },
    // every event gets the layer passed in
    bind: function() {
        // Use the same bind function as for the map
        this.map.bind.apply(this, arguments);
        return this;
    },
/**
###*layer*.`trigger(name [, parameters])`
_version added 0.2_
####**Description**: triggers an event on the layer and map

 * **name** the name of the event
 * **parameters** additional parameters that will be passed on with the event

>Returns: layer (MapQuery.Layer)

The events get triggered on the layer as well as on the map. To subscribe to
the triggered events, you can either bind to the layer or the map. If bound
to the map, the second argument in the bind will be the layer the event
came from

     layer.bind('myEvent', function(evt) {
         console.log('the values are: ' + evt.data[0] + ' and ' + evt.data[1])
     });
     map.bind('myEvent', function(evt, layer) {
         console.log('the values are: ' + evt.data[0] + ' and ' + evt.data[1])
     });
     layer.trigger('myEvent', 'some', 'values');
*/
    trigger: function() {
        var args = Array.prototype.slice.call(arguments);
        this.events.triggerHandler.apply(this.events, args);

        this._addLayerToArgs(args);

        this.map.events.triggerHandler.apply(this.map.events, args);
        return this;
    },
    // Basically a trigger that returns the return value of the last listener
    _triggerReturn: function() {
        var args = Array.prototype.slice.call(arguments);
        var ret = this.events.triggerHandler.apply(this.events, args);
        if (ret !== undefined) {
            return ret;
        }

        this._addLayerToArgs(args);
        return this.events.triggerHandler.apply(this.map.events, args);
    },
    // Adds the current layer to the event arguments, so that it is included
    // in the event on the map
    _addLayerToArgs: function(args) {
        // Add layer for the map event
        if (args.length===1) {
            args.push([this]);
        }
        else {
            args[1].unshift(this);
        }
    },
/**
###*layer*.`features([options])`
_version added 0.2.0_
####**Description**: get/set the features of a (vector) layer

**options** an object of key-value pairs with options to create one or
more features

>Returns: [features] (array of MapQuery.Feature)


The `.features()` method allows us to attach features to a mapQuery layer
object. It takes an options object with feature options. To add multiple
features, create an array of feature options objects. If an options object
is given, it will return the resulting feature(s). We can also use it to
retrieve all features currently attached to the layer.


     // add an (vector) json layer to the map
     var jsonlayer = map.layers({type:'json'});
     // add a feature to the layer
     jsonlayer.features({geometry: {type: "Point", coordinates: [5.3, 7.4]}});
     // get all features of a layer (sorted with first added feature at the beginning
     var features = jsonlayer.features();
*/
    features: function(options) {
        var self = this;
        switch(arguments.length) {
        // return all features
        case 0:
            return this._allFeatures();
        // add new feature(s)
        case 1:
            if (!$.isArray(options)) {
                return this._addFeature(options);
            }
            else {
                return $.map(options, function(feature) {
                    return self._addFeature(feature);
                });
            }
            break;
        default:
            throw('wrong argument number');
        }
    },
    _allFeatures: function() {
        var layer = this;
        return $.map(layer.olLayer.features, function(feature) {
            return new $.MapQuery.Feature(layer, {olFeature: feature});
        });
    },
    _addFeature: function(options) {
        var feature = new $.MapQuery.Feature(this, options);
        // NOTE vmx 2012-04-19: Not sure if this is a good idea, or if it would
        //     be better to include `options` with the preaddfeature event
        if (this._triggerReturn('preaddfeature', [feature])===false) {
            return false;
        }
        this.olLayer.addFeatures(feature.olFeature);
        this.trigger('addfeature', [feature]);
        return feature;
    }
};

/**
#MapQuery.Feature

The MapQuery.Feature object. It is constructed with a feature options object
in the layer.`features([options])` function. The Feautre object is refered to
as _feature_ in the documentation.

TODO vmx 20110905: Support other geometry types than GeoJSON
options:
 * geometry: A GeoJSON geometry
 * properties: Properties for the feature
*/
// Not in the pulic API docs: You can pass in as options:
//  * olFeature: This will wrap the olFeature in a MapQuery feature
$.MapQuery.Feature = function(layer, options) {
    // The ID is the
    this._id = layer.map._createId();
    this.layer = layer;

    // Feature already exists on the layer, it just needs to be wrapped
    // to an MapQuery feature
    if (options.olFeature) {
        this.olFeature = options.olFeature;
    }
    else {
        // XXX vmx 20110905: Different feature types might make sense:
        //     (Geo)JSON, KML, WKT
        // vmx 2012-04-14: I changed my mind quite some time ago. We should onlu
        //     support GeoJSON and let the user easily transfrom their format
        //     (e.g. KML) to GeoJSON, before they add a feature to the layer
        var GeoJSON = new OpenLayers.Format.GeoJSON();
        var geometry = GeoJSON.parseGeometry(options.geometry);
        geometry.transform(
            new OpenLayers.Projection(this.layer.map.displaProjection),
            new OpenLayers.Projection(this.layer.map.projection));

        this.olFeature = new OpenLayers.Feature.Vector(geometry,
            options.properties);
    }

    // Modify the features to be more practical
    // e.g. copy properties that should be easily accessed from the
    // outside, out of the olLayer and to the feature level
    this.properties = $.extend(true, {}, this.olFeature.attributes);
    this.geometry = $.parseJSON(
        new OpenLayers.Format.GeoJSON().write(this.olFeature.geometry));

    return this;
};

$.MapQuery.Feature.prototype = {
/**
###*feature*.`remove()`
_version added 0.2.0_
####**Description**: remove the feature from the layer

>Returns: layer (layer) or false


The `.remove()` method allows us to remove a feature from the layer.
It returns the `layer` object if the feature was removed, or `false` if the
removal was prevented in the preremovefeature event.

     // add a feature to a layer
     var feature = layer.features({geometry: {type: "Point", coordinates: [5.3, 7.4]}});
     // remove the feature again
     feature.remove();
*/
    remove: function() {
        if (this.layer._triggerReturn('preremovefeature', [this])===false) {
            return false;
        }
        this.layer.olLayer.removeFeatures(this.olFeature);
        // The `removefeature` event is triggered by an OpenLayes event handler
        return this.layer;
    },
/**
###*feature*.`select(exclusive)`
_version added 0.2.0_
####**Description**: select a feature

**exclusive** (boolean, default: true) True means that all other features get
deselectd

>Returns: layer (layer)


The `.select()` method allows us to select a feature from the layer.
A `featureselected` will be fired.

     // add a feature to a layer
     var feature = layer.features({geometry: {type: "Point", coordinates: [5.3, 7.4]}});
     // select the feature again
     feature.select();
*/
    select: function(exclusive) {
        if (exclusive===undefined || exclusive===true) {
            this.layer.map.selectFeatureControl.unselectAll();
        }
        this.layer.map.selectFeatureControl.select(this.olFeature);
    },
/**
###*feature*.`unselect()`
_version added 0.2.0_
####**Description**: unselect a feature

>Returns: layer (layer)


The `.unselect()` method allows us to unselect a feature from the layer.
A `featureunselected` will be fired.

     // add a feature to a layer
     var feature = layer.features({geometry: {type: "Point", coordinates: [5.3, 7.4]}});
     // select the feature
     feature.select();
     // unselect the feature again
     feature.unselect();
*/
    unselect: function() {
        this.layer.map.selectFeatureControl.unselect(this.olFeature);
    }
};

$.fn.mapQuery = function(options) {
    return this.each(function() {
        var instance = $.data(this, 'mapQuery');
        if (!instance) {
            $.data(this, 'mapQuery', new $.MapQuery.Map($(this), options));
        }
    });
};

$.extend($.MapQuery.Layer, {
    types: {
/**
###*layer* `{type:bing}`
_version added 0.1_
####**Description**: create a Bing maps layer

 * **view** a string ['road','hybrid','satellite'] to define which Bing maps
layer to use (default road)   
 * **key** Bing Maps API key for your application. Get you own at
http://bingmapsportal.com/ 
 * **label** string with the name of the layer


      layers:[{
            type:'bing',      //create a bing maps layer
            view:'satellite', //use the bing satellite layer
            key:'ArAGGPJ16xm0RX' //the Bing maps API key
            }]

*/
        bing: function(options) {
            var o = $.extend(true, {}, $.fn.mapQuery.defaults.layer.all,
                $.fn.mapQuery.defaults.layer.bing,
                options);
            var view = o.view;
            switch(view){
                case 'road':
                    view = 'Road'; break;
                case 'hybrid':
                    view = 'AerialWithLabels'; break;
                case 'satellite':
                    view = 'Aerial'; break;
            }
            return {
                layer: new OpenLayers.Layer.Bing({type:view,key:o.key}),
                options: o
            };
        },
        //Not sure this one is worth pursuing works with ecwp:// & jpip:// urls
        //See ../lib/NCSOpenLayersECWP.js
        ecwp: function(options) {
            var o = $.extend(true, {}, $.fn.mapQuery.defaults.layer.all,
                    $.fn.mapQuery.defaults.layer.raster,
                    options);
            return {
                layer: new OpenLayers.Layer.ECWP(o.label, o.url, o),
                options: o
            };
        },
/**
###*layer* `{type:google}`
_version added 0.1_
####**Description**: create a Google maps layer

 * **view** a string ['road','hybrid','satellite'] to define which Google maps
layer to use (default road)
 * **label** string with the name of the layer


*Note* you need to include the google maps v3 API in your application by adding
`<script src="http://maps.google.com/maps/api/js?v=3.5&amp;sensor=false"type="text/javascript"></script>`


      layers:[{
            type:'google',      //create a google maps layer
            view:'hybrid' //use the google hybridlayer
            }]

*/
        google: function(options) {
            var o = $.extend(true, {}, $.fn.mapQuery.defaults.layer.all,
                    $.fn.mapQuery.defaults.layer.google,
                    options);
            var view = o.view;
            switch(view){
                case 'road':
                    view = google.maps.MapTypeId.ROADMAP; break;
                case 'terrain':
                    view = google.maps.MapTypeId.TERRAIN; break;
                case 'hybrid':
                    view = google.maps.MapTypeId.HYBRID; break;
                case 'satellite':
                    view = google.maps.MapTypeId.SATELLITE; break;
            }
            return {
                layer: new OpenLayers.Layer.Google({type:view}),
                options: o
            };
        },
/**
###*layer* `{type:vector}`
_version added 0.1_
####**Description**: create a vector layer

 * **label** string with the name of the layer


      layers:[{
            type:'vector'     //create a vector layer
            }]

*/
        vector: function(options) {
            var o = $.extend(true, {}, $.fn.mapQuery.defaults.layer.all,
                    $.fn.mapQuery.defaults.layer.vector,
                    options);
            this.isVector = true;
            return {
                layer: new OpenLayers.Layer.Vector(o.label),
                options: o
            };
        },
/**
###*layer* `{type:json}`
_version added 0.1_
####**Description**: create a JSON layer

 * **url** a string pointing to the location of the JSON data
 * **strategies** a string ['bbox','cluster','filter','fixed','paging','refresh','save']
stating which update strategy should be used (default fixed)
(see also http://dev.openlayers.org/apidocs/files/OpenLayers/Strategy-js.html)
 * **projection** a string with the projection of the JSON data (default EPSG:4326)
 * **styleMap** {object} the style to be used to render the JSON data    
 * **label** string with the name of the layer


      layers:[{
            type: 'JSON',
            url: 'data/reservate.json',
            label: 'reservate'
            }]

*/
        json: function(options) {
            var o = $.extend(true, {}, $.fn.mapQuery.defaults.layer.all,
                $.fn.mapQuery.defaults.layer.vector,
                options);
            this.isVector = true;
            var strategies = [];
            for (var i in o.strategies) {
                if(o.strategies.hasOwnProperty(i)) {
                    switch(o.strategies[i].toLowerCase()) {
                    case 'bbox':
                        strategies.push(new OpenLayers.Strategy.BBOX());
                   break;
                    case 'cluster':
                        strategies.push(new OpenLayers.Strategy.Cluster());
                   break;
                    case 'filter':
                        strategies.push(new OpenLayers.Strategy.Filter());
                   break;
                    case 'fixed':
                        strategies.push(new OpenLayers.Strategy.Fixed());
                   break;
                    case 'paging':
                        strategies.push(new OpenLayers.Strategy.Paging());
                   break;
                    case 'refresh':
                        strategies.push(new OpenLayers.Strategy.Refresh());
                   break;
                    case 'save':
                        strategies.push(new OpenLayers.Strategy.Save());
                   break;
                    }
                }
            }
            var protocol;

            var params = {
                strategies: strategies,
                projection: o.projection || 'EPSG:4326',
                styleMap: o.styleMap
            };

            if (o.url) {
                // only use JSONP if we use http(s)
                if (o.url.match(/^https?:\/\//)!==null &&
                    !$.MapQuery.util.sameOrigin(o.url)) {
                    protocol = 'Script';
                }
                else {
                    protocol = 'HTTP';
                }
                params.protocol = new OpenLayers.Protocol[protocol]({
                    url: o.url,
                    format: new OpenLayers.Format.GeoJSON()
                });
            };

            var layer = new OpenLayers.Layer.Vector(o.label, params);
            return {
                layer: layer,
                options: o
            };
        },
/**
###*layer* `{type:osm}`
_version added 0.1_
####**Description**: create an OpenStreetMap layer

 
 * **label** string with the name of the layer   
 * **url** A single URL (string) or an array of URLs to OSM-like server like 
Cloudmade   
 * **attribution** A string to put some attribution on the map

      layers:[{
        type: 'osm',
        url: [
          'http://a.tile.cloudmade.com/<yourapikey>/999/256/${z}/${x}/${y}.png',
          'http://b.tile.cloudmade.com/<yourapikey>/999/256/${z}/${x}/${y}.png',
          'http://c.tile.cloudmade.com/<yourapikey>/999/256/${z}/${x}/${y}.png'
        ],
        attribution: "Data &copy; 2009 <a href='http://openstreetmap.org/'>
          OpenStreetMap</a>. Rendering &copy; 2009 
          <a href='http://cloudmade.com'>CloudMade</a>."
        }]

*/
        osm: function(options) {
            var o = $.extend(true, {}, $.fn.mapQuery.defaults.layer.all,
                $.fn.mapQuery.defaults.layer.osm,
                options);
            var label = options.label || undefined;
            var url = options.url || undefined;
            return {
                layer: new OpenLayers.Layer.OSM(label, url, o),
                options: o
            };
        },
/**
###*layer* `{type:tms}`
_version added 0.1_
####**Description**: create an OpenStreetMap layer

 
 * **label** string with the name of the layer   
 * **url** A single URL (string) or an array of URLs to the TMS end point
 * **layer** The identifier for the <TileMap> as advertised by the service. 
 For example, if the service advertises a <TileMap> with ‘href=”http://tms.osgeo.org/1.0.0/vmap0”’, 
 the layer property would be set to “vmap0”.
 * **format** The image format (default png)

      layers:[{
        type: 'tms',
        url: 'http://tilecache.osgeo.org/wms-c/Basic.py/',
        layer: 'basic'
        }]

*/        
        tms: function(options) {
            var o = $.extend(true, {}, $.fn.mapQuery.defaults.layer.all,
                $.fn.mapQuery.defaults.layer.tms,
                options);
            var label = options.label || undefined;
            var url = options.url || undefined;
            var params = {
                layername: o.layer,
                type: o.format
            };
            return {
                layer: new OpenLayers.Layer.TMS(label, url, params),
                options: o
            };
        },
/**
###*layer* `{type:wms}`
_version added 0.1_
####**Description**: create a WMS layer

 * **url** a string pointing to the location of the WMS service
 * **layers** a string with the name of the WMS layer(s)
 * **format** a string with format of the WMS image (default image/jpeg)
 * **transparent** a boolean for requesting images with transparency
 * **label** string with the name of the layer
 * **wms_parameters** an hashtable of extra GetMap query string parameters and parameter values


      layers:[{
            type:'wms',
            url:'http://vmap0.tiles.osgeo.org/wms/vmap0',
            layers:'basic'
            }]

*/
        wms: function(options) {
            var o = $.extend(true, {}, $.fn.mapQuery.defaults.layer.all,
                    $.fn.mapQuery.defaults.layer.raster,
                    options);
            var params = {
                layers: o.layers,
                transparent: o.transparent,
                format: o.format
            };
            if(typeof o.wms_parameters != "undefined"){
                params = $.extend(params, o.wms_parameters);
            }
            return {
                layer: new OpenLayers.Layer.WMS(o.label, o.url, params, o),
                options: o
            };
        },
//TODO complete this documentation
/**
###*layer* `{type:wmts}`
_version added 0.1_
####**Description**: create a WMTS (tiling) layer

 * **url** a string pointing to the location of the WMTS service
 * **layer** a string with the name of the WMTS layer
 * **matrixSet** a string with one of the advertised matrix set identifiers
 * **style** a string with one of the advertised layer styles    
 * **label** string with the name of the layer


      layers:[{
            type:'wmts'
            }]

*/
        wmts: function(options) {
            var o = $.extend(true, {}, $.fn.mapQuery.defaults.layer.all,
                    $.fn.mapQuery.defaults.layer.wmts);
            //smo 20110614 the maxExtent is set here with OpenLayers.Bounds
            if (options.sphericalMercator===true) {
                $.extend(true, o, {
                    maxExtent: new OpenLayers.Bounds(
                        -128 * 156543.0339, -128 * 156543.0339,
                        128 * 156543.0339, 128 * 156543.0339),
                    maxResolution: 156543.0339,
                    numZoomLevels: 19,
                    projection: 'EPSG:900913',
                    units: 'm'
                });
            }
            $.extend(true, o, options);
            // use by default all options that were passed in for the final
            // openlayers layer consrtuctor
            var params = $.extend(true, {}, o);

            // remove trailing slash
            if (params.url.charAt(params.url.length-1)==='/') {
                params.url = params.url.slice(0, params.url.length-1);
            }
            // if no options that influence the URL where set, extract them
            // from the given URL
            if (o.layer===undefined && o.matrixSet===undefined &&
                    o.style===undefined) {
                var url = $.MapQuery.util.parseUri(params.url);
                var urlParts = url.path.split('/');
                var wmtsPath = urlParts.slice(urlParts.length-3);
                params.url = url.protocol ? url.protocol + '//' : '';
                params.url += url.authority +
                    // remove WMTS version (1.0.0) as well
                    urlParts.slice(0, urlParts.length-4).join('/');
                params.layer = wmtsPath[0];
                params.style = wmtsPath[1];
                params.matrixSet = wmtsPath[2];
            }
            return {
                layer: new OpenLayers.Layer.WMTS(params),
                options: o
            };
        }
    }
});

// default options for the map and layers
$.fn.mapQuery.defaults = {
    // The controls for the map are per instance, therefore it need to
    // be an function that can be initiated per instance
    map: function() {
        return {
            // Remove quirky moveTo behavior, probably not a good idea in the
            // long run
            allOverlays: true,
            controls: [
                // Since OL2.11 the Navigation control includes touch navigation as well
                new OpenLayers.Control.Navigation({
                    documentDrag: true,
                    dragPanOptions: {
                        interval: 1,
                        enableKinetic: true
                    }
                }),
                new OpenLayers.Control.ArgParser(),
                new OpenLayers.Control.Attribution(),
                new OpenLayers.Control.KeyboardDefaults()
            ],
            format: 'image/png',
            maxExtent: [-128*156543.0339,
                -128*156543.0339,
                128*156543.0339,
                128*156543.0339],
            maxResolution: 156543.0339,
            numZoomLevels: 19,
            projection: 'EPSG:900913',
            displayProjection: 'EPSG:4326',
            zoomToMaxExtent: true,
            units: 'm'
        };
    },
    layer: {
        all: {
            isBaseLayer: false,
        //in general it is kinda pointless to load tiles outside a maxextent
            displayOutsideMaxExtent: false
        },
        bing: {
            transitionEffect: 'resize',
            view: 'road',
            sphericalMercator: true
        },
        google: {
            transitionEffect: 'resize',
            view: 'road',
            sphericalMercator: true
        },
        osm: {
            transitionEffect: 'resize',
            sphericalMercator: true
        },
        tms: {
            transitionEffect: 'resize',
            format: 'png'
        },
        raster: {
            // options for raster layers
            transparent: true
        },
        vector: {
            // options for vector layers
            strategies: ['bbox']
        },
        wmts: {
            format: 'image/jpeg',
            requestEncoding: 'REST',
            sphericalMercator: false
        }
    }
};

// Some utility functions

$.MapQuery.util = {};
// http://blog.stevenlevithan.com/archives/parseuri (2010-12-18)
// parseUri 1.2.2
// (c) Steven Levithan <stevenlevithan.com>
// MIT License
// Edited to include the colon in the protocol, just like it is
// with window.location.protocol
$.MapQuery.util.parseUri = function (str) {
    var o = $.MapQuery.util.parseUri.options,
        m = o.parser[o.strictMode ? "strict" : "loose"].exec(str),
        uri = {},
        i = 14;

    while (i--) {uri[o.key[i]] = m[i] || "";}

    uri[o.q.name] = {};
    uri[o.key[12]].replace(o.q.parser, function ($0, $1, $2) {
        if ($1) {uri[o.q.name][$1] = $2;}
    });

    return uri;
};
$.MapQuery.util.parseUri.options = {
    strictMode: false,
    key: ["source", "protocol", "authority", "userInfo", "user",
            "password", "host", "port", "relative", "path", "directory",
            "file", "query", "anchor"],
    q: {
        name: "queryKey",
        parser: /(?:^|&)([^&=]*)=?([^&]*)/g
    },
    parser: {
        strict: /^(?:([^:\/?#]+:))?(?:\/\/((?:(([^:@]*)(?::([^:@]*))?)?@)?([^:\/?#]*)(?::(\d*))?))?((((?:[^?#\/]*\/)*)([^?#]*))(?:\?([^#]*))?(?:#(.*))?)/,
        loose:  /^(?:(?![^:@]+:[^:@\/]*@)([^:\/?#.]+:))?(?:\/\/)?((?:(([^:@]*)(?::([^:@]*))?)?@)?([^:\/?#]*)(?::(\d*))?)(((\/(?:[^?#](?![^?#\/]*\.[^?#\/.]+(?:[?#]|$)))*\/?)?([^?#\/]*))(?:\?([^#]*))?(?:#(.*))?)/
        }
};
// Checks whether a URL conforms to the same origin policy or not
$.MapQuery.util.sameOrigin = function(url) {
    var parsed = $.MapQuery.util.parseUri(url);
    parsed.protocol = parsed.protocol || 'file:';
    parsed.port = parsed.port || "80";

    var current = {
        domain: document.domain,
        port: window.location.port,
        protocol: window.location.protocol
    };
    current.port = current.port || "80";

    return parsed.protocol===current.protocol &&
        parsed.port===current.port &&
        // the current domain is a suffix of the parsed domain
        parsed.host.match(current.domain + '$')!==null;
};
})(jQuery);
