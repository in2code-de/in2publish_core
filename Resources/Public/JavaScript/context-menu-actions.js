import Notification from "@typo3/backend/notification.js";

const PENDING_NOTIFICATION_DURATION = 3

class ContextMenuActions {
	static publishRecord(table, uid, element) {
		if ("pages" !== table) {
			Notification.warning("Can not publish non-page via context menu entry")
			return;
		}
		const publishUrl = element["publishUrl"]
		if (!publishUrl) {
			Notification.error("Publish URL is not set for this page")
			return
		}
		Notification.info(element["publishPendingLabel"], "", PENDING_NOTIFICATION_DURATION)
		fetch(publishUrl)
			.then(response => {
				if (!response.ok) {
					throw new Error("Something went wrong");
				}
				return response.json()
			})
			.then(body => {
				if (body.error) {
					Notification.error(body.message)
					return
				}
				if (!body.success) {
					Notification.warning(body.message)
					return
				}
				Notification.success(body.message)
				top.document.dispatchEvent(new CustomEvent("typo3:pagetree:refresh"))
			})
			.catch(error => Notification.error(error.message))
	}
}

export default ContextMenuActions;
