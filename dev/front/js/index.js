import Alpine from "alpinejs"
import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';


import { applySnippets } from "./utils"

import "../css/index.css"
import "../../../app/modules/Front/components/Calendar/Calendar.scss";
import "../../../app/modules/Front/components/HpHeader/HpHeader.scss";

import initHeaderSwiper from "../../../app/modules/Front/components/HpHeader/HpHeader";
import initGalleryComponent from "../../../app/modules/Front/components/Gallery/Gallery";
import initCalendar from "../../../app/modules/Front/components/Calendar/Calendar";
import initArticleModal from "../../../app/modules/Front/components/ItemModal/ItemModal";
import initOrgsMap from "../../../app/modules/Front/components/OrgsMap/OrgsMap";
import initCategoryFilter from "./page/categoryFilter";
import initItemModal from "../../../app/modules/Front/components/ItemModal/ItemModal";
import initTooltip from "./tooltip";

Alpine.data("ajax", (initState = {}) => ({
  loading: false,
  interactive: false,
  state: initState,
  getBody() {
    let formData = null
    if (this.$el instanceof HTMLFormElement) formData = new FormData(this.$el)
    if (this.$root instanceof HTMLFormElement)
      formData = new FormData(this.$root)
    // needed for isSubmittedBy nette method
    if (formData && this.$el instanceof HTMLButtonElement)
      formData.append(this.$el.name, this.$el.value)
    return formData
  },
  getUrl() {
    if (this.$el instanceof HTMLFormElement) return this.$el.action
    if (this.$root instanceof HTMLFormElement) return this.$root.action
    if (this.$el instanceof HTMLAnchorElement) return this.$el.href
    return initState.url
  },
  request(body = null) {
    this.loading = true
    return fetch(this.getUrl(), {
      method: body || this.getBody() ? "POST" : "GET",
      headers: { "X-Requested-With": "XMLHttpRequest" },
      body:
        body instanceof InputEvent ||
        body instanceof PointerEvent ||
        body instanceof MouseEvent ||
        body instanceof SubmitEvent
          ? this.getBody()
          : body || this.getBody(),
    })
      .then((response) => {
        if (response.redirected) location.href = response.url
        return response.json()
      })
      .then(({ snippets, redirect, url }) => {
        if (redirect) {
          location.href = redirect
        }
        if (snippets) {
          if (url) {
            console.log(url)
            window.history.pushState(snippets, "", url)
          }
          applySnippets(snippets)
          this.interactive = true
          this.loading = false
        }

        initTooltip()
      })
      .catch((e) => {
        console.warn(e)
        this.loading = false
      })
  },
}))

window.Alpine = Alpine
Alpine.start()

window.addEventListener(`DOMContentLoaded`, () => {
  console.log("DOM loaded")

  initHeaderSwiper()
  initGalleryComponent()
  initCalendar()
  initItemModal()
  initOrgsMap()
  initCategoryFilter()
  initTooltip()
})
