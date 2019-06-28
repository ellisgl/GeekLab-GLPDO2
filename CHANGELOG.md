2019-??-?? (4.0.0):
* Splitting out methods for nullable. PHPMD was complaining.

2019-06-25 (3.0.1):
* Fixed for PHPMD issues (There shall not be ELSE statements...).
* Better exception types from the inner classes.

2019-06-23 (3.0.0):
* Mass refactoring, which split the bindings into multiple classes based loosely on the type. Also using a factory to fill a "package".

2019-06-22 (2.0.2):
* More simplification.

2019-06-22 (2.0.1):
* bBool should only use PDO::PARAM_BOOL.
* Added bBoolInt.
* Doc updates.

2019-06-22 (2.0.0):
* Making it more S.O.L.I.D.

2019-06-22 (1.3.3):
* Correct exception throwing for JSON binding.

2019-06-22 (1.3.2):
* Improving on code quality.

2019-06-22 (1.3.2):
* Improving on code quality.

2019-06-22 (1.3.1):
* Improving on code quality.

2019-05-27 (1.3.0):
* Added bJSON for JSON stuff.
* Updated PHP unit to 8.1.5 
* Updated Docs.
* Updated Tests.

2019-05-27 (1.2.2):	
* Removed dead code.	
* Tests.	

 2019-05-27 (1.2.1):	
* bFloat now binds to '%%', instead of '?'.	
* Tests.	
* Docs.	
* More fixes and tweaks.	

 2019-05-27 (1.2.0):	
* PHP Requirement set to >=7.2.	
* PSR Styling.	
* Updated for EA Inspections (Should improve speed and readability).	
* Transaction support is completed.	
* Tests.	
* Docs.	

 2018-08-20 (1.1.2):	
* Added exception throwing.	

 2018-08-08 (1.1.1):	
* Forced PHP requirement to >=7.0.	
* Added type hints.	
* Added return types.	
* Code comment cleanup.	

 2018-07-03 (1.1.0):	
* Changed Statement::intArray() to Statement->bIntArray(). Will bind to '%%'.	

 2018-04-06 (1.0.4):	
* Fixed issue with more than 10 raw bindings.	

 2017-12-05 (1.0.2):	
* Fixed case sensitivity with selectValue()	

 2017-11-18 (1.0.0):	
* Initial (re)release
