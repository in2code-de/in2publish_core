'use strict';

/**
 * Module: @in2code/in2publish_core/information-modal.js
 */
import Modal from '@typo3/backend/modal.js';
import { html } from 'lit';

class InformationModal {
	// Klassen-Eigenschaft mit Pfeilfunktion, um die 'this'-Bindung zu gewährleisten
	informationModalMethod = (event) => {
		this.handleClickInformationModal(event);
	};

	constructor() {
		document.querySelectorAll('.js-in2publish-information-modal').forEach(element => {
			this.registerClickHandler(element);
		});
	}

	/**
	 * Registriert einen Klick-Handler für Information-Modal-Elemente.
	 * Für Elemente, die Informationen ohne Aktions-Buttons anzeigen sollen.
	 *
	 * Elemente mit der Klasse 'js-in2publish-information-modal' werden automatisch beim Seitenaufbau registriert.
	 * Diese Methode kann auch für Elemente verwendet werden, die via fetch nachgeladen werden.
	 *
	 * @param {HTMLElement} node Das HTML-Element.
	 */
	registerClickHandler(node) {
		if (node.dataset.easyModalTitle) {
			node.addEventListener('click', this.informationModalMethod, { capture: true });
		}
	}

	/**
	 * Behandelt das Klick-Ereignis für das Informations-Modal.
	 * @param {Event} event Das auslösende Event.
	 */
	handleClickInformationModal(event) {
		event.preventDefault();
		const target = event.currentTarget;
		const severity = parseInt(target.dataset.easyModalSeverity ?? '-1', 10);

		const content = this.processModalContent(target.dataset.easyModalContent ?? '');

		Modal.advanced({
			type: 'default',
			title: target.dataset.easyModalTitle,
			content: content,
			severity: severity,
			buttons: this.getInformationModalButtons(),
		});
	}

	/**
	 * Verarbeitet den Modal-Content und konvertiert Zeilenumbrüche
	 * @param {string} rawContent Der rohe Content-String
	 * @returns {TemplateResult} Lit-HTML Template
	 */
	processModalContent(rawContent) {
		// Verschiedene Zeilenumbruch-Formate unterstützen
		const processedContent = rawContent
			.replace(/\\n/g, '\n')     // Escaped \n zu echten Zeilenumbrüchen
			.replace(/\r\n/g, '\n')    // Windows-Zeilenumbrüche
			.replace(/\r/g, '\n')      // Mac-Zeilenumbrüche
			.split('\n')               // In Array aufteilen
			.map(line => line.trim())  // Whitespace entfernen
			.filter(line => line !== ''); // Leere Zeilen entfernen (optional)

		return html`
            <div class="information-modal-content">
                ${processedContent.map((line, index) =>
			html`${index > 0 ? html`<br>` : ''}${line}`
		)}
            </div>
        `;
	}

	/**
	 * Gibt die Buttons für das Informations-Modal zurück (nur ein Schließen-Button).
	 * @returns {Array} Ein Array mit dem Button-Objekt.
	 */
	getInformationModalButtons() {
		return [
			{
				text: TYPO3.lang['button.close'] || 'Close',
				btnClass: 'btn-default',
				name: 'close',
				active: true,
				trigger: this.closeModal.bind(this),
			},
		];
	}

	/**
	 * Schließt das aktuell geöffnete Modal.
	 */
	closeModal() {
		if (typeof Modal.currentModal?.hideModal === 'function') {
			Modal.currentModal.hideModal();
		} else {
			Modal.currentModal?.trigger('modal-dismiss');
		}
	}
}

// Erstellt eine Instanz der Klasse und exportiert sie als Standard-Export.
export default new InformationModal();
