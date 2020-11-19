import Bulma from 'cover-style-system/src/js';
import './general';
import './pages';
import bulmaCarousel from 'bulma-carousel/dist/js/bulma-carousel.min.js';

// Initialize all elements with carousel class.
bulmaCarousel.attach('#carousel-demo', {
    
    slidesToScroll: 3,

});