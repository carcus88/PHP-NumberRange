<?php
require('../NumberRange.php');

$nr = new NumberRange('!1..2,3..4,-10..-5');

print "Scalar Range is ". $nr->range() . "\n";
if($nr->negated()) {
    print "Range is negated\n";
} else {
    print "Range is not negated\n";
}
print "Array Range is ". print_r($nr->range(true), true) . "\n";
$number = 4;
if ($nr->inrange($number)) {
    print "$number is in range\n";
} else {
    print "$number is NOT in range\n";
}

$number = 1;
if ($nr->inrange($number)) {
    print "$number is in range\n";
} else {
    print "$number is NOT in range\n";
}

$number = -5;
if ($nr->inrange($number)) {
    print "$number is in range\n";
} else {
    print "$number is NOT in range\n";
}


# Send as array
$nr = new NumberRange("10..20","25..30");
print "Scalar Range is ". $nr->range() . "\n";
print "Array Range is ". print_r($nr->range(true), true) . "\n";

$number = 10;
if ($nr->inrange($number)) {
    print "$number is in range\n";
} else {
    print "$number is NOT in range\n";
}

print "Range size is " . $nr->size()."\n";


# Large TEst
$nr = new NumberRange("1..9999");
$number = 99;
if ($nr->inrange($number)) {
    print "$number is in range\n";
} else {
    print "$number is NOT in range\n";
}
print "Scalar Range is ". $nr->range() . "\n";
print "Array Range is ". print_r($nr->range(true), true) . "\n";


?>
