import bulmaCarousel from 'bulma-carousel/dist/js/bulma-carousel.min.js';
import bulmaCollapsible from '@creativebulma/bulma-collapsible/dist/js/bulma-collapsible.min.js'

// Initialize all elements with carousel class.
const carousels = bulmaCarousel.attach('.carousel', {
    slidesToScroll: 1,
    slidesToShow: 1,
    autoplay: true,
    loop: true
});

for(var i = 0; i < carousels.length; i++) {
	// Add listener to  event
	carousels[i].on('before:show', state => {
    for (var i = 0; i < state.length; ++i)
        document.getElementById('event-' + i).classList.remove('is-active')
    var event = 'event-' + ((state.index + 1) % state.length)
    document.getElementById(event).classList.add('is-active')
    console.log(state)
	});
}


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

// Get the announcements that are too long and make them collapsible
const half = document.getElementsByClassName("is-half-height")

for (let element of half) {
  var h = element.getElementsByClassName("card-content")
  if (h[1] && h[1].clientHeight > 350){
      element.getElementsByClassName("controls")[0].classList.remove("is-not-active-read-more")
      element.getElementsByClassName("controls")[0].classList.add("is-active-read-more")
      h[1].classList.add("is-half-height")
      h[1].classList.add("is-long-text")
      h[1].classList.add("collapse-content")
  }
}