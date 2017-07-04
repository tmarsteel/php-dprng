# php-dprng

Implementation of a DPRNG algorithm aimed at being simple to implement in almost any general-purpose programming language. Its intended use are simple games; scenarios where a pseudo-random sequence has to be replayed deterministically with low memory and computational cost. **The algorithm is not secure. DO NOT USE THIS FOR SECURITY RELATED TASKS.**

A JavaScript implementation that generates equal sequences for the same salts/seeds can be found [here](//github.com/tmarsteel/js-dprng)

Please contribute with your own implementation in your favorite language! A description of the algorithm and test vectors can be found in [algorithm.md](algorithm.md). I`ll cross-link compliant forks :)

## Installation & Usage

`composer require tmarsteel/php-dprng:1.*` or just download the [single class file](tmarsteel/dprng/DPRNG.php).

```php
use tmarsteel\dprng\DPRNG;

// secure random numbers (seeded from mcrypt_create_iv() if available, rand() otherwise)
$rng = new DPRNG();

// deterministic random sequence
$seed = 0xA2F38C0;
$rng = new DPRNG($seed);

// generate random numbers
$random = $rng->next(); // random float from 0 inclusive to 1 exclusive (same range as Math.random())

$random = $rng->nextInt(14, 300); // random integer in the range 14 to 299

$bytes = $rng->nextBytes(30); // 30 random integers in the range 0 to 255
```

## Entropy / Randomness inspection
[ENT](http://www.fourmilab.ch/random/) is a neat program to inspect the entropy and randomness of a sequence of bytes or bits. This table compares its output with values returned from rand(), mcrypt_create_iv() and this DPRNG. For explanations on the metrics see [the ENT Website](http://www.fourmilab.ch/random/) **These numbers do only show that output of this DPRNG is sufficently random to be indistinguishable from random noise to HUMANS. Computers will be able to work out the initial salt given a RIDICULOUSLY SMALL SAMPLE!**

| RNG | OS   | Entropy | Arithmetic mean | Chi-Square % | Correlation coefficient | Monte-Carlo PI error % |
| :-- | :--- | ------: | --------------: | -----------: | ----------------------: | ---------------------: |
rand()|Windows 10| 7.998118 | 127.6669 | 99.99 | \-0.006520 | 0.55 |
DPRNG seeded by mcrypt_create_iv()|Windows 10| 7.992490 | 127.7548 | 29.17 | 0.005192 | 0.02 |
mcrypt_create_iv()|Windows 10| 7.993520 | 127.6631 | 86.44 | 0.010960 | 0.37 |
rand()|Ubuntu 15.10| 7.992890 | 127.6763 | 56.82 | \-0.004945 | 1.15 |
DPRNG seeded by mcrypt_create_iv() *(a)*|Ubuntu 15.10| 7.993482 | 127.8451 | 84.77 | \-0.000205 | 2.43 |
mcrypt_create_iv()*(b)*|Ubuntu 15.10| 7.993261 | 127.4429 | 73.70 | 0.000831 | 0.4 |

*You can run these tests yourself with the ent executable and the entTestfile.php script.*  
*(a) source = MCRYPT_DEV_RANDOM*  
*(b) source = MCRYPT_DEV_URANDOM*

## Methods
Here is a full list of the methods supported by `tmarsteel\dprng\DPRNG` objects and their signatures + contracts:

**`float next()`**  
Returns an uniformly distributed float value in the range `[0, 1)` (0 inclusive to 1 exclusive).

**`float nextFloat(float $from, float $to)`**  
Returns an uniformly distributed float value in the range `[$from, $to)` (`$from` inclusive to `$to` exclusive).

**`int nextInt(int $from, int $to)`**  
Returns an uniformly distributed integer in the range `[$from, $to]` (`$from` inclusive to `$to` inclusive).

**`array nextBytes(int $n)`**  
Returns an array of length `$n`. Each entry is a uniformly distributed integer between 0 and 255.
