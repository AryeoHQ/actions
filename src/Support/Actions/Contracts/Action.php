<?php

namespace Support\Actions\Contracts;

interface Action
{
    public static function make(): static;
}