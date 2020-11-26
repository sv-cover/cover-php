import bulmaCarousel from 'bulma-carousel/dist/js/bulma-carousel.min.js';
import bulmaCollapsible from '@creativebulma/bulma-collapsible/dist/js/bulma-collapsible.min.js'

// Initialize all elements with carousel class.
var carousels = bulmaCarousel.attach('.carousel', {
    slidesToScroll: 1,
    slidesToShow: 1,
    autoplay: false,
    loop: true,
    autoplaySpeed: 5000,
    infinite: true,
    breakpoints: [{
      changePoint: 480,
      slidesToShow: 1,
      slidesToScroll: 1
    },
    {
      changePoint: 640,
      slidesToShow: 1,
      slidesToScroll: 1
    },
    {
      changePoint: 768,
      slidesToShow: 1,
      slidesToScroll: 1
    }
  ],
});

function reInetializeCarrousel() {
  carousels = bulmaCarousel.attach('.carousel', {
    slidesToScroll: 1,
    slidesToShow: 1,
    autoplay: false,
    loop: true,
    autoplaySpeed: 5000,
    infinite: true,
    breakpoints: [{
      changePoint: 480,
      slidesToShow: 1,
      slidesToScroll: 1
    },
    {
      changePoint: 640,
      slidesToShow: 1,
      slidesToScroll: 1
    },
    {
      changePoint: 768,
      slidesToShow: 1,
      slidesToScroll: 1
    }
  ],
});
}

window.addEventListener('resize', reInetializeCarrousel);

// Activate the event in the calendar that corresponds with the one in the carousel

for(var i = 0; i < carousels.length; i++) {
  //  The carousel event listener only activates after the first slide
  // so we have to set up the first event manually
  document.getElementById('event-0').classList.add('is-active')
  
	carousels[i].on('before:show', state => {
    // deactivate the rest of the events
    for (var i = 0; i < state.length; ++i)
        document.getElementById('event-' + i).classList.remove('is-active')

    // the first slide is actually the second one so we need some math to get the order right
    var event = 'event-' + ((state.index + 1) % state.length)
    document.getElementById(event).classList.add('is-active')
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
  if (h[1] && h[1].clientHeight > 400){
      element.getElementsByClassName("controls")[0].classList.remove("is-not-active-read-more")
      element.getElementsByClassName("controls")[0].classList.add("is-active-read-more")
      h[1].classList.add("is-half-height")
      h[1].classList.add("is-long-text")
      h[1].classList.add("collapse-content")
  }
}