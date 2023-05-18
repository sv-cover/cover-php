import {Bulma} from 'cover-style-system/src/js';
import AutocompleteBase from './autocomplete_base';

/**
 * Autocomplete plugin for simple autocomplete elements. Supports the following data options:
 *
 * autocomplete = "data" | "url"
 * autocomplete-src = [JSON Array] if autocomplete = "data", can be array of strings or array of objects
                      or
                      [URL String] if autocomplete = "url", search query will be appended at the end
 * autocomplete-src-key = string, key to identify value in object type datasource
 * autocomplete-search-engine = "strict" | "loose" (default)
 * autocomplete-no-results = string, will be displayed in case of no results. default: empty (nothing will be displayed)
 * autocomplete-threshold = number, threshold for displaying suggestions. default: 1
 * autocomplete-max-results = number, maximum number of suggestions to display at once. default: 5
 * autocomplete-mock-select = boolean data option to make autocomplete behave like a select that allows custom values
 */
class Autocomplete extends AutocompleteBase {
    static parseDocument(context) {
        const elements = context.querySelectorAll('[data-autocomplete=json],[data-autocomplete=url]');

        Bulma.each(elements, element => {
            let options = {
                element: element,
                type: element.dataset.autocomplete,
                src: element.dataset.autocompleteSrc,
            };

            // Only include optional options if present
            if (element.dataset.autocompleteSrcKey)
                options.srcKey = element.dataset.autocompleteSrcKey;

            if (element.dataset.autocompleteSearchEngine)
                options.searchEngine = element.dataset.autocompleteSearchEngine;

            if (element.dataset.autocompleteNoResults)
                options.noResultsText = element.dataset.autocompleteNoResults;

            if (element.dataset.autocompleteThreshold)
                options.threshold = element.dataset.autocompleteThreshold;

            if (element.dataset.autocompleteMaxResults)
                options.maxResults = element.dataset.autocompleteMaxResults;

            options.mockSelect = (
                element.dataset.autocompleteMockSelect != null 
                && element.dataset.autocompleteMockSelect.toLowerCase() !== 'false'
            );

            new Autocomplete(options);
        });
    }

    initAutocomplete(config) {
        // Simple options
        let options = {
            threshold: this.options.threshold || 1,
            searchEngine: this.options.searchEngine || 'loose',
            resultsList: {
                maxResults: this.options.maxResults || 5,
            }
        };

        // Data source
        if (this.options.type === 'json')
            options.data = {
                src: JSON.parse(this.options.src),
                cache: true
            };
        else if (this.options.type === 'url')
            options.data = {
                src: this.fetchData.bind(this, this.options.src),
                cache: false // Caching should be disabled for remote sources
            };
        else {
            console.warn(`Unsupported autocomplete type "${this.options.type}".`);
            return;
        }

        if (this.options.srcKey)
            options.data.keys = [this.options.srcKey];

        // No results
        if (this.sourceElement.dataset.autocompleteNoResults)
            options.noResultsText = this.sourceElement.dataset.autocompleteNoResults;
        else
            options.resultsList.noResults = false;

        if (this.options.mockSelect) {
            options.trigger = () => true;
            options.threshold = 0;
            if (!this.options.searchEngine)
                options.searchEngine = (query, record) => {
                    return query ? this.autocomplete.search(query, record) : record;
                };
        }

        return super.initAutocomplete(options);
    }

    async fetchData(baseUrl, query) {
        // Don't fetch data if the query is too short
        if (this.autocomplete && query.length < this.autocomplete.threshold)
            return [];

        // Prepare request
        const url = baseUrl + query;
        const init = {
            'method': 'GET',
            'headers': { 'Accept': 'application/json' },
        };

        // Execute request
        const source = await fetch(url, init);
        const data = await source.json();

        return data;
    }
}

Autocomplete.parseDocument(document);
document.addEventListener('partial-content-loaded', event => Autocomplete.parseDocument(event.detail));

export default Autocomplete;
