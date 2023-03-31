import {Bulma} from 'cover-style-system/src/js';


class MailingListArchivedMessage {
    /**
     * Get the root class this plugin is responsible for.
     * This will tell the core to match this plugin to an element with a .modal class.
     * @returns {string} The class this plugin is responsible for.
     */
    static getRootClass() {
        return 'mailing-list-message-formatted';
    }


    /**
     * Handle parsing the DOMs data attribute API.
     * @param {HTMLElement} element The root element for this instance
     * @return {undefined}
     */
    static parse(element) {
        new MailingListArchivedMessage({
            element: element,
        });
    }

    constructor(options) {
        this.element = options.element;
        this.element.addEventListener('load', this.makeLegible.bind(this));

        // Just in case the iframe is already loaded
        this.makeLegible();
    }

    makeLegible() {
        // Grab document
        let doc = this.element.contentWindow.document;

        // "inherit" the font
        const style = getComputedStyle(this.element.parentNode);
        doc.body.style.fontFamily = style.fontFamily;
        doc.body.style.fontStyle = style.fontStyle;
        doc.body.style.fontWeight = style.fontWeight;

        // Make sure links open in new windows
        // in sandboxed, this will block links unless allow-popup is set
        let base = doc.createElement('base');
        base.target = '_blank';
        doc.head.appendChild(base);
    }
}


Bulma.registerPlugin('mailing-list-message', MailingListArchivedMessage);

export default MailingListArchivedMessage;
