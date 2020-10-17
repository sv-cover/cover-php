import {Bulma} from 'cover-style-system/src/js';
import Hammer from 'hammerjs';

const PHOTO_FACE_MIN_SIZE = 2;

class DragHandler {
    constructor({element, onStart=null, onEnd=null, onMove=null, enabled=true, stopPropagation=false}) {
        this.element = element;
        this.enabled = enabled;
        this.stopPropagation = stopPropagation;

        this.onStart = onStart;
        this.onEnd = onEnd;
        this.onMove = onMove;

        this.start = false;

        // Handle Start
        element.addEventListener('pointerdown', this.handleStart.bind(this));
        
        // Handle Move (even if moved of target)
        element.addEventListener('pointermove', this.handleMove.bind(this));
        document.addEventListener('pointermove', this.handleMove.bind(this));

        // Handle End (even if moved of target)
        element.addEventListener('pointerup', this.handleEnd.bind(this));
        document.addEventListener('pointerup', this.handleEnd.bind(this));
    }

    isEnabled() {
        if (this.enabled instanceof Function)
            return this.enabled();
        return this.enabled;
    }

    handleStart(event) {
        if (!this.isEnabled())
            return;

        if (this.stopPropagation)
            event.stopPropagation();

        this.start = {
            x: event.clientX,
            y: event.clientY,
        };

        if (this.onStart)
            this.onStart(event, {x: 0, y:0});
    }

    handleEnd(event) {
        if (!this.isEnabled() || !this.start)
            return;

        if (this.stopPropagation)
            event.stopPropagation();

        const delta = {
            x: event.clientX - this.start.x,
            y: event.clientY - this.start.y,
        };

        this.start = false;

        if (this.onEnd)
            this.onEnd(event, delta);
    }

    handleMove(event) {
        if (!this.isEnabled() || !this.start)
            return;

        if (this.stopPropagation)
            event.stopPropagation();

        const delta = {
            x: event.clientX - this.start.x,
            y: event.clientY - this.start.y,
        };

        if (this.onMove)
            this.onMove(event, delta);
    }
}


class Face {
    constructor(options) {
        this.element = options.element;
        this.canTag = false;
        this.imgScale = {
            offsetX: 0,
            offsetY: 0,
            width: 0,
            height: 0,
        }
        this.position = JSON.parse(this.element.dataset.position);
        this.deltaPosition = {
            x: 0,
            y: 0,
            w: 0,
            h: 0,
        };
        this.init();
    }

    init() {
        if (this.element.dataset.updateAction) {
            this.createResizeGrip('is-horizontal');
            this.createResizeGrip('is-vertical');
            this.createResizeGrip('is-diagonal');
            this.enableGestures();
        }
    }

    createResizeGrip(cls) {
        let grip = document.createElement('span');
        grip.classList.add('resize', cls);
        this.element.append(grip);
        
        new DragHandler({
            element: grip,
            enabled: () => this.canTag,
            stopPropagation: true,
            onMove: this.handleResize.bind(this, 'move'),
            onEnd: this.handleResize.bind(this, 'end'),
        });
    }

    disableTagging() {
        this.canTag = false;

        this.element.querySelector('.delete').remove();

        for (const el of this.element.querySelectorAll('.resize'))
            el.remove();
    }

    enableTagging() {
        this.canTag = true;

        if (this.element.dataset.deleteAction) {
            let deleteButton = document.createElement('button');
            deleteButton.classList.add('delete', 'is-small');
            deleteButton.addEventListener('click', this.handleDelete.bind(this));
            this.element.append(deleteButton);
        }
    }

    enableGestures() {
        new DragHandler({
            element: this.element,
            enabled: () => this.canTag,
            stopPropagation: true,
            onMove: this.handleMove.bind(this, 'move'),
            onEnd: this.handleMove.bind(this, 'end'),
        });

        let mc = new Hammer.Manager(this.element, {
            enabled: () => this.canTag,
        });

        const pinch = new Hammer.Pinch();

        mc.add([pinch]);

        // Bind events
        mc.on('pinch', this.handlePinch.bind(this));
    }

    async handleDelete(event) {
        event.preventDefault();
        event.stopPropagation();

        if (this.element.dataset.deleteAction) {
            const init = {
                'method': 'POST',
                'headers': {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
            };
            const response = await fetch (this.element.dataset.deleteAction, init);

            if (response.ok) {
                this.element.remove();
                // TODO: Update list
            }
            // TODO: handle errors
        }
    }

    handleMove(type, event, delta) {
        if (!this.canTag)
            return;

        const newPos = this.calculateNewPosition(delta.x, delta.y, 0, 0);

        if (type === 'end') {
            this.commitPosition(newPos);
        } else if (type === 'move') {
            this._updatePostion(newPos);
        }
    }

    handleResize(type, event, delta) {
        if (!this.canTag)
            return;

        const newPos = this.calculateNewPosition(0, 0, delta.x, delta.y);

        if (type === 'end') {
            this.commitPosition(newPos);
        } else if (type === 'move') {
            this._updatePostion(newPos);
        }
    }

    handlePinch(event) {
        if (!this.canTag)
            return;

        const oldPos = this.getAbsolutePosition();

        const dw = oldPos.w * event.scale;
        const dh = oldPos.h * event.scale;

        const newPos = this.calculateNewPosition(-dw/2, -dh/2, dw, dh);

        if (event.eventType & Hammer.INPUT_END) {
            this.commitPosition(newPos);
        } else if (event.eventType & Hammer.INPUT_CANCEL) {
            this._updatePostion();
        } else {
            this._updatePostion(newPos);
        }
    }

    async submitUpdate(data) {        
        if (this.element.dataset.updateAction) {
            const formData = new FormData();

            for (const name in data)
                formData.append(name, data[name]);

            const init = {
                'method': 'POST',
                'headers': {
                    'Accept': 'application/json',
                },
                'body': new URLSearchParams(formData),
            };
            const response = await fetch (this.element.dataset.updateAction, init);

            if (!response.ok) {
                throw new Error('Error during update');
            }
        }
    }

    /****
     * Position stuff
     */ 

    calculateNewPosition(dx, dy, dw, dh) {
        const oldPos = this.getAbsolutePosition();
        let newPos = {
            x: oldPos.x + dx,
            y: oldPos.y + dy,
            w: oldPos.w + dw,
            h: oldPos.h + dh,
        };

        // Ensure squareness
        newPos.h = Math.max(newPos.h, newPos.w);
        newPos.w = newPos.h;

        // too small
        if (newPos.h < PHOTO_FACE_MIN_SIZE) {
            newPos.h = PHOTO_FACE_MIN_SIZE;
            newPos.w = PHOTO_FACE_MIN_SIZE;
        }

        // too far to the left
        if (newPos.x < this.imgScale.offsetX)
            newPos.x = this.imgScale.offsetX;

        // too wide or too far to the right
        if (newPos.x + newPos.w > this.imgScale.width + this.imgScale.offsetX) {
            if (dx !== 0) {
                newPos.x = this.imgScale.width + this.imgScale.offsetX - newPos.w;
            } else {
                newPos.w = this.imgScale.width + this.imgScale.offsetX - newPos.x;
                newPos.h = newPos.w;
            }
        }

        // too far to the top
        if (newPos.y < this.imgScale.offsetY)
            newPos.y = this.imgScale.offsetY;

        // too tall or too far to the bottom
        if (newPos.y + newPos.h > this.imgScale.height + this.imgScale.offsetY) {
            if (dx !== 0) {
                newPos.y = this.imgScale.height + this.imgScale.offsetY - newPos.h;
            } else {
                newPos.h = this.imgScale.height + this.imgScale.offsetY - newPos.y;
                newPos.w = newPos.h; // this should work, as we can only shrink here
            }
        }

        return newPos;
    }

    commitPosition(newAbsPos) {
        const newPos = {
            x: (newAbsPos.x - this.imgScale.offsetX) / this.imgScale.width,
            y: (newAbsPos.y - this.imgScale.offsetY) / this.imgScale.height,
            w: newAbsPos.w / this.imgScale.width,
            h: newAbsPos.h / this.imgScale.height,
        };

        this.submitUpdate(newPos).then(() => {
                this.position = newPos;
                this._updatePostion();
        }).catch(() => {/* Revert to initial position */});
    }

    getAbsolutePosition() {
        return {
            x: (this.imgScale.width * this.position.x) + this.imgScale.offsetX + this.deltaPosition.x,
            y: (this.imgScale.height * this.position.y) + this.imgScale.offsetY + this.deltaPosition.y,
            w: (this.imgScale.width * this.position.w) + this.deltaPosition.w,
            h: (this.imgScale.height * this.position.h) + this.deltaPosition.h,
        };
    }

    _updatePostion(pos=null) {
        if (!pos)
            pos = this.getAbsolutePosition();

        this.element.style.setProperty('top', `${pos.y}px`);
        this.element.style.setProperty('left', `${pos.x}px`);
        this.element.style.setProperty('height', `${pos.h}px`);
        this.element.style.setProperty('width', `${pos.w}px`);
    }

    updatePostion(realWidth, realHeight, offsetX, offsetY) {
        this.imgScale = {
            offsetX: offsetX,
            offsetY: offsetY,
            width: realWidth,
            height: realHeight,
        };

        this._updatePostion();
    }
}


class PhotoFaces {
    static parseDocument(context) {
        const elements = context.querySelectorAll('.photo-single .photo .image');

        Bulma.each(elements, element => {
            const tagLists = element.closest('.photo-single').querySelectorAll('.photo-image .faces');
            const tagButtons = element.closest('.photo-single').querySelectorAll('.photo-tag-button');

            new PhotoFaces({
                element: element,
                tagLists: tagLists,
                tagButtons: tagButtons,
            });
        });
    }

    constructor(options) {
        this.element = options.element;
        this.tagLists = options.tagLists;
        this.tagButtons = options.tagButtons;
        this.imgElement = this.element.querySelector('img');
        this.facesElement = this.element.querySelector('.faces');
        this.faces = [];

        this.init();
    }

    init() {
        this.initFaces();

        this.imgElement.addEventListener('load', this.updatePositions.bind(this));
        window.addEventListener('resize', this.handleResize.bind(this));
        this.isTagging = false;

        this.initButtons();
        this.facesElement.hidden = false;
    }

    initButtons() {
        for (const button of this.tagButtons)
            button.addEventListener('click', this.handleToggleTagging.bind(this));
    }

    initFaces() {
        this.faces = [];

        for (const face of this.facesElement.querySelectorAll('.face')) {
            this.faces.push(new Face({
                element: face,
            }));
        }
    }

    updatePositions() {
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

        for (const face of this.faces)
            face.updatePostion(realWidth, realHeight, offsetX, offsetY);
    }

    disableTagging() {
        this.isTagging = false;

        this.facesElement.classList.remove('is-active');

        for (const button of this.tagButtons)
            button.classList.remove('is-active');

        for (const face of this.faces)
            face.disableTagging();
    }

    enableTagging() {
        this.isTagging = true;

        this.facesElement.classList.add('is-active');

        for (const face of this.faces)
            face.enableTagging();

        for (const button of this.tagButtons)
            button.classList.add('is-active');
    }

    handleToggleTagging() {
        if (this.isTagging)
            this.disableTagging();
        else
            this.enableTagging();
    }

    handleResize() {
        if (this.imgElement.complete)
            this.updatePositions();
    }
}


PhotoFaces.parseDocument(document);
document.addEventListener('partial-content-loaded', event => PhotoFaces.parseDocument(event.detail));

export default PhotoFaces;
