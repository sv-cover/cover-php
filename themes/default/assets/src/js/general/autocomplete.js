import autoComplete from '@tarekraafat/autocomplete.js';

class Autocomplete {
    constructor(options) {
        this.element = this.initUi(options.element);
        this.autocomplete = this.initAutocomplete(options.config);
        this.resultsListVisible = true;
        this.initFocusEvents();
    }

    generateConfig(overrides) {
        const defaultConfig = {
            selector: () => this.sourceElement,
            threshold: 2,
            highlight: true,
            noResults: () => {
                const result = document.createElement('li');
                result.setAttribute('class', 'no_result');
                result.setAttribute('tabindex', '1');
                result.innerHTML = 'No Results';
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
        return new autoComplete(this.generateConfig(config));
    }

    initUi(sourceInput) {
        // Create container
        let containerElement = document.createElement('div');
        containerElement.classList.add('autocomplete');

        let newSourceInput = sourceInput.cloneNode(true);
        newSourceInput.type = 'hidden';
        newSourceInput.removeAttribute('id');
        newSourceInput.removeAttribute('class');
        newSourceInput.classList.add('autocomplete-target'); 

        containerElement.append(newSourceInput);

        sourceInput.parentNode.replaceChild(containerElement, sourceInput);

        this.sourceElement = newSourceInput;
        return containerElement;
    }

    initFocusEvents() {
        this.sourceElement.addEventListener('focus', () => this.toggleResultsList(true));
        this.sourceElement.addEventListener('blur', () => this.toggleResultsList(false));
    }

    handleSelection(feedback) {
        if (this.autocomplete)
            this.sourceElement.value = feedback.selection.value[this.autocomplete.data.key[0]];
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
        source.removeAttribute('id');
        source.classList.add('autocomplete-list');

        this.updateResultsListPosition();
    }

    toggleResultsList(isVisible) {
        this.resultsListVisible = isVisible;

        if (!this.getResultsListElement())
            return;

        if (isVisible)
            this.getResultsListElement().hidden = false;
        else
            this.getResultsListElement().hidden = true;
    }

    updateResultsListPosition() {
        let ticking = false;

        const updatePosition = () => {
            if (this.resultsListVisible && this.getResultsListElement()) {
                const bodyRect = document.body.getBoundingClientRect();
                const sourceRect = this.sourceElement.getBoundingClientRect();
                this.getResultsListElement().style.top = sourceRect.bottom - bodyRect.top + 'px';
                this.getResultsListElement().style.left = sourceRect.left - bodyRect.left + 'px';
                this.getResultsListElement().style.width = sourceRect.width + 'px';
            }
            this.toggleResultsList(this.resultsListVisible);
        };

        window.addEventListener('scroll', (event) => {
          if (!ticking) {
            window.requestAnimationFrame(() => {
              updatePosition();
              ticking = false;
            });

            ticking = true;
          }
        }, {capture: true});

        const sourceElementObserver = new IntersectionObserver(
            (entries, observer) => {
                for (const entry of entries)
                    this.resultsListVisible = entry.isIntersecting;
                updatePosition();
            }
        );

        sourceElementObserver.observe(this.sourceElement);

        updatePosition();
    }
}

export default Autocomplete;
