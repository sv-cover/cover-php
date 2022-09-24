<?php
namespace App\Validator;

use Symfony\Component\Validator\Constraint;

class FilemanagerFile extends Constraint
{
    public $extension_message = 'File type not allowed. Please upload one of the following: {{ extension }}';
}
