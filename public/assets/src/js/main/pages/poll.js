import {Bulma} from 'cover-style-system/src/js';

class Poll {
    /**
     * Get the root class this plugin is responsible for.
     * This will tell the core to match this plugin to an element with a .modal class.
     * @returns {string} The class this plugin is responsible for.
     */
    static getRootClass() {
        return 'poll';
    }


    /**
     * Handle parsing the DOMs data attribute API.
     * @param {HTMLElement} element The root element for this instance
     * @return {undefined}
     */
    static parse(element) {
        new Poll({
            element: element,
        });
    }

    constructor(options) {
        this.element = options.element;
        this.initLikeButtons();
    }

    initLikeButtons(element=undefined) {
        this.element.querySelectorAll('.like-form').forEach(el => {
            el.reset();
            el.addEventListener('submit', this.handleLike.bind(this));
        });    
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


Bulma.registerPlugin('poll', Poll);

export default Poll;