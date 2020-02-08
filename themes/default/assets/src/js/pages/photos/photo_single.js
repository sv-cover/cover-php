import {Bulma} from 'cover-style-system/src/js';
import {copyTextToClipboard} from '../../utils';


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
                this.navigate('next');
            });

        if (previousButton)
            previousButton.addEventListener('click', (event) => {
                event.preventDefault();
                this.navigate('previous');
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

    async navigate(direction) {
        // Backup old stuff
        const oldCurrent = this.current;
        const oldCurrentPicture = this.currentPicture;

        
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
            newCurrentPicture.addEventListener('transitionend', this.renderNavigationEnd.bind(this), {once: true});
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
        new PhotoInfo(newInfo, this.current.photo);

        document.dispatchEvent(new CustomEvent('partial-content-loaded', { bubbles: true, detail: newInfo }));

        // Update parent link
        const parentLink = this.navigation.querySelector('.photo-parent');
        parentLink.replaceWith(this.current.photo.querySelector('.photo-parent').cloneNode(true));
    }
}

class PhotoInfo {
    constructor(element, photo) {
        this.element = element;
        this.initCopyLink();
        this.initLikeButtons();
    }

    initCopyLink() {
        this.element.querySelectorAll('.photo-copy-link').forEach(element => {
            element.addEventListener('click', this.handleCopy.bind(this, element));
        });
    }

    handleCopy(element, event) {
        event.preventDefault();
        let result = copyTextToClipboard(element.href);
        if (result)
            alert(element.dataset.successMessage);
        else
            alert('Oops, unable to copy!');
    }

    async handleLike(event) {
        event.preventDefault();
        const form = event.target;

        const data = {
            'action': form.action.value,
        };

        const init = {
            'method': 'POST',
            'headers': { 'Content-Type': 'application/json' },
            'body': JSON.stringify(data),
        };

        // Use getAttribute, because field called action exists.
        const response = await fetch(form.getAttribute('action'), init);

        const result = await response.json();

        const button = form.querySelector('button[type=submit]');
        const buttonTitles = JSON.parse(button.dataset.title || '["", ""]');
        const buttonSrTexts = JSON.parse(button.dataset.srText || '["", ""]');

        if (result.liked) {
            form.action.value = 'unlike';
            form.querySelector('.fa-heart').classList.add('has-text-cover');
            button.title = buttonTitles[0];
            button.querySelector('.is-sr-only').textContent = buttonSrTexts[0];
        } else {
            form.action.value = 'like';
            form.querySelector('.fa-heart').classList.remove('has-text-cover');
            button.title = buttonTitles[1];
            button.querySelector('.is-sr-only').textContent = buttonSrTexts[1];
        }

        const likesCount = form.querySelector('.likes-count');
        const lcTitles = JSON.parse(likesCount.dataset.title || '["", ""]');
        const lcSrTexts = JSON.parse(likesCount.dataset.srText || '["", ""]');

        likesCount.querySelector('.likes-count-number').textContent = result.likes;
        if (result.likes > 0) {
            likesCount.hidden = false;
            if (result.likes === 1) {
                likesCount.title = `${result.likes} ${lcTitles[0]}`;
                likesCount.querySelector('.is-sr-only').textContent = lcSrTexts[0];
            } else {
                likesCount.title = `${result.likes} ${lcTitles[1]}`;
                likesCount.querySelector('.is-sr-only').textContent = lcSrTexts[1];
            }
        } else {
            likesCount.hidden = true;
        }
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

        switch (event.code) {
            case "Left": // IE/Edge specific value
            case "ArrowLeft":
                this.carousel.navigate('previous');
                break;
            case "Right": // IE/Edge specific value
            case "ArrowRight":
                this.carousel.navigate('next');
                break
            case "KeyC":
                this.element.querySelector('#field-reactie').focus();
                break;
            case "Esc": // IE/Edge specific value
            case "Escape":
                event.preventDefault(); // Esc stops reload
                window.location.assign(this.element.querySelector('.photo-parent').href);
                break;
        }
    }
}

SinglePhoto.parseDocument(document);
document.addEventListener('partial-content-loaded', event => SinglePhoto.parseDocument(event.detail));

export default SinglePhoto;