import {Bulma} from 'cover-style-system/src/js';
import Sortable from 'sortablejs';

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

        const sortableOptions = {
            handle: options.handle ? options.handle : '',
            onEnd: this.handleSortableEnd.bind(this),
        };
        this.sortable = Sortable.create(options.element, sortableOptions);

        const handles = this.element.querySelectorAll(options.handle);
        for (let el of handles)
            el.hidden = false;
    }

    handleSortableEnd(event) {
        const data = new FormData();
        
        for (let id of this.sortable.toArray())
            data.append('order[]', id);

        const init = {
            method: 'POST',
            body: new URLSearchParams(data),
        };

        // Execute request
        fetch(this.action, init).catch(error => alert(error));
    }
}

BasicSortable.parseDocument(document);
document.addEventListener('partial-content-loaded', event => BasicSortable.parseDocument(event.detail));

export default BasicSortable;
