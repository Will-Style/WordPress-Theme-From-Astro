document.addEventListener('DOMContentLoaded', function () {
	var elements_to_remove = ['#pw-weak', '.pw-weak'];
	var elements_to_remove_length = elements_to_remove.length;
	
	for (var i = 0; i < elements_to_remove_length; i++) {
		var nodes = document.querySelectorAll(elements_to_remove[i]);
		var nodes_length = nodes.length;
		
		for (var j = 0; j < nodes_length; j++) {
			if (nodes[j].parentNode) {
				nodes[j].parentNode.removeChild(nodes[j]);
			}
		}
	}
});