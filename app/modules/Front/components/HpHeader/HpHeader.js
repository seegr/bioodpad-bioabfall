import Swiper from "swiper"
import { Navigation } from 'swiper/modules';
import 'swiper/css';

export default function initHeaderSwiper() {
  const container = document.querySelector(".header-slider")

  console.log(container)
  if (!container) {
    return
  }

  new Swiper(container.querySelector(".swiper"), {
    modules: [Navigation],
    grabCursor: true,
    speed: 400,
    slidesPerView: 4,
    loop: true,
    navigation: {
      nextEl: container.querySelector(".button-next"),
      prevEl: container.querySelector(".button-prev"),
    },
  })
}