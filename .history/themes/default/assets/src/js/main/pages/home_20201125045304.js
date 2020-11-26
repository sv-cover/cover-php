import bulmaCarousel from 'bulma-carousel/dist/js/bulma-carousel.min.js';
import bulmaCollapsible from '@creativebulma/bulma-collapsible/dist/js/bulma-collapsible.min.js'

// Initialize all elements with carousel class.
const carousel = bulmaCarousel.attach('#carousel-demo', {
    slidesToScroll: 1,
    slidesToShow: 1
});



// document.getElementById("spani").addEventListener("click", openNav);
// document.getElementById("mySidebar").addEventListener("click", closeNav);

// document.getElementById("testNews").addEventListener("click", openCard);
// document.getElementById("delete").addEventListener("click",closeCard);

function openCard() {
  document.getElementById("testCard").classList.add ("is-active");
}

function closeCard() {
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

// document.getElementById("spani").addEventListener("click", animation);

// function animation() {
//     document.getElementById("spani").classList.toggle("left");
//     document.getElementById("spani").classList.toggle("right");
// }


// $('.arrow--l-r').on('click', function() {
//     $(this).toggleClass('left right');
// });


const bulmaCollapsibleElement = document.getElementById('collapsible-card');
if (bulmaCollapsibleElement)
{
  new bulmaCollapsible(bulmaCollapsibleElement);

  bulmaCollapsibleElement.bulmaCollapsible('collapse');
}


const half = document.getElementsByClassName("is-half-height")

for (let element of half) {
  var h = element.getElementsByClassName("card-content")
  if (h[1].clientHeight > 350){
      element.getElementsByClassName("controls")[0].classList.remove("is-not-active-read-more")
  }
}