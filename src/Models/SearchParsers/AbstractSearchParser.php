<?php
use \Chibi\Sql as Sql;

abstract class AbstractSearchParser
{
	protected $statement;

	public function disassembleTokens($searchString)
	{
		return preg_split('/\s+/', $searchString);
	}

	public function assembleTokens($tokens)
	{
		return implode(' ', $tokens);
	}

	public function addTokenToSearchString($searchString, $token)
	{
		$tokensToInclude = is_array($token)
			? $token
			: [$token];
		$tokens = $this->disassembleTokens($searchString);
		$newTokens = array_filter(array_unique(array_merge($tokens, $tokensToInclude)));
		return $this->assembleTokens($newTokens);
	}

	public function removeTokenFromSearchString($searchString, $token)
	{
		$tokensToExclude = is_array($token)
			? $token
			: [$token];
		$tokens = $this->disassembleTokens($searchString);
		$newTokens = array_diff($tokens, $tokensToExclude);
		return $this->assembleTokens($newTokens);
	}

	public function decorate($statement, $searchString)
	{
		$this->statement = $statement;

		$tokens = $this->disassembleTokens($searchString);
		$tokens = array_filter($tokens);
		$tokens = array_unique($tokens);
		$this->processSetup($tokens);

		foreach ($tokens as $token)
		{
			$neg = false;
			if ($token{0} == '-')
			{
				$token = substr($token, 1);
				$neg = true;
			}

			if (strpos($token, ':') !== false)
			{
				list ($key, $value) = explode(':', $token, 2);
				$key = strtolower($key);

				if ($key == 'order' or $key == 'sort')
				{
					$this->internalProcessOrderToken($value, $neg);
				}
				else
				{
					if (!$this->processComplexToken($key, $value, $neg))
						throw new SimpleException('Invalid search token "%s"', $key);
				}
			}
			else
			{
				if (!$this->processSimpleToken($token, $neg))
					throw new SimpleException('Invalid search token "%s"', $token);
			}
		}
		$this->processTeardown();
	}

	protected function processSetup(&$tokens)
	{
	}

	protected function processTeardown()
	{
	}

	protected function internalProcessOrderToken($orderToken, $neg)
	{
		$arr = preg_split('/[;,]/', $orderToken);
		if (count($arr) == 1)
			$arr []= 'desc';

		if (count($arr) != 2)
			throw new SimpleException('Invalid search order token "%s"', $orderToken);

		$orderByString = strtolower(array_shift($arr));
		$orderDirString = strtolower(array_shift($arr));
		if ($orderDirString == 'asc')
			$orderDir = Sql\Statements\SelectStatement::ORDER_ASC;
		elseif ($orderDirString == 'desc')
			$orderDir = Sql\Statements\SelectStatement::ORDER_DESC;
		else
			throw new SimpleException('Invalid search order direction "%s"', $searchOrderDir);

		if ($neg)
		{
			$orderDir = $orderDir == Sql\Statements\SelectStatement::ORDER_ASC
				? Sql\Statements\SelectStatement::ORDER_DESC
				: Sql\Statements\SelectStatement::ORDER_ASC;
		}

		if (!$this->processOrderToken($orderByString, $orderDir))
			throw new SimpleException('Invalid search order type "%s"', $orderByString);
	}

	protected function processComplexToken($key, $value, $neg)
	{
		return false;
	}

	protected function processSimpleToken($value, $neg)
	{
		return false;
	}

	protected function processOrderToken($orderToken, $orderDir)
	{
		return false;
	}
}
