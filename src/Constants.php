<?php

namespace GeekLab\GLPDO2;

interface Constants
{
    /** @const string DATE_REGEX Standard date format YYYY-MM-DD */
    public const DATE_REGEX = '%^(19|20)\d{2}-(0[1-9]|1[012])-(0[1-9]|[12]\d|3[01])$%';

    /** @const string DATE_TIME_REGEX Standard date time format YYYY-MM-DD HH:MM:SS */
    public const DATE_TIME_REGEX = '%^(19|20)\d{2}-(0[1-9]|1[012])-(0[1-9]|[12]\d|3[01]) ' .
    '([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$%';
}