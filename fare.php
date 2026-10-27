<?php
// Fare rules live in one place. `require 'config.php';` must come first.
// Admin settings table e (k,v) niche er key gulo dile rate bodlano jay, na dile default.
function fare_rate($type='local'){return (float)setting($type=='local'?'fare_per_km_local':'fare_per_km_intercity',$type=='local'?2.5:2.6);}
function fare_min($type='local'){return (float)setting($type=='local'?'fare_min_local':'fare_min_intercity',$type=='local'?10:100);}
function fare_for_km($km,$type='local'){return max(fare_min($type),round($km*fare_rate($type)));}
function student_fare_amount($full){return max(5,round($full*(100-(int)setting('student_pct',50))/100,2));}
// $u logged in user (me()). Verified student hole discount fare, noile full fare.
function fare_for_user($full,$u){return ($u&&function_exists('is_student')&&is_student($u))?student_fare_amount($full):$full;}
