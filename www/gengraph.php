<?php
/*
 * $Id$
 * 
 * (c) 2008 Monzoon Networks AG. All rights reserved.
 */

require_once('func.inc');

$as = $_GET['as'];
if (!preg_match("/^[0-9a-z]+$/", $as))
	die("Invalid AS");

header("Content-Type: image/png");

$width = 500;
$height = 300;
if ($_GET['width'])
	$width = (int)$_GET['width'];
if ($_GET['height'])
	$height = (int)$_GET['height'];

$knownlinks = getknownlinks();

$cmd = "$rrdtool graph - " .
	"--slope-mode --alt-autoscale --imgformat=PNG --base=1000 --height=$height --width=$width " .
	"--color BACK#ffffff00 --color SHADEA#ffffff00 --color SHADEB#ffffff00 ";

if ($_GET['nolegend'])
	$cmd .= "--no-legend ";

if ($_GET['start'] && is_numeric($_GET['start']))
	$cmd .= "--start " . $_GET['start'] . " ";

if ($_GET['end'] && is_numeric($_GET['end']))
	$cmd .= "--end " . $_GET['end'] . " ";

/* geneate RRD DEFs */
foreach ($knownlinks as $link) {
	$cmd .= "DEF:{$link['tag']}_in=\"$rrdpath/$as.rrd\":{$link['tag']}_in:AVERAGE ";
	$cmd .= "DEF:{$link['tag']}_out=\"$rrdpath/$as.rrd\":{$link['tag']}_out:AVERAGE ";
}

/* generate a CDEF for each DEF to multiply by 8 (bytes to bits), and reverse for outbound */
foreach ($knownlinks as $link) {
	$cmd .= "CDEF:{$link['tag']}_in_bits={$link['tag']}_in,8,* ";
	$cmd .= "CDEF:{$link['tag']}_out_bits_rev={$link['tag']}_out,-8,* ";
}

/* generate graph area/stack for inbound */
$i = 0;
foreach ($knownlinks as $link) {
	$cmd .= "AREA:{$link['tag']}_in_bits#{$link['color']}:\"{$link['descr']}\"";
	if ($i > 0)
		$cmd .= ":STACK";
	$cmd .= " ";
	$i++;
}

/* generate graph area/stack for outbound */
$i = 0;
foreach ($knownlinks as $link) {
	$cmd .= "AREA:{$link['tag']}_out_bits_rev#{$link['color']}BB:";
	if ($i > 0)
		$cmd .= ":STACK";
	$cmd .= " ";
	$i++;
}

# zero line
$cmd .= "HRULE:0#00000080";

passthru($cmd);

exit;

?>
