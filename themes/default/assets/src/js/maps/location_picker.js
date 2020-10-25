import {Bulma} from 'cover-style-system/src/js';
import mapboxgl from 'mapbox-gl/dist/mapbox-gl';
import {createMap, createMarker} from './utils';


const DEFAULT_ZOOM = 15;
const DEFAULT_LAT = 53.219386; // Martinitoren
const DEFAULT_LNG = 6.568210; // Martinitoren

class LocationPicker {
    /**
     * Get the root class this plugin is responsible for.
     * This will tell the core to match this plugin to an element with a .modal class.
     * @returns {string} The class this plugin is responsible for.
     */
    static getRootClass() {
        return 'location-picker';
    }

    /**
     * Handle parsing the DOMs data attribute API.
     * @param {HTMLElement} element The root element for this instance
     * @return {undefined}
     */
    static parse(element) {
        new LocationPicker({
            element: element,
            label: element.dataset.label || 'Location',
            helpText: element.dataset.helpText,
            latField: element.dataset.latField || 'lat',
            lngField: element.dataset.lngField || 'lng',
            zoom: element.dataset.zoom || DEFAULT_ZOOM,
        });
    }

    constructor(options) {
        this.element = options.element;
        this.latField = this.element.querySelector(`[name=${options.latField}]`);
        this.lngField = this.element.querySelector(`[name=${options.lngField}]`);

        if (!this.latField || !this.lngField)
            throw Error('Location Picker: lat field or lng field not provided');

        if (!this.latField.value)
            this.latField.value = DEFAULT_LAT;

        if (!this.lngField.value)
            this.lngField.value = DEFAULT_LNG;

        this.initUI(options);
    }

    initUI(options) {
        // Clear out fallback html
        while (this.element.firstChild)
            this.element.removeChild(this.element.firstChild);

        // Re-add lat and lng fields, but now they're hidden
        this.latField.type = 'hidden';
        this.lngField.type = 'hidden';
        this.element.append(this.latField);
        this.element.append(this.lngField);

        const labelElement = document.createElement('div');
        labelElement.classList.add('label');
        labelElement.textContent = options.label;
        this.element.append(labelElement);

        if (options.helpText) {
            const helpElement = document.createElement('p');
            helpElement.classList.add('help');
            helpElement.textContent = options.helpText;
            this.element.append(helpElement);
        }

        let mapElement = document.createElement('div');
        mapElement.classList.add('map', 'control');
        this.element.append(mapElement);

        this.initMap(mapElement, options);
    }

    initMap(mapElement, options) {
        const coordinates = [this.lngField.value, this.latField.value];

        const map = createMap({
            container: mapElement,
            center: coordinates,
            zoom: options.zoom,
        });

        map.on('click', this.handleClick.bind(this));

        map.addControl(new mapboxgl.NavigationControl({
            showCompass: false,
        }));

        const geolocate = new mapboxgl.GeolocateControl();
        map.addControl(geolocate);
        geolocate.on('geolocate', this.handleLocate.bind(this));

        this.marker = createMarker({
            draggable: true,
        });
        this.marker.setLngLat(coordinates);
        this.marker.addTo(map)
        this.marker.on('dragend', this.handleDrag.bind(this));
    }

    handleClick(event) {
        this.marker.setLngLat([event.lngLat.lng, event.lngLat.lat]);
        this.setCoordinates(event.lngLat.lng, event.lngLat.lat);   
    }

    handleDrag(event) {
        const coordinates = event.target.getLngLat();
        this.setCoordinates(coordinates.lng, coordinates.lat);
    }

    handleLocate(event) {
        this.marker.setLngLat([event.coords.longitude, event.coords.latitude]);
        this.setCoordinates(event.coords.longitude, event.coords.latitude);
    }

    setCoordinates(lng, lat) {
        this.lngField.value = lng;
        this.latField.value = lat;
    }
}

Bulma.registerPlugin('locatin-picker', LocationPicker);

export default LocationPicker;
