import Notification from "@typo3/backend/notification.js";

class ContextMenuActions {
	static publishRecord(table, uid, element) {
		Notification.info("Page " + uid + " is published in the background")
		if ("pages" !== table) {
			Notification.warning("Can not publish non-page via context menu entry")
			return;
		}
		const publishUrl = element["publishUrl"]
		if (!publishUrl) {
			Notification.error("Publish URL is not set for this page")
			return
		}
		fetch(publishUrl)
			.then(response => {
				if (!response.ok) {
					throw new Error("Something went wrong");
				}
				return response.json()
			})
			.then(body => {
				console.log(body)
				if (body.error) {
					Notification.error(body.message)
					return
				}
				Notification.success(body.message)
				top.document.dispatchEvent(new CustomEvent("typo3:pagetree:refresh"))
			})
			.catch(error => Notification.error(error.message))
	}
}

export default ContextMenuActions;
