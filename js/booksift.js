function setRowColour(rowID, newColour) {
	document.getElementById(rowID).style.backgroundColor = newColour;
}

function clearRowColour(rowID) {
	document.getElementById(rowID).style.backgroundColor = "";
}

function notifySearching(retryURL) {
	if (!retryURL) {
		var retryURL = '../search?keywords=' + searchForm.keywords.value;
	}
	self.SubTemplate.innerHTML = self.Searching.innerHTML.replace(/%retry_url%/g, retryURL);
}