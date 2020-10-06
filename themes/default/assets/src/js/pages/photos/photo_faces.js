import {Bulma} from 'cover-style-system/src/js';


class PhotoFaces {
    static parseDocument(context) {
        const elements = context.querySelectorAll('.photo-single .photo .image');

        Bulma.each(elements, element => {
            const tagLists = element.closest('.photo-single').querySelectorAll('.photo-image .faces');

            new PhotoFaces({
                element: element,
                tagLists: tagLists,
            });
        });
    }

    constructor(options) {
        this.element = options.element;
        this.tagLists = options.taglists;
        this.imgElement = this.element.querySelector('img');
        this.faces = this.element.querySelector('.faces');

        this.init();
    }

    init() {
        this.imgElement.addEventListener('load', this.updatePositions.bind(this));
        window.addEventListener('resize', this.handleResize.bind(this));
        this.faces.hidden = false;
    }

    updatePositions() {
        const faces = this.faces.querySelectorAll('.face');

        const ratioY = this.imgElement.height / this.imgElement.naturalHeight;
        const ratioX = this.imgElement.width / this.imgElement.naturalWidth;

        let offsetX = 0;
        let offsetY = 0;
        let realHeight = this.imgElement.height;
        let realWidth = this.imgElement.width;

        if (ratioY > ratioX) {
            realHeight = this.imgElement.naturalHeight * ratioX;
            offsetY = (this.imgElement.height - realHeight) / 2;
        } else if (ratioX > ratioY) {
            realWidth = this.imgElement.naturalWidth * ratioY;
            offsetX = (this.imgElement.width - realWidth) / 2;
        }

        for (const face of faces) {
            const pos = JSON.parse(face.dataset.position);

            face.style.setProperty('top', `${(realHeight * pos.y/100) + offsetY}px`);
            face.style.setProperty('left', `${(realWidth * pos.x/100) + offsetX}px`);
            face.style.setProperty('height', `${(realHeight * pos.h/100)}px`);
            face.style.setProperty('width', `${(realWidth * pos.w/100)}px`);
        }
    }

    handleResize() {
        if (this.imgElement.complete)
            this.updatePositions();
    }
}


PhotoFaces.parseDocument(document);
document.addEventListener('partial-content-loaded', event => PhotoFaces.parseDocument(event.detail));

export default PhotoFaces;
