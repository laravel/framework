<?php

namespace Illuminate\Foundation\Testing\Constraints;

trait TableInfo
{
    /**
     * Get additional info about the records found in the database table.
     *
     * @param  string  $table
     * @return string
     */
    protected function getAdditionalInfo($table)
    {
        $results = $this->database->table($table)->get();

        if ($results->isEmpty()) {
            return 'The table is empty';
        }

        $description = 'Found: '.json_encode($results->take($this->show), JSON_PRETTY_PRINT);

        if ($results->count() > $this->show) {
            $description .= sprintf(' and %s others', $results->count() - $this->show);
        }

        return $description;
    }
}