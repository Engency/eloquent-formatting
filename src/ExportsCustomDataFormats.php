<?php

namespace Engency\DataStructures;

interface ExportsCustomDataFormats
{
    /**
     * @param string $format
     * @param bool   $resolveRelations
     *
     * @return array
     */
    public function toArrayFormat(string $format = 'default', bool $resolveRelations = true);

    /**
     * @param string $format
     *
     * @return $this
     */
    public function setFormat(string $format = 'default');
}