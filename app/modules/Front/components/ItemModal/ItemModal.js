export default function initItemModal() {
  const modal = document.querySelector('.item-modal-component')

  if (!modal) return

  const invisibleClass = ['invisible', 'opacity-0', 'scale-90']
  const visibleClass = ['visible', 'opacity-100', 'scale-1']

  const fetchUrl = modal.dataset.fetchUrl

  modal.addEventListener('click', e => {
    if (e.target.classList.contains('item-modal-component')) {
      hideModal()
    }
  })

  document.querySelectorAll('[data-item-modal-id]').forEach(btn => {
    btn.classList.add('cursor-pointer')

    btn.addEventListener('click', () => {
      const type = btn.dataset.itemModalType
      const id = btn.dataset.itemModalId

      fetch(`${fetchUrl}&type=${type}&itemId=${id}`, { method: "GET" })
        .then(res => {
          if (!res.ok) {
            throw new Error('Network response was not ok');
          }
          return res.json();
        })
        .then(data => {
          modal.querySelector('.item-title').textContent = data.title
          modal.querySelector('.item-text').innerHTML = data.text
          const imageWrap = modal.querySelector('.item-image')
          const galleryWrap = modal.querySelector('.item-gallery')
          const filesWrap = modal.querySelector('.item-files')

          imageWrap.innerHTML = ''
          galleryWrap.innerHTML = ''
          filesWrap.innerHTML = ''

          const articleImgClass = ["aspect-[3/1]"]
          const orgImgClass = ["block", 'mx-auto', 'py-24px', 'max-h-[200px]', 'pt-48px']
          imageWrap.classList.remove(...articleImgClass, ...orgImgClass)

          if (data.image) {
            const baseUrl = modal.dataset.baseUrl
            const imgEl = document.createElement('img')

            if (type === 'article') {
              imageWrap.classList.add(...articleImgClass)
              if (data.image_fit) {
                imgEl.classList.add("img-cover")
              }
            }

            if (type === 'org') {
              imgEl.classList.add(...orgImgClass)
            }

            imgEl.setAttribute('src', `${baseUrl}/${data.image}`)
            imageWrap.appendChild(imgEl)
            imageWrap.classList.remove('hidden')
          } else {
            imageWrap.classList.add('hidden')
          }

          if (data.gallery) {
            Object.values(data.gallery).forEach((src)=> {
              const imageEl = document.createElement('img')

              imageEl.srcset = src

              galleryWrap.appendChild(imageEl)
            })

            galleryWrap.classList.remove('hidden')
          } else {
            galleryWrap.classList.add('hidden')
          }

          if (data.files) {
            Object.values(data.files).forEach((data)=> {
              const fileEl = document.createElement('a')
              fileEl.classList.add('block', 'text-green-dark', 'hover:text-black')

              fileEl.href = data.src
              fileEl.target = '_blank'
              fileEl.innerText = data.name

              filesWrap.appendChild(fileEl)
            })

            filesWrap.classList.remove('hidden')
          } else {
            filesWrap.classList.add('hidden')
          }

          const itemLink = modal.querySelector('.item-link')

          if (data.link) {
            const linkBtn = modal.querySelector('.button-component')
            linkBtn.setAttribute('href', data.link)
            itemLink.style.display = 'block'
          } else {
            itemLink.style.display = 'none'
          }

          showModal()

          const btnClose = modal.querySelector('.btn-close')
          btnClose.addEventListener('click', () => {
            hideModal()
          })
        })
        .catch(error => {
          console.error('There has been a problem with your fetch operation:', error);
        });
    })
  })

  function showModal() {
    setTimeout(() => {
      modal.classList.remove(...invisibleClass)
      modal.classList.add(...visibleClass)
    }, 1)
  }

  function hideModal() {
    modal.classList.remove(...visibleClass)
    modal.classList.add(...invisibleClass)
  }

  // wraps.forEach(wrap => {
  //   const btn = wrap.querySelector('.btn')
  //
  //   btn.addEventListener('click', () => {
  //   })
  //
  // })
}