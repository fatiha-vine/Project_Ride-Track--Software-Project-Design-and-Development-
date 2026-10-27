<?php
// Paste your Google Maps API key here (Maps JavaScript API + Places API (New) enabled).
// Restrict the key to your website (HTTP referrers), e.g. localhost/* while testing.
define('GMAPS_KEY','PASTE_YOUR_KEY_HERE');
function gmaps_ready(){return GMAPS_KEY!==''&&GMAPS_KEY!=='PASTE_YOUR_KEY_HERE';}
function gmaps_script($cb='initMap'){return '<script async src="https://maps.googleapis.com/maps/api/js?key='.rawurlencode(GMAPS_KEY).'&libraries=places&loading=async&callback='.$cb.'"></script>';}
