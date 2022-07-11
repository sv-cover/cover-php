import {Bulma, AutoPopup} from 'cover-style-system/src/js';
import Sortable from 'sortablejs';

class PhotoGalleryAdmin {
    static parseDocument(context) {
        const elements = context.querySelectorAll('.photo-book[data-permissions=admin]');

        Bulma.each(elements, element => {
            new PhotoGalleryAdmin({
                element: element,
                bookId: element.dataset.bookId,
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
        this.bookId = options.bookId;

        // Set defaults
        this.selectedIds = [];
        this.sortableLists = [];
        this.sortableActive = false;

        // Init functionality
        this.initCheckboxes();
        this.initDeleteButton();
        this.initSortable();

        // Disable selection based controls
        this.element.querySelectorAll('.photo-selection-control').forEach( el => {
            el.disabled = true;
        });
    }

    initCheckboxes() {
        let elements = this.element.querySelectorAll('.photo-gallery .photo');

        Bulma.each(elements, element => {
            let checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.classList.add('admin-selector');
            checkbox.value = element.dataset.id;
            checkbox.addEventListener('change', this.handlePhotoSelect.bind(this));

            // Add to `.photo` to prevent interference with `.photo > a`
            element.append(checkbox);
        });
    }

    initDeleteButton() {
        const button = this.element.querySelector('#delete-selected-photos-button');
        if (button) {
            button.addEventListener('click', this.handleDelete.bind(this));

            // Grab url from html
            this.deletePhotosUrl = button.dataset.deletePhotosUrl;
        }
    }

    initSortable() {
        // Init sort button and grab urls from html
        this.sortableButton = this.element.querySelector('#order-photos-button');
        if (!this.sortableButton)
            return;
        this.sortableButton.addEventListener('click', this.handleSortable.bind(this));
        this.photoOrderUrl = this.sortableButton.dataset.photoOrderUrl;
        this.bookOrderUrl = this.sortableButton.dataset.bookOrderUrl;

        // Init sortable photo galleries
        const photo_galleries = this.element.querySelectorAll('.photo-gallery');
        Bulma.each(photo_galleries, element => {
            this.sortableLists.push(new Sortable(element, {
                disabled: 'true',
                handle: '.photo a',
                onUpdate: this.handleSort.bind(this, this.photoOrderUrl)
            }));
        });

        // Init sortable book galleries
        const book_galleries = this.element.querySelectorAll('.book-gallery');
        Bulma.each(book_galleries, element => {
            this.sortableLists.push(new Sortable(element, {
                disabled: 'true',
                handle: '.book a',
                onUpdate: this.handleSort.bind(this, this.bookOrderUrl)
            }));
        });

        // Randomise wiggle
        this.element.querySelectorAll('.photo-gallery > .photo, .book-gallery > .book').forEach( element => {
            const duration = getComputedStyle(element).getPropertyValue('--wiggle-duration');
            const delay = -1 * parseFloat(duration) * Math.random();
            element.style.setProperty("--wiggle-delay", delay + 's');
        });
    }

    handlePhotoSelect(event) {
        if (event.target.checked)
            this.selectedIds.push(event.target.value);
        else // Remove all instances of ID
            this.selectedIds = this.selectedIds.filter(x => x != event.target.value);

        // Toggle selection based controlls (if needed)
        this.element.querySelectorAll('.photo-selection-control').forEach( el => {
            el.disabled = this.selectedIds.length === 0;
        });
    }

    handleDelete(event) {
        if (this.selectedIds.length === 0)
            return;

        // Deconstruct url from template
        let url = new URL(this.deletePhotosUrl, window.location.href);
        const params = new URLSearchParams(url.search);

        // Add ID parameters
        for (let id of this.selectedIds)
            params.append('photo_id[]', id);

        // Reconstruct url
        url.search = params.toString();

        // Execute request and load modal
        const request = fetch(url.toString());
        new AutoPopup({contentType: 'modal'}, request);
    }

    handleSortable(event) {
        if (this.sortableActive) {
            // Deactivate sorting
            this.sortableActive = false;
            this.sortableButton.classList.remove('is-active');

            for (let list of this.sortableLists) {
                list.option('disabled', true);
                list.el.classList.remove('is-sortable');
            }
        } else {
            // Activate sorting
            this.sortableActive = true;
            this.sortableButton.classList.add('is-active');

            for (let list of this.sortableLists){
                list.option('disabled', false);
                list.el.classList.add('is-sortable');
            }
        }
    }

    handleSort(url, event) {
        // Collect and submit new order
        const list = Sortable.get(event.to);
        const data = new FormData();
        
        for (let id of list.toArray())
            data.append('order[]', id);

        fetch(url, {
            method: 'POST',
            body: new URLSearchParams(data)
        }).then(response => {
            if (!response.ok) {
                throw new Error('The order could not be saved. Generally, this happens when the book is automatically generated or you don’t have permission to update the book.');
            }
        }).catch(error => {
            alert(error);
        });
    }
}

PhotoGalleryAdmin.parseDocument(document);

export default PhotoGalleryAdmin;
