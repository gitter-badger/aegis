<?php

namespace Aegis\Node;

use Aegis\Node;

class RootNode extends Node
{
	public function compile( $compiler )
	{
		foreach( $this->getChildren() as $c ) {

			$c->compile( $compiler );
		}
	}
}