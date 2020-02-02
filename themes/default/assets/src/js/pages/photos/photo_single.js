import {Bulma} from 'cover-style-system/src/js';


class PhotoCarousel {
    constructor(link, photo, info) {

        // Initialise cache
        this.cache = {};
        this.cache[link] = {};
        this.current = this.cachePhoto(link, photo.cloneNode(true), info.cloneNode(true));

        // Initialise elements
        this.info = info;

        this.carousel = photo.querySelector('.carousel');
        this.currentPicture = photo.querySelector('.image');
        this.currentPicture.classList.add('current');
        this.currentPicture.dataset.link = link;

        this.navigation = photo.querySelector('.photo-navigation');
        this.initNavigation();

        // Detect transitions
        const styles = window.getComputedStyle(this.currentPicture);
        this.hasTransition = styles.transitionDelay !== "0s" || styles.transitionDuration !== "0s";

        // Start preloading photos
        this.nextPicture = this.stagePhoto(this.current.next, 'next');
        this.previousPicture = this.stagePhoto(this.current.previous, 'previous');
    }

    initNavigation() {
        // Init navigation events
        const nextButton = this.navigation.querySelector('.photo-next');
        const previousButton = this.navigation.querySelector('.photo-previous');

        if (nextButton)
            nextButton.addEventListener('click', (event) => {
                event.preventDefault();
                this.navigate('next', nextButton.href);
            });

        if (previousButton)
            previousButton.addEventListener('click', (event) => {
                event.preventDefault();
                this.navigate('previous', previousButton.href);
            });
    }

    cachePhoto(link, photo, info) {
        const previousLink = photo.querySelector('nav .photo-previous');
        const nextLink = photo.querySelector('nav .photo-next');

        this.cache[link] = {
            'status': 'ready',
            'next': nextLink ? nextLink.href : null,
            'previous': previousLink ? previousLink.href : null,
            'photo': photo,
            'info': info,
            'picture': photo.querySelector('.image'),
            'nextLink': nextLink,
            'previousLink': previousLink,
        }

        return this.cache[link];
    }

    async loadPhoto(link) {
        // Load photo from server
        const response = await fetch(link);
        const result = await response.text();

        // Parse doc
        const doc = (new DOMParser()).parseFromString(result, 'text/html');
        const photo = doc.querySelector('.photo-single .photo');
        const info = doc.querySelector('.photo-single .photo-info')

        // Add to cache
        return this.cachePhoto(link, photo, info);
    }

    async stagePhoto(link, direction) {
        if (!link)
            return null;

        // Retrieve photo from cache or load from server
        let photo = null;
        if (link in this.cache)
            photo = this.cache[link];
        else
            photo = await this.loadPhoto(link);

        // create picture node, set direction and append
        const newPicture = photo.picture.cloneNode(true);
        newPicture.classList.add(direction);
        this.carousel.append(newPicture);

        return newPicture;
    }

    async navigate(direction, link=null) {
        // Backup old stuff
        const oldCurrent = this.current;
        const oldCurrentPicture = this.currentPicture;

        // don't navigate to the same photo twice.
        if (oldCurrent[direction] != link)
            return;

        
        // Load next picture in direction
        this.currentPicture = await this[direction + 'Picture'];

        if (!this.currentPicture) {
            if (oldCurrent[direction])
                console.log('Failed at loading picture' + oldCurrent[direction]);
            return;
        }

        // Update current
        this.current = this.cache[oldCurrent[direction]];

        // Stage next photos
        if (direction === 'next') {
            this.nextPicture = this.stagePhoto(this.current.next, 'next');
            this.previousPicture = Promise.resolve(oldCurrentPicture);
        } else {
            this.previousPicture = this.stagePhoto(this.current.previous, 'previous');
            this.nextPicture = Promise.resolve(oldCurrentPicture);
        }

        // Update history & DOM
        history.pushState({}, document.title, oldCurrent[direction]);
        this.renderNavigation(direction, oldCurrentPicture, this.currentPicture);
    }

    renderNavigation(direction, oldCurrentPicture, newCurrentPicture) {
        // Update image elements in carousel
        newCurrentPicture.classList.add('current');

        if (direction === 'next') {
            const previousPicture = this.carousel.querySelector('.image.previous');
            newCurrentPicture.classList.remove('next');
            oldCurrentPicture.classList.replace('current', 'previous');

            if (previousPicture)
                previousPicture.remove();
        } else {
            const nextPicture = this.carousel.querySelector('.image.next');
            newCurrentPicture.classList.remove('previous');
            oldCurrentPicture.classList.replace('current', 'next');

            if (nextPicture)
                nextPicture.remove();   
        }

        // Schedule changes on for transition end
        if (this.hasTransition) {
            newCurrentPicture.addEventListener('transitioncancel', this.renderNavigationEnd.bind(this));
            newCurrentPicture.addEventListener('transitionend', this.renderNavigationEnd.bind(this));
        } else {
            this.renderNavigationEnd();
        }

        // Update navigation links
        const previousLink = this.navigation.querySelector('.photo-previous');
        const nextLink = this.navigation.querySelector('.photo-next');
        if (this.current.nextLink) {
            if (nextLink)
                nextLink.replaceWith(this.current.nextLink.cloneNode(true));
            else
                this.navigation.append(this.current.nextLink.cloneNode(true));
        } else if(nextLink) {
            nextLink.remove();
        }

        if (this.current.previousLink) {
            if (previousLink)
                previousLink.replaceWith(this.current.previousLink.cloneNode(true));
            else
                this.navigation.append(this.current.previousLink.cloneNode(true));
        } else if(previousLink) {
            previousLink.remove();
        }

        // Bind navigation events
        this.initNavigation();
    }

    renderNavigationEnd() {
        // Update info
        const newInfo = this.current.info;
        this.info.replaceWith(newInfo);
        this.info = newInfo;
        Bulma.traverseDOM(newInfo);

        // Update parent link
        const parentLink = this.navigation.querySelector('.photo-parent');
        parentLink.replaceWith(this.current.photo.querySelector('.photo-parent').cloneNode(true));
    }
}

class SinglePhoto {

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
        this.carousel = new PhotoCarousel(window.location.href, this.photo, this.photoInfo);
        this.navigation = this.photo.querySelector('.photo-navigation');
        this.initFullscreen();
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
}

SinglePhoto.parseDocument(document);

export default SinglePhoto;