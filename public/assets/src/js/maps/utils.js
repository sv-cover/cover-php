import mapboxgl from 'mapbox-gl/dist/mapbox-gl';

mapboxgl.accessToken = process.env.MAPBOX_GL_ACCESS_TOKEN;

export function getMapStyle() {
    const colorMode = String(getComputedStyle(document.documentElement).getPropertyValue('--color-mode')).trim();

    if (colorMode === 'dark')
        return process.env.MAPBOX_GL_STYLE_DARK;

    return process.env.MAPBOX_GL_STYLE_LIGHT;
}

export function createMap(options=null) {
    if (!options)
        options = {};

    options = Object.assign({
        style: getMapStyle(),
        dragRotate: false,
        pitchWithRotate: false,
    }, options);

    return new mapboxgl.Map(options);   
}

export function createMarker(options=null) {
    if (!options)
        options = {};

    if (!options.element && !options.useDefaultMarker) {
        options.element = document.createElement('div');
        options.element.classList.add('map-marker-cover');
        options.offset = [0, -18];
    }

    return new mapboxgl.Marker(options);
}


export function checkMapboxSupport(element) {
    let message = element.querySelector('[data-unsupported-message]')

    if (mapboxgl.supported()) {
        if (message)
            message.remove();
        return true;
    }

    if (!message) {
        message = document.createElement('div');
        message.classList.add('notification');
        message.innerHTML = 'This map requires WebGL support. Please check that you are using a supported browser and that <a href="https://get.webgl.org/" target="_blank" rel="noopener noreferrer" >WebGL is enabled</a>.';
        element.appendChild(message);
    }

    message.hidden = false;

    return false;
}
