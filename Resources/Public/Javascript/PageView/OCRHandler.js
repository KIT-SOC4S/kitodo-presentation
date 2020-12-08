var dlfOCRHandler = function(fulltextURL) {
  
    this.url = fulltextURL;
  
    this.wip = undefined;
}

dlfOCRHandler.prototype.checkWIP = function(xmlFile) {
  
    var xml_doc = $(xmlFile),
	$wip = xml_doc.find("Fulltext");
  
    if ($wip.text() == "WIP") {
	console.log("wip");
	this.showModal();
	return true;
    }
    
    this.hideModal();
    return false;
}

dlfOCRHandler.prototype.showModal = function() {
    var modal = $("#tx-dlf-fulltext-refresh-modal");
    modal.show();
}

dlfOCRHandler.prototype.hideModal = function() {
    var modal = $("#tx-dlf-fulltext-refresh-modal");
    modal.hide();
}
