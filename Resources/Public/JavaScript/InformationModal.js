'use strict'

define(['TYPO3/CMS/Backend/Modal'], function (Modal) {
    'use strict'

    class InformationModal {

        informationModalMethod = this.handleClickInformationModal.bind(this)

        constructor() {
            document.querySelectorAll('.js-in2publish-information-modal').forEach(element => this.registerClickHandler(element))
        }

        /**
         * Register a click handler for information modal elements.
         * Use for elements that should display information without any action buttons.
         *
         * If you want to register an element automatically on page load, give the element the class js-in2publish-information-modal.
         * This method can be used to register the handler for elements loaded via fetch.
         *
         * @param {HTMLElement} node
         */
        registerClickHandler(node) {
            if (node.dataset.easyModalTitle) {
                node.addEventListener('click', this.informationModalMethod, {capture: true})
            }
        }

        /**
         * @param {Event} event
         */
        handleClickInformationModal(event) {
            event.preventDefault()
            const target = event.currentTarget
            const severity = parseInt(target.dataset.easyModalSeverity ?? -1) // Default to info severity

            Modal.confirm(
                target.dataset.easyModalTitle,
                target.dataset.easyModalContent ?? '',
                severity,
                this.getInformationModalButtons(),
            )
        }

        /**
         * Returns only a close button for the information modal
         */
        getInformationModalButtons() {
            return [
                {
                    text: TYPO3.lang['button.close'] || 'Close',
                    btnClass: 'btn-default',
                    name: 'close',
                    active: true,
                    trigger: this.closeModal.bind(this)
                }
            ]
        }

        closeModal() {
            if (typeof Modal.currentModal.hideModal === "function") {
                Modal.currentModal.hideModal()
            } else {
                Modal.currentModal.trigger('modal-dismiss')
            }
        }
    }

    return new InformationModal()
})