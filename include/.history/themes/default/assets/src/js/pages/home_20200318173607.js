
import bulmaCarousel from 'bulma-carousel/dist/js/bulma-carousel.min.js';

(index):154 Uncaught ReferenceError: openNav is not defined
    at HTMLButtonElement.onclick 

function openNav() {
    document.getElementById("mySidebar").style.width = "500px";
    document.getElementById("main").style.marginLeft = "0";
  }
  
  function closeNav() {
    document.getElementById("mySidebar").style.width = "0";
    document.getElementById("main").style.marginLeft= "0";
  }