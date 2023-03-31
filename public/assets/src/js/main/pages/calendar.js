import {Bulma} from 'cover-style-system/src/js';

class CalendarListViewButton {
    /**
     * Get the root class this plugin is responsible for.
     * This will tell the core to match this plugin to an element with a .modal class.
     * @returns {string} The class this plugin is responsible for.
     */
    static getRootClass() {
        return 'calendar-list-view-button';
    }


    /**
     * Handle parsing the DOMs data attribute API.
     * @param {HTMLElement} element The root element for this instance
     * @return {undefined}
     */
    static parse(element) {
        new CalendarListViewButton({
            element: element,
        });
    }

    constructor(options) {
        this.element = options.element;
        this.calendarElement = this.element.closest('.calendar');
        this.targetMode = this.element.dataset.targetMode;

        if (this.getCurrentMode() != this.targetMode) {
            this.element.hidden = false;
        }

        this.setupEvents();
    }

    getCurrentMode() {
        return this.calendarElement.classList.contains('is-list') ? 'list' : 'grid';
    }

    setupEvents() {
        this.element.addEventListener('click', this.handleClick.bind(this));
    }

    handleClick() {
        const currentMode = this.getCurrentMode();

        if (currentMode == this.targetMode)
            return;

        document.cookie = 'cover_calendar_mode=' + this.targetMode + '; expires=Fri, 31 Dec 9999 23:59:59 GMT; SameSite=Lax';
        
        this.calendarElement.querySelectorAll(`.calendar-list-view-button[data-target-mode=${currentMode}]`).forEach(
            el => el.hidden = false
        );

        this.calendarElement.querySelectorAll(`.calendar-list-view-button[data-target-mode=${this.targetMode}]`).forEach(
            el => el.hidden = true
        );

        if (this.targetMode === 'list')
            this.calendarElement.classList.add('is-list');
        else
            this.calendarElement.classList.remove('is-list');
    }
}


Bulma.registerPlugin('calendar-list-view-button', CalendarListViewButton);

export default CalendarListViewButton;
