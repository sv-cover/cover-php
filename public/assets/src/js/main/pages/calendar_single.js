import {Bulma} from 'cover-style-system/src/js';

/*
 * Bulma plugin to give the Sticky Header in the calendar a scroll based animation.
 */
class CalendarSingleStickyHeader {
    static getRootClass() {
        return 'event-sidebar-sticky-header';
    }

    static parse(element) {
        new CalendarSingleStickyHeader({
            element: element,
        });
    }

    constructor(options) {
        this.element = options.element;
        
        // Also need header and (sticky part of) sidebar
        this.header = document.querySelector('.event-header');
        this.sidebar = options.element.closest('.is-sticky');

        // Init helpers
        this.prevRatio = 1;
        this.headerVisible = true;

        // Start observing
        this.update();
        this.monitorScroll();
    }

    monitorScroll() {
        let ticking = false;

        // Update visible ratio on scroll
        // Prevent updating too much using ticking.
        window.addEventListener('scroll', (event) => {
          if (!ticking) {
            window.requestAnimationFrame(() => {
                // Only update if there's a reasonable chance the update would be useful
                if (this.headerVisible && this.sidebar.classList.contains('is-stuck')) {
                    this.update();
                }
                ticking = false;
            });

            ticking = true;
          }
        });

        // Observe header to prevent unnecessary updates
        const observer = new IntersectionObserver(
            (entries, observer) => {
                this.headerVisible = entries[0].isIntersecting;
                this.update(); // Update to deal with navitational jumps
            },
            {threshold: [0]}
        );
        observer.observe(this.header);
    }

    update() {
        // Get position information
        const elementRect = this.element.getBoundingClientRect();
        const headerRect = this.header.getBoundingClientRect();

        // Calculate visible ratio
        let ratio = (elementRect.bottom - headerRect.bottom) / elementRect.height;
        ratio = Math.min(Math.max(ratio, 0), 1);

        // only update if necessary
        if (ratio != this.prevRatio)
            this.element.style.setProperty('--ratio-visible', ratio);
        this.prevRatio = ratio;
    }
}


Bulma.registerPlugin('calendar-single-sticky-header', CalendarSingleStickyHeader);

export default CalendarSingleStickyHeader;
