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

        // Init autocomplete
        this.element = this.initUi(options.element);
        if (options.config)
            this.autocomplete = this.initAutocomplete(options.config);
        else
            this.autocomplete = this.initAutocomplete({});

        this.sourceElement.addEventListener('selection', this.handleSelection.bind(this));
        this.sourceElement.addEventListener('open', this.monitorPosition.bind(this), {once: true});
    }

    generateConfig(overrides) {
        // Init default config (in three parts)
        const defaultConfig = {
            selector: () => this.sourceElement,
            threshold: 2,
            highlight: true,
            diacritics: true,
            events: {
                input: {
                    focus: this.handleFocus.bind(this),
                },
            },
        };

        const defaultResultsList = {
            element: this.renderResultsList.bind(this, overrides),
            class: 'autocomplete-list',
            noResults: true,
            destination: () => document.body,
            position: 'beforeend',
        };

        const defaultResultItem = {
            class: 'autocomplete-result',
            highlight: 'is-highlighted',
            selected: 'is-selected',
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

    handleSelection(event) {
        if (this.autocomplete && this.autocomplete.data.keys)
            // If key is set, assume object value and select item based on first key
            this.sourceElement.value = event.detail.selection.value[this.autocomplete.data.keys[0]];
        else if (this.autocomplete)
            // If no key, the assume string value instead
            this.sourceElement.value = event.detail.selection.value;
    }

    handleFocus() {
        // Should we show?
        const trigger = this.autocomplete.trigger?.();
        const inputValue = this.autocomplete.input.value;
        if (trigger || inputValue.length) {
            // Position just to be sure. May not be necessary…
            this.positionResultsList(true);
            this.autocomplete.start();
        }
    }

    renderResultsList(overrides, list, data) {
        if (!data.results.length)
            this.renderNoResults(overrides, list, data);
    }

    renderNoResults(overrides, list, data) {
        const result = document.createElement('li');
        result.classList.add('autocomplete-no-result');
        result.setAttribute('tabindex', '1');

        if (overrides.noResultsText)
            result.append(document.createTextNode(overrides.noResultsText));
        else
            result.append(document.createTextNode(`No results for “${data.query}”`));

        list.append(result);
    }

    positionResultsList(force) {
        if (!this.autocomplete)
            // No resultlist to position
            return;

        if (force || (this.autocomplete.isOpen && this.resultsListVisible)) {
            const bodyRect = document.body.getBoundingClientRect();
            const sourceRect = this.sourceElement.getBoundingClientRect();
            this.autocomplete.list.style.top = sourceRect.bottom - bodyRect.top + 'px';
            this.autocomplete.list.style.left = sourceRect.left - bodyRect.left + 'px';
            this.autocomplete.list.style.width = sourceRect.width + 'px';
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
                this.positionResultsList();
                ticking = false;
            });

            ticking = true;
          }
        }, {capture: true});

        // Only show results list if the source input is actually in frame
        const sourceElementObserver = new IntersectionObserver(
            (entries, observer) => {
                for (const entry of entries)
                    this.resultsListVisible = entry.isIntersecting;
                this.positionResultsList();
            }
        );

        sourceElementObserver.observe(this.sourceElement);
        window.addEventListener('resize', this.positionResultsList.bind(this));

        // Force initial positioning
        this.positionResultsList();
    }
}

export default AutocompleteBase;
