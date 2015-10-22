<?php

/*
 * Copyright (C) 2015 Ashley Marando
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

namespace Marando\Meeus\Nutation;

use \Exception;
use \InvalidArgumentException;
use \Marando\AstroDate\AstroDate;
use \Marando\Units\Angle;
use \Marando\Units\Time;

/**
 * Represents nutation in longitude (Δψ) and obliquity (Δε), also provides
 * additional useful nutation related static functions
 *
 * @property Angle $long Nutation in longitude (Δψ)
 * @property Angle $obli Nutation in obliquity (Δε)
 */
class Nutation {
  //----------------------------------------------------------------------------
  // Constructors
  //----------------------------------------------------------------------------

  /**
   * Creates a new Nutation from longitude and obliquity components
   *
   * @param Angle $long Nutation in longitude (Δψ)
   * @param Angle $obli Nutation in obliquity (Δε)
   */
  public function __construct(Angle $long, Angle $obli) {
    $this->long = $long;
    $this->obli = $obli;
  }

  //----------------------------------------------------------------------------
  // Properties
  //----------------------------------------------------------------------------

  /**
   * Nutation in longitude (Δψ)
   * @var Angle
   */
  protected $long;

  /**
   * Nutation in obliquity (Δε)
   * @var Angle
   */
  protected $obli;

  public function __get($name) {
    switch ($name) {
      // Pass through to property
      case 'long':
      case 'obli':
        return $this->{$name};

      default:
        throw new Exception("{$name} is not a valid property");
    }
  }

  public function __set($name, $value) {
    switch ($name) {
      // Pass through to property
      case 'long':
      case 'obli':
        $this->{$name} = $value;
        break;

      default:
        throw new Exception("{$name} is not a valid property");
    }
  }

  //----------------------------------------------------------------------------
  // Functions
  //----------------------------------------------------------------------------
  // // // Static

  /**
   * Calculates the nutation in Right Ascension (equation of the equinoxes) for
   * a given date
   *
   * @param  AstroDate $date Date to calculate
   * @return Time            Nutation in Right Ascension as time
   */
  public static function inRA(AstroDate $date) {
    // Copy time as UTC
    $utc = $date->copy()->toUTC();

    // Get the nutations and true obliquity of the date
    $n = static::find($utc);
    $ε = static::trueObliquity($utc);

    // Calculate the nutation in right ascension as seconds of time
    $nRA = rad2deg(($n->long->rad * cos($ε->rad)) / 15) * Time::SEC_IN_HOUR;
    return Time::sec($nRA)->round(4);
  }

  /**
   * Finds the mean obliquity of the ecliptic (ε0) for a given date by
   * automatically selecting the appropriate mean obliquity algorithm based on
   * the date value
   *
   * @param  AstroDate $date Date to calculate ε0 for
   * @return Angle           Mean obliquity (ε0)
   *
   * @see Meeus, Jean. Astronomical Algorithms. Richmond, Virg.: Willmann-Bell,
   *          2009. 147. Print.
   */
  public static function meanObliquity(AstroDate $date) {
    return static::meanObliquityIAU($date);

    /*
      try {
      // Attempt the Laskar method
      return static::meanObliquityLaskar($date);
      }
      catch (Exception $ex) {
      // Out of range for the Laskar method, use IAU method
      return static::meanObliquityIAU($date);
      }
     *
     */
  }

  /**
   * Finds the mean obliquity of the ecliptic (ε0) for a date by using the
   * coefficients provided by the IAU.
   *
   * By using this method, the error in ε0 reaches 1" over a period of 2000
   * years, and about 10" over a period of 4000 years from the epoch J2000.
   *
   * @param  AstroDate $date Date to find ε0 for
   * @return Angle           Mean obliquity (ε0)
   *
   * @see Meeus, Jean. Astronomical Algorithms. Richmond, Virg.: Willmann-Bell,
   *          2009. 147. Print.
   */
  public static function meanObliquityIAU(AstroDate $date) {
    $ε0TermsIAU = [
        Angle::dms(23, 26, 21.448)->rad,
        Angle::dms(0, 0, -46.8150)->rad,
        Angle::dms(0, 0, -0.00059)->rad,
        Angle::dms(0, 0, 0.001813)->rad,
    ];

    // Time factor
    $t = ($date->copy()->toUTC()->jd - 2451545.0) / 36525;

    // Evaluate the coefficients
    $ε0 = static::Horner($t, $ε0TermsIAU);
    return Angle::rad($ε0);
  }

  /**
   * Finds the mean obliquity of the ecliptic (ε0) for a given date by using the
   * coefficients provided by Laskar.
   *
   * By using this method the accuracy is estimated to be at 0".01 after 1000
   * years and a few arc seconds after 10,000 years on either side of the
   * epoch J2000. Also, this method is only valid over a period of 10,000 years
   * on either side of J2000 and will throe an exception if attempted on out
   * of range dates
   *
   * @param  AstroDate $date Date to find ε0 for
   * @return Angle           Mean obliquity (ε0)
   * @throws Exception       Occurs with an out of range date, |year| > 10,000
   *
   * @see Meeus, Jean. Astronomical Algorithms. Richmond, Virg.: Willmann-Bell,
   *          2009. 147. Print.
   */
  public static function meanObliquityLaskar(AstroDate $date) {
    // Time factor
    $t = ($date->copy()->toUTC()->jd - 2451545.0) / 36525;

    // Check for out of range date
    if (abs($t) >= 1) {
      $msg = "The Laskar method is not valid for the year {$date->year}.";
      throw new Exception($msg);
    }

    $ε0TermsLaskar = [
        Angle::dms(23, 26, 21.448)->rad,
        Angle::dms(0, 0, -4680.93)->rad,
        Angle::dms(0, 0, -1.55)->rad,
        Angle::dms(0, 0, 1999.25)->rad,
        Angle::dms(0, 0, -51.38)->rad,
        Angle::dms(0, 0, -249.67)->rad,
        Angle::dms(0, 0, -39.05)->rad,
        Angle::dms(0, 0, 7.12)->rad,
        Angle::dms(0, 0, 27.87)->rad,
        Angle::dms(0, 0, 5.79)->rad,
        Angle::dms(0, 0, 2.45)->rad,
    ];

    $ε0 = static::Horner($t, $ε0TermsLaskar);
    return Angle::rad($ε0);
  }

  /**
   * Finds the true obliquity of the ecliptic (ε = ε0 + Δε) for a given date.
   *
   * @param  AstroDate $date Date to calculate the ε for
   * @return Angle           True obliquity of the ecliptic (ε)
   */
  public static function trueObliquity(AstroDate $date) {
    $n  = Nutation::find($date);
    $ε0 = static::meanObliquity($date);

    // ε = ε0 + Δε
    return $ε = $ε0->add($n->obli);
  }

  /**
   * Calculates the nutations in longitude (Δψ) and latitude (Δε) for a date
   *
   * @param  AstroDate $date
   * @return static
   *
   * @see Meeus, Jean. Astronomical Algorithms. Richmond, Virg.: Willmann-Bell,
   *          2009. 143-147. Print.
   */
  public static function find(AstroDate $date) {
    // Time factor
    $t = ($date->copy()->toUTC()->jd - 2451545.0) / 36525;

    /*
     * Required terms
     * ----------------
     * D  = mean elongation of the Moon from the Sun
     * M  = mean anomaly of the Earth
     * M´ = mean anomaly of the Moon
     * F  = argument of latitude for Moon
     * Ω  = long. of asc. node for Moon's mean ecl. orbit (mean equi. of date)
     */
    $coeffD  = [297.85036, 445267.11480, -0.0019142, 1 / 189474];
    $coeffM  = [357.52772, 35999.050340, -0.0001603, -1 / 300000];
    $coeffM´ = [134.96298, 477198.867398, 0.0086972, 1 / 5620];
    $coeffF  = [93.27191, 483202.017538, -0.0036825, 1 / 327270];
    $coeffΩ  = [125.04452, -1934.136261, 0.0020708, 1. / 450000];

    // Calculate the terms
    $D  = Angle::deg(static::Horner($t, $coeffD))->norm();
    $M  = Angle::deg(static::Horner($t, $coeffM))->norm();
    $M´ = Angle::deg(static::Horner($t, $coeffM´))->norm();
    $F  = Angle::deg(static::Horner($t, $coeffF))->norm();
    $Ω  = Angle::deg(static::Horner($t, $coeffΩ))->norm();

    // Nutation coefficient terms
    $nutationTerms = static::NutationTerms();

    // Evaluate the nutation terms
    $Δψ = 0;
    $Δε = 0;
    for ($i = 0; $i < count($nutationTerms); $i++) {
      $row = $nutationTerms[$i];
      $arg = 0 +
              $row[0] * $D->rad +
              $row[1] * $M->rad +
              $row[2] * $M´->rad +
              $row[3] * $F->rad +
              $row[4] * $Ω->rad;

      $Δψ += ($row[5] + $row[6] * $t) * sin($arg) / 1e4 / Time::SEC_IN_HOUR;
      $Δε += ($row[7] + $row[8] * $t) * cos($arg) / 1e4 / Time::SEC_IN_HOUR;
    }

    // Store as angles
    $Δψ = Angle::deg($Δψ);
    $Δε = Angle::deg($Δε);

    // Return the nutation
    return new Nutation($Δψ, $Δε);
  }

  // // // Overrides

  /**
   * Represents this instance as a string
   * @return string
   */
  public function __toString() {
    return "Δψ = {$this->long}, Δε = {$this->obli}";
  }

  // // // Static

  /**
   * Evaluates a polynomial with coefficients c at x of which x is the constant
   * term by means of the Horner method
   *
   * @param  float                    $x The constant term
   * @param  array                    $c The coefficients of the polynomial
   * @return float                       The value of the polynomial
   * @throws InvalidArgumentException    Occurs if no coefficients are provided
   *
   * @see Meeus, Jean. "Avoiding powers." Astronomical Algorithms. Richmond,
   *          Virg.: Willmann-Bell, 2009. 10-11. Print.
   */
  protected static function Horner($x, $c) {
    if (count($c) == 0)
      throw new InvalidArgumentException('No coefficients were provided');

    $i = count($c) - 1;
    $y = $c[$i];
    while ($i > 0) {
      $i--;
      $y = $y * $x + $c[$i];
    }

    return $y;
  }

  /**
   * Periodic nutation terms
   * @return array
   */
  protected static function NutationTerms() {
    return [
        //D, M, M', F, Ω, sin 0, sin 1, cos 0, cos 1
        [0, 0, 0, 0, 1, -171996, -174.2, 92025, 8.9],
        [-2, 0, 0, 2, 2, -13187, -1.6, 5736, -3.1],
        [0, 0, 0, 2, 2, -2274, -0.2, 977, -0.5],
        [0, 0, 0, 0, 2, 2062, 0.2, -895, 0.5],
        [0, 1, 0, 0, 0, 1426, -3.4, 54, -0.1],
        [0, 0, 1, 0, 0, 712, 0.1, -7, 0],
        [-2, 1, 0, 2, 2, -517, 1.2, 224, -0.6],
        [0, 0, 0, 2, 1, -386, -0.4, 200, 0],
        [0, 0, 1, 2, 2, -301, 0, 129, -0.1],
        [-2, -1, 0, 2, 2, 217, -0.5, -95, 0.3],
        [-2, 0, 1, 0, 0, -158, 0, 0, 0],
        [-2, 0, 0, 2, 1, 129, 0.1, -70, 0],
        [0, 0, -1, 2, 2, 123, 0, -53, 0],
        [2, 0, 0, 0, 0, 63, 0, 0, 0],
        [0, 0, 1, 0, 1, 63, 0.1, -33, 0],
        [2, 0, -1, 2, 2, -59, 0, 26, 0],
        [0, 0, -1, 0, 1, -58, -0.1, 32, 0],
        [0, 0, 1, 2, 1, -51, 0, 27, 0],
        [-2, 0, 2, 0, 0, 48, 0, 0, 0],
        [0, 0, -2, 2, 1, 46, 0, -24, 0],
        [2, 0, 0, 2, 2, -38, 0, 16, 0],
        [0, 0, 2, 2, 2, -31, 0, 13, 0],
        [0, 0, 2, 0, 0, 29, 0, 0, 0],
        [-2, 0, 1, 2, 2, 29, 0, -12, 0],
        [0, 0, 0, 2, 0, 26, 0, 0, 0],
        [-2, 0, 0, 2, 0, -22, 0, 0, 0],
        [0, 0, -1, 2, 1, 21, 0, -10, 0],
        [0, 2, 0, 0, 0, 17, -0.1, 0, 0],
        [2, 0, -1, 0, 1, 16, 0, -8, 0],
        [-2, 2, 0, 2, 2, -16, 0.1, 7, 0],
        [0, 1, 0, 0, 1, -15, 0, 9, 0],
        [-2, 0, 1, 0, 1, -13, 0, 7, 0],
        [0, -1, 0, 0, 1, -12, 0, 6, 0],
        [0, 0, 2, -2, 0, 11, 0, 0, 0],
        [2, 0, -1, 2, 1, -10, 0, 5, 0],
        [2, 0, 1, 2, 2, -8, 0, 3, 0],
        [0, 1, 0, 2, 2, 7, 0, -3, 0],
        [-2, 1, 1, 0, 0, -7, 0, 0, 0],
        [0, -1, 0, 2, 2, -7, 0, 3, 0],
        [2, 0, 0, 2, 1, -7, 0, 3, 0],
        [2, 0, 1, 0, 0, 6, 0, 0, 0],
        [-2, 0, 2, 2, 2, 6, 0, -3, 0],
        [-2, 0, 1, 2, 1, 6, 0, -3, 0],
        [2, 0, -2, 0, 1, -6, 0, 3, 0],
        [2, 0, 0, 0, 1, -6, 0, 3, 0],
        [0, -1, 1, 0, 0, 5, 0, 0, 0],
        [-2, -1, 0, 2, 1, -5, 0, 3, 0],
        [-2, 0, 0, 0, 1, -5, 0, 3, 0],
        [0, 0, 2, 2, 1, -5, 0, 3, 0],
        [-2, 0, 2, 0, 1, 4, 0, 0, 0],
        [-2, 1, 0, 2, 1, 4, 0, 0, 0],
        [0, 0, 1, -2, 0, 4, 0, 0, 0],
        [-1, 0, 1, 0, 0, -4, 0, 0, 0],
        [-2, 1, 0, 0, 0, -4, 0, 0, 0],
        [1, 0, 0, 0, 0, -4, 0, 0, 0],
        [0, 0, 1, 2, 0, 3, 0, 0, 0],
        [0, 0, -2, 2, 2, -3, 0, 0, 0],
        [-1, -1, 1, 0, 0, -3, 0, 0, 0],
        [0, 1, 1, 0, 0, -3, 0, 0, 0],
        [0, -1, 1, 2, 2, -3, 0, 0, 0],
        [2, -1, -1, 2, 2, -3, 0, 0, 0],
        [0, 0, 3, 2, 2, -3, 0, 0, 0],
        [2, -1, 0, 2, 2, -3, 0, 0, 0],
    ];
  }

}
