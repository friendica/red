<?php
/**
 * Name: Calculator App
 * Description: Simple Calculator Application
 * Version: 1.0
 * Author: Mike Macgirvin <http://macgirvin.com/profile/mike>
 */


function calc_install() {
	register_hook('app_menu', 'addon/calc/calc.php', 'calc_app_menu');
}

function calc_uninstall() {
	unregister_hook('app_menu', 'addon/calc/calc.php', 'calc_app_menu');

}

function calc_app_menu($a,&$b) {
	$b['app_menu'] .= '<div class="app-title"><a href="calc">Calculator</a></div>'; 
}


function calc_module() {}




function calc_init($a) {

$x = <<< EOT

<script language="JavaScript">
/**************************************
 * www.FemaleNerd.com         *
 **************************************/

// Declare global variables
var displayText = ""
var num1
var num2
var operatorType

// Write to display
function addDisplay(n){
   id = document.getElementById("display");
id.value = ""
displayText += n
id.value = displayText
}

// Addition
function addNumbers() {
if (displayText == "") {
  displayText = result
 }
num1 = parseFloat(displayText)
operatorType = "add"
displayText = ""
}

// Subtraction
function subtractNumbers() {
if (displayText == "") {
  displayText = result
 }
num1 = parseFloat(displayText)
operatorType = "subtract"
displayText = ""
}

// Multiplication
function multiplyNumbers() {
if (displayText == "") {
  displayText = result
 }
num1 = parseFloat(displayText)
operatorType = "multiply"
displayText = ""
}

// Division
function divideNumbers() {
if (displayText == "") {
  displayText = result
 }
num1 = parseFloat(displayText)
operatorType = "divide"
displayText = ""
}

// Sine
function sin() {
   id = document.getElementById("display");
if (displayText == "") {
  num1 = result
  }
else {
  num1 = parseFloat(displayText)
  }
if (num1 != "") {
  result = Math.sin(num1)
  id.value = result
  displayText = ""
  }
else {
  alert("Please write the number first")
  }
}

// Cosine
function cos() {
   id = document.getElementById("display");
if (displayText == "") {
  num1 = result
  }
else {
  num1 = parseFloat(displayText)
  }
if (num1 != "") {
  result = Math.cos(num1)
  id.value = result
  displayText = ""
  }
else {
  alert("Please write the number first")
  }
}

// ArcSine
function arcSin() {
   id = document.getElementById("display");
if (displayText == "") {
  num1 = result
  }
else {
  num1 = parseFloat(displayText)
  }
if (num1 != "") {
  result = Math.asin(num1)
  id.value = result
  displayText = ""
  }
else {
  alert("Please write the number first")
  }
}

// ArcCosine
function arcCos() {
   id = document.getElementById("display");
if (displayText == "") {
  num1 = result
  }
else {
  num1 = parseFloat(displayText)
  }
if (num1 != "") {
  result = Math.acos(num1)
  id.value = result
  displayText = ""
  }
else {
  alert("Please write the number first")
  }
}

// Square root
function sqrt() {
   id = document.getElementById("display");
if (displayText == "") {
  num1 = result
  }
else {
  num1 = parseFloat(displayText)
  }
if (num1 != "") {
  result = Math.sqrt(num1)
  id.value = result
  displayText = ""
  }
else {
  alert("Please write the number first")
  }
}

// Square number (number to the power of two)
function square() {
   id = document.getElementById("display");
if (displayText == "") {
  num1 = result
  }
else {
  num1 = parseFloat(displayText)
  }
if (num1 != "") {
  result = num1 * num1
  id.value = result
  displayText = ""
  }
else {
  alert("Please write the number first")
  }
}

// Convert degrees to radians
function degToRad() {
   id = document.getElementById("display");
if (displayText == "") {
  num1 = result
  }
else {
  num1 = parseFloat(displayText)
  }
if (num1 != "") {
  result = num1 * Math.PI / 180
  id.value = result
  displayText = ""
  }
else {
  alert("Please write the number first")
  }
}

// Convert radians to degrees
function radToDeg() {
   id = document.getElementById("display");
if (displayText == "") {
  num1 = result
  }
else {
  num1 = parseFloat(displayText)
  }
if (num1 != "") {
  result = num1 * 180 / Math.PI
  id.value = result
  displayText = ""
  }
else {
  alert("Please write the number first")
  }
}

// Calculations
function calculate() {
   id = document.getElementById("display");

if (displayText != "") {
  num2 = parseFloat(displayText)
// Calc: Addition
  if (operatorType == "add") {
    result = num1 + num2
    id.value = result
    }
// Calc: Subtraction
  if (operatorType == "subtract") {
    result = num1 - num2
    id.value = result
    }
// Calc: Multiplication
  if (operatorType == "multiply") {
    result = num1 * num2
    id.value = result
    }
// Calc: Division
  if (operatorType == "divide") {
    result = num1 / num2
    id.value = result
    }
  displayText = ""
  }
  else {
  id.value = "Oops! Error!"
  }
}

// Clear the display
function clearDisplay() {
   id = document.getElementById("display");

displayText = ""
id.value = ""
}
</script>

EOT;
$a->page['htmlhead'] .= $x;
}

function calc_content($app) {

$o = '';

$o .=  <<< EOT

<h3>Calculator</h3>
<br /><br />
<table>
<tbody><tr><td> 
<table bgcolor="#af9999" border="1">
<tbody><tr><td>
<table border="1" cellpadding="2" cellspacing="2">
<form name="calc">
<!--
<TR><TD VALIGN=top colspan=6 ALIGN="center"> <H2>Calculator</H2> </TD>
-->
<tbody><tr>
	<td colspan="5"><input size="22" id="display" name="display" type="text"></td>
</tr><tr align="left" valign="middle">
	<td><input name="one" value="&nbsp;&nbsp;1&nbsp;&nbsp;&nbsp;" onclick="addDisplay(1)" type="button"></td>
	<td><input name="two" value="&nbsp;&nbsp;2&nbsp;&nbsp;&nbsp;" onclick="addDisplay(2)" type="button"></td>
	<td><input name="three" value="&nbsp;&nbsp;3&nbsp;&nbsp;&nbsp;" onclick="addDisplay(3)" type="button"></td>
	<td><input name="plus" value="&nbsp;&nbsp;+&nbsp;&nbsp;&nbsp;" onclick="addNumbers()" type="button"></td>
</tr><tr align="left" valign="middle">
	<td><input name="four" value="&nbsp;&nbsp;4&nbsp;&nbsp;&nbsp;" onclick="addDisplay(4)" type="button"></td>
	<td><input name="five" value="&nbsp;&nbsp;5&nbsp;&nbsp;&nbsp;" onclick="addDisplay(5)" type="button"></td>
	<td><input name="six" value="&nbsp;&nbsp;6&nbsp;&nbsp;&nbsp;" onclick="addDisplay(6)" type="button"></td>
	<td><input name="minus" value="&nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;&nbsp;" onclick="subtractNumbers()" type="button"></td>
</tr><tr align="left" valign="middle">
	<td><input name="seven" value="&nbsp;&nbsp;7&nbsp;&nbsp;&nbsp;" onclick="addDisplay(7)" type="button"></td>
	<td><input name="eight" value="&nbsp;&nbsp;8&nbsp;&nbsp;&nbsp;" onclick="addDisplay(8)" type="button"></td>
	<td><input name="nine" value="&nbsp;&nbsp;9&nbsp;&nbsp;&nbsp;" onclick="addDisplay(9)" type="button"></td>
	<td><input name="multiplication" value="&nbsp;&nbsp;*&nbsp;&nbsp;&nbsp;&nbsp;" onclick="multiplyNumbers()" type="button"></td>
</tr><tr align="left" valign="middle">
	<td><input name="zero" value="&nbsp;&nbsp;0&nbsp;&nbsp;&nbsp;" onclick="addDisplay(0)" type="button"></td>
	<td><input name="pi" value="&nbsp;Pi&nbsp;&nbsp;" onclick="addDisplay(Math.PI)" type="button"> </td> 
	<td><input name="dot" value="&nbsp;&nbsp;&nbsp;.&nbsp;&nbsp;&nbsp;" onclick='addDisplay(".")' type="button"></td>
	<td><input name="division" value="&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;" onclick="divideNumbers()" type="button"></td>
</tr><tr align="left" valign="middle">
	<td><input name="sqareroot" value="sqrt" onclick="sqrt()" type="button"></td>
	<td><input name="squarex" value=" x^2" onclick="square()" type="button"></td>
	<td><input name="deg-rad" value="d2r&nbsp;" onclick="degToRad()" type="button"></td>
	<td><input name="rad-deg" value="r2d&nbsp;" onclick="radToDeg()" type="button"></td>
</tr><tr align="left" valign="middle">
	<td><input name="sine" value="&nbsp;sin&nbsp;" onclick="sin()" type="button"></td>
	<td><input name="arcsine" value="asin" onclick="arcSin()" type="button"></td>
	<td><input name="cosine" value="cos" onclick="cos()" type="button"></td>
	<td><input name="arccosine" value="acs" onclick="arcCos()" type="button"></td>

</tr><tr align="left" valign="middle">
	<td colspan="2"><input name="clear" value="&nbsp;&nbsp;Clear&nbsp;&nbsp;" onclick="clearDisplay()" type="button"></td>
	<td colspan="3"><input name="enter" value="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;=&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" onclick="calculate()" type="button"></td>

</tr></tbody></table>
</form>

	<!--
	<TD VALIGN=top> 
		<B>NOTE:</B> All sine and cosine calculations are
		<br>done in radians. Remember to convert first
		<br>if using degrees.
	</TD>
	-->
	
</td></tr></tbody></table>


</td></tr></tbody></table>

EOT;
return $o;

}
