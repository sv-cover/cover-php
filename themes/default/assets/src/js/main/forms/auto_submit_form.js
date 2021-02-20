import {Bulma} from 'cover-style-system/src/js';

/**
 * AutoSubmitForm plugin to submit forms when their contents have changed.
 * PROVIDES NO FEEDBACK AFTER SUBMISSION
 * Supports the following data options:
 *
 * submit-extra-data = [JSON Object] contains fields to add to data before submission
 * async-action = action to submit form, if different from fallback
 * use-native-submit = Boolean attribute. Uses native submit instead of ajax if present.
 * allow-submit-on-enter = Boolean attribute. Uses native submit instead of ajax if present.
 * visibility-selector = selector for elements outside the form that needs visibility changed based on auto submit
 * 
 * Disables buttons that submit the form if they have the boolean data option auto-submit-hidden
 */
class AutoSubmitForm {
    static parseDocument(context) {
        const elements = context.querySelectorAll('form.auto-submit, form[data-auto-submit]');

        Bulma.each(elements, element => {
            new AutoSubmitForm({
                element: element,
                // extraData is always an object
                extraData: JSON.parse(element.dataset.autoSubmitExtraData || null) || {},
                // Buttons may exist outside of field, but assume they're always inside context
                buttons: Array.from(context.querySelectorAll('button')).filter(btn => btn.form === element),
                externalElements: element.dataset.visibilitySelector ? Array.from(context.querySelectorAll(element.dataset.visibilitySelector)) : [],
                useNativeSubmit: (element.dataset.useNativeSubmit != null
                    && element.dataset.useNativeSubmit.toLowerCase() !== 'false'),
                allowSubmitOnEnter: (element.dataset.allowSubmitOnEnter != null
                    && element.dataset.allowSubmitOnEnter.toLowerCase() !== 'false'),
            });
        });

    }

    constructor(options) {
        this.options = options;
        this.element = options.element;
        this.extraData = options.extraData;
        this.buttons = options.buttons;
        this.externalElements = options.externalElements;

        this.initSwitches();
        this.initVisibility();
        this.setupEvents();
    }

    initVisibility() {
        // Disable buttons that are allowed to be disabled by boolean data attribute (include buttons outside of the form)
        for (let button of this.buttons) {
            if (button.dataset.autoSubmitHidden != null && button.dataset.autoSubmitHidden.toLowerCase() !== 'false')
                button.hidden = true;
        }

        for (let element of this.externalElements) {
            if (element.dataset.autoSubmitHidden != null && element.dataset.autoSubmitHidden.toLowerCase() !== 'false')
                element.hidden = true;
            if (element.dataset.autoSubmitVisible != null && element.dataset.autoSubmitVisible.toLowerCase() !== 'false')
                element.hidden = false;
        }

        this.element.querySelectorAll('[data-auto-submit-hidden]').forEach(element => element.hidden = true);
        this.element.querySelectorAll('[data-auto-submit-visible]').forEach(element => element.hidden = false);
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
        if (!this.options.allowSubmitOnEnter && event.key === 'Enter' && event.target.tagName.toLowerCase() !== 'textarea')
            event.preventDefault();    
    }

    submit() {
        if (this.options.useNativeSubmit) {
            this.element.submit();
            return;
        }

        const url = this.element.dataset.asyncAction || this.element.getAttribute('action');
        
        // Append extra data to formdata
        let data = new FormData(this.element);
        for (let key in this.extraData)
            data.append(key, this.extraData[key]);

        for (let el of this.element)
            if (el.dataset.autoSubmitExclude != null && el.dataset.autoSubmitExclude.toLowerCase() !== 'false')
                data.delete(el.name);

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
