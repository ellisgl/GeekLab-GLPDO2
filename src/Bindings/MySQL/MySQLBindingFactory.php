<?php

namespace GeekLab\GLPDO2\Bindings\MySQL;

use GeekLab\GLPDO2\Bindings\Bindings;

class MySQLBindingFactory
{
    public static function build(): Bindings
    {
        return new Bindings(
            new MySQLDateTimeBindings(),
            new MySQLLogicBindings(),
            new MySQLNumericBindings(),
            new MySQLRawBindings(),
            new MySQLStringBindings()
        );
    }
}
