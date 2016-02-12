# php-dprng

Generate deterministic pesudo-random numbers, increase the seucrity of `rand()`. This implementation is particularly helpful when deterministic random sequences are required to be consistent across multiple platforms and languages. It relys only on simple bit operations that can easily be implemented consistently across many scripting languages (e.g. JavaScript, Python, PHP, Ruby)

A JavaScript implementation that generates equal sequences for the same salts/seeds can be found [here](//github.com/tmarsteel/jsdprng)

Please contribute with your own implementation in your favourite language! A description of the algorithm can be found in [algorithm.md](algorithm.md). I`ll cross-link compliant forks :)

## Usage
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
[ENT](http://www.fourmilab.ch/random/) is a neat program to inspect the entropy and randomness of a sequence of bytes or bits. This table compares its output with values returned from rand(), mcrypt_create_iv() and this DPRNG. It shows that the numbers generated by `tmarsteel.DPRNG` are significantly more secure/random than those generated by `rand()`. If determinism of the sequence is not required, `mcrypt_create_iv()` is still the better choice.
For explanations on the metrics see [the ENT Website](http://www.fourmilab.ch/random/)

| RNG | OS   | Entropy | Arithmetic mean | Chi-Square % | Correlation coefficient | Monte-Carlo PI error % |
| :-- | :--- | ------: | --------------: | -----------: | ----------------------: | ---------------------: |
rand()|Windows 8.1| yet to be tested | yet to be tested | yet to be tested | yet to be tested | yet to be tested |
DPRNG seeded by mcrypt_create_iv()|Windows 8.1| yet to be tested | yet to be tested | yet to be tested | yet to be tested | yet to be tested |
mcrypt_create_iv()|Windows 8.1| yet to be tested | yet to be tested | yet to be tested | yet to be tested | yet to be tested |
rand()|Windows 10| yet to be tested | yet to be tested | yet to be tested | yet to be tested | yet to be tested |
DPRNG seeded by mcrypt_create_iv()|Windows 10| yet to be tested | yet to be tested | yet to be tested | yet to be tested | yet to be tested |
mcrypt_create_iv()|Windows 10| yet to be tested | yet to be tested | yet to be tested | yet to be tested | yet to be tested |
rand()|Ubuntu 15.10| yet to be tested | yet to be tested | yet to be tested | yet to be tested | yet to be tested |
DPRNG seeded by mcrypt_create_iv()|Ubuntu 15.10| yet to be tested | yet to be tested | yet to be tested | yet to be tested | yet to be tested |
mcrypt_create_iv()|Ubuntu 15.10| yet to be tested | yet to be tested | yet to be tested | yet to be tested | yet to be tested |

*The test-files can be found in the [ent-test directory](ent-test). You can run these tests yourself with the ent executable and the entTestfile.php script.*

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