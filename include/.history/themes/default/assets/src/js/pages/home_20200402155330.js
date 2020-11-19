import bulmaCarousel from 'bulma-carousel/dist/js/bulma-carousel.min.js';

// Initialize all elements with carousel class.
bulmaCarousel.attach('#carousel-demo', {
    slidesToScroll: 1,
    slidesToShow: 2
});

document.getElementById("main").addEventListener("click", openNav);
document.getElementById("mySidebar").addEventListener("click", closeNav);

function openNav() {
    document.getElementById("s").style.width = "500px";
    document.getElementById("main").style.marginLeft = "500px";
  }
  
  function closeNav() {
    document.getElementById("mySidebar").style.width = "0";
    document.getElementById("main").style.marginLeft= "0";
  }

document.getElementById("spani").addEventListener("click", animation);

function animation() {
    document.getElementById("spani").classList.toggle("left");
    document.getElementById("spani").classList.toggle("right");
}


// $('.arrow--l-r').on('click', function() {
//     $(this).toggleClass('left right');
// });
