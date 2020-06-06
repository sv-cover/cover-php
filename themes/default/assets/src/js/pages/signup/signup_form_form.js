import {Bulma} from 'cover-style-system/src/js';
import Sortable from 'sortablejs';


class SignupFormForm {
    static parseDocument(context) {
        const elements = context.querySelectorAll('.signup-form-form');

        Bulma.each(elements, element => {
            new SignupFormForm({
                element: element,
            });
        });
    }

    constructor(options) {
        this.element = options.element;
        this.fieldListElement = options.element.querySelector('.signup-form-field-list');
        this.addFieldForm = options.element.querySelector('.signup-form-field-form');
        this.setupEvents();
    }

    setupEvents() {
        this.addFieldForm.addEventListener('submit', this.handleAddField.bind(this));
    }

    async handleAddField(event) {
        event.preventDefault();

        const url = this.addFieldForm.dataset.asyncAction;
        const data = new FormData(this.addFieldForm);
        const init = {
            method: 'POST',
            body: new URLSearchParams(data),
        };

        const response = await fetch(url, init);
        const text = await response.text();

        const doc = (new DOMParser()).parseFromString(text, 'text/html');
        const field = doc.querySelector('.signup-form-field');

        this.fieldListElement.append(field);

        document.dispatchEvent(new CustomEvent('partial-content-loaded', { bubbles: true, detail: field }));
    }
}


SignupFormForm.parseDocument(document);

export default SignupFormForm;
