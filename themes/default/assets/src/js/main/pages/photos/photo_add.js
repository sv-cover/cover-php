import {Bulma} from 'cover-style-system/src/js';


const SELECT_SIZE = 10;

class AddPhotosAdmin {
    static parseDocument(context) {
        const elements = context.querySelectorAll('.add-photos');

        Bulma.each(elements, element => {
            new AddPhotosAdmin({
                element: element,
                apiBaseUrl: element.dataset.apiBaseUrl,
                photoBaseUrl: element.dataset.photoBaseUrl,
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
        this.apiBaseUrl = options.apiBaseUrl;
        this.photoBaseUrl = options.photoBaseUrl;
        this.stream = null;
        this.initForm();
        this.initFolderSelector();
        this.initPhotoSelector();
    }

    initFolderSelector() {
        this.folderSelector = this.element.querySelector('#folder-selector');

        let element = document.createElement('select');
        element.size = SELECT_SIZE;
        element.addEventListener('change', this.handleFolderSelect.bind(this));
        this.folderSelector.append(element);

        this.loadFolders('', element);
    }

    initForm() {
        this.form = this.element.querySelector('#add-photos-form');
        this.form.addEventListener('submit', this.handleSubmit.bind(this));
    }

    initPhotoSelector() {
        this.photoSelector = this.element.querySelector('#photo-selector tbody');
        this.photoTemplate = this.photoSelector.querySelector('#photo-template');
        this.photoTemplate.removeAttribute('id');
        this.photoTemplate.remove();
    }

    getBasename(path) {
        let match = path.match(/\/([^\/]+)$/);
        return match ? match[1] : path;
    }

    loadFolders(path, element) {
        // Deconstruct url from template
        let url = new URL(this.apiBaseUrl, window.location.href);
        const params = new URLSearchParams(url.search);

        params.append('view', 'add_photos_list_folders');
        params.append('path', path);

        // Reconstruct url
        url.search = params.toString();

        // Execute request and load modal
        fetch(url.toString())
        .then(response => response.json())
        .then(data => {
            for (let folder of data) {
                let option = document.createElement('option');
                option.value = folder;
                option.text = this.getBasename(folder);
                element.add(option, null);
            }
        });
    }

    loadPhotos(path) {
        if (this.stream)
            this.stream.close();

        this._photoIndex = 0;

        // Remove all current photos
        while (this.photoSelector.firstChild)
            this.photoSelector.removeChild(this.photoSelector.firstChild);

        // Deconstruct url from template
        let url = new URL(this.apiBaseUrl, window.location.href);
        const params = new URLSearchParams(url.search);

        params.append('view', 'add_photos_list_photos');
        params.append('path', path);

        // Reconstruct url
        url.search = params.toString();

        this.stream = new EventSource(url.toString());

        this.stream.addEventListener('error', this.handleError.bind(this));
        this.stream.addEventListener('end', event => this.stream.close());
        this.stream.addEventListener('photo', this.handlePhoto.bind(this));
    }

    handleError(event) {
        let data = JSON.parse(event.data);

        // Prepare notification
        let notificationElement = document.createElement('div');
        notificationElement.classList.add('notification', 'is-danger');
        notificationElement.append(document.createTextNode(data.message));

        // Prepare table structure
        let trElement = document.createElement('tr');
        let tdElement = document.createElement('td');
        tdElement.colSpan = this.photoTemplate.childElementCount;

        // Insert into DOM
        tdElement.append(notificationElement);
        trElement.append(tdElement);
        this.photoSelector.append(trElement);
    }

    handleFolderSelect(event) {
        event.preventDefault();
        event.stopPropagation();

        // Prepare select box
        let element = event.target.nextElementSibling;
        if (element) {
            // Clear current column
            while (element.firstChild)
                element.removeChild(element.firstChild);

            // Remove all next column
            while (element.nextElementSibling)
                element.nextElementSibling.remove();
        } else {
            // event.target is last column
            element = document.createElement('select');
            element.size = SELECT_SIZE;
            element.addEventListener('change', this.handleFolderSelect.bind(this));
            this.folderSelector.append(element);
        }

        // Scroll folder selector all the way to the right
        this.folderSelector.scrollLeft = this.folderSelector.scrollWidth - this.folderSelector.clientWidth;

        // Load folders and photos (if any)
        this.loadFolders(event.target.value, element);
        this.loadPhotos(event.target.value);
    }

    handlePhoto(event) {
        let photo = JSON.parse(event.data);

        // Clone template
        let element = this.photoTemplate.cloneNode(true);
        const createdOn = Date.parse(photo['created_on'].replace(' ', 'T'));
        const baseName = 'photo[' + this._photoIndex + ']';
        element.dataset.sortOrder = createdOn;

        // Insert at right chronological position or at the end of the list
        for (let el of this.photoSelector.querySelectorAll('li.photo'))
            if (el.dataset.sortOrder > createdOn)
                this.photoSelector.insertBefore(element, el);

        // If not inserted, insert at the end of the list
        if (!element.parentElement)
            this.photoSelector.append(element);

        // Fill thumbnail
        element.querySelector('.thumbnail img').src = photo['thumbnail'];

        // Fill filename
        let filenameElement = element.querySelector('.filename');
        filenameElement.append(document.createTextNode(this.getBasename(photo['path'])));
        filenameElement.title = photo['path'];

        // Fill created-on
        element.querySelector('.created-on').append(document.createTextNode(photo['created_on']));

        // Fill path input
        let pathInputElement = element.querySelector('input[type=hidden]');
        pathInputElement.name = baseName + '[path]';
        pathInputElement.value = photo['path'];

        // Set description and input controls depending on whether the photo is already added to the book.
        let descriptionInputElement = element.querySelector('.description input');
        if (photo['id'] != null) {
            element.querySelector('.add-control input').remove();
            element.querySelector('.add-control a').href = this.photoBaseUrl + photo['id'];

            descriptionInputElement.remove();
            element.querySelector('.description').append(document.createTextNode(photo['description']));
        } else {
            element.querySelector('.add-control a').remove();
            element.querySelector('.add-control input').name = baseName + '[add]';

            descriptionInputElement.name = baseName + '[description]';
            descriptionInputElement.value = photo['description'];
        }

        this._photoIndex++;
    }

    handleSubmit(event) {
        // Disable form elements
        setTimeout(() => {
            this.form.querySelectorAll('select, input, button').forEach(element => {
                element.disabled = true;
            });
        }, 10);
    }
}

AddPhotosAdmin.parseDocument(document);

export default AddPhotosAdmin;
