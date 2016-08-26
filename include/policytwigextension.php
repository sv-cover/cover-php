<?php

class Policy_Twig_Node_Expression_UserCanCreate extends Twig_Node_Expression
{
    public function __construct(Twig_NodeInterface $node, $lineno)
    {
        parent::__construct(array('node' => $node), array(), $lineno);
    }

    public function compile(Twig_Compiler $compiler)
    {
        $compiler->raw(' get_policy(');
        $compiler->subcompile($this->getNode('node'));
        $compiler->raw(')->user_can_create()');
    }
}


abstract class Policy_Twig_Node_Expression_UserCan extends Twig_Node_Expression
{
    public function __construct(Twig_NodeInterface $node, $lineno)
    {
        parent::__construct(array('node' => $node), array(), $lineno);
    }

    public function compile(Twig_Compiler $compiler)
    {
        $compiler->raw(' get_policy(call_user_func(array(');
        $compiler->subcompile($this->getNode('node'));
        $compiler->raw(', \'model\')))->' . $this->action() . '(');
        $compiler->subcompile($this->getNode('node'));
        $compiler->raw(')');
    }

    abstract protected function action();
}

class Policy_Twig_Node_Expression_UserCanRead extends Policy_Twig_Node_Expression_UserCan
{
    protected function action()
    {
        return 'user_can_read';
    }
}

class Policy_Twig_Node_Expression_UserCanUpdate extends Policy_Twig_Node_Expression_UserCan
{
    protected function action()
    {
        return 'user_can_update';
    }
}

class Policy_Twig_Node_Expression_UserCanDelete extends Policy_Twig_Node_Expression_UserCan
{
    protected function action()
    {
        return 'user_can_delete';
    }
}

class PolicyTwigExtension extends Twig_Extension
{
	public function getName()
	{
		return 'policy';
	}

	public function getOperators()
	{
		return [
			[
				'user_can_create' => [
					'precedence' => 50, 
					'class' => 'Policy_Twig_Node_Expression_UserCanCreate'
				],
				'user_can_read' => [
					'precedence' => 50,
					'class' => 'Policy_Twig_Node_Expression_UserCanRead'
				],
				'user_can_update' => [
					'precedence' => 50,
					'class' => 'Policy_Twig_Node_Expression_UserCanUpdate'
				],
				'user_can_delete' => [
					'precedence' => 50,
					'class' => 'Policy_Twig_Node_Expression_UserCanDelete'
				]
            ],
            [] // binary
	   ];
    }

    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('user_can_read', function($iters) {
                return array_filter($iters, function($iter) {
                    return get_policy($iter->model())->user_can_read($iter);
                });
            }),
            new Twig_SimpleFilter('user_can_update', function($iters) {
                return array_filter($iters, function($iter) {
                    return get_policy($iter->model())->user_can_update($iter);
                });
            }),
            new Twig_SimpleFilter('user_can_delete', function($iters) {
                return array_filter($iters, function($iter) {
                    return get_policy($iter->model())->user_can_delete($iter);
                });
            })
        ];
    }
} 