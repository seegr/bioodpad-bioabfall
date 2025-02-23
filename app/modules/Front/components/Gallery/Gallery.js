import Swiper from "swiper";
import {Navigation} from "swiper/modules";

export default function initGalleryComponent() {
  const container = document.querySelector('.gallery-component')

  if (!container) return

  new Swiper(container.querySelector(".swiper"), {
    modules: [Navigation],
    grabCursor: true,
    speed: 400,
    slidesPerView: 1,
    spaceBetween: 6,
    breakpoints: {
      480: {
        slidesPerView: 2
      },
      768: {
        slidesPerView: 3
      },
      1024: {
        slidesPerView: 5
      }
    },
    loop: true,
    navigation: {
      nextEl: container.querySelector(".button-next"),
      prevEl: container.querySelector(".button-prev"),
    },
  })
}