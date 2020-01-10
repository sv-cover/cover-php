import {Bulma, AutoPopup} from 'cover-style-system/src/js';

class PhotoGalleryAdmin {
    static parseDocument(context) {
        let elements = context.querySelectorAll('.photo-gallery[data-permissions=admin]');

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

        this.initCheckboxes();
        this.deleteButton = this.initDeleteButton();

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
        let buttons = this.element.querySelectorAll('#delete-selected-photos-button');

        Bulma.each(buttons, button => {
            button.addEventListener('click', evt => {
                if (this.selectedIds.length === 0)
                    return;

                const params = new URLSearchParams({
                    book: this.bookId,
                    view: 'delete_photos'
                });

                for (const id of this.selectedIds)
                    params.append('photo_id[]', id);

                let request = fetch('fotoboek.php?' + params.toString());
                new AutoPopup({contentType: 'modal'}, request);
            });
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
}

PhotoGalleryAdmin.parseDocument(document);

export default PhotoGalleryAdmin;
