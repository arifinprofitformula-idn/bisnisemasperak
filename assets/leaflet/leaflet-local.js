/* Leaflet JS Local Fallback - Enhanced Version */
(function() {
  'use strict';
  
  // Cek apakah Leaflet sudah ada
  if (window.L) return;
  
  // Minimal Leaflet implementation
  var L = window.L = {};

  // Simple Class implementation (defined early)
  L.Class = function() {};
  L.Class.extend = function(props) {
    var NewClass = function() {
      if (this.initialize) {
        this.initialize.apply(this, arguments);
      }
    };
    var parentProto = NewClass.__super__ = this.prototype;
    var proto = Object.create(parentProto);
    proto.constructor = NewClass;
    NewClass.prototype = proto;
    for (var i in props) { proto[i] = props[i]; }
    return NewClass;
  };
  
  // Point class
  L.Point = function(x, y) {
    this.x = x;
    this.y = y;
  };
  
  L.Point.prototype = {
    add: function(point) {
      return new L.Point(this.x + point.x, this.y + point.y);
    },
    
    subtract: function(point) {
      return new L.Point(this.x - point.x, this.y - point.y);
    },
    
    divideBy: function(num) {
      return new L.Point(this.x / num, this.y / num);
    },
    
    multiplyBy: function(num) {
      return new L.Point(this.x * num, this.y * num);
    },
    
    distanceTo: function(point) {
      var x = point.x - this.x,
          y = point.y - this.y;
      return Math.sqrt(x * x + y * y);
    },
    
    clone: function() {
      return new L.Point(this.x, this.y);
    }
  };
  
  // LatLng class
  L.LatLng = function(lat, lng) {
    this.lat = parseFloat(lat);
    this.lng = parseFloat(lng);
  };
  
  L.LatLng.prototype = {
    equals: function(other) {
      return Math.abs(this.lat - other.lat) < 0.000001 && 
             Math.abs(this.lng - other.lng) < 0.000001;
    }
  };
  
  // Bounds class
  L.Bounds = function(a, b) {
    if (!a) return;
    var points = b ? [a, b] : a;
    for (var i = 0; i < points.length; i++) {
      this.extend(points[i]);
    }
  };
  
  L.Bounds.prototype = {
    extend: function(point) {
      if (!this.min && !this.max) {
        this.min = new L.Point(point.x, point.y);
        this.max = new L.Point(point.x, point.y);
      } else {
        this.min.x = Math.min(point.x, this.min.x);
        this.max.x = Math.max(point.x, this.max.x);
        this.min.y = Math.min(point.y, this.min.y);
        this.max.y = Math.max(point.y, this.max.y);
      }
      return this;
    },
    
    getCenter: function() {
      return new L.Point(
        (this.min.x + this.max.x) / 2,
        (this.min.y + this.max.y) / 2
      );
    }
  };
  
  // LatLngBounds class
  L.LatLngBounds = function(a, b) {
    if (!a) return;
    var latlngs = b ? [a, b] : a;
    for (var i = 0; i < latlngs.length; i++) {
      this.extend(latlngs[i]);
    }
  };
  
  L.LatLngBounds.prototype = {
    extend: function(latlng) {
      if (!this._southWest && !this._northEast) {
        this._southWest = new L.LatLng(latlng.lat, latlng.lng);
        this._northEast = new L.LatLng(latlng.lat, latlng.lng);
      } else {
        this._southWest.lat = Math.min(latlng.lat, this._southWest.lat);
        this._southWest.lng = Math.min(latlng.lng, this._southWest.lng);
        this._northEast.lat = Math.max(latlng.lat, this._northEast.lat);
        this._northEast.lng = Math.max(latlng.lng, this._northEast.lng);
      }
      return this;
    },
    
    getCenter: function() {
      return new L.LatLng(
        (this._southWest.lat + this._northEast.lat) / 2,
        (this._southWest.lng + this._northEast.lng) / 2
      );
    }
  };
  
  // Transformation class
  L.Transformation = function(a, b, c, d) {
    this._a = a;
    this._b = b;
    this._c = c;
    this._d = d;
  };
  
  L.Transformation.prototype = {
    transform: function(point, scale) {
      return this._transform(point.clone(), scale);
    },
    
    _transform: function(point, scale) {
      scale = scale || 1;
      point.x = scale * (this._a * point.x + this._b);
      point.y = scale * (this._c * point.y + this._d);
      return point;
    },
    
    untransform: function(point, scale) {
      scale = scale || 1;
      return new L.Point(
        (point.x / scale - this._b) / this._a,
        (point.y / scale - this._d) / this._c
      );
    }
  };
  
  // CRS class
  L.CRS = {
    latLngToPoint: function(latlng, zoom) {
      var projectedPoint = this.projection.project(latlng);
      var scale = this.scale(zoom);
      return this.transformation.transform(projectedPoint, scale);
    },
    
    pointToLatLng: function(point, zoom) {
      var scale = this.scale(zoom);
      var untransformedPoint = this.transformation.untransform(point, scale);
      return this.projection.unproject(untransformedPoint);
    },
    
    scale: function(zoom) {
      return 256 * Math.pow(2, zoom);
    }
  };
  
  // SphericalMercator projection
  var SphericalMercator = {
    R: 6378137,
    MAX_LATITUDE: 85.0511287798,
    
    project: function(latlng) {
      var d = Math.PI / 180,
          max = this.MAX_LATITUDE,
          lat = Math.max(-max, Math.min(max, latlng.lat)),
          sin = Math.sin(lat * d);
      
      return new L.Point(
        this.R * latlng.lng * d,
        this.R * Math.log((1 + sin) / (1 - sin)) / 2
      );
    },
    
    unproject: function(point) {
      var d = 180 / Math.PI;
      return new L.LatLng(
        (2 * Math.atan(Math.exp(point.y / this.R)) - (Math.PI / 2)) * d,
        point.x * d / this.R
      );
    }
  };
  
  // EPSG3857 CRS
  L.CRS.EPSG3857 = L.extend({}, L.CRS, {
    projection: SphericalMercator,
    transformation: (function() {
      var scale = 0.5 / (Math.PI * SphericalMercator.R);
      return new L.Transformation(scale, 0.5, -scale, 0.5);
    })()
  });
  
  // Icon class
  L.Icon = function(options) {
    L.setOptions(this, options);
  };
  
  L.Icon.prototype = {
    createIcon: function() {
      return this._createIcon('icon');
    },
    
    _createIcon: function(name) {
      var src = this._getIconUrl(name);
      if (!src) {
        if (name === 'icon') {
          throw new Error('iconUrl not set in Icon options');
        }
        return null;
      }
      
      var img = document.createElement('img');
      img.src = src;
      return img;
    },
    
    _getIconUrl: function(name) {
      return this.options[name + 'Url'];
    }
  };
  
  L.Icon.Default = L.Icon.extend({
    options: {
      iconSize: [25, 41],
      iconAnchor: [12, 41],
      popupAnchor: [1, -34],
      shadowSize: [41, 41]
    },
    
    _getIconUrl: function(name) {
      if (!L.Icon.Default.imagePath) {
        L.Icon.Default.imagePath = this._detectIconPath();
      }
      return L.Icon.Default.imagePath + '/images/marker-' + name + '.png';
    },
    
    _detectIconPath: function() {
      var path = '';
      var scripts = document.getElementsByTagName('script');
      for (var i = 0; i < scripts.length; i++) {
        var src = scripts[i].src;
        if (src && src.indexOf('leaflet') !== -1) {
          path = src.split('?')[0];
          path = path.substring(0, path.lastIndexOf('/') + 1);
          break;
        }
      }
      return path;
    }
  });
  
  // DivIcon class
  L.DivIcon = L.Icon.extend({
    options: {
      iconSize: [12, 12],
      className: 'leaflet-div-icon',
      html: false
    },
    
    createIcon: function() {
      var div = document.createElement('div');
      var options = this.options;
      
      if (options.html === false) {
        div.innerHTML = '';
      } else {
        div.innerHTML = options.html;
      }
      
      if (options.bgPos) {
        div.style.backgroundPosition = (-options.bgPos.x) + 'px ' + (-options.bgPos.y) + 'px';
      }
      
      this._setIconStyles(div, 'icon');
      return div;
    },
    
    _setIconStyles: function(img, name) {
      var options = this.options;
      var size = options.iconSize;
      var anchor = options.iconAnchor;
      
      if (typeof size === 'number') {
        size = [size, size];
      }
      
      if (typeof anchor === 'number') {
        anchor = [anchor, anchor];
      }
      
      img.className = options.className + ' leaflet-marker-' + name;
      
      if (size) {
        img.style.width = size[0] + 'px';
        img.style.height = size[1] + 'px';
      }
      
      if (anchor) {
        img.style.marginLeft = (-anchor[0]) + 'px';
        img.style.marginTop = (-anchor[1]) + 'px';
      }
    }
  };
  
  // Utility functions
  L.setOptions = function(obj, options) {
    if (!Object.prototype.hasOwnProperty.call(obj, 'options')) {
      obj.options = obj.options ? L.extend({}, obj.options) : {};
    }
    for (var i in options) {
      obj.options[i] = options[i];
    }
    return obj.options;
  };
  
  L.extend = function(dest) {
    var i, j, len, src;
    for (j = 1, len = arguments.length; j < len; j++) {
      src = arguments[j];
      for (i in src) {
        dest[i] = src[i];
      }
    }
    return dest;
  };
  
  L.bind = function(fn, obj) {
    var slice = Array.prototype.slice;
    if (fn.bind) {
      return fn.bind.apply(fn, slice.call(arguments, 1));
    }
    var args = slice.call(arguments, 2);
    return function() {
      return fn.apply(obj, args.length ? args.concat(slice.call(arguments)) : arguments);
    };
  };
  
  // Event system
  L.Evented = L.Class.extend({
    on: function(types, fn, context) {
      if (typeof types === 'object') {
        for (var type in types) {
          this._on(type, types[type], fn);
        }
      } else {
        types = this._splitWords(types);
        for (var i = 0, len = types.length; i < len; i++) {
          this._on(types[i], fn, context);
        }
      }
      return this;
    },
    
    _on: function(type, fn, context) {
      this._events = this._events || {};
      var typeListeners = this._events[type] = this._events[type] || [];
      typeListeners.push({fn: fn, ctx: context});
    },
    
    _splitWords: function(str) {
      return str.trim().split(/\s+/);
    }
  });
  
  // Simple Class implementation
  L.Class = function() {};
  
  L.Class.extend = function(props) {
    var NewClass = function() {
      if (this.initialize) {
        this.initialize.apply(this, arguments);
      }
    };
    
    var parentProto = NewClass.__super__ = this.prototype;
    var proto = Object.create(parentProto);
    proto.constructor = NewClass;
    NewClass.prototype = proto;
    
    for (var i in props) {
      proto[i] = props[i];
    }
    
    return NewClass;
  };
  
  // Map class (minimal implementation)
  L.Map = L.Class.extend({
    options: {
      crs: L.CRS.EPSG3857,
      center: null,
      zoom: 0,
      scrollWheelZoom: true,
      zoomControl: true,
      maxBounds: undefined,
      maxBoundsViscosity: 0.0
    },
    
    initialize: function(id, options) {
      L.setOptions(this, options);
      this._initContainer(id);
      this._initLayout();
      this._loaded = false;
      
      if (this.options.center && this.options.zoom !== undefined) {
        this.setView(L.latLng(this.options.center), this.options.zoom);
      }
    },
    
    _initContainer: function(id) {
      var container = typeof id === 'string' ? document.getElementById(id) : id;
      if (!container) {
        throw new Error('Map container not found.');
      }
      this._container = container;
      container._leaflet = this;
    },
    
    _initLayout: function() {
      var container = this._container;
      container.style.position = 'relative';
      container.style.outline = '0';
      
      this._initPanes();
    },
    
    _initPanes: function() {
      var panes = this._panes = {};
      
      this._mapPane = this.createPane('mapPane', this._container);
      this.createPane('tilePane', this._mapPane);
      this.createPane('shadowPane', this._mapPane);
      this.createPane('overlayPane', this._mapPane);
      this.createPane('markerPane', this._mapPane);
      this.createPane('popupPane', this._mapPane);
    },
    
    createPane: function(name, container) {
      var className = 'leaflet-pane' + (name ? ' leaflet-' + name.replace('Pane', '') + '-pane' : '');
      var pane = document.createElement('div');
      pane.className = className;
      
      if (container) {
        container.appendChild(pane);
      } else {
        this._container.appendChild(pane);
      }
      
      this._panes[name] = pane;
      return pane;
    },
    
    setView: function(center, zoom) {
      this._loaded = true;
      this._initialCenter = center;
      this._zoom = zoom;
      
      // Set container size
      if (!this._container._leaflet) {
        this._container.style.width = '100%';
        this._container.style.height = '100%';
      }
      
      return this;
    },
    
    getCenter: function() {
      return this._loaded ? this._initialCenter : this.options.center;
    },
    
    getZoom: function() {
      return this._zoom;
    },
    
    getBounds: function() {
      return new L.LatLngBounds(this._initialCenter, this._initialCenter);
    },
    
    invalidateSize: function() { return this; },
    
    fitBounds: function(bounds, options) {
      this.setView(bounds.getCenter(), this.getBoundsZoom(bounds, options));
      return this;
    },
    
    getBoundsZoom: function(bounds, options) {
      options = options || {};
      var padding = options.padding || [0, 0];
      var zoom = this.getZoom() || 0;
      return Math.min(zoom + 2, 18); // Simple approximation
    },
    
    addLayer: function(layer) {
      if (!layer.addTo) {
        throw new Error('The provided object is not a Layer.');
      }
      layer.addTo(this);
      return this;
    },
    
    removeLayer: function(layer) {
      if (layer.remove) {
        layer.remove();
      }
      return this;
    },
    
    fire: function() { return this; },
    listens: function() { return false; },
    latLngToContainerPoint: function(latlng) {
      var w = this._container.clientWidth || 300;
      var h = this._container.clientHeight || 300;
      var x = (latlng.lng + 180) / 360 * w;
      var y = (90 - latlng.lat) / 180 * h;
      return new L.Point(x, y);
    }
  });
  
  // TileLayer class
  L.TileLayer = L.Class.extend({
    options: {
      minZoom: 0,
      maxZoom: 18,
      attribution: '',
      opacity: 1
    },
    
    initialize: function(urlTemplate, options) {
      L.setOptions(this, options);
      this._url = urlTemplate;
    },
    
    addTo: function(map) {
      this._map = map;
      this._container = map._panes.tilePane;
      
      // Create tile container
      this._initContainer();
      
      // Add attribution
      if (this.options.attribution) {
        this._map.attributionControl = this._map.attributionControl || {
          addAttribution: function(text) {
            var attribution = this._container.querySelector('.leaflet-control-attribution') || 
                             this._createAttributionControl();
            attribution.innerHTML += text;
          }.bind(this._map)
        };
        this._map.attributionControl.addAttribution(this.options.attribution);
      }
      
      // Load tiles
      this._update();
      
      return this;
    },
    
    _initContainer: function() {
      if (!this._container._tileContainer) {
        var tileContainer = document.createElement('div');
        tileContainer.className = 'leaflet-layer leaflet-tile-container';
        tileContainer.style.position = 'absolute';
        tileContainer.style.top = '0';
        tileContainer.style.left = '0';
        tileContainer.style.width = '100%';
        tileContainer.style.height = '100%';
        this._container.appendChild(tileContainer);
        this._container._tileContainer = tileContainer;
      }
      this._tileContainer = this._container._tileContainer;
    },
    
    _createAttributionControl: function() {
      var attribution = document.createElement('div');
      attribution.className = 'leaflet-control-attribution leaflet-control';
      attribution.style.position = 'absolute';
      attribution.style.bottom = '0';
      attribution.style.right = '0';
      attribution.style.background = 'rgba(255,255,255,0.7)';
      attribution.style.padding = '0 5px';
      attribution.style.fontSize = '11px';
      attribution.style.zIndex = '1000';
      this._container.appendChild(attribution);
      return attribution;
    },
    
    _update: function() {
      // Simple tile loading simulation
      var center = this._map.getCenter();
      var zoom = this._map.getZoom();
      
      // Clear existing tiles
      this._tileContainer.innerHTML = '';
      
      // Create a simple placeholder
      var placeholder = document.createElement('div');
      placeholder.style.position = 'absolute';
      placeholder.style.top = '50%';
      placeholder.style.left = '50%';
      placeholder.style.transform = 'translate(-50%, -50%)';
      placeholder.style.textAlign = 'center';
      placeholder.style.color = '#666';
      placeholder.innerHTML = '<i class="fas fa-map fa-3x"></i><br><small>Peta OSM</small>';
      this._tileContainer.appendChild(placeholder);
      
      // Fire load event
      this.fire('load');
    },
    
    on: function(type, fn, context) {
      this._events = this._events || {};
      var typeListeners = this._events[type] = this._events[type] || [];
      typeListeners.push({fn: fn, ctx: context});
      return this;
    },
    
    fire: function(type, data) {
      if (!this._events) return this;
      var listeners = this._events[type];
      if (listeners) {
        for (var i = 0, len = listeners.length; i < len; i++) {
          var l = listeners[i];
          l.fn.call(l.ctx || this, data);
        }
      }
      return this;
    }
  };
  
  // Marker class
  L.Marker = L.Class.extend({
    options: {
      icon: new L.Icon.Default(),
      draggable: false
    },
    
    initialize: function(latlng, options) {
      L.setOptions(this, options);
      this._latlng = L.latLng(latlng);
    },
    
    addTo: function(map) {
      this._map = map;
      this._initIcon();
      this.update();
      return this;
    },
    
    _initIcon: function() {
      var options = this.options;
      var classToAdd = 'leaflet-zoom-hide';
      
      var icon = options.icon.createIcon();
      this._icon = icon;
      
      icon.classList.add(classToAdd);
      
      if (options.icon.options.tooltipAnchor) {
        icon.style.position = 'relative';
      }
      
      this._map._panes.markerPane.appendChild(icon);
    },
    
    update: function() {
      if (this._icon && this._map) {
        var pos = this._map.latLngToContainerPoint(this._latlng);
        this._setPos(pos);
      }
      return this;
    },
    
    _setPos: function(pos) {
      var icon = this._icon;
      if (icon) {
        icon.style.left = pos.x + 'px';
        icon.style.top = pos.y + 'px';
      }
    },
    
    bindTooltip: function(content, options) {
      this._tooltipContent = content;
      this._tooltipOptions = options || {};
      return this;
    },
    
    bindPopup: function(content, options) {
      this._popupContent = content;
      this._popupOptions = options || {};
      return this;
    },
    
    getLatLng: function() {
      return this._latlng;
    }
  };
  
  // DivIcon class
  L.DivIcon = L.Icon.extend({
    options: {
      iconSize: [12, 12],
      className: 'leaflet-div-icon',
      html: false
    },
    
    createIcon: function() {
      var div = document.createElement('div');
      var options = this.options;
      
      if (options.html === false) {
        div.innerHTML = '';
      } else {
        div.innerHTML = options.html;
      }
      
      if (options.bgPos) {
        div.style.backgroundPosition = (-options.bgPos.x) + 'px ' + (-options.bgPos.y) + 'px';
      }
      
      this._setIconStyles(div, 'icon');
      return div;
    },
    
    _setIconStyles: function(img, name) {
      var options = this.options;
      var size = options.iconSize;
      var anchor = options.iconAnchor;
      
      if (typeof size === 'number') {
        size = [size, size];
      }
      
      if (typeof anchor === 'number') {
        anchor = [anchor, anchor];
      }
      
      img.className = options.className + ' leaflet-marker-' + name;
      
      if (size) {
        img.style.width = size[0] + 'px';
        img.style.height = size[1] + 'px';
      }
      
      if (anchor) {
        img.style.marginLeft = (-anchor[0]) + 'px';
        img.style.marginTop = (-anchor[1]) + 'px';
      }
    }
  });
  
  // Utility functions
  L.setOptions = function(obj, options) {
    if (!Object.prototype.hasOwnProperty.call(obj, 'options')) {
      obj.options = obj.options ? L.extend({}, obj.options) : {};
    }
    for (var i in options) {
      obj.options[i] = options[i];
    }
    return obj.options;
  };
  
  L.extend = function(dest) {
    var i, j, len, src;
    for (j = 1, len = arguments.length; j < len; j++) {
      src = arguments[j];
      for (i in src) {
        dest[i] = src[i];
      }
    }
    return dest;
  };
  
  L.bind = function(fn, obj) {
    var slice = Array.prototype.slice;
    if (fn.bind) {
      return fn.bind.apply(fn, slice.call(arguments, 1));
    }
    var args = slice.call(arguments, 2);
    return function() {
      return fn.apply(obj, args.length ? args.concat(slice.call(arguments)) : arguments);
    };
  };
  
  // Event system
  L.Evented = L.Class.extend({
    on: function(types, fn, context) {
      if (typeof types === 'object') {
        for (var type in types) {
          this._on(type, types[type], fn);
        }
      } else {
        types = this._splitWords(types);
        for (var i = 0, len = types.length; i < len; i++) {
          this._on(types[i], fn, context);
        }
      }
      return this;
    },
    
    _on: function(type, fn, context) {
      this._events = this._events || {};
      var typeListeners = this._events[type] = this._events[type] || [];
      typeListeners.push({fn: fn, ctx: context});
    },
    
    _splitWords: function(str) {
      return str.trim().split(/\s+/);
    }
  });
  
  // Simple Class implementation
  L.Class = function() {};
  
  L.Class.extend = function(props) {
    var NewClass = function() {
      if (this.initialize) {
        this.initialize.apply(this, arguments);
      }
    };
    
    var parentProto = NewClass.__super__ = this.prototype;
    var proto = Object.create(parentProto);
    proto.constructor = NewClass;
    NewClass.prototype = proto;
    
    for (var i in props) {
      proto[i] = props[i];
    }
    
    return NewClass;
  };
  
  // Factory functions
  L.map = function(id, options) {
    return new L.Map(id, options);
  };
  
  L.tileLayer = function(urlTemplate, options) {
    return new L.TileLayer(urlTemplate, options);
  };
  
  L.marker = function(latlng, options) {
    return new L.Marker(latlng, options);
  };
  
  L.latLng = function(lat, lng) {
    if (lat instanceof L.LatLng) {
      return lat;
    }
    return new L.LatLng(lat, lng);
  };
  
  L.latLngBounds = function(a, b) {
    return new L.LatLngBounds(a, b);
  };
  
  L.divIcon = function(options) {
    return new L.DivIcon(options);
  };
  
  console.log('Leaflet fallback loaded successfully');
})();
