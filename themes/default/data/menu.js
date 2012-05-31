function dropDown(name)
{

	if (document.getElementsByClassName == undefined) {
	document.getElementsByClassName = function(className)
	{
		var hasClassName = new RegExp("(?:^|\\s)" + className + "(?:$|\\s)");
		var allElements = document.getElementsByTagName("*");
		var results = [];

		var element;
		for (var i = 0; (element = allElements[i]) != null; i++) {
			var elementClass = element.className;
			if (elementClass && elementClass.indexOf(className) != -1 && hasClassName.test(elementClass))
				results.push(element);
		}

		return results;
	}
	}

			//remove all active dropdown menu's
	var allElements = document.getElementsByClassName("expander");
	for (var idx = 0; idx != allElements.length; ++idx)
	{
					//for every other element than the one we want to show
		if (allElements[idx].getAttribute("id") != name)


					//remove the style attribute if it exists
			if (allElements[idx].getAttribute("style") != null)
			{
				allElements[idx].removeAttribute("style");
			}
				
	}

			//get the attribute we want to show
	var element = document.getElementById(name);
	if (element.getAttribute("style") == null)
	{
			//add styling attribute to display element
		element.setAttribute("style","display:block;")
	}
	else
	{
			//remove the style attribute
		element.removeAttribute("style");
	}

	function removeStyle(element)
	{
		element.removeAttribute("style");
	}
}