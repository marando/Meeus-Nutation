Meeus-Nutation
==============

This package can be used to calculate values related to the Earth's nutations. Unless otherwise noted, all methods in this package are an implementation of those discussed in the book "Astronomical Algorithms" by J. Meeus.

Installation 
------------
#### With Composer

```
$ composer require marando/meeus-nutation
```


Usage
-----

#### Earth's Nutations in Longitude (Δψ) and Obliquity (Δε) 
Earth's nutations in longitude (Δψ) and obliquity (Δε) can be found for a date as such:
```php
// Find the Earth's nutations
$date = AstroDate::parse('2015-Oct-10');
echo $n = Nutation::find($date);
echo $n->long
echo $n->obli
```
```
Output:
Δψ = -0°0'0".757, Δε = -0°0'8".742
-0°0'0".757
-0°0'8".742
```

#### Earth's Nutation in Right Ascension
The nutation in right ascension can be found like this:
```php
// Find nutation in right ascension 
$date = AstroDate::parse('2015-Oct-14 04:34:10');
echo Nutation::inRA($date);
```
```
Output: 
-0.0786 sec
```
The result is returned as a `Time` instance

#### Earth's Mean Obliquity of the Ecliptic
There are two algorithms for finding Earth's mean obliquity (ε0). The `meanObliquityIAU()` method uses coefficients provided from the IAU, and has an error of approximately 1" over a period of 2000 years, and about 10" over a period of 4000 years from the epoch J2000.
```php
$date = AstroDate::parse('2015-Jul-10');
echo Nutation::meanObliquityIAU($date);
```
```
Output:
23°26'14".183
```

The `meanObliquityLaskar()` method uses coefficients provided by J. Laskar, and has an arruracy estimated at 0".01 after 1000 years and a few arc seconds after 10,000 years on either side of the J2000 epoch. Also, of note is that this method is only valid over a period of 10,000 years on either side of the J2000 epoch.
```php
$date = AstroDate::parse('2015-Jul-10');
echo Nutation::meanObliquityLaskar($date);
```
```
Output:
23°14'22".374
```


#### Earth's True Obliquity of the Ecliptic
Earth's true obliquity (ε) is found by adding its nutations in obliquity (Δε) to its mean obliquity ε = ε0 + Δε. You can call upon the `trueObliquity()` method to do this automatically for a specified date:
```php
$date = AstroDate::parse('2015-Jul-10');
echo Nutation::trueObliquity($date);
```
```
Output:
23°14'12".682
```
