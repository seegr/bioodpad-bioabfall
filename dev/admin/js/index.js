import "./imports/polyfills.js"
import tippy from 'tippy.js';
import naja from 'naja';
import 'tippy.js/dist/tippy.css';
import UIkit from "uikit/dist/js/uikit.js"
import UIkitIcons from "uikit/dist/js/uikit-icons.js"
import { initAll } from "./imports/initilaizers.js"
import { requestSnippets } from "./imports/helpers.js"

import "../css/index.css"
import initTooltip from "../../front/js/tooltip";

window.requestSnippets = requestSnippets
UIkit.use(UIkitIcons)

document.addEventListener(`DOMContentLoaded`, () => {
  naja.initialize();
  insertNetteFormsScript()
  initTooltip()
  initAll().then(() => {
    // eslint-disable-next-line no-console
    console.log("All modules successfully loaded")
  })
})

function insertNetteFormsScript() {
  // npm version of nette forms has no named export to use :(
  // fallback to CDN
  const script = document.createElement("script")
  script.src = "https://nette.github.io/resources/js/3/netteForms.min.js"
  document.head.appendChild(script)
}
