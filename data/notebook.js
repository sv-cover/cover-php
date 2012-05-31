function Notebook(identifier, current_page) {
	this.identifier = identifier;
	this.current_page = current_page;
}

function show_notebook_page(notebook, pageid) {
	var current_page = notebook.current_page;
	
	if (current_page != -1) {
		div = document.getElementById(notebook.identifier + "_page_" + current_page);
		div.style.display = "none";
		
		li = document.getElementById(notebook.identifier + "_tab_" + current_page);
		li.setAttribute("class", "");
	}
	
	current_page = pageid;
	div = document.getElementById(notebook.identifier + "_page_" + current_page);
	div.style.display = "block";
	
	li = document.getElementById(notebook.identifier + "_tab_" + current_page);
	li.setAttribute("class", "selected");
	
	notebook.current_page = current_page;
}
