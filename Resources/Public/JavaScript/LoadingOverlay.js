'use strict'

define([], function () {
    'use strict'

    class LoadingOverlay {

        eventListenerMethod = this.handleClick.bind(this)
        /**
         * @var {NodeListOf<HTMLElement>}
         */
        overlays

        constructor() {
            this.registerOverlays()
            document.querySelectorAll('.js-in2publish-loading-overlay').forEach(element => this.registerClickHandler(element))
        }

        /**
         * @param {NodeListOf<HTMLElement>} nodes
         */
        registerOverlays(nodes = null) {
            this.overlays = nodes ?? document.querySelectorAll('.in2publish-loading-overlay')
        }

        /**
         * Register a click handler which will show the loading-overlay
         * when the element is clicked. Use only for elements that will
         * reload the page or disable the loading-overlay yourself.
         *
         * If you want to register an element automatically on page load,
         * give the element the class js-in2publish-loading-overlay. This
         * method can be used to register the handler for elements loaded
         * via fetch.
         *
         * @param {HTMLElement} node
         */
        registerClickHandler(node) {
            node.addEventListener('click', this.eventListenerMethod)
        }

        handleClick() {
            this.showOverlay()
        }

        showOverlay() {
            this.overlays.forEach(element => {
                element.classList.remove('in2publish-loading-overlay--hidden')
                element.classList.add('in2publish-loading-overlay--active')
            })
        }

        hideOverlay() {
            this.overlays.forEach(element => {
                element.classList.add('in2publish-loading-overlay--hidden')
                element.classList.remove('in2publish-loading-overlay--active')
            })
        }
    }

    return new LoadingOverlay()
})
