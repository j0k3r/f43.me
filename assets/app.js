const timeago = require('timeago.js')

import './styles/app.scss'

// handle timeago date on the public page
if (document.querySelectorAll('.time-ago').length > 0) {
    timeago.render(document.querySelectorAll('.time-ago'), { minInterval: 60 })
}

// handle show / hide of the textarea when testing config file
const siteconfig = document.getElementById('siteconfig')
if (siteconfig !== null) {
    const siteconfigTextarea = siteconfig.childNodes[3]
    if (siteconfigTextarea.value === '') {
        siteconfigTextarea.style.display = 'none'
    }

    document.getElementById('try-siteconfig').onclick = function (event) {
        event.preventDefault()

        if (siteconfigTextarea.value != '') {
            return false
        }

        if (siteconfigTextarea.style.display === 'none') {
            siteconfigTextarea.style.display = 'block'
        } else {
            siteconfigTextarea.style.display = 'none'
        }

        return false
    }
}

// display a confirm message when trying to delete something
const deleteForm = document.querySelectorAll('.delete_form')
if (deleteForm.length === 1) {
    deleteForm[0].onsubmit = function () {
        return window.confirm('Are you sure you want to do this action ?')
    }
}

// handle preview of a feed
const parsingResult = document.getElementById('preview-parsing-result')
const previewParsing = document.getElementById('preview-parsing')
if (previewParsing !== null) {
    const url = previewParsing.dataset.url
    const loader = '<a href="#" aria-busy="true">Loading content, please wait…</a>'

    // internal parser
    previewParsing.childNodes[1].onclick = async function (event) {
        parsingResult.innerHTML = loader
        event.preventDefault()

        const response = await fetch(url + '?parser=internal', { method: 'GET' })

        if (!response.ok) {
            parsingResult.innerHTML = '<p class="error">Error while retrieving content…</a>'

            return
        }

        const body = await response.text()

        parsingResult.innerHTML = body
    }
    // external parser
    previewParsing.childNodes[3].onclick = async function (event) {
        parsingResult.innerHTML = loader
        event.preventDefault()

        const response = await fetch(url + '?parser=external', { method: 'GET' })

        if (!response.ok) {
            parsingResult.innerHTML = '<p class="error">Error while retrieving content…</a>'

            return
        }

        const body = await response.text()

        parsingResult.innerHTML = body
    }
}
