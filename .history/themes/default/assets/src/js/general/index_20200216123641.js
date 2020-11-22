import './darkmode';
import bulmaCarousel from 'bulma-carousel/dist/js/bulma-carousel.min.js';
import './general';
// Initialize all elements with carousel class.
bulmaCarousel.attach('#carousel-demo', {
    slidesToScroll: 1,
    slidesToShow: 4
});