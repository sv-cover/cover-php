import {Bulma} from 'cover-style-system/src/js';

class SinglePhotos {

    /**
     * Get the root class this plugin is responsible for.
     * This will tell the core to match this plugin to an element with a .modal class.
     * @returns {string} The class this plugin is responsible for.
     */
    static getRootClass() {
        return 'photo-single';
    }

    static parseDocument(context) {
        const elements = context.querySelectorAll('.photo-single');

        Bulma.each(elements, element => {
            new SinglePhotos({
                element: element,
            });
        });
    }

    /**
     * Plugin constructor
     * @param  {Object} options The options object for this plugin
     * @return {this} The newly created plugin instance
     */
    constructor(options) {
        this.element = options.element;
        this.photo = options.element.querySelector('.photo');
        this.initFullscreen();
    }

    createFullscreenButton(buttonClass, iconClass, srText) {
        let buttonElement = document.createElement('button');
        buttonElement.classList.add(buttonClass, 'button', 'is-text');

        let iconElement = document.createElement('span');
        iconElement.classList.add('icon');

        let faElement = document.createElement('i');
        faElement.classList.add('fas', iconClass);
        faElement.setAttribute('aria-hidden', true);

        let srElement = document.createElement('span');
        srElement.classList.add('is-sr-only');
        srElement.append(document.createTextNode(srText));

        iconElement.append(faElement);
        iconElement.append(srElement);
        buttonElement.append(iconElement);
        return buttonElement;
    }

    initFullscreen() {
        const navElement = this.photo.querySelector('nav');
        this.enterFullscreenButton = this.createFullscreenButton('photo-enter-fullscreen', 'fa-expand', navElement.dataset.enterFullscreenText);
        this.exitFullscreenButton = this.createFullscreenButton('photo-exit-fullscreen', 'fa-compress', navElement.dataset.exitFullscreenText);
        this.exitFullscreenButton.hidden = true;

        this.enterFullscreenButton.addEventListener('click', () => this.photo.requestFullscreen());
        this.exitFullscreenButton.addEventListener('click', () => document.exitFullscreen());

        navElement.append(this.enterFullscreenButton);
        navElement.append(this.exitFullscreenButton);

        document.addEventListener('fullscreenchange', this.handleFullscreenChange.bind(this));
    }

    handleFullscreenChange(event) {
        if (document.fullscreenElement) {
            this.enterFullscreenButton.hidden = true;
            this.exitFullscreenButton.hidden = false;
            this.photo.classList.add('is-fullscreen');
        } else {
            this.enterFullscreenButton.hidden = false;
            this.exitFullscreenButton.hidden = true;
            this.photo.classList.remove('is-fullscreen');
        }
    }
}

SinglePhotos.parseDocument(document);

export default SinglePhotos;
