Meeus-Nutation
==============

#### Finding Earth's Nutations for a Date

```php
// Define the date to find nutations for
$date = AstroDate::parse('2015-Oct-10')

// Find the nutations
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
