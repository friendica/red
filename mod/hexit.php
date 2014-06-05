<?php













function hexit_content(&$a) {


$o .= <<< EOT

<script type="text/javascript">
/**
 * A function for converting hex <-> dec w/o loss of precision.
 *
 * The problem is that parseInt("0x12345...") isn't precise enough to convert
 * 64-bit integers correctly.
 *
 * Internally, this uses arrays to encode decimal digits starting with the least
 * significant:
 * 8 = [8]
 * 16 = [6, 1]
 * 1024 = [4, 2, 0, 1]
 */

// Adds two arrays for the given base (10 or 16), returning the result.
// This turns out to be the only "primitive" operation we need.
function add(x, y, base) {
  var z = [];
  var n = Math.max(x.length, y.length);
  var carry = 0;
  var i = 0;
  while (i < n || carry) {
    var xi = i < x.length ? x[i] : 0;
    var yi = i < y.length ? y[i] : 0;
    var zi = carry + xi + yi;
    z.push(zi % base);
    carry = Math.floor(zi / base);
    i++;
  }
  return z;
}

// Returns a*x, where x is an array of decimal digits and a is an ordinary
// JavaScript number. base is the number base of the array x.
function multiplyByNumber(num, x, base) {
  if (num < 0) return null;
  if (num == 0) return [];

  var result = [];
  var power = x;
  while (true) {
    if (num & 1) {
      result = add(result, power, base);
    }
    num = num >> 1;
    if (num === 0) break;
    power = add(power, power, base);
  }

  return result;
}

function parseToDigitsArray(str, base) {
  var digits = str.split('');
  var ary = [];
  for (var i = digits.length - 1; i >= 0; i--) {
    var n = parseInt(digits[i], base);
    if (isNaN(n)) return null;
    ary.push(n);
  }
  return ary;
}

function convertBase(str, fromBase, toBase) {
  var digits = parseToDigitsArray(str, fromBase);
  if (digits === null) return null;

  var outArray = [];
  var power = [1];
  for (var i = 0; i < digits.length; i++) {
    // invariant: at this point, fromBase^i = power
    if (digits[i]) {
      outArray = add(outArray, multiplyByNumber(digits[i], power, toBase), toBase);
    }
    power = multiplyByNumber(fromBase, power, toBase);
  }

  var out = '';
  for (var i = outArray.length - 1; i >= 0; i--) {
    out += outArray[i].toString(toBase);
  }
  return out;
}

function decToHex(decStr) {
  var hex = convertBase(decStr, 10, 16);
  return hex ? '0x' + hex : null;
}

function hexToDec(hexStr) {
  if (hexStr.substring(0, 2) === '0x') hexStr = hexStr.substring(2);
  hexStr = hexStr.toLowerCase();
  return convertBase(hexStr, 16, 10);
}



    function str_or_null(x) {
      return x === null ? 'null' : x;
    }

    // "1.234e+5" -> "12340"
    function expandExponential(x) {
      var pos = x.indexOf("e");
      if (pos === -1) pos = x.indexOf("E");
      if (pos === -1) return x;

      var base = x.substring(0, pos);
      var pow = parseInt(x.substring(pos + 1), 10);
      if (pow < 0) return x;  // not supported.

      var dotPos = base.indexOf('.');
      if (dotPos === -1) dotPos = base.length;

      var ret = base.replace('.', '');
      while (ret.length < dotPos + pow) ret += '0';
      return ret;
    }

    function boldDifference(correct, actual) {
      for (var i = 0, j = 0; i < correct.length && j < actual.length; i++, j++) {
        if (correct[i] !== actual[j]) {
          break;
        }
      }
      if (j < actual.length) {
        return actual.substring(0, j) + '<b>' + actual.substring(j) + '</b>';
      } else {
        return actual;
      }
    }

    function convert() {
      var input = document.getElementById("in").value;
      if (input) {
        var aHex = str_or_null(decToHex(input));
        var aDec = str_or_null(hexToDec(input));
        var bHex = '0x' + (parseInt(input, 10)).toString(16);
        var bDec = "" + expandExponential("" +parseInt(input, 16));

        var html = '<p></p><p>To Decimal(' + input + ') = ' + aDec + '</p>';
        html += '<p>To Hex(' + input + ') = ' + aHex + '</p>';
        document.getElementById('result').innerHTML = html;
      }
    }
    convert();


</script>

	<h2>Hexit</h2>

 <p>Type in a hex or decimal string:</p>
  <input type="text" size=40 id="in" onkeyup="convert()" value="" />
  <p id="result"></p>



EOT;

return $o;
}
