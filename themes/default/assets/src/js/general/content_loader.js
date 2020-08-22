import Bulma from '@vizuaalog/bulmajs/src/core';
import Plugin from '@vizuaalog/bulmajs/src/plugin';


class ContentLoader extends Plugin {
    constructor(options) {
        super(options);

        this.src = options.src;
        this.dest = this.getDest(options);

        this.loadContent().catch(this.displayError.bind(this));
    }

    async loadContent() {
        this.initFallback();

        const doc = await this.fetchDocument();
        const partial = doc.querySelector(this.getSrcSelector());

        this.cancelFallback();
        this.dest.replaceWith(partial);

        if (this.options.onComplete)
            this.options.onComplete(partial);

        // Make sure all JS is applied to partial
        document.dispatchEvent(new CustomEvent('partial-content-loaded', { bubbles: true, detail: partial }));
    }

    initFallback() {
        const spinnerDelay = this.option('spinnerDelay', .75 * 1000);
        const fallbackDelay = this.option('fallbackDelay', 3 * 1000);

        this.fallbackTimeout = setTimeout(() => {
            this.displaySpinner();
            this.fallbackTimeout = setTimeout(() => this.displayFallback(), fallbackDelay);
        }, spinnerDelay);
    }

    cancelFallback() {
        clearTimeout(this.fallbackTimeout);
    }

    clearDest() {
        while (this.dest.firstChild)
            this.dest.removeChild(this.dest.firstChild);
    }

    displayError(message) {
        this.cancelFallback();

        let title = Bulma.createElement('h2', 'title');
        title.append('Oops! something went wrong…');

        let p1 = Bulma.createElement('p');
        p1.append(message);

        this.clearDest();
        this.dest.append(title);
        this.dest.append(p1);
    }

    displayFallback() {
        let message = Bulma.createElement('p', ['has-text-centered', 'level-item']);
        message.append(this.option('fallbackMessage', 'Loading takes longer than expected…'));

        let messageWrapper = Bulma.createElement('div', 'level');
        messageWrapper.append(message);

        this.clearDest();
        this.dest.append(messageWrapper);
    }

    displaySpinner() {
        let icon = Bulma.createElement('i', ['fas', 'fa-circle-notch', 'fa-spin', 'fa-3x']);
        let spinner = Bulma.createElement('span', ['icon', 'is-large', 'level-item', 'has-text-light']);
        spinner.append(icon);

        let spinnerWrapper = Bulma.createElement('div', 'level');
        spinnerWrapper.append(spinner);

        this.clearDest();
        this.dest.append(spinnerWrapper);
    }

    async fetchDocument() {
        let url, init;
        const type = this.src.tagName.toLowerCase()

        // Prepare request
        if (typeof this.src === 'string' || this.src instanceof URL) {
            url = this.src;
            init = {
                method: this.option('method', 'GET'),
            };
        } else if (this.src.tagName.toLowerCase() === 'a') {
            // Follow link if anchor
            url = this.src.dataset.asyncAction || this.src.href;
            init = {
                method: this.option('method', 'GET'),
            };
        } else if (this.src.tagName.toLowerCase() === 'form') {
            // Submit if form
            url = this.src.dataset.asyncAction || this.src.action;
            const data = new FormData(this.src);
            init = {
                method: this.options.method || this.src.method.toUpperCase(),
                body: new URLSearchParams(data),
            };
        } else {
            console.log(this.src);
            throw new Error('No suitable remote source found');
        }

        // Execute request
        const response = await fetch(url, init);
        const text = await response.text();

        // Parse response to DOM
        return (new DOMParser()).parseFromString(text, 'text/html');
    }

    getDest(options) {
        if (options.dest) {
            return options.dest;
        } else {
            const placementSelector = this.options.placementSelector ||  this.src.dataset.placementSelector;
            const placementMethod = this.options.placementMethod || this.src.dataset.placementMethod || 'replace';

            if (!placementSelector)
                throw new Error('No destination found for remote content');

            const target = document.querySelector(placementSelector);

            if (placementMethod === 'replace') {
                return target;
            } else if (placementMedhod === 'append') {
                const dest = Bulma.createElement('div');
                target.append(dest);
                return dest;
            } else {
                throw new Error(`Unsupported remote content placement method: ${placementMethod}`);
            }
        }
    }

    getSrcSelector() {
        // Try options or src dataset
        if (this.options.srcSelector)
            return this.options.srcSelector;
        else if (this.src.dataset.srcSelector)
            return this.src.dataset.srcSelector;

        // Try url hash
        let url;
        if (typeof this.src === 'string' || this.src instanceof URL)
            url = this.src;
        else if (this.src.tagName.toLowerCase() === 'a')
            url = this.src.dataset.asyncAction || this.src.href;
        else if (this.src.tagName.toLowerCase() === 'form')
            url = this.src.dataset.asyncAction || this.src.action;
        else
            throw new Error('No suitable remote source found');

        if (typeof url === 'string')
            url = new URL (this.src, window.location.origin);

        if (url.hash)
            return url.hash;

        // Fall back to body
        return 'body';
    }
}

export default ContentLoader;
