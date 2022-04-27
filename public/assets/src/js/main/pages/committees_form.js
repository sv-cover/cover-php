import {Bulma} from 'cover-style-system/src/js';


class CommitteesForm {
    /**
     * Get the root class this plugin is responsible for.
     * This will tell the core to match this plugin to an element with a .modal class.
     * @returns {string} The class this plugin is responsible for.
     */
    static getRootClass() {
        return 'committees-form';
    }


    /**
     * Handle parsing the DOMs data attribute API.
     * @param {HTMLElement} element The root element for this instance
     * @return {undefined}
     */
    static parse(element) {
        new CommitteesForm({
            element: element,
        });
    }

    constructor(options) {
        this.element = options.element;

        // Init buttons
        this.element.querySelector('button.add-button').addEventListener('click', this.handleAdd.bind(this));
        this.element.querySelectorAll('button.remove-button').forEach( 
            element => element.addEventListener('click', this.handleRemove.bind(this))
        );
    }

    handleAdd() {
        // Collect info
        const memberId = this.element.querySelector('input.committee-member-name.autocomplete-target').value;
        const memberName = this.element.querySelector('input.committee-member-name.autocomplete-source').value;
        const memberFunction = this.element.querySelector('input.committee-member-function').value;

        // Init template
        const template = this.element.querySelector('#member-function-row');
        let clone = template.content.firstElementChild.cloneNode(true);
        
        // Fill name
        let nameTdElement = clone.querySelector('.name');
        nameTdElement.append(document.createTextNode(memberName));

        // Fill function
        let functionInputElement = clone.querySelector('.function');
        functionInputElement.name = `members[${memberId}]`;
        functionInputElement.value = memberFunction;

        // Init button
        let buttonElement = clone.querySelector('.button.remove-button');
        buttonElement.addEventListener('click', this.handleRemove.bind(this));

        // Add to table body
        let tbodyElement = this.element.querySelector('tbody.member-function-list');
        tbodyElement.append(clone);
    }

    handleRemove(event) {
        event.target.closest('tr').remove();
    }
}


Bulma.registerPlugin('committees-form', CommitteesForm);

export default CommitteesForm;
