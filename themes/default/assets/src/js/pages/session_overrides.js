import {Bulma, Collapse} from 'cover-style-system/src/js';


class SessionOverridesForm {
    /**
     * Get the root class this plugin is responsible for.
     * This will tell the core to match this plugin to an element with a .modal class.
     * @returns {string} The class this plugin is responsible for.
     */
    static getRootClass() {
        return 'session-overrides-form';
    }


    /**
     * Handle parsing the DOMs data attribute API.
     * @param {HTMLElement} element The root element for this instance
     * @return {undefined}
     */
    static parse(element) {
        new SessionOverridesForm({
            element: element,
        });
    }

    constructor(options) {
        this.element = options.element;
        const fieldsetToggles = this.element.querySelectorAll('input[type=checkbox][data-target]');

        for (const toggle of fieldsetToggles)
            this.initFieldset(toggle)
    }

    initFieldset(toggle) {
        let containerElement = document.createElement('div');
        containerElement.classList.add('collapse');

        let fieldset = this.element.querySelector(toggle.dataset.target);
        fieldset.parentNode.replaceChild(containerElement, fieldset);

        fieldset.classList.add('collapse-content');
        containerElement.append(fieldset);

        let collapse = new Collapse({element: containerElement});

        this.handleFieldsetToggle(fieldset, collapse, {target: toggle});
        toggle.addEventListener('change', this.handleFieldsetToggle.bind(this, fieldset, collapse));
    }

    handleFieldsetToggle(fieldset, collapse, event) {
        fieldset.disabled = !event.target.checked;
        if (event.target.checked)
            collapse.show();
        else
            collapse.hide();
    }
}


Bulma.registerPlugin('preview-loader', SessionOverridesForm);

export default SessionOverridesForm;
