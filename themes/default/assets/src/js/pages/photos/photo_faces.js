import {Bulma} from 'cover-style-system/src/js';
import AutocompleteMember from '../../forms/autocomplete_member';
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

        if (event.target instanceof HTMLInputElement)
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
        this.parent = options.parent;
        this.data = options.data
        this.element = options.template.content.firstElementChild.cloneNode(true);
        this._imgScale = options.imgScale;
        this._canTag = options.enabled;

        this.init();
    }

    init() {
        this._initDelete();
        this._initResize();
        this.render();
        this.parent.append(this.element);
    }

    _initDelete() {
        this.delete = this.element.querySelector('[data-delete]');
        this.delete.addEventListener('click', this.handleDelete.bind(this));
    }

    _initResize() {
        this._initResizeGrip('is-horizontal');
        this._initResizeGrip('is-vertical');
        this._initResizeGrip('is-diagonal');

        new DragHandler({
            element: this.element,
            enabled: () => this.canUpdate() && this.canTag(),
            stopPropagation: true,
            onMove: this.handleMove.bind(this, 'move'),
            onEnd: this.handleMove.bind(this, 'end'),
        });

        let mc = new Hammer.Manager(this.element, {
            enabled: () => this.canUpdate() && this.canTag(),
        });

        const pinch = new Hammer.Pinch();

        mc.add([pinch]);

        // Bind events
        mc.on('pinch', this.handlePinch.bind(this));
    }

    _initResizeGrip(cls) {
        let grip = document.createElement('span');
        grip.classList.add('resize', cls);
        this.element.append(grip);

        new DragHandler({
            element: grip,
            enabled: () => this.canUpdate() && this.canTag(),
            stopPropagation: true,
            onMove: this.handleResize.bind(this, 'move'),
            onEnd: this.handleResize.bind(this, 'end'),
        });
    }

    render(intent='all') {
        if (intent === 'state' || intent === 'all') {
            this._renderLabel();
            this._renderDelete();
        }

        if (intent === 'position' || intent === 'all') {
            this._renderPosition();
        }
    }

    _renderLabel() {        
        for (let label of this.element.querySelectorAll('[data-label]'))
            label.hidden = true;
    }

    _renderPosition(pos=null) {
        if (!pos)
            pos = this.getPosition();

        this.element.style.setProperty('top', `${pos.y}px`);
        this.element.style.setProperty('left', `${pos.x}px`);
        this.element.style.setProperty('height', `${pos.h}px`);
        this.element.style.setProperty('width', `${pos.w}px`);
    }

    _renderDelete() {
        if (this.canDelete() && this.canTag())
            this.delete.hidden = false;
        else
            this.delete.hidden = true;
    }

    canDelete() {
        return !!this.data.__links.delete;
    }

    canUpdate() {
        return !!this.data.__links.update;
    }

    canTag() {
        if (this._canTag instanceof Function)
            return this._canTag();
        return this._canTag;        
    }

    getImgScale() {
        if (this._imgScale instanceof Function)
            return this._imgScale();
        return this._imgScale;
    }

    getPosition() {
        // TODO: Cache somehow?
        const imgScale = this.getImgScale();
        return {
            x: (imgScale.w * this.data.x) + imgScale.x,
            y: (imgScale.h * this.data.y) + imgScale.y,
            w: (imgScale.w * this.data.w),
            h: (imgScale.h * this.data.h),
        };
    }

    // Handlers

    async handleDelete(event) {
        event.preventDefault();
        event.stopPropagation();

        if (this.canDelete()) {
            const init = {
                'method': 'POST',
                'headers': {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
            };
            const response = await fetch(this.data.__links.delete, init);

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
            this.updatePosition(newPos);
        } else if (type === 'move') {
            this._renderPosition(newPos);
        }
    }

    handlePinch(event) {
        if (!this.canTag)
            return;

        const oldPos = this.getPosition();

        const dw = oldPos.w * event.scale;
        const dh = oldPos.h * event.scale;

        const newPos = this.calculateNewPosition(-dw/2, -dh/2, dw, dh);

        if (event.eventType & Hammer.INPUT_END) {
            this.updatePosition(newPos);
        } else if (event.eventType & Hammer.INPUT_CANCEL) {
            this._renderPosition();
        } else {
            this._renderPosition(newPos);
        }
    }

    handleResize(type, event, delta) {
        if (!this.canTag)
            return;

        const newPos = this.calculateNewPosition(0, 0, delta.x, delta.y);

        if (type === 'end') {
            this.updatePosition(newPos);
        } else if (type === 'move') {
            this._renderPosition(newPos);
        }
    }

    async submitUpdate(data) {
        if (this.canUpdate()) {
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

            const response = await fetch(this.data.__links.update, init);

            if (!response.ok) {
                throw new Error('Error during update');
            }

            const result = await response.json();
            this.data = result.iter
        }
    }

    calculateNewPosition(dx, dy, dw, dh) {
        const oldPos = this.getPosition();
        const imgScale = this.getImgScale();

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
        if (newPos.x < imgScale.x)
            newPos.x = imgScale.x;

        // too wide or too far to the right
        if (newPos.x + newPos.w > imgScale.w + imgScale.x) {
            if (dx !== 0) {
                newPos.x = imgScale.w + imgScale.x - newPos.w;
            } else {
                newPos.w = imgScale.w + imgScale.x - newPos.x;
                newPos.h = newPos.w;
            }
        }

        // too far to the top
        if (newPos.y < imgScale.y)
            newPos.y = imgScale.y;

        // too tall or too far to the bottom
        if (newPos.y + newPos.h > imgScale.h + imgScale.y) {
            if (dx !== 0) {
                newPos.y = imgScale.h + imgScale.y - newPos.h;
            } else {
                newPos.h = imgScale.h + imgScale.y - newPos.y;
                newPos.w = newPos.h; // this should work, as we can only shrink here
            }
        }

        return newPos;
    }

    async updatePosition(newAbsPos) {
        const imgScale = this.getImgScale();

        const newPos = {
            x: (newAbsPos.x - imgScale.x) / imgScale.w,
            y: (newAbsPos.y - imgScale.y) / imgScale.h,
            w: newAbsPos.w / imgScale.w,
            h: newAbsPos.h / imgScale.h,
        };

        try {
            await this.submitUpdate(newPos);
        } finally {
            this.render('position');
        }
    }

}


class TaggedFace extends Face {
    _renderLabel() {
        super._renderLabel();

        if (this.data.member_id) {
            let label = this.element.querySelector('[data-label-member]');
            label.hidden = false;
            let name = label.querySelector('[data-name]');
            name.textContent = this.data.member_full_name;
            name.href = this.data.member_url;
        } else if (this.data.custom_label) {
            let label = this.element.querySelector('[data-label-custom]');
            label.hidden = false;
            let name = label.querySelector('[data-name]');
            name.textContent = this.data.custom_label;
        }
        // TODO: Fallback
    }
}


class UnTaggedFace extends Face {
    _renderLabel() {
        super._renderLabel();

        if (this.canUpdate()) {
            let label = this.element.querySelector('[data-label-untagged]');
            label.hidden = false;
        } else {
            let label = this.element.querySelector('[data-label-untagged-noedit]');
            label.hidden = false;
        }
    }
}


class SuggestedFace extends Face {
    _renderLabel() {
        super._renderLabel();

        if (this.data.suggested_id) {
            let label = this.element.querySelector('[data-label-suggested]');
            label.hidden = false;
            let name = label.querySelector('[data-name]');
            name.textContent = this.data.suggested_full_name;
            name.href = this.data.suggested_url;
        }
        // TODO: Fallback
    }
}


function faceFactory(options) {
    if (options.data.member_id || options.data.custom_label)
        return new TaggedFace(options);
    else if (options.data.suggested_id)
        return new SuggestedFace(options);
    else
        return new UnTaggedFace(options);
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
        this.faceTemplate = this.facesElement.querySelector('.face-template');
        this.faceTemplate.remove();

        this.faces = [];
        this.init();
    }

    init() {
        this.initFaces();

        this.imgElement.addEventListener('load', this.updateScale.bind(this));
        window.addEventListener('resize', this.handleResize.bind(this));
        this.isTagging = false;

        this.initButtons();
        this.facesElement.hidden = false;
    }

    initButtons() {
        for (const button of this.tagButtons)
            button.addEventListener('click', this.handleToggleTagging.bind(this));
    }

    async initFaces() {
        const init = {
            'method': 'GET',
            'headers': {
                'Accept': 'application/json',
            },
        };

        const response = await fetch (this.facesElement.dataset.apiUrl, init);
        
        if (!response.ok)
            return;
        
        const data = await response.json();

        this.faces = [];

        for (const face of data.iters) {
            this.faces.push(faceFactory({
                parent: this.facesElement,
                template: this.faceTemplate,
                data: face,
                imgScale: this.getScale.bind(this),
                enabled: () => this.isTagging,
            }));
        }
    }

    getScale() {
        if (!this._scale)        
            this._scale = {
                x: 0,
                y: 0,
                w: this.imgElement.width,
                h: this.imgElement.height,
            };
        return this._scale;
    }

    updateScale() {
        const ratioY = this.imgElement.height / this.imgElement.naturalHeight;
        const ratioX = this.imgElement.width / this.imgElement.naturalWidth;

        this._scale = {
            x: 0,
            y: 0,
            w: this.imgElement.width,
            h: this.imgElement.height,
        };

        if (ratioY > ratioX) {
            this._scale.h = this.imgElement.naturalHeight * ratioX;
            this._scale.y = (this.imgElement.height - this._scale.h) / 2;
        } else if (ratioX > ratioY) {
            this._scale.w = this.imgElement.naturalWidth * ratioY;
            this._scale.x = (this.imgElement.width - this._scale.w) / 2;
        }

        this.render('position');
    }

    render(intent='all') {
        for (const face of this.faces)
            face.render(intent);
    }

    disableTagging() {
        this.isTagging = false;

        this.facesElement.classList.remove('is-active');

        for (const button of this.tagButtons)
            button.classList.remove('is-active');

        this.render('state');
    }

    enableTagging() {
        this.isTagging = true;

        this.facesElement.classList.add('is-active');

        this.render('state');

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
            this.updateScale();
    }
}


PhotoFaces.parseDocument(document);
document.addEventListener('partial-content-loaded', event => PhotoFaces.parseDocument(event.detail));

export default PhotoFaces;
