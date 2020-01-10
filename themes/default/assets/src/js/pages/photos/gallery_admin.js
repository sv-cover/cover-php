import {Bulma, AutoPopup} from 'cover-style-system/src/js';
import Sortable from 'sortablejs';

class PhotoGalleryAdmin {
    static parseDocument(context) {
        const elements = context.querySelectorAll('.photo-gallery[data-permissions=admin]');

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
        this.selectedIds = [];
        this.sortableLists = [];
        this.sortableActive = false;

        this.initCheckboxes();
        this.initDeleteButton();
        this.initSortable();

        this.element.querySelectorAll('.photo-selection-control').forEach( el => {
            el.disabled = true;
        });
    }

    initCheckboxes() {
        let elements = this.element.querySelectorAll('.gallery .photo');

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

        button.addEventListener('click', this.handleDelete.bind(this));
    }

    initSortable() {
        this.sortableButton = this.element.querySelector('#order-photos-button');
        this.sortableButton.addEventListener('click', this.handleSortable.bind(this));
        this.photoOrderUrl = this.sortableButton.dataset.photoOrderUrl;
        this.bookOrderUrl = this.sortableButton.dataset.bookOrderUrl;

        // Init sortable photo galleries
        const photo_galleries = this.element.querySelectorAll('.gallery');
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
                handle: '.book',
                onUpdate: this.handleSort.bind(this, this.bookOrderUrl)
            }));
        });

        // Randomise wiggle
        this.element.querySelectorAll('.gallery > .photo, .book-gallery > .column').forEach( element => {
            const duration = getComputedStyle(element).getPropertyValue('--wiggle-duration');
            const delay = -1 * parseFloat(duration) * Math.random();
            element.style.setProperty("--wiggle-delay", delay + 's');
        });
    }

    handlePhotoSelect(event) {
        if (event.target.checked)
            this.selectedIds.push(event.target.value);
        else
            this.selectedIds = this.selectedIds.filter(x => x != event.target.value);


        this.element.querySelectorAll('.photo-selection-control').forEach( el => {
            el.disabled = this.selectedIds.length === 0;
        });
    }

    handleDelete(event) {
        if (this.selectedIds.length === 0)
            return;

        const params = new URLSearchParams({
            book: this.bookId,
            view: 'delete_photos'
        });

        for (let id of this.selectedIds)
            params.append('photo_id[]', id);

        const request = fetch('fotoboek.php?' + params.toString());
        new AutoPopup({contentType: 'modal'}, request);
    }

    handleSortable(event) {
        if (this.sortableActive) {
            this.sortableActive = false;
            for (let list of this.sortableLists) {
                list.option("disabled", true);
                list.el.classList.remove('is-sortable');
            }
            this.sortableButton.classList.remove('is-active');
        } else {
            this.sortableActive = true;
            for (let list of this.sortableLists){
                list.option("disabled", false);
                list.el.classList.add('is-sortable');
            }
            this.sortableButton.classList.add('is-active');
        }
    }

    handleSort(url, event) {
        const list = Sortable.get(event.to);
        const data = new FormData();
        for (let id of list.toArray())
            data.append('order[]', id);

        fetch(url, {
            method: 'POST',
            body: new URLSearchParams(data)
        });
    }
}

PhotoGalleryAdmin.parseDocument(document);

export default PhotoGalleryAdmin;
