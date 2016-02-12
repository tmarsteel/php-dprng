# The Algorithm

The DPRNG is based on a simply hash / shuffling function. That hash function is used to advance the internal state of the RNG at least once for each random number request.

Test-Vectors are appended to this document.

## Terminology / Notation

All operations consider the values to be represented in Big-Endian encoding (that is: MSB to LSB). All integer litaraly are written in hexadecimal notation.

The following table denotes the bitwise operation notation used in this document. It is the same as used in many modern programming languages; you can skip this if you are familiar with the bitwise operations C-Like languages.

| Notation   | Operation |
| ---------: | :-------  |
|`a ^ b`     | bitwise `a` XOR `b` |
|`a & b`     | bitwise `a` AND `b` |
|`a | b`     | bitwise `a` OR `b` 
|`a % b`     | `a` mod `b` |
|`abs(x)`    | the absolute value of `x` (otherwise also noted as `|x|`) |
|`min(a, b)` | evaluates to the lesser value of `a` and `b`: `a < b? a : b` |
|`ceil(x)`   | The value of `x` rounded to the next integer greater than `x` |
|`pow(x, e)` | `x` to the power of `e` |
|`log(x, b)` | The logarithm of the value `x` to base `b` |
|`a >> x`    | shift the bits of `a` `x` places to the right. Discards the `x` rightmost bits of `a` |
|`a << x`    | shift the bits of `a` `x` places to the left. Discards the `x` leftmost bits of `a` |

## The hasing / shuffling function

The hash function maps a 28-bit integer to another 28-bit integer. It internally uses the Rijndael S-Box. Appendix 1 contains the Rijndael S-Box. Usage of the S-Box will be denoted as `sbox(i)` where `i` is an 8-bit integer.

Let the 28bit input to the function be `in`. Repeat the following steps five times:

1. Let `a` be the bits 0 to 3 of `in`
2. Let `b` be the bits 4 to 11 of `in`
3. Let `c` be the bits 12 to 19 of `in`
4. Let `d` be the bits 20 to 27 of `in`
5. Substitude `b`, `c` and `d` with the S-Box to `b'`, `c'` and `d'` respectively.
	1. `b' = sbox(b)`
	2. `c' = sbox(c)`
	3. `d' = sbox(d)`
6. Recombine `a`, `b'`, `c'` and `d'` to an 28-bit integer and store the result in `in`
	1. `in = (d' << 20) | (c' << 12) | (b' << 4) | a`
7. Multiply `in` by 7
8. Take the absolute value of `in` (drop the sign, if any)
9. Calculate `in % FFFFFFF` and store the result in `in`

### Pseudo-Code

```
function hash(in)
	repeat 5 times:
		a :=  in & F;
        b := (in >> 4) & FF;
        c := (in >> 12) & FF;
        d := (in >> 20) & FF;
		
		b' := sbox(b)
		c' := sbox(c)
		d' := sbox(d)

		in := (d' << 20) | (c' << 12) | (b' << 4) | a

		in := |in * 7| % FFFFFFF
	endRepeat

	return in
```

## RNG algorithms

The RNG keeps an internal state value of 28bits (because the underlying hashing/shuffling function operates on 28bits of input/output). Initial seeds to the RNG have to be 28bit in size. The internal state is initialized with those 28bits of input.

If no seed is given the RNG should capture 28 random bits from the highest-quality source available (e.g. `/dev/random` on UNIX or PHPs `mcrypt_create_iv()`).

In addition to the 28bit seed value the RNG also keeps a 28bit counter value that is incremented by at least 1 with each generation request (see below for details). 

### advance function

The RNG is based on a function called `advance` which derives 28 bits from the internal state, mutates the inner state, increments the counter and returns the previously derived 28 bits. This is the pesudo code for the `advance`-Routine where `state` denotes the 28 bit internal state value, `counter` denotes the 28 bit counter and `hash(?)` denotes the hash function as described above.

```
function advance()
	h := hash(state ^ counter)
        
    state := state ^ hash(state)
    
    counter := counter + 1

    if counter is greater than FFFFFFF
	then
        counter := 0
    end if
    
    return h
```

### Generating random numbers

The base function of the RNG is the function `nextInt(a, b)` that generates an uniformly distributed integer value of up to 32 bits in the range `[a, b]` (a inclusive to b inclusive). All other generation functions are derived from this function.

#### nextInt(a, b)

Input: `a`, `b` 32 bit integer values with `a < b`

Generates and returns a pseudo-random, uniformly distributed integer value in the range `[a, b]`.

```
function nextInt(a, b)
	rangeSize := b - a;
        
	nRequiredBits := min(ceil(log(rangeSize, 2)), 20)
	result := 0
	
	if nRequiredBits is greater than 20
	then
	    additionalBits := 20 - nRequiredBits
	    mask := pow(2, additionalBits) - 1
	    
	    result := ((advance() << additionalBits) | (advance() & mask);
	else
	    result := advance() & (pow(2, nRequiredBits) - 1)
	end if
	
	while (a + result) is greater than b
	do
		result:= result / 2
	end while
	
	return result
```

### next()

Generates and returns a pseudo-random, uniformly distributed integer value in the range `[0, 1)`.

```
function next()
	return nextInt(0, FFFFFFF) / FFFFFFF
```

### nextDoulbe(a, b) alias nextFloat(a, b)

Input: `a`, `b` floating-point values with `a < b`

Generates and returns a pseudo-random, uniformly distributed value in the range `[a, b)`.

```
function nextFloat(a, b) alias nextDouble(a, b)
	return min + next() * (b - a)
```

### nextBytes(n)

Input: `n` an arbitrarily sized integer greater than 0.

Generates and returns `n` pseudo-random and uniformly distributed 8-bit integers each in the range `[0, 255]`

```
function nextBytes(n)
	ar := new dynamic list

	repeat n times
		append nextInt(0, 255) to ar
	end repeat

	return ar
``` 

## Appendix 1: Rijndael S-Box

The AES S-Box uniquely (that is: without collisions) maps a 8-bit integer to another 8-bit integer.

The Rijndael S-Box can be implemented as a 1-dimensional array with 256 entries (index 00 to FF). The value of `sbox(x)` can then be determined by looking up the `x`th element from the array.

```
63, 7c, 77, 7b, f2, 6b, 6f, c5, 30, 01, 67, 2b, fe, d7, ab, 76,
ca, 82, c9, 7d, fa, 59, 47, f0, ad, d4, a2, af, 9c, a4, 72, c0,
b7, fd, 93, 26, 36, 3f, f7, cc, 34, a5, e5, f1, 71, d8, 31, 15,
04, c7, 23, c3, 18, 96, 05, 9a, 07, 12, 80, e2, eb, 27, b2, 75,
09, 83, 2c, 1a, 1b, 6e, 5a, a0, 52, 3b, d6, b3, 29, e3, 2f, 84,
53, d1, 00, ed, 20, fc, b1, 5b, 6a, cb, be, 39, 4a, 4c, 58, cf,
d0, ef, aa, fb, 43, 4d, 33, 85, 45, f9, 02, 7f, 50, 3c, 9f, a8,
51, a3, 40, 8f, 92, 9d, 38, f5, bc, b6, da, 21, 10, ff, f3, d2,
cd, 0c, 13, ec, 5f, 97, 44, 17, c4, a7, 7e, 3d, 64, 5d, 19, 73,
60, 81, 4f, dc, 22, 2a, 90, 88, 46, ee, b8, 14, de, 5e, 0b, db,
e0, 32, 3a, 0a, 49, 06, 24, 5c, c2, d3, ac, 62, 91, 95, e4, 79,
e7, c8, 37, 6d, 8d, d5, 4e, a9, 6c, 56, f4, ea, 65, 7a, ae, 08,
ba, 78, 25, 2e, 1c, a6, b4, c6, e8, dd, 74, 1f, 4b, bd, 8b, 8a,
70, 3e, b5, 66, 48, 03, f6, 0e, 61, 35, 57, b9, 86, c1, 1d, 9e,
e1, f8, 98, 11, 69, d9, 8e, 94, 9b, 1e, 87, e9, ce, 55, 28, df,
8c, a1, 89, 0d, bf, e6, 42, 68, 41, 99, 2d, 0f, b0, 54, bb, 16
```

## Appendix 2: Test-Vectors

You can use these test-vectors to test implementations of this algorithm. Every test vector denotes the first 20, the 40th to 50th and the 90th to 100th value of `nextInt(0, 255)` 

### Salt / initial state: `0000000`

| index | value |
| ----: | :---- |
|`0`|`cc`|
|`1`|`68`|
|`2`|`2d`|
|`3`|`9c`|
|`4`|`13`|
|`5`|`73`|
|`6`|`27`|
|`7`|`52`|
|`8`|`2a`|
|`9`|`83`|
|`10`|`5f`|
|`11`|`b6`|
|`12`|`36`|
|`13`|`de`|
|`14`|`b5`|
|`15`|`7b`|
|`16`|`88`|
|`17`|`3e`|
|`18`|`58`|
|`19`|`77`|
|`--`|`--`|
|`39`|`c5`|
|`40`|`92`|
|`41`|`f3`|
|`42`|`c1`|
|`43`|`aa`|
|`44`|`78`|
|`45`|`f1`|
|`46`|`a1`|
|`47`|`c6`|
|`48`|`29`|
|`49`|`95`|
|`--`|`--`|
|`89`|`19`|
|`90`|`51`|
|`91`|`29`|
|`92`|`56`|
|`93`|`fa`|
|`94`|`55`|
|`95`|`ff`|
|`96`|`70`|
|`97`|`9c`|
|`98`|`be`|
|`99`|`36`|

### Salt / initial state `48615de`
| index | value |
| ----: | :---- |
|`0`|`b6`|
|`1`|`11`|
|`2`|`bc`|
|`3`|`fd`|
|`4`|`cb`|
|`5`|`32`|
|`6`|`e1`|
|`7`|`c7`|
|`8`|`f1`|
|`9`|`3e`|
|`10`|`97`|
|`11`|`f0`|
|`12`|`44`|
|`13`|`68`|
|`14`|`1d`|
|`15`|`9f`|
|`16`|`68`|
|`17`|`45`|
|`18`|`41`|
|`19`|`dc`|
|`--`|`--`|
|`39`|`b4`|
|`40`|`c9`|
|`41`|`0d`|
|`42`|`9d`|
|`43`|`e5`|
|`44`|`d3`|
|`45`|`3b`|
|`46`|`77`|
|`47`|`eb`|
|`48`|`75`|
|`49`|`75`|
|`--`|`--`|
|`89`|`03`|
|`90`|`51`|
|`91`|`de`|
|`92`|`9d`|
|`93`|`0e`|
|`94`|`0e`|
|`95`|`3b`|
|`96`|`de`|
|`97`|`82`|
|`98`|`4b`|
|`99`|`49`|

### Salt / initial state `b40e86e`

| index | value |
| ----: | :---- |
|`0`|`6d`|
|`1`|`b8`|
|`2`|`05`|
|`3`|`00`|
|`4`|`a5`|
|`5`|`cc`|
|`6`|`3a`|
|`7`|`e2`|
|`8`|`cf`|
|`9`|`5d`|
|`10`|`61`|
|`11`|`bb`|
|`12`|`6e`|
|`13`|`69`|
|`14`|`e1`|
|`15`|`b4`|
|`16`|`c3`|
|`17`|`66`|
|`18`|`18`|
|`19`|`6b`|
|`--`|`--`|
|`39`|`b1`|
|`40`|`aa`|
|`41`|`4d`|
|`42`|`ce`|
|`43`|`36`|
|`44`|`89`|
|`45`|`38`|
|`46`|`7a`|
|`47`|`97`|
|`48`|`10`|
|`49`|`34`|
|`--`|`--`|
|`89`|`ea`|
|`90`|`0a`|
|`91`|`a1`|
|`92`|`0c`|
|`93`|`9e`|
|`94`|`48`|
|`95`|`66`|
|`96`|`fe`|
|`97`|`e3`|
|`98`|`96`|
|`99`|`14`|

### Salt / initial state `331838c`

| index | value |
| ----: | :---- |
|`0`|`72`|
|`1`|`74`|
|`2`|`d6`|
|`3`|`0b`|
|`4`|`18`|
|`5`|`3b`|
|`6`|`82`|
|`7`|`65`|
|`8`|`8f`|
|`9`|`73`|
|`10`|`da`|
|`11`|`2d`|
|`12`|`ad`|
|`13`|`6d`|
|`14`|`da`|
|`15`|`06`|
|`16`|`23`|
|`17`|`37`|
|`18`|`52`|
|`19`|`a6`|
|`--`|`--`|
|`39`|`8e`|
|`40`|`14`|
|`41`|`ba`|
|`42`|`e8`|
|`43`|`d2`|
|`44`|`94`|
|`45`|`bb`|
|`46`|`46`|
|`47`|`f3`|
|`48`|`d4`|
|`49`|`52`|
|`--`|`--`|
|`89`|`51`|
|`90`|`23`|
|`91`|`59`|
|`92`|`da`|
|`93`|`30`|
|`94`|`8f`|
|`95`|`b0`|
|`96`|`96`|
|`97`|`f0`|
|`98`|`4a`|
|`99`|`15`|