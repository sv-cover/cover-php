import {Bulma} from 'cover-style-system/src/js';


class PreviewLoader {
    /**
     * Get the root class this plugin is responsible for.
     * This will tell the core to match this plugin to an element with a .modal class.
     * @returns {string} The class this plugin is responsible for.
     */
    static getRootClass() {
        return 'content-preview';
    }


    /**
     * Handle parsing the DOMs data attribute API.
     * @param {HTMLElement} element The root element for this instance
     * @return {undefined}
     */
    static parse(element) {
        new PreviewLoader({
            element: element,
            source: element.dataset.previewSource,
            url: element.dataset.previewUrl
        });
    }

    constructor(options) {
        this.element = options.element;
        this.url = options.url;
        this.source = document.querySelector(options.source);
        this.form = options.element.closest('form');

        if (!this.source)
            throw new Error('Preview source not found for "' + options.source + '"');

        this.element.addEventListener('show-tab', this.loadPreview.bind(this));
    }

    async fetchPreview() {
        const response = await fetch(this.url, {
            method: 'POST',
            body: new FormData(this.form)
        });
     
        if (!response.ok) {
            throw new Error(await response.text());
        }

        return await response.text();
    }

    loadPreview() {
        this.fetchPreview()
            .then(content => this.element.innerHTML = content)
            .catch(error => console.warn(error));
    }
}


Bulma.registerPlugin('preview-loader', PreviewLoader);

export default PreviewLoader;
