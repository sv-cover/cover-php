"use strict";

var _bulmaCarouselMin = _interopRequireDefault(require("bulma-carousel/dist/js/bulma-carousel.min.js"));

var _bulmaCollapsibleMin = _interopRequireDefault(require("@creativebulma/bulma-collapsible/dist/js/bulma-collapsible.min.js"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

// Initialize all elements with carousel class.
_bulmaCarouselMin["default"].attach('#carousel-demo', {
  slidesToScroll: 1,
  slidesToShow: 1
});

_bulmaCarouselMin["default"]; // document.getElementById("spani").addEventListener("click", openNav);
// document.getElementById("mySidebar").addEventListener("click", closeNav);
// document.getElementById("testNews").addEventListener("click", openCard);
// document.getElementById("delete").addEventListener("click",closeCard);

function openCard() {
  document.getElementById("testCard").classList.add("is-active");
}

function closeCard() {
  document.getElementById("testCard").classList.remove("is-active");
}

function openNav() {
  console.log(document.getElementById("mySidebar").style.width);

  if (document.getElementById("mySidebar").style.width == "500px") {
    document.getElementById("mySidebar").style.width = "0px";
    document.getElementById("main").style.marginLeft = "0px";
  } else {
    document.getElementById("mySidebar").style.width = "500px";
    document.getElementById("main").style.marginLeft = "500px";
  }
} // function closeNav() {
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


var bulmaCollapsibleElement = document.getElementById('collapsible-card');

if (bulmaCollapsibleElement) {
  new _bulmaCollapsibleMin["default"](bulmaCollapsibleElement);
  bulmaCollapsibleElement.bulmaCollapsible('collapse');
}