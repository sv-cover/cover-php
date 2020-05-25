import autoComplete from '@tarekraafat/autocomplete.js';

/*
 * Base class for Autocomplete Bulma plugin.
 * Acts as a wrapper for https://tarekraafat.github.io/autoComplete.js.
 * Provides Bulma compatible rendering and positioning fixes. Wraps source input
 * element in a div.autocomplete
 */
class AutocompleteBase {
    constructor(options) {
        this.options = options;
        this.resultsListVisible = true;
        this.hasFocus = false;

        // Init autocomplete
        this.element = this.initUi(options.element);
        this.autocomplete = this.initAutocomplete(options.config);
        this.initFocusEvents();

        // Don't submit form on keyboard selection (enter)
        this.sourceElement.addEventListener('keydown', (event) => {
            if (event.key === 'Enter')
                event.preventDefault();
        });
    }

    generateConfig(overrides) {
        // Init default config (in three parts)
        const defaultConfig = {
            selector: () => this.sourceElement,
            threshold: 2,
            highlight: true,
            noResults: () => {
                const result = document.createElement('li');
                result.setAttribute('class', 'no_result');
                result.setAttribute('tabindex', '1');

                if (overrides.noResultsText)
                    result.append(document.createTextNode(overrides.noResultsText));
                else
                    result.append(document.createTextNode('No results'));

                if (this.getResultsListElement())
                    this.getResultsListElement().append(result);
            },
            onSelection: this.handleSelection.bind(this),
        };

        const defaultResultsList = {
            render: true,
            container: this.renderResultsList.bind(this),
            destination: document.body,
            position: 'beforeend',
        };

        const defaultResultItem = {
            content: this.renderResult.bind(this),
        };

        // Override default config
        let config = null;
        if (overrides) {
            config = Object.assign(defaultConfig, overrides);
            config.resultsList = Object.assign(defaultResultsList, overrides.resultsList);
            config.resultItem = Object.assign(defaultResultItem, overrides.resultItem);
        } else {
            config = defaultConfig;
            config.resultsList = defaultResultsList;
            config.resultItem = defaultResultItem;
        }

        return config;
    }

    initAutocomplete(config) {
        // Init autoComplete.js core
        return new autoComplete(this.generateConfig(config));
    }

    initUi(sourceInput) {
        // Create container
        let containerElement = document.createElement('div');
        containerElement.classList.add('autocomplete');

        // Clone source input and turn off browser based autocomplete
        let newSourceInput = sourceInput.cloneNode(true);
        newSourceInput.autocomplete = 'off';

        // Create structure and append to DOM
        containerElement.append(newSourceInput);
        sourceInput.parentNode.replaceChild(containerElement, sourceInput);

        // Allow direct access to the source input from elswehere
        this.sourceElement = newSourceInput;
        return containerElement;
    }

    initFocusEvents() {
        // Show the autocomplete list when source element gets focused
        this.sourceElement.addEventListener('focus', () => {
            this.hasFocus = true;
            this.toggleResultsList(true);
        });
        // Hide the autocomplete list when source element gets unfocused
        this.sourceElement.addEventListener('blur', () => {
            this.hasFocus = false;
            this.toggleResultsList(false);
        });
    }

    handleSelection(feedback) {
        if (this.autocomplete && this.autocomplete.data.key)
            // If key is set, assume object value and select item based on first key
            this.sourceElement.value = feedback.selection.value[this.autocomplete.data.key[0]];
        else if (this.autocomplete)
            // If no key, the assume string value instead
            this.sourceElement.value = feedback.selection.value;
    }

    getResultsListElement() {
        if (this.autocomplete)
            return this.autocomplete.resultsList.view;
        return null;
    }

    renderResult(data, source) {
        source.innerHTML = data.match;
    }

    renderResultsList(source) {
        // Override id and use class instead. Multiple autocompletes on a page should be possible.
        source.removeAttribute('id');
        source.classList.add('autocomplete-list');

        // Monitor the on screen position of the autocomplete
        this.monitorPosition();
    }

    toggleResultsList(isVisible=null) {
        if (isVisible !== null)
            this.resultsListVisible = isVisible;

        if (!this.getResultsListElement())
            // No resultlist to toggle/position
            return;

        if (this.resultsListVisible) {
            // Postion result list correctly
            const bodyRect = document.body.getBoundingClientRect();
            const sourceRect = this.sourceElement.getBoundingClientRect();
            this.getResultsListElement().style.top = sourceRect.bottom - bodyRect.top + 'px';
            this.getResultsListElement().style.left = sourceRect.left - bodyRect.left + 'px';
            this.getResultsListElement().style.width = sourceRect.width + 'px';
            // Toggle
            this.getResultsListElement().hidden = false;
        } else {
            this.getResultsListElement().hidden = true;
        }
    }

    monitorPosition() {
        let ticking = false;

        // Update resultslist position on scroll.
        // Prevent updating too much using ticking.
        // Listener is registered on capture, to also monitor scroll events inside elements correctly
        window.addEventListener('scroll', (event) => {
          if (!ticking) {
            window.requestAnimationFrame(() => {
                this.toggleResultsList();
                ticking = false;
            });

            ticking = true;
          }
        }, {capture: true});

        // Only show results list if the source input is actually in frame
        const sourceElementObserver = new IntersectionObserver(
            (entries, observer) => {
                for (const entry of entries)
                    this.resultsListVisible = this.hasFocus && entry.isIntersecting;
                this.toggleResultsList();
            }
        );

        sourceElementObserver.observe(this.sourceElement);

        // Apply initial settings
        this.toggleResultsList();
    }
}

export default AutocompleteBase;
