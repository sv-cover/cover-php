import {Bulma, AutoPopup} from 'cover-style-system/src/js';
import mapboxgl from 'mapbox-gl/dist/mapbox-gl';
import {createMap, createMarker} from './utils';

const DEFAULT_ZOOM = 12;
const DEFAULT_LAT = 53.219386; // Martinitoren
const DEFAULT_LNG = 6.568210; // Martinitoren

class StickerMap {
    /**
     * Get the root class this plugin is responsible for.
     * This will tell the core to match this plugin to an element with a .modal class.
     * @returns {string} The class this plugin is responsible for.
     */
    static getRootClass() {
        return 'sticker-map';
    }

    /**
     * Handle parsing the DOMs data attribute API.
     * @param {HTMLElement} element The root element for this instance
     * @return {undefined}
     */
    static parse(element) {
        new StickerMap({
            element: element,
        });
    }

    constructor(options) {
        this.element = options.element;
        this.mapElement = this.element.querySelector('.map');
        this.popupTemplate = this.element.querySelector('[data-map-popup-template]');
        this.defaults = this.getInitialState();
        this.createButtons = this.element.querySelectorAll('[data-add-sticker-button]');

        this.initMap();
    }

    initMap() {
        this.map = createMap({
            container: this.mapElement,
            center: [this.defaults.lng, this.defaults.lat],
            zoom: this.defaults.zoom,
        });

        this.map.addControl(new mapboxgl.NavigationControl({
            showCompass: false,
        }));

        this.map.addControl(new mapboxgl.FullscreenControl());

        this.map.on('dragend', this.handleMapViewChange.bind(this));
        this.map.on('zoomend', this.handleMapViewChange.bind(this));
        this.map.on('dblclick', this.handleMapDoubleClick.bind(this));

        this.handleMapViewChange();

        fetch(this.mapElement.dataset.geojsonUrl).then(
            response => response.json()
        ).then(
            data => this.initStickers(data)
        );
    }

    initStickers(geojson) {
        // Sort points from north to south to stack them naturally
        this.points = geojson.features;

        geojson.features.sort((a,b) => b.geometry.coordinates[1] - a.geometry.coordinates[1]);

        for (let sticker of geojson.features) {
            let marker = createMarker();
            marker.setLngLat(sticker.geometry.coordinates);
            marker.setPopup(this.initPopup(sticker));
            marker.addTo(this.map);

            if (this.defaults.point && sticker.properties.id == this.defaults.point)
                marker.togglePopup();
        }

        // All popups emit close on initiation, be sure to not loose our active popup from the url
        if (this.defaults.point)
            this.updateState({point: this.defaults.point});
    }

    initPopup(sticker) {
        let popupElement = this.popupTemplate.content.firstElementChild.cloneNode(true);

        if (sticker.properties.label) {
            let el = popupElement.querySelector('[data-sticker-label]');
            el.textContent = sticker.properties.label;
            el.hidden = false;
        }

        if (sticker.properties.added_by_name) {
            let el = popupElement.querySelector('[data-sticker-user-link]');
            el.textContent = sticker.properties.added_by_name;

            if (sticker.properties.added_by_url)
                el.href = sticker.properties.added_by_url;

            popupElement.querySelector('[data-sticker-user]').hidden = false;
        }

        if (sticker.properties.description && sticker.properties.description != '') {
            let el = popupElement.querySelector('[data-sticker-description]');
            el.textContent = sticker.properties.description;
            el.hidden = false;
        }

        if (sticker.properties.added_on) {
            let el = popupElement.querySelector('[data-sticker-time]');
            el.append(sticker.properties.added_on);
            el.hidden = false;
        }

        if (sticker.properties.photo_url) {
            let el = popupElement.querySelector('[data-sticker-image] img');
            el.src = sticker.properties.photo_url;
            popupElement.querySelector('[data-sticker-image]').hidden = false;
        }

        if (sticker.properties.add_photo_url) {
            let button = popupElement.querySelector('[data-sticker-upload-image]');
            button.addEventListener('click', this.handlePopup.bind(this, sticker.properties.add_photo_url));
            button.hidden = false;

            popupElement.querySelector('[data-sticker-controls]').hidden = false;
        }

        if (sticker.properties.delete_url) {
            let button = popupElement.querySelector('[data-sticker-delete]');
            button.addEventListener('click', this.handlePopup.bind(this, sticker.properties.delete_url));
            button.hidden = false;

            popupElement.querySelector('[data-sticker-controls]').hidden = false;
        }

        let popup = new mapboxgl.Popup({
            maxWidth: '80vw',
        }).setDOMContent(popupElement);

        if (sticker.properties.id) {
            popup.on('open', () => this.updateState({point: sticker.properties.id}));
            popup.on('close', () => this.updateState({point: null}));
        }

        return popup;
    }

    getInitialState() {
        let searchParams = new URLSearchParams(window.location.search);

        return {
            lat: parseFloat(searchParams.get('lat')) || DEFAULT_LAT,
            lng: parseFloat(searchParams.get('lng')) || DEFAULT_LNG,
            zoom: parseFloat(searchParams.get('zoom')) || DEFAULT_ZOOM,
            point: parseInt(searchParams.get('point')),
        };
    }

    updateState(data) {
        const url = new URL(window.location.href);
        let searchParams = url.searchParams;

        for (name in data) {
            if (data[name] === null)
                searchParams.delete(name);
            else
                searchParams.set(name, data[name]);
        }

        url.search = searchParams.toString();
        history.replaceState({}, '', url.toString());
    }

    updateCreateButtons(data) {
        for (let button of this.createButtons) {
            const url = new URL(button.href, window.location.href);
            let searchParams = url.searchParams;

            for (name in data) {
                if (data[name] === null)
                    searchParams.delete(name);
                else
                    searchParams.set(name, data[name]);
            }

            url.search = searchParams.toString();
            button.href = url.toString();
        }
    }

    handleMapDoubleClick(event) {
        event.preventDefault();

        const url = new URL(this.mapElement.dataset.createUrl, window.location.href);
        let searchParams = url.searchParams;

        searchParams.set('lat', event.lngLat.lat);
        searchParams.set('lng', event.lngLat.lng);
        searchParams.set('zoom', this.map.getZoom());

        url.search = searchParams.toString();

        this.handlePopup(url, event);
    }

    handleMapViewChange() {
        const center = this.map.getCenter();
        const data = {
            lat: center.lat,
            lng: center.lng,
            zoom: this.map.getZoom(),
        };

        this.updateState(data);
        this.updateCreateButtons(data);
    }

    handlePopup(url, event) {
        if (document.fullscreenElement)
            document.exitFullscreen();

        url = new URL(url, window.location.href);
        let params = url.searchParams;

        params.set('next', window.location.href);

        // Execute request and load modal
        const request = fetch(url.toString());
        new AutoPopup({contentType: 'modal'}, request);
    }
}


Bulma.registerPlugin('sticker-map', StickerMap);

export default StickerMap;
