import {Bulma} from 'cover-style-system/src/js';

/**
 * AutoSubmitForm plugin to submit forms when their contents have changed.
 * PROVIDES NO FEEDBACK AFTER SUBMISSION
 * Supports the following data options:
 *
 * submit-extra-data = [JSON Object] contains fields to add to data before submission
 * async-action = action to submit form, if different from fallback
 * 
 * Disables buttons that submit the form if they have the boolean data option auto-submit-hide
 */
class AutoSubmitForm {
    static parseDocument(context) {
        const elements = context.querySelectorAll('form.auto-submit');

        Bulma.each(elements, element => {
            new AutoSubmitForm({
                element: element,
                // extraData is always an object
                extraData: JSON.parse(element.dataset.autoSubmitExtraData || null) || {},
                // Buttons may exist outside of field, but assume they're always inside context
                buttons: Array.from(context.querySelectorAll('button')).filter(btn => btn.form === element),
            });
        });

    }

    constructor(options) {
        this.element = options.element;
        this.extraData = options.extraData;
        this.buttons = options.buttons;

        this.initSwitches();
        this.initButtons();
        this.setupEvents();
    }

    initButtons() {
        // Disable buttons that are allowed to be disabled by boolean data attribute
        for (let button of this.buttons) {
            if (button.dataset.autoSubmitHide != null && button.dataset.autoSubmitHide.toLowerCase() !== 'false')
                button.hidden = true;
        }
    }

    /**
     * Turns checkboxes into toggle switches.
     * Autosubmitting a form will make the settings apply instantly, and therefore 
     * should be a switch in many cases. Can be overriden when a checkbox is always
     * desired, by adding the attribute `data-auto-submit-no-switch`.
     * 
     * See https://uxplanet.org/checkbox-vs-toggle-switch-7fc6e83f10b8
     */
    initSwitches() {
        const checkboxes = this.element.querySelectorAll('label.checkbox:not([data-auto-submit-no-switch])');

        Bulma.each(checkboxes, checkboxLabel => {
            // Bulma's structure is "label.checkbox > input[type=checkbox]"
            const checkbox = checkboxLabel.querySelector('input[type=checkbox]');
            
            if (!checkbox) {
                throw new Error('label.checkbox doesn\'t contain checkbox');
            }

            // Switch structure is "input.switch[type=checkbox] + label"
            const newCheckbox = checkbox.cloneNode(true);
            newCheckbox.classList.add('switch', 'is-rounded', 'is-rtl', 'is-full-width');

            checkboxLabel.classList.remove('checkbox');

            checkbox.remove();
            checkboxLabel.before(newCheckbox);
        });
    }

    setupEvents() {
        this.element.addEventListener('change', this.handleChange.bind(this));
        this.element.addEventListener('keydown', this.handleKeyDown.bind(this));
    }

    handleChange(event) {
        // Don't update if SortableJS change event
        if (event.newIndex === undefined)
            this.submit();
    }

    handleKeyDown(event) {
        // Don't use default submit on enter. That's it.
        if (event.key === 'Enter' && event.target.tagName.toLowerCase() !== 'textarea')
            event.preventDefault();    
    }

    submit() {
        const url = this.element.dataset.asyncAction || this.element.action;
        
        // Append extra data to formdata
        const data = new FormData(this.element);
        for (let key in this.extraData)
            data.append(key, this.extraData[key]);

        // Prepare and submit
        const init = {
            method: this.element.method,
            body: new URLSearchParams(data),
        };
        fetch(url, init).catch(error => console.error(error));
    }
}

AutoSubmitForm.parseDocument(document);
document.addEventListener('partial-content-loaded', event => AutoSubmitForm.parseDocument(event.detail));

export default AutoSubmitForm;
