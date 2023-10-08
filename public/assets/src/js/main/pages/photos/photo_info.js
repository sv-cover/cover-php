import {copyTextToClipboard} from '../../utils';


class PhotoInfo {
    constructor(element, photo) {
        this.element = element;
        this.photo = photo;
        this.initCopyLink();
        this.initLikeButtons();
        this.initFullscreenInfo();
    }

    initCopyLink() {
        this.element.querySelectorAll('.photo-copy-link').forEach(element => {
            element.addEventListener('click', this.handleCopy.bind(this, element));
        });
    }

    initFullscreenInfo() {
        // Create container
        let containerElement = document.createElement('div');
        containerElement.classList.add('photo-info-fullscreen');

        // Create controls element
        let controlsElement = document.createElement('div');
        controlsElement.classList.add('controls', 'level', 'is-mobile');

        // Fetch title and add to container
        const title = this.element.querySelector('h1.name');
        if (title)
            containerElement.append(title.cloneNode(true));

        // Fetch like button and add to controls
        const likeButton = this.element.querySelector('.photo-like-form');
        if (likeButton)
            controlsElement.append(likeButton.cloneNode(true));

        // Init like button functionality
        this.initLikeButtons(controlsElement);

        // Create comments icon and append to controls
        const comments = this.element.querySelector('.photo-interaction .comments');
        if (comments) {
            let commentsElement = document.createElement('div');
            commentsElement.classList.add('comments', 'level-item');

            let iconElement = document.createElement('span');
            iconElement.classList.add('icon');

            let faElement = document.createElement('i');
            faElement.classList.add('fas', comments.dataset.iconClass);
            faElement.setAttribute('aria-hidden', true);

            let countNode = document.createTextNode(comments.dataset.count);
            let countElement = document.createElement('span');
            countElement.classList.add('count');

            let srElement = document.createElement('span');
            srElement.classList.add('is-sr-only');
            
            const texts = JSON.parse(comments.dataset.srText || '["", ""]');
            if (comments.dataset.count === 1) {
                commentsElement.title = texts[0];
                srElement.append(document.createTextNode(texts[0]));
            } else {
                commentsElement.title = texts[1];
                srElement.append(document.createTextNode(texts[1]));
            }

            iconElement.append(faElement);

            countElement.append(countNode);
            countElement.append(srElement);

            commentsElement.append(iconElement);
            commentsElement.append(countElement);
            controlsElement.append(commentsElement);
        }
        containerElement.append(controlsElement);

        // Replace if already exists, otherwise append to navigation
        const currentInfo = this.photo.querySelector('.photo-info-fullscreen');
        if (currentInfo)
            currentInfo.replaceWith(containerElement);
        else
            this.photo.querySelector('.photo-navigation').append(containerElement);
    }

    initLikeButtons(element=undefined) {
        if (!element)
            element = this.element;

        element.querySelectorAll('.like-form').forEach(element => {
            element.reset();
            element.addEventListener('submit', this.handleLike.bind(this));
        });    
    }

    handleCopy(element, event) {
        event.preventDefault();
        if (navigator.share) {
            navigator.share({
                title: `Photo from svcover.nl`,
                url: element.href
            }).catch(
                (e) => console.error(e)
            );
        } else if (window.confirm(element.dataset.copyQuestion)) { 
            let result = copyTextToClipboard(element.href);
            if (!result)
                alert('Oops, unable to copy!');
        }
    }

    async handleLike(event) {
        // Don't submit form
        event.preventDefault();

        const form = event.target;

        // Prepare request (meta) data
        const data = {
            'action': form.action.value,
        };

        const init = {
            'method': 'POST',
            'headers': { 'Content-Type': 'application/json' },
            'body': JSON.stringify(data),
        };

        // Perform request
        // Use getAttribute, because field called action exists.
        const response = await fetch(form.getAttribute('action'), init);
        const result = await response.json();

        // Reflect button change
        const button = form.querySelector('button[type=submit]');
        const buttonTitles = JSON.parse(form.dataset.cta || '["", ""]');

        if (result.liked) {
            form.action.value = 'unlike';
            form.querySelector('.fa-heart').classList.add('has-text-cover');
            button.title = buttonTitles[0];
            button.setAttribute('aria-label', buttonTitles[0]);
        } else {
            form.action.value = 'like';
            form.querySelector('.fa-heart').classList.remove('has-text-cover');
            button.title = buttonTitles[1];
            button.setAttribute('aria-label', buttonTitles[1]);
        }

        // Reflect counter change
        const likesCount = form.querySelector('.count');
        const lcTitles = JSON.parse(likesCount.dataset.title || '["", ""]');
        const lcSrTexts = JSON.parse(likesCount.dataset.srText || '["", ""]');

        likesCount.querySelector('.count-number').textContent = result.likes;
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

export default PhotoInfo;
