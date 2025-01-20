'use strict';

/**
 * Module: @in2code/in2publish_core/confirmation-modal.js
 */
import Modal from '@typo3/backend/modal.js';
import LoadingOverlay from '@typo3/backend/modal.js';

class ConfirmationModal {

    easyModalMethod = this.handleClickEasyModal.bind(this)
    advancedModalMethod = this.handleClickAdvancedModal.bind(this)

    constructor() {
        document.querySelectorAll('.js-in2publish-confirmation-modal').forEach(element => this.registerClickHandler(element))
    }

    /**
     * Register a click handler which will show the loading-overlay when the element is clicked.
     * Use only for elements that will reload the page or disable the loading-overlay yourself.
     *
     * If you want to register an element automatically on page load, give the element the class js-loading-overlay.
     * This method can be used to register the handler for elements loaded via fetch.
     *
     * @param {HTMLElement} node
     */
    registerClickHandler(node) {
        if (node.dataset.easyModalTitle) {
            node.addEventListener('click', this.easyModalMethod, {capture: true})
        } else if (node.dataset.modalConfiguration) {
            node.addEventListener('click', this.advancedModalMethod, {capture: true})
        }
    }

    /**
     * @param {Event} event
     */
    handleClickEasyModal(event) {
        event.preventDefault()
        const target = event.currentTarget
        const severity = parseInt(target.dataset.easyModalSeverity ?? 0)

        Modal.confirm(
            target.dataset.easyModalTitle,
            target.dataset.easyModalContent ?? '',
            severity,
            this.defaultEasyModalButtons(event, target, severity),
        )
    }

    defaultEasyModalButtons(event, target, severity) {
        let actionButtonClass = ''
        switch (severity) {
            /*
             * TYPO3.Severity.error = 2
             * TYPO3.Severity.warning = 1
             * TYPO3.Severity.ok = 0
             * TYPO3.Severity.info = -1
             * TYPO3.Severity.notice = -2
             */
            case -2:
            case -1:
                actionButtonClass = 'btn-info'
                break
            case 0:
                actionButtonClass = 'btn-success'
                break
            case 1:
                actionButtonClass = 'btn-warning'
                break
            case 2:
                actionButtonClass = 'btn-danger'
                break
        }
        return [
            {
                text: TYPO3.lang['tx_in2publishcore.action.abort'],
                btnClass: 'btn-default',
                name: 'abort',
                active: true,
                trigger: this.abortClick.bind(this)
            },
            {
                text: TYPO3.lang['tx_in2publishcore.actions.publish'],
                btnClass: actionButtonClass,
                name: 'publish',
                trigger: () => this.confirmClick(event, target, this.easyModalMethod)
            }
        ]
    }

    /**
     * @param {Event} event
     */
    handleClickAdvancedModal(event) {
        event.preventDefault()

        const target = event.currentTarget
        const modalConfiguration = JSON.parse(target.dataset.modalConfiguration)

        let finalModalConfiguration = modalConfiguration.settings
        finalModalConfiguration.buttons = []
        if (modalConfiguration.buttons.abort) {
            let abortButton = modalConfiguration.buttons.abort
            abortButton.trigger = this.abortClick.bind(this)
            finalModalConfiguration.buttons.push(abortButton)
        }
        if (modalConfiguration.buttons.confirm) {
            let confirmButton = modalConfiguration.buttons.confirm
            confirmButton.trigger = () => this.confirmClick(event, target, this.advancedModalMethod)
            finalModalConfiguration.buttons.push(confirmButton)
        }

        Modal.advanced(finalModalConfiguration)
    }

    abortClick() {
        this.closeModal()
        LoadingOverlay.hideOverlay()
    }

    confirmClick(event, target, previousHandler) {
        target.removeEventListener('click', previousHandler, {capture: true})
        LoadingOverlay.showOverlay()
        this.closeModal()
        if (target instanceof HTMLAnchorElement) {
            target.dispatchEvent(event)
            window.location = target.href
        } else if (target instanceof HTMLButtonElement) {
            if (target.getAttribute("type") === "submit") {
                if (target.hasAttribute("form")) {
                    const targetForm = target.getAttribute("form")
                    document.getElementById(targetForm).requestSubmit()
                } else {
                    target.dispatchEvent(event)
                }
            }
        }
    }

    closeModal() {
        if (typeof Modal.currentModal.hideModal === "function") {
            Modal.currentModal.hideModal()
        } else {
            Modal.currentModal.trigger('modal-dismiss')
        }
    }
}

export default new ConfirmationModal();