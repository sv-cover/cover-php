function _real_do_expander(menu, expanded, collapsed, default_hidden) {
	element = document.getElementById(menu);
	expander = document.getElementById('expander_' + menu);

	if (!element || !expander)
		return;
	
	if ((element.style.display == '' && default_hidden) || element.style.display == 'none') {
		element.style.display = 'block';
		expander.src = expanded;
		
		return true;
	} else {
		element.style.display = 'none';
		expander.src = collapsed;
		
		return false;
	}
}

function menu_config_alert() {
	var response = this.get_response();

	if (response) 
		alert(response);
}

function save_menu_config(menu, visible) {
	request = new Connection();
	request.on_task_finished = menu_config_alert;
	request.get('data/menuconfig.php?menu=' + menu.substr('menu_'.length) + '&collapse=' + (visible ? 0 : 1));
}

function do_expander(menu, save) {
	var visible = _real_do_expander(menu, 'themes/default/images/expanded.png', 'themes/default/images/collapsed.png', true);
	
	if (!save)
		return;
	
	save_menu_config(menu, visible);
}

function do_menu_expander(menu, save) {
	var visible = _real_do_expander(menu, 'themes/default/images/min.png', 'themes/default/images/max.png', false);
	
	if (!save)
		return;

	save_menu_config(menu, visible);
}
