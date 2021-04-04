function isIOS() {
    let userAgent = navigator.userAgent || navigator.vendor || window.opera;
    // IE on windows phone has included iPhone at some point.
    return /iPad|iPhone|iPod/.test(userAgent) && !window.MSStream;
}

/**
 * copyTextToClipboard function courtecy of Dean Taylor on stackoverflow
 * https://stackoverflow.com/questions/400212/how-do-i-copy-to-the-clipboard-in-javascript 
 */
export function copyTextToClipboard(text) {
    let textArea = document.createElement("textarea");

    // Make sure textarea is invisible
    textArea.style.position = 'fixed';
    textArea.style.top = 0;
    textArea.style.left = 0;
    textArea.style.width = '2em';
    textArea.style.height = '2em';
    textArea.style.padding = 0;
    textArea.style.border = 'none';
    textArea.style.outline = 'none';
    textArea.style.boxShadow = 'none';
    textArea.style.background = 'transparent';
    textArea.style.fontSize = '16px';
    textArea.value = text;
    document.body.appendChild(textArea);

    // Select and copy contents
    if (isIOS()) {
        let range = document.createRange();
        range.selectNodeContents(textArea);
        let selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(range);
        textArea.setSelectionRange(0, 999999);
    } else {
        textArea.select();
    }


    try {
        document.execCommand('copy');
    } catch (err) {
        return false;
    }

    // Clean up
    document.body.removeChild(textArea);

    return true;
}

export class DragHandler {
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
