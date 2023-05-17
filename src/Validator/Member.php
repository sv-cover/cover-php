<?php
namespace App\Validator;

use Symfony\Component\Validator\Constraint;

class Member extends Constraint
{
	public $member_not_found_message = 'Member could not be found.';
}
