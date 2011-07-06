<?php
/*
 *	Sample implementation
 */

/**
*	A very quick and rough PHP class to scrape data from google+
*	Copyright (C) 2011  Mabujo
*	http://plusdevs.com
*
*	This program is free software: you can redistribute it and/or modify
*	it under the terms of the GNU General Public License as published by
*	the Free Software Foundation, either version 3 of the License, or
*	(at your option) any later version.
*
*	This program is distributed in the hope that it will be useful,
*	but WITHOUT ANY WARRANTY; without even the implied warranty of
*	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*	GNU General Public License for more details.
*
*	You should have received a copy of the GNU General Public License
*	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
?>

<!DOCTYPE html>
<html dir="ltr" lang="en-US">
<head>
<meta charset="UTF-8" />
<title>Google+ cards</title>
<link rel="stylesheet" type="text/css" media="all" href="style.css" />
</head>
<body>
<?php
// put your google+ id here :
$plus_id = '106189723444098348646';

// include our scraper class
include_once('plus_cards.php');

// initiate an instance of our scraper class
$plus = new googleCard($plus_id);

// enable caching (off by default)
$plus->cache_data = 0;

// do the scrape
$data = $plus->googleCard();

// if we have data, show the output
if (isset($data) && !empty($data['name']) && !empty($data['count']) && !empty($data['img']))
{
	echo $data['name'] . ' is followed by ' . $data['count'] . ' people <br />';
	echo '<img src="' . $data['img'] . '" width="80" height="80" />';
	echo '<br /><br />';
	?>
	<div id="plus_card">
		<div id="plus_card_image">
			<a href="<?php echo $data['url']; ?>">
				<?php echo '<img src="' . $data['img'] . '" width="80" height="80" />'; ?>
			</a>
		</div>
		<div id="plus_card_name">
			<a href="<?php echo $data['url']; ?>"><?php echo $data['name'] ?></a>
		</div>
		<span id="plus_card_add">
			<a href="<?php echo $data['url']; ?>">Add to circles</a>
		</span>
		<div id="plus_card_count">
			<p>In <?php echo $data['count']; ?> people's circles</p>
		</div>
	</div>
<?php
}
// else show an error
else
{
	echo 'Couldn\'t get data from google+';
}
?>