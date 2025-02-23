import tippy from "tippy.js";
import 'tippy.js/dist/tippy.css';

export default function initCalendar() {
  const wraps = document.querySelectorAll('.calendar-component')

  if (!wraps.length) return

  wraps.forEach(wrap => {
    const eventDays = wrap.querySelectorAll('a.has-event')

    eventDays.forEach(day => {
      const events = Array.from(day.querySelectorAll('.event'))
      console.log(events)

      if (!events.length) return

      const titles = events.map(event => `• ${event.textContent}`)

      tippy(day, {
        content: titles.join('<br>'),
        allowHTML: true
      })
    })
  })
}