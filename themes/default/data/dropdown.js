$(document).ready(activate);

function activate()
{
	$(".dropDown").click(function(event){event.preventDefault(); dropMenu(event)});
}

/*event argument is a normalized jQuery event (cross-browser)*/
function dropMenu(event)
{
		//remove the click handlers
	$(".dropDown").unbind();
	
		//add the expaneded class
	$(event.currentTarget).addClass("expanded");
	//jsTrace.send($(event.currentTarget).html());
		//drop attribute contains the name of the subMenu id that we must use
	var subName = $(event.currentTarget).find("a").attr("drop");

		//hide all visible expander menu's
	var areVisible = $(".subNav").find(".expander:visible");
	//jsTrace.send("visible length: " + areVisible.length);
	if (areVisible.length != 0)
	{
			//if visible menu is not the one we are showing or there are more than one, hide them all
		$(".subNav").find(".expander:visible").each(function(idx, element)
							  {
										//if the visible element is the same as whose link we just clicked
										//only hide the element, do not show it again
									if ($(element).attr("id") == subName)
									{
										//jsTrace.send("hide menu");
										$(event.currentTarget).removeClass("expanded");
										$(element).slideToggle(100);
										$(".dropDown").click(function(e){e.preventDefault(); dropMenu(e)});
									}
									else
									{
										//flip the corresponding expander to collapsed
										$("a[drop="+$(element).attr("id")+"]").parent().removeClass("expanded");
										//insert callback to show function
										$(element).slideToggle(100, function()
													   {
															//jsTrace.send("hide callback");
															showSubMenu(subName);
													   });
									}
							  });
		
	}
	else
	{
		//jsTrace.send("no menus visible");
		showSubMenu(subName);
	}
}

function showSubMenu(name)
{
	//jsTrace.send("showSubMenu");
			//if submenu is not visible, show
	var subMenu = $(".subNav").find("#"+name);
	if ($(subMenu).is(":hidden"))
	{
		//jsTrace.send(name + " is hidden, show.");
		$(subMenu).slideToggle(200, function()
					   {
						//re-attach the click handler
						$(".dropDown").click(function(event){event.preventDefault(); dropMenu(event)});
					   });
	}
}

jQuery(function($) {
	$('#language-switch label').click(function(e) {
		e.preventDefault();
		$(this).find('input[type=radio]').prop('checked', true);
		$(this.form).submit();
	});
});