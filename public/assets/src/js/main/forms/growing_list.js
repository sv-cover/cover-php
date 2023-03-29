import {Bulma} from 'cover-style-system/src/js';
import Sortable from 'sortablejs';

/**
 * GrowingList plugin to create a list that gets longer once more options are desired.
 * Supports the following data options:
 *
 * growing-list-template = selector to find the template for an emty item (mandatory)
 * growing-list-input = selector to find the input in each item (default: "input")
 * growing-list-max-length = maximum allowed length to grow to (default: Number.MAX_SAFE_INTEGER)
 * growing-list-sortable = Boolean attribute. List will be sortable if present. 
 * sortable-handle = selector to find the sortable handle (default none, the entire item is the handle)
 */
class GrowingList {
    static parseDocument(context) {
        const elements = context.querySelectorAll('.growing-list');

        Bulma.each(elements, element => {
            // Make sure we have a template
            if (!element.dataset.growingListTemplate)
                throw new Error ('No template selector provided for growing list');

            const template = context.querySelector(element.dataset.growingListTemplate);
            if (!template)
                throw new Error (`Growing list template '${element.dataset.growingListTemplate}' not found`);
        
            new GrowingList({
                element: element,
                template: template,
                inputSelector: element.dataset.growingListInput || 'input',
                placeholder: element.dataset.growingListPlaceholder || '__name__',
                maxLength: element.dataset.growingListMaxLength || Number.MAX_SAFE_INTEGER,
                isSortable: (element.dataset.growingListSortable != null
                    && element.dataset.growingListSortable.toLowerCase() !== 'false'),
                sortableHandle: element.dataset.sortableHandle,
            });
        });

    }

    constructor(options) {
        this.element = options.element;
        this.template = options.template;
        this.maxLength = options.maxLength;
        this.inputSelector = options.inputSelector;
        this.placeholder = options.placeholder;

        // Init sortable
        if (options.isSortable) {
            this.sortable = Sortable.create(this.element, {
                handle: options.handle ? options.handle : '',
                onUpdate: this.handleSortableUpdate.bind(this),
            });
        }

        this.setupEvents();

        // Init empty field
        this.grow();
    }

    setupEvents() {
        this.element.addEventListener('input', this.handleInput.bind(this));
        this.element.addEventListener('keydown', this.handleKeyDown.bind(this));
    }

    getInputs() {
        return this.element.querySelectorAll(this.inputSelector);
    }

    grow() {
        // Add a field if no fields, or if the last field is no longer empty unless maxLength is reached
        const inputs = this.getInputs();
        if ((inputs.length === 0 || inputs[inputs.length-1].value != '' && inputs.length <= this.maxLength))  {
            // Replace stuff to keep Symfony happy
            let template = this.template.cloneNode(true);
            template.innerHTML = template.innerHTML.replace(new RegExp(this.placeholder, 'g'), inputs.length);
            const clone = template.content.cloneNode(true);
            this.element.appendChild(clone)
        }
    }

    focus(element) {
        let input = element.querySelector(this.inputSelector);

        // Place cursor at end of field
        input.focus();
        input.setSelectionRange(input.value.length, input.value.length);
    }

    handleInput(event) {
        this.grow();
    }

    handleKeyDown(event) {
        // Focus next option on enter
        if (event.key === 'Enter') {
            let previous = null;
            for (let el of this.element.children) {
                if (previous && previous.contains(event.target)) {
                    this.focus(el);
                    break;
                }
                previous = el;
            }
        }

        // Delete option on backspace in empty field
        if (event.key === 'Backspace' && event.target.value == '') {
            event.preventDefault();

            // Don't remove field if it's the last
            if (this.getInputs().length <= 1)
                return;

            // Find previous element and remove current
            let previous = null;
            for (let el of this.element.children) {
                if (el.contains(event.target)) {
                    el.remove();
                    break;
                }
                previous = el;
            }

            // Dispatch change for autosubmit
            this.element.dispatchEvent(new Event('change', {'bubbles':true}));

            // Focus on previous (or first in list)
            if (previous)
                this.focus(previous);
            else
                this.focus(this.element); // selects first, because querySelector
        }
    }

    handleSortableUpdate(event) {
        // Recalculate id's and names to keep Symfony happy
        const templateInput = this.template.content.querySelector(`[name*="${this.placeholder}"]`);
        if (templateInput) {
            const id = templateInput.id;
            const name = templateInput.name;
            for (const [idx, input] of this.getInputs().entries()) {
                input.id = id.replace(new RegExp(this.placeholder, 'g'), idx);
                input.name = name.replace(new RegExp(this.placeholder, 'g'), idx);
            }
        }

        // Dispatch change for autosubmit
        this.element.dispatchEvent(new Event('change', {'bubbles':true}));
    }
}

GrowingList.parseDocument(document);
document.addEventListener('partial-content-loaded', event => GrowingList.parseDocument(event.detail));

export default GrowingList;
