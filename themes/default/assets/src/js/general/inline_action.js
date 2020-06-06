import {Bulma} from 'cover-style-system/src/js';
import Sortable from 'sortablejs';

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

        if (this.type === 'a') {
            url = this.element.href;
            init = {
                method: 'GET',
            };
        } else if (this.type === 'form') {
            url = this.element.dataset.asyncAction || this.element.action;
            const data = new FormData(this.element);
            init = {
                method: this.element.method,
                body: new URLSearchParams(data),
            };
        }

        const response = await fetch(url, init);
        const text = await response.text();

        return (new DOMParser()).parseFromString(text, 'text/html');
    }

    async handleAction(event) {
        // Do not disturb any effect of modifier keys
        if (event.shiftKey || event.metaKey || event.ctrlKey)
            return;

        event.preventDefault();

        const target = document.querySelector(this.placementSelector);

        const doc = await this.fetchDocument();
        const partial = doc.querySelector(this.partialSelector);

        if (this.placementMethod === 'replace')
            target.replaceWith(partial);
        else if (this.placementMethod === 'append')
            target.appendChild(partial);
        else
            console.error(`Unsupported inline action placement method: ${this.placementMethod}`);

        document.dispatchEvent(new CustomEvent('partial-content-loaded', { bubbles: true, detail: partial }));
    }
}

InlineAction.parseDocument(document);
document.addEventListener('partial-content-loaded', event => InlineAction.parseDocument(event.detail));

export default InlineAction;
