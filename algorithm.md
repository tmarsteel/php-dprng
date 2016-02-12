# The Algorithm

The DPRNG is based on a simply hash / shuffling function. That hash function is used to advance the internal state of the RNG at least once for each random number request.

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

Horizontal headline: the least significant 4 bits of the input in hexadecimal notation.
Vertical headline: the most significant 4 bits of the input in hexadecimal notation.
 
|      |00|01|02|03|04|05|06|07|08|09|0A|0B|0C|0D|0E|0F|
|**00**|63|7c|77|7b|f2|6b|6f|c5|30|01|67|2b|fe|d7|ab|76|
|**01**|ca|82|c9|7d|fa|59|47|f0|ad|d4|a2|af|9c|a4|72|c0|
|**02**|b7|fd|93|26|36|3f|f7|cc|34|a5|e5|f1|71|d8|31|15|
|**03**|04|c7|23|c3|18|96|05|9a|07|12|80|e2|eb|27|b2|75|
|**04**|09|83|2c|1a|1b|6e|5a|a0|52|3b|d6|b3|29|e3|2f|84|
|**05**|53|d1|00|ed|20|fc|b1|5b|6a|cb|be|39|4a|4c|58|cf|
|**06**|d0|ef|aa|fb|43|4d|33|85|45|f9|02|7f|50|3c|9f|a8|
|**07**|51|a3|40|8f|92|9d|38|f5|bc|b6|da|21|10|ff|f3|d2|
|**08**|cd|0c|13|ec|5f|97|44|17|c4|a7|7e|3d|64|5d|19|73|
|**09**|60|81|4f|dc|22|2a|90|88|46|ee|b8|14|de|5e|0b|db|
|**0A**|e0|32|3a|0a|49|06|24|5c|c2|d3|ac|62|91|95|e4|79|
|**0B**|e7|c8|37|6d|8d|d5|4e|a9|6c|56|f4|ea|65|7a|ae|08|
|**0C**|ba|78|25|2e|1c|a6|b4|c6|e8|dd|74|1f|4b|bd|8b|8a|
|**0D**|70|3e|b5|66|48|03|f6|0e|61|35|57|b9|86|c1|1d|9e|
|**0E**|e1|f8|98|11|69|d9|8e|94|9b|1e|87|e9|ce|55|28|df|
|**0F**|8c|a1|89|0d|bf|e6|42|68|41|99|2d|0f|b0|54|bb|16|

The Rijndael S-Box can also be implemented as a 1-dimensional array with 256 entries (index 00 to FF):

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