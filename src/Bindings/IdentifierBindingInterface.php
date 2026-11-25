<?php

namespace GeekLab\GLPDO2\Bindings;

use InvalidArgumentException;

interface IdentifierBindingInterface
{
    /**
     * Quote one identifier, or a list of them, for the SQL text.
     * Dots split a name into parts ("db.users.id"), and each part is quoted on its own.
     * A "*" as the last part ("users.*") is left as is.
     * An array becomes a comma separated list, for column lists.
     *
     * @param string | array<mixed> $value
     *
     * @return array{string}
     * @throws InvalidArgumentException
     */
    public function bIdent(string | array $value): array;
}
