<?php

// TODO: This doesn't seem to be used

class URL_TokenParser extends Twig_TokenParser
{
	public function parse(Twig_Token $token)
	{
		$parser = $this->parser;
		$stream = $parser->getStream();

		$name = $stream->expect(Twig_Token::NAME_TYPE)->getValue();
		$stream->expect(Twig_Token::OPERATOR_TYPE, '=');
		$value = $parser->getExpressionParser()->parseExpression();
		$stream->expect(Twig_Token::BLOCK_END_TYPE);

		return new URL_Node_Node($name, $value, $token->getLine(), $this->getTag());
	}

	public function getTag()
	{
		return 'url';
	}
}

class URL_Node extends Twig_Node
{
	public function __construct($name, Twig_Node_Expression $value, $line, $tag = null)
	{
		parent::__construct(array('value' => $value), array('name' => $name), $line, $tag);
	}

	public function compile(Twig_Compiler $compiler)
	{
		$compiler
			->addDebugInfo($this)
			->write('url_for(\''.$this->getAttribute('name').'\', ')
			->subcompile($this->getNode('value'))
			->raw(");\n")
		;
	}
}

class URL_Twig_Extension extends Twig_Extension
{
	public function getName()
	{
		return 'cover';
	}

	public function getTokenParsers()
	{
		return array(new Cover_URL_TokenParser());
	}
}