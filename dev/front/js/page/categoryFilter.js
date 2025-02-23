export default function initCategoryFilter() {
  const form = document.getElementById('frm-filterForm')

  if (!form) return

  const alpineComponent = Alpine.$data(form);
  const submitDelay = 1000
  let submitInterval

  form.querySelectorAll('input').forEach(input => {
    input.addEventListener('change', () => {
      console.log('change')

      alpineComponent.request()
    })
    input.addEventListener('keyup', () => {
      console.log('keyUp')

      clearInterval(submitInterval)

      submitInterval = setInterval(() => {
        console.log('submit')
        clearInterval(submitInterval)

        alpineComponent.request()
      }, submitDelay)
    })
  })
}