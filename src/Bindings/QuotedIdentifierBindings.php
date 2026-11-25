<?php

namespace GeekLab\GLPDO2\Bindings;

use InvalidArgumentException;

/**
 * Quotes identifiers by wrapping each part in quote characters and doubling any closing quote inside it.
 * The defaults use the SQL standard double quote, as PostgreSQL, SQLite and Oracle do.
 */
class QuotedIdentifierBindings implements IdentifierBindingInterface
{
    private string $open;
    private string $close;

    /**
     * @param string      $open  Opening quote character.
     * @param string|null $close Closing quote character. Defaults to $open. For SQL Server use '[' and ']'.
     */
    public function __construct(string $open = '"', ?string $close = null)
    {
        $close ??= $open;

        if ($open === '' || $close === '') {
            throw new InvalidArgumentException('Identifier quote characters can not be empty.');
        }

        $this->open = $open;
        $this->close = $close;
    }

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
    public function bIdent(string | array $value): array
    {
        $names = is_array($value) ? $value : [$value];

        if (empty($names)) {
            throw new InvalidArgumentException('Can not quote an empty list of identifiers.');
        }

        $quoted = [];

        foreach ($names as $name) {
            if (!is_string($name)) {
                throw new InvalidArgumentException('Identifiers must be strings.');
            }

            $quoted[] = $this->quoteName($name);
        }

        return [implode(', ', $quoted)];
    }

    /**
     * Quote a possibly qualified name, such as "db.users.id" or "users.*".
     *
     * @param string $name
     *
     * @return string
     * @throws InvalidArgumentException
     */
    private function quoteName(string $name): string
    {
        $parts = explode('.', $name);
        $last = count($parts) - 1;

        foreach ($parts as $i => $part) {
            if ($i === $last && $part === '*') {
                continue;
            }

            $parts[$i] = $this->quotePart($part);
        }

        return implode('.', $parts);
    }

    /**
     * Quote a single identifier part.
     *
     * @param string $part
     *
     * @return string
     * @throws InvalidArgumentException
     */
    private function quotePart(string $part): string
    {
        if ($part === '') {
            throw new InvalidArgumentException('Identifiers and their parts can not be empty.');
        }

        if (str_contains($part, "\0")) {
            throw new InvalidArgumentException('Identifiers can not contain NUL bytes.');
        }

        return $this->open . str_replace($this->close, $this->close . $this->close, $part) . $this->close;
    }
}
