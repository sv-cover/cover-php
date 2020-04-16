import {Bulma} from 'cover-style-system/src/js';
import autoComplete from '@tarekraafat/autocomplete.js';
import Autocomplete from './autocomplete';


class AutocompleteData extends Autocomplete{
    static parseDocument(context) {
        const elements = context.querySelectorAll('[data-autocomplete=data]');

        Bulma.each(elements, element => {
            new AutocompleteData({
                element: element,
            });
        });
    }


    initAutocomplete(config) {
        let options = {
            data: {
                src: JSON.parse(this.sourceElement.dataset.autocompleteSrc),
                cache: true
            },
            threshold: 0,
            searchEngine: 'loose',
        };

        if (this.sourceElement.dataset.autocompleteSearchEngine)
            options.searchEngine = this.sourceElement.dataset.autocompleteSearchEngine;

        if (this.sourceElement.dataset.autocompleteNoResults)
            options.noResultsText = this.sourceElement.dataset.autocompleteNoResults;
        else
            options.noResults = () => {};

        return new autoComplete(this.generateConfig(options));
    }
}

AutocompleteData.parseDocument(document);
document.addEventListener('partial-content-loaded', event => AutocompleteData.parseDocument(event.detail));

export default AutocompleteData;
