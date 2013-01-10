<?php namespace Illuminate\Database\Schema\Grammars;

use Illuminate\Support\Fluent;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Grammar as BaseGrammar;

abstract class Grammar extends BaseGrammar {

	/**
	 * Compile a foreign key command.
	 *
	 * @param  Illuminate\Database\Schema\Blueprint  $blueprint
	 * @param  Illuminate\Support\Fluent  $command
	 * @return string
	 */
	public function compileForeign(Blueprint $blueprint, Fluent $command)
	{
		$table = $this->wrapTable($blueprint);

		$on = $this->wrapTable($command->on);

		// We need to prepare several of the elements of the foreign key definition
		// before we can create the SQL, such as wrapping the tables and convert
		// an array of columns to comma-delimited strings for the SQL queries.
		$columns = $this->columnize($command->columns);

		$onColumns = $this->columnize((array) $command->references);

		$sql = "alter table {$table} add constraint {$command->index} ";

		$sql .= "foreign key ({$columns}) references {$on} ({$onColumns})";

		// Once we have the basic foreign key creation statement constructed we can
		// build out the syntax for what should happen on an update or delete of
		// the affected columns, which will get something like "cascade", etc.
		if ( ! is_null($command->onDelete))
		{
			$sql .= " on delete {$command->onDelete}";
		}

		if ( ! is_null($command->onUpdate))
		{
			$sql .= " on update {$command->onUpdate}";
		}

		return $sql;
	}

	/**
	 * Compile the blueprint's column definitions.
	 *
	 * @param  Illuminate\Database\Schema\Blueprint  $blueprint
	 * @return array
	 */
	protected function getColumns(Blueprint $blueprint)
	{
		$columns = array();

		foreach ($blueprint->getColumns() as $column)
		{
			// Each of the column types have their own compiler functions which are
			// responsible for turning the column definition into its SQL format
			// for the platform. Then column modifiers are compiled and added.
			$sql = $this->wrap($column).' '.$this->getType($column);

			$columns[] = $this->addModifiers($sql, $blueprint, $column);
		}

		return $columns;
	}

	/**
	 * Add the column modifiers to the definition.
	 *
	 * @param  string  $sql
	 * @param  Illuminate\Database\Schema\Blueprint  $blueprint
	 * @param  Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function addModifiers($sql, Blueprint $blueprint, Fluent $column)
	{
		foreach ($this->modifiers as $modifier)
		{
			$sql .= $this->{"modify{$modifier}"}($blueprint, $column);
		}

		return $sql;
	}

	/**
	 * Get the primary key command if it exists on the blueprint.
	 *
	 * @param  Illuminate\Database\Schema\Blueprint  $blueprint
	 * @return Illuminate\Support\Fluent|null
	 */
	protected function getCommandByName(Blueprint $blueprint, $name)
	{
		$commands = $this->getCommandsByName($blueprint, $name);

		if (count($commands) > 0)
		{
			return reset($commands);
		}
	}

	/**
	 * Get all of the commands with a given name.
	 *
	 * @param  Illuminate\Database\Schema\Blueprint  $blueprint
	 * @param  string  $name
	 * @return array
	 */
	protected function getCommandsByName(Blueprint $blueprint, $name)
	{
		return array_filter($blueprint->getCommands(), function($value) use ($name)
		{
			return $value->name == $name;
		});
	}

	/**
	 * Get the SQL for the column data type.
	 *
	 * @param  Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function getType(Fluent $column)
	{
		return $this->{"type".ucfirst($column->type)}($column);
	}

	/**
	 * Add a prefix to an array of values.
	 *
	 * @param  string  $prefix
	 * @param  array   $values
	 * @return array
	 */
	public function prefixArray($prefix, array $values)
	{
		return array_map(function($value) use ($prefix)
		{
			return $prefix.' '.$value;

		}, $values);
	}

	/**
	 * Wrap a table in keyword identifiers.
	 *
	 * @param  mixed   $table
	 * @return string
	 */
	public function wrapTable($table)
	{
		if ($table instanceof Blueprint) $table = $table->getTable();

		return parent::wrapTable($table);
	}

	/**
	 * Wrap a value in keyword identifiers.
	 *
	 * @param  string  $value
	 * @return string
	 */
	public function wrap($value)
	{
		if ($value instanceof Fluent) $value = $value->name;

		return parent::wrap($value);
	}

	/**
	 * Format a value so that it can be used in "default" clauses.
	 *
	 * @param  mixed   $value
	 * @return string
	 */
	protected function getDefaultValue($value)
	{
		if (is_bool($value)) return intval($value);

		return strval($value);
	}

}