import {Bulma, Collapse} from 'cover-style-system/src/js';


class SearchResults {
    /**
     * Get the root class this plugin is responsible for.
     * This will tell the core to match this plugin to an element with a .modal class.
     * @returns {string} The class this plugin is responsible for.
     */
    static getRootClass() {
        return 'search-results';
    }


    /**
     * Handle parsing the DOMs data attribute API.
     * @param {HTMLElement} element The root element for this instance
     * @return {undefined}
     */
    static parse(element) {
        new SearchResults({
            element: element,
        });
    }

    constructor(options) {
        this.element = options.element;
        this.links = this.element.querySelectorAll('[data-search-main-link]');

        for (let idx = 0; idx < this.links.length; idx++) {
            this.links[idx].tabIndex = idx + 1;
            this.links[idx].addEventListener('focus', this.handleFocus.bind(this, idx));
        }

        if (this.links.length > 0)
            this.links[0].focus();

        document.addEventListener('keydown', this.handleKeyDown.bind(this));
    }

    handleKeyDown(event) {
        let currentFocus = null;

        for (let idx = 0; idx < this.links.length; idx++)
            if (document.activeElement === this.links[idx])
                currentFocus = idx;

        if (currentFocus === null)
            return;

        switch (event.key) {
            case "Down":
            case "ArrowDown":
                currentFocus++;
                break;
            case "Up": // IE/Edge specific value
            case "ArrowUp":
                currentFocus--;
                break;
            default:
                // Don't need to do anything else now
                return;
        }

        event.preventDefault();

        // Wrap around, focus element
        currentFocus = (this.links.length + currentFocus) % this.links.length;
        this.links[currentFocus].focus();
    }

    handleFocus(idx, event) {
        this.links[idx].closest('.search-result').scrollIntoView({
            behavior: 'auto',
            block: 'nearest'
        });
    }
}


Bulma.registerPlugin('search-results', SearchResults);

export default SearchResults;
