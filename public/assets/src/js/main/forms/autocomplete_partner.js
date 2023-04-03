import Autocomplete from './autocomplete';

/**
 * Plugin to autocomplete member names to member ID's. Member ID remains hidden from the user.
 */
class AutocompleteCompany extends Autocomplete {
    static parseDocument(context) {
        const elements = context.querySelectorAll('[data-autocomplete=partner_name]');

        for (let element of elements) {
            new AutocompleteCompany({
                element: element,
                type: 'url',
                src: element.dataset.autocompleteSrc,
                srcKey: 'name',
            });
        }
    }

    initUi(sourceInput) {
        const parent = sourceInput.parentElement;

        this.partnerNameInput = sourceInput.cloneNode(true);
        this.partnerNameInput.type = 'hidden';
        this.partnerNameInput.removeAttribute('id');
        this.partnerNameInput.removeAttribute('data-autocomplete');
        this.partnerNameInput.removeAttribute('data-autocomplete-src');
        this.partnerNameInput.removeAttribute('class');
        parent.append(this.partnerNameInput);

        this.partnerIdInput = parent.querySelector('[data-partner-id-field]');

        if (this.partnerIdInput && !sourceInput.value)
            sourceInput.value = this.partnerIdInput.dataset.partnerName;

        sourceInput.removeAttribute('name');
        const container = super.initUi(sourceInput);

        this.sourceElement.addEventListener('input', this.handleInput.bind(this));
        return container;
    }

    handleInput(event) {
        if (typeof this.partnerIdInput !== 'undefined')
            this.partnerIdInput.value = '';
        this.partnerNameInput.value = event.target.value;
    }

    handleSelection(event) {
        // Place ID and name in their corresponding input elements
        if (typeof this.partnerIdInput !== 'undefined') {
            this.partnerIdInput.value = event.detail.selection.value.id;
            this.partnerNameInput.value = '';
        }
        this.sourceElement.value = event.detail.selection.value.name;
    }
}

AutocompleteCompany.parseDocument(document);
document.addEventListener('partial-content-loaded', event => AutocompleteCompany.parseDocument(event.detail));

export default AutocompleteCompany;
