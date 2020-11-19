import bulmaCarousel from 'bulma-carousel/dist/js/bulma-carousel.min.js';

// Initialize all elements with carousel class.
bulmaCarousel.attach('#carousel-demo', {
    slidesToScroll: 1,
    slidesToShow: 2
});

function openNav() {
    document.getElementById("mySidebar").style.width = "500px";
    document.getElementById("main").style.marginLeft = "0";
  }function closeNav() {
      
    document.getElementById("mySidebar").style.width = "0";
    document.getElementById("main").style.marginLeft= "0";
  }


