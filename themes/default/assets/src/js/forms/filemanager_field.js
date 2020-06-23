import {Bulma} from 'cover-style-system/src/js';
import Modal from '@vizuaalog/bulmajs/src/plugins/modal';

/**
 * FilemanagerField plugin to generate a Bulma file component that uses the 
 * Cover filemanager als source. Supports the following data options:
 *
 * filemanager = Boolean attribute to enable the plugin
 * filemanager-url = the Cover filemanager root url. (default: "https://filemanager.svcover.nl")
 * filemanarge-class = classlist to add to the Bulma file component (default: "is-fullwidth")
 * filemanager-cta = the call to action on the Bulma file component (default: "Choose a file…")
 * filemanager-icon = the classlist for the CTA icon on the Bulma file component (default: "fas fa-upload")
 * filemanager-no-name = Boolean attribute to disable the name in the Bulma file component
 * filemanager-title = the title of the filemanager modal (default: filemanager-cta)
 */
class FilemanagerField {
    static parseDocument(context) {
        const elements = context.querySelectorAll('input[data-filemanager]');

        Bulma.each(elements, element => {
            const cta = element.dataset.filemanagerCta || 'Choose a file…';
            const title = element.dataset.filemanagerTitle || cta;
            new FilemanagerField({
                element: element,
                filemanagerUrl: element.dataset.filemanagerUrl || 'https://filemanager.svcover.nl',
                filemanagerClass: element.dataset.filemanagerClass || 'is-fullwidth',
                filemanagerCta: cta,
                filemanagerIcon: element.dataset.filemanagerIcon || 'fas fa-upload',
                filemanagerNoName: (element.dataset.filemanagerNoName != null),
                filemanagerTitle: title,
            });
        });

    }

    constructor(options) {
        this.filemanagerUrl = options.filemanagerUrl;
        this.filemanagerTitle = options.filemanagerTitle;
        this.firstOpen = true;

        this.initUI(options);
        this.initModal();
        this.setupEvents();
    }

    /**
     * Replaces fallback text input with slightly modified Bulma file component:
     * div.file
     *   div.file-label
     *     input
     *     span.file-cta
     *       span.file-icon
     *         i.fa
     *       span.file-label
     *     span.file-name
     */
    initUI(options) {
        // Init wrappers
        let containerElement = document.createElement('div');
        containerElement.classList.add('file', ...(options.filemanagerClass.split(' ')));
        if (!options.filemanagerNoName)
            containerElement.classList.add('has-name');
        
        let labelElement = document.createElement('div');
        labelElement.classList.add('file-label');

        // Init CTA
        let ctaElement = document.createElement('span');
        ctaElement.classList.add('file-cta');

        let ctaIconElement = document.createElement('span');
        ctaIconElement.classList.add('file-icon');

        let ctaIconFaElement = document.createElement('i');
        ctaIconFaElement.classList.add(...(options.filemanagerIcon.split(' ')));

        let ctaLabelElement = document.createElement('span');
        ctaLabelElement.classList.add('file-label');
        ctaLabelElement.appendChild(document.createTextNode(options.filemanagerCta));

        // Construct CTA
        ctaIconElement.appendChild(ctaIconFaElement);
        ctaElement.appendChild(ctaIconElement);
        ctaElement.appendChild(ctaLabelElement);

        // Clone input
        let inputElement = options.element.cloneNode(true);
        inputElement.classList.remove('input');
        inputElement.classList.add('file-input');

        // Construct main "label" wrapper
        labelElement.appendChild(inputElement);
        labelElement.appendChild(ctaElement);

        // Init file-name
        if (!options.filemanagerNoName) {
            let fileNameElement = document.createElement('span');
            fileNameElement.classList.add('file-name');

            if (options.element.value) {
                fileNameElement.appendChild(document.createTextNode(options.element.value));
                fileNameElement.title = options.element.value;
            }

            labelElement.appendChild(fileNameElement);
            this.fileNameElement = fileNameElement;
        }

        // Construct main component
        containerElement.appendChild(labelElement);

        // Place in DOM
        options.element.replaceWith(containerElement);

        // Store stuff for later access
        this.element = containerElement;
        this.inputElement = inputElement;
    }

    initModal() {
        // Init iframe and modal
        const src = `${this.filemanagerUrl}/fileman`;
        const body = `<iframe src="${src}" title="Filemanager Window"></iframe>`;
        this.modal = Bulma.create('modal', {
            root: this.element,
            title: this.filemanagerTitle,
            body: body,
            style: 'card',
        });
        this.modal.element.classList.add('filemanager');

        // Get reference to iframe
        this.filemanagerIframe = this.modal.content.querySelector('iframe');
    }

    setupEvents() {
        this.element.addEventListener('click', this.handleClick.bind(this));
        window.addEventListener('message', this.handleMessage.bind(this), false);
    }

    handleClick() {
        if (this.firstOpen) {
            // The iframe doesn't load correctly in some browsers. Reload on first open.
            this.filemanagerIframe.src += '';
            this.firstOpen = false;
        }
        this.modal.open();
    }

    handleMessage(event) {
        // Make sure the message originates from our own iframe
        if (this.filemanagerIframe.contentWindow === event.source) {
            const file = JSON.parse(event.data);
            this.pickFile(file);
        }
    }

    pickFile(file) {
        // Derrive correct url
        const fileUrl = `${file.fullPath}`;

        // Set url everywhere
        this.inputElement.value = fileUrl;
        if (this.fileNameElement) {
            this.fileNameElement.innerText = fileUrl;
            this.fileNameElement.title = fileUrl;
        }

        // Close modal
        this.modal.close();
    }
}

FilemanagerField.parseDocument(document);
document.addEventListener('partial-content-loaded', event => FilemanagerField.parseDocument(event.detail));

export default FilemanagerField;
