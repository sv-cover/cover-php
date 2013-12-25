<?php
	require_once('data.php');
	require_once('login.php');

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
	function member_in_commissie($id, $easy = true) {
		$member_data = logged_in();

		if (!$member_data)
			return false;
		
		/* Easy members always return true */
		if ($easy && in_array(COMMISSIE_EASY, $member_data['commissies']))
			return true;
		
		return in_array($id, $member_data['commissies']);
	}

	/** @group Member
	  * Return the nick name of the currently logged in member
	  * @iter optional; iter to get the name of a specified member instead
	  * of the currently logged in one
	  * @result the currently logged in members nick name
	  */	
	function member_nick_name($iter = null) {
		if ($iter) {
			if (is_numeric($iter)) {
				$model = get_model('DataModelMember');
				$iter = $model->get_iter($iter);
			}
			
			$member_data = $iter->data;
		} elseif (!($member_data = logged_in())) {
			return __('Geen naam');
		}

		return $member_data['nick'];
	}
	
	/** @group Member
	  * Return the full name of the currently logged in member
	  * @iter optional; iter to get the name of a specified member instead
	  * of the currently logged in one
	  * @result the currently logged in members full name
	  */
	function member_full_name($iter = null) {
		if ($iter) {
			if (is_numeric($iter)) {
				$model = get_model('DataModelMember');
				$iter = $model->get_iter($iter);
			}
			
			$member_data = $iter->data;
		} elseif (!($member_data = logged_in())) {
			return __('Geen naam');
		}

		return $member_data['voornaam'] . ($member_data['tussenvoegsel'] ? (' ' . $member_data['tussenvoegsel']) : '') . ' ' . $member_data['achternaam'];
	}
?>
