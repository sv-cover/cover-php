import Bulma from '@vizuaalog/bulmajs/src/core';

const GALLERY_ANIMATION_INTERVAL_MIN = 5
const GALLERY_ANIMATION_INTERVAL_RANGE = 10
const GALLERY_ANIMATION_INTERVAL_BOOK_WEIGHT = .5
const GALLERY_BOOK_WIDTH = .1


class PhotoGallery {
    /**
     * Get the root class this plugin is responsible for.
     * This will tell the core to match this plugin to an element with a .modal class.
     * @returns {string} The class this plugin is responsible for.
     */
    static getRootClass() {
        return 'book-gallery';
    }


    /**
     * Handle parsing the DOMs data attribute API.
     * @param {HTMLElement} element The root element for this instance
     * @return {undefined}
     */
    static parse(element) {
        element.querySelectorAll('.book').forEach(el => {
            new PhotoGallery({
                element: el
            });
        });
    }

    constructor(options) {
        this.element = options.element;
        this.container = this.element.querySelector('.thumbnail-images');
        this.images = [];

        let images = this.element.querySelectorAll('.thumbnail-images .image');
        for (let i = 0; i < images.length; i++) {
            this.images.push(images[i]);

            let fig = images[i].querySelector('figure');
            let width = this.element.clientWidth * (1 - (images.length - 1) * GALLERY_BOOK_WIDTH * 0.09);
            fig.style.width = Math.ceil(width) + 'px';

            if (images[i].classList.contains('active'))
                this.active = i;
        }

        this.interval_min = GALLERY_ANIMATION_INTERVAL_MIN;
        this.interval_range = GALLERY_ANIMATION_INTERVAL_RANGE + document.querySelectorAll('.book-gallery .book').length * GALLERY_ANIMATION_INTERVAL_BOOK_WEIGHT;

        this.animate(true);
    }

    animate(first = false) {
        const min = first ? 0 : this.interval_min;
        const next = Math.floor((Math.random() * this.interval_range + min) * 1000);
        setTimeout(() => {
            this.flipImages();
        }, next);
    }

    flipImages() {
        let current = this.images[this.active];
        current.classList.remove('active');

        let clone = current.cloneNode(true);
        let nextIdx = (this.active + 1) % this.images.length;
        let next = this.images[nextIdx];

        current.addEventListener('transitionend', () => {
            current.remove();
        });

        this.container.appendChild(clone);
        current.classList.add('out');
        clone.classList.add('in');
        next.classList.add('active');

        setTimeout(() => {
            clone.classList.remove('in');
        }, 100);

        this.images[this.active] = clone;
        this.active = nextIdx;
        this.animate();
    }
}


Bulma.registerPlugin('photos', PhotoGallery);

export default PhotoGallery;
