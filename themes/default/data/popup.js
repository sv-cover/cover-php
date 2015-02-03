var popup_div = null;
var grow_items;

function MenuItem(image, url, size, tooltip) {
	this.image = image;
	this.url = url;
	this.size = size;
	this.tooltip = tooltip;
}
	function menu_item_built(parent, x, y) {
		a = document.createElement("a");
		
		if (this.url != '') {
			a.setAttribute("href", this.url);
		} else {
			a.setAttribute("href", "javascript:popup_sub_menu(" + this.menu + ")");
		}
		
		element = document.createElement("img");
		element.setAttribute("width", "0px");
		element.setAttribute("height", "0px");
		element.setAttribute("src", this.image);
		element.setAttribute("class", "popup");
		element.setAttribute("alt", "");
		element.setAttribute("title", this.tooltip);

		element.style.position = "absolute";
		element.style.width = "0px";
		element.style.height = "0px";

		element.style.left = x + "px";
		element.style.top = y + "px"
		
		a.appendChild(element);
		parent.appendChild(a);
		
		this.image_element = element;
		this.x = x;
		this.y = y;
	}
	
	function menu_item_grow(step) {
		var width = parseInt(this.image_element.style.width + 0, 10);
		var height = parseInt(this.image_element.style.height + 0, 10);
		var add = this.size * step;

		if (width >= this.size)
			return false;

		width = Math.ceil(width + add);
		height = Math.ceil(height + add);

		this.image_element.style.width = width + "px";
		this.image_element.style.height = height + "px";
		
		this.image_element.style.left = (this.x - (width / 2)) + "px";
		this.image_element.style.top = (this.y - (height / 2)) + "px";		
		
		return true;
	}

MenuItem.prototype.built = menu_item_built;
MenuItem.prototype.grow = menu_item_grow;

function remove_children(item) {
	var child = item.firstChild;
	var remove;
	
	while (child) {
		remove_children(child);
		remove = child;
		child = child.nextSibling;
		item.removeChild(remove);
	}
}

function sin(a) {
	return Math.round(Math.sin(a) * 100) / 100;
}

function cos(a) {
	return Math.round(Math.cos(a) * 100) / 100;
}

function PopupMenu(items) {
	this.items = items;
}

	function popup_menu_get_size() {
		var n = this.items.length;
		item_size = 0;

		for (i in this.items)
			item_size = Math.max(item_size, this.items[i].size);
		
		/* Increase the width a bit so they are nicely separated */
		//item_size = item_size * 1.2;

		/* r = \frac{width}{\sin{(\frac{2}{n} * \pi)}} */
		var angle = sin((2 * Math.PI) / n);
		return ((item_size / angle) * 2);
	}

	function popup_menu_popup(x, y) {
		if (popup_div == null) {
			/* Create the popup container */
			var mybody = document.getElementsByTagName("body")[0];

			popup_div = document.createElement("div");
			popup_div.appendChild(document.createTextNode(""));
			popup_div.setAttribute("class", "popup");
			mybody.appendChild(popup_div);
		} else {
			remove_children(popup_div);
		}

		/* Hide it when we built it */
		popup_div.style.display = 'block';
		popup_div.style.position = 'absolute';

		/* Calculate the size */
		var size = this.get_size();


		if (x >= 0)
			popup_div.style.left = (x - (size / 2)) + "px";
		else
			popup_div.style.left = (parseInt(popup_div.style.left + 0, 10) + (parseInt(popup_div.style.width + 0, 10) - size) / 2) + "px";
		
		if (y >= 0)
			popup_div.style.top = (y - (size / 2)) + "px";
		else
			popup_div.style.top = (parseInt(popup_div.style.top + 0, 10) + (parseInt(popup_div.style.height + 0, 10) - size) / 2) + "px";

		popup_div.style.width = size + "px";
		popup_div.style.height = size + "px";

		grow_items = new Array();

		/* Start adding the menu items */
		for (i in this.items) {
			var angle = ((2 * Math.PI) / this.items.length) * i;
			var item = this.items[i];
			
			item.built(popup_div, (cos(angle - 0.5 * Math.PI) * (size / 2)) + (size / 2), (sin(angle - 0.5 * Math.PI) * (size / 2)) + (size / 2));
			grow_items.push(item);
		}

		popup_menu_grow();
	}
	
function popup_menu_grow() {
	var timeout_time = 10;
	var timeout_reach = 100;
	var items_left = new Array();
	
	var step = timeout_time / timeout_reach;
	
	for (x in grow_items) {
		var item = grow_items[x];
		
		if (item.grow(step))
			items_left.push(item);
	}
	
	grow_items = items_left;
	
	if (grow_items.length > 0)
		setTimeout("popup_menu_grow()", timeout_time);
}

PopupMenu.prototype.popup = popup_menu_popup;
PopupMenu.prototype.get_size = popup_menu_get_size;

function set_contents(s) {
	div = document.getElementById('contents');
	
	div.innerHTML = s;
}

function display_properties(obj) {
	var s = "<pre>";

	for (x in obj) {
		s += x + ": " + obj[x] + "\n";
	}
	
	set_contents(s + "</pre>");
}

function on_context_menu(e) {
	if (!e)
		e = window.event;
		
	var x = e.clientX + document.documentElement.scrollLeft;
	var y = e.clientY + document.documentElement.scrollTop;

	if (e.shiftKey)
		popup_main.popup(x, y);
	
	return !e.shiftKey;
}

function on_mouse_click(e) {
	if (popup_div != null && popup_div.style.display == "block") {
		popup_div.style.display = "none";
	}
}

function popup_sub_menu(menu) {
	menu.popup(-1, -1);
}

/* Popup menu definitions */
var popup_sub = new PopupMenu(new Array(
		new MenuItem('themes/default/images/popup/profiel.png', 'profiel.php', 60, 'Profiel'),
		new MenuItem('themes/default/images/popup/boek.png', 'boeken.php', 60, 'Boeken bestellen'),
		new MenuItem('themes/default/images/popup/documenten.png', 'show.php?id=30', 60, 'Documenten')
		));

var menu_item_sub = new MenuItem('themes/default/images/popup/ki.png', '', 60, 'Cover');
menu_item_sub.menu = 'popup_sub';

var popup_main = new PopupMenu(new Array(
		new MenuItem('themes/default/images/popup/home.png', 'index.php', 60, 'Home'),
		new MenuItem('themes/default/images/popup/almanak.png', 'almanak.php', 60, 'Almanak'),
		menu_item_sub
		));

document.oncontextmenu = on_context_menu;
document.onclick = on_mouse_click;
