<?php
	require_once 'include/data.php';
	require_once 'include/auth.php';

	/** @group Member
	  * Check whether the currently logged in member is a member of
	  * a commissie. This function always returns true for members
	  * of the Easy commissie (see easy parameter)
	  * @id the id of the commissie to check
	  * @easy whether or not to always turn true when member is a member
	  * of the easy
	  *
	  * @result true if the currently logged in member is a member of
	  * the commissie with id `id'
	  */
	function member_in_commissie($id = null)
	{
		trigger_error("member_in_commissie is deprecated, use get_identity()->member_in_committee() or member_in_committee()", E_USER_NOTICE);

		return get_identity()->member_in_committee($id);
	}

	function member_in_committee($id = null)
	{
		return get_identity()->member_in_committee($id);
	}

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
			$iter = get_identity()->get_member();
		
		return $iter && $iter->has('nick')
			? $iter->get('nick')
			: __('Geen naam');
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
			$iter = $identity->get_member();
			$is_self = true;
		}

		// When the user is not found (or not logged in)
		if (!$iter)
			return __('Geen naam');

		if (($flags & BE_PERSONAL) && $is_self)
			return __('Jij!');

		// Or when the privacy settings prevent their name from being displayed
		if (!($flags & IGNORE_PRIVACY)
			&& !$is_self
			&& !$identity->member_in_committee(COMMISSIE_BESTUUR)
			&& !$identity->member_in_committee(COMMISSIE_KANDIBESTUUR)
			&& $model->is_private($iter, 'naam'))
			return __('Onbekend');

		return format_string($format, $iter);
	}
