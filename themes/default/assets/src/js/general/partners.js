import {Bulma} from 'cover-style-system/src/js';


class Partners {
    /**
     * Get the root class this plugin is responsible for.
     * This will tell the core to match this plugin to an element with a .modal class.
     * @returns {string} The class this plugin is responsible for.
     */
    static getRootClass() {
        return 'partners';
    }


    /**
     * Handle parsing the DOMs data attribute API.
     * @param {HTMLElement} element The root element for this instance
     * @return {undefined}
     */
    static parse(element) {
        new Partners({
            element: element,
        });
    }

    constructor(options) {
        this.element = options.element;

        window.addEventListener('resize', this.handleResize.bind(this));

        this.handleResize();
    }

    handleResize() {
        const collapses = this.element.querySelectorAll('.collapse');
        const columns = parseInt(getComputedStyle(this.element).getPropertyValue('--column-count'), 10);

        for (const collapse of collapses) {
            collapse.classList.remove('is-disabled');
            if (collapse.querySelectorAll('.partner').length <= columns) {
                collapse.classList.add('is-disabled');
            }
        }
    }
}


Bulma.registerPlugin('partners', Partners);

export default Partners;
