import {Bulma} from 'cover-style-system/src/js';


class SignupFormField {
    static parseDocument(context) {
        const elements = context.querySelectorAll('.signup-form-field');

        Bulma.each(elements, element => {
            new SignupFormField({
                element: element,
            });
        });

        if (context.matches && context.matches('.signup-form-field')) {
            new SignupFormField({
                element: context,
            });
        }
    }

    constructor(options) {
        this.element = options.element;

        const deleteButtons = this.element.querySelectorAll('.card-header .signup-form-field-delete-button');
        for (let element of deleteButtons)
            element.hidden = false;

        const sortHandles = this.element.querySelectorAll('.sortable-handle');
        for (let element of sortHandles)
            element.hidden = false;
    }
}


SignupFormField.parseDocument(document);
document.addEventListener('partial-content-loaded', event => SignupFormField.parseDocument(event.detail));

export default SignupFormField;
