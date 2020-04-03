import bulmaCarousel from 'bulma-carousel/dist/js/bulma-carousel.min.js';
import modalCard from 'bulma-modal-fx/dist/js/modal-fx';


// Initialize all elements with carousel class.
bulmaCarousel.attach('#carousel-demo', {
    slidesToScroll: 1,
    slidesToShow: 1
});

bulmaCarousel.attach('#carousel-demo2', {
  slidesToScroll: 1,
  slidesToShow: 3
});


document.getElementById("main").addEventListener("click", openNav);
document.getElementById("mySidebar").addEventListener("click", closeNav);

// document.getElementById("testNews").addEventListener("click", openCard);
// document.getElementById("delete").addEventListener("click",closeCard);

function openCard(){
  document.getElementById("testCard").classList.add ("is-active");
}

function closeCard(){
  document.getElementById("testCard").classList.remove ("is-active");
}

function openNav() {
    document.getElementById("mySidebar").style.width = "500px";
    document.getElementById("main").style.marginLeft = "500px";
  }
  
  function closeNav() {
    document.getElementById("mySidebar").style.width = "0";
    document.getElementById("main").style.marginLeft= "0";
  }


