import Bulma from 'cover-style-system/src/js';
import bulmaCarousel from 'bulma-carousel/dist/js/bulma-carousel.min.js';
import './general';
// Initialize all elements with carousel class.
bulmaCarousel.attach('#carousel', {
    slidesToScroll: 1,
    slidesToShow: 4
});