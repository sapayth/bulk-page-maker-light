function init() {
	document.getElementById('cmb_post_type').onchange = typeChangeHandler;
}

function typeChangeHandler() {
	var postType = document.getElementById('cmb_post_type').value;
	var pageCmb = document.getElementById('page_id');
	if (postType == 'post') {
	    pageCmb.setAttribute('disabled', true);
	    pageCmb.selectedIndex = 0;
	} else {
		pageCmb.removeAttribute('disabled');
	}
}

window.onload = init;