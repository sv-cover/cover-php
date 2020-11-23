import bulmaCarousel from 'bulma-carousel/dist/js/bulma-carousel.min.js';

// Initialize all elements with carousel class.
bulmaCarousel.attach('#carousel-demo', {
    slidesToScroll: 1,
    slidesToShow: 2
});

document.getElementById("main").addEventListener("click", openNav);
document.getElementById("mySidebar").addEventListener("click", closeNav);

function openNav() {
    document.getElementById("mySidebar").style.width = "500px";
    document.getElementById("animation1").style.marginLeft = "500px";
  }
  
  function closeNav() {
    document.getElementById("mySidebar").style.width = "0";
    document.getElementById("animation1").style.marginLeft= "0";
  }


