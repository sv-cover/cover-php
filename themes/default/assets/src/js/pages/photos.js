import Bulma from '@vizuaalog/bulmajs/src/core';

const GALLERY_ANIMATION_MIN = 1
const GALLERY_ANIMATION_RANGE = 1
const GALLERY_ANIMATION_COOLDOWN = 5
const GALLERY_BOOK_WIDTH = .1


class PhotoGalleryThumbnail {
    constructor(element) {
        this.element = element;
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
    }

    animate() {
        if (this.active === undefined)
            return

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
        this.lastAnimation = new Date();
    }

    isAvailable() {
        const bounding = this.element.getBoundingClientRect();
        const topVisible = bounding.top >= 0 && bounding.top <= (window.innerHeight || document.documentElement.clientHeight);
        const bottomVisible = bounding.bottom >= 0 && bounding.bottom <= (window.innerHeight || document.documentElement.clientHeight);

        let isAllowed = true;
        if (this.lastAnimation)
            isAllowed = (new Date() - this.lastAnimation) > (GALLERY_ANIMATION_COOLDOWN * 1000);

        return isAllowed && (topVisible || bottomVisible);
    }
}

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
        new PhotoGallery({
            element: element
        });
    }

    constructor(options) {
        this.element = options.element;
        this.thumbnails = [];

        this.element.querySelectorAll('.book').forEach(element => {
            this.thumbnails.push(new PhotoGalleryThumbnail(element));
        });

        this.animate();
    }

    animate() {
        const next = Math.floor((Math.random() * GALLERY_ANIMATION_RANGE + GALLERY_ANIMATION_MIN) * 1000);
        setTimeout(() => {
            this.animateThumbnail();
            this.animate();
        }, next);
    }

    animateThumbnail() {
        let available = [];
        for (let i = 0; i < this.thumbnails.length; i++) {
            if (this.thumbnails[i].isAvailable())
                available.push(i);
        }

        const idx = available[Math.floor(Math.random() * available.length)];

        if (idx)
            this.thumbnails[idx].animate();
    }
}


Bulma.registerPlugin('photos', PhotoGallery);

export default PhotoGallery;
