<?php
/* This file is part of Houston | SSITU | (c) 2021 I-is-as-I-does */
namespace SSITU\Houston;

class Channel
{
    private $chanName;
    private $Houston;

    public function __construct($Houston, $chanName)
    {
        $this->Houston = $Houston;
        $this->chanName = $chanName;
    }

    public function log($level, $message, $context = [])
    {
        return $this->Houston->log($level, $message, $context, $this->chanName);
    }
}
