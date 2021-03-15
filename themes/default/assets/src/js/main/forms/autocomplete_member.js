import {Bulma} from 'cover-style-system/src/js';
import AutocompleteBase from './autocomplete_base';

const AUTOCOMPLETE_MEMBER_URL = '/almanak';
const AUTOCOMPLETE_MEMBER_LIMIT = 15;

/**
 * Plugin to autocomplete member names to member ID's. Member ID remains hidden from the user.
 */
class AutocompleteMember extends AutocompleteBase {
    static parseDocument(context) {
        const elements = context.querySelectorAll('[data-autocomplete=member_id]');

        Bulma.each(elements, element => {
            new AutocompleteMember({
                element: element,
                keepIdField: true,
            });
        });
    }

    initAutocomplete(config) {
        // Set config
        config.data = {
            src: this.fetchMembers.bind(this),
            key: ['name'],
            cache: false
        };
        config.searchEngine = 'loose';
        if (!config.noResultsText)
            config.noResultsText = 'No members found :(';
        return super.initAutocomplete(config);
    }

    initUi(memberIdInput) {
        // Create container element
        let containerElement = document.createElement('div');
        containerElement.classList.add('autocomplete');

        // Create source input element, copy some properties from original input element
        let nameInputElement = document.createElement('input');
        memberIdInput.classList.forEach(cls => nameInputElement.classList.add(cls));
        nameInputElement.type = 'text';
        nameInputElement.autocomplete = 'off';
        nameInputElement.id = memberIdInput.id;
        nameInputElement.classList.add('autocomplete-source');

        if (memberIdInput instanceof HTMLInputElement)
            nameInputElement.placeholder = memberIdInput.placeholder;
        else
            nameInputElement.placeholder = 'Type a name';

        if (memberIdInput.dataset.name)
            nameInputElement.value = memberIdInput.dataset.name;

        let newMemberIdInput;
        if (!('keepIdField' in this.options) || this.options.keepIdField) {
            // Convert original input element to hidden element. This is used to actually submit the data.
            newMemberIdInput = memberIdInput.cloneNode(true);
            newMemberIdInput.type = 'hidden';
            newMemberIdInput.removeAttribute('id');
            newMemberIdInput.classList.add('autocomplete-target');
    
            containerElement.append(newMemberIdInput);
        }

        // Build component
        containerElement.append(nameInputElement);

        // Place in DOM
        memberIdInput.parentNode.replaceChild(containerElement, memberIdInput);

        // Allow direct access to the source and target inputs from elswehere
        this.sourceElement = nameInputElement;
        if (typeof newMemberIdInput !== 'undefined')
            this.targetElement = newMemberIdInput;

        return containerElement;
    }

    async fetchMembers() {
        const query = this.sourceElement.value;

        // Don't fetch data if the query is too short
        if (this.autocomplete && query.length <= this.autocomplete.threshold)
            return;

        // Prepare request
        const url = `${AUTOCOMPLETE_MEMBER_URL}?search=${query}&limit=${AUTOCOMPLETE_MEMBER_LIMIT}`;
        const init = {
            'method': 'GET',
            'headers': { 'Accept': 'application/json' },
        };

        // Execute request
        const source = await fetch(url, init);
        const data = await source.json();

        return data;
    }

    handleSelection(feedback) {
        // Place ID and name in their corresponding input elements
        if (typeof this.targetElement !== 'undefined')
            this.targetElement.value = feedback.selection.value.id;
        this.sourceElement.value = feedback.selection.value.name;
    }

    renderResult(data, source) {
        // Use a media element for result
        source.classList.add('profile', 'media');

        // Create .image Bulma element (also serves as media-left)
        let photoElement = document.createElement('figure');
        photoElement.classList.add('image', 'is-32x32', 'media-left');

        // Create img element
        let imgElement = document.createElement('img');
        imgElement.classList.add('is-rounded');
        imgElement.src = `/profile_picture/${data.value.id}/square/64`;

        // Append img to .image
        photoElement.append(imgElement);
        
        // Create element for name. Use data.match to highlight matching characters.
        let nameElement = document.createElement('div');
        nameElement.classList.add('name');
        nameElement.innerHTML = data.match;

        // Create element for starting year
        let startingYearElement = document.createElement('div');
        startingYearElement.classList.add('starting-year', 'is-size-7', 'has-text-grey');
        startingYearElement.append(document.createTextNode(data.value.starting_year));
        
        // Build media-content
        let containerElement = document.createElement('div');
        containerElement.classList.add('media-content');
        containerElement.append(nameElement);
        containerElement.append(startingYearElement);

        // Build result element
        source.append(photoElement);
        source.append(containerElement);
    }
}

AutocompleteMember.parseDocument(document);
document.addEventListener('partial-content-loaded', event => AutocompleteMember.parseDocument(event.detail));

export default AutocompleteMember;
