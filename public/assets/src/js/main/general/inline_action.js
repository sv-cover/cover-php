import {Bulma} from 'cover-style-system/src/js';

/**
 * InlineAction plugin to submit forms and load links asynchronously and inject result in DOM.
 * Supports the following data options:
 *
 * placement-selector = selector for the target element in current DOM. This attribute enables the plugin
 * placement-method = "append" | "replace" (default)
 * partial-selector = selector to find partial subtree in the destination DOM. (default: "body")
 * async-action = href for anchor or action to submit form, if different from fallback
 */
class InlineAction {
    static parseDocument(context) {
        const elements = context.querySelectorAll('a[data-placement-selector],form[data-placement-selector]');

        Bulma.each(elements, element => {
            new InlineAction({
                element: element,
                type: element.tagName.toLowerCase(),
                partialSelector: element.dataset.partialSelector || 'body',
                placementSelector: element.dataset.placementSelector,
                placementMethod: element.dataset.placementMethod || 'replace',
            });
        });

    }

    constructor(options) {
        this.element = options.element;
        this.type = options.type;
        this.partialSelector = options.partialSelector;
        this.placementSelector = options.placementSelector;
        this.placementMethod = options.placementMethod;

        this.setupEvents();
    }

    setupEvents() {
        if (this.type === 'a')
            this.element.addEventListener('click', this.handleAction.bind(this));
        else if (this.type === 'form')
            this.element.addEventListener('submit', this.handleAction.bind(this));
        else
            console.error(`Unsupported inline action element: ${this.type}`);
    }

    async fetchDocument() {
        let url, init;

        // Prepare request
        if (this.type === 'a') {
            // Follow link if anchor
            url = this.element.dataset.asyncAction || this.element.href;
            init = {
                method: 'GET',
            };
        } else if (this.type === 'form') {
            // Submit if form
            url = this.element.dataset.asyncAction || this.element.action;
            const data = new FormData(this.element);
            init = {
                method: this.element.method.toUpperCase(),
                body: new URLSearchParams(data),
            };
        }

        // Execute request
        const response = await fetch(url, init);
        const text = await response.text();

        // Parse response to DOM
        return (new DOMParser()).parseFromString(text, 'text/html');
    }

    async handleAction(event) {
        // Do not disturb any effect of modifier keys
        if (event.shiftKey || event.metaKey || event.ctrlKey)
            return;

        // Don't follow links or use default form submit
        event.preventDefault();

        // Find destination
        const target = document.querySelector(this.placementSelector);

        // Fetch partial
        const doc = await this.fetchDocument();
        const partial = doc.querySelector(this.partialSelector);

        // Place partial in destination position
        if (this.placementMethod === 'replace')
            target.replaceWith(partial);
        else if (this.placementMethod === 'append')
            target.appendChild(partial);
        else
            console.error(`Unsupported inline action placement method: ${this.placementMethod}`);

        // Make sure all JS is applied to partial
        document.dispatchEvent(new CustomEvent('partial-content-loaded', { bubbles: true, detail: partial }));
    }
}

InlineAction.parseDocument(document);
document.addEventListener('partial-content-loaded', event => InlineAction.parseDocument(event.detail));

export default InlineAction;
