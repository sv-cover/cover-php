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

        this.element.addEventListener('input', this.handleInput.bind(this));
        this.element.addEventListener('keydown', this.handleKeyDown.bind(this));
        this.setupEvents(this.element);

        // Init empty field
        this.grow();
    }

    setupEvents(element) {
        for (let el of element.querySelectorAll('[data-growing-list-delete]'))
            el.addEventListener('click', this.shrink.bind(this));
    }


    getInputs(element) {
        if (element.parentElement !== this.element) {
            // Find row that contains element
            for (let el of this.element.children) {
                if (el.contains(element)) {
                    element = el;
                    break;
                }
            }
        }

        const inputs = element.querySelectorAll(this.inputSelector);
        if (inputs)
            return [...inputs];
        return [];
    }

    isEmpty(element) {
        return this.getInputs(element)
            .filter(e => e.type !== 'hidden') // don't care about hidden fields
            .every(e => e.value == '');
    }

    focus(element, reverse=false, filter=null) {
        let inputs = [...this.element.querySelectorAll(this.inputSelector)];
        if (reverse)
            inputs.reverse();

        let idx = inputs.indexOf(element);
        if (idx < 0)
            return;

        idx++;
        while (idx < inputs.length) {
            const input = inputs[idx];
             // Can't focus hidden fields
            if (inputs[idx].type !== 'hidden' && (!filter || filter(inputs[idx]))) {
                // Place cursor at end of field
                input.focus();
                input.setSelectionRange(input.value.length, input.value.length);
                return;
            }
            idx++;
        }
    }

    grow() {
        // Add a field if no fields, or if the last field is no longer empty unless maxLength is reached
        if (this.element.childElementCount < this.maxLength && (!this.element.lastElementChild || !this.isEmpty(this.element.lastElementChild))) {
            // Replace stuff to keep Symfony happy
            let template = this.template.cloneNode(true);
            template.innerHTML = template.innerHTML.replace(new RegExp(this.placeholder, 'g'), this.element.childElementCount);
            const clone = template.content.cloneNode(true);
            this.setupEvents(clone);
            this.element.appendChild(clone);
            document.dispatchEvent(new CustomEvent('partial-content-loaded', { bubbles: true, detail: this.element.lastElementChild }));
        }
    }

    shrink(event) {
        let parent;
        for (let el of this.element.children) {
            if (el.contains(event.target)) {
                parent = el;
                break;
            }
        }

        if (!parent)
            return;

        const active = document.activeElement;
        if (parent === this.element.lastElementChild) {
            this.focus(active, true);
            return;
        }

        if (parent.contains(active) && active.matches(this.inputSelector)) {
            // now focus the next or previous sibling.
            if (parent.previousElementSibling)
                this.focus(active, true, (el) => !parent.contains(el));
            else if (parent.nextElementSibling)
                this.focus(active, false, (el) => !parent.contains(el));
        }

        parent.remove();
    }

    handleInput(event) {
        this.grow();
    }

    handleKeyDown(event) {
        // Focus next option on enter
        if (event.key === 'Enter') {
            this.focus(event.target);
        }

        if (event.key === 'Backspace' && this.isEmpty(event.target)) {
            // Delete option on backspace in empty rows
            event.preventDefault();
            this.shrink(event);
        } else if (event.key === 'Backspace' && event.target.value == '') {
            // Allow navigation from empty subfields
            this.focus(event.target, true);
        }
    }

    handleSortableUpdate(event) {
        // Recalculate id's and names to keep Symfony happy
        const templateInput = this.template.content.querySelector(`[name*="${this.placeholder}"]`);
        if (templateInput) {
            const id = templateInput.id;
            const name = templateInput.name;
            for (let idx = 0; idx < this.element.childElementCount; idx++) {
                const element = this.element.children[idx];
                for (const input of this.getInputs(element)) {
                    if (input.id)
                        input.id = id.replace(new RegExp(this.placeholder, 'g'), idx);
                    if (input.name)
                        input.name = name.replace(new RegExp(this.placeholder, 'g'), idx);
                }
            }
        }

        // Dispatch change for autosubmit
        this.element.dispatchEvent(new Event('change', {'bubbles':true}));
    }
}

GrowingList.parseDocument(document);
document.addEventListener('partial-content-loaded', event => GrowingList.parseDocument(event.detail));

export default GrowingList;
