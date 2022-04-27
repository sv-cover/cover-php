import Hammer from 'hammerjs';
import PhotoFaces from './photo_faces.js';
import PhotoInfo from './photo_info.js';


class PhotoCarousel {
    constructor(link, photo, info) {
        // Initialise cache
        this.cache = {};
        this.cache[link] = {};
        this.current = this.cachePhoto(link, photo.cloneNode(true), info.cloneNode(true));

        // Initialise elements
        this.info = info;
        this.photo = photo;

        this.carousel = photo.querySelector('.carousel');
        this.currentPicture = photo.querySelector('.image');
        this.currentPicture.classList.add('current');
        this.currentPicture.dataset.link = link;

        // Update face tagging
        this.updateFaces(this.currentPicture);

        // Initialise different navigation modes
        this.isNavigating = false;
        this.navigation = photo.querySelector('.photo-navigation');
        this.initNavigation();
        this.initGestures();
        window.onpopstate = this.handleHistory.bind(this);

        // Detect transitions
        const styles = window.getComputedStyle(this.currentPicture);
        this.hasTransition = styles.transitionDelay !== "0s" || styles.transitionDuration !== "0s";

        // Start preloading photos
        this.nextPicture = this.stagePhoto(this.current.next, 'next');
        this.previousPicture = this.stagePhoto(this.current.previous, 'previous');
    }

    initGestures() {
        // Create manager and initialise gestures
        let mc = new Hammer.Manager(this.photo);
        let pan = new Hammer.Pan({ direction: Hammer.DIRECTION_HORIZONTAL });
        let swipe = new Hammer.Swipe({ direction: Hammer.DIRECTION_ALL });

        mc.add([pan, swipe]);
        pan.recognizeWith(swipe);

        // Bind events
        mc.on('swipe', this.handleSwipe.bind(this));
        mc.on('pan', this.handlePan.bind(this));
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
        const info = doc.querySelector('.photo-single .photo-info');

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

    async navigate(direction, updateHistory=true) {
        // Only navigate if not already navigating
        if (this.isNavigating)
            return

        this.isNavigating = true;

        // Backup old stuff
        const oldCurrent = this.current;
        const oldCurrentPicture = this.currentPicture;
        
        // Load next picture in direction
        this.currentPicture = await this[direction + 'Picture'];

        if (!this.currentPicture) {
            if (oldCurrent[direction])
                console.error('Failed at loading picture' + oldCurrent[direction]);
            this.currentPicture = oldCurrentPicture;
            this.isNavigating = false;
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
        if (updateHistory)
            history.pushState({}, document.title, oldCurrent[direction]);
        this.renderNavigation(direction, oldCurrentPicture, this.currentPicture);
        this.isNavigating = false;
    }

    renderNavigation(direction, oldCurrentPicture, newCurrentPicture) {
        // Update image elements in carousel
        newCurrentPicture.classList.add('current');

        if (direction === 'next') {
            const previousPictures = this.carousel.querySelectorAll('.image.previous');
            newCurrentPicture.classList.remove('next');
            oldCurrentPicture.classList.replace('current', 'previous');

            if (previousPictures)
                previousPictures.forEach( el => el.remove());
        } else if (direction === 'previous') {
            const nextPictures = this.carousel.querySelectorAll('.image.next');
            newCurrentPicture.classList.remove('previous');
            oldCurrentPicture.classList.replace('current', 'next');

            if (nextPictures)
                nextPictures.forEach( el => el.remove());  
        }

        // Reset left positions in case it has changed by handlePan
        this.carousel.classList.add('is-animated');
        this.carousel.querySelectorAll('.image').forEach( (el) => {
            let base = '0%';
            if (el.classList.contains('previous'))
                base = '-100%';
            else if (el.classList.contains('next'))
                base = '100%';
            el.style.left = base;
        });

        // Schedule changes on for transition end
        if (direction && this.hasTransition) {
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
        const newInfo = this.current.info.cloneNode(true);
        this.info.replaceWith(newInfo);
        this.info = newInfo;
        new PhotoInfo(newInfo, this.photo);
        
        // Update face tagging
        this.updateFaces(this.currentPicture);

        document.dispatchEvent(new CustomEvent('partial-content-loaded', { bubbles: true, detail: newInfo }));

        // Update parent link
        const parentLink = this.navigation.querySelector('.photo-parent');
        parentLink.replaceWith(this.current.photo.querySelector('.photo-parent').cloneNode(true));
    }

    updateFaces(photo) {
        if (this.faces)
            this.faces.disableTagging();

        const tagLists = photo.closest('.photo-single').querySelectorAll('.photo-tag-list');
        const tagButtons = photo.closest('.photo-single').querySelectorAll('.photo-tag-button');

        this.faces = new PhotoFaces({
            element: photo,
            tagLists: tagLists,
            tagButtons: tagButtons,
        });
    }

    async handleHistory(event) {
        // If direction is known, navigate properly WITHOUT changing history
        if (document.location == this.current.next) {
            this.navigate('next', false);
        } else if (document.location == this.current.previous) {
            this.navigate('previous', false);
        } else {
            // Otherwise, navigate by intimidation
            const oldCurrent = this.current;
            const oldCurrentPicture = this.currentPicture;

            // Empty carousel
            while (this.carousel.firstChild)
                this.carousel.removeChild(this.carousel.firstChild);

            // Load current picture into DOM directly
            this.current = this.cache[document.location];
            const newCurrentPicture = await this.stagePhoto(document.location, 'current');
            this.currentPicture = newCurrentPicture;

            // Stage next and previous
            this.nextPicture = this.stagePhoto(this.current.next, 'next');
            this.previousPicture = this.stagePhoto(this.current.previous, 'previous');

            // Perform DOM modifications
            this.renderNavigation(false, oldCurrentPicture, this.currentPicture);
        }
    }

    handlePan(event) {
        if (event.target instanceof HTMLInputElement)
            return;

        if (event.eventType & (Hammer.INPUT_START | Hammer.INPUT_MOVE)) {
            // If start/move, move carousel items by deltaX.
            // Disable animation to make it smoother
            this.carousel.classList.remove('is-animated');
            this.carousel.querySelectorAll('.image').forEach( (el) => {
                let base = '0%';
                if (el.classList.contains('previous'))
                    base = '-100%';
                else if (el.classList.contains('next'))
                    base = '100%';
                el.style.left = `calc(${base} + ${event.deltaX}px)`;
            });
        } else {
            // If cancel/end, commit.
            // Reenable animations
            this.carousel.classList.add('is-animated');

            // Should navigate if move is bigger than 50% and not canceled.
            // This step also triggers a reflow, which is essential to make the animations work
            let shouldNavigate = Math.abs(event.deltaX) > this.carousel.offsetWidth / 2;

            shouldNavigate = shouldNavigate && event.eventType != Hammer.INPUT_CANCEL;

            // Navigate if should navigate and a next/previous photo is available
            if (shouldNavigate && event.deltaX < 0 && this.current.next)
                this.navigate('next');
            else if (shouldNavigate && event.deltaX > 0 && this.current.previous)
                this.navigate('previous');
            else {
                // Reset view if no navigation
                this.carousel.querySelectorAll('.image').forEach( (el) => {
                    let base = '0%';
                    if (el.classList.contains('previous'))
                        base = '-100%';
                    else if (el.classList.contains('next'))
                        base = '100%';
                    el.style.left = base;
                });
            }
        }
    }

    handleSwipe(event) {
        // Swipe swipe directions are in the direction of the movement, so the other way around in terms of navigation
        if (event.direction == Hammer.DIRECTION_LEFT)
            this.navigate('next');
        else if (event.direction == Hammer.DIRECTION_RIGHT)
            this.navigate('previous');
        else if (event.direction & Hammer.DIRECTION_VERTICAL) {
            if (document.fullscreenElement)
                document.exitFullscreen();
        }
    }
}

export default PhotoCarousel;
