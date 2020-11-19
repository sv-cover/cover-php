import bulmaCarousel from 'bulma-carousel/dist/js/bulma-carousel.min.js';
//import bulmaCollapsible from '@creativebulma/bulma-collapsible/dist/js/bulma-collapsible.min.js';
import bulmaCollapsible from '@creativebulma/bulma-collapsible/src/js/index.js'

// Initialize all elements with carousel class.
bulmaCarousel.attach('#carousel-demo', {
    slidesToScroll: 1,
    slidesToShow: 1
});

bulmaCarousel.attach('#carousel-demo2', {
  slidesToScroll: 1,
  slidesToShow: 3
});

document.getElementById("spani").addEventListener("click", openNav);
// document.getElementById("mySidebar").addEventListener("click", closeNav);

// document.getElementById("testNews").addEventListener("click", openCard);
// document.getElementById("delete").addEventListener("click",closeCard);

function openCard(){
  document.getElementById("testCard").classList.add ("is-active");
}

function closeCard(){
  document.getElementById("testCard").classList.remove ("is-active");
}

function openNav() {
    console.log(document.getElementById("mySidebar").style.width);
    if(document.getElementById("mySidebar").style.width == "500px") {
      document.getElementById("mySidebar").style.width = "0px";
      document.getElementById("main").style.marginLeft = "0px";
    } else {
      document.getElementById("mySidebar").style.width = "500px";
      document.getElementById("main").style.marginLeft= "500px";  
    }

    
  }
  
  // function closeNav() {
  //   document.getElementById("mySidebar").style.width = "0";
  //   document.getElementById("main").style.marginLeft= "0";
  // }

document.getElementById("spani").addEventListener("click", animation);

function animation() {
    document.getElementById("spani").classList.toggle("left");
    document.getElementById("spani").classList.toggle("right");
}

// Find DOM node from ID
const CollapsibleElement = document.getElementById('collapsible-card');
if (CollapsibleElement) {
  // Instanciate Collapsible component on the node
  new Collapsible(CollapsibleElement);

  // Call method directly on Collapsible instance registered on the node
  CollapsibleElement.bulmaCollapsible().on('after:expand', (e) => {
    console.log('After expand');
  });
}

// $('.arrow--l-r').on('click', function() {
//     $(this).toggleClass('left right');
// });
