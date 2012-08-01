// jQuery access to all!
(function($) {


/**
 * @class Localize_Exception
 */
function Localize_Exception(message) {
	this.name = 'Localize Exception';
	this.message = message;
}
Localize_Exception.prototype.toString = function() {
	return this.name + ': ' + this.message;	
};


/**
 * @class Localize
 */
function Localize(id, mapOptions) {

	this.middleAmerica = new google.maps.LatLng(48.283193, -93.779297);

	this.LOCATION_USER      = 'geo-loc-address-user';
	this.LOCATION_COMP      = 'geo-loc-address-computed';
	this.LOCATION_ADDR      = 'geo-loc-address';
	this.LOCATION_LAT       = 'geo-loc-lat';
	this.LOCATION_LNG       = 'geo-loc-lng';
	this.LOCATION_STATE     = 'geo-loc-state';
	this.LOCATION_COUNTRY   = 'geo-loc-country';

	this.map = null;
	this.geocoder = new google.maps.Geocoder();
	this.currentLat = this.middleAmerica.lat();
	this.currentLng = this.middleAmerica.lng();
	this.geocodeURL = 'http://maps.googleapis.com/maps/api/geocode/json';

	this.mapOptions = {
		center: this.middleAmerica,
		zoom: 1,
		mapTypeId: google.maps.MapTypeId.ROADMAP,
		disableDefaultUI: true,
		disableDoubleClickZoom: true,
		draggable: false,
		scrollwheel: false
	};

	if ('string' !== typeof id) throw new Localize_Exception('HTML element ID is not a string.');
	else this.id = id;
	
	for(var optionName in mapOptions) {
		this.mapOptions[optionName] = mapOptions[optionName];
	}
	
}

Localize.prototype.load = function(id, mapOptions) {
	this.map = new google.maps.Map(document.getElementById(this.id), this.mapOptions);
};

Localize.prototype.update = function() {
	
	var address = $('#' + this.LOCATION_USER).val();
	if (address.length === 0) return;

	var _this = this;
	this.geocoder.geocode({'address': address}, function(results, status){
		console.log(results);
		if (status == google.maps.GeocoderStatus.OK) {
			_this.setCenterAndBounds(results[0].geometry);
			// Set computed fields
			$('#' + _this.LOCATION_COMP).text(results[0].formatted_address);
			$('#' + _this.LOCATION_ADDR).val(results[0].formatted_address);
			$('#' + _this.LOCATION_LAT).val(results[0].geometry.location.lat());
			$('#' + _this.LOCATION_LNG).val(results[0].geometry.location.lng());
			
			// Get administrative area and country
			for (var i = 0; i < results[0].address_components.length; i++) {
				var component = results[0].address_components[i];
				if ($.inArray('country', component.types) >= 0) {
					$('#' + _this.LOCATION_COUNTRY).val(component.short_name);
				} else if ($.inArray('administrative_area_level_1', component.types) >= 0) {
					$('#' + _this.LOCATION_STATE).val(component.short_name);
				}
			}
			
		} else {
			console.log('Unable to geocode the provided address: ' + status);
		}
	});

};

Localize.prototype.setCenterAndBounds = function(geometry) {
	this.currentLat = geometry.location.lat();
	this.currentLng = geometry.location.lng();
	this.map.setCenter(geometry.location);
	if ('undefined' !== typeof geometry.bounds) {
		this.map.fitBounds(geometry.bounds);
	} else {
		this.map.setZoom(18);
	}
};


/**
* Code that is executed immediately.
*/
$(document).ready(function(){
	
	var localizeObj = new Localize('map_canvas');	
	localizeObj.load();
	
	if ($('#' + localizeObj.LOCATION_ADDR).val().length > 0) {
		localizeObj.update();
	}
	
	$('#update-map').click(function(e){
		e.preventDefault();
		localizeObj.update();
	});
	
	$('#' + localizeObj.LOCATION_USER).keydown(function(e){
		if (e.which === 13) {
			e.preventDefault();
			localizeObj.update();
		}
	});
	
});


// End jQuery access to all!
})(jQuery);
