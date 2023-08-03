<?php
require_once 'src/framework/data/data.php';
require_once 'src/framework/auth.php';

/** @group Member
  * Return the nick name of the currently logged in member
  * @iter optional; iter to get the name of a specified member instead
  * of the currently logged in one
  * @result the currently logged in members nick name
  */	
function member_nick_name($iter = null)
{
	if ($iter && is_numeric($iter))
	{
		$model = get_model('DataModelMember');
		$iter = $model->get_iter($iter);
	}
	else if ($iter === null)
		$iter = get_identity()->member();
	
	return $iter && $iter->has_value('nick')
		? $iter->get('nick')
		: __('No name');
}

/** @group Member
  * Return the full name of the currently logged in member
  * @iter optional; iter to get the name of a specified member instead
  * of the currently logged in one
  * @result the currently logged in members full name
  */

const IGNORE_PRIVACY = 1;
const BE_PERSONAL = 2;

function member_full_name($iter = null, $flags = 0)
{
	return member_format_name('$voornaam$tussenvoegsel|optional $achternaam', $iter, $flags);
}

function member_first_name($iter = null, $flags = 0)
{
	return member_format_name('$voornaam', $iter, $flags);
}

function member_format_name($format, $iter = null, $flags = 0)
{
	$model = get_model('DataModelMember');

	$identity = get_identity();

	if ($iter) {
		// If the iter is just a member id, fetch that member is data.
		if (is_numeric($iter))
			$iter = $model->get_iter($iter);

		$is_self = $identity->get('id') == $iter->get('id');
	}
	// No argument provided, get the full name of the currently logged in member.
	else {
		$iter = $identity->member();
		$is_self = true;
	}

	// When the user is not found (or not logged in)
	if (!$iter)
		return __('No name');

	if (($flags & BE_PERSONAL) && $is_self)
		return __('You!');

	// Or when the privacy settings prevent their name from being displayed
	if (!($flags & IGNORE_PRIVACY)
		&& !$is_self
		&& !$identity->member_in_committee(COMMISSIE_BESTUUR)
		&& !$identity->member_in_committee(COMMISSIE_KANDIBESTUUR)
		&& $model->is_private($iter, 'naam'))
		return __('Anonymous');

	return format_string($format, $iter);
}
