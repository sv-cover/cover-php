import {Bulma} from 'cover-style-system/src/js';
import AutocompleteMember from '../../forms/autocomplete_member';
import { DragHandler } from '../../utils';
import Hammer from 'hammerjs';


const PHOTO_FACE_MIN_SIZE = 2;
const PHOTO_FACE_DEFAULT_SIZE = 0.1;
const PHOTO_FACE_MIN_DEFAULT_SIZE = 50;


function calculateFacePosition(pos, scale, delta=null) {
    if (!delta)
        delta = { x: 0, y: 0, w: 0, h: 0 };

    let newPos = {
        x: pos.x + delta.x,
        y: pos.y + delta.y,
        w: pos.w + delta.w,
        h: pos.h + delta.h,
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
    if (newPos.x < scale.x)
        newPos.x = scale.x;

    // too wide or too far to the right
    if (newPos.x + newPos.w > scale.w + scale.x) {
        if (delta.w !== 0 || delta.h !== 0) {
            newPos.w = scale.w + scale.x - newPos.x;
            newPos.h = newPos.w;
        } else {
            newPos.x = scale.w + scale.x - newPos.w;
        }
    }

    // too far to the top
    if (newPos.y < scale.y)
        newPos.y = scale.y;

    // too tall or too far to the bottom
    if (newPos.y + newPos.h > scale.h + scale.y) {
        if (delta.w !== 0 || delta.h !== 0) {
            newPos.h = scale.h + scale.y - newPos.y;
            newPos.w = newPos.h; // this should work, as we can only shrink here
        } else {
            newPos.y = scale.h + scale.y - newPos.h;
        }
    }

    return newPos;
}

function calculateRelativeFacePosition(pos, scale) {
    return {
        x: (pos.x - scale.x) / scale.w,
        y: (pos.y - scale.y) / scale.h,
        w: pos.w / scale.w,
        h: pos.h / scale.h,
    };
}


class Face {
    constructor(options) {
        this.options = options;

        this.parent = options.parent;
        this.data = options.data;
        this.element = options.template.content.firstElementChild.cloneNode(true);
        this._imgScale = options.imgScale;
        this._canTag = options.enabled;

        this.init();
    }

    // Initialisation

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

    // Rendering

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

    // Getters/Setters

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

    getLabel() {
        return {
            label: this.data.member_id ? this.data.member_full_name : this.data.custom_label,
            label_url: this.data.member_id ? this.data.member_url : null,
        };
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

    setHighlighted(value) {
        if (value)
            this.element.classList.add('is-highlighted');
        else
            this.element.classList.remove('is-highlighted');
    }

    // Handlers

    async handleDelete(event) {
        event.preventDefault();
        event.stopPropagation();

        if (!this.canDelete())
            return;

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
            if (this.options.onDelete)
                this.options.onDelete(this);
        }
        // TODO: handle errors
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

    // Utilities

    calculateNewPosition(dx, dy, dw, dh) {
        const pos = this.getPosition();
        const scale = this.getImgScale();
        const delta = { x: dx, y: dy, w: dw, h: dh };
        return calculateFacePosition(pos, scale, delta);
    }

    replace(newFace) {
        this.element.remove();
        if (this.options.onReplace)
            this.options.onReplace(this, newFace);
    }

    async submitUpdate(data) {
        if (!this.canUpdate())
            return;

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

    async updatePosition(newAbsPos) {
        const imgScale = this.getImgScale();
        const newPos = calculateRelativeFacePosition(newAbsPos, imgScale);
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
            label.textContent = this.data.member_full_name;
            label.href = this.data.member_url;
        } else if (this.data.custom_label) {
            let label = this.element.querySelector('[data-label-custom]');
            label.hidden = false;
            label.textContent = this.data.custom_label;
        } else {
            // Fallback
            let label = this.element.querySelector('[data-label-untagged-noedit]');
            label.hidden = false;
        }
    }
}


class UnTaggedFace extends Face {
    init() {
        this._initAutocomplete();
        super.init();

        if (this.options.isActive)
            this.setActive(true);
    }

    _initAutocomplete() {
        this.isActive = false;

        // Init Autocomplete element
        this.autocomplete = new AutocompleteMember({
            element: this.element.querySelector('[data-autocomplete-placeholder]'),
            keepIdField: false,
            onHide: () => this.setActive(false),
            config: {
                onSelection: this.handleSelection.bind(this),
                noResults: () => {/* Literally do nothing */},
            }
        });

        // Handle additional events (for custom tag and more)
        this.autocomplete.sourceElement.addEventListener('keydown', this.handleKeyDown.bind(this));
        this.autocomplete.sourceElement.addEventListener('autoComplete', this.handleAutocomplete.bind(this));

        // Make sure the tag button triggers autocomplete
        const tagButton = this.element.querySelector('[data-button-autocomplete]');
        tagButton.addEventListener('click', () => this.setActive(true));
    }

    _renderLabel() {
        super._renderLabel();

        if (this.canUpdate() && this.isActive) {
            let label = this.element.querySelector('[data-label-autocomplete]');
            label.hidden = false;
            this.autocomplete.sourceElement.focus();
        } else if (this.canUpdate()) {
            let label = this.element.querySelector('[data-label-untagged]');
            label.hidden = false;
        } else {
            let label = this.element.querySelector('[data-label-untagged-noedit]');
            label.hidden = false;
        }
    }

    setActive(value) {
        this.isActive = value;
        this.render('state');

        if (!value)
            this.autocomplete.sourceElement.value = '';
        else if (this.options.setEnableTagging)
            this.options.setEnableTagging();
    }

    async submitSelection(data) {
        if (!this.canUpdate())
            return;

        try {
            await this.submitUpdate(data);

            // this.data is updated if successful
            this.options.data = this.data;

            this.replace(new TaggedFace(this.options));
        } catch (e) {
            // TODO: Provide feedback
        }
    }

    handleSelection(feedback) {
        this.submitSelection({
            'lid_id': feedback.selection.value.id,
        });
    }

    handleKeyDown(event) {
        if (event.key === 'Tab' && this.hasSuggestions) {
            // Don't tab away when suggestions
            event.preventDefault();
        } else if (event.key === 'Enter' && !this.hasSuggestions) {
            // Submit custom tag
            event.preventDefault();
            this.submitSelection({
                'custom_label': this.autocomplete.sourceElement.value,
            });
        } else if (event.key === 'Escape' || event.key === 'Esc') {
            // Allow escape
            event.preventDefault();
            this.setActive(false);
        }
    }

    handleAutocomplete(event) {
        this.hasSuggestions = !!event.detail.matches;
    }
}


class SuggestedFace extends Face {
    init() {
        super.init();
        this._initButtons();
    }

    _initButtons() {
        const acceptButton = this.element.querySelector('[data-button-yes]');
        acceptButton.addEventListener('click', this.handleAccept.bind(this));

        const rejectButton = this.element.querySelector('[data-button-no]');
        rejectButton.addEventListener('click', this.handleReject.bind(this));
    }

    _renderLabel() {
        super._renderLabel();

        if (this.data.suggested_id && this.canUpdate()) {
            let label = this.element.querySelector('[data-label-suggested]');
            label.hidden = false;
            let name = label.querySelector('[data-name]');
            name.textContent = this.data.suggested_full_name;
            name.href = this.data.suggested_url;
        } else {
            // Fallback
            let label = this.element.querySelector('[data-label-untagged-noedit]');
            label.hidden = false;
        }
    }

    async handleAccept() {
        if (!this.canUpdate())
            return;

        const data = {
            'lid_id': this.options.data.suggested_id,
        };

        try {
            await this.submitUpdate(data);

            // this.data is updated if successful
            this.options.data = this.data;

            this.replace(new TaggedFace(this.options));
        } catch (e) {
            // TODO: Provide feedback
        }
    }

    handleReject() {
        if (!this.canUpdate())
            return;

        // Just to prevent confusion…
        this.options.data.suggested_id = null;
        this.options.data.suggested_full_name = null;
        this.options.data.suggested_url = null;

        // Focus autocomplete
        this.options.isActive = true;
        this.replace(new UnTaggedFace(this.options));
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


class TagList {
    constructor(options) {
        this.element = options.element;
        this._faces = options.faces;
        this.listElement = this.element.querySelector('.face-list');
        this.template = this.element.querySelector('.face-template');
        this.template.remove();

        this.render();
    }

    render() {
        let labels = [];
        let unknownCount = 0;

        for (const face of this.getFaces()) {
            if (face.getLabel().label)
                labels.push(this._renderLabel(face));
            else
                unknownCount++;
        }

        if (unknownCount > 0)
            labels.push(this._renderUnknownLabel(unknownCount));

        while (this.listElement.firstChild)
            this.listElement.removeChild(this.listElement.firstChild);

        for (let idx = 0; idx < labels.length; idx++) {
            if (idx > 0 && idx < labels.length - 1)
                this.listElement.append(', ');
            else if (idx === labels.length - 1)
                this.listElement.append(` ${this.listElement.dataset.glue} `);

            this.listElement.append(labels[idx]);
        }
    }

    _renderLabel(face) {
        let element = this.template.content.firstElementChild.cloneNode(true);
        let label = face.getLabel();

        if (label.label_url) {
            let el = element.querySelector('[data-label-url]');
            el.href = label.label_url;
            el.textContent = label.label;
            el.hidden = false;
        } else if (label.label) {
            let el = element.querySelector('[data-label-text]');
            el.textContent = label.label;
            el.hidden = false;
        } else {
            let el = element.querySelector('[data-label-other]');
            el.hidden = false;
        }

        element.addEventListener('mouseover', () => face.setHighlighted(true));
        element.addEventListener('mouseout', () => face.setHighlighted(false));

        return element;
    }

    _renderUnknownLabel(count) {
        let element = this.template.content.firstElementChild.cloneNode(true);
        let el = element.querySelector('[data-label-unknown]');
        el.hidden = false;
        el.querySelector('[data-count]').textContent = `${count}`;
        
        if (count === 1) {
            el.querySelector('[data-singular]').hidden = false;
            el.querySelector('[data-plural]').hidden = true;
        } else {
            el.querySelector('[data-singular]').hidden = true;
            el.querySelector('[data-plural]').hidden = false;
        }

        return element;
    }

    getFaces() {
        if (this._faces instanceof Function)
            return this._faces();
        return this._faces;
    }
}


class PhotoFaces {
    static parseDocument(context) {
        const elements = context.querySelectorAll('.photo-single .photo .image');

        Bulma.each(elements, element => {
            const tagLists = element.closest('.photo-single').querySelectorAll('.photo-info .faces');
            const tagButtons = element.closest('.photo-single').querySelectorAll('.photo-tag-button');

            new PhotoFaces({
                element: element,
                tagLists: tagLists,
                tagButtons: tagButtons,
            });
        });
    }

    constructor(options) {
        this.options = options;
        this.element = options.element;
        this.tagButtons = options.tagButtons;
        this.imgElement = this.element.querySelector('img');

        this.facesElement = this.element.querySelector('.faces');
        this.faceTemplate = this.facesElement.querySelector('.face-template');
        this.faceTemplate.remove();

        this.init();
    }

    init() {
        this.faces = [];
        this.tagLists = [];

        this.imgElement.addEventListener('load', this.updateScale.bind(this));
        window.addEventListener('resize', this.handleResize.bind(this));
        
        this._initFaces().then(() => {
            this._initTagLists();
            this.updateScale();
        });

        this.imgElement.addEventListener('click', this.handleImageClick.bind(this));
        this.isTagging = false;

        this._initButtons();
        this.facesElement.hidden = false;
    }

    _initButtons() {
        for (const button of this.tagButtons) {
            button.addEventListener('click', this.handleToggleTagging.bind(this));
            button.hidden = false;
        }
    }

    async _initFaces() {
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

        this.createUrl = data.__links.create;
        this.faces = [];

        for (const face of data.iters)
            this._createFace(face);
    }

    _initTagLists() {
        this.tagLists = [];

        for (const list of this.options.tagLists)
            this.tagLists.push(new TagList({
                element: list,
                faces: () => this.faces,
            }));
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
        if (intent === 'state' || intent === 'all') {
            for (const tagList of this.tagLists)
                tagList.render();
        }

        for (const face of this.faces)
            face.render(intent);
    }

    disableTagging() {
        this.isTagging = false;

        this.element.classList.remove('is-tagging');
        this.facesElement.classList.remove('is-active');

        for (const button of this.tagButtons)
            button.classList.remove('is-active');

        this.render('state');
    }

    enableTagging() {
        this.isTagging = true;

        this.element.classList.add('is-tagging');
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

    _createFace(data) {
        const face = faceFactory({
            parent: this.facesElement,
            template: this.faceTemplate,
            data: data,
            imgScale: this.getScale.bind(this),
            enabled: () => this.isTagging,
            onDelete: this.deleteFace.bind(this),
            onReplace: this.replaceFace.bind(this),
            setEnableTagging: this.enableTagging.bind(this),
        })
        this.faces.push(face);
        return face;
    }

    async createFace(x, y) {
        if (!this.createUrl)
            return;

        // Calculate size
        const scale = this.getScale();
        const size = Math.max(
            scale.w * PHOTO_FACE_DEFAULT_SIZE,
            scale.h * PHOTO_FACE_DEFAULT_SIZE,
            PHOTO_FACE_MIN_DEFAULT_SIZE
        );
        let pos = {
            x: x - size/2,
            y: y - size/2,
            w: size,
            h: size,
        }
        pos = calculateFacePosition(pos, scale);
        pos = calculateRelativeFacePosition(pos, scale);

        // Create temporary face
        const face = this._createFace({
            ...pos,
            __links: {},
        });

        try {
            // Submit data
            const result = await this.submitFace(pos);
            // Replace temporary face
            face.options.data = result;
            face.options.isActive = true;
            face.replace(faceFactory(face.options));
        } catch (e) {
            // TODO: Provide feedback
        }
    }

    deleteFace(face) {
        const idx = this.faces.indexOf(face);
        if (idx >= 0)
            this.faces.splice(idx,1);
        this.render('state');
    }

    replaceFace(oldFace, newFace) {
        this.deleteFace(oldFace);
        this.faces.push(newFace);
        this.render('state');
    }

    async submitFace(data) {
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

        const response = await fetch(this.createUrl, init);

        if (!response.ok) {
            throw new Error('Error during create');
        }

        const result = await response.json();
        return result.iter;
    }

    handleImageClick(event) {
        const scale = this.getScale();
        const is_inside = (
            event.offsetX > scale.x
            && event.offsetX < (scale.x + scale.w)
            && event.offsetY > scale.y
            && event.offsetY < (scale.y + scale.h)
        );

        if (!is_inside || !this.isTagging || !this.createUrl)
            return;

        event.preventDefault();
        event.stopPropagation();

        this.createFace(event.offsetX, event.offsetY);
    }
}


PhotoFaces.parseDocument(document);
document.addEventListener('partial-content-loaded', event => PhotoFaces.parseDocument(event.detail));

export default PhotoFaces;
