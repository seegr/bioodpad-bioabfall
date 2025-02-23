import clear from "alpinejs"

export function scrollPercent(el, seekBottom = false) {
  const rect = el.getBoundingClientRect()
  const scrollTop = window.pageYOffset || document.documentElement.scrollTop
  const top = rect.top + scrollTop - window.innerHeight
  const bottom = top + rect.height + window.innerHeight
  const scrollPos = window.pageYOffset || document.documentElement.scrollTop
  const scrollEl = bottom - scrollPos

  let percent

  if (seekBottom) {
    percent = Math.round(((2 * rect.height - scrollEl) / rect.height) * 100)
  } else {
    percent = Math.round(((scrollPos - top) / (bottom - top)) * 100)
  }

  return percent
}

export function applySnippets(snippets) {
  for (const [id, html] of Object.entries(snippets)) {
    const element = document.getElementById(id)
    if (element) {
      if (element.dataset.ajaxAppend !== undefined) {
        element.innerHTML = element.innerHTML + html
      } else {
        element.innerHTML = html
      }
    }
  }
}

export function scrollToTarget(selector) {
  const target = document.querySelector(selector)
  if (!target) return

  const rect = target.getBoundingClientRect()

  const navH = document.getElementById("main-nav").offsetHeight
  const pos = rect.top + window.scrollY - navH * 2

  window.scrollTo(0, pos)
}

export function isInputFilled(inputEl) {
  console.log(inputEl.value)
  return inputEl.value !== ""
}

export function showInputs() {
  if (!document.body.classList.contains("page-order")) return

  let usedOil = document.getElementById("used_oil")
  let clearOil = document.getElementById("clear_oil")
  let amountType = document.getElementById("amount-type")
  const array = [clearOil, usedOil]

  array.forEach(function (element) {
    element.addEventListener("click", () => {
      amountType.classList.remove("hidden")
    })
  })
}
