<?php


namespace Larelastic\Elastic\Traits;


trait Migratable
{
    /**
     * Get the write alias.
     *
     * @return string
     */
    public function getWriteAlias()
    {
        return $this->getName().'_write';
    }
}
