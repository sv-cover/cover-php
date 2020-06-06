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

        const deleteButtons = this.element.querySelectorAll('.card-header .signup-form-field-delete-button');
        for (let element of deleteButtons)
            element.hidden = false;
    }
}


SignupFormForm.parseDocument(document);

export default SignupFormForm;
