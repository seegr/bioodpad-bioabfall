import tippy from "tippy.js";

export default function initTooltip() {
  tippy('[data-tooltip]', {
    content: (reference) => reference.getAttribute('data-tooltip'),
    placement: 'top',
  });
}