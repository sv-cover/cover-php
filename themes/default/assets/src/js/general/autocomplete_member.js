import {Bulma} from 'cover-style-system/src/js';
import autoComplete from '@tarekraafat/autocomplete.js';
import Autocomplete from './autocomplete';

const AUTOCOMPLETE_MEMBER_URL = '/almanak.php';
const AUTOCOMPLETE_MEMBER_LIMIT = 15;

class AutocompleteMember extends Autocomplete{
    static parseDocument(context) {
        const elements = context.querySelectorAll('[data-autocomplete=member_id]');

        Bulma.each(elements, element => {
            new AutocompleteMember({
                element: element,
            });
        });
    }


    initAutocomplete(config) {
        return new autoComplete(this.generateConfig({
            data: {
                src: this.fetchMembers.bind(this),
                key: ['name'],
                cache: false
            },
            searchEngine: 'loose',
        }));
    }

    initUi(memberIdInput) {
        // Create container
        let containerElement = document.createElement('div');
        containerElement.classList.add('autocomplete');

        let nameInputElement = document.createElement('input');
        memberIdInput.classList.forEach(cls => nameInputElement.classList.add(cls));
        nameInputElement.type = 'text';
        nameInputElement.autocomplete = 'off';
        nameInputElement.placeholder = memberIdInput.placeholder;
        nameInputElement.id = memberIdInput.id;
        nameInputElement.classList.add('autocomplete-source');

        if (memberIdInput.dataset.name)
            nameInputElement.value = memberIdInput.dataset.name;

        let newMemberIdInput = memberIdInput.cloneNode(true);
        newMemberIdInput.type = 'hidden';
        newMemberIdInput.removeAttribute('id');
        newMemberIdInput.removeAttribute('class');
        newMemberIdInput.classList.add('autocomplete-target'); 

        containerElement.append(newMemberIdInput);
        containerElement.append(nameInputElement);

        memberIdInput.parentNode.replaceChild(containerElement, memberIdInput);

        this.sourceElement = nameInputElement;
        this.targetElement = newMemberIdInput;
        return containerElement;
    }

    async fetchMembers() {
        const query = this.sourceElement.value;

        if (this.autocomplete && query.length <= this.autocomplete.threshold)
            return;

        const url = `${AUTOCOMPLETE_MEMBER_URL}?search=${query}&limit=${AUTOCOMPLETE_MEMBER_LIMIT}`;

        const init = {
            'method': 'GET',
            'headers': { 'Accept': 'application/json' },
        };

        const source = await fetch(url, init);
        const data = await source.json();

        return data;
    }

    handleSelection(feedback) {
        this.targetElement.value = feedback.selection.value.id;
        this.sourceElement.value = feedback.selection.value.name;
    }

    renderResult(data, source) {
        source.classList.add('profile', 'media');

        let photoElement = document.createElement('figure');
        photoElement.classList.add('image', 'is-32x32', 'media-left');

        let imgElement = document.createElement('img');
        imgElement.classList.add('is-rounded');
        imgElement.src = `/foto.php?lid_id=${data.value.id} &format=square&width=64`;

        photoElement.append(imgElement);
        
        let nameElement = document.createElement('div');
        nameElement.classList.add('name');
        nameElement.innerHTML = data.match;

        let startingYearElement = document.createElement('div');
        startingYearElement.classList.add('starting-year', 'is-size-7', 'has-text-grey');
        startingYearElement.append(document.createTextNode(data.value.starting_year));
        

        let containerElement = document.createElement('div');
        containerElement.classList.add('media-content');
        containerElement.append(nameElement);
        containerElement.append(startingYearElement);

        source.append(photoElement);
        source.append(containerElement);
    }
}

AutocompleteMember.parseDocument(document);
document.addEventListener('partial-content-loaded', event => AutocompleteMember.parseDocument(event.detail));

export default AutocompleteMember;
