<?
/**
  * A Class implementing the default view. New views should subclass this one.
  * creating functions in the same directory as the view, with the extension .phtml
  * will allow a call to function_name().
  *
  */
class View { 
	
	function get_name() {
		return str_replace("view", "", strtolower(get_class($this)));
	}
	
	/**
	 * Renders the file with the name of _$name.phtml in the directory of 
	 * the view
	 *
	 * @name the name of the partial to be rendered
	 * @params a hash with variables local to the partial
	 *
	 * @result true if the rendering was successful; otherwise false
	 * @author Pieter de Bie
	 **/
	function render_partial($name, $params = array())
	{
		$filename = dirname($this->__file) . '/_' . $name . '.phtml';

		if (file_exists($filename))
		{
			// Insert hash into local space
			extract($params);
			include($filename);
			return true;
		}
		
		return false;
	}
	
	/**
	 * Renders the file with the name of _$name.phtml in the directory of 
	 * the view and returns the resulting string
	 *
	 * @name the name of the partial to be rendered
	 * @params a hash with variables local to the partial
	 *
	 * @result the contents of the partial of null if the partial coouldn't
	 * be rendered
	 * @author Jesse van den Kieboom
	 **/
	function render_partial_s($name, $params = array())
	{
		ob_start();
		$this->render_partial($name, $params);
		$contents = ob_get_contents();
		ob_end_clean();
		
		return $contents;
	}
	
	function __call($name, $args) {
		$filename = dirname($this->__file) . '/' . $name . '.phtml';
		
		if (file_exists($filename))
		{
			// Insert hash into local space
			extract($args[0]);
			include($filename);
			return true;
		}
		
		// Fallback on old methods
		$method_name = "view_" . $name;
		if (method_exists($this, $method_name))
		{
			extract($args[0]);
			$this->$method_name($args[0]["model"], $args[0]["iter"], $args[0]);
			return true;
		}
		
		echo report_error(N__("View"), N__("De gespecificeerde functie <b>%s</b> kon niet worden gevonden voor de view <b>%s</b>."), $name, $this->get_name());
		return false;
	}
	
	/** 
	  * Common generic automatic authentication message view 
	  */
	function view_auth_common() {
		echo '<div class="messageBox error_message">' . 
	sprintf(__('Dit deel van de website is alleen toegankelijk voor Cover-leden. Vul links je E-Mail en wachtwoord in te loggen. Indien je je wachtwoord vergeten bent kun je een nieuw wachtwoord %s. Heb je problemen met inloggen, mail dan naar %s.'), '<a href="wachtwoordvergeten.php">' . __('aanvragen') . '</a>', '<a href="mailto:webcie@ai.rug.nl">' . __('de WebCie') . '</a>') . '</div>';
	}
	
	/** 
	  * Common generic automatic bestuur authentication message view 
	  */
	function view_auth_bestuur() {
		echo '<div class="messageBox error_message">' . 
	sprintf(__('Dit deel van de website is alleen toegankelijk voor het bestuur. Vul links je E-Mail en wachtwoord in te loggen. Indien je je wachtwoord vergeten bent kun je een nieuw wachtwoord %s. Heb je problemen met inloggen, mail dan naar %s.'), '<a href="wachtwoordvergeten.php">' . __('aanvragen') . '</a>', '<a href="mailto:webcie@ai.rug.nl">' . __('de WebCie') . '</a>') . '</div>';
	}
}
?>
