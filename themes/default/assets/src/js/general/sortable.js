import {Bulma} from 'cover-style-system/src/js';
import Sortable from 'sortablejs';

/**
 * BasicSortable plugin to a basic sortable list, that submits itself on update.
 * Supports the following data options:
 *
 * sortable-handle = selector to find the sortable handle (default none, the entire item is the handle)
 * sortable-action = the url to POST the order to
 */
class BasicSortable {
    static parseDocument(context) {
        const elements = context.querySelectorAll('.sortable');

        Bulma.each(elements, element => {
            new BasicSortable({
                element: element,
                handle: element.dataset.sortableHandle,
                action: element.dataset.sortableAction,
            });
        });

    }

    constructor(options) {
        this.element = options.element;
        this.action = options.action;

        // Init sortable
        const sortableOptions = {
            handle: options.handle ? options.handle : '',
            onUpdate: this.handleSortableUpdate.bind(this),
        };
        this.sortable = Sortable.create(options.element, sortableOptions);

        // Show handles if available
        const handles = this.element.querySelectorAll(options.handle);
        for (let el of handles)
            el.hidden = false;
    }

    handleSortableUpdate(event) {
        // Prepare data
        const data = new FormData();
        for (let id of this.sortable.toArray())
            data.append('order[]', id);

        // Prepare request
        const init = {
            method: 'POST',
            body: new URLSearchParams(data),
        };

        // Execute request
        fetch(this.action, init).catch(error => console.error(error));
    }
}

BasicSortable.parseDocument(document);
document.addEventListener('partial-content-loaded', event => BasicSortable.parseDocument(event.detail));

export default BasicSortable;
