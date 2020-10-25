import {Bulma} from 'cover-style-system/src/js';
import {copyTextToClipboard} from '../../utils';
import PhotoCarousel from './photo_carousel.js';
import PhotoInfo from './photo_info.js';


class SinglePhoto {
    static parseDocument(context) {
        const elements = context.querySelectorAll('.photo-single');

        Bulma.each(elements, element => {
            new SinglePhoto({
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
        this.photoInfo = options.element.querySelector('.photo-info');
        new PhotoInfo(this.photoInfo, this.photo);
        this.carousel = new PhotoCarousel(window.location.href, this.photo, this.photoInfo);
        this.navigation = this.photo.querySelector('.photo-navigation');
        this.initFullscreen();
        document.addEventListener('keydown', this.handleKeydown.bind(this));
    }

    initFullscreen() {
        // Create full screen buttons
        this.enterFullscreenButton = this.createFullscreenButton('photo-enter-fullscreen', 'fa-expand', this.navigation.dataset.enterFullscreenText);
        this.exitFullscreenButton = this.createFullscreenButton('photo-exit-fullscreen', 'fa-compress', this.navigation.dataset.exitFullscreenText);
        this.exitFullscreenButton.hidden = true;

        // Add event listeners for full screen to buttons
        this.enterFullscreenButton.addEventListener('click', () => this.photo.requestFullscreen());
        this.exitFullscreenButton.addEventListener('click', () => document.exitFullscreen());

        // Add buttons to navigation
        this.navigation.append(this.enterFullscreenButton);
        this.navigation.append(this.exitFullscreenButton);

        // Detect full screen changes
        document.addEventListener('fullscreenchange', this.handleFullscreenChange.bind(this));

        // Toggle navigation
        this.photo.querySelector('.carousel').addEventListener('click', this.handleFullscreenNavToggle.bind(this));
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

    handleFullscreenChange(event) {
        if (document.fullscreenElement) {
            this.enterFullscreenButton.hidden = true;
            this.exitFullscreenButton.hidden = false;
            this.photo.classList.add('is-fullscreen');
        } else {
            this.enterFullscreenButton.hidden = false;
            this.exitFullscreenButton.hidden = true;
            this.navigation.hidden = false;
            this.photo.classList.remove('is-fullscreen');
        }
    }

    handleFullscreenNavToggle(event) {
        if (document.fullscreenElement)
            this.navigation.hidden = !this.navigation.hidden;
    }

    handleKeydown(event) {
        // Don't prevent normal keyboard usage
        if (['TEXTAREA', 'INPUT'].indexOf(event.target.nodeName) !== -1)
            return;

        // Don't prevent normal keyboard shortcuts
        if (event.shiftKey || event.metaKey || event.ctrlKey)
            return;

        switch (event.key) {
            case "Left": // IE/Edge specific value
            case "ArrowLeft":
                this.carousel.navigate('previous');
                break;
            case "Right": // IE/Edge specific value
            case "ArrowRight":
                this.carousel.navigate('next');
                break
            case "c":
            case "C":
                this.element.querySelector('#field-reactie').focus();
                break;
            case "f":
            case "F":
                if (document.fullscreenElement)
                    document.exitFullscreen();
                else
                    this.photo.requestFullscreen();
                break;
            // case "Esc": // IE/Edge specific value
            // case "Escape":
            //     event.preventDefault(); // Esc stops reload
            //     window.location.assign(this.element.querySelector('.photo-parent').href);
            //     break;
        }
    }
}

SinglePhoto.parseDocument(document);
document.addEventListener('partial-content-loaded', event => SinglePhoto.parseDocument(event.detail));

export default SinglePhoto;