import bulmaCarousel from 'bulma-carousel/dist/js/bulma-carousel.min.js';
import { Collapse } from 'cover-style-system/src/js';

function containedScrollIntoView(element) {
  // element.scrollIntoView, but only inside scrollparent
  const scrollParent = element.closest('.scroll-container');
  const rect = element.getBoundingClientRect();
  const newPos = Math.max((scrollParent.clientHeight - rect.height) / 2, 0) - element.offsetTop;

  scrollParent.scrollTo({
    top: newPos,
    behavior: 'smooth'
  });
}

function init_homepage(element) {
  // Initialize all elements with carousel class.
  var carousels = bulmaCarousel.attach('.carousel', {
      slidesToScroll: 1,
      slidesToShow: 1,
      autoplay: true,
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

  // Activate the event in the calendar that corresponds with the one in the carousel

  for(let i = 0; i < carousels.length; i++) {
    //  The carousel event listener only activates after the first slide
    // so we have to set up the first event manually
    document.getElementById('event-0').classList.add('is-active')
    
  	carousels[i].on('before:show', state => {
      // deactivate the rest of the events
      for (let i = 0; i < state.length; ++i)
        document.getElementById('event-' + i).classList.remove('is-active')

      // the first slide is actually the second one so we need some math to get the order right
      let event = 'event-' + ((state.index + 1) % state.length)
      let element = document.getElementById(event);
      element.classList.add('is-active');
      containedScrollIntoView(element.closest('.event'));
  	});
  }

  // Resize carousels with window
  // TODO: This is still really buggy...
  window.addEventListener('resize', () => {
    for (let carousel of carousels) {
      carousel.reset();
    }
  });

  // Get the announcements that are too long and make them collapsible
  const half = document.getElementsByClassName("is-half-height")

  for (let element of half) {
    let h = element.getElementsByClassName("card-content")
    if (h[1] && h[1].clientHeight > 400){
        element.getElementsByClassName("controls")[0].classList.remove("is-not-active-read-more")
        element.getElementsByClassName("controls")[0].classList.add("is-active-read-more")
        h[1].classList.add("is-half-height")
        h[1].classList.add("is-long-text")
        h[1].classList.add("collapse-content");

        let collapse_element = element.closest('.card');
        collapse_element.classList.add('collapse');
        new Collapse({element: collapse_element});
    }
  }
}


const homepage = document.querySelector('.homepage');

if (homepage) {
  init_homepage(homepage);
}
