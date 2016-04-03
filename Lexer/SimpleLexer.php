<?php

require_once 'Lexer.php';
require_once 'TokenStream.php';

class SimpleLexer extends Lexer
{
	private $mode;

	private $input;

	private $cursor;
	private $end;
	private $token_starts;

	private $current_char;
	private $last_char_pos;
	private $current_value = '';

	private $token_stream;

	const MODE_ALL = 0;
	const MODE_INSIDE_TAG = 1;
	const MODE_VAR = 2;
	const MODE_STRING = 3;
	const MODE_IDENT = 4;

	public function tokenize( $input )
	{
		// Create new token stream
		$this->token_stream = new TokenStream();

		// Set input
		$this->input = str_replace( [ "\n\r", "\r" ], "\n", $input );

		// Set mode
		$this->setMode( self::MODE_ALL );

		// Set start position
		$this->setCursor( 0 );

		// Set end position
		$this->end = strlen( $this->input );

		$this->last_char_pos = $this->end - 1;

		// Loop each character
		while( $this->cursor < $this->end )
		{
			$this->current_char = $this->getCharAtCursor();

			switch( $this->mode )
			{
				case self::MODE_ALL:
					$this->lexAll();
					break;
				case self::MODE_INSIDE_TAG:
					$this->lexInsideTag();
					break;
				case self::MODE_IDENT:
					$this->lexIdent();
					break;
				case self::MODE_VAR:
					$this->lexVar();
					break;
				case self::MODE_STRING:
					$this->lexString();
					break;
			}
		}

		return $this->token_stream;
	}

	private function getCharAtCursor()
	{
		return $this->input[ $this->cursor ];
	}

	private function getNextChar()
	{
		return $this->input[ $this->cursor + 1 ];
	}

	private function setMode( $mode )
	{
		$this->mode = $mode;
	}

	private function debug()
	{
		echo $this->cursor . ' > "' . $this->current_char . '"<br />';
		echo $this->end . '<br />';
	}

	private function lexAll()
	{
		// If were at the end of the file, write a text token with the remaining text
		if( $this->cursor + 1 === $this->end )
		{
			if( $this->current_value !== '' )
			{
				$this->token_stream->addToken( new Token( Token::T_TEXT, $this->current_value ) );
			}
			$this->advanceCursor(); // Advance one last time so the while loops stops running
			return;
		}

		if( $this->current_char === '{' && $this->getNextChar() === '{' )
		{
			// Add text until now to the token stream
			if( $this->current_value !== '' )
			{
				$this->token_stream->addToken( new Token( Token::T_TEXT, $this->current_value ) );
				$this->current_value = '';
			}

			// Add the opening tag to the stream
			$this->token_stream->addToken( new Token( Token::T_OPENING_TAG, '{{' ) );
			$this->advanceCursor( 2 );
			$this->setMode( self::MODE_INSIDE_TAG );
			return;
		}

		// Write text to temp token
		$this->current_value .= $this->current_char;
		$this->advanceCursor();
	}

	private function lexInsideTag()
	{
		if( $this->current_char === '}' && $this->getNextChar() === '}' )
		{
			$this->token_stream->addToken( new Token( Token::T_CLOSING_TAG, '}}' ) );
			$this->advanceCursor( 2 );

			$this->setMode( self::MODE_ALL );
			return;
		}
		else if( preg_match( '@' . Token::$token_regexes[ Token::T_IDENT ] . '@i', $this->current_char ) )
		{
			$this->setMode( self::MODE_IDENT );
			return;
		}
		else if( $this->current_char === '"' )
		{
			$this->setMode( self::MODE_STRING );
			$this->advanceCursor();
			return;
		}
		else if( $this->current_char === '@' )
		{
			$this->setMode( self::MODE_VAR );
			$this->advanceCursor();
			return;
		}
		else if( preg_match( '@' . Token::$token_regexes[ Token::T_OP ] . '@', $this->current_char ) )
		{
			$this->token_stream->addToken( new Token( Token::T_OP, $this->current_char ) );
			$this->advanceCursor();
			return;
		}

		$this->advanceCursor();
/*
		else if( preg_match( '@' . Token::$token_regexes[ Token::T_IDENT ] . '@i', $char ) )
		{
			$this->setMode( self::MODE_IDENT );
		}
		else if( $char === '"' )
		{
			$this->setMode( self::MODE_STRING );
		}
*/
	}

	// MODE IDENT
	private function lexIdent()
	{
		if( $this->current_char === ' ' )
		{
			$this->token_stream->addToken( new Token( Token::T_IDENT, $this->current_value ) );
			$this->current_value = '';
			$this->setMode( self::MODE_INSIDE_TAG );
			return;
		}

		$this->current_value .= $this->current_char;
		$this->advanceCursor();
	}

	// MODE STRING
	private function lexString()
	{
		if( $this->current_char === '"' )
		{
			$this->advanceCursor();
			$this->token_stream->addToken( new Token( Token::T_STRING, $this->current_value ) );
			$this->current_value = '';
			$this->setMode( self::MODE_INSIDE_TAG );
			return;
		}

		$this->current_value .= $this->current_char;
		$this->advanceCursor();
	}

	// MODE VAR
	private function lexVar()
	{
		if( $this->current_char === ' ' ) // Var is ended with a space
		{
			$this->advanceCursor();
			$this->token_stream->addToken( new Token( Token::T_VAR, $this->current_value ) );
			$this->current_value = '';
			$this->setMode( self::MODE_INSIDE_TAG );
			return;
		}
		else if( preg_match( '@' . Token::$token_regexes[ Token::T_OP ] . '@', $this->current_char ) )
		{
			$this->advanceCursor();
			$this->token_stream->addToken( new Token( Token::T_VAR, $this->current_value ) );
			$this->current_value = '';

			$this->token_stream->addToken( new Token( Token::T_OP, $this->current_char ) );
			$this->setMode( self::MODE_INSIDE_TAG );
			return;
		}

		$this->current_value .= $this->current_char;
		$this->advanceCursor();
	}


	private function setCursor( $n )
	{
		$this->cursor = $n;
	}

	private function advanceCursor( $n = 1 )
	{
		$this->cursor += $n;
	}
}
