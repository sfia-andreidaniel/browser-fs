<?php

# KCAPTCHA configuration file

$alphabet = "0123456789abcdefghijklmnopqrstuvwxyz"; # do not change without changing font files!

# symbols used to draw CAPTCHA
//$allowed_symbols = "0123456789"; #digits
$allowed_symbols = "23456789abcdeghkmnpqsuvxyz"; #alphabet without similar symbols (o=0, 1=l, i=j, t=f)

# folder with fonts
$fontsdir = 'fonts';

# CAPTCHA string length
$length = mt_rand(5,6); # random 5 or 6
//$length = 6;

# CAPTCHA image size (you do not need to change it, whis parameters is optimal)
$width = 115;
$height = 40;

# symbol's vertical fluctuation amplitude divided by 2
$fluctuation_amplitude = 0;

# increase safety by prevention of spaces between symbols
$no_spaces = true;

# show credits
$show_credits = false; # set to false to remove credits line. Credits adds 12 pixels to image height
$credits = ''; # if empty, HTTP_HOST will be shown

# CAPTCHA image colors (RGB, 0-255)
//$foreground_color = array(0, 0, 0);
//$background_color = array(220, 230, 255);
$foreground_color = array(29, 74, 173);
$background_color = array(220, 220, 220);

# JPEG quality of CAPTCHA image (bigger is better quality, but larger file size)
$jpeg_quality = 100;
?>