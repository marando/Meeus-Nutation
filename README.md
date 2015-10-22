Meeus-Nutation
==============

#### Finding Earth's Nutations
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

#### Nutation in Right Ascension (aka Equation of the Equinoxes)
```php
$date = AstroDate::parse('2015-Oct-14 04:34:10');
echo Nutation::inRA($date);
```
```
Output: 
-0.0786 sec
```
