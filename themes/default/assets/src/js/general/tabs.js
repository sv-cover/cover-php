import Bulma from '@vizuaalog/bulmajs/src/core';
import Tabs from '@vizuaalog/bulmajs/src/plugins/tabs';
import ContentLoader from './content_loader';


class CoverTabs extends Tabs {
    /**
     * Helper method used by the Bulma core to create a new instance.
     * @param  {Object} options The options object for this instance
     * @returns {Tabs} The newly created instance
     */
    static create(options) {
        return new CoverTabs(options);
    }

    /**
     * Handle parsing the DOMs data attribute API.
     * @param {HTMLElement} element The root element for this instance
     * @returns {undefined}
     */
    static parse(element) {
        let hover = element.hasAttribute('data-hover') ? true : false;

        let options = {
            element: element,
            hover: hover
        };

        new CoverTabs(options);
    }

    /**
     * Find each individual content item
     * @returns {HTMLElement[]} An array of the found items
     */
    findContentItems() {
        if (this.content.children[0].tagName.toLowerCase() === 'ul')
            return super.findContentItems();

        let items = [];

        Bulma.each(this.navItems, (navItem, idx) => {
            const navElement = navItem.querySelector('a');
            const destination = navElement.getAttribute('href');

            let item;

            if (destination.startsWith('#')) {
                item = this.content.querySelector(destination);
            } else {
                try {
                    const url = new URL(destination, window.location.origin);
                    item = this.content.querySelector(url.hash);
                    // Register we still found a valid url (no exception thrown)
                    if (!item)
                        item = null;
                } catch (e) {
                    console.error('Invalid href in tab', destination, navElement);
                }
            }


            if (item === undefined)
                console.warn('No content item found for', navElement);
            else
                items[idx] = item;
        });

        return items;
    }

    /**
     * Setup initial visibility according to the url, to enable navigation to
     * specific tabs or items on specific tabs.
     * @returns {void}
     */
    setInitialVisibility() {
        const hash = decodeURIComponent(window.location.hash);
        if (hash) {
            Bulma.each(this.navItems, (navItem, index) => {
                const navElement = navItem.querySelector('a');
                const url = new URL(navElement.getAttribute('href'), window.location.origin);
                if (url.hash == hash || (this.contentItems[index] && this.contentItems[index].querySelector(hash))) {
                    this.handleNavClick(navItem, index);
                }
            });
        }
    }

    /**
     * Setup the events to handle tab changing
     * @returns {void}
     */
    setupNavEvents() {
        // Not the best place, but now we don't have to override the constructor
        this.setInitialVisibility();

        Bulma.each(this.navItems, (navItem, index) => {
            navItem.addEventListener('click', (evt) => {
                evt.preventDefault();
                this.handleNavClick(navItem, index);
            });

            if(this.hover) {
                navItem.addEventListener('mouseover', () => {
                    this.handleNavClick(navItem, index);
                });
            }
        });
    }


    /**
     * Handle the changing of the visible tab
     * @param {HTMLelement} navItem The nav item we are changing to
     * @param {number} index The internal index of the nav item we're changing to
     * @returns {void}
     */
    handleNavClick(navItem, index) {
        if (!this.contentItems[index]) {
            let newContentItem = Bulma.createElement('div');
            this.content.append(newContentItem);
            this.contentItems[index] = newContentItem;
            new ContentLoader({
                src: navItem.querySelector('a'),
                dest: newContentItem,
                onComplete: content => this.contentItems[index] = content
            });
        }
        this.setActive(index);
    }

    /**
     * Set the provided tab's index as the active tab.
     * 
     * @param {integer} index The new index to set
     */
    setActive(index) {
        Bulma.each(this.navItems, (navItem) => {
            navItem.classList.remove('is-active');
        });

        Bulma.each(this.contentItems, (contentItem) => {
            if (contentItem)
                contentItem.classList.remove('is-active');
        });

        this.navItems[index].classList.add('is-active');
        this.contentItems[index].classList.add('is-active');

        // Notify others about state change
        this.contentItems[index].dispatchEvent(new Event('show-tab'));
        
        // Set history state
        const navElement = this.navItems[index].querySelector('a');
        history.replaceState(null, '', navElement.getAttribute('href'));
    }
}

Bulma.registerPlugin('tabs', CoverTabs, 100001)

export default CoverTabs;
