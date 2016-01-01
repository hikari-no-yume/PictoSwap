<?php
declare(strict_types=1);

namespace ajf\PictoSwap;

// From http://www.brandonheyer.com/2013/03/27/convert-hsl-to-rgb-and-rgb-to-hsl-via-php/
function hslToRgb( float $h, float $s, float $l ): array {
    $c = ( 1 - abs( 2 * $l - 1 ) ) * $s;
    $x = $c * ( 1 - abs( fmod( ( $h / 60 ), 2 ) - 1 ) );
    $m = $l - ( $c / 2 );
 
    if ( $h < 60 ) {
        $r = $c;
        $g = $x;
        $b = 0;
    } else if ( $h < 120 ) {
        $r = $x;
        $g = $c;
        $b = 0;            
    } else if ( $h < 180 ) {
        $r = 0;
        $g = $c;
        $b = $x;                    
    } else if ( $h < 240 ) {
        $r = 0;
        $g = $x;
        $b = $c;
    } else if ( $h < 300 ) {
        $r = $x;
        $g = 0;
        $b = $c;
    } else {
        $r = $c;
        $g = 0;
        $b = $x;
    }
 
    $r = ( $r + $m ) * 255;
    $g = ( $g + $m ) * 255;
    $b = ( $b + $m  ) * 255;
 
    return array( floor( $r ), floor( $g ), floor( $b ) );
}

function CSSColourToGd(array &$colourCache, $image, string $colour) {
    if (\array_key_exists($colour, $colourCache)) {
        return $colourCache[$colour];
    }

    if ($colour === 'black') {
        return $colourCache['black'] = \imageColorAllocate($image, 0, 0, 0);
    } else if ($colour === 'white') {
        return $colourCache['white'] = \imageColorAllocate($image, 255, 255, 255);
    } else if (\substr($colour, 0, 4) === 'hsl(') {
        // remove 'hsl(' and ')'
        $colour = \substr($colour, 4, -1);
        // split by commas
        list($hue, $saturation, $lightness) = \explode(',', $colour);
        $hue = (float)$hue;
        // trim whitespace to left of number, trim off percent sign, divide
        $saturation = ((float)\rtrim(\ltrim($saturation), '%')) / 100;
        $lightness = ((float)\rtrim(\ltrim($lightness), '%')) / 100;
        list($red, $green, $blue) = hslToRgb($hue, $saturation, $lightness);
        return $colourCache[$colour] = \imageColorAllocate($image, (int)$red, (int)$green, (int)$blue);
    }
}

function renderLetterPreviews(\StdClass $letter): array {
    $pageImages = [];

    $background = \imageCreateFromPNG('backgrounds/' . $letter->background);
    foreach ($letter->pages as $page) {
        // No use rendering empty pages
        if (\count($page) === 0) {
            continue;
        }
        $image = \imageCreateTrueColor(PAGE_WIDTH, PAGE_HEIGHT);
        $colourCache = [];
        \imageCopy($image, $background, 0, 0, 0, 0, PAGE_WIDTH, PAGE_HEIGHT);
        foreach ($page as $stroke) {
            foreach ($stroke as $segment) {
                $colour = CSSColourToGd($colourCache, $image, $segment->colour);
                switch ($segment->type) {
                    case 'dot':
                        \imageFilledRectangle($image, (int)($segment->x - 1.5), (int)($segment->y - 1.5), (int)($segment->x + 1.5), (int)($segment->y + 1.5), $colour);
                    break;
                    case 'line':
                        \imageSetThickness($image, 3);
                        \imageLine($image, (int)$segment->from_x, (int)$segment->from_y, (int)$segment->x, (int)$segment->y, $colour);
                    break;
                }
            }
        }
        $pageImages[] = $image;
    }
    return $pageImages;
}
