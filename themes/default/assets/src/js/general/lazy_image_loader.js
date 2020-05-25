import { Bulma } from 'cover-style-system/src/js';

const EMPTY_PIXEL = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
const MIN_THRESHOLD = 300;

class LazyImageLoader {
    static parseDocument(context) {
        const elements = context.querySelectorAll('img.lazy[data-src]');

        Bulma.each(elements, element => {
            new LazyImageLoader({
                element: element,
            });
        });
    }

    constructor(options) {
        this.element = options.element;
        this.initPlaceholder();
        this.initLazy();
    }

    initPlaceholder() {
        // If src is already set and dataset.src is not set, temporary copy src to dataset.src
        if (this.element.src && !this.element.dataset.src)
            this.element.dataset.src = this.element.src;

        // Set src to placeholder or empty pixel
        if (this.element.dataset.placeholderSrc)
            this.element.src = this.element.dataset.placeholderSrc;
        else
            this.element.src = EMPTY_PIXEL;
    }

    initLazy() {
        // Set threshold. No horizontal scroll, so vertical threshold only
        let threshold = Math.max(MIN_THRESHOLD, document.documentElement.clientHeight);
        let options = {
          rootMargin: `${threshold}px 0px`,
          threshold: 0,
        };

        // Create & init observer
        let lazyImageObserver = new IntersectionObserver(
            this.handleObserve.bind(this),
            options
        );
        lazyImageObserver.observe(this.element);
    }

    handleObserve(entries, observer) {
        for (let entry of entries) {
            if (entry.isIntersecting) {
                // Replace values, and stop lazyloading for this image
                let element = entry.target;
                element.src = element.dataset.src;
                element.srcSet = element.dataset.srcSet;
                element.classList.remove('lazy');
                observer.unobserve(element);
            }
        }
    }


}

LazyImageLoader.parseDocument(document);
document.addEventListener('partial-content-loaded', event => LazyImageLoader.parseDocument(event.detail));

export default LazyImageLoader;
