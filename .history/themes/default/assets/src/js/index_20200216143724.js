import Bulma from 'cover-style-system/src/js';
import ulmaCarousel from 'bulma-carousel/dist/js/bulma-carousel.min.js';
import './general';
// Initialize all elements with carousel class.
bulmaCarousel.attach('#carousel-demo', {
    slidesToScroll: 1,
    slidesToShow: 4
});